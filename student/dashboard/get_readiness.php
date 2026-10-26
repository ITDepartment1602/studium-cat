<?php
// ── Overall NCLEX Readiness, scoped to a time period (day / week / month) ──
// Powers the date-aware Overall readiness cards on the dashboard.
//
// The dashboard "resets" to 0% each midnight simply because the DEFAULT view is
// the current day — no data is ever deleted. Students can backtrack to any past
// day / week / month to review the progress they made.
//
// For each concept the card shows:
//   • big %      = AVERAGE accuracy of every exam taken in the selected period
//   • Last 3     = the 3 most recent exams within that period (oldest → newest)
// combining Traditional (history) + NGN (exam_results), mirroring how the rest
// of the Overall view blends both banks.
include '../../config.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}
$uid = intval($user_id);

$period = $_GET['period'] ?? 'day';
if (!in_array($period, ['day', 'week', 'month'], true)) $period = 'day';

// ── Resolve the period window [start, end) in Manila time ──
$tz    = new DateTimeZone('Asia/Manila');
$today = new DateTime('now', $tz);
$today->setTime(0, 0, 0);

$dateStr = $_GET['date'] ?? '';
try {
    $anchor = $dateStr !== '' ? new DateTime($dateStr, $tz) : clone $today;
} catch (Exception $e) {
    $anchor = clone $today;
}
$anchor->setTime(0, 0, 0);
if ($anchor > $today) $anchor = clone $today; // never navigate into the future

if ($period === 'day') {
    $start = clone $anchor;
    $end   = (clone $anchor)->modify('+1 day');
} elseif ($period === 'week') {
    // ISO week: snap back to Monday
    $start = clone $anchor;
    $dow   = (int)$start->format('N'); // 1 = Mon … 7 = Sun
    $start->modify('-' . ($dow - 1) . ' days');
    $end   = (clone $start)->modify('+7 days');
} else { // month
    $start = (clone $anchor)->modify('first day of this month');
    $start->setTime(0, 0, 0);
    $end   = (clone $start)->modify('+1 month');
}

$startS = $start->format('Y-m-d H:i:s');
$endS   = $end->format('Y-m-d H:i:s');

// The selected period is the "current" one when it contains today → block "next"
$isCurrent = ($start <= $today && $today < $end);

// ── Human-readable range label ──
if ($period === 'day') {
    if ($start == $today) {
        $rangeLabel = 'Today';
    } elseif ($start == (clone $today)->modify('-1 day')) {
        $rangeLabel = 'Yesterday';
    } else {
        $rangeLabel = $start->format('M j, Y');
    }
} elseif ($period === 'week') {
    $lastDay    = (clone $end)->modify('-1 day');
    $rangeLabel = $start->format('M j') . ' – ' . $lastDay->format('M j, Y');
} else {
    $rangeLabel = $start->format('F Y');
}

// ── Concept → icon maps (kept in sync with student/dashboard/index.php) ──
$readinessConceptMap = [
    'Adult Health'                => 'fa-solid fa-user',
    'Child Health'                => 'fa-solid fa-baby',
    'Critical Care'               => 'fa-solid fa-heart-pulse',
    'Fundamentals'                => 'fa-solid fa-book-medical',
    'Leadership and Management'   => 'fa-solid fa-user-tie',
    'Mental Health'               => 'fa-solid fa-brain',
    'Pharmacology'                => 'fa-solid fa-pills',
    'Maternal and Newborn Health' => 'fa-solid fa-baby-carriage',
];
$ngnConceptMap = [
    'Critical Care'          => 'fa-solid fa-heart-pulse',
    'Endocrine'              => 'fa-solid fa-syringe',
    'Fluid and Electrolytes' => 'fa-solid fa-droplet',
    'Fundamentals'           => 'fa-solid fa-book-medical',
    'Heart Failure'          => 'fa-solid fa-heart-crack',
    'Pharmacology'           => 'fa-solid fa-pills',
    'Respiratory'            => 'fa-solid fa-lungs',
];
// Overall card order: Traditional concepts first, then NGN-only (matches index.php)
$iconMap = $readinessConceptMap;
foreach ($ngnConceptMap as $c => $ic) {
    if (!isset($iconMap[$c])) $iconMap[$c] = $ic;
}

// ── Collect per-concept exam events (one entry per exam) within the window ──
// $events[concept] = [ ['ts' => unixTime, 'acc' => 0-100], ... ]
$events = [];
foreach (array_keys($iconMap) as $c) $events[$c] = [];

// Traditional: history table (one row = one concept exam, score is already a %)
$quizCon = getQuizConnection();
mysqli_query($quizCon, "SET time_zone = '+08:00'");
$startEsc = mysqli_real_escape_string($quizCon, $startS);
$endEsc   = mysqli_real_escape_string($quizCon, $endS);
$tq = mysqli_query($quizCon, "
    SELECT eid AS concept, score, date
    FROM `history`
    WHERE email = '$uid'
      AND kilanlan = 'NARC Intermediate and Advance QBanks'
      AND date >= '$startEsc' AND date < '$endEsc'
    ORDER BY date ASC
");
while ($tq && $row = mysqli_fetch_assoc($tq)) {
    $c = $row['concept'];
    if (!isset($events[$c])) continue; // ignore concepts not on the readiness grid
    $events[$c][] = ['ts' => strtotime($row['date']), 'acc' => (int)round((float)$row['score'])];
}

// NGN: exam_results table (one row = one question) grouped into exams by examTaken
$db   = db()->getConnection();
$stmt = $db->prepare("
    SELECT concept, examTaken,
           SUM(isCorrect) AS correct, COUNT(*) AS total, MIN(timestamp) AS ts
    FROM exam_results
    WHERE student_id = ? AND timestamp >= ? AND timestamp < ?
    GROUP BY concept, examTaken
    ORDER BY ts ASC
");
$stmt->bind_param('iss', $uid, $startS, $endS);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $c = $row['concept'];
    if (!isset($events[$c])) continue;
    $total = (int)$row['total'];
    $acc   = $total > 0 ? (int)round(($row['correct'] / $total) * 100) : 0;
    $events[$c][] = ['ts' => strtotime($row['ts']), 'acc' => $acc];
}
$stmt->close();

// ── Build per-concept readiness ──
$concepts   = [];
$readyCount = 0;
$attempted  = 0;
foreach ($iconMap as $concept => $icon) {
    $list = $events[$concept];
    usort($list, fn($a, $b) => $a['ts'] <=> $b['ts']); // chronological

    $attempts = count($list);
    if ($attempts > 0) {
        $accs   = array_column($list, 'acc');
        $avg    = (int)round(array_sum($accs) / $attempts);
        $status = $avg >= 75 ? 'ready' : ($avg >= 60 ? 'ontrack' : 'needs');
        // last 3 exams, oldest → newest (display order)
        $scores = array_map('intval', array_slice($accs, -3));
        $attempted++;
        if ($status === 'ready') $readyCount++;
    } else {
        $avg = 0; $status = 'none'; $scores = [];
    }

    $concepts[] = [
        'name'     => $concept,
        'icon'     => $icon,
        'avg'      => $avg,
        'status'   => $status,
        'attempts' => $attempts,
        'scores'   => $scores,
    ];
}

$totalConcepts = count($concepts);
if ($attempted === 0) {
    $overallStatus = 'none';
} elseif ($readyCount === $totalConcepts) {
    $overallStatus = 'ready';
} elseif ($readyCount >= 5) {
    $overallStatus = 'ontrack';
} else {
    $overallStatus = 'needs';
}

echo json_encode([
    'period'     => $period,
    'anchor'     => $anchor->format('Y-m-d'),
    'today'      => $today->format('Y-m-d'),
    'rangeLabel' => $rangeLabel,
    'isCurrent'  => $isCurrent,
    'concepts'   => $concepts,
    'summary'    => [
        'ready'     => $readyCount,
        'attempted' => $attempted,
        'total'     => $totalConcepts,
        'status'    => $overallStatus,
    ],
]);
