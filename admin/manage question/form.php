<?php
include('../../config.php');
date_default_timezone_set('Asia/Manila');
if (session_status() === PHP_SESSION_NONE) session_start();

/* ── Type routing ─────────────────────────────────── */
$tableMap = [
    'traditional' => 'mcq',
    'sata'        => 'sata',
    'mpr'         => 'mpr',
    'mmr'         => 'mmr',
    'btq'         => 'btq',
    'dragndrop'   => 'dragndrop',
    'dropdown'    => 'dropdown_questions',
    'highlight'   => 'highlight',
];
$typeLabels = [
    'traditional' => 'Multiple Choice (MCQ)',
    'sata'        => 'SATA — Select All That Apply',
    'mpr'         => 'Matrix / Priority Response',
    'mmr'         => 'Matrix Multiple Response',
    'btq'         => 'Bow-Tie',
    'dragndrop'   => 'Drag & Drop',
    'dropdown'    => 'Dropdown Cloze',
    'highlight'   => 'Highlight',
];

$type = $_GET['type'] ?? '';
$id   = (int)($_GET['id'] ?? 0);
if (!array_key_exists($type, $tableMap)) { header('Location: index.php'); exit; }
$table     = $tableMap[$type];
$typeLabel = $typeLabels[$type];

/* ── Fetch existing question for edit ─────────────── */
$q = [];
if ($id) {
    $res = mysqli_query($con, "SELECT * FROM `$table` WHERE id = $id LIMIT 1");
    if ($res) $q = mysqli_fetch_assoc($res) ?: [];
}
$isEdit = $id > 0 && !empty($q);

// Decode JSON fields for pre-filling
$jsonFields = ['choices','items','correct','columns','rows','actionToTake','potentialConditions','parameterToMonitor'];
foreach ($jsonFields as $f) {
    if (!empty($q[$f]) && is_string($q[$f])) {
        $dec = json_decode($q[$f], true);
        if (json_last_error() === JSON_ERROR_NONE) $q[$f] = $dec;
    }
}

/* ── Reference data ──────────────────────────────── */
$topicsRes = mysqli_query($con, "SELECT COALESCE(name,title) as label FROM topics ORDER BY label");
$topicList = [];
while ($t = mysqli_fetch_assoc($topicsRes)) $topicList[] = $t['label'];

$concepts = ['Adult Health','Child Health','Critical Care','Fundamentals',
             'Leadership & Management','Maternal & Newborn Health','Mental Health','Pharmacology'];
$dlevels  = ['Easy','Medium','Hard'];

/* ── Helpers ─────────────────────────────────────── */
function val(array $q, string $k, $default = '') {
    return htmlspecialchars($q[$k] ?? $default);
}
function sel(string $v, string $opt): string {
    return $v === $opt ? ' selected' : '';
}
function selOpts(array $arr, string $cur): string {
    $h = '<option value="">— select —</option>';
    foreach ($arr as $a) {
        $esc = htmlspecialchars($a);
        $h .= '<option value="'.$esc.'"'.sel($cur,$a).'>'.$esc.'</option>';
    }
    return $h;
}

/* ── Page setup ──────────────────────────────────── */
$ap_base    = '../';
$active_nav = 'questions';
$ap_title   = ($isEdit ? 'Edit' : 'Add') . ' — ' . $typeLabel;
$ap_extra_head = '
<style>
  .qp-grid{display:grid;grid-template-columns:1.5fr 1fr;gap:20px;align-items:start;padding:0 20px 30px;}
  .qp-sticky{position:sticky;top:14px;}
  .qp-card{background:var(--ap-card);border-radius:var(--ap-radius-lg);border:1px solid var(--ap-border);overflow:hidden;margin-bottom:16px;}
  .qp-card-hd{padding:14px 18px;border-bottom:1px solid var(--ap-border);font-size:0.78rem;font-weight:700;color:var(--ap-text-2);letter-spacing:.03em;display:flex;align-items:center;gap:8px;}
  .qp-card-bd{padding:18px;}
  .qp-sdiv{font-size:0.67rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--ap-text-3);margin:14px 0 9px;display:flex;align-items:center;gap:7px;}
  .qp-sdiv::after{content:"";flex:1;height:1px;background:var(--ap-border);}
  .qp-g2{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
  .qp-g3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;}
  .fg{margin-bottom:12px;}
  .fg:last-child{margin-bottom:0;}
  .fg label{display:block;font-size:0.73rem;font-weight:600;color:var(--ap-text-2);margin-bottom:5px;}
  .fg input,.fg select,.fg textarea{width:100%;box-sizing:border-box;}
  .dyn-list{display:flex;flex-direction:column;gap:6px;}
  .dyn-row{display:flex;align-items:center;gap:7px;}
  .dyn-row input[type=text]{flex:1;min-width:0;}
  .dyn-add{display:inline-flex;align-items:center;gap:5px;margin-top:8px;font-size:0.77rem;font-weight:600;color:var(--ap-primary);background:none;border:none;cursor:pointer;padding:0;}
  .dyn-add:hover{text-decoration:underline;}
  .dyn-rm{width:26px;height:26px;flex-shrink:0;border:none;border-radius:6px;background:#fee2e2;color:#dc2626;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:0.82rem;}
  .matrix-wrap{overflow-x:auto;}
  .mx-tbl{border-collapse:collapse;font-size:0.76rem;width:100%;}
  .mx-tbl th,.mx-tbl td{border:1px solid var(--ap-border);padding:5px 10px;}
  .mx-tbl th{background:var(--ap-bg-2);font-weight:600;text-align:center;}
  .mx-tbl td{text-align:center;}
  .mx-tbl td:first-child{text-align:left;font-weight:600;white-space:nowrap;}
  .btq-section{display:flex;flex-direction:column;gap:5px;}
  .btq-row{display:flex;align-items:center;gap:8px;}
  .btq-row input[type=text]{flex:1;min-width:0;}
  @media(max-width:900px){.qp-grid{grid-template-columns:1fr;}.qp-sticky{position:static;}}
</style>';
$ap_topbar_actions = '
<a href="list.php?type='.urlencode($type).'" class="ap-btn ap-btn-ghost ap-btn-sm">
  <i class="bi bi-arrow-left"></i> Back to list
</a>
<button form="qForm" type="submit" class="ap-btn ap-btn-primary ap-btn-sm">
  <i class="bi bi-check-lg"></i> '.($isEdit ? 'Update' : 'Save').' Question
</button>';
require_once '../partials/sidebar.php';

/* shortcuts */
$qt = $q; // the question data array
?>

<form id="qForm" action="qaction.php" method="post" enctype="multipart/form-data">
<input type="hidden" name="action" value="<?= $isEdit ? 'edit' : 'add' ?>">
<input type="hidden" name="type"   value="<?= $type ?>">
<?php if ($isEdit): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

<!-- JSON hidden fields (for types that need them) -->
<?php if ($type==='sata'||$type==='dragndrop'): ?>
<input type="hidden" name="items_json"   id="items_json">
<input type="hidden" name="correct_json" id="correct_json">
<?php elseif ($type==='mmr'): ?>
<input type="hidden" name="columns_json" id="columns_json">
<input type="hidden" name="rows_json"    id="rows_json">
<input type="hidden" name="correct_json" id="correct_json">
<?php elseif ($type==='btq'): ?>
<input type="hidden" name="actionToTake_json"        id="actionToTake_json">
<input type="hidden" name="potentialConditions_json" id="potentialConditions_json">
<input type="hidden" name="parameterToMonitor_json"  id="parameterToMonitor_json">
<?php endif; ?>

<div class="qp-grid">

  <!-- ════ LEFT: Question + type-specific ════ -->
  <div>

    <!-- Question stem -->
    <div class="qp-card">
      <div class="qp-card-hd">
        <i class="bi bi-question-circle" style="color:var(--ap-primary)"></i>
        Question Stem
      </div>
      <div class="qp-card-bd">
        <div class="fg">
          <label>
            Question <span style="color:#dc2626">*</span>
            <?php if (in_array($type,['dragndrop','dropdown'])): ?>
            <span style="font-weight:400;color:var(--ap-text-3)"> — use ___ for each blank</span>
            <?php endif; ?>
          </label>
          <textarea class="ap-form-input" name="question" rows="4" required
                    style="resize:vertical;"><?= val($qt,'question') ?></textarea>
        </div>

        <?php if ($type==='highlight'): ?>
        <div class="fg">
          <label>Passage <span style="color:#dc2626">*</span> <span style="font-weight:400;color:var(--ap-text-3)">— text students highlight in</span></label>
          <textarea class="ap-form-input" name="passage" id="f_passage" rows="6" required
                    style="resize:vertical;"><?= val($qt,'passage') ?></textarea>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Type-specific card -->
    <div class="qp-card">
      <div class="qp-card-hd">
        <?php
        $icons = ['traditional'=>'bi-list-check','sata'=>'bi-check2-square','mpr'=>'bi-grid-3x3',
                  'mmr'=>'bi-table','btq'=>'bi-arrow-left-right','dragndrop'=>'bi-arrows-move',
                  'dropdown'=>'bi-menu-button-wide','highlight'=>'bi-highlighter'];
        ?>
        <i class="<?= $icons[$type] ?>" style="color:var(--ap-primary)"></i>
        <?= htmlspecialchars($typeLabel) ?> — Answer Configuration
      </div>
      <div class="qp-card-bd">

        <?php /* ══ MCQ ══ */ if ($type==='traditional'):
          $choices = $qt['choices'] ?? ['','','',''];
          if (is_string($choices)) $choices = json_decode($choices,true) ?: ['','','',''];
          $correct = $qt['correct'] ?? '';
        ?>
        <div class="qp-g2" style="margin-bottom:10px;">
          <?php foreach (['A','B','C','D'] as $i=>$ltr): ?>
          <div class="fg" style="margin:0;">
            <label>Choice <?= $ltr ?></label>
            <input type="text" class="ap-form-input" name="choice<?= $ltr ?>"
                   value="<?= htmlspecialchars($choices[$i]??'') ?>" required>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="fg" style="margin:0;">
          <label>Correct Answer</label>
          <select class="ap-form-input" name="correct" required>
            <option value="">— select —</option>
            <?php foreach (['A','B','C','D'] as $ltr): ?>
            <option value="<?=$ltr?>"<?= $correct===$ltr?' selected':'' ?>><?=$ltr?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php /* ══ SATA ══ */ elseif ($type==='sata'):
          $items   = is_array($qt['items']  ??null) ? $qt['items']   : [];
          $correct = is_array($qt['correct']??null) ? $qt['correct'] : [];
          if (empty($items)) $items = ['','','',''];
        ?>
        <p style="font-size:0.78rem;color:var(--ap-text-3);margin:0 0 10px;">
          Check the items that are correct answers.
        </p>
        <div class="dyn-list" id="sataList">
          <?php foreach ($items as $i=>$item): ?>
          <div class="dyn-row">
            <input type="checkbox" name="sata_correct[]" value="<?=$i?>"
                   <?= in_array($i,$correct,false)?'checked':'' ?> style="flex-shrink:0;">
            <input type="text" class="ap-form-input" name="sata_item[]"
                   value="<?= htmlspecialchars($item) ?>" placeholder="Item <?=$i+1?>">
            <button type="button" class="dyn-rm" onclick="removeRow(this)"><i class="bi bi-x"></i></button>
          </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="dyn-add" onclick="addSataItem()"><i class="bi bi-plus-circle"></i> Add Item</button>

        <?php /* ══ MPR ══ */ elseif ($type==='mpr'): ?>
        <div class="fg">
          <label>Items <span style="font-weight:400;color:var(--ap-text-3)">(one per line)</span></label>
          <textarea class="ap-form-input" name="items" id="f_items" rows="7" required
                    style="resize:vertical;"><?= val($qt,'items') ?></textarea>
        </div>
        <div class="fg" style="margin:0;">
          <label>Correct Indices <span style="font-weight:400;color:var(--ap-text-3)">(comma-separated, 0-based)</span></label>
          <input type="text" class="ap-form-input" name="correct" id="f_correct"
                 value="<?= val($qt,'correct') ?>" placeholder="e.g. 0,2" required>
        </div>

        <?php /* ══ MMR ══ */ elseif ($type==='mmr'):
          $mmrCols = is_array($qt['columns']??null) ? $qt['columns'] : [];
          $mmrRows = is_array($qt['rows']   ??null) ? $qt['rows']    : [];
          $mmrCor  = is_array($qt['correct']??null) ? $qt['correct'] : [];
        ?>
        <div class="qp-g2" style="margin-bottom:14px;align-items:start;">
          <div>
            <label style="font-size:0.73rem;font-weight:600;color:var(--ap-text-2);display:block;margin-bottom:6px;">Columns (headers)</label>
            <div class="dyn-list" id="mmrColList">
              <?php foreach ($mmrCols as $c): ?>
              <div class="dyn-row">
                <input type="text" class="ap-form-input" value="<?= htmlspecialchars($c) ?>" placeholder="Column…" oninput="buildMatrix()">
                <button type="button" class="dyn-rm" onclick="removeRow(this);buildMatrix()"><i class="bi bi-x"></i></button>
              </div>
              <?php endforeach; ?>
            </div>
            <button type="button" class="dyn-add" onclick="addMmrCol()"><i class="bi bi-plus-circle"></i> Add Column</button>
          </div>
          <div>
            <label style="font-size:0.73rem;font-weight:600;color:var(--ap-text-2);display:block;margin-bottom:6px;">Rows (labels)</label>
            <div class="dyn-list" id="mmrRowList">
              <?php foreach ($mmrRows as $r): ?>
              <div class="dyn-row">
                <input type="text" class="ap-form-input" value="<?= htmlspecialchars($r) ?>" placeholder="Row…" oninput="buildMatrix()">
                <button type="button" class="dyn-rm" onclick="removeRow(this);buildMatrix()"><i class="bi bi-x"></i></button>
              </div>
              <?php endforeach; ?>
            </div>
            <button type="button" class="dyn-add" onclick="addMmrRow()"><i class="bi bi-plus-circle"></i> Add Row</button>
          </div>
        </div>
        <div class="fg" style="margin:0;">
          <label style="font-size:0.73rem;font-weight:600;color:var(--ap-text-2);display:block;margin-bottom:6px;">
            Correct Cells <span style="font-weight:400;color:var(--ap-text-3)">(check the correct ones)</span>
          </label>
          <div class="matrix-wrap" id="mmrMatrix">
            <em style="font-size:0.77rem;color:var(--ap-text-3);">Add columns and rows above.</em>
          </div>
        </div>
        <!-- Pass existing correct data to JS -->
        <script id="mmrInitData" type="application/json"><?= json_encode($mmrCor) ?></script>
        <div class="qp-sdiv">Clinical Context <span style="font-weight:400;text-transform:none;">(optional)</span></div>
        <div class="fg">
          <label>Nurses Notes</label>
          <textarea class="ap-form-input" name="nurses_notes" rows="2" style="resize:vertical;"><?= val($qt,'nurses_notes') ?></textarea>
        </div>
        <div class="qp-g2">
          <div class="fg" style="margin:0;">
            <label>Vital Signs</label>
            <textarea class="ap-form-input" name="vital_signs" rows="2" style="resize:vertical;"><?= val($qt,'vital_signs') ?></textarea>
          </div>
          <div class="fg" style="margin:0;">
            <label>Diagnostics</label>
            <textarea class="ap-form-input" name="diagnostics" rows="2" style="resize:vertical;"><?= val($qt,'diagnostics') ?></textarea>
          </div>
        </div>

        <?php /* ══ BTQ ══ */ elseif ($type==='btq'):
          $btqSections = [
            ['key'=>'actionToTake',       'label'=>'Action to Take',       'id'=>'btq_at'],
            ['key'=>'potentialConditions','label'=>'Potential Conditions',  'id'=>'btq_pc'],
            ['key'=>'parameterToMonitor', 'label'=>'Parameter to Monitor', 'id'=>'btq_pm'],
          ];
          foreach ($btqSections as &$s) {
              $raw = $qt[$s['key']] ?? [];
              if (is_string($raw)) $raw = json_decode($raw,true) ?: [];
              while (count($raw) < 5) $raw[] = ['text'=>'','correct'=>false];
              $s['data'] = $raw;
          }
          unset($s);
        ?>
        <?php foreach ($btqSections as $s): ?>
        <div class="qp-sdiv"><?= htmlspecialchars($s['label']) ?> <span style="font-weight:400;text-transform:none;font-size:0.75rem;">(● = correct)</span></div>
        <div class="btq-section" id="<?= $s['id'] ?>">
          <?php foreach ($s['data'] as $i=>$item): ?>
          <div class="btq-row">
            <input type="radio" name="<?= $s['key'] ?>_correct" value="<?=$i?>"
                   <?= !empty($item['correct'])?'checked':'' ?> style="flex-shrink:0;margin-top:1px;">
            <input type="text" class="ap-form-input" name="<?= $s['key'] ?>[]"
                   value="<?= htmlspecialchars($item['text']??'') ?>" placeholder="Option <?=$i+1?>">
          </div>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        <div class="qp-sdiv">Clinical Context <span style="font-weight:400;text-transform:none;">(optional)</span></div>
        <div class="fg">
          <label>Nurses Notes</label>
          <textarea class="ap-form-input" name="nursesNotes" rows="2" style="resize:vertical;"><?= val($qt,'nursesNotes') ?></textarea>
        </div>
        <div class="qp-g2">
          <div class="fg" style="margin:0;">
            <label>Vital Signs</label>
            <textarea class="ap-form-input" name="vitalSigns" rows="2" style="resize:vertical;"><?= val($qt,'vitalSigns') ?></textarea>
          </div>
          <div class="fg" style="margin:0;">
            <label>Diagnostics</label>
            <textarea class="ap-form-input" name="diagnostics" rows="2" style="resize:vertical;"><?= val($qt,'diagnostics') ?></textarea>
          </div>
        </div>

        <?php /* ══ Drag & Drop ══ */ elseif ($type==='dragndrop'):
          $dragItems   = is_array($qt['items']  ??null) ? $qt['items']   : [];
          $dragCorrect = is_array($qt['correct']??null) ? $qt['correct'] : [];
        ?>
        <div class="qp-sdiv">Draggable Items <span style="font-weight:400;text-transform:none;">(all choices incl. distractors)</span></div>
        <div class="dyn-list" id="dragItemList">
          <?php foreach ($dragItems as $item): ?>
          <div class="dyn-row">
            <input type="text" class="ap-form-input" value="<?= htmlspecialchars($item) ?>" placeholder="Item…">
            <button type="button" class="dyn-rm" onclick="removeRow(this)"><i class="bi bi-x"></i></button>
          </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="dyn-add" onclick="addDragItem('dragItemList')"><i class="bi bi-plus-circle"></i> Add Item</button>
        <div class="qp-sdiv">Correct Answers <span style="font-weight:400;text-transform:none;">(one per blank, in order)</span></div>
        <div class="dyn-list" id="dragCorrectList">
          <?php foreach ($dragCorrect as $c): ?>
          <div class="dyn-row">
            <input type="text" class="ap-form-input" value="<?= htmlspecialchars($c) ?>" placeholder="Answer…">
            <button type="button" class="dyn-rm" onclick="removeRow(this)"><i class="bi bi-x"></i></button>
          </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="dyn-add" onclick="addDragItem('dragCorrectList')"><i class="bi bi-plus-circle"></i> Add Answer</button>

        <?php /* ══ Dropdown Cloze ══ */ elseif ($type==='dropdown'): ?>
        <div class="fg">
          <label>Options per blank <span style="font-weight:400;color:var(--ap-text-3)">— separate groups with <code>|</code>, options within group with comma</span></label>
          <textarea class="ap-form-input" name="options" rows="4" style="resize:vertical;"
                    placeholder="opt1,opt2,opt3 | opt4,opt5,opt6"><?= val($qt,'options') ?></textarea>
        </div>
        <div class="fg" style="margin:0;">
          <label>Correct Words <span style="font-weight:400;color:var(--ap-text-3)">(comma-separated, one per blank)</span></label>
          <input type="text" class="ap-form-input" name="correct_words"
                 value="<?= val($qt,'correct_words') ?>" placeholder="opt1, opt5">
        </div>

        <?php /* ══ Highlight ══ */ elseif ($type==='highlight'): ?>
        <div class="fg">
          <label>Highlightable Phrases <span style="font-weight:400;color:var(--ap-text-3)">(comma-separated)</span></label>
          <input type="text" class="ap-form-input" name="options"
                 value="<?= val($qt,'options') ?>" placeholder="phrase one, phrase two, phrase three">
        </div>
        <div class="qp-g2" style="margin:0;">
          <div class="fg" style="margin:0;">
            <label>Correct Phrases <span style="font-weight:400;color:var(--ap-text-3)">(comma-separated)</span></label>
            <input type="text" class="ap-form-input" name="correct_words"
                   value="<?= val($qt,'correct_words') ?>" placeholder="phrase one">
          </div>
          <div class="fg" style="margin:0;">
            <label>Max Highlights</label>
            <input type="number" class="ap-form-input" name="maxHighlights"
                   value="<?= val($qt,'maxHighlights',2) ?>" min="1">
          </div>
        </div>
        <?php endif; ?>

      </div><!-- /qp-card-bd -->
    </div><!-- /type card -->

    <!-- Bottom submit (convenience for long forms) -->
    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:4px;">
      <a href="list.php?type=<?= urlencode($type) ?>" class="ap-btn ap-btn-ghost ap-btn-sm">Cancel</a>
      <button form="qForm" type="submit" class="ap-btn ap-btn-primary ap-btn-sm">
        <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Update' : 'Save' ?> Question
      </button>
    </div>

  </div><!-- /left -->

  <!-- ════ RIGHT: Classification (sticky) ════ -->
  <div class="qp-sticky">
    <div class="qp-card">
      <div class="qp-card-hd">
        <i class="bi bi-tag" style="color:var(--ap-primary)"></i>
        Rationale & Classification
      </div>
      <div class="qp-card-bd">
        <div class="fg">
          <label>Rationale <span style="color:#dc2626">*</span></label>
          <textarea class="ap-form-input" name="rationale" rows="4" required
                    style="resize:vertical;"><?= val($qt,'rationale') ?></textarea>
        </div>
        <div class="fg">
          <label>Topic</label>
          <select class="ap-form-input" name="topic">
            <option value="">— select topic —</option>
            <?php foreach ($topicList as $tl):
              $esc = htmlspecialchars($tl);
            ?><option value="<?=$esc?>"<?= sel($qt['topic']??'',$tl) ?>><?=$esc?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg">
          <label>Concept</label>
          <select class="ap-form-input" name="concept">
            <?= selOpts($concepts, $qt['concept']??'') ?>
          </select>
        </div>
        <div class="fg">
          <label>Difficulty Level</label>
          <select class="ap-form-input" name="dlevel">
            <?= selOpts($dlevels, $qt['dlevel']??'') ?>
          </select>
        </div>
        <div class="fg">
          <label>System</label>
          <input type="text" class="ap-form-input" name="system" value="<?= val($qt,'system') ?>">
        </div>
        <div class="fg">
          <label>CNC</label>
          <input type="text" class="ap-form-input" name="cnc" value="<?= val($qt,'cnc') ?>">
        </div>
        <div class="fg">
          <label>NARCAN</label>
          <input type="text" class="ap-form-input" name="narcan" value="<?= val($qt,'narcan') ?>">
        </div>
        <div class="fg">
          <label>Difficulty Logit</label>
          <input type="number" class="ap-form-input" name="difficulty_logit" step="0.01"
                 value="<?= val($qt,'difficulty_logit','') ?>" placeholder="0.00">
        </div>
        <div class="fg">
          <label>Image <span style="font-weight:400;color:var(--ap-text-3)">(optional)</span></label>
          <input type="file" class="ap-form-input" name="image" accept="image/*">
          <?php if (!empty($qt['image'])): ?>
          <div style="margin-top:5px;font-size:0.73rem;color:var(--ap-text-3);">
            Current: <?= htmlspecialchars($qt['image']) ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="fg" style="margin:0;">
          <label>Further Info</label>
          <textarea class="ap-form-input" name="furtherinfo" rows="2"
                    style="resize:vertical;"><?= val($qt,'furtherinfo') ?></textarea>
        </div>
      </div>
    </div>
  </div><!-- /right sticky -->

</div><!-- /qp-grid -->
</form>

<script>
/* ── Dynamic row helpers ── */
function removeRow(btn) { btn.closest('.dyn-row,.btq-row').remove(); }

function addSataItem(text='', checked=false) {
  const list = document.getElementById('sataList');
  const idx  = list.querySelectorAll('.dyn-row').length;
  const div  = document.createElement('div');
  div.className = 'dyn-row';
  div.innerHTML = `
    <input type="checkbox" name="sata_correct[]" value="${idx}" ${checked?'checked':''} style="flex-shrink:0;">
    <input type="text" class="ap-form-input" name="sata_item[]" value="${e(text)}" placeholder="Item ${idx+1}">
    <button type="button" class="dyn-rm" onclick="removeRow(this)"><i class="bi bi-x"></i></button>`;
  list.appendChild(div);
}

function addDragItem(listId, text='') {
  const list = document.getElementById(listId);
  const div  = document.createElement('div');
  div.className = 'dyn-row';
  div.innerHTML = `
    <input type="text" class="ap-form-input" value="${e(text)}" placeholder="Item…">
    <button type="button" class="dyn-rm" onclick="removeRow(this)"><i class="bi bi-x"></i></button>`;
  list.appendChild(div);
}

/* ── MMR matrix ── */
function addMmrCol(text='') {
  const div = document.createElement('div');
  div.className='dyn-row';
  div.innerHTML=`<input type="text" class="ap-form-input" value="${e(text)}" placeholder="Column…" oninput="buildMatrix()">
    <button type="button" class="dyn-rm" onclick="removeRow(this);buildMatrix()"><i class="bi bi-x"></i></button>`;
  document.getElementById('mmrColList').appendChild(div);
  buildMatrix();
}
function addMmrRow(text='') {
  const div = document.createElement('div');
  div.className='dyn-row';
  div.innerHTML=`<input type="text" class="ap-form-input" value="${e(text)}" placeholder="Row…" oninput="buildMatrix()">
    <button type="button" class="dyn-rm" onclick="removeRow(this);buildMatrix()"><i class="bi bi-x"></i></button>`;
  document.getElementById('mmrRowList').appendChild(div);
  buildMatrix();
}
function buildMatrix() {
  const cols = [...document.querySelectorAll('#mmrColList input[type=text]')].map(i=>i.value);
  const rows = [...document.querySelectorAll('#mmrRowList input[type=text]')].map(i=>i.value);
  const wrap = document.getElementById('mmrMatrix');
  if (!cols.length||!rows.length) {
    wrap.innerHTML='<em style="font-size:0.77rem;color:var(--ap-text-3);">Add columns and rows above.</em>'; return;
  }
  let h=`<table class="mx-tbl"><thead><tr><th></th>`;
  cols.forEach(c=>h+=`<th>${eh(c)}</th>`);
  h+=`</tr></thead><tbody>`;
  rows.forEach((r,ri)=>{
    h+=`<tr><td>${eh(r)}</td>`;
    cols.forEach((_,ci)=>h+=`<td><input type="checkbox" id="mmr_${ri}_${ci}"></td>`);
    h+=`</tr>`;
  });
  wrap.innerHTML=h+`</tbody></table>`;
}

/* ── Pre-submit: encode JSON fields ── */
document.getElementById('qForm').addEventListener('submit', function() {
  <?php if ($type==='sata'): ?>
  const items = [...document.querySelectorAll('#sataList input[type=text]')].map(i=>i.value);
  const chk   = [...document.querySelectorAll('#sataList input[type=checkbox]')].map((cb,i)=>cb.checked?i:null).filter(v=>v!==null);
  document.getElementById('items_json').value   = JSON.stringify(items);
  document.getElementById('correct_json').value = JSON.stringify(chk);
  <?php elseif ($type==='dragndrop'): ?>
  document.getElementById('items_json').value   = JSON.stringify([...document.querySelectorAll('#dragItemList input[type=text]')].map(i=>i.value));
  document.getElementById('correct_json').value = JSON.stringify([...document.querySelectorAll('#dragCorrectList input[type=text]')].map(i=>i.value));
  <?php elseif ($type==='mmr'): ?>
  const cols = [...document.querySelectorAll('#mmrColList input[type=text]')].map(i=>i.value);
  const rows = [...document.querySelectorAll('#mmrRowList input[type=text]')].map(i=>i.value);
  const correct={};
  rows.forEach((_,ri)=>{
    const ci=[]; cols.forEach((_,ci2)=>{const cb=document.getElementById(`mmr_${ri}_${ci2}`);if(cb&&cb.checked)ci.push(ci2);});
    if(ci.length) correct[ri]=ci;
  });
  document.getElementById('columns_json').value=JSON.stringify(cols);
  document.getElementById('rows_json').value   =JSON.stringify(rows);
  document.getElementById('correct_json').value=JSON.stringify(correct);
  <?php elseif ($type==='btq'): ?>
  ['actionToTake:btq_at','potentialConditions:btq_pc','parameterToMonitor:btq_pm'].forEach(pair=>{
    const [key,sid]=pair.split(':');
    const inputs=[...document.querySelectorAll(`#${sid} input[type=text]`)];
    const radios=[...document.querySelectorAll(`#${sid} input[type=radio]`)];
    const data=inputs.map((inp,i)=>({text:inp.value,correct:radios[i]?.checked||false}));
    document.getElementById(`${key}_json`).value=JSON.stringify(data);
  });
  <?php endif; ?>
});

/* ── Init MMR matrix with existing data (edit mode) ── */
<?php if ($type==='mmr' && $isEdit): ?>
document.addEventListener('DOMContentLoaded', ()=>{
  buildMatrix();
  const cor = <?= json_encode($mmrCor) ?>;
  setTimeout(()=>{
    Object.entries(cor||{}).forEach(([ri,cols])=>{
      (cols||[]).forEach(ci=>{const cb=document.getElementById(`mmr_${ri}_${ci}`);if(cb)cb.checked=true;});
    });
  }, 80);
});
<?php elseif ($type==='mmr'): ?>
document.addEventListener('DOMContentLoaded', buildMatrix);
<?php endif; ?>

function e(t)  { return String(t).replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
function eh(t) { return String(t).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
</script>

<?php require_once '../partials/footer.php'; ?>
