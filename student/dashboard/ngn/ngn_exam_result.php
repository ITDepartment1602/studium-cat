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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --accent:  <?= $cat['color'] ?>;
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

        /* ── NAVBAR ── */
        .navbar {
            height: 52px; background: #fff;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center;
            padding: 0 28px; gap: 10px;
        }
        .nb-brand { display: flex; align-items: center; gap: 8px; text-decoration: none; flex: 1; }
        .nb-name  { font-size: 13px; font-weight: 700; color: #0f172a; }
        .nb-sep   { width: 1px; height: 14px; background: var(--border); }
        .nb-ctx   { font-size: 11px; color: #94a3b8; }
        .nb-btn {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 11.5px; font-weight: 500; color: #64748b;
            text-decoration: none; padding: 5px 12px;
            border: 1px solid var(--border); border-radius: 6px;
            background: #f8fafc; transition: all .15s; white-space: nowrap;
        }
        .nb-btn:hover { color: var(--accent); border-color: var(--accent); }

        /* ── RESULT HEADER ── */
        .result-header {
            background: #fff;
            border-bottom: 1px solid var(--border);
            border-top: 3px solid var(--accent);
            padding: 32px 40px 0;
        }
        .rh-top { display: flex; align-items: flex-start; gap: 32px; padding-bottom: 24px; }
        .rh-left { flex: 1; min-width: 0; }

        .eyebrow {
            font-size: 10px; font-weight: 600; letter-spacing: 1.2px;
            text-transform: uppercase; color: #94a3b8; margin-bottom: 10px;
        }
        .score-display {
            font-size: 72px; font-weight: 800;
            letter-spacing: -4px; line-height: .9;
            margin-bottom: 10px;
            color: var(--accent);
        }
        .score-sub {
            font-size: 13.5px; font-weight: 300;
            color: #64748b; line-height: 1.6; margin-bottom: 12px;
        }
        .cat-badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 14px; border-radius: 20px;
            font-size: 12px; font-weight: 800; text-transform: uppercase;
            background: <?= $cat['bg'] ?>; color: <?= $cat['color'] ?>;
        }

        /* stat strip */
        .stat-strip {
            flex-shrink: 0;
            display: flex; align-items: stretch;
            border: 1px solid var(--border); border-radius: 10px;
            overflow: hidden; background: #f8fafc;
            align-self: center;
        }
        .stat-item {
            padding: 14px 22px;
            display: flex; flex-direction: column; gap: 4px;
            min-width: 100px;
        }
        .stat-item + .stat-item { border-left: 1px solid var(--border); }
        .stat-val {
            font-size: 22px; font-weight: 700;
            letter-spacing: -.5px; color: #0f172a; line-height: 1;
        }
        .stat-lbl {
            font-size: 10px; font-weight: 600;
            color: #94a3b8; text-transform: uppercase; letter-spacing: .8px;
        }

        /* tab bar */
        .rh-tabs { display: flex; border-top: 1px solid #f1f5f9; }
        .rh-tab {
            font-size: 11.5px; font-weight: 500; color: #94a3b8;
            padding: 10px 18px; border-bottom: 2px solid transparent;
            cursor: default;
        }
        .rh-tab.active { color: var(--accent); border-bottom-color: var(--accent); font-weight: 600; }

        /* ── BODY ── */
        .result-body { padding: 28px 40px 56px; }
        .body-grid {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 20px;
        }
        .body-main { min-width: 0; }
        .body-side { display: flex; flex-direction: column; gap: 16px; }

        /* ── Section card ── */
        .section {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 22px 24px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .section-last { margin-bottom: 0; }
        .section-title {
            font-size: 11px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.6px; color: var(--muted); margin-bottom: 16px;
        }

        /* ── Concept rows (CPR-style) ── */
        .concept-list { display: flex; flex-direction: column; gap: 12px; }
        .concept-row { display: flex; align-items: center; gap: 12px; }
        .concept-name {
            font-size: 13px; font-weight: 600; color: #334155;
            width: 140px; flex-shrink: 0;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .concept-bar-wrap { flex: 1; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; }
        .concept-bar-fill { height: 100%; border-radius: 4px; transition: width 0.5s ease; }
        .concept-pct { font-size: 12px; font-weight: 700; white-space: nowrap; min-width: 38px; text-align: right; }
        .concept-band {
            font-size: 10px; font-weight: 700; text-transform: uppercase;
            padding: 2px 8px; border-radius: 10px; white-space: nowrap;
        }

        /* ── Action buttons ── */
        .action-row { display: flex; gap: 10px; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 18px; border-radius: 12px;
            font-size: 13px; font-weight: 700; text-decoration: none;
            transition: all 0.2s ease; flex: 1; justify-content: center;
            border: none; cursor: pointer;
        }
        .btn-primary { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(37,99,235,.25); }
        .btn-outline { background: white; color: var(--text); border: 1px solid var(--border); }
        .btn-outline:hover { border-color: var(--accent); color: var(--accent); }

        /* ── Question Table ── */
        .q-table-wrapper { overflow-x: auto; }
        .q-table { width: 100%; border-collapse: collapse; }
        .q-table thead th {
            background: #f1f5f9; padding: 10px 12px;
            font-size: 11px; font-weight: 700; text-transform: uppercase;
            color: var(--muted); text-align: left; border-bottom: 1px solid var(--border);
        }
        .q-table tbody td { padding: 10px 12px; border-bottom: 1px solid var(--border); font-size: 13px; }
        .q-table tbody tr:last-child td { border-bottom: none; }
        .q-table .q-num-col { width: 40px; text-align: center; color: var(--muted); font-weight: 700; }
        .q-table tbody tr.correct { background: rgba(16,185,129,0.03); }
        .q-table tbody tr.incorrect { background: rgba(239,68,68,0.03); }
        .q-table .q-correct { color: #10b981; font-weight: 600; }
        .q-table .q-incorrect { color: #ef4444; font-weight: 600; }
        .q-table .q-score-col { min-width: 80px; text-align: center; font-weight: 700; }
        .q-table .q-action-col { width: 70px; text-align: center; }
        .view-btn {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 6px 12px; border-radius: 8px;
            background: #3b82f6; color: white; border: none; cursor: pointer;
            font-size: 12px; font-weight: 600; transition: all .2s;
        }
        .view-btn:hover { background: #2563eb; transform: translateY(-1px); }

        /* ── Modal ── */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 16px; width: 98%; max-width: 1200px; max-height: 95vh; overflow: hidden; display: flex; flex-direction: column; }
        .modal-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid var(--border); flex-shrink: 0; }
        .modal-title { font-size: 16px; font-weight: 700; }
        .modal-close { background: none; border: none; font-size: 24px; color: var(--muted); cursor: pointer; }
        .modal-body { padding: 16px; overflow-y: auto; flex: 1; }
        .modal-loading { display: flex; align-items: center; justify-content: center; height: 200px; color: var(--muted); font-size: 14px; gap: 10px; }
        .question-iframe { width: 100%; height: 680px; border: none; }

        /* ── Pagination ── */
        .pagination { display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 20px; }
        .pagination-btn {
            padding: 8px 14px; border-radius: 8px; border: 1px solid var(--border);
            background: white; color: var(--text); cursor: pointer;
            font-size: 13px; font-weight: 600; transition: all .2s;
        }
        .pagination-btn:hover:not(:disabled) { background: #3b82f6; color: white; border-color: #3b82f6; }
        .pagination-btn:disabled { opacity: 0.4; cursor: not-allowed; }
        .pagination-info { font-size: 13px; color: var(--muted); font-weight: 600; }

        /* ── RESPONSIVE ── */
        @media (max-width: 900px) {
            .body-grid { grid-template-columns: 1fr; }
            .body-side { flex-direction: row; flex-wrap: wrap; }
            .body-side .section { flex: 1 1 300px; margin-bottom: 0; }
        }
        @media (max-width: 768px) {
            .result-header { padding: 20px 16px 0; }
            .rh-top { flex-direction: column; gap: 16px; }
            .stat-strip { width: 100%; }
            .stat-item { flex: 1; padding: 12px 10px; min-width: 0; }
            .stat-val { font-size: 18px; }
            .score-display { font-size: 52px; letter-spacing: -2px; }
            .result-body { padding: 16px; }
            .body-side { flex-direction: column; }
            .concept-name { width: 100px; }
            .action-row { flex-direction: column; }
            .q-table thead th:nth-child(3),
            .q-table tbody td:nth-child(3) { display: none; }
        }
        @media (max-width: 480px) {
            .stat-strip { flex-wrap: wrap; }
            .stat-item { flex: 1 1 45%; border-left: none !important; border-top: 1px solid var(--border); }
            .stat-item:nth-child(-n+2) { border-top: none; }
            .score-display { font-size: 44px; }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <a href="../index.php" class="nb-brand">
        <span class="nb-name">Studium</span>
    </a>
    <span class="nb-sep"></span>
    <span class="nb-ctx">NGN Exam Result</span>
    <a href="../index.php" class="nb-btn" style="margin-left:auto;">
        <i class="fas fa-home"></i> Dashboard
    </a>
</nav>

<!-- Result Header -->
<div class="result-header">
    <div class="rh-top">
        <div class="rh-left">
            <div class="eyebrow">NGN Session Complete</div>
            <div class="score-display"><?= $percent ?>%</div>
            <div class="score-sub">
                <?= date("M d, Y · h:i A", strtotime($summary['exam_time'])) ?>
                &nbsp;·&nbsp; <?= intval($summary['total_questions']) ?> questions
            </div>
            <div class="cat-badge">
                <i class="fas <?= $cat['icon'] ?>"></i> <?= $cat['label'] ?>
            </div>
        </div>

        <!-- Stat Strip -->
        <div class="stat-strip">
            <div class="stat-item">
                <div class="stat-val" style="color:<?= $cat['color'] ?>;"><?= $percent ?>%</div>
                <div class="stat-lbl">Score</div>
            </div>
            <div class="stat-item">
                <div class="stat-val"><?= intval($summary['total_questions']) ?></div>
                <div class="stat-lbl">Questions</div>
            </div>
            <div class="stat-item">
                <div class="stat-val"><?= round($totalEarned, 1) ?></div>
                <div class="stat-lbl">Pts Earned</div>
            </div>
            <div class="stat-item">
                <div class="stat-val"><?= count($topicRows) ?></div>
                <div class="stat-lbl">Topics</div>
            </div>
        </div>
    </div>
    <div class="rh-tabs">
        <div class="rh-tab active">Results Overview</div>
        <div class="rh-tab">Question Breakdown</div>
    </div>
</div>

<!-- Body -->
<div class="result-body">
    <div class="body-grid">

        <!-- Main column -->
        <div class="body-main">

            <!-- Concept Performance -->
            <div class="section">
                <div class="section-title">Concept Performance</div>
                <?php if ($topicRows): ?>
                <div class="concept-list">
                    <?php foreach ($topicRows as $t):
                        $tp = ($t['mp'] > 0) ? min(100, round(($t['ep'] / $t['mp']) * 100)) : 0;
                        if ($tp >= 75) { $bc = '#10b981'; $bandLabel = 'Strong'; $bandBg = '#d1fae5'; $bandColor = '#065f46'; }
                        elseif ($tp >= 50) { $bc = '#f59e0b'; $bandLabel = 'Average'; $bandBg = '#fef3c7'; $bandColor = '#92400e'; }
                        else { $bc = '#ef4444'; $bandLabel = 'Needs Work'; $bandBg = '#fee2e2'; $bandColor = '#991b1b'; }
                    ?>
                    <div class="concept-row">
                        <span class="concept-name" title="<?= htmlspecialchars($t['concept'] ?: 'General') ?>"><?= htmlspecialchars($t['concept'] ?: 'General') ?></span>
                        <div class="concept-bar-wrap">
                            <div class="concept-bar-fill" style="width:<?= $tp ?>%;background:<?= $bc ?>;"></div>
                        </div>
                        <span class="concept-pct" style="color:<?= $bc ?>;"><?= $tp ?>%</span>
                        <span class="concept-band" style="background:<?= $bandBg ?>;color:<?= $bandColor ?>;"><?= $bandLabel ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p style="color:var(--muted);font-size:13px;">No concept data available.</p>
                <?php endif; ?>
            </div>

            <!-- Question Breakdown -->
            <div class="section section-last">
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
                                <th class="q-action-col">Review</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($questions as $idx => $q):
                                $isOk      = intval($q['isCorrect']);
                                $qEp       = floatval($q['earned_points']);
                                $qMp       = floatval($q['max_points']);
                                $qNum      = $q['question_number'] ?? ($idx + 1);
                                $qType     = htmlspecialchars(strtoupper($q['question_type'] ?? ''));
                                $qTopic    = htmlspecialchars($q['topic'] ?: 'General');
                                $qTypeCode = strtolower($q['question_type']);
                            ?>
                            <tr class="q-row <?= $isOk ? 'correct' : 'incorrect' ?>" data-index="<?= $idx ?>">
                                <td class="q-num-col"><?= $qNum ?></td>
                                <td><?= $qType ?></td>
                                <td><?= $qTopic ?></td>
                                <td class="q-score-col"><?= round($qEp, 1) ?> / <?= round($qMp, 1) ?></td>
                                <td class="q-score-col <?= $isOk ? 'q-correct' : 'q-incorrect' ?>"><?= $isOk ? '✓ Correct' : '✗ Incorrect' ?></td>
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
                <div class="pagination">
                    <button class="pagination-btn" id="prevBtn" onclick="previousPage()"><i class="fas fa-chevron-left"></i> Prev</button>
                    <span class="pagination-info" id="pageInfo">Page 1 of 1</span>
                    <button class="pagination-btn" id="nextBtn" onclick="nextPage()">Next <i class="fas fa-chevron-right"></i></button>
                </div>
            </div>

        </div>

        <!-- Sidebar -->
        <div class="body-side">
            <div class="section section-last">
                <div class="section-title">Actions</div>
                <div class="action-row">
                    <a href="../index.php?start_ngn=1" class="btn btn-outline"><i class="fas fa-plus"></i> New Exam</a>
                    <a href="../index.php" class="btn btn-primary"><i class="fas fa-home"></i> Dashboard</a>
                </div>
            </div>
            <?php if ($topicRows): ?>
            <div class="section section-last">
                <div class="section-title">Topic Summary</div>
                <?php foreach ($topicRows as $t):
                    $tp = ($t['mp'] > 0) ? min(100, round(($t['ep'] / $t['mp']) * 100)) : 0;
                    $bc = $tp >= 75 ? '#10b981' : ($tp >= 50 ? '#f59e0b' : '#ef4444');
                ?>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                    <span style="font-size:13px;font-weight:600;color:#334155;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($t['concept'] ?: 'General') ?></span>
                    <span style="font-size:13px;font-weight:800;color:<?= $bc ?>;margin-left:10px;"><?= $tp ?>%</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
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
