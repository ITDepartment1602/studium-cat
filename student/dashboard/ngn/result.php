<?php
require_once '../../../config.php';
// session_start handled by config.php

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$examTaken = isset($_GET['examTaken']) ? intval($_GET['examTaken']) : null;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$resultsPerPage = 3;

// Handling session reset on finish or intentional exit
if (isset($_GET['finish']) && $_GET['finish'] == '1') {
    unset($_SESSION['current_ngn_examTaken']);
    unset($_SESSION['ngn_exam_set']); // Clear question set too
    
    // Redirect to dashboard immediately on intentional exit
    if (isset($_GET['exit']) && $_GET['exit'] == '1') {
        header("Location: ../index.php");
        exit;
    }

    // If they came via the Exit button but didn't actually answer any questions, 
    // just redirect them home instead of showing a blank results page
    if (!$examTaken) {
       header("Location: ../index.php");
       exit;
    }
}

// Build shared where condition
$whereClause = " WHERE student_id='$student_id' ";
if ($examTaken) {
    $whereClause .= " AND examTaken='$examTaken' ";
}

// Count total grouped attempts for pagination
$countSql = "SELECT COUNT(*) as total_attempts FROM (SELECT examTaken FROM exam_results" . $whereClause . "GROUP BY examTaken) as grouped_attempts";
$countResult = mysqli_query($con, $countSql);
$countRow = mysqli_fetch_assoc($countResult);
$totalAttempts = intval($countRow['total_attempts'] ?? 0);
$totalPages = max(1, (int) ceil($totalAttempts / $resultsPerPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $resultsPerPage;

// Get latest attempt for summary banner (always latest overall under same filter)
$latestSql = "SELECT examTaken, COUNT(*) as total_questions, SUM(score) as total_score, MAX(timestamp) as exam_time
              FROM exam_results" . $whereClause . "GROUP BY examTaken ORDER BY exam_time DESC LIMIT 1";
$latestResult = mysqli_query($con, $latestSql);
$latest = mysqli_fetch_assoc($latestResult);

// Fetch attempts for current page
$sql = "SELECT examTaken, COUNT(*) as total_questions, SUM(score) as total_score, MAX(timestamp) as exam_time 
        FROM exam_results" . $whereClause . "GROUP BY examTaken ORDER BY exam_time DESC LIMIT $resultsPerPage OFFSET $offset";
$attempts = mysqli_query($con, $sql);

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #0a1628;
            --primary-light: #1e3a5f;
            --accent: #3b82f6;
            --surface: #ffffff;
            --surface-alt: #f8fafc;
            --text: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --radius: 16px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--surface-alt); 
            color: var(--text); 
            padding: 40px 20px;
            line-height: 1.5;
        }
        
        .container { max-width: 1200px; margin: 0 auto; }
        
        .header { 
            margin-bottom: 32px; 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-end;
        }
        
        .header-text h2 { font-size: 32px; font-weight: 800; letter-spacing: -1px; color: #0f172a; }
        .header-text p { color: var(--text-muted); margin-top: 4px; font-weight: 500; }

        .attempts-grid {
            display: grid;
            gap: 24px;
        }

        .card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 32px;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 10px 30px rgba(0,0,0,0.02);
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            align-items: center;
            gap: 40px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 40px rgba(0,0,0,0.05);
        }

        /* Score Display Column */
        .score-display {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .chart-container {
            width: 140px;
            height: 140px;
            margin-bottom: 16px;
            position: relative;
        }

        .score-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            flex-direction: column;
        }

        .score-percent { font-size: 32px; font-weight: 800; color: var(--text); line-height: 1; }
        .score-label { font-size: 12px; font-weight: 600; color: var(--text-muted); margin-top: 2px; }

        /* Performance Data Column */
        .performance-data {
            display: grid;
            gap: 20px;
        }

        .data-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .data-title { font-size: 14px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .date { font-size: 13px; font-weight: 500; color: var(--text-muted); display: flex; align-items: center; gap: 6px; }

        .topic-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .topic-pill {
            background: #f1f5f9;
            border: 1px solid var(--border);
            padding: 6px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .topic-pill span { color: var(--text-muted); font-weight: 400; }

        .progress-meta {
            margin-top: 16px;
            display: flex;
            gap: 24px;
        }

        .meta-item { display: flex; flex-direction: column; gap: 2px; }
        .meta-label { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
        .meta-value { font-size: 16px; font-weight: 700; color: var(--text); }

        /* Action Column */
        .card-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .badge { 
            padding: 10px; 
            border-radius: 12px; 
            font-size: 12px; 
            font-weight: 800; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            gap: 8px;
            text-transform: uppercase;
        }

        .btn-details {
            background: linear-gradient(135deg, var(--accent) 0%, #2563eb 100%);
            color: white;
            text-decoration: none;
            padding: 12px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            text-align: center;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }

        .btn-details:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
        }

        .btn-details i {
            margin-left: 6px;
        }

        .summary-banner {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            overflow: hidden;
            position: relative;
        }

        .summary-banner::after {
            content: '';
            position: absolute;
            right: -50px;
            top: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }

        .summary-main h1 { font-size: 20px; font-weight: 500; opacity: 0.8; }
        .summary-main .total-score { font-size: 48px; font-weight: 800; margin-top: 4px; display: flex; align-items: flex-end; gap: 8px; }
        .summary-main .total-score span { font-size: 24px; opacity: 0.5; margin-bottom: 8px; }

        .pagination {
            margin-top: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            flex-wrap: nowrap;
            overflow-x: auto;
            padding-bottom: 4px;
            white-space: nowrap;
        }

        .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            padding: 0 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            text-decoration: none;
            color: var(--text);
            background: #fff;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .page-link:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .page-link.active {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        .page-link.disabled {
            pointer-events: none;
            opacity: 0.45;
        }

        .page-ellipsis {
            color: var(--text-muted);
            font-weight: 700;
            padding: 0 2px;
        }

        @media (max-width: 992px) {
            .card { grid-template-columns: 1fr; gap: 24px; padding: 24px; }
            .performance-data { order: -1; }
            .score-display { flex-direction: row; gap: 24px; text-align: left; }
            .chart-container { margin-bottom: 0; width: 100px; height: 100px; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="header-text">
            <h2>Exam Performance History</h2>
            <p>Review your NGN practice attempts and skill progression.</p>
        </div>
        <a href="index.php" class="btn-details" style="background: white; color: var(--accent); box-shadow: none; border: 1px solid var(--border);">
            <i class="fas fa-plus"></i> New Exam
        </a>
    </div>

    <?php 
    if ($latest): 
        $latestPercent = round(($latest['total_score'] / $latest['total_questions']) * 100);
    ?>
    <div class="summary-banner">
        <div class="summary-main">
            <h1>Latest Performance Index</h1>
            <div class="total-score">
                <?php echo $latestPercent; ?>%
                <span>Overall Mastery</span>
            </div>
        </div>
        <div class="summary-stats" style="display: flex; gap: 40px;">
            <div class="meta-item">
                <span class="meta-label" style="color: rgba(255,255,255,0.6)">Questions Taken</span>
                <span class="meta-value" style="color: white; font-size: 24px;"><?php echo $latest['total_questions']; ?></span>
            </div>
            <div class="meta-item">
                <span class="meta-label" style="color: rgba(255,255,255,0.6)">Avg. Per Question</span>
                <span class="meta-value" style="color: white; font-size: 24px;"><?php echo round($latest['total_score'], 1); ?> pts</span>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="attempts-grid">
        <?php while ($row = mysqli_fetch_assoc($attempts)):
            $id = $row['examTaken'];
            // score is sum of fractions 0.00-1.00
            $percent = ($row['total_questions'] > 0) ? round(($row['total_score'] / $row['total_questions']) * 100) : 0;
            $cat = getCategory($percent);

            $t_res = mysqli_query($con, "SELECT topic, COUNT(*) as q, SUM(score) as s FROM exam_results WHERE student_id='$student_id' AND examTaken='$id' GROUP BY topic");
        ?>
        <div class="card">
            <div class="score-display">
                <div class="chart-container">
                    <canvas id="chart-<?php echo $id; ?>"></canvas>
                    <div class="score-center">
                        <span class="score-percent"><?php echo $percent; ?>%</span>
                        <span class="score-label">Score</span>
                    </div>
                </div>
            </div>

            <div class="performance-data">
                <div class="data-group">
                    <div class="data-header">
                        <span class="data-title">Topic Performance</span>
                        <span class="date">
                            <i class="far fa-calendar-alt"></i>
                            <?php echo date("M d, Y • h:i A", strtotime($row['exam_time'])); ?>
                        </span>
                    </div>
                    <div class="topic-list">
                        <?php while ($t = mysqli_fetch_assoc($t_res)):
                            // t['s'] is sum of float scores
                            $tp = ($t['q'] > 0) ? round(($t['s'] / $t['q']) * 100) : 0;
                        ?>
                            <div class="topic-pill">
                                <?php echo htmlspecialchars($t['topic'] ?: 'General'); ?>
                                <span><?php echo $tp; ?>%</span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="progress-meta">
                    <div class="meta-item">
                        <span class="meta-label">Attempt ID</span>
                        <span class="meta-value">#<?php echo $id; ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Total Items</span>
                        <span class="meta-value"><?php echo $row['total_questions']; ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Raw Points</span>
                        <span class="meta-value"><?php echo round($row['total_score'], 2); ?></span>
                    </div>
                </div>
            </div>

            <div class="card-actions">
                <div class="badge" style="background: <?php echo $cat['bg']; ?>; color: <?php echo $cat['color']; ?>;">
                    <i class="fas <?php echo $cat['icon']; ?>"></i>
                    <?php echo $cat['label']; ?>
                </div>
                <a href="history_details.php?examTaken=<?php echo $id; ?>" class="btn-details">
                    Vew Result Details <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>

        <script>
        new Chart(document.getElementById('chart-<?php echo $id; ?>'), {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [<?php echo $percent; ?>, <?php echo 100 - $percent; ?>],
                    backgroundColor: ['<?php echo $cat['color']; ?>', '#f1f5f9'],
                    borderWidth: 0,
                    cutout: '82%',
                    borderRadius: 10
                }]
            },
            options: {
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                interaction: { mode: 'none' }
            }
        });
        </script>
        <?php endwhile; ?>
    </div>

    <?php if ($totalAttempts > 0 && !$examTaken): ?>
    <?php
        $queryBase = $_GET;
        unset($queryBase['page']);
        $prevPage = max(1, $currentPage - 1);
        $nextPage = min($totalPages, $currentPage + 1);
        $prevUrl = '?' . http_build_query(array_merge($queryBase, ['page' => $prevPage]));
        $nextUrl = '?' . http_build_query(array_merge($queryBase, ['page' => $nextPage]));

        $visiblePages = [];
        $visiblePages[] = 1;
        for ($i = $currentPage - 1; $i <= $currentPage + 1; $i++) {
            if ($i > 1 && $i < $totalPages) {
                $visiblePages[] = $i;
            }
        }
        if ($totalPages > 1) {
            $visiblePages[] = $totalPages;
        }
        $visiblePages = array_values(array_unique($visiblePages));
        sort($visiblePages);
    ?>
    <div class="pagination">
        <a href="<?php echo htmlspecialchars($prevUrl); ?>" class="page-link <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
            <i class="fas fa-chevron-left"></i>&nbsp;Prev
        </a>

        <?php $lastShown = 0; ?>
        <?php foreach ($visiblePages as $p): ?>
            <?php if ($lastShown && ($p - $lastShown) > 1): ?>
                <span class="page-ellipsis">...</span>
            <?php endif; ?>
            <?php $pageUrl = '?' . http_build_query(array_merge($queryBase, ['page' => $p])); ?>
            <a href="<?php echo htmlspecialchars($pageUrl); ?>" class="page-link <?php echo $p === $currentPage ? 'active' : ''; ?>">
                <?php echo $p; ?>
            </a>
            <?php $lastShown = $p; ?>
        <?php endforeach; ?>

        <a href="<?php echo htmlspecialchars($nextUrl); ?>" class="page-link <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
            Next&nbsp;<i class="fas fa-chevron-right"></i>
        </a>
    </div>
    <?php endif; ?>
</div>

</body>
</html>