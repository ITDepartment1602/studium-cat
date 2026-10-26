<?php
date_default_timezone_set('Asia/Manila');

define('SHEETS_SERVICE_ACCOUNT_FILE', __DIR__ . '/../.private/service-account.json');
define('SHEETS_SPREADSHEET_ID', '1t3W0nTNdZhNKAJbwzuvnud38ZJzZ0xQPe0j6U4CTdGE');
define('SHEETS_RENEW_TAB', 'STUDIUM-RENEW');
define('SHEETS_RENEW_GID', 1457698022);
define('SHEETS_HIGHLIGHT_COLOR', [
    'red'   => 0xD0 / 255,
    'green' => 0xE0 / 255,
    'blue'  => 0xE3 / 255,
]);

function sheets_b64url($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function sheets_get_access_token(): string {
    static $token = null;
    if ($token !== null) return $token;

    $sa = json_decode(file_get_contents(SHEETS_SERVICE_ACCOUNT_FILE), true);

    $now = time();
    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $claims = [
        'iss'   => $sa['client_email'],
        'scope' => 'https://www.googleapis.com/auth/spreadsheets',
        'aud'   => 'https://oauth2.googleapis.com/token',
        'exp'   => $now + 3600,
        'iat'   => $now,
    ];

    $segments = sheets_b64url(json_encode($header)) . '.' . sheets_b64url(json_encode($claims));
    openssl_sign($segments, $signature, $sa['private_key'], 'SHA256');
    $jwt = $segments . '.' . sheets_b64url($signature);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]),
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    if (empty($data['access_token'])) {
        throw new Exception('Failed to obtain Sheets access token: ' . $response);
    }

    $token = $data['access_token'];
    return $token;
}

function sheets_api_request(string $method, string $url, $body = null): array {
    $ch = curl_init($url);
    $opts = [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . sheets_get_access_token(),
            'Content-Type: application/json',
        ],
    ];
    if ($body !== null) {
        $opts[CURLOPT_POSTFIELDS] = json_encode($body);
    }
    curl_setopt_array($ch, $opts);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);
    if ($httpCode < 200 || $httpCode >= 300) {
        throw new Exception("Sheets API error ({$httpCode}): {$response}");
    }
    return $data;
}

// Compares a cell background color against SHEETS_HIGHLIGHT_COLOR (#D0E0E3)
function sheets_color_is_given(?array $bg): bool {
    if ($bg === null) return false;

    $target = SHEETS_HIGHLIGHT_COLOR;
    $tol = 0.01;

    $r = $bg['red'] ?? 0;
    $g = $bg['green'] ?? 0;
    $b = $bg['blue'] ?? 0;

    return abs($r - $target['red']) < $tol
        && abs($g - $target['green']) < $tol
        && abs($b - $target['blue']) < $tol;
}

// Parses subscription length text like "1 MONTH", "2 MONTHS", "1 MONTH FREE"
function sheets_parse_subscription(string $text): ?array {
    if (!preg_match('/(\d+)\s*MONTHS?/i', $text, $m)) {
        return null;
    }
    return [
        'months' => (int)$m[1],
        'free'   => (stripos($text, 'FREE') !== false),
    ];
}

// Maps a parsed subscription to a tally_helper.php counter key, or null if unmapped
function sheets_subscription_tally_key(array $sub): ?string {
    $months = $sub['months'];

    if ($sub['free']) {
        switch ($months) {
            case 1:  return 'added_1month_free';
            case 2:  return 'added_2months_free';
            default: return null;
        }
    }

    switch ($months) {
        case 1:  return 'added_1month';
        case 2:  return 'added_2months';
        case 3:  return 'added_3months';
        case 6:  return 'added_6months';
        case 12: return 'added_12months';
        default: return null;
    }
}

// Reads a cell's background colour, preferring the explicitly-set format but
// falling back to the effective (rendered) format so highlights applied via
// alternating-colour banding or a theme are still detected.
function sheets_row_background(array $values): ?array {
    return $values[0]['userEnteredFormat']['backgroundColor']
        ?? $values[0]['effectiveFormat']['backgroundColor']
        ?? null;
}

// Field mask shared by the two read helpers below.
const SHEETS_GRID_FIELDS = 'sheets.data.rowData.values(formattedValue,userEnteredFormat.backgroundColor,effectiveFormat.backgroundColor)';

/**
 * Scans STUDIUM-RENEW for rows that still need access given (column A not yet highlighted).
 *
 * Returns ['pending' => [...], 'skipped' => [...]].  Rows that look like real
 * entries but cannot be actioned (missing student number, unrecognised
 * subscription text) are reported in 'skipped' with a reason instead of being
 * dropped silently — a silently dropped row is a renewal nobody ever sees.
 */
function sheets_get_renewal_pending(): array {
    // Open-ended range: the sheet grows past its current row count over time and
    // a hard upper bound (previously H1000) would hide every row beyond it.
    $range  = rawurlencode(SHEETS_RENEW_TAB . '!A3:H');
    $fields = rawurlencode(SHEETS_GRID_FIELDS);
    $url = 'https://sheets.googleapis.com/v4/spreadsheets/' . SHEETS_SPREADSHEET_ID
         . '?ranges=' . $range . '&includeGridData=true&fields=' . $fields;

    $data = sheets_api_request('GET', $url);
    $rows = $data['sheets'][0]['data'][0]['rowData'] ?? [];

    return sheets_classify_renewal_rows($rows);
}

/**
 * Splits raw STUDIUM-RENEW rowData (starting at sheet row 3) into actionable
 * pending renewals and rows that need a human to fix the sheet.
 *
 * Kept separate from the HTTP call so the filtering rules can be exercised
 * directly against sample rows.
 */
function sheets_classify_renewal_rows(array $rows): array {
    $pending = [];
    $skipped = [];

    foreach ($rows as $i => $row) {
        $values = $row['values'] ?? [];
        $cell = fn($col) => trim($values[$col]['formattedValue'] ?? '');

        $rowIndex = $i + 2;   // A3 -> 0-based sheet row index 2
        $sheetRow = $i + 3;   // 1-based row number, for display

        $studentNumber = $cell(1); // column B
        $fullName      = $cell(2); // column C
        $subLabel      = $cell(7); // column H

        // A row with no student number AND no name is a date divider or blank
        // spacer — those are structural, not missed renewals.
        if ($studentNumber === '' && $fullName === '') continue;

        if (sheets_color_is_given(sheets_row_background($values))) continue; // already processed

        if ($studentNumber === '') {
            $skipped[] = [
                'sheet_row' => $sheetRow,
                'full_name' => $fullName,
                'reason'    => 'No student number in column B.',
            ];
            continue;
        }

        $sub = sheets_parse_subscription($subLabel);
        if ($sub === null) {
            $skipped[] = [
                'sheet_row' => $sheetRow,
                'full_name' => $fullName,
                'reason'    => $subLabel === ''
                    ? 'Subscription (column H) is blank.'
                    : 'Subscription "' . $subLabel . '" is not recognised — expected text like "1 MONTH".',
            ];
            continue;
        }

        $pending[] = [
            'row_index'          => $rowIndex,
            'sheet_row'          => $sheetRow,
            'student_number'     => $studentNumber,
            'full_name'          => $fullName,
            'fb_name'            => $cell(3),
            'gmail'              => $cell(4),
            'subscription_label' => $subLabel,
            'months'             => $sub['months'],
            'free'               => $sub['free'],
        ];
    }

    return ['pending' => $pending, 'skipped' => $skipped];
}

// Re-reads a single row's student number + given status (race-condition check)
function sheets_get_renewal_row_status(int $rowIndex): array {
    $rowNum = $rowIndex + 1; // 0-based -> 1-based
    $range  = rawurlencode(SHEETS_RENEW_TAB . "!A{$rowNum}:H{$rowNum}");
    $fields = rawurlencode(SHEETS_GRID_FIELDS);
    $url = 'https://sheets.googleapis.com/v4/spreadsheets/' . SHEETS_SPREADSHEET_ID
         . '?ranges=' . $range . '&includeGridData=true&fields=' . $fields;

    $data = sheets_api_request('GET', $url);
    $values = $data['sheets'][0]['data'][0]['rowData'][0]['values'] ?? [];

    return [
        'student_number' => trim($values[1]['formattedValue'] ?? ''),
        'is_given'       => sheets_color_is_given(sheets_row_background($values)),
    ];
}

// Highlights a STUDIUM-RENEW row (columns A:H) with SHEETS_HIGHLIGHT_COLOR
function sheets_mark_renewal_given(int $rowIndex): void {
    $body = [
        'requests' => [[
            'repeatCell' => [
                'range' => [
                    'sheetId'          => SHEETS_RENEW_GID,
                    'startRowIndex'    => $rowIndex,
                    'endRowIndex'      => $rowIndex + 1,
                    'startColumnIndex' => 0,
                    'endColumnIndex'   => 8,
                ],
                'cell' => [
                    'userEnteredFormat' => [
                        'backgroundColor' => SHEETS_HIGHLIGHT_COLOR,
                    ],
                ],
                'fields' => 'userEnteredFormat.backgroundColor',
            ],
        ]],
    ];

    $url = 'https://sheets.googleapis.com/v4/spreadsheets/' . SHEETS_SPREADSHEET_ID . ':batchUpdate';
    sheets_api_request('POST', $url, $body);
}
