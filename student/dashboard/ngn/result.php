<?php
require_once '../../../config.php';
// session_start handled by config.php

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../login.php");
    exit;
}

$student_id = intval($_SESSION['user_id']);
$examTaken = isset($_GET['examTaken']) ? intval($_GET['examTaken']) : null;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$resultsPerPage = 10; // Show 10 attempts per page for scalability


// Handling session reset on finish or intentional exit
if (isset($_GET['finish']) && $_GET['finish'] == '1') {
    unset($_SESSION['current_ngn_examTaken']);
    unset($_SESSION['ngn_exam_set']); // Clear question set too

    // Redirect to dashboard immediately on intentional exit
    if (isset($_GET['exit']) && $_GET['exit'] == '1') {
        header("Location: ../index.php");
        exit;
    }
    // After a completed exam we show the full history — no redirect needed.
}

// Build parameterized where condition
$whereSQL = "WHERE student_id = ?";
$whereParams = [$student_id];
if ($examTaken) {
    $whereSQL .= " AND examTaken = ?";
    $whereParams[] = $examTaken;
}

// Count total grouped attempts for pagination
$countRow = db()->fetchOne(
    "SELECT COUNT(*) as total_attempts FROM (SELECT examTaken FROM exam_results {$whereSQL} GROUP BY examTaken) as grouped_attempts",
    $whereParams
);
$totalAttempts = intval($countRow['total_attempts'] ?? 0);
$totalPages = max(1, (int) ceil($totalAttempts / $resultsPerPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $resultsPerPage;

// Get latest attempt for summary banner (always latest overall under same filter)
$latest = db()->fetchOne(
    "SELECT examTaken, COUNT(*) as total_questions, SUM(score) as total_score,
            SUM(earned_points) as total_earned, SUM(max_points) as total_max,
            MAX(timestamp) as exam_time
     FROM exam_results {$whereSQL} GROUP BY examTaken ORDER BY exam_time DESC LIMIT 1",
    $whereParams
);

// Fetch attempts for current page
$attemptsRows = db()->fetchAll(
    "SELECT examTaken, COUNT(*) as total_questions, SUM(score) as total_score,
            SUM(earned_points) as total_earned, SUM(max_points) as total_max,
            MAX(timestamp) as exam_time
     FROM exam_results {$whereSQL} GROUP BY examTaken ORDER BY exam_time DESC LIMIT ? OFFSET ?",
    array_merge($whereParams, [$resultsPerPage, $offset])
);

function getCategory($percent)
{
    if ($percent >= 90)
        return ['label' => 'EXCELLENT', 'color' => '#10b981', 'bg' => '#d1fae5', 'icon' => 'fa-crown'];
    if ($percent >= 75)
        return ['label' => 'GOOD', 'color' => '#3b82f6', 'bg' => '#dbeafe', 'icon' => 'fa-thumbs-up'];
    if ($percent >= 50)
        return ['label' => 'AVERAGE', 'color' => '#f59e0b', 'bg' => '#fef3c7', 'icon' => 'fa-user-pen'];
    return ['label' => 'NEEDS IMPROVEMENT', 'color' => '#ef4444', 'bg' => '#fee2e2', 'icon' => 'fa-triangle-exclamation'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Results — Studium</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --accent: #3b82f6;
            --surface: #ffffff;
            --surface-alt: #f1f5f9;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --radius: 16px;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--surface-alt);
            color: var(--text);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        /* ── NAVBAR (exam/result.php style) ── */
        .navbar {
            height: 52px; background: #fff;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center;
            padding: 0 40px; gap: 10px;
        }
        .nb-brand { display: flex; align-items: center; gap: 8px; text-decoration: none; flex: 1; }
        .nb-name  { font-size: 13px; font-weight: 700; color: #0f172a; letter-spacing: -.2px; }
        .nb-sep   { width: 1px; height: 14px; background: var(--border); flex-shrink: 0; }
        .nb-ctx   { font-size: 11px; font-weight: 500; color: #94a3b8; }
        .nb-btn {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 11.5px; font-weight: 500; color: #64748b;
            text-decoration: none; padding: 5px 12px;
            border: 1px solid #e2e8f0; border-radius: 6px;
            background: #f8fafc; transition: all .15s; white-space: nowrap;
        }
        .nb-btn:hover { color: #0078b8; border-color: #bfdbfe; background: #eff6ff; }

        /* ── PAGE HEADER (exam/result.php style) ── */
        .page-header {
            background: #fff; border-bottom: 1px solid var(--border);
            border-top: 3px solid var(--accent);
            padding: 36px 40px 28px;
            animation: fadeUp .3s ease both;
        }
        @keyframes fadeUp { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
        .ph-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; flex-wrap: wrap; }
        .ph-eyebrow { font-size: 10px; font-weight: 600; letter-spacing: 1.2px; text-transform: uppercase; color: #94a3b8; margin-bottom: 10px; }
        .ph-title { font-size: 52px; font-weight: 800; letter-spacing: -2.5px; line-height: .95; color: var(--accent); margin-bottom: 8px; }
        .ph-sub   { font-size: 13px; font-weight: 300; color: var(--muted); line-height: 1.6; margin-top: 0; }

        /* ── SUMMARY BANNER ── */
        .summary-banner {
            background: linear-gradient(135deg, #0a1628, #1e3a5f);
            color: white; padding: 28px 40px;
            margin: 24px 40px; border-radius: 18px;
            display: flex; align-items: center; justify-content: space-between;
            gap: 24px; flex-wrap: wrap; overflow: hidden; position: relative;
        }
        .summary-banner::after {
            content: ''; position: absolute; right: -50px; top: -50px;
            width: 180px; height: 180px; background: rgba(255,255,255,0.05); border-radius: 50%;
        }
        .summary-score { font-size: 48px; font-weight: 800; line-height: 1; }
        .summary-label { font-size: 13px; opacity: 0.6; margin-top: 4px; }
        .summary-stats { display: flex; gap: 32px; flex-wrap: wrap; }
        .s-stat { display: flex; flex-direction: column; gap: 2px; }
        .s-stat-lbl { font-size: 10px; font-weight: 700; text-transform: uppercase; opacity: 0.6; letter-spacing: .6px; }
        .s-stat-val { font-size: 20px; font-weight: 800; }

        /* ── ATTEMPTS LIST ── */
        .attempts-wrap { padding: 0 40px 48px; }
        .attempts-grid { display: grid; gap: 18px; margin-top: 24px; }

        .card {
            background: var(--surface);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 28px;
            padding: 24px 28px;
            transition: transform .2s, box-shadow .2s;
        }
        .card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.07); }

        /* Score badge */
        .score-badge {
            width: 88px; height: 88px; border-radius: 50%;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            flex-shrink: 0;
            border: 3px solid currentColor;
        }
        .score-badge .pct  { font-size: 24px; font-weight: 800; line-height: 1; }
        .score-badge .slbl { font-size: 10px; font-weight: 700; text-transform: uppercase; opacity: 0.7; margin-top: 2px; }

        /* Card body */
        .card-body { min-width: 0; }
        .card-date { font-size: 12px; color: var(--muted); margin-bottom: 6px; }
        .card-meta { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 12px; }
        .meta-item { display: flex; flex-direction: column; }
        .meta-label { font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--muted); }
        .meta-value { font-size: 15px; font-weight: 700; color: var(--text); }
        .topic-list { display: flex; flex-wrap: wrap; gap: 6px; }
        .topic-pill {
            background: #f1f5f9; border: 1px solid var(--border);
            padding: 4px 12px; border-radius: 8px;
            font-size: 12px; font-weight: 600; color: #475569;
            display: flex; align-items: center; gap: 6px;
        }
        .topic-pill span { color: var(--muted); font-weight: 500; }

        /* Card actions */
        .card-actions { display: flex; flex-direction: column; gap: 10px; align-items: flex-end; flex-shrink: 0; }
        .cat-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 12px; border-radius: 10px;
            font-size: 11px; font-weight: 800; text-transform: uppercase;
            white-space: nowrap;
        }
        .btn-details {
            display: inline-flex; align-items: center; gap: 6px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white; text-decoration: none;
            padding: 10px 18px; border-radius: 10px;
            font-size: 13px; font-weight: 700;
            transition: all .2s; white-space: nowrap;
        }
        .btn-details:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(37,99,235,.25); }

        /* ── LATEST BADGE ── */
        .latest-badge {
            display: inline-flex; align-items: center; gap: 5px;
            background: #0d9488; color: #fff;
            font-size: 10px; font-weight: 800; text-transform: uppercase;
            letter-spacing: .6px; padding: 3px 10px; border-radius: 20px;
            white-space: nowrap; flex-shrink: 0;
        }

/* ── PAGINATION ── */
        .pagination {
            margin-top: 28px;
            display: flex; align-items: center; justify-content: center;
            gap: 10px; flex-wrap: wrap;
        }
        .page-link {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 40px; height: 40px; padding: 0 14px;
            border: 1px solid var(--border); border-radius: 10px;
            text-decoration: none; color: var(--text); background: #fff;
            font-weight: 600; font-size: 13px; transition: all .2s;
        }
        .page-link:hover { border-color: var(--accent); color: var(--accent); }
        .page-link.active { background: var(--accent); border-color: var(--accent); color: #fff; }
        .page-link.disabled { pointer-events: none; opacity: 0.4; }
        .page-ellipsis { color: var(--muted); font-weight: 700; }

        /* ── RESPONSIVE ── */
        @media (max-width: 900px) {
            .navbar { padding: 0 20px; }
            .page-header { padding: 24px 20px 20px; }
            .ph-title { font-size: 38px; letter-spacing: -1.5px; }
            .summary-banner { margin: 16px 20px; padding: 22px 20px; }
            .attempts-wrap { padding: 0 20px 40px; }
        }
        @media (max-width: 768px) {
            .navbar { padding: 0 14px; height: 48px; }
            .nb-ctx { display: none; }
            .page-header { padding: 20px 16px 16px; }
            .ph-title { font-size: 32px; letter-spacing: -1px; }
            .summary-banner { padding: 18px 16px; margin: 12px 16px; }
            .summary-score  { font-size: 32px; }
            .attempts-wrap  { padding: 0 16px 32px; }
            .card { grid-template-columns: auto 1fr; gap: 14px; padding: 16px; }
            .card-actions { flex-direction: row; align-items: center; gap: 8px; grid-column: span 2; }
            .score-badge { width: 68px; height: 68px; }
            .score-badge .pct { font-size: 18px; }
        }
        @media (max-width: 480px) {
            .navbar { padding: 0 10px; }
            .page-header { padding: 16px 12px 14px; }
            .ph-title { font-size: 26px; }
            .summary-banner { margin: 10px 12px; padding: 16px 14px; }
            .summary-score { font-size: 28px; }
            .attempts-wrap { padding: 0 12px 28px; }
            .card { grid-template-columns: 1fr; gap: 12px; padding: 14px; }
            .score-badge { flex-direction: row; width: 100%; height: auto; border-radius: 12px; padding: 10px 16px; justify-content: flex-start; gap: 12px; }
            .score-badge .pct { font-size: 22px; }
            .card-actions { flex-direction: column; align-items: stretch; }
            .btn-details { justify-content: center; }
            .summary-stats { gap: 16px; }
        }
    </style>
</head>
<body>


<!-- Navbar -->
<nav class="navbar">
    <a href="../index.php" class="nb-brand">
        <span class="nb-name">Studium</span>
    </a>
    <div class="nb-sep"></div>
    <span class="nb-ctx">NGN Results</span>
</nav>

<!-- Page Header -->
<div class="page-header">
    <div class="ph-row">
        <div>
            <?php if (isset($_GET['finish']) && $_GET['finish'] == '1'): ?>
            <div class="ph-eyebrow" style="color:#0d9488;">
                <i class="fas fa-check-circle" style="margin-right:5px;"></i> Exam Submitted &mdash; Results Saved
            </div>
            <?php else: ?>
            <div class="ph-eyebrow">NGN Practice &mdash; Performance History</div>
            <?php endif; ?>
            <div class="ph-title">Results.</div>
            <div class="ph-sub">
                <?php if (isset($_GET['finish']) && $_GET['finish'] == '1'): ?>
                    Your latest exam has been submitted. Here's your full attempt history — your most recent take is highlighted.
                <?php else: ?>
                    Review your NGN practice attempts and skill progression.
                <?php endif; ?>
            </div>
        </div>
        <?php if (isset($_GET['finish']) && $_GET['finish'] == '1'): ?>
        <div style="display:flex;gap:10px;align-items:center;flex-shrink:0;flex-wrap:wrap;">
            <a href="../index.php?start_ngn=1" class="nb-btn" style="font-size:12px;padding:8px 16px;">
                <i class="fas fa-plus"></i> New Exam
            </a>
            <a href="../index.php" class="nb-btn" style="font-size:12px;padding:8px 16px;background:#0a1628;color:#fff;border-color:#0a1628;">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
if ($latest):
    $latestPercent = ($latest['total_max'] > 0)
        ? min(100, round(($latest['total_earned'] / $latest['total_max']) * 100))
        : 0;
    $latestCat = getCategory($latestPercent);
?>
<div class="summary-banner">
    <div>
        <div class="summary-label" style="display:flex;align-items:center;gap:8px;">
            Latest Attempt
            <span style="background:rgba(255,255,255,.15);padding:2px 10px;border-radius:20px;font-size:10px;font-weight:800;letter-spacing:.6px;text-transform:uppercase;">
                <i class="fas fa-star" style="font-size:9px;margin-right:3px;"></i> Latest Take
            </span>
        </div>
        <div class="summary-score" style="color:<?= $latestCat['color'] ?>;"><?= $latestPercent ?>%</div>
        <div style="margin-top:6px;display:inline-flex;align-items:center;gap:6px;background:<?= $latestCat['bg'] ?>;color:<?= $latestCat['color'] ?>;padding:4px 14px;border-radius:20px;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.5px;">
            <i class="fas <?= $latestCat['icon'] ?>"></i> <?= $latestCat['label'] ?>
        </div>
    </div>
    <div class="summary-stats">
        <div class="s-stat">
            <span class="s-stat-lbl">Questions</span>
            <span class="s-stat-val"><?= $latest['total_questions'] ?></span>
        </div>
        <div class="s-stat">
            <span class="s-stat-lbl">Points Earned</span>
            <span class="s-stat-val"><?= round($latest['total_earned'], 1) ?> / <?= round($latest['total_max'], 1) ?></span>
        </div>
        <div class="s-stat">
            <span class="s-stat-lbl">Date</span>
            <span class="s-stat-val" style="font-size:14px;"><?= date("M d, Y", strtotime($latest['exam_time'])) ?></span>
        </div>
        <div class="s-stat">
            <span class="s-stat-lbl">Total Attempts</span>
            <span class="s-stat-val"><?= $totalAttempts ?></span>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="attempts-wrap">
    <?php if (empty($attemptsRows)): ?>
    <div style="text-align:center;padding:60px 20px;color:var(--muted);">
        <i class="fas fa-clipboard-list" style="font-size:44px;margin-bottom:16px;opacity:.3;"></i>
        <h3 style="font-size:18px;font-weight:700;color:var(--text);margin-bottom:8px;">No Results Found</h3>
        <p style="font-size:14px;">No exam data was recorded for this attempt.</p>
        <a href="../index.php?start_ngn=1" class="btn-details" style="display:inline-flex;margin-top:20px;">
            <i class="fas fa-plus"></i> Try a New Exam
        </a>
    </div>
    <?php endif; ?>

    <div class="attempts-grid">
        <?php
        $latestExamTaken = isset($latest['examTaken']) ? intval($latest['examTaken']) : null;
        foreach ($attemptsRows as $row):
            $id = intval($row['examTaken']);
            $isLatest = ($latestExamTaken !== null && $id === $latestExamTaken);
            $percent = ($row['total_max'] > 0)
                ? min(100, round(($row['total_earned'] / $row['total_max']) * 100))
                : 0;
            $cat = getCategory($percent);

            $topicRows = db()->fetchAll(
                "SELECT topic, COUNT(*) as q,
                        SUM(earned_points) as ep, SUM(max_points) as mp
                 FROM exam_results WHERE student_id = ? AND examTaken = ? GROUP BY topic",
                [$student_id, $id]
            );
        ?>
        <div class="card" <?= $isLatest ? 'style="border-color: #0d9488; border-width: 2px;"' : '' ?>>
            <!-- Score badge -->
            <div class="score-badge" style="color:<?= $cat['color'] ?>;background:<?= $cat['bg'] ?>;">
                <div class="pct"><?= $percent ?>%</div>
                <div class="slbl">Score</div>
            </div>

            <!-- Card body -->
            <div class="card-body">
                <div class="card-date" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <span><i class="far fa-calendar-alt"></i>
                    <?= date("M d, Y", strtotime($row['exam_time'])) ?>
                    &nbsp;·&nbsp; Attempt #<?= $id ?></span>
                    <?php if ($isLatest): ?>
                    <span class="latest-badge"><i class="fas fa-star"></i> Latest Take</span>
                    <?php endif; ?>
                </div>
                <div class="card-meta">
                    <div class="meta-item">
                        <span class="meta-label">Questions</span>
                        <span class="meta-value"><?= $row['total_questions'] ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Points</span>
                        <span class="meta-value"><?= round($row['total_earned'], 1) ?> / <?= round($row['total_max'], 1) ?></span>
                    </div>
                </div>
                <?php if ($topicRows): ?>
                <div class="topic-list">
                    <?php foreach ($topicRows as $t):
                        $tp = ($t['mp'] > 0) ? min(100, round(($t['ep'] / $t['mp']) * 100)) : 0;
                        $tc = $tp >= 75 ? '#10b981' : ($tp >= 50 ? '#f59e0b' : '#ef4444');
                    ?>
                    <div class="topic-pill">
                        <?= htmlspecialchars($t['topic'] ?: 'General') ?>
                        <span style="color:<?= $tc ?>;font-weight:700;"><?= $tp ?>%</span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <div class="card-actions">
                <div class="cat-badge" style="background:<?= $cat['bg'] ?>;color:<?= $cat['color'] ?>;">
                    <i class="fas <?= $cat['icon'] ?>"></i> <?= $cat['label'] ?>
                </div>
                <a href="history_details.php?examTaken=<?= $id ?>" class="btn-details">
                    View Details <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalAttempts > 0 && !$examTaken): ?>
    <?php
        $queryBase = $_GET;
        unset($queryBase['page']);
        $prevPage = max(1, $currentPage - 1);
        $nextPage = min($totalPages, $currentPage + 1);
        $prevUrl = '?' . http_build_query(array_merge($queryBase, ['page' => $prevPage]));
        $nextUrl = '?' . http_build_query(array_merge($queryBase, ['page' => $nextPage]));
        $visiblePages = [1];
        for ($i = $currentPage - 1; $i <= $currentPage + 1; $i++) {
            if ($i > 1 && $i < $totalPages) $visiblePages[] = $i;
        }
        if ($totalPages > 1) $visiblePages[] = $totalPages;
        $visiblePages = array_values(array_unique($visiblePages));
        sort($visiblePages);
    ?>
    <div class="pagination">
        <a href="<?= htmlspecialchars($prevUrl) ?>" class="page-link <?= $currentPage <= 1 ? 'disabled' : '' ?>">
            <i class="fas fa-chevron-left"></i>&nbsp;Prev
        </a>
        <?php $lastShown = 0; foreach ($visiblePages as $p): ?>
            <?php if ($lastShown && ($p - $lastShown) > 1): ?><span class="page-ellipsis">…</span><?php endif; ?>
            <a href="<?= htmlspecialchars('?' . http_build_query(array_merge($queryBase, ['page' => $p]))) ?>"
               class="page-link <?= $p === $currentPage ? 'active' : '' ?>"><?= $p ?></a>
            <?php $lastShown = $p; endforeach; ?>
        <a href="<?= htmlspecialchars($nextUrl) ?>" class="page-link <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
            Next&nbsp;<i class="fas fa-chevron-right"></i>
        </a>
    </div>
    <?php endif; ?>
</div>

</body>
</html>