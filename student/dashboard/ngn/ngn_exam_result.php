<?php
require_once '../../../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../login.php");
    exit;
}

$student_id = intval($_SESSION['user_id']);
$examTaken  = isset($_GET['examTaken']) ? intval($_GET['examTaken']) : 0;

if (!$examTaken) {
    header("Location: index.php");
    exit;
}

// Clear session state on finish
if (isset($_GET['finish'])) {
    unset($_SESSION['current_ngn_examTaken']);
    unset($_SESSION['ngn_exam_set']);
}

// Summary row
$summary = db()->fetchOne(
    "SELECT COUNT(*) as total_questions,
            SUM(earned_points) as total_earned,
            SUM(max_points)    as total_max,
            MAX(timestamp)     as exam_time
     FROM exam_results
     WHERE student_id = ? AND examTaken = ?",
    [$student_id, $examTaken]
);

if (!$summary || !$summary['total_questions']) {
    header("Location: index.php");
    exit;
}

$totalEarned = floatval($summary['total_earned']);
$totalMax    = floatval($summary['total_max']);
$percent     = $totalMax > 0 ? min(100, round(($totalEarned / $totalMax) * 100)) : 0;

function getCategory($p) {
    if ($p >= 90) return ['label' => 'EXCELLENT',          'color' => '#10b981', 'bg' => '#d1fae5', 'icon' => 'fa-crown'];
    if ($p >= 75) return ['label' => 'GOOD',               'color' => '#3b82f6', 'bg' => '#dbeafe', 'icon' => 'fa-thumbs-up'];
    if ($p >= 50) return ['label' => 'AVERAGE',            'color' => '#f59e0b', 'bg' => '#fef3c7', 'icon' => 'fa-user-pen'];
    return             ['label' => 'NEEDS IMPROVEMENT',   'color' => '#ef4444', 'bg' => '#fee2e2', 'icon' => 'fa-triangle-exclamation'];
}
$cat = getCategory($percent);

// Per-concept breakdown
$topicRows = db()->fetchAll(
    "SELECT concept, COUNT(*) as q,
            SUM(earned_points) as ep, SUM(max_points) as mp
     FROM exam_results
     WHERE student_id = ? AND examTaken = ?
     GROUP BY concept
     ORDER BY concept",
    [$student_id, $examTaken]
);

// Per-question detail
$questions = db()->fetchAll(
    "SELECT question_number, question_uid, question_type, topic,
            user_answer, correct_answer, isCorrect,
            earned_points, max_points, rationale
     FROM exam_results
     WHERE student_id = ? AND examTaken = ?
     ORDER BY question_number ASC, id ASC",
    [$student_id, $examTaken]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Result — Studium</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #0a1628;
            --accent:  #3b82f6;
            --surface: #ffffff;
            --surface-alt: #f8fafc;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --radius: 16px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--surface-alt); color: var(--text); padding: 30px 15px; line-height: 1.5; }
        .container { max-width: 1200px; margin: 0 auto; width: 100%; }

        /* ── Top row: hero left + right sidebar ── */
        .top-row {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 20px;
            margin-bottom: 20px;
            align-items: start;
        }
        @media (max-width: 768px) {
            .top-row { grid-template-columns: 1fr; }
        }

        /* ── Hero (left panel) ── */
        .hero {
            background: linear-gradient(135deg, #0a1628 0%, #1e3a5f 100%);
            color: white;
            border-radius: 20px;
            padding: 36px 40px;
            display: flex;
            align-items: center;
            gap: 36px;
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        .hero::after {
            content: '';
            position: absolute;
            right: -60px; top: -60px;
            width: 220px; height: 220px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }
        .hero-chart { position: relative; width: 130px; height: 130px; flex-shrink: 0; }
        .hero-center {
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        .hero-percent { font-size: 28px; font-weight: 800; line-height: 1; }
        .hero-label   { font-size: 11px; font-weight: 600; opacity: 0.7; margin-top: 2px; }
        .hero-info h1  { font-size: 22px; font-weight: 800; margin-bottom: 4px; }
        .hero-info p   { font-size: 13px; opacity: 0.7; margin-bottom: 18px; }
        .hero-stats    { display: flex; gap: 28px; flex-wrap: wrap; }
        .hero-stat span:first-child { font-size: 11px; font-weight: 700; opacity: 0.6; text-transform: uppercase; display: block; }
        .hero-stat span:last-child  { font-size: 18px; font-weight: 800; }
        .badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 7px 14px; border-radius: 10px;
            font-size: 12px; font-weight: 800; text-transform: uppercase;
        }

        /* ── Right sidebar ── */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        /* ── Section card ── */
        .section {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 22px 24px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .section-title {
            font-size: 12px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.5px; color: var(--muted); margin-bottom: 16px;
        }

        /* ── Topic pills in sidebar ── */
        .topic-list { display: flex; flex-direction: column; gap: 10px; }
        .topic-row {
            display: flex; align-items: center; gap: 10px;
        }
        .topic-name {
            font-size: 13px; font-weight: 600; color: #475569;
            min-width: 60px; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .topic-bar-wrap {
            flex: 1; height: 7px; background: #e2e8f0; border-radius: 4px; overflow: hidden; min-width: 40px;
        }
        .topic-bar-fill { height: 100%; border-radius: 4px; transition: width 0.4s ease; }
        .topic-pct { font-size: 12px; font-weight: 700; color: var(--muted); white-space: nowrap; min-width: 38px; text-align: right; }

        /* ── Action buttons ── */
        .actions-sidebar {
            display: flex;
            gap: 10px;
        }
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 18px; border-radius: 12px;
            font-size: 13px; font-weight: 700; text-decoration: none;
            transition: all 0.2s ease; flex: 1; justify-content: center;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--accent), #2563eb);
            color: white; box-shadow: 0 4px 12px rgba(59,130,246,0.2);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(59,130,246,0.3); }
        .btn-outline {
            background: white; color: var(--text);
            border: 1px solid var(--border);
        }
        .btn-outline:hover { border-color: var(--accent); color: var(--accent); }

        /* ── Performance Graph ── */
        .graph-container { height: 300px; width: 100%; }

        /* ── Question Table ── */
        .q-table { width: 100%; border-collapse: collapse; }
        .q-table thead th {
            background: #f1f5f9; padding: 10px 12px;
            font-size: 11px; font-weight: 700; text-transform: uppercase;
            color: var(--muted); text-align: left; border-bottom: 1px solid var(--border);
        }
        .q-table tbody td {
            padding: 10px 12px; border-bottom: 1px solid var(--border);
            font-size: 13px;
        }
        .q-table tbody tr:last-child td { border-bottom: none; }
        .q-table .q-num-col { width: 40px; text-align: center; color: var(--muted); font-weight: 700; }
        .q-table tbody tr.correct { background: rgba(16, 185, 129, 0.03); }
        .q-table tbody tr.incorrect { background: rgba(239, 68, 68, 0.03); }
        .q-table .q-correct { color: #10b981; font-weight: 600; }
        .q-table .q-incorrect { color: #ef4444; font-weight: 600; }
        .q-table .q-score-col { width: auto; min-width: 90px; text-align: center; font-weight: 700; }
        .q-table .q-action-col { width: 70px; text-align: center; }
        .view-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 12px; border-radius: 8px;
            background: var(--accent); color: white;
            border: none; cursor: pointer;
            font-size: 12px; font-weight: 600;
            transition: all 0.2s ease;
        }
        .view-btn:hover { background: #2563eb; transform: translateY(-1px); }

        /* ── Modal ── */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 16px; width: 98%; max-width: 1200px; max-height: 95vh; overflow: hidden; position: relative; display: flex; flex-direction: column; }
        .modal-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid var(--border); flex-shrink: 0; }
        .modal-title { font-size: 16px; font-weight: 700; }
        .modal-close { background: none; border: none; font-size: 24px; color: var(--muted); cursor: pointer; padding: 0 8px; }
        .modal-body { padding: 16px; overflow-y: auto; flex: 1; }
        .modal-loading { display: flex; align-items: center; justify-content: center; height: 200px; color: var(--muted); font-size: 14px; gap: 10px; }
        .question-iframe { width: 100%; height: 680px; border: none; }

        /* ── Pagination ── */
        .pagination { display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 20px; }
        .pagination-btn {
            padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border);
            background: white; color: var(--text); cursor: pointer;
            font-size: 13px; font-weight: 600; transition: all 0.2s;
        }
        .pagination-btn:hover:not(:disabled) { background: var(--accent); color: white; border-color: var(--accent); }
        .pagination-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .pagination-info { font-size: 13px; color: var(--muted); font-weight: 600; }
        .q-table-wrapper { overflow-x: auto; width: 100%; }
        .section { width: 100%; }

        @media (max-width: 640px) {
            .hero { flex-direction: column; gap: 20px; padding: 24px; }
            .hero-stats { gap: 16px; }
            .actions-sidebar { flex-direction: column; }
        }
    </style>
</head>
<body>
<div class="container">

    <!-- Top Row: Hero + Sidebar -->
    <div class="top-row">

        <!-- Left: Hero Score Panel -->
        <div class="hero">
            <div class="hero-chart">
                <canvas id="heroChart"></canvas>
                <div class="hero-center">
                    <div class="hero-percent"><?= $percent ?>%</div>
                    <div class="hero-label">Score</div>
                </div>
            </div>
            <div class="hero-info">
                <h1>Exam Complete</h1>
                <p>Attempt #<?= $examTaken ?> &nbsp;·&nbsp; <?= date("M d, Y • h:i A", strtotime($summary['exam_time'])) ?></p>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <span>Questions</span>
                        <span><?= intval($summary['total_questions']) ?></span>
                    </div>
                    <div class="hero-stat">
                        <span>Points Earned</span>
                        <span><?= round($totalEarned, 1) ?> / <?= round($totalMax, 1) ?></span>
                    </div>
                    <div class="hero-stat">
                        <span>Rating</span>
                        <span>
                            <div class="badge" style="background:<?= $cat['bg'] ?>; color:<?= $cat['color'] ?>;">
                                <i class="fas <?= $cat['icon'] ?>"></i> <?= $cat['label'] ?>
                            </div>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Concept Performance + Buttons -->
        <div class="sidebar">
            <!-- Concept Performance -->
            <div class="section" style="margin-bottom:0; flex:1;">
                <div class="section-title">Concept Performance</div>
                <?php if ($topicRows): ?>
                <div class="topic-list">
                    <?php foreach ($topicRows as $t):
                        $tp = ($t['mp'] > 0) ? min(100, round(($t['ep'] / $t['mp']) * 100)) : 0;
                        $barColor = $tp >= 75 ? '#10b981' : ($tp >= 50 ? '#f59e0b' : '#ef4444');
                    ?>
                    <div class="topic-row">
                        <span class="topic-name" title="<?= htmlspecialchars($t['concept'] ?: 'General') ?>"><?= htmlspecialchars($t['concept'] ?: 'General') ?></span>
                        <div class="topic-bar-wrap">
                            <div class="topic-bar-fill" style="width:<?= $tp ?>%;background:<?= $barColor ?>;"></div>
                        </div>
                        <span class="topic-pct" style="color:<?= $barColor ?>;"><?= $tp ?>%</span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p style="color:var(--muted);font-size:13px;">No concept data available.</p>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="actions-sidebar">
                <a href="index.php" class="btn btn-outline"><i class="fas fa-plus"></i> New Exam</a>
                <a href="../index.php" class="btn btn-primary"><i class="fas fa-home"></i> Dashboard</a>
            </div>
        </div>

    </div>

    <!-- Performance Graph -->
    <div class="section">
        <div class="section-title">Exam Performance (Streak Chart)</div>
        <div class="graph-container">
            <canvas id="performanceChart"></canvas>
        </div>
    </div>

    <!-- Per-question detail -->
    <div class="section">
        <div class="section-title">Question Breakdown</div>
        <div class="q-table-wrapper">
            <table class="q-table" id="questionsTable">
                <thead>
                    <tr>
                        <th class="q-num-col">#</th>
                        <th>Type</th>
                        <th>Topic</th>
                        <th class="q-score-col">Score</th>
                        <th class="q-score-col">Status</th>
                        <th class="q-action-col">Action</th>
                    </tr>
                </thead>
                <tbody id="questionsTableBody">
                    <?php foreach ($questions as $idx => $q):
                        $isOk  = intval($q['isCorrect']);
                        $qEp   = floatval($q['earned_points']);
                        $qMp   = floatval($q['max_points']);
                        $qNum  = $q['question_number'] ?? ($idx + 1);
                        $qType = htmlspecialchars(strtoupper($q['question_type'] ?? ''));
                        $qTopic = htmlspecialchars($q['topic'] ?: 'General');
                        $qTypeCode = strtolower($q['question_type']);
                    ?>
                    <tr class="q-row <?= $isOk ? 'correct' : 'incorrect' ?>" data-index="<?= $idx ?>">
                        <td class="q-num-col"><?= $qNum ?></td>
                        <td><?= $qType ?></td>
                        <td><?= $qTopic ?></td>
                        <td class="q-score-col"><?= round($qEp, 1) ?> / <?= round($qMp, 1) ?></td>
                        <td class="q-score-col <?= $isOk ? 'q-correct' : 'q-incorrect' ?>">
                            <?= $isOk ? '✓ Correct' : '✗ Incorrect' ?>
                        </td>
                        <td class="q-action-col">
                            <button class="view-btn" onclick="openQuestionModal('<?= htmlspecialchars($q['question_uid']) ?>', '<?= $qTypeCode ?>', <?= $idx ?>)">
                                <i class="fas fa-eye"></i> View
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="pagination">
            <button class="pagination-btn" id="prevBtn" onclick="previousPage()"><i class="fas fa-chevron-left"></i> Prev</button>
            <span class="pagination-info" id="pageInfo">Page 1 of 1</span>
            <button class="pagination-btn" id="nextBtn" onclick="nextPage()">Next <i class="fas fa-chevron-right"></i></button>
        </div>
    </div>

</div>

<!-- Question Modal -->
<div class="modal" id="questionModal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title">Question Preview</div>
            <button class="modal-close" onclick="closeQuestionModal()">✕</button>
        </div>
        <div class="modal-body" id="modalBody">
            <div class="modal-loading" id="modalLoading">
                <i class="fas fa-spinner fa-spin"></i> Loading question…
            </div>
            <iframe id="questionIframe" class="question-iframe" style="display:none;"></iframe>
        </div>
    </div>
</div>

<script>
// Hero chart
new Chart(document.getElementById('heroChart'), {
    type: 'doughnut',
    data: {
        datasets: [{
            data: [<?= $percent ?>, <?= 100 - $percent ?>],
            backgroundColor: ['<?= $cat['color'] ?>', 'rgba(255,255,255,0.15)'],
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

// Performance chart
const rawData = [
    <?php foreach ($questions as $idx => $q): ?>
    { qNum: <?= ($q['question_number'] ?? ($idx + 1)) ?>, correct: <?= intval($q['isCorrect']) ? 1 : 0 ?> },
    <?php endforeach; ?>
];

let streakValue = 0;
const streakData = rawData.map(q => {
    streakValue += q.correct ? 1 : -1;
    return { qNum: q.qNum, correct: q.correct, streak: streakValue };
});

const chartCtx = document.getElementById('performanceChart');
if (chartCtx) {
    new Chart(chartCtx, {
        type: 'line',
        data: {
            labels: streakData.map(d => 'Q' + d.qNum),
            datasets: [{
                label: 'Correct/Incorrect Streak',
                data: streakData.map(d => d.streak),
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.3,
                pointRadius: 6,
                pointHoverRadius: 8,
                pointBorderWidth: 2,
                pointBackgroundColor: streakData.map(d => d.correct ? '#10b981' : '#ef4444'),
                pointBorderColor: streakData.map(d => d.correct ? '#10b981' : '#ef4444'),
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: true, position: 'top' },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    padding: 12,
                    titleFont: { size: 13, weight: 700 },
                    bodyFont: { size: 12 },
                    borderColor: '#e2e8f0',
                    borderWidth: 1,
                    callbacks: {
                        afterLabel: ctx => streakData[ctx.dataIndex].correct ? '✓ Correct' : '✗ Incorrect'
                    }
                }
            },
            scales: {
                y: {
                    title: { display: true, text: 'Streak Points' },
                    ticks: { font: { size: 11 } },
                    grid: { color: 'rgba(226, 232, 240, 0.5)' }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11 } }
                }
            }
        }
    });
}

// Questions data for modal
const questionsData = [
    <?php foreach ($questions as $idx => $q):
        $qUidParts = explode('-', $q['question_uid'], 2);
        $qId = isset($qUidParts[1]) ? intval($qUidParts[1]) : 0;
        $qEp = floatval($q['earned_points']);
        $qMp = floatval($q['max_points']);
    ?>
    {
        uid: <?= json_encode($q['question_uid']) ?>,
        id: <?= $qId ?>,
        type: <?= json_encode(strtolower($q['question_type'])) ?>,
        answer: <?= json_encode($q['user_answer'] ?? '') ?>,
        correct_answer: <?= json_encode($q['correct_answer'] ?? '') ?>,
        isCorrect: <?= intval($q['isCorrect']) ? 'true' : 'false' ?>,
        score: <?= $qMp > 0 ? round($qEp / $qMp, 4) : 0 ?>
    },
    <?php endforeach; ?>
];

// Pending prefill payload — sent once iframe signals ready
let pendingPrefill = null;

function openQuestionModal(questionUid, questionType, questionIndex) {
    const modal   = document.getElementById('questionModal');
    const iframe  = document.getElementById('questionIframe');
    const loading = document.getElementById('modalLoading');
    const qData   = questionsData[questionIndex];

    const parts      = questionUid.split('-');
    const questionId = parts[1] || qData.id;

    // Parse stored answer and correct_answer from JSON if needed
    let answerData = qData.answer;
    try { const p = JSON.parse(qData.answer); if (Array.isArray(p) || typeof p === 'object') answerData = p; } catch(e) {}

    let correctData = qData.correct_answer;
    try { const p = JSON.parse(qData.correct_answer); if (Array.isArray(p) || typeof p === 'object') correctData = p; } catch(e) {}

    pendingPrefill = {
        type: 'prefill',
        answer: answerData,
        correct_answer: correctData,
        initial_answer: answerData,
        isCorrect: qData.isCorrect,
        score: qData.score,
        showRationale: true,
        isReview: true
    };

    // Show loading, hide iframe
    loading.style.display = 'flex';
    iframe.style.display  = 'none';
    iframe.src = '';

    modal.classList.add('active');

    // Small delay lets the modal render before setting src
    setTimeout(() => {
        iframe.src = `${questionType}/index.php?id=${questionId}&t=${Date.now()}`;
    }, 50);
}

function closeQuestionModal() {
    document.getElementById('questionModal').classList.remove('active');
    document.getElementById('questionIframe').src = '';
    document.getElementById('questionIframe').style.display = 'none';
    document.getElementById('modalLoading').style.display   = 'flex';
    pendingPrefill = null;
}

// Listen for ready signal from iframe, then send prefill
window.addEventListener('message', (e) => {
    if (e.data && e.data.type === 'ready' && pendingPrefill) {
        const iframe = document.getElementById('questionIframe');
        try {
            iframe.contentWindow.postMessage(pendingPrefill, '*');
        } catch(err) {
            console.error('prefill postMessage failed:', err);
        }
        // Show iframe, hide loader
        iframe.style.display  = '';
        document.getElementById('modalLoading').style.display = 'none';
    }
});

// Fallback: if iframe doesn't send ready within 2s, send anyway
let _iframeReadyTimer = null;
const _origOpen = openQuestionModal;
document.getElementById('questionModal').addEventListener('click', function(e) {
    if (e.target === this) closeQuestionModal();
});

// Override to attach fallback timer
window.openQuestionModal = function(questionUid, questionType, questionIndex) {
    clearTimeout(_iframeReadyTimer);
    _origOpen(questionUid, questionType, questionIndex);
    _iframeReadyTimer = setTimeout(() => {
        if (!pendingPrefill) return;
        const iframe = document.getElementById('questionIframe');
        try { iframe.contentWindow.postMessage(pendingPrefill, '*'); } catch(e) {}
        iframe.style.display  = '';
        document.getElementById('modalLoading').style.display = 'none';
        pendingPrefill = null;
    }, 2000);
};

// Pagination
const ITEMS_PER_PAGE = 10;
let currentPage = 1;
const allRows   = document.querySelectorAll('.q-row');
const totalPages = Math.ceil(allRows.length / ITEMS_PER_PAGE);

function showPage(page) {
    currentPage = page;
    const start = (page - 1) * ITEMS_PER_PAGE;
    const end   = start + ITEMS_PER_PAGE;
    allRows.forEach((row, idx) => { row.style.display = (idx >= start && idx < end) ? '' : 'none'; });
    document.getElementById('pageInfo').textContent = `Page ${page} of ${totalPages || 1}`;
    document.getElementById('prevBtn').disabled = page === 1;
    document.getElementById('nextBtn').disabled = page === totalPages || totalPages === 0;
}

function nextPage()     { if (currentPage < totalPages) showPage(currentPage + 1); }
function previousPage() { if (currentPage > 1)          showPage(currentPage - 1); }

showPage(1);
</script>
</body>
</html>
