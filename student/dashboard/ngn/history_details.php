<?php
require_once '../../../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);
$examTaken = isset($_GET['examTaken']) ? intval($_GET['examTaken']) : 0;
if (!$examTaken) { die("No exam selected."); }

// 1. Fetch Student Info (ownership: only own profile)
$user_data = db()->fetchOne(
    "SELECT studentnumber, fullname FROM login WHERE id = ? LIMIT 1",
    [$user_id]
);
$display_student_no = $user_data['studentnumber'] ?? 'N/A';
$display_fullname = $user_data['fullname'] ?? 'Student';

// 2. Fetch Results and Calculate Stats (ownership enforced via student_id = ?)
$statsRows = db()->fetchAll(
    "SELECT isCorrect, score, topic, omitted, changes_count
     FROM exam_results
     WHERE student_id = ? AND examTaken = ?",
    [$user_id, $examTaken]
);
$stats = ['total' => 0, 'correct' => 0, 'partial' => 0, 'wrong' => 0, 'total_score' => 0, 'topics' => [], 'omitted' => 0, 'changes_total' => 0];

foreach ($statsRows as $s_row) {
    $stats['total']++;
    $score = floatval($s_row['score']);
    $stats['total_score'] += $score;

    // Three-way categorization: correct / partial / wrong
    if (!empty($s_row['omitted'])) {
        $stats['omitted']++;
    } elseif ($score >= 1.00) {
        $stats['correct']++;
    } elseif ($score > 0.00) {
        $stats['partial']++;
    } else {
        $stats['wrong']++;
    }

    $stats['changes_total'] += intval($s_row['changes_count'] ?? 0);

    $topic = $s_row['topic'] ?: 'General';
    if (!isset($stats['topics'][$topic])) {
        $stats['topics'][$topic] = ['score' => 0, 'total' => 0];
    }
    $stats['topics'][$topic]['total']++;
    $stats['topics'][$topic]['score'] += $score;
}

$overall_percent = ($stats['total'] > 0) ? round(($stats['total_score'] / $stats['total']) * 100) : 0;

// 3. Fetch the Main Results List (ownership enforced via student_id = ?)
$results = db()->fetchAll(
    "SELECT * FROM exam_results
     WHERE student_id = ? AND examTaken = ?
     ORDER BY question_number ASC",
    [$user_id, $examTaken]
);

function getTypeColor($type) {
    $colors = [
        'highlight' => '#f59e0b',
        'bowtie' => '#8b5cf6',
        'mmr' => '#06b6d4',
        'mpr' => '#10b981',
        'dragndrop' => '#ec4899',
        'dropdown' => '#6366f1',
        'sata' => '#14b8a6',
        'column' => '#f97316',
        'traditional' => '#64748b'
    ];
    return $colors[$type] ?? '#94a3b8';
}

function table_exists_db($table) {
    $result = db()->fetchOne(
        "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
        [$table]
    );
    return $result !== null;
}

function resolveQuestionTable($questionType) {
    $type = strtolower(trim((string) $questionType));
    $candidatesByType = [
        'bowtie' => ['btq'],
        'btq' => ['btq'],
        'dropdown' => ['dropdown', 'dropdown_questions'],
        'highlight' => ['highlight'],
        'mmr' => ['mmr'],
        'mpr' => ['mpr'],
        'dragndrop' => ['dragndrop'],
        'sata' => ['sata'],
        'column' => ['column'],
        'traditional' => ['traditional'],
    ];

    $candidates = $candidatesByType[$type] ?? [$type];
    foreach ($candidates as $table) {
        if (table_exists_db($table)) return $table;
    }
    return null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Grid — Attempt #<?php echo htmlspecialchars($examTaken); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; color: #0f172a; -webkit-font-smoothing: antialiased; }

        /* ── NAVBAR (exam/result.php style) ── */
        .hd-navbar {
            height: 52px; background: #fff;
            border-bottom: 1px solid #e2e8f0;
            display: flex; align-items: center;
            padding: 0 40px; gap: 10px;
            position: static;
        }
        .hd-brand { display: flex; align-items: center; gap: 8px; text-decoration: none; flex: 1; }
        .hd-name  { font-size: 13px; font-weight: 700; color: #0f172a; letter-spacing: -.2px; }
        .hd-sep   { width: 1px; height: 14px; background: #e2e8f0; flex-shrink: 0; }
        .hd-ctx   { font-size: 11px; font-weight: 500; color: #94a3b8; }
        .hd-btn {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 11.5px; font-weight: 500; color: #64748b;
            text-decoration: none; padding: 5px 12px;
            border: 1px solid #e2e8f0; border-radius: 6px;
            background: #f8fafc; transition: all .15s; white-space: nowrap;
        }
        .hd-btn:hover { color: #0078b8; border-color: #bfdbfe; background: #eff6ff; }

        /* ── RESULT HEADER (exam/result.php style) ── */
        .hd-result-header {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            border-top: 3px solid #007CBF;
            padding: 28px 40px 0;
            animation: fadeUp .3s ease both;
        }
        .hd-rh-top { display: flex; align-items: flex-start; gap: 32px; padding-bottom: 24px; flex-wrap: wrap; }
        .hd-rh-left { flex: 1; min-width: 0; }
        .hd-eyebrow { font-size: 10px; font-weight: 600; letter-spacing: 1.2px; text-transform: uppercase; color: #94a3b8; margin-bottom: 10px; }
        .hd-verdict { font-size: 52px; font-weight: 800; letter-spacing: -2.5px; line-height: .95; margin-bottom: 8px; color: #007CBF; }
        .hd-verdict-sub { font-size: 13px; font-weight: 300; color: #64748b; line-height: 1.6; }

        /* stat strip */
        .hd-stat-strip {
            flex-shrink: 0; display: flex; align-items: stretch;
            border: 1px solid #e2e8f0; border-radius: 10px;
            overflow: hidden; background: #f8fafc; align-self: center;
        }
        .hd-stat-item { padding: 14px 20px; display: flex; flex-direction: column; gap: 4px; min-width: 100px; }
        .hd-stat-item + .hd-stat-item { border-left: 1px solid #e2e8f0; }
        .hd-stat-val { font-size: 22px; font-weight: 700; letter-spacing: -.5px; color: #0f172a; line-height: 1; }
        .hd-stat-lbl { font-size: 10px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .8px; }

        /* ── SCORE BADGE (replaces doughnut chart) ── */
        .hd-score-badge {
            width: 108px; height: 108px; border-radius: 50%;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            border: 4px solid #3b82f6; background: #eff6ff;
            flex-shrink: 0;
        }
        .hd-score-badge .hd-pct  { font-size: 26px; font-weight: 800; line-height: 1; color: #1d4ed8; }
        .hd-score-badge .hd-slbl { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: #3b82f6; margin-top: 3px; }

        /* ── CONTENT CARDS (exam/result.php style) ── */
        .hd-main-card {
            background: #fff; border: 1px solid #e2e8f0;
            border-radius: 12px; overflow: hidden;
        }
        .hd-main-card-head {
            padding: 13px 20px; border-bottom: 1px solid #f1f5f9;
            display: flex; align-items: center; justify-content: space-between;
        }
        .hd-card-lbl { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.1px; color: #94a3b8; }

        /* stat tiles for breakdown */
        .hd-stat-tiles { display: flex; gap: 0; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; background: #f8fafc; }
        .hd-tile { flex: 1; padding: 14px 16px; display: flex; flex-direction: column; gap: 3px; }
        .hd-tile + .hd-tile { border-left: 1px solid #e2e8f0; }
        .hd-tile-val { font-size: 22px; font-weight: 700; line-height: 1; }
        .hd-tile-lbl { font-size: 9.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .7px; color: #94a3b8; }
        .hd-tile-val.green { color: #10b981; }
        .hd-tile-val.amber { color: #f59e0b; }
        .hd-tile-val.red   { color: #ef4444; }
        .hd-tile-val.slate { color: #94a3b8; }

        /* topic mastery grid */
        .hd-topic-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px; }
        .hd-topic-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 14px; border-radius: 10px;
            background: #f8fafc; border: 1px solid #e2e8f0;
            font-size: 12px;
        }
        .hd-topic-name { font-weight: 600; color: #1e293b; flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-right: 8px; }
        .hd-topic-pct  { font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 6px; flex-shrink: 0; }

        /* table */
        .hd-table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
        .hd-table-head { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; }
        .data-grid-container { overflow-x: auto; }
        .data-grid-container table { width: 100%; border-collapse: collapse; min-width: 1100px; font-size: 12.5px; }
        .data-grid-container thead { background: #f8fafc; position: sticky; top: 0; z-index: 2; }
        .data-grid-container thead th { padding: 11px 14px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .9px; color: #94a3b8; border-bottom: 1px solid #e2e8f0; text-align: left; white-space: nowrap; }
        .data-grid-container tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .1s; }
        .data-grid-container tbody tr:hover { background: #f8fafc; }
        .data-grid-container tbody td { padding: 12px 14px; vertical-align: top; color: #334155; }

        .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        .type-pill {
            font-size: 10px; font-weight: 700; text-transform: uppercase;
            padding: 2px 8px; border-radius: 20px; display: inline-flex;
            align-items: center; border: 1px solid rgba(0,0,0,0.08);
            white-space: nowrap;
        }
        #viewModal { display: none; }

        /* ── Text expand modal ── */
        #textModal {
            display: none;
            position: fixed; inset: 0;
            background: rgba(15,23,42,.55);
            backdrop-filter: blur(3px);
            z-index: 9000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        #textModal.open { display: flex; }
        #textModal-box {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 24px 60px rgba(0,0,0,.22);
            width: 100%;
            max-width: 680px;
            max-height: 82vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            animation: fadeUp .22s ease both;
        }
        #textModal-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 20px; border-bottom: 1px solid #e2e8f0;
            background: #f8fafc; flex-shrink: 0;
        }
        #textModal-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .9px; color: #64748b; }
        #textModal-close {
            width: 32px; height: 32px; border-radius: 8px;
            border: none; background: #e2e8f0; color: #64748b;
            cursor: pointer; font-size: 18px; line-height: 1;
            display: flex; align-items: center; justify-content: center;
            transition: background .15s, color .15s;
        }
        #textModal-close:hover { background: #ef4444; color: #fff; }
        #textModal-body {
            flex: 1; overflow-y: auto; padding: 20px 24px 28px;
            font-size: 14px; line-height: 1.8; color: #1e293b;
            white-space: pre-wrap; word-break: break-word;
        }
        /* Truncation helper */
        .truncate-cell {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
        }
        .truncate-cell:hover { text-decoration: underline; text-decoration-color: #94a3b8; text-underline-offset: 3px; }
        .view-text-btn {
            display: inline-flex; align-items: center; gap: 4px;
            margin-top: 4px; font-size: 10.5px; font-weight: 600;
            color: #007cbf; cursor: pointer; background: none; border: none;
            padding: 0; font-family: inherit; white-space: nowrap;
        }
        .view-text-btn:hover { text-decoration: underline; }

        @keyframes fadeUp { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }

        /* ── RESPONSIVE ── */
        @media (max-width: 1024px) {
            .hd-main-body { padding: 20px 24px 40px !important; }
        }
        @media (max-width: 768px) {
            .hd-navbar { padding: 0 16px; }
            .hd-result-header { padding: 20px 16px 0; }
            .hd-verdict { font-size: 28px; letter-spacing: -1px; }
            .hd-stat-strip { flex-wrap: wrap; }
            .hd-stat-item { flex: 1 1 auto; min-width: 80px; }
            .hd-score-badge { width: 84px; height: 84px; }
            .hd-score-badge .hd-pct { font-size: 20px; }
            .hd-stat-tiles { flex-wrap: wrap; }
            .hd-tile { flex: 1 1 40%; }
            .hd-topic-grid { grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); }
            .hd-main-body { padding: 16px 16px 32px !important; }
        }
        @media (max-width: 480px) {
            .hd-navbar { height: 48px; }
            .hd-ctx { display: none; }
            .hd-rh-top { gap: 12px; }
            .hd-stat-strip { width: 100%; flex-wrap: wrap; }
            .hd-stat-item { flex: 1 1 50%; }
            .hd-stat-item:nth-child(3), .hd-stat-item:nth-child(4) { border-top: 1px solid #e2e8f0; }
            .hd-stat-item:nth-child(even) { border-left: 1px solid #e2e8f0; }
            .hd-main-body { padding: 12px 12px 24px !important; }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="pb-10">

<!-- ── NAVBAR ── -->
<nav class="hd-navbar">
    <a href="../index.php" class="hd-brand">
        <span class="hd-name">Studium</span>
    </a>
    <div class="hd-sep"></div>
    <span class="hd-ctx">Practice Exam Review</span>
    <a href="../history.php" class="hd-btn" style="margin-left:auto;"><i class="bi bi-arrow-left"></i> Back</a>
    <a href="../index.php" class="hd-btn"><i class="bi bi-house"></i> Home</a>
</nav>

<!-- ── RESULT HEADER ── -->
<div class="hd-result-header">
    <div class="hd-rh-top">
        <div class="hd-rh-left">
            <div class="hd-eyebrow">Practice Exam Review &mdash; Attempt #<?php echo htmlspecialchars($examTaken); ?></div>
            <div class="hd-verdict"><?php echo htmlspecialchars($display_fullname); ?></div>
            <div class="hd-verdict-sub">Student No: <?php echo htmlspecialchars($display_student_no); ?></div>
        </div>
        <div class="hd-stat-strip">
            <div class="hd-stat-item">
                <div class="hd-stat-val"><?php echo $stats['total']; ?></div>
                <div class="hd-stat-lbl">Questions</div>
            </div>
            <div class="hd-stat-item">
                <div class="hd-stat-val"><?php echo round($stats['total_score'], 1); ?></div>
                <div class="hd-stat-lbl">Score</div>
            </div>
            <div class="hd-stat-item">
                <div class="hd-stat-val"><?php echo $overall_percent; ?>%</div>
                <div class="hd-stat-lbl">Mastery</div>
            </div>
        </div>
    </div>
</div>

    <main style="max-width:1600px;margin:0 auto;padding:28px 40px 56px;" class="hd-main-body">

        <!-- ── STATS ROW ── -->
        <div style="display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap;margin-bottom:20px;">

            <!-- Score badge + total -->
            <div class="hd-main-card" style="display:flex;align-items:center;gap:20px;padding:22px 28px;flex-shrink:0;">
                <div class="hd-score-badge" style="border-color:<?php echo $overall_percent >= 75 ? '#10b981' : ($overall_percent >= 50 ? '#f59e0b' : '#ef4444'); ?>;background:<?php echo $overall_percent >= 75 ? '#f0fdf4' : ($overall_percent >= 50 ? '#fffbeb' : '#fef2f2'); ?>;">
                    <div class="hd-pct" style="color:<?php echo $overall_percent >= 75 ? '#059669' : ($overall_percent >= 50 ? '#d97706' : '#dc2626'); ?>;"><?php echo $overall_percent; ?>%</div>
                    <div class="hd-slbl" style="color:<?php echo $overall_percent >= 75 ? '#10b981' : ($overall_percent >= 50 ? '#f59e0b' : '#ef4444'); ?>;">Mastery</div>
                </div>
                <div>
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.9px;color:#94a3b8;margin-bottom:5px;">Total Score</div>
                    <div style="font-size:28px;font-weight:800;color:#0f172a;line-height:1;"><?php echo round($stats['total_score'], 1); ?> <span style="font-size:14px;font-weight:500;color:#94a3b8;">/ <?php echo $stats['total']; ?></span></div>
                </div>
            </div>

            <!-- Execution breakdown -->
            <div class="hd-main-card" style="flex:1;min-width:280px;">
                <div class="hd-main-card-head"><span class="hd-card-lbl">Execution Breakdown</span></div>
                <div style="padding:18px 20px;">
                    <div class="hd-stat-tiles" style="margin-bottom:14px;">
                        <div class="hd-tile"><div class="hd-tile-val green"><?php echo $stats['correct']; ?></div><div class="hd-tile-lbl">Perfect</div></div>
                        <div class="hd-tile"><div class="hd-tile-val amber"><?php echo $stats['partial']; ?></div><div class="hd-tile-lbl">Partial</div></div>
                        <div class="hd-tile"><div class="hd-tile-val red"><?php echo $stats['wrong']; ?></div><div class="hd-tile-lbl">Wrong</div></div>
                        <div class="hd-tile"><div class="hd-tile-val slate"><?php echo $stats['omitted']; ?></div><div class="hd-tile-lbl">Omitted</div></div>
                    </div>
                    <?php if ($stats['changes_total'] > 0): ?>
                    <div style="margin-bottom:12px;padding:8px 12px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;display:flex;align-items:center;justify-content:space-between;">
                        <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#d97706;">Answer Changes</span>
                        <span style="font-size:18px;font-weight:800;color:#d97706;"><?php echo $stats['changes_total']; ?></span>
                    </div>
                    <?php endif; ?>
                    <div style="height:6px;background:#f1f5f9;border-radius:4px;overflow:hidden;display:flex;">
                        <div style="width:<?php echo ($stats['total'] > 0 ? ($stats['correct']/$stats['total'])*100 : 0); ?>%;background:#10b981;"></div>
                        <div style="width:<?php echo ($stats['total'] > 0 ? ($stats['partial']/$stats['total'])*100 : 0); ?>%;background:#f59e0b;"></div>
                        <div style="width:<?php echo ($stats['total'] > 0 ? ($stats['wrong']/$stats['total'])*100 : 0); ?>%;background:#ef4444;"></div>
                        <div style="width:<?php echo ($stats['total'] > 0 ? ($stats['omitted']/$stats['total'])*100 : 0); ?>%;background:#cbd5e1;"></div>
                    </div>
                    <div style="font-size:10px;font-weight:600;color:#94a3b8;text-align:center;margin-top:6px;text-transform:uppercase;letter-spacing:.6px;">Performance Distribution</div>
                </div>
            </div>

            <!-- Topic mastery -->
            <div class="hd-main-card" style="flex:2;min-width:300px;">
                <div class="hd-main-card-head">
                    <span class="hd-card-lbl">Mastery by Category</span>
                    <span style="font-size:10px;font-weight:700;color:#3b82f6;background:#eff6ff;padding:2px 8px;border-radius:6px;text-transform:uppercase;"><?php echo count($stats['topics']); ?> Analyzed</span>
                </div>
                <div style="padding:16px 20px;">
                    <div class="hd-topic-grid custom-scrollbar" style="max-height:160px;overflow-y:auto;">
                        <?php foreach($stats['topics'] as $topic => $tstats):
                            $pct = ($tstats['total'] > 0) ? round(($tstats['score'] / $tstats['total']) * 100) : 0;
                            $tc  = $pct >= 80 ? '#059669' : ($pct >= 50 ? '#d97706' : '#dc2626');
                            $tbg = $pct >= 80 ? '#f0fdf4' : ($pct >= 50 ? '#fffbeb' : '#fef2f2');
                        ?>
                        <div class="hd-topic-item">
                            <span class="hd-topic-name" title="<?php echo htmlspecialchars($topic); ?>"><?php echo htmlspecialchars($topic); ?></span>
                            <span class="hd-topic-pct" style="color:<?php echo $tc; ?>;background:<?php echo $tbg; ?>;"><?php echo $pct; ?>%</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>

        <!-- ── QUESTION TABLE ── -->
        <div class="hd-table-wrap">
            <div class="hd-table-head">
                <span class="hd-card-lbl">Question-by-Question Review</span>
            </div>
            <div class="data-grid-container custom-scrollbar">
                <table class="w-full text-left border-collapse min-w-[1200px]">
                    <thead class="bg-slate-50 sticky top-0 z-10">
                        <tr class="text-[11px] uppercase tracking-widest text-slate-500 font-bold border-b border-slate-200">
                            <th class="p-4 w-12 text-center">#</th>
                            <th class="p-4 w-32">NGN Metrics</th>
                            <th class="p-4 w-1/3">Question Detail</th>
                            <th class="p-4">Topic / Category</th>
                            <th class="p-4">Your Answer</th>
                            <th class="p-4">Omitted/Initial</th>
                            <th class="p-4">Correct Key</th>
                            <th class="p-4">Analysis / Rationale</th>
                            <th class="p-4 text-center w-24">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-[13px]">
                        <?php
if (count($results) > 0):
    foreach ($results as $row):
        $score = floatval($row['score']);
        $isPerfect = $score >= 1.00;
        $scorePercent = round($score * 100);

        $uAnsRaw = json_decode($row['user_answer'], true);
        $cAnsRaw = json_decode($row['correct_answer'], true);
        $initialAnsRaw = isset($row['initial_answer']) && !empty($row['initial_answer']) ? json_decode($row['initial_answer'], true) : null;
        $changesData = isset($row['changes']) && !empty($row['changes']) ? json_decode($row['changes'], true) : null;

        $formatAns = function ($val) use (&$formatAns) {
            if (is_array($val)) {
                $keys = array_keys($val);
                $isAssoc = !empty($keys) && ($keys !== range(0, count($val) - 1));
                $parts = [];
                foreach ($val as $k => $v) {
                    $item = is_array($v) ? $formatAns($v) : htmlspecialchars((string)$v);
                    $parts[] = $isAssoc ? "<strong>$k</strong>: $item" : $item;
                }
                return implode("<br>", $parts);
            }
            if (is_bool($val)) return $val ? 'True' : 'False';
            return htmlspecialchars((string)($val ?: 'N/A'));
        };

        $uAnsDisplay = $formatAns($uAnsRaw);
        $cAnsDisplay = $formatAns($cAnsRaw);

        // Fetch question content dynamically
        $qTypeTable = resolveQuestionTable($row['question_type']);
        $rawUid = $row['question_uid'];
        if (strpos($rawUid, '-') !== false) {
            $parts = explode('-', $rawUid);
            $actualId = end($parts);
        } else {
            $actualId = $rawUid;
        }

        $displayQuestion = "Question content placeholder (ID: $actualId)";
        $displayRationale = isset($row['rationale']) ? $row['rationale'] : "No rationale provided.";
        $displayCNC = isset($row['cnc']) ? $row['cnc'] : "N/A";

        if (!empty($qTypeTable) && !empty($actualId)) {
            // $qTypeTable is whitelist-validated by resolveQuestionTable(). Only bind the user-controlled id.
            $q_data = db()->fetchOne(
                "SELECT * FROM `{$qTypeTable}` WHERE id = ? LIMIT 1",
                [intval($actualId)]
            );
            if ($q_data) {
                $displayQuestion = $q_data['question'] ?? ($q_data['passage'] ?? $displayQuestion);
                if (empty(trim((string) $displayRationale)) || $displayRationale === "No rationale provided." || $displayRationale === "No rationale available.") {
                    $displayRationale = $q_data['rationale'] ?? $displayRationale;
                }
                $displayCNC = $q_data['cnc'] ?? $displayCNC;
            }
        }

        $typeColor = getTypeColor($row['question_type']);
?>
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="p-4 text-center font-bold text-slate-300"><?php echo $row['question_number']; ?></td>
                            <td class="p-4">
                                <div class="flex flex-col gap-1.5">
                                    <span class="type-pill" style="color: <?php echo $typeColor; ?>; background: <?php echo $typeColor; ?>10; border-color: <?php echo $typeColor; ?>20;">
                                        <?php echo htmlspecialchars($row['question_type']); ?>
                                    </span>
                                    <?php if (!empty($row['omitted'])): ?>
                                        <span class="text-[10px] font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded">OMITTED</span>
                                    <?php endif; ?>
                                    <?php if (!empty($row['changes_count']) && $row['changes_count'] > 0): ?>
                                        <span class="text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded"><?php echo $row['changes_count']; ?> CHANGE<?php echo $row['changes_count'] > 1 ? 'S' : ''; ?></span>
                                    <?php endif; ?>
                                    <div class="flex items-center gap-2">
                                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                            <div class="h-full <?php echo $score >= 0.75 ? 'bg-green-500' : ($score >= 0.5 ? 'bg-amber-500' : 'bg-red-500'); ?>" 
                                                 style="width: <?php echo $scorePercent; ?>%"></div>
                                        </div>
                                        <span class="text-[10px] font-extrabold <?php echo $score >= 0.75 ? 'text-green-600' : ($score >= 0.5 ? 'text-amber-600' : 'text-red-600'); ?>">
                                            <?php echo $scorePercent; ?>%
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4">
                                <?php $qPreview = mb_strimwidth(strip_tags($displayQuestion), 0, 120, '…'); ?>
                                <div class="leading-relaxed text-slate-800 font-semibold truncate-cell"
                                     onclick="openTextModal('Question Detail', <?php echo htmlspecialchars(json_encode($displayQuestion), ENT_QUOTES); ?>)">
                                    <?php echo htmlspecialchars($qPreview); ?>
                                </div>
                                <?php if (mb_strlen(strip_tags($displayQuestion)) > 120): ?>
                                <button class="view-text-btn" onclick="openTextModal('Question Detail', <?php echo htmlspecialchars(json_encode($displayQuestion), ENT_QUOTES); ?>)">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> View full
                                </button>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <span class="block font-bold text-[#0f172a]"><?php echo htmlspecialchars($row['topic'] ?: 'General'); ?></span>
                                <span class="block text-[10px] text-slate-400 font-medium uppercase mt-1">CNC: <?php echo htmlspecialchars($displayCNC); ?></span>
                            </td>
                            <td class="p-4">
                                <?php $uAnsText = strip_tags(str_replace('<br>', "\n", $uAnsDisplay)); $uPreview = mb_strimwidth($uAnsText, 0, 100, '…'); ?>
                                <div class="p-2.5 rounded-xl border <?php echo $isPerfect ? 'bg-green-50 border-green-100 text-green-800' : 'bg-slate-50 border-slate-100 text-slate-600'; ?> font-semibold text-[12px]">
                                    <div class="truncate-cell" onclick="openTextModal('Your Answer', <?php echo htmlspecialchars(json_encode($uAnsText), ENT_QUOTES); ?>)"><?php echo htmlspecialchars($uPreview); ?></div>
                                    <?php if (mb_strlen($uAnsText) > 100): ?>
                                    <button class="view-text-btn" onclick="openTextModal('Your Answer', <?php echo htmlspecialchars(json_encode($uAnsText), ENT_QUOTES); ?>)">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> View full
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="p-4">
                                <?php if ($initialAnsRaw && $changesData && $changesData['changed']): ?>
                                    <?php $initText = strip_tags(str_replace('<br>', "\n", $formatAns($initialAnsRaw))); $initPreview = mb_strimwidth($initText, 0, 80, '…'); ?>
                                    <div class="p-2.5 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 font-semibold text-[12px]">
                                        <div class="text-[10px] font-bold uppercase text-amber-600 mb-1">Initial Answer:</div>
                                        <div class="truncate-cell" style="text-decoration:line-through;opacity:.8;" onclick="openTextModal('Initial Answer', <?php echo htmlspecialchars(json_encode($initText), ENT_QUOTES); ?>)"><?php echo htmlspecialchars($initPreview); ?></div>
                                    </div>
                                <?php else: ?>
                                    <div class="p-2.5 rounded-xl border border-slate-200 bg-slate-100 text-slate-500 font-semibold text-[12px] text-center">—</div>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <?php $cAnsText = strip_tags(str_replace('<br>', "\n", $cAnsDisplay)); $cPreview = mb_strimwidth($cAnsText, 0, 100, '…'); ?>
                                <div class="p-2.5 rounded-xl bg-blue-50 border border-blue-100 text-blue-800 font-bold text-[12px]">
                                    <div class="truncate-cell" onclick="openTextModal('Correct Answer', <?php echo htmlspecialchars(json_encode($cAnsText), ENT_QUOTES); ?>)"><?php echo htmlspecialchars($cPreview); ?></div>
                                    <?php if (mb_strlen($cAnsText) > 100): ?>
                                    <button class="view-text-btn" onclick="openTextModal('Correct Answer', <?php echo htmlspecialchars(json_encode($cAnsText), ENT_QUOTES); ?>)">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> View full
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="p-4">
                                <?php $ratPreview = mb_strimwidth($displayRationale, 0, 120, '…'); ?>
                                <div class="text-slate-500 text-[12px] leading-relaxed italic truncate-cell"
                                     onclick="openTextModal('Rationale', <?php echo htmlspecialchars(json_encode($displayRationale), ENT_QUOTES); ?>)">
                                    <?php echo htmlspecialchars($ratPreview); ?>
                                </div>
                                <?php if (mb_strlen($displayRationale) > 120): ?>
                                <button class="view-text-btn" onclick="openTextModal('Rationale', <?php echo htmlspecialchars(json_encode($displayRationale), ENT_QUOTES); ?>)">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> View full
                                </button>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-center">
                                <button class="btn-view inline-flex items-center justify-center w-10 h-10 rounded-xl bg-slate-100 text-slate-600 hover:bg-blue-600 hover:text-white transition-all shadow-sm"
                                        data-payload='<?php echo htmlspecialchars(json_encode([
                                            "type" => $row["question_type"],
                                            "id" => $actualId,
                                            "answer" => $uAnsRaw,
                                            "correct_answer" => $cAnsRaw,
                                            "initial_answer" => $initialAnsRaw,
                                            "changes" => $changesData,
                                            "score" => $score,
                                            "earned_points" => $row["earned_points"] ?? 0,
                                            "max_points" => $row["max_points"] ?? 0,
                                            "rationale" => $displayRationale
                                        ]), ENT_QUOTES, 'UTF-8'); ?>'>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            </td>
                        </tr>
                        <?php
    endforeach;
else:
?>
                        <tr>
                            <td colspan="7" class="p-20 text-center">
                                <div class="flex flex-col items-center gap-3 text-slate-400">
                                    <svg class="w-12 h-12 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span class="font-bold uppercase tracking-widest text-xs">No records found for this attempt</span>
                                </div>
                            </td>
                        </tr>
                        <?php
endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="viewModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-6">
        <div class="bg-white w-full max-w-5xl h-[85vh] rounded-2xl shadow-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in duration-200">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </span>
                    <h3 class="font-extrabold text-slate-800">Question Review</h3>
                </div>
                <button onclick="closeModal()" class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-200 text-slate-500 hover:bg-red-500 hover:text-white transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-hidden relative">
                <iframe id="reviewFrame" src="" class="w-full h-full border-none"></iframe>
            </div>
        </div>
    </div>

    <!-- ── Text Content Modal ── -->
    <div id="textModal" role="dialog" aria-modal="true" aria-labelledby="textModal-title" onclick="if(event.target===this)closeTextModal()">
        <div id="textModal-box">
            <div id="textModal-header">
                <span id="textModal-title">Content</span>
                <button id="textModal-close" onclick="closeTextModal()" aria-label="Close">&times;</button>
            </div>
            <div id="textModal-body"></div>
        </div>
    </div>

    <script>
    function openTextModal(title, content) {
        document.getElementById('textModal-title').textContent = title;
        document.getElementById('textModal-body').textContent = content;
        document.getElementById('textModal').classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeTextModal() {
        document.getElementById('textModal').classList.remove('open');
        document.body.style.overflow = '';
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeTextModal();
    });
    </script>

    <script>
    let currentPayload = null;
    function viewQuestion(payload) {
        currentPayload = payload;
        const modal = document.getElementById('viewModal');
        const frame = document.getElementById('reviewFrame');
        modal.style.display = 'flex';
        frame.src = `${payload.type}/index.php?id=${payload.id}&mode=review&t=${Date.now()}`;
        frame.onload = function() {
            frame.contentWindow.postMessage({
                type: 'prefill',
                answer: currentPayload.answer,
                correct_answer: currentPayload.correct_answer,
                initial_answer: currentPayload.initial_answer,
                changes: currentPayload.changes,
                score: currentPayload.score,
                earned_points: currentPayload.earned_points,
                max_points: currentPayload.max_points,
                rationale: currentPayload.rationale,
                showRationale: true,
                isReview: true
            }, "*");
        };
    }
    function closeModal() { document.getElementById('viewModal').style.display = 'none'; document.getElementById('reviewFrame').src = ''; }

    // Delegated listener for .btn-view buttons (replaces inline onclick)
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-view');
        if (!btn || !btn.dataset.payload) return;
        try {
            viewQuestion(JSON.parse(btn.dataset.payload));
        } catch (err) {
            console.error('Failed to parse question payload', err);
        }
    });

    </script>
</body>
</html>