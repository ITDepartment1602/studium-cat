<?php
require_once '../../../../config.php';
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id > 0) {
    $q = mysqli_query($con, "SELECT * FROM btq WHERE id = {$id} LIMIT 1");
} else {
    $q = mysqli_query($con, "SELECT * FROM btq ORDER BY RAND() LIMIT 1");
}
if (!$q) die('Database error: ' . htmlspecialchars(mysqli_error($con)));
$data = mysqli_fetch_assoc($q);
if (!$data) die('Question not found.');

$actions    = json_decode($data['actionToTake']        ?: '[]', true) ?: [];
$conditions = json_decode($data['potentialConditions'] ?: '[]', true) ?: [];
$parameters = json_decode($data['parameterToMonitor']  ?: '[]', true) ?: [];
$rationale  = $data['rationale'] ?? '';
$topic      = $data['topic']  ?? 'General';
$system     = $data['system'] ?? 'N/A';
$cnc        = $data['cnc']    ?? 'N/A';
$dlevel     = $data['dlevel'] ?? 'N/A';

$tabs_data = json_decode(($data['tabs'] ?? '') ?: '[]', true) ?: [];
if (empty($tabs_data)) {
    $nn = json_decode(($data['nursesNotes'] ?? '') ?: '[]', true) ?: [];
    $vs = json_decode(($data['vitalSigns']  ?? '') ?: '[]', true) ?: [];
    $dx = json_decode(($data['diagnostics'] ?? '') ?: '[]', true) ?: [];
    if (!empty($nn)) $tabs_data[] = ['title' => "Nurses' Notes", 'content' => $nn];
    if (!empty($vs)) $tabs_data[] = ['title' => 'Vital Signs',   'content' => $vs];
    if (!empty($dx)) $tabs_data[] = ['title' => 'Diagnostics',   'content' => $dx];
}
$hasTabs = !empty($tabs_data);

$correctActions    = [];
$correctConditions = [];
$correctParameters = [];
foreach ($actions    as $a) if (!empty($a['correct'])) $correctActions[]    = $a['text'];
foreach ($conditions as $c) if (!empty($c['correct'])) $correctConditions[] = $c['text'];
foreach ($parameters as $p) if (!empty($p['correct'])) $correctParameters[] = $p['text'];
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
<script src="https://bernardo-castilho.github.io/DragDropTouch/DragDropTouch.js"></script>
<style>
:root {
  --primary: #0a1628; --accent: #3b82f6; --success: #10b981; --danger: #ef4444;
  --bg: #f8fafc; --surface: #ffffff; --border: #e2e8f0; --text: #0f172a; --text-muted: #64748b; --drop-bg: #eff6ff;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; margin: 0; padding: 0; }
body { font-family: 'Inter', sans-serif; background: var(--bg); display: flex; flex-direction: column; overflow: hidden; }
.app-container { display: flex; flex-direction: column; height: 100%; width: 100%; }
.main-container { display: flex; flex: 1; overflow: hidden; }

.left-panel { width: 40%; background: white; border-right: 2px solid var(--border); display: flex; flex-direction: column; }
.panel-title { padding: 16px 20px; background: #f1f5f9; font-weight: 800; font-size: 11px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 1px; border-bottom: 1px solid var(--border); }
.tabs-row { display: flex; padding: 8px 12px 0; gap: 4px; border-bottom: 1px solid var(--border); overflow-x: auto; overflow-y: hidden; flex-shrink: 0; scrollbar-width: none; }
.tabs-row::-webkit-scrollbar { height: 3px; }
.tabs-row::-webkit-scrollbar-thumb { background: transparent; border-radius: 10px; }
.tabs-row:hover::-webkit-scrollbar-thumb { background: #cbd5e1; }
.tab-btn { padding: 10px 16px; font-size: 13px; font-weight: 600; cursor: pointer; border-radius: 8px 8px 0 0; color: var(--text-muted); }
.tab-btn.active { background: var(--bg); color: var(--accent); border: 1px solid var(--border); border-bottom-color: var(--bg); margin-bottom: -1px; }
.tab-content-area { flex: 1; overflow-y: auto; padding: 20px; }
.clinical-record { background: #fdfdfd; border: 1px solid #f1f5f9; padding: 12px 16px; border-radius: 8px; margin-bottom: 8px; font-size: 14px; line-height: 1.5; }

.right-panel { width: 60%; background: white; overflow-y: auto; padding: 32px; }
.question-header { font-size: 17px; font-weight: 700; line-height: 1.6; margin-bottom: 32px; }

.diagram-wrapper { display: flex; align-items: center; justify-content: center; gap: 20px; margin-bottom: 40px; position: relative; }
.col-label { font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 12px; text-align: center; }
.diagram-col { flex: 1; display: flex; flex-direction: column; gap: 12px; position: relative; z-index: 2; }
.dropzone { min-height: 70px; background: var(--drop-bg); border: 2px dashed #bfdbfe; border-radius: 12px; display: flex; align-items: center; justify-content: center; padding: 8px; text-align: center; font-size: 12px; color: #3b82f6; font-weight: 500; transition: all .2s; cursor: pointer; position: relative; z-index: 2; }
.bowtie-svg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 1; }
.bowtie-line { stroke: #cbd5e1; stroke-width: 2; stroke-dasharray: 4 2; fill: none; vector-effect: non-scaling-stroke; opacity: .6; }
.bowtie-knot { fill: #cbd5e1; opacity: .8; }
@media (max-width: 900px) { .bowtie-svg { display: none; } }
.dropzone:hover { background: #dbeafe; border-color: #60a5fa; }
.dropzone.center-slot { min-height: 100px; border: 3px solid #3b82f6; background: white; color: var(--primary); box-shadow: 0 4px 12px rgba(59,130,246,.1); }
.choice-token { background: white; border: 1px solid var(--border); border-radius: 10px; padding: 10px 14px; font-size: 13px; font-weight: 600; cursor: grab; width: 100%; box-shadow: 0 2px 4px rgba(0,0,0,.05); transition: transform .1s; }
.choice-token:active { cursor: grabbing; transform: scale(.98); }

.banks-container { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; background: #f8fafc; padding: 24px; border-radius: 16px; }
.bank-col { display: flex; flex-direction: column; gap: 8px; }
.bank-header { font-size: 11px; font-weight: 800; color: #64748b; margin-bottom: 4px; text-transform: uppercase; }
.bank-list { min-height: 100px; display: flex; flex-direction: column; gap: 8px; }

.footer { position: sticky; bottom: 0; background: white; border-top: 1px solid var(--border); padding: 16px 0 20px; display: flex; justify-content: center; z-index: 10; }
.btn { padding: 14px 40px; border-radius: 12px; font-weight: 800; font-size: 15px; cursor: pointer; border: none; transition: all .2s ease; min-width: 220px; display: flex; align-items: center; justify-content: center; gap: 10px; }
.btn-primary { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; box-shadow: 0 4px 14px rgba(59,130,246,.4); }
.btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(59,130,246,.5); }
.btn-primary:disabled { opacity: .45; cursor: not-allowed; transform: none !important; box-shadow: none; }

/* RESPONSIVE CSS MUST BE LAST */
@media (max-width: 900px) {
  .main-container { flex-direction: column; overflow: visible; display: block; height: auto; }
  .left-panel, .right-panel { width: 100%; height: auto; flex: none; border-right: none; overflow: visible; }
  .left-panel { border-bottom: 2px solid var(--border); min-height: auto; max-height: 35vh; overflow-y: auto; }
  .right-panel { padding: 12px; min-height: auto; }
  .diagram-wrapper { flex-direction: column; overflow-x: hidden; justify-content: flex-start; gap: 20px; padding-bottom: 12px; width: 100%; }
  .diagram-col, .diagram-col[style*="flex: 1.2"] { width: 100%; min-width: unset; flex: 0 0 auto; flex-direction: row; flex-wrap: wrap; justify-content: center; gap: 12px; }
  .diagram-col .col-label { width: 100%; text-align: center; margin-bottom: 2px; }
  .diagram-col .dropzone { flex: 1 1 42%; max-width: 48%; min-height: 60px; padding: 6px; font-size: 11px; border-radius: 8px; }
  .diagram-col[style*="flex: 1.2"] .dropzone { flex: 1 1 80%; max-width: 80%; min-height: 70px; }
  .banks-container { grid-template-columns: 1fr; gap: 12px; padding: 16px; }
  .app-container { overflow-y: visible; height: auto; min-height: 100%; display: block; }
  body { overflow: auto; height: auto; min-height: 100%; display: block; }
}
@media (max-width: 768px) {
  .banks-container { display: none !important; }
  .dropzone { cursor: pointer !important; border-style: solid !important; border-color: #93c5fd !important; background: #eff6ff !important; position: relative; }
  .dropzone::after { content: '\f078'; font-family: 'Font Awesome 6 Free'; font-weight: 900; font-size: 10px; color: #3b82f6; position: absolute; bottom: 4px; right: 6px; opacity: .6; }
  .dropzone.mobile-filled { border-color: #3b82f6 !important; background: white !important; }
  .dropzone.mobile-filled::after { content: '\f044'; opacity: .5; }
}
/* Mobile bottom-sheet picker */
.mobile-sheet { display: none; position: fixed; inset: 0; z-index: 9000; }
.mobile-sheet.active { display: block; }
.mobile-sheet-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,.45); }
.mobile-sheet-panel { position: absolute; bottom: 0; left: 0; right: 0; background: white; border-radius: 20px 20px 0 0; padding: 0 0 env(safe-area-inset-bottom,12px); max-height: 75vh; display: flex; flex-direction: column; box-shadow: 0 -8px 32px rgba(0,0,0,.15); animation: slideUp .22s ease; }
@keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
.mobile-sheet-handle { width: 40px; height: 4px; background: #cbd5e1; border-radius: 2px; margin: 12px auto 0; }
.mobile-sheet-header { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px 10px; border-bottom: 1px solid #e2e8f0; flex-shrink: 0; }
.mobile-sheet-title { font-size: 14px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: .5px; }
.mobile-sheet-close { background: #f1f5f9; border: none; width: 30px; height: 30px; border-radius: 50%; font-size: 16px; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.mobile-sheet-options { overflow-y: auto; flex: 1; padding: 12px 16px; display: flex; flex-direction: column; gap: 8px; }
.mobile-radio-label { display: flex; align-items: center; gap: 14px; padding: 14px 16px; border: 2px solid #e2e8f0; border-radius: 12px; cursor: pointer; font-size: 14px; font-weight: 500; color: #0f172a; transition: all .15s; user-select: none; }
.mobile-radio-label:has(input:checked) { border-color: #3b82f6; background: #eff6ff; color: #1d4ed8; font-weight: 700; }
.mobile-radio-label input[type="radio"] { width: 18px; height: 18px; accent-color: #3b82f6; flex-shrink: 0; }
.mobile-sheet-footer { padding: 12px 16px; border-top: 1px solid #e2e8f0; flex-shrink: 0; display: flex; gap: 10px; }
.mobile-sheet-confirm { flex: 1; padding: 14px; background: #0a1628; color: white; border: none; border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer; }
.mobile-sheet-clear { padding: 14px 18px; background: #f1f5f9; color: #64748b; border: none; border-radius: 12px; font-size: 14px; font-weight: 700; cursor: pointer; }

/* ── Mobile Clinical Reference — Senior-Friendly Design ── */
@media (max-width: 768px) {
  /* Hide panel from normal flow */
  .left-panel { display: none !important; }

  /* Full-width banner button above the question — impossible to miss */
  .mobile-clinref-btn {
    display: flex !important;
    width: 100%;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: #e8f4fd;
    color: #0369a1;
    border: 2px solid #bae6fd;
    border-radius: 12px;
    padding: 14px 16px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    margin-bottom: 16px;
    text-align: center;
    letter-spacing: 0.1px;
    -webkit-tap-highlight-color: transparent;
  }
  .mobile-clinref-btn i { font-size: 17px; }
  /* Submit button */
  .footer { padding: 10px 12px 14px !important; }
  .footer .btn { min-width: 0 !important; width: 100% !important; font-size: 13px !important; padding: 10px 16px !important; border-radius: 10px !important; }
  .question-header { font-size: 14px !important; line-height: 1.55 !important; margin-bottom: 14px !important; }

  /* Full-screen overlay */
  .left-panel.mobile-open {
    display: flex !important;
    position: fixed !important;
    inset: 0 !important;
    z-index: 9998 !important;
    width: 100% !important;
    min-width: unset !important;
    max-width: 100% !important;
    height: 100dvh !important;
    max-height: 100% !important;
    border-right: none !important;
    border-bottom: none !important;
    flex-direction: column !important;
    overflow: hidden !important;
    border-radius: 0 !important;
    background: #fff !important;
  }

  /* Header inside modal */
  .left-panel.mobile-open .panel-title {
    padding: 16px 18px !important;
    font-size: 13px !important;
    display: flex !important;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
    background: #f1f5f9;
    border-bottom: 1px solid #e2e8f0;
  }

  /* Close button — large, with text */
  .mobile-clinref-close {
    display: flex !important;
    align-items: center;
    gap: 6px;
    background: #ef4444;
    color: #fff !important;
    border: none;
    border-radius: 10px;
    padding: 8px 16px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    min-width: 80px;
    justify-content: center;
  }

  /* Tabs — large tap targets */
  .left-panel.mobile-open .tabs-row {
    flex-shrink: 0;
    padding: 6px 8px 0 !important;
    gap: 3px !important;
    overflow-x: hidden;
    display: flex;
    flex-wrap: nowrap;
  }
  .left-panel.mobile-open .tab-btn {
    font-size: 11px !important;
    padding: 8px 10px !important;
    font-weight: 600;
    white-space: nowrap;
    flex: 1 1 0;
    min-width: 0;
    text-align: center;
    border-radius: 8px 8px 0 0;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  /* Content area — scrollable, comfortable font */
  .left-panel.mobile-open .tab-content-area {
    flex: 1;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    padding: 16px !important;
  }
  .left-panel.mobile-open .clinical-record {
    font-size: 14.5px !important;
    line-height: 1.7 !important;
    padding: 12px 14px !important;
    margin-bottom: 10px !important;
    border-radius: 8px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
  }
}

  /* Submit pill FAB */
  .submit-fab {
    position: fixed;
    bottom: 80px;
    right: 14px;
    z-index: 1100;
    display: none;
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 24px;
    padding: 11px 20px;
    font-size: 14px;
    font-weight: 700;
    box-shadow: 0 4px 18px rgba(37,99,235,0.35);
    cursor: pointer;
    align-items: center;
    gap: 8px;
    transition: opacity 0.2s, transform 0.2s;
  }
  .submit-fab.visible { display: flex; }
  .submit-fab.pulse { animation: submitPulse 0.4s ease; }
  @keyframes submitPulse {
    0%   { transform: scale(1); }
    50%  { transform: scale(1.12); }
    100% { transform: scale(1); }
  }
}
@media (min-width: 769px) {
  .mobile-clinref-btn { display: none !important; }
}

/* ── Color-coded option types: Actions (#F7DD7D), Condition (#6EADBC), Parameters (#F1F7D4) ──
   The option tokens themselves are coloured, and each dropzone shares the colour
   of the bank whose options belong in it. */

/* Bank headers (text only) */
.banks-container .bank-col:nth-child(1) .bank-header { color: #8A6D1B; }
.banks-container .bank-col:nth-child(2) .bank-header { color: #3F8294; }
.banks-container .bank-col:nth-child(3) .bank-header { color: #7E8C2F; }

/* Diagram column labels (above the bowtie dropzones) */
.diagram-col:has([data-type="action"]) .col-label { color: #8A6D1B; }
.diagram-col:has([data-type="condition"]) .col-label { color: #3F8294; }
.diagram-col:has([data-type="parameter"]) .col-label { color: #7E8C2F; }

/* Choice tokens — the option itself is coloured by type */
.choice-token[data-type="action"] {
  background: #fae7a0; border: 2px solid #e3c354; color: #6b551a;
}
.choice-token[data-type="condition"] {
  background: #e2f1f4; border: 2px solid #6EADBC; color: #2c5c68;
}
.choice-token[data-type="parameter"] {
  background: #f1f7d4; border: 2px solid #c5d27a; color: #5c6726;
}

/* Dropzones — tinted to match their bank (only before the answer is revealed) */
.dropzone[data-type="action"]:not(.correct-reveal):not(.wrong-reveal) {
  background: #fdf6e0; border-color: #f0e1a0; color: #8A6D1B;
}
.dropzone[data-type="condition"]:not(.correct-reveal):not(.wrong-reveal) {
  background: #eff8fa; border-color: #a9d4dd; color: #3F8294;
}
.dropzone[data-type="parameter"]:not(.correct-reveal):not(.wrong-reveal) {
  background: #f8fbe9; border-color: #dce6a6; color: #7E8C2F;
}
.dropzone.center-slot[data-type="condition"]:not(.correct-reveal):not(.wrong-reveal) {
  border-color: #6EADBC; background: #eff8fa; color: #2c5c68;
}

/* Hover keeps the type colour, a shade stronger */
.dropzone[data-type="action"]:not(.correct-reveal):not(.wrong-reveal):hover {
  background: #f3e7a8; border-color: #e3c354;
}
.dropzone[data-type="condition"]:not(.correct-reveal):not(.wrong-reveal):hover {
  background: #d2ebf0; border-color: #6EADBC;
}
.dropzone[data-type="parameter"]:not(.correct-reveal):not(.wrong-reveal):hover {
  background: #eaf2c2; border-color: #c5d27a;
}

/* Mobile: keep the type tint on empty dropzones (override the blanket blue) */
@media (max-width: 768px) {
  .dropzone[data-type="condition"]:not(.correct-reveal):not(.wrong-reveal):not(.mobile-filled) {
    border-color: #a9d4dd !important; background: #eff8fa !important;
  }
  .dropzone[data-type="parameter"]:not(.correct-reveal):not(.wrong-reveal):not(.mobile-filled) {
    border-color: #dce6a6 !important; background: #f8fbe9 !important;
  }
}
</style>
</head>
<body>
<div class="app-container">
  <div class="main-container">
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

    <div class="right-panel" <?= !$hasTabs ? 'style="width:100%;"' : '' ?>>
      <div class="question-header"><?= nl2br(htmlspecialchars($data['question'])) ?></div>

      <div class="diagram-wrapper">
        <svg class="bowtie-svg" viewBox="0 0 100 100" preserveAspectRatio="none">
          <path class="bowtie-line" d="M 18 35 L 50 55 L 18 75 M 82 35 L 50 55 L 82 75"/>
          <circle class="bowtie-knot" cx="50" cy="55" r="2"/>
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
            <?php foreach ($actions as $a) echo "<div class='choice-token' draggable='true' data-type='action'>" . htmlspecialchars($a['text']) . "</div>"; ?>
          </div>
        </div>
        <div class="bank-col">
          <div class="bank-header">Conditions Bank</div>
          <div class="bank-list" data-type="condition">
            <?php foreach ($conditions as $c) echo "<div class='choice-token' draggable='true' data-type='condition'>" . htmlspecialchars($c['text']) . "</div>"; ?>
          </div>
        </div>
        <div class="bank-col">
          <div class="bank-header">Parameters Bank</div>
          <div class="bank-list" data-type="parameter">
            <?php foreach ($parameters as $p) echo "<div class='choice-token' draggable='true' data-type='parameter'>" . htmlspecialchars($p['text']) . "</div>"; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="footer">
    <button id="submitBtn" class="btn btn-primary"><i class="fas fa-check-circle"></i> Submit Bow-Tie</button>
  </div>
</div>

<!-- Mobile sheet -->
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
  let locked = false;
  let initialAnswers = {};
  let hasInteracted = false;

  // Timeout (Pressure Mode)
  window.addEventListener('message', e => {
    if (e.data?.type === 'timeout') {
      locked = true;
      $('#submitBtn').hide();
      $('.choice-token').attr('draggable', false);
      const ov = document.createElement('div');
      ov.style.cssText = 'position:fixed;inset:0;background:rgba(239,68,68,.08);display:flex;align-items:flex-start;justify-content:center;padding-top:20px;z-index:9999;pointer-events:none;';
      ov.innerHTML = '<div style="background:#ef4444;color:#fff;padding:8px 22px;border-radius:100px;font-weight:800;font-size:13px;">⏰ Time Expired</div>';
      document.body.appendChild(ov);
    }
  });

  if (window.parent !== window) window.parent.postMessage({ type: 'ready' }, '*');

  // Tabs
  $('.tab-btn').click(function(){
    $('.tab-btn').removeClass('active');
    $(this).addClass('active');
    $('.tab-pane').hide();
    $('#' + $(this).data('tab')).show();
  });

  // Drag
  let dragged = null;
  $(document).on('dragstart', '.choice-token', function(e){
    dragged = this;
    e.originalEvent.dataTransfer.setData('text/plain', '');
  });
  $('.dropzone').on('dragover', function(e){ e.preventDefault(); });
  $('.dropzone').on('drop', function(e){
    e.preventDefault();
    if (locked || !dragged) return;
    if (dragged.dataset.type !== this.dataset.type) {
      Swal.fire({ icon:'error', title:'Wrong Section', text:'This item belongs in the ' + dragged.dataset.type + ' section.' });
      return;
    }
    hasInteracted = true;
    let existing = $(this).find('.choice-token');
    if (existing.length > 0) $(`.bank-list[data-type="${dragged.dataset.type}"]`).append(existing);
    $(this).find('span').hide();
    $(this).append(dragged);
  });
  $('.bank-list').on('dragover', function(e){ e.preventDefault(); });
  $('.bank-list').on('drop', function(e){
    e.preventDefault();
    if (dragged && dragged.dataset.type === this.dataset.type) {
      $(this).append(dragged);
      $('.dropzone').each(function(){
        if ($(this).find('.choice-token').length === 0) $(this).find('span').show();
      });
    }
  });

  // Auto-scroll while dragging
  let isDragging = false;
  $(document).on('dragstart', '.choice-token', function(){ isDragging = true; });
  $(document).on('dragend',   '.choice-token', function(){ isDragging = false; });
  $(document).on('dragover', function(e){
    if (!isDragging) return;
    const mouseY = e.clientY;
    if (mouseY < 100) window.scrollBy(0, -10);
    else if (mouseY > window.innerHeight - 100) window.scrollBy(0, 10);
  });

  // Submit
  $('#submitBtn').on('click', function(){
    if (locked) return;
    let incomplete = false;
    $('.dropzone').each(function(){ if ($(this).find('.choice-token').length === 0) incomplete = true; });
    if (incomplete) {
      Swal.fire({ icon:'warning', title:'Incomplete', text:'Please fill all slots in the diagram.' });
      return;
    }
    locked = true;
    $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting…');
    $('.choice-token').attr('draggable', false);
    setTimeout(() => $(this).hide(), 400);

    let userA = [], userC = [], userP = [];
    $('.dropzone[data-type="action"]').each(function(){ userA.push($(this).find('.choice-token').text().trim()); });
    $('.dropzone[data-type="condition"]').each(function(){ userC.push($(this).find('.choice-token').text().trim()); });
    $('.dropzone[data-type="parameter"]').each(function(){ userP.push($(this).find('.choice-token').text().trim()); });

    let earned = 0;
    const total = 5;
    $('.dropzone').each(function(){
      let txt  = $(this).find('.choice-token').text().trim().toLowerCase();
      let type = this.dataset.type;
      let list = (type === 'action') ? correctA : (type === 'condition' ? correctC : correctP);
      if (list.map(s => s.toString().trim().toLowerCase()).includes(txt)) earned++;
    });

    const normalized = parseFloat((earned / total).toFixed(2));
    const selected   = { actions: userA, conditions: userC, parameters: userP };

    let changesData = null;
    if (hasInteracted && JSON.stringify(initialAnswers) !== JSON.stringify(selected))
      changesData = { modified_count: 1, changed: true };

    if (window.parent !== window) {
      window.parent.postMessage({
        type: 'answered',
        answer:         selected,
        initial_answer: Object.keys(initialAnswers).length > 0 ? initialAnswers : null,
        correctAnswer:  { actions: correctA, conditions: correctC, parameters: correctP },
        correct:        earned === total,
        isCorrect:      earned === total,
        score:          normalized,
        max_points:     total,
        earned_points:  earned,
        changes:        changesData,
        rationale,
        topic:   <?= json_encode($topic) ?>,
        system:  <?= json_encode($system) ?>,
        cnc:     <?= json_encode($cnc) ?>,
        dlevel:  <?= json_encode($dlevel) ?>,
        question_id:   <?= json_encode($data['id']) ?>,
        question_type: 'bowtie'
      }, '*');
    }
  });

  // Capture initial state
  setTimeout(() => {
    if (Object.keys(initialAnswers).length === 0) {
      let a = {}, c = {}, p = {};
      let idx = 0;
      $('.dropzone[data-type="action"]').each(function(){ a[idx++] = $(this).find('.choice-token').text().trim(); });
      idx = 0;
      $('.dropzone[data-type="condition"]').each(function(){ c[idx++] = $(this).find('.choice-token').text().trim(); });
      idx = 0;
      $('.dropzone[data-type="parameter"]').each(function(){ p[idx++] = $(this).find('.choice-token').text().trim(); });
      initialAnswers = { actions: a, conditions: c, parameters: p };
    }
  }, 50);

  // ===== MOBILE TAP-TO-SELECT =====
  const isMobile = () => window.innerWidth <= 768;
  let mobileTargetZone = null;

  function getAllOptionsForType(type) {
    const opts = [];
    $(`.bank-list[data-type="${type}"] .choice-token`).each(function(){ const t = $(this).text().trim(); if (t) opts.push(t); });
    $(`.dropzone[data-type="${type}"] .choice-token`).each(function(){ const t = $(this).text().trim(); if (t && !opts.includes(t)) opts.push(t); });
    return opts;
  }

  function escapeHtml(s) { return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

  function openMobileSheet(zone) {
    if (!isMobile() || locked) return;
    mobileTargetZone = zone;
    const type = zone.dataset.type;
    const labels = { action: 'Actions to Take', condition: 'Condition', parameter: 'Monitor Parameters' };
    document.getElementById('mobileSheetTitle').textContent = labels[type] || type;
    const opts = getAllOptionsForType(type);
    const currentVal = $(zone).find('.choice-token').text().trim();
    let html = '';
    opts.forEach((txt, i) => {
      const checked = txt === currentVal ? 'checked' : '';
      const safeId  = 'mopt_' + i;
      html += `<label class="mobile-radio-label" for="${safeId}"><input type="radio" id="${safeId}" name="mobileOpt" value="${escapeHtml(txt)}" ${checked}><span>${escapeHtml(txt)}</span></label>`;
    });
    document.getElementById('mobileSheetOptions').innerHTML = html;
    document.getElementById('mobileSelectSheet').classList.add('active');
  }

  function closeMobileSheet() { document.getElementById('mobileSelectSheet').classList.remove('active'); mobileTargetZone = null; }

  function placeTokenInZone(zone, tokenText) {
    const type = zone.dataset.type;
    const existing = $(zone).find('.choice-token');
    if (existing.length) { $(`.bank-list[data-type="${type}"]`).append(existing); $(zone).find('span').show(); }
    let token = $(`.bank-list[data-type="${type}"] .choice-token`).filter(function(){ return $(this).text().trim() === tokenText; }).first();
    if (!token.length) {
      token = $(`.dropzone[data-type="${type}"] .choice-token`).filter(function(){ return $(this).text().trim() === tokenText; }).first();
      if (token.length) { const oz = token.closest('.dropzone'); token.detach(); oz.find('span').show(); oz.removeClass('mobile-filled'); }
    } else { token.detach(); }
    if (token.length) { $(zone).find('span').hide(); $(zone).empty().append(token); $(zone).addClass('mobile-filled'); hasInteracted = true; }
  }

  $(document).on('click', '.dropzone', function(e){
    if (!isMobile() || locked) return;
    e.stopPropagation();
    openMobileSheet(this);
  });

  document.getElementById('mobileSheetConfirmBtn').addEventListener('click', function(){
    const selected = document.querySelector('input[name="mobileOpt"]:checked');
    if (selected && mobileTargetZone) placeTokenInZone(mobileTargetZone, selected.value);
    closeMobileSheet();
  });
  document.getElementById('mobileSheetClearBtn').addEventListener('click', function(){
    if (!mobileTargetZone) { closeMobileSheet(); return; }
    const type = mobileTargetZone.dataset.type;
    const existing = $(mobileTargetZone).find('.choice-token');
    if (existing.length) { $(`.bank-list[data-type="${type}"]`).append(existing); $(mobileTargetZone).find('span').show(); $(mobileTargetZone).removeClass('mobile-filled'); }
    closeMobileSheet();
  });
  document.getElementById('mobileSheetBackdrop').addEventListener('click', closeMobileSheet);
  document.getElementById('mobileSheetCloseBtn').addEventListener('click', closeMobileSheet);
});
</script>

<script>
(function () {
  if (window.innerWidth > 768) return;
  var lp = document.querySelector(".left-panel");
  if (!lp) return;

  /* Close button inside modal header — text label, not just × */
  var pt = lp.querySelector(".panel-title");
  if (pt) {
    var cb = document.createElement("button");
    cb.className = "mobile-clinref-close";
    cb.innerHTML = "&#10005; Close";
    cb.onclick = function () { lp.classList.remove("mobile-open"); };
    pt.appendChild(cb);
  }

  /* Banner button injected at the TOP of right-panel content (before question) */
  var rp = document.querySelector(".right-panel");
  var btn = document.createElement("button");
  btn.className = "mobile-clinref-btn";
  btn.innerHTML = "<i class='fas fa-folder-open'></i> View Patient Chart &amp; Lab Results";
  btn.onclick = function () { lp.classList.add("mobile-open"); };

  if (rp) {
    rp.insertBefore(btn, rp.firstChild);
  } else {
    document.body.insertBefore(btn, document.body.firstChild);
  }

  /* Submit pill FAB for mobile */
  var sb = document.getElementById("submitBtn");
  if (sb) {
    var fab = document.createElement("button");
    fab.className = "submit-fab";
    fab.innerHTML = "<i class='fas fa-check-circle'></i> Submit";
    document.body.appendChild(fab);
    fab.addEventListener("click", function () {
      sb.scrollIntoView({ behavior: "smooth", block: "center" });
      fab.classList.add("pulse");
      setTimeout(function () { fab.classList.remove("pulse"); }, 500);
    });
    if ("IntersectionObserver" in window) {
      var obs = new IntersectionObserver(function (entries) {
        fab.classList.toggle("visible", !entries[0].isIntersecting);
      }, { threshold: 0.5 });
      obs.observe(sb);
    } else {
      fab.classList.add("visible");
    }
    /* Hide original empty footer if present */
    var ft = document.querySelector(".footer");
    if (ft && ft.children.length === 0) ft.style.display = "none";
  }
})();
</script>
</body>
</html>
