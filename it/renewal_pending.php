<?php
session_start();
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['admin_login'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Unauthorized. Please log in to the admin panel again.']);
    exit;
}

include 'sheets_helper.php';

header('Content-Type: application/json');

try {
    $result = sheets_get_renewal_pending();

    echo json_encode([
        'ok'            => true,
        'count'         => count($result['pending']),
        'students'      => $result['pending'],
        // Rows that look like real entries but could not be actioned. Surfaced
        // in the UI so a renewal is never dropped without the admin knowing.
        'skipped'       => $result['skipped'],
        'skipped_count' => count($result['skipped']),
        'source'        => SHEETS_RENEW_TAB,
    ]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
