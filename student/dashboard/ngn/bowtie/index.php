<?php
// bowtie/index.php
require_once '../../../../config.php';
// Suppress deprecated notices from json_decode(null) on older data rows
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id > 0) {
    $q = mysqli_query($con, "SELECT * FROM btq WHERE id = $id LIMIT 1");
} else {
    $q = mysqli_query($con, "SELECT * FROM btq ORDER BY RAND() LIMIT 1");
}
$data = mysqli_fetch_assoc($q);
if (!$data) die('Question not found.');

// Use ?: '[]' so json_decode always receives a non-null string
$actions    = json_decode($data['actionToTake']        ?: '[]', true) ?: [];
$conditions = json_decode($data['potentialConditions'] ?: '[]', true) ?: [];
$parameters = json_decode($data['parameterToMonitor']  ?: '[]', true) ?: [];
$rationale = $data['rationale'] ?? '';
$furtherinfo = $data['furtherinfo'] ?? '';
$image = $data['image'] ?? '';

// Tabs: spec §1.2 — JSON array of {title, content[]} objects from the `tabs` DB field.
$tabs_data = json_decode(($data['tabs'] ?? '') ?: '[]', true) ?: [];
if (empty($tabs_data)) {
    // Legacy fallback: build tabs from old separate columns
    $nn = json_decode(($data['nursesNotes'] ?? '') ?: '[]', true) ?: [];
    $vs = json_decode(($data['vitalSigns']  ?? '') ?: '[]', true) ?: [];
    $dx = json_decode(($data['diagnostics'] ?? '') ?: '[]', true) ?: [];
    if (!empty($nn))  $tabs_data[] = ['title' => "Nurses' Notes", 'content' => $nn];
    if (!empty($vs))  $tabs_data[] = ['title' => 'Vital Signs',   'content' => $vs];
    if (!empty($dx))  $tabs_data[] = ['title' => 'Diagnostics',   'content' => $dx];
}
$hasTabs = !empty($tabs_data);

$correctActions = []; $correctConditions = []; $correctParameters = [];
foreach ($actions as $a) if (!empty($a['correct'])) $correctActions[] = $a['text'];
foreach ($conditions as $c) if (!empty($c['correct'])) $correctConditions[] = $c['text'];
foreach ($parameters as $p) if (!empty($p['correct'])) $correctParameters[] = $p['text'];

// Fetch Stats
$topic = $data['topic'] ?? 'General';
$system = $data['system'] ?? 'N/A';
$cnc = $data['cnc'] ?? 'N/A';
$dlevel = $data['dlevel'] ?? 'N/A';
$concept = $data['concept'] ?? 'General';
$narcan = $data['narcan'] ?? 'N/A';
$q_uid = 'bowtie_' . $data['id']; 
$peer_q = mysqli_query($con, "SELECT AVG(isCorrect) * 100 as avg_score FROM exam_results WHERE question_uid = '$q_uid'");
$peer_data = mysqli_fetch_assoc($peer_q);
$avg_peer_score = $peer_data['avg_score'] ? round($peer_data['avg_score'], 1) . '%' : 'N/A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NCLEX NGN Bow-Tie</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Mobile Drag & Drop Polyfill -->
<script src="https://bernardo-castilho.github.io/DragDropTouch/DragDropTouch.js"></script>
<style>
:root {
  --primary: #0a1628;
  --accent: #3b82f6;
  --success: #10b981;
  --danger: #ef4444;
  --bg: #f8fafc;
  --surface: #ffffff;
  --border: #e2e8f0;
  --text: #0f172a;
  --text-muted: #64748b;
  --drop-bg: #eff6ff;
}

* { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; margin: 0; padding: 0; }
body {
  font-family: 'Inter', sans-serif;
  background: var(--bg);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.app-container {
  display: flex;
  flex-direction: column;
  height: 100%;
  width: 100%;
}

.main-container {
  display: flex;
  flex: 1;
  overflow: hidden;
}

/* Moved responsive block to bottom of cascade */

/* EXHIBITS */
.left-panel {
  width: 40%;
  background: white;
  border-right: 2px solid var(--border);
  display: flex;
  flex-direction: column;
}

.panel-title {
  padding: 16px 20px;
  background: #f1f5f9;
  font-weight: 800;
  font-size: 11px;
  text-transform: uppercase;
  color: var(--text-muted);
  letter-spacing: 1px;
  border-bottom: 1px solid var(--border);
}

.tabs-row {
  display: flex;
  padding: 8px 12px 0;
  gap: 4px;
  border-bottom: 1px solid var(--border);
  overflow-x: auto;
  overflow-y: hidden;
  flex-shrink: 0;
  scrollbar-width: none; /* Hide scrollbar by default in Firefox */
}

.tabs-row::-webkit-scrollbar {
  height: 3px;
}
.tabs-row::-webkit-scrollbar-track {
  background: transparent;
}
.tabs-row::-webkit-scrollbar-thumb {
  background: transparent;
  border-radius: 10px;
}
.tabs-row:hover::-webkit-scrollbar-thumb {
  background: #cbd5e1;
}
.tabs-row:hover {
  scrollbar-width: thin; /* Show on hover for Firefox */
}

.tab-btn {
  padding: 10px 16px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  border-radius: 8px 8px 0 0;
  color: var(--text-muted);
}

.tab-btn.active {
  background: var(--bg);
  color: var(--accent);
  border: 1px solid var(--border);
  border-bottom-color: var(--bg);
  margin-bottom: -1px;
}

.tab-content-area {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
}

.clinical-record {
    background: #fdfdfd;
    border: 1px solid #f1f5f9;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 8px;
    font-size: 14px;
    line-height: 1.5;
}

/* BOWTIE INTERFACE */
.right-panel {
  width: 60%;
  background: white;
  overflow-y: auto;
  padding: 32px;
}

.question-header {
  font-size: 17px;
  font-weight: 700;
  line-height: 1.6;
  margin-bottom: 32px;
}

/* THE DIAGRAM */
.diagram-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 20px;
  margin-bottom: 40px;
  position: relative;
}

.col-label {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 12px;
    text-align: center;
}

.diagram-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 12px;
  position: relative;
  z-index: 2;
}

.dropzone {
  min-height: 70px;
  background: var(--drop-bg);
  border: 2px dashed #bfdbfe;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px;
  text-align: center;
  font-size: 12px;
  color: #3b82f6;
  font-weight: 500;
  transition: all 0.2s;
  cursor: pointer;
  position: relative;
  z-index: 2;
}

.bowtie-svg {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 1;
}

.bowtie-line {
  stroke: #cbd5e1;
  stroke-width: 2;
  stroke-dasharray: 4 2;
  fill: none;
  vector-effect: non-scaling-stroke;
  opacity: 0.6;
}

.bowtie-knot {
  fill: #cbd5e1;
  opacity: 0.8;
}

@media (max-width: 900px) {
  .bowtie-svg { display: none; }
}

.dropzone:hover { background: #dbeafe; border-color: #60a5fa; }
.dropzone.center-slot {
  min-height: 100px;
  border: 3px solid #3b82f6;
  background: white;
  color: var(--primary);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
}

.choice-token {
  background: white;
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 10px 14px;
  font-size: 13px;
  font-weight: 600;
  cursor: grab;
  width: 100%;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  transition: transform 0.1s;
}
.choice-token:active { cursor: grabbing; transform: scale(0.98); }

/* Feedback states */
.dropzone.correct-reveal { border-color: var(--success); background: #f0fdf4; }
.dropzone.wrong-reveal { border-color: var(--danger); background: #fef2f2; }

/* CHOICE BANKS */
.banks-container {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 20px;
  background: #f8fafc;
  padding: 24px;
  border-radius: 16px;
}

.bank-col { display: flex; flex-direction: column; gap: 8px; }
.bank-header { font-size: 11px; font-weight: 800; color: #64748b; margin-bottom: 4px; text-transform: uppercase; }
.bank-list { min-height: 100px; display: flex; flex-direction: column; gap: 8px; }

.footer {
  padding: 16px 32px;
  background: white;
  border-top: 1px solid var(--border);
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}
@media (max-width: 600px) {
  .footer { padding: 16px; justify-content: stretch; }
  .footer .btn { width: 100%; font-size: 15px; padding: 14px; }
}

.btn { padding: 12px 28px; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer; border: none; }
.btn-primary { background: var(--primary); color: white; }
.btn-outline { background: transparent; border: 2px solid var(--border); color: var(--text-muted); }

#result {
  margin-top: 24px;
  padding: 24px;
  border-radius: 12px;
  background: #f8fafc;
  border-left: 4px solid var(--accent);
  display: none;
}

.previous-badge {
    display: none;
    background: #f1f5f9;
    color: #475569;
    font-size: 12px;
    font-weight: 600;
    padding: 8px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid #cbd5e1;
}

.nclex-tips {
    margin-top: 24px;
    padding: 20px;
    background: #f0fdf4;
    border-radius: 12px;
    border: 1px solid #bbf7d0;
}
.tips-title {
    font-weight: 800;
    color: #64748b;
    font-size: 11px;
    text-transform: uppercase;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.tip-highlight {
    background: #fef08a;
    padding: 2px 4px;
    border-radius: 4px;
    font-weight: 700;
    color: #854d0e;
    border-bottom: 1.5px solid #f59e0b;
    display: inline;
    line-height: 1;
    white-space: normal;
}
.stats-btn {
    background: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
    padding: 6px 10px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.stats-btn:hover {
    background: #e2e8f0;
    color: #0f172a;
}
.stats-btn i { font-size: 14px; color: #3b82f6; }
.tips-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.tips-list li {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    font-size: 14.5px;
    color: #15803d;
    margin-bottom: 10px;
    line-height: 1.5;
}
.tips-list li i {
    color: #22c55e;
    margin-top: 3px;
    flex-shrink: 0;
    font-size: 16px;
}
.rationale-image-wrapper {
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid var(--border);
}
.image-title {
    font-weight: 800;
    color: var(--text-muted);
    font-size: 11px;
    text-transform: uppercase;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* RESPONSIVE CSS MUST BE LAST IN CASCADE */
@media (max-width: 900px) {
  .main-container { flex-direction: column; overflow: visible; display: block; height: auto; }
  .left-panel, .right-panel { width: 100%; height: auto; flex: none; border-right: none; overflow: visible; }
  .left-panel { border-bottom: 2px solid var(--border); min-height: auto; max-height: 35vh; overflow-y: auto; }
  .right-panel { padding: 12px; min-height: auto; }
  .tabs-row { flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 4px; }

  /* Vertical "Hourglass" Layout for Ribbon Tie */
  .diagram-wrapper {
      flex-direction: column;
      overflow-x: hidden;
      justify-content: flex-start;
      gap: 20px;
      padding-bottom: 12px;
      width: 100%;
  }
  .diagram-col, .diagram-col[style*="flex: 1.2"] {
      width: 100%;
      min-width: unset;
      flex: 0 0 auto;
      flex-direction: row;
      flex-wrap: wrap;
      justify-content: center;
      gap: 12px;
  }
  .diagram-col .col-label {
      width: 100%;
      text-align: center;
      margin-bottom: 2px;
  }
  .diagram-col .dropzone {
      flex: 1 1 42%;
      max-width: 48%;
      min-height: 60px;
      padding: 6px;
      font-size: 11px;
      border-radius: 8px;
  }
  .diagram-col[style*="flex: 1.2"] .dropzone {
      flex: 1 1 80%;
      max-width: 80%;
      min-height: 70px;
  }

  .banks-container { grid-template-columns: 1fr; gap: 12px; padding: 16px; }
  .app-container { overflow-y: visible; height: auto; min-height: 100%; display: block; }
  body { overflow: auto; height: auto; min-height: 100%; display: block; }
}

@media (max-width: 480px) {
  .diagram-wrapper { gap: 16px; padding-bottom: 8px; }
  .diagram-col .dropzone { min-height: 50px; font-size: 10px; }
  .diagram-col[style*="flex: 1.2"] .dropzone { min-height: 60px; font-size: 11px; }
  .choice-token { padding: 8px 10px; font-size: 11.5px; border-radius: 8px; }
  .col-label { font-size: 10px; }
  .bank-header { font-size: 10px; }
  .banks-container { padding: 12px; border-radius: 12px; }
  .question-header { font-size: 14px; margin-bottom: 16px; line-height: 1.5; }
  .tab-btn { padding: 8px 10px; font-size: 11px; white-space: nowrap; flex-shrink: 0; }
  .clinical-record { padding: 10px 12px; font-size: 13px; line-height: 1.4; }
}

/* ── Mobile Tap-to-Select (≤768px) ── */
@media (max-width: 768px) {
  /* Hide the drag-and-drop banks; keep tokens in DOM for submit logic */
  .banks-container { display: none !important; }

  /* Dropzones become obviously tappable */
  .dropzone {
    cursor: pointer !important;
    border-style: solid !important;
    border-color: #93c5fd !important;
    background: #eff6ff !important;
    position: relative;
  }
  .dropzone::after {
    content: '\f078'; /* chevron-down */
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    font-size: 10px;
    color: #3b82f6;
    position: absolute;
    bottom: 4px;
    right: 6px;
    opacity: 0.6;
  }
  /* Already-filled slots */
  .dropzone.mobile-filled {
    border-color: #3b82f6 !important;
    background: white !important;
  }
  .dropzone.mobile-filled::after { content: '\f044'; opacity: 0.5; }
  /* After reveal, keep correct/wrong colours */
  .dropzone.correct-reveal { border-color: var(--success) !important; background: #f0fdf4 !important; }
  .dropzone.wrong-reveal   { border-color: var(--danger)  !important; background: #fef2f2 !important; }
  .dropzone.correct-reveal::after,
  .dropzone.wrong-reveal::after { content: ''; }
}

/* Mobile bottom-sheet picker */
.mobile-sheet {
  display: none;
  position: fixed;
  inset: 0;
  z-index: 9000;
}
.mobile-sheet.active { display: block; }

.mobile-sheet-backdrop {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0.45);
}

.mobile-sheet-panel {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  background: white;
  border-radius: 20px 20px 0 0;
  padding: 0 0 env(safe-area-inset-bottom, 12px);
  max-height: 75vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 -8px 32px rgba(0,0,0,0.15);
  animation: slideUp 0.22s ease;
}
@keyframes slideUp {
  from { transform: translateY(100%); }
  to   { transform: translateY(0); }
}

.mobile-sheet-handle {
  width: 40px; height: 4px;
  background: #cbd5e1; border-radius: 2px;
  margin: 12px auto 0;
}

.mobile-sheet-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 20px 10px;
  border-bottom: 1px solid #e2e8f0;
  flex-shrink: 0;
}
.mobile-sheet-title {
  font-size: 14px;
  font-weight: 800;
  color: #0f172a;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.mobile-sheet-close {
  background: #f1f5f9;
  border: none;
  width: 30px; height: 30px;
  border-radius: 50%;
  font-size: 16px;
  color: #64748b;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
}

.mobile-sheet-options {
  overflow-y: auto;
  flex: 1;
  padding: 12px 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.mobile-radio-label {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  color: #0f172a;
  transition: all 0.15s;
  user-select: none;
}
.mobile-radio-label:has(input:checked) {
  border-color: #3b82f6;
  background: #eff6ff;
  color: #1d4ed8;
  font-weight: 700;
}
.mobile-radio-label input[type="radio"] {
  width: 18px; height: 18px;
  accent-color: #3b82f6;
  flex-shrink: 0;
}

.mobile-sheet-footer {
  padding: 12px 16px;
  border-top: 1px solid #e2e8f0;
  flex-shrink: 0;
  display: flex;
  gap: 10px;
}
.mobile-sheet-confirm {
  flex: 1;
  padding: 14px;
  background: #0a1628;
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
}
.mobile-sheet-clear {
  padding: 14px 18px;
  background: #f1f5f9;
  color: #64748b;
  border: none;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
}
</style>
</head>
<body>

<div class="app-container">
    <div class="main-container">
        <!-- Exhibit — hidden when no tabs data -->
        <?php if ($hasTabs): ?>
        <div class="left-panel">
            <div class="panel-title">Clinical History</div>
            <div class="tabs-row">
                <?php foreach ($tabs_data as $i => $tab): ?>
                <div class="tab-btn <?= $i === 0 ? 'active' : '' ?>" data-tab="btab-<?= $i ?>"><?= htmlspecialchars($tab['title']) ?></div>
                <?php endforeach; ?>
            </div>
            <div class="tab-content-area">
                <?php foreach ($tabs_data as $i => $tab): ?>
                <div id="btab-<?= $i ?>" class="tab-pane" <?= $i > 0 ? 'style="display:none;"' : '' ?>>
                    <?php foreach ((array)($tab['content'] ?? []) as $item): ?>
                        <div class="clinical-record"><?= htmlspecialchars($item) ?></div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Question Area -->
        <div class="right-panel" <?= !$hasTabs ? 'style="width:100%;"' : '' ?>>
            <div class="previous-badge" id="prevBadge">
                <i class="fas fa-lock"></i> This Bow-Tie has been submitted and is now read-only.
            </div>

            <div class="question-header">
                <?= nl2br(htmlspecialchars($data['question'])) ?>
            </div>

            <div class="diagram-wrapper">
                <!-- Visual Bowtie Connections -->
                <svg class="bowtie-svg" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <path class="bowtie-line" d="M 18 35 L 50 55 L 18 75 M 82 35 L 50 55 L 82 75" />
                    <circle class="bowtie-knot" cx="50" cy="55" r="2" />
                </svg>
                <div class="diagram-col">
                    <div class="col-label">Actions to Take</div>
                    <div class="dropzone" data-type="action"><span>Drop Action Here</span></div>
                    <div class="dropzone" data-type="action"><span>Drop Action Here</span></div>
                </div>
                <div class="diagram-col" style="flex: 1.2;">
                    <div class="col-label">Condition</div>
                    <div class="dropzone center-slot" data-type="condition"><span>Drop Condition Here</span></div>
                </div>
                <div class="diagram-col">
                    <div class="col-label">Monitor Parameters</div>
                    <div class="dropzone" data-type="parameter"><span>Drop Parameter Here</span></div>
                    <div class="dropzone" data-type="parameter"><span>Drop Parameter Here</span></div>
                </div>
            </div>

            <div class="banks-container">
                <div class="bank-col">
                    <div class="bank-header">Actions Bank</div>
                    <div class="bank-list" data-type="action">
                        <?php foreach($actions as $a) echo "<div class='choice-token' draggable='true' data-type='action'>".htmlspecialchars($a['text'])."</div>"; ?>
                    </div>
                </div>
                <div class="bank-col">
                    <div class="bank-header">Conditions Bank</div>
                    <div class="bank-list" data-type="condition">
                        <?php foreach($conditions as $c) echo "<div class='choice-token' draggable='true' data-type='condition'>".htmlspecialchars($c['text'])."</div>"; ?>
                    </div>
                </div>
                <div class="bank-col">
                    <div class="bank-header">Parameters Bank</div>
                    <div class="bank-list" data-type="parameter">
                        <?php foreach($parameters as $p) echo "<div class='choice-token' draggable='true' data-type='parameter'>".htmlspecialchars($p['text'])."</div>"; ?>
                    </div>
                </div>
            </div>

            <div id="result">
                <div id="resSummary" style="font-weight:800; font-size:18px; margin-bottom:12px;"></div>
                <div style="font-weight:800; color:var(--text-muted); font-size:11px; text-transform:uppercase; margin-bottom:8px;">Rationale</div>
                <div id="rationaleText" style="font-size:14px; line-height:1.6; color:#475569;"></div>
            </div>
        </div>
    </div>

    <div class="footer">
        <button id="submitBtn" class="btn btn-primary">Submit Bow-Tie</button>
    </div>
</div>

<!-- Mobile Tap-to-Select Sheet (only active on ≤768px) -->
<div class="mobile-sheet" id="mobileSelectSheet">
    <div class="mobile-sheet-backdrop" id="mobileSheetBackdrop"></div>
    <div class="mobile-sheet-panel">
        <div class="mobile-sheet-handle"></div>
        <div class="mobile-sheet-header">
            <span class="mobile-sheet-title" id="mobileSheetTitle">Select Option</span>
            <button class="mobile-sheet-close" id="mobileSheetCloseBtn">&#x2715;</button>
        </div>
        <div class="mobile-sheet-options" id="mobileSheetOptions"></div>
        <div class="mobile-sheet-footer">
            <button class="mobile-sheet-clear" id="mobileSheetClearBtn">Clear</button>
            <button class="mobile-sheet-confirm" id="mobileSheetConfirmBtn">Confirm</button>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    const correctA = <?= json_encode($correctActions) ?>;
    const correctC = <?= json_encode($correctConditions) ?>;
    const correctP = <?= json_encode($correctParameters) ?>;
    const rationale = <?= json_encode($rationale) ?>;
    const furtherinfo = <?= json_encode($furtherinfo) ?>;
    const image = <?= json_encode($image) ?>;

    /* Stats Data */
    const _qStartTime = Date.now();
    const questionStats = {
        difficulty: <?= json_encode($dlevel) ?>,
        peerScore: <?= json_encode($avg_peer_score) ?>,
        concept: <?= json_encode($concept) ?>,
        topic: <?= json_encode($topic) ?>,
        system: <?= json_encode($system) ?>,
        cnc: <?= json_encode($cnc) ?>,
        type: 'Bow-Tie'
    };

    window.showStatsModal = function() {
        const secs = Math.round((Date.now() - _qStartTime) / 1000);
        const timeTaken = secs < 60 ? secs + ' s' : Math.floor(secs/60) + ' m ' + (secs%60) + ' s';
        Swal.fire({
            title: '<i class="fas fa-chart-bar" style="color:#3b82f6; margin-right:6px;"></i> Statistics',
            html: `
                <div style="text-align:left; padding:4px 0;">
                    <div style="display:flex; gap:10px; margin-bottom:16px;">
                        <div style="flex:1; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px; text-align:center;">
                            <div style="font-size:18px; margin-bottom:4px;"><i class="fas fa-gauge-high" style="color:#f59e0b;"></i></div>
                            <div style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Difficulty level</div>
                            <div style="font-size:13px; font-weight:800; color:#0f172a;">${questionStats.difficulty}</div>
                        </div>
                        <div style="flex:1; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px; text-align:center;">
                            <div style="font-size:18px; margin-bottom:4px;"><i class="fas fa-users" style="color:#8b5cf6;"></i></div>
                            <div style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Avg. Peers Score</div>
                            <div style="font-size:13px; font-weight:800; color:#10b981;">${questionStats.peerScore}</div>
                        </div>
                        <div style="flex:1; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px; text-align:center;">
                            <div style="font-size:18px; margin-bottom:4px;"><i class="fas fa-hourglass-half" style="color:#3b82f6;"></i></div>
                            <div style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Time taken</div>
                            <div style="font-size:13px; font-weight:800; color:#0f172a;">${timeTaken}</div>
                        </div>
                    </div>
                    <div style="border-top:1px solid #e2e8f0; padding-top:14px; display:flex; flex-direction:column; gap:10px;">
                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <span style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; min-width:130px;">Subject</span>
                            <span style="background:#eff6ff; color:#3b82f6; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">${questionStats.concept}</span>
                            <span style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">Lesson</span>
                            <span style="background:#eff6ff; color:#3b82f6; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">${questionStats.topic}</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <span style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; min-width:130px;">Client Need Area</span>
                            <span style="background:#f0fdf4; color:#16a34a; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">${questionStats.cnc}</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <span style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; min-width:130px;">Client Need Topic</span>
                            <span style="background:#f0fdf4; color:#16a34a; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">${questionStats.system}</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <span style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; min-width:130px;">Question Type</span>
                            <span style="background:#fef3c7; color:#d97706; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">${questionStats.type}</span>
                        </div>
                    </div>
                </div>
            `,
            confirmButtonText: 'Got it',
            confirmButtonColor: '#3b82f6',
            width: '500px'
        });
    };

    let isReviewMode = false;
    let initialAnswers = {};
    let hasInteracted = false;
    
    // Capture initial state on page load (for fresh exams)
    function captureInitialState() {
        if(Object.keys(initialAnswers).length === 0) {
            let a = {}, c = {}, p = {};
            let idx = 0;
            $('.dropzone[data-type="action"]').each(function(){ a[idx++] = $(this).find('.choice-token').text().trim(); });
            idx = 0;
            $('.dropzone[data-type="condition"]').each(function(){ c[idx++] = $(this).find('.choice-token').text().trim(); });
            idx = 0;
            $('.dropzone[data-type="parameter"]').each(function(){ p[idx++] = $(this).find('.choice-token').text().trim(); });
            initialAnswers = {actions: a, conditions: c, parameters: p};
        }
    }
    setTimeout(captureInitialState, 50);
    
    // Tabs
    $('.tab-btn').click(function(){
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.tab-pane').hide();
        $('#' + $(this).data('tab')).show();
    });

    // Drag Handle
    let dragged = null;
    $(document).on('dragstart', '.choice-token', function(e){
        dragged = this;
        e.originalEvent.dataTransfer.setData('text/plain', ''); 
    });

    $('.dropzone').on('dragover', function(e){ e.preventDefault(); });
    $('.dropzone').on('drop', function(e){
        e.preventDefault();
        if(isReviewMode) return; // review mode
        if(!dragged) return;
        
        hasInteracted = true;  // Mark that user has interacted
        
        if(dragged.dataset.type !== this.dataset.type) {
            Swal.fire({ icon:'error', title:'Wrong Section', text:'This item belongs in the ' + dragged.dataset.type + ' section.' });
            return;
        }
        
        let existing = $(this).find('.choice-token');
        if(existing.length > 0) $(`.bank-list[data-type="${dragged.dataset.type}"]`).append(existing);
        
        $(this).find('span').hide();
        $(this).append(dragged);
    });

    $('.bank-list').on('dragover', function(e){ e.preventDefault(); });
    $('.bank-list').on('drop', function(e){
        e.preventDefault();
        if(dragged && dragged.dataset.type === this.dataset.type) {
            $(this).append(dragged);
            $('.dropzone').each(function(){
                if($(this).find('.choice-token').length === 0) $(this).find('span').show();
            });
        }
    });

    function reveal(summary, prevInitial = null, revealCorrect = false, revealEarned = 0, revealMax = 5) {
        $('.dropzone').removeClass('correct-reveal wrong-reveal omitted-reveal');

        $('.dropzone').each(function(){
            let token = $(this).find('.choice-token');
            if(token.length === 0) return;
            let txt = token.text().trim();
            let type = this.dataset.type;
            let checkList = (type === 'action') ? correctA : (type === 'condition' ? correctC : correctP);

            if(checkList.includes(txt)) {
                $(this).addClass('correct-reveal');
            } else {
                $(this).addClass('wrong-reveal');
            }
        });

        let summaryHtml = `
            <div style="display:flex; align-items:center; justify-content: space-between; width: 100%;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <i class="fas ${revealCorrect ? 'fa-check-circle' : 'fa-times-circle'}" style="color:${revealCorrect ? '#10b981' : '#ef4444'}; font-size:18px;"></i>
                    <span style="color:${revealCorrect ? '#10b981' : '#ef4444'}; font-size:16px;">${revealCorrect ? 'Correct' : 'Incorrect'} (${revealEarned}/${revealMax})</span>
                </div>
                <button class="stats-btn" onclick="showStatsModal()">
                    <i class="fas fa-info-circle"></i> Question Info
                </button>
            </div>
        `;
        $('#resSummary').html(summaryHtml);
        
        let resultHtml = rationale ? rationale.replace(/\n/g, '<br>') : "No rationale provided.";
        
        if (furtherinfo) {
            let tips = [];
            try {
                let decoded = JSON.parse(furtherinfo);
                if (Array.isArray(decoded)) tips = decoded;
                else tips = [furtherinfo];
            } catch (e) {
                tips = furtherinfo.split('\n').filter(l => l.trim() !== '');
            }

            resultHtml += '<div class="nclex-tips">';
            resultHtml += '<div class="tips-title"><i class="fas fa-lightbulb"></i> NCLEX Tips & Further Information</div>';
            resultHtml += '<ul class="tips-list">';
            tips.forEach(t => {
                // Collapse newlines and extra spaces for a continuous sentence
                let cleanTip = t.replace(/\s+/g, ' ').trim();
                // Highlight words wrapped in %
                let highlighted = cleanTip.replace(/%([^%]+)%/g, '<span class="tip-highlight">$1</span>');
                resultHtml += '<li><i class="fas fa-check-circle"></i> <span>' + highlighted + '</span></li>';
            });
            resultHtml += '</ul></div>';
        }
        
        if (image) {
            resultHtml += '<div class="rationale-image-wrapper">';
            resultHtml += '<div class="image-title"><i class="fas fa-image"></i> Related Illustration</div>';
            resultHtml += '<img src="../../../../admin/dashboard/pages/uploads/' + image + '" alt="NCLEX Illustration" style="max-width:100%; border-radius:12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">';
            resultHtml += '</div>';
        }

        $('#rationaleText').html(resultHtml);
        $('#result').fadeIn();
        $('.choice-token').attr('draggable', false);
        $('#submitBtn').hide();
    }



    window.addEventListener('message', (e) => {
        if(e.data.type === 'prefill' || e.data.type === 'previous') {
            isReviewMode = e.data.isReview ?? false;
            const ans = e.data.answer || {};
            let prevInitial = e.data.initial_answer || {};
            
            // Handle JSON-encoded initial_answer from database
            if(typeof prevInitial === 'string') {
                try { prevInitial = JSON.parse(prevInitial); } catch(er) { prevInitial = {}; }
            }
            
            let filled = false;
            
            // Track initial answers
            initialAnswers = Object.keys(prevInitial).length > 0 ? prevInitial : ans;
            
            // Restoring answers globally with improved matching
            const types = ['action','condition','parameter'];
            types.forEach(type => {
                let vals = ans[type+'s'] || ans[type] || [];
                if (!Array.isArray(vals)) vals = [vals]; // Handle single values

                vals.forEach((v, i) => {
                    if(!v) return;
                    filled = true;
                    // Normalized match: trim and lowercase
                    let matchVal = v.toString().trim().toLowerCase();
                    
                    let targetZone = $(`.dropzone[data-type="${type}"]:eq(${i})`);
                    let token = $(`.choice-token[data-type="${type}"]`).filter(function(){ 
                        return $(this).text().trim().toLowerCase() === matchVal; 
                    }).first();
                    
                    if(token.length && targetZone.length) {
                        targetZone.find('span').hide();
                        targetZone.empty().append(token);
                        targetZone.addClass('mobile-filled');
                    }
                });
            });

            if(filled) {
                $('#prevBadge').show();
                if(e.data.showRationale) {
                    let s_val = parseFloat(e.data.score || 0);
                    let e_pts = e.data.earned_points || Math.round(s_val * 5);
                    reveal("Score: " + Math.round(s_val*100) + "% ("+e_pts+"/5 pts)", prevInitial, e_pts === 5, e_pts, 5);
                }
            }
        }
    });

    // Signal parent that this iframe is ready to receive prefill data
    if (window.parent !== window) window.parent.postMessage({ type: 'ready' }, '*');

    $('#submitBtn').on('click', function(){
        if($(this).prop('disabled') || isReviewMode) return;
        $(this).prop('disabled', true); // prevent double-click

        let incomplete = false;
        $('.dropzone').each(function(){ if($(this).find('.choice-token').length === 0) incomplete = true; });
        if(incomplete) {
            $(this).prop('disabled', false);
            Swal.fire({ icon:'warning', title:'Incomplete', text:'Please fill all slots in the diagram.' });
            return;
        }
        
        // Capture initial if not done yet (safety net)
        if(!hasInteracted){
            hasInteracted = true;
            let a=[], c=[], p=[];
            $('.dropzone[data-type="action"]').each(function(){ a.push($(this).find('.choice-token').text().trim()); });
            $('.dropzone[data-type="condition"]').each(function(){ c.push($(this).find('.choice-token').text().trim()); });
            $('.dropzone[data-type="parameter"]').each(function(){ p.push($(this).find('.choice-token').text().trim()); });
            initialAnswers = {actions: a, conditions: c, parameters: p};
        }

        let earned = 0;
        $('.dropzone').each(function(){
            let txt = $(this).find('.choice-token').text().trim().toLowerCase();
            let type = this.dataset.type;
            let list = (type === 'action') ? correctA : (type === 'condition' ? correctC : correctP);
            
            // Normalize correct list as well
            if(list.map(s => s.toString().trim().toLowerCase()).includes(txt)) earned++;
        });

        let total = 5;
        let normalized = parseFloat((earned / total).toFixed(2));

        let userA = [], userC = [], userP = [];
        $('.dropzone[data-type="action"]').each(function(){ userA.push($(this).find('.choice-token').text().trim()); });
        $('.dropzone[data-type="condition"]').each(function(){ userC.push($(this).find('.choice-token').text().trim()); });
        $('.dropzone[data-type="parameter"]').each(function(){ userP.push($(this).find('.choice-token').text().trim()); });

        let selected = {actions: userA, conditions: userC, parameters: userP};

        // Pass scoring vars explicitly so reveal() can reference them
        reveal("Score: " + Math.round(normalized*100) + "% ("+earned+"/"+total+" pts)", null, earned === total, earned, total);
        
        // Calculate changes
        let changesData = null;
        if(JSON.stringify(initialAnswers) !== JSON.stringify(selected)){
            changesData = {
                modified_count: 1,
                changed: true
            };
        }

        const payload = {
            type: 'answered',
            answer: selected,
            initial_answer: Object.keys(initialAnswers).length > 0 ? initialAnswers : null,
            correctAnswer: {actions: correctA, conditions: correctC, parameters: correctP},
            correct: earned === total,
            isCorrect: earned === total,
            score: normalized,
            max_points: total,
            earned_points: earned,
            changes: changesData,
            rationale: rationale,
            question_id: <?= json_encode($data['id']) ?>,
            question_type: 'bowtie'
        };
        // Send to parent; retry once after short delay to handle timing edge cases
        window.parent.postMessage(payload, '*');
        setTimeout(() => {
            if (window.parent !== window) window.parent.postMessage(payload, '*');
        }, 300);
    });

    // ===== AUTO-SCROLL FOR DRAG & DROP ON MOBILE =====
    let isDragging = false;
    let autoScrollInterval = null;

    // Detect when dragging starts
    $(document).on('dragstart', '.choice-token', function(e){
        isDragging = true;
    });

    // Detect when dragging ends
    $(document).on('dragend', '.choice-token', function(e){
        isDragging = false;
        if (autoScrollInterval) clearInterval(autoScrollInterval);
    });

    // Auto-scroll while dragging on desktop/mouse
    $(document).on('dragover', function(e){
        if (!isDragging) return;

        const mouseY = e.clientY;
        const scrollThreshold = 100; // pixels from top/bottom
        let scrollAmount = 0;

        // Check if mouse is near top of viewport
        if (mouseY < scrollThreshold) {
            scrollAmount = -10; // scroll up
        }
        // Check if mouse is near bottom of viewport
        else if (mouseY > window.innerHeight - scrollThreshold) {
            scrollAmount = 10; // scroll down
        }

        if (scrollAmount !== 0) {
            window.scrollBy(0, scrollAmount);
        }
    });

    // For touch/mobile drag - also monitor drag events on drop zones
    document.addEventListener('dragover', function(e){
        if (!isDragging) return;

        // Get touch position if available (from DragDropTouch polyfill)
        let clientY = e.clientY;
        if (clientY === 0 && e.touches && e.touches[0]) {
            clientY = e.touches[0].clientY;
        }

        const scrollThreshold = 100;
        if (clientY < scrollThreshold) {
            window.scrollBy(0, -10);
        } else if (clientY > window.innerHeight - scrollThreshold) {
            window.scrollBy(0, 10);
        }
    }, true); // Use capture phase for better responsiveness

    // Final fallback for touch end
    $(document).on('touchend', function(){
        isDragging = false;
        if (autoScrollInterval) clearInterval(autoScrollInterval);
    });

    // ===== MOBILE TAP-TO-SELECT =====
    const isMobile = () => window.innerWidth <= 768;

    let mobileTargetZone = null;

    // All option texts by type (gathered from the bank at load time)
    function getAllOptionsForType(type) {
        const opts = [];
        // From the hidden bank
        $(`.bank-list[data-type="${type}"] .choice-token`).each(function() {
            const t = $(this).text().trim();
            if (t) opts.push(t);
        });
        // From dropzones of that type (already placed)
        $(`.dropzone[data-type="${type}"] .choice-token`).each(function() {
            const t = $(this).text().trim();
            if (t && !opts.includes(t)) opts.push(t);
        });
        return opts;
    }

    function openMobileSheet(zone) {
        if (!isMobile()) return;
        if (isReviewMode) return;

        mobileTargetZone = zone;
        const type = zone.dataset.type;
        const labels = { action: 'Actions to Take', condition: 'Condition', parameter: 'Monitor Parameters' };
        document.getElementById('mobileSheetTitle').textContent = labels[type] || type;

        const opts = getAllOptionsForType(type);
        const currentVal = $(zone).find('.choice-token').text().trim();

        let html = '';
        opts.forEach((txt, i) => {
            const checked = txt === currentVal ? 'checked' : '';
            const safeId = 'mopt_' + i;
            html += `<label class="mobile-radio-label" for="${safeId}">
                <input type="radio" id="${safeId}" name="mobileOpt" value="${escapeHtml(txt)}" ${checked}>
                <span>${escapeHtml(txt)}</span>
            </label>`;
        });
        document.getElementById('mobileSheetOptions').innerHTML = html;
        document.getElementById('mobileSelectSheet').classList.add('active');
    }

    function closeMobileSheet() {
        document.getElementById('mobileSelectSheet').classList.remove('active');
        mobileTargetZone = null;
    }

    function escapeHtml(s) {
        return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function placeTokenInZone(zone, tokenText) {
        const type = zone.dataset.type;

        // If zone already has a token, return it to bank
        const existing = $(zone).find('.choice-token');
        if (existing.length) {
            $(`.bank-list[data-type="${type}"]`).append(existing);
            $(zone).find('span').show();
        }

        // Find the token (first check bank, then other dropzones of same type)
        let token = $(`.bank-list[data-type="${type}"] .choice-token`).filter(function() {
            return $(this).text().trim() === tokenText;
        }).first();

        if (!token.length) {
            token = $(`.dropzone[data-type="${type}"] .choice-token`).filter(function() {
                return $(this).text().trim() === tokenText;
            }).first();
            if (token.length) {
                // Return the other zone's placeholder span
                const otherZone = token.closest('.dropzone');
                token.detach();
                otherZone.find('span').show();
                otherZone.removeClass('mobile-filled');
            }
        } else {
            token.detach();
        }

        if (token.length) {
            $(zone).find('span').hide();
            $(zone).empty().append(token);
            $(zone).addClass('mobile-filled');
            hasInteracted = true;
        }
    }

    // Dropzone tap handler (mobile only)
    $(document).on('click', '.dropzone', function(e) {
        if (!isMobile()) return;
        if (isReviewMode) return;
        e.stopPropagation();
        openMobileSheet(this);
    });

    // Confirm selection
    document.getElementById('mobileSheetConfirmBtn').addEventListener('click', function() {
        const selected = document.querySelector('input[name="mobileOpt"]:checked');
        if (selected && mobileTargetZone) {
            placeTokenInZone(mobileTargetZone, selected.value);
        }
        closeMobileSheet();
    });

    // Clear slot
    document.getElementById('mobileSheetClearBtn').addEventListener('click', function() {
        if (!mobileTargetZone) { closeMobileSheet(); return; }
        const type = mobileTargetZone.dataset.type;
        const existing = $(mobileTargetZone).find('.choice-token');
        if (existing.length) {
            $(`.bank-list[data-type="${type}"]`).append(existing);
            $(mobileTargetZone).find('span').show();
            $(mobileTargetZone).removeClass('mobile-filled');
        }
        closeMobileSheet();
    });

    // Close sheet via backdrop or X button
    document.getElementById('mobileSheetBackdrop').addEventListener('click', closeMobileSheet);
    document.getElementById('mobileSheetCloseBtn').addEventListener('click', closeMobileSheet);
});
</script>
</body>
</html>