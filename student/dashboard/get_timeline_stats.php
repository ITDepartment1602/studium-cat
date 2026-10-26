<?php
// Timeline stats for the Statistics section — powers the bar/line chart
// (accuracy % + questions answered per day/week/month). Mirrors the
// Trad+NGN split used elsewhere on this dashboard (get_stats.php, index.php).
include '../../config.php';
$user_id = $_SESSION['user_id'] ?? 0;

header('Content-Type: application/json');

if (!$user_id) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$mode   = $_GET['mode']   ?? 'overall'; // overall | trad | ngn
$period = $_GET['period'] ?? 'day';     // day | week | month
if (!in_array($mode, ['overall', 'trad', 'ngn'], true))   $mode   = 'overall';
if (!in_array($period, ['day', 'week', 'month'], true))   $period = 'day';

$uid = intval($user_id);

// MySQL DATE_FORMAT pattern per bucket granularity
$bucketFmt = [
    'day'   => '%Y-%m-%d',
    'week'  => '%x-W%v',   // ISO year-week, e.g. 2026-W26
    'month' => '%Y-%m',
];
$fmt = $bucketFmt[$period];

$quizCon = getQuizConnection();
mysqli_query($quizCon, "SET time_zone = '+08:00'");
$db = db()->getConnection();

// ── Traditional buckets (history table = one row per full attempt) ──
$tradBuckets = [];
if ($mode === 'overall' || $mode === 'trad') {
    $r = mysqli_query($quizCon, "
        SELECT DATE_FORMAT(date, '$fmt') as bucket,
               SUM(sahi) as correct, SUM(wrong) as wrong, COUNT(*) as attempts
        FROM `history`
        WHERE email = '$uid' AND kilanlan = 'NARC Intermediate and Advance QBanks'
        GROUP BY bucket
        ORDER BY MIN(date) ASC
    ");
    while ($row = mysqli_fetch_assoc($r)) {
        $tradBuckets[$row['bucket']] = [
            'correct'  => (int)$row['correct'],
            'wrong'    => (int)$row['wrong'],
            'attempts' => (int)$row['attempts'],
        ];
    }
}

// ── NGN buckets (exam_results = one row per question, group by examTaken's first timestamp) ──
$ngnBuckets = [];
if ($mode === 'overall' || $mode === 'ngn') {
    $stmt = $db->prepare("
        SELECT DATE_FORMAT(timestamp, ?) as bucket,
               SUM(isCorrect) as correct, COUNT(*) - SUM(isCorrect) as wrong,
               COUNT(DISTINCT examTaken) as attempts
        FROM exam_results
        WHERE student_id = ?
        GROUP BY bucket
        ORDER BY MIN(timestamp) ASC
    ");
    $stmt->bind_param('si', $fmt, $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $ngnBuckets[$row['bucket']] = [
            'correct'  => (int)$row['correct'],
            'wrong'    => (int)$row['wrong'],
            'attempts' => (int)$row['attempts'],
        ];
    }
    $stmt->close();
}

// ── Merge buckets chronologically ──
$allBucketKeys = array_unique(array_merge(array_keys($tradBuckets), array_keys($ngnBuckets)));
sort($allBucketKeys);

$timeline = [];
foreach ($allBucketKeys as $key) {
    $t = $tradBuckets[$key] ?? ['correct' => 0, 'wrong' => 0, 'attempts' => 0];
    $n = $ngnBuckets[$key]  ?? ['correct' => 0, 'wrong' => 0, 'attempts' => 0];

    if ($mode === 'trad') { $c = $t['correct']; $w = $t['wrong']; $a = $t['attempts']; }
    elseif ($mode === 'ngn') { $c = $n['correct']; $w = $n['wrong']; $a = $n['attempts']; }
    else { $c = $t['correct'] + $n['correct']; $w = $t['wrong'] + $n['wrong']; $a = $t['attempts'] + $n['attempts']; }

    $total = $c + $w;
    $timeline[] = [
        'label'    => $key,
        'correct'  => $c,
        'wrong'    => $w,
        'answered' => $total,
        'accuracy' => $total > 0 ? round($c / $total * 100) : null,
        'attempts' => $a,
    ];
}

// Cap to the most recent 24 buckets so the chart stays readable
if (count($timeline) > 24) {
    $timeline = array_slice($timeline, -24);
}

echo json_encode([
    'timeline' => $timeline,
]);
