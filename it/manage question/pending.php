<?php
/**
 * pending.php — Approval queue for one question type.
 * Imported questions wait here until an admin approves them into the live question bank.
 * Validity is recomputed on every render rather than trusted from import time, because a
 * payload can be edited here after it was queued.
 */
include('../../config.php');
require_once __DIR__ . '/question_validators.php';
date_default_timezone_set('Asia/Manila');
if (session_status() === PHP_SESSION_NONE) session_start();

$type = $_GET['type'] ?? '';
if (!array_key_exists($type, $QUESTION_TABLE_MAP)) { header('Location: index.php'); exit; }
$table     = $QUESTION_TABLE_MAP[$type];
$typeLabel = $QUESTION_TYPE_LABELS[$type];

$status = $_GET['status'] ?? 'pending';
if (!in_array($status, ['pending', 'approved', 'rejected'], true)) $status = 'pending';

/* ── Counts per status, for the filter tabs ── */
$counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
$stmt = $con->prepare("SELECT status, COUNT(*) c FROM `pending_questions` WHERE `type` = ? GROUP BY status");
$stmt->bind_param('s', $type);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    if (isset($counts[$r['status']])) $counts[$r['status']] = (int)$r['c'];
}

/* ── Existing question text in the live table, for duplicate detection ── */
$keyMap = loadQuestionKeyMap($con, $table);

/* ── The queue itself ── */
$rows = [];
$stmt = $con->prepare("SELECT * FROM `pending_questions` WHERE `type` = ? AND status = ? ORDER BY id DESC");
$stmt->bind_param('ss', $type, $status);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $payload = json_decode($r['payload'], true);
    if (!is_array($payload)) {
        $r['payload_obj'] = null;
        $r['is_valid']    = false;
        $r['errors']      = ['Stored payload is not readable JSON.'];
    } else {
        $check            = validateForType($type, $payload);
        $r['payload_obj'] = $payload;
        $r['is_valid']    = $check['ok'];
        $r['errors']      = $check['ok'] ? [] : $check['errors'];
    }
    $key        = normalizeQuestionKey($r['question']);
    $existingId = ($key !== '' && isset($keyMap[$key])) ? $keyMap[$key] : 0;
    // An approved row matches the very copy it created, which is not worth warning about.
    $r['is_duplicate'] = $existingId > 0 && $existingId !== (int)$r['imported_id'];
    $rows[] = $r;
}

$validPending = 0;
if ($status === 'pending') foreach ($rows as $r) if ($r['is_valid']) $validPending++;

/* ── Page setup ──────────────────────────────────── */
$ap_base    = '../';
$active_nav = 'questions';
$ap_title   = 'Waiting for Approval — ' . $typeLabel;
$ap_extra_head = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
  .pq-wrap{padding:0 20px 30px;}
  .pq-tabs{display:flex;gap:6px;padding:14px 0 0;flex-wrap:wrap;}
  .pq-tab{padding:8px 16px;border-radius:9px 9px 0 0;font-size:0.8rem;font-weight:700;color:var(--ap-text-3);text-decoration:none;border:1px solid transparent;display:flex;align-items:center;gap:7px;}
  .pq-tab.active{background:var(--ap-card);color:var(--ap-primary);border-color:var(--ap-border);border-bottom-color:var(--ap-card);margin-bottom:-1px;}
  .pq-tab-count{background:var(--ap-bg-2);border-radius:100px;padding:1px 8px;font-size:0.7rem;font-weight:800;}
  .pq-tab.active .pq-tab-count{background:rgba(124,58,237,.12);color:#7C3AED;}
  .pq-card{background:var(--ap-card);border:1px solid var(--ap-border);border-radius:0 var(--ap-radius-lg) var(--ap-radius-lg) var(--ap-radius-lg);overflow:hidden;}
  .pq-bulkbar{position:sticky;top:0;z-index:20;display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:12px 18px;background:var(--ap-bg-2);border-bottom:1px solid var(--ap-border);}
  .pq-selcount{font-size:0.78rem;font-weight:700;color:var(--ap-text-2);min-width:92px;}
  .pq-spacer{flex:1;}
  .pq-row{display:flex;gap:14px;align-items:flex-start;padding:14px 18px;border-bottom:1px solid var(--ap-border);}
  .pq-row:last-child{border-bottom:none;}
  .pq-row.invalid{background:#fef2f2;}
  .pq-row.checked{background:#eff6ff;}
  .pq-row.invalid.checked{background:#fdf1f1;}
  .pq-check{width:18px;height:18px;flex-shrink:0;margin-top:3px;accent-color:#7C3AED;cursor:pointer;}
  .pq-main{flex:1;min-width:0;}
  .pq-qtext{font-size:0.85rem;font-weight:600;color:var(--ap-text-1);line-height:1.5;margin-bottom:6px;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;}
  .pq-meta{display:flex;gap:6px;flex-wrap:wrap;align-items:center;}
  .pq-chip{font-size:0.69rem;font-weight:700;padding:2px 9px;border-radius:100px;background:var(--ap-bg-2);color:var(--ap-text-3);}
  .pq-chip.ok{background:#dcfce7;color:#15803d;}
  .pq-chip.bad{background:#fee2e2;color:#b91c1c;}
  .pq-chip.warn{background:#fef3c7;color:#92400e;}
  .pq-errs{margin:8px 0 0;padding-left:18px;font-size:0.73rem;line-height:1.6;color:#b91c1c;}
  .pq-actions{display:flex;gap:6px;flex-shrink:0;flex-wrap:wrap;justify-content:flex-end;max-width:260px;}
  .pq-empty{text-align:center;padding:60px 20px;color:var(--ap-text-3);font-size:0.85rem;}
  .pq-empty i{font-size:2rem;display:block;margin-bottom:10px;opacity:.5;}
  #pqPreviewFrame{width:100%;height:78vh;border:none;background:#fff;}
  .pq-edit-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
  @media(max-width:700px){.pq-edit-grid{grid-template-columns:1fr;}}
  .pq-edit-full{grid-column:1/-1;}
  #pqAdvanced{width:100%;box-sizing:border-box;font-family:"SFMono-Regular",Consolas,Menlo,monospace;font-size:0.76rem;line-height:1.5;min-height:170px;resize:vertical;background:var(--ap-bg-2);}
  .pq-edit-status{font-size:0.76rem;font-weight:700;padding:9px 12px;border-radius:8px;margin-bottom:12px;}
  .pq-edit-status.ok{background:#dcfce7;color:#15803d;}
  .pq-edit-status.bad{background:#fee2e2;color:#b91c1c;}
  .pq-cmp-head{background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;padding:12px 14px;margin-bottom:14px;font-size:0.8rem;color:#92400e;line-height:1.6;}
  .pq-cmp-tbl{width:100%;border-collapse:separate;border-spacing:0;border:1px solid var(--ap-border);border-radius:10px;overflow:hidden;font-size:0.76rem;}
  .pq-cmp-tbl th{background:var(--ap-bg-2);padding:9px 11px;text-align:left;font-size:0.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.03em;color:var(--ap-text-3);border-bottom:1px solid var(--ap-border);}
  .pq-cmp-tbl td{padding:9px 11px;border-bottom:1px solid var(--ap-border);vertical-align:top;line-height:1.5;word-break:break-word;}
  .pq-cmp-tbl tr:last-child td{border-bottom:none;}
  .pq-cmp-tbl td.pq-cmp-field{font-weight:700;color:var(--ap-text-2);white-space:nowrap;width:150px;}
  .pq-cmp-tbl tr.diff td{background:#fffbeb;}
  .pq-cmp-tbl tr.diff td.pq-cmp-new{background:#f0fdf4;}
  .pq-cmp-empty{color:var(--ap-text-3);font-style:italic;}
  .pq-cmp-progress{font-size:0.74rem;font-weight:700;color:var(--ap-text-3);}
</style>';
$ap_topbar_actions = '
<a href="list.php?type=' . urlencode($type) . '" class="ap-btn ap-btn-ghost ap-btn-sm">
  <i class="bi bi-arrow-left"></i> Back to list
</a>
<a href="json_import.php?type=' . urlencode($type) . '" class="ap-btn ap-btn-ghost ap-btn-sm">
  <i class="bi bi-database"></i> Import more
</a>';
require_once '../partials/sidebar.php';
?>

<div class="pq-wrap">

  <div class="pq-tabs">
    <?php foreach (['pending' => ['Waiting for Approval', 'bi-hourglass-split'], 'approved' => ['Approved', 'bi-check2-circle'], 'rejected' => ['Rejected', 'bi-x-circle']] as $k => $meta): ?>
    <a class="pq-tab <?= $status === $k ? 'active' : '' ?>" href="?type=<?= urlencode($type) ?>&status=<?= $k ?>">
      <i class="bi <?= $meta[1] ?>"></i> <?= $meta[0] ?>
      <span class="pq-tab-count"><?= number_format($counts[$k]) ?></span>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="pq-card">

    <div class="pq-bulkbar">
      <label style="display:flex;align-items:center;gap:8px;font-size:0.78rem;font-weight:700;color:var(--ap-text-2);cursor:pointer;">
        <input type="checkbox" class="pq-check" id="pqSelectAll" onchange="pqToggleAll(this.checked)">
        Select all
      </label>
      <span class="pq-selcount" id="pqSelCount">0 selected</span>

      <?php if ($status === 'pending'): ?>
      <button type="button" class="ap-btn ap-btn-primary ap-btn-sm" id="pqApproveSel" onclick="pqBulk('approve')" disabled>
        <i class="bi bi-check2-all"></i> Approve Selected
      </button>
      <button type="button" class="ap-btn ap-btn-ghost ap-btn-sm" onclick="pqBulk('reject')" id="pqRejectSel" disabled>
        <i class="bi bi-x-circle"></i> Reject
      </button>
      <?php elseif ($status === 'rejected'): ?>
      <button type="button" class="ap-btn ap-btn-accent ap-btn-sm" onclick="pqBulk('restore')" id="pqRestoreSel" disabled>
        <i class="bi bi-arrow-counterclockwise"></i> Move back to queue
      </button>
      <?php endif; ?>

      <button type="button" class="ap-btn ap-btn-danger ap-btn-sm" onclick="pqBulk('delete')" id="pqDeleteSel" disabled>
        <i class="bi bi-trash"></i> Delete
      </button>

      <span class="pq-spacer"></span>

      <?php if ($status === 'pending' && $validPending > 0): ?>
      <button type="button" class="ap-btn ap-btn-primary ap-btn-sm" onclick="pqApproveAllValid()">
        <i class="bi bi-lightning-charge-fill"></i> Approve All Valid (<?= $validPending ?>)
      </button>
      <?php endif; ?>
    </div>

    <?php if (!$rows): ?>
      <div class="pq-empty">
        <i class="bi bi-inbox"></i>
        <?php if ($status === 'pending'): ?>
          Nothing is waiting for approval here.<br>
          Import first through <a href="json_import.php?type=<?= urlencode($type) ?>">Import from SQL / JSON</a> — questions land here before they go into the question bank.
        <?php else: ?>
          No <?= htmlspecialchars($status) ?> questions yet for <?= htmlspecialchars($typeLabel) ?>.
        <?php endif; ?>
      </div>
    <?php else: ?>
      <?php foreach ($rows as $r): ?>
      <div class="pq-row <?= $r['is_valid'] ? '' : 'invalid' ?>" id="pqRow<?= $r['id'] ?>">
        <input type="checkbox" class="pq-check pq-sel" value="<?= $r['id'] ?>" onchange="pqSync()">
        <div class="pq-main">
          <div class="pq-qtext"><?= htmlspecialchars(strip_tags($r['question'] ?: '(no question text)')) ?></div>
          <div class="pq-meta">
            <?php if ($r['is_valid']): ?>
              <span class="pq-chip ok"><i class="bi bi-check-circle-fill"></i> Ready</span>
            <?php else: ?>
              <span class="pq-chip bad"><i class="bi bi-exclamation-triangle-fill"></i> Needs fixing</span>
            <?php endif; ?>
            <?php if ($r['is_duplicate']): ?>
              <span class="pq-chip warn"><i class="bi bi-files"></i> Duplicate — already in the bank</span>
            <?php endif; ?>
            <?php if ($r['dlevel']): ?><span class="pq-chip"><?= htmlspecialchars($r['dlevel']) ?></span><?php endif; ?>
            <?php if ($r['topic']): ?><span class="pq-chip"><?= htmlspecialchars($r['topic']) ?></span><?php endif; ?>
            <?php if ($r['concept']): ?><span class="pq-chip"><?= htmlspecialchars($r['concept']) ?></span><?php endif; ?>
            <?php if ($status === 'approved' && $r['imported_id']): ?>
              <span class="pq-chip ok">Saved as #<?= (int)$r['imported_id'] ?></span>
            <?php endif; ?>
          </div>
          <?php if ($r['errors']): ?>
          <ul class="pq-errs">
            <?php foreach ($r['errors'] as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
          </ul>
          <?php endif; ?>
          <?php if ($status === 'rejected' && $r['reject_reason']): ?>
            <div style="margin-top:7px;font-size:0.74rem;color:#b45309;"><strong>Reason:</strong> <?= htmlspecialchars($r['reject_reason']) ?></div>
          <?php endif; ?>
        </div>
        <div class="pq-actions">
          <?php if ($r['is_valid']): ?>
          <button type="button" class="ap-btn ap-btn-ghost ap-btn-xs" onclick="pqPreview(<?= $r['id'] ?>)">
            <i class="bi bi-eye"></i> Preview
          </button>
          <?php endif; ?>
          <?php if ($status !== 'approved'): ?>
          <button type="button" class="ap-btn ap-btn-accent ap-btn-xs" onclick="pqEdit(<?= $r['id'] ?>)">
            <i class="bi bi-pencil"></i> Edit
          </button>
          <?php endif; ?>
          <?php if ($status === 'pending' && $r['is_valid']): ?>
          <button type="button" class="ap-btn ap-btn-primary ap-btn-xs" onclick="pqOne('approve', <?= $r['id'] ?>)">
            <i class="bi bi-check-lg"></i> Approve
          </button>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>

  </div>
</div>

<!-- Student-side preview -->
<div class="ap-modal-overlay" id="pqPreviewModal">
  <div class="ap-modal" style="max-width:1000px;width:95%;">
    <div class="ap-modal-header">
      <span class="ap-modal-title"><i class="bi bi-eye" style="color:var(--ap-primary);margin-right:6px;"></i>Student-Side Preview</span>
      <button class="ap-modal-close" onclick="document.getElementById('pqPreviewModal').classList.remove('open')">&times;</button>
    </div>
    <div style="padding:0;"><iframe id="pqPreviewFrame"></iframe></div>
  </div>
</div>

<!-- Edit before approval -->
<div class="ap-modal-overlay" id="pqEditModal">
  <div class="ap-modal" style="max-width:860px;width:95%;">
    <div class="ap-modal-header">
      <span class="ap-modal-title"><i class="bi bi-pencil-square" style="color:var(--ap-primary);margin-right:6px;"></i>Edit Before Approving</span>
      <button class="ap-modal-close" onclick="document.getElementById('pqEditModal').classList.remove('open')">&times;</button>
    </div>
    <div class="ap-modal-body" style="max-height:70vh;overflow-y:auto;">
      <div class="pq-edit-status" id="pqEditStatus"></div>
      <div class="pq-edit-grid">
        <div class="ap-form-group pq-edit-full">
          <label class="ap-form-label">Question</label>
          <textarea class="ap-form-textarea" id="pqfQuestion" rows="3"></textarea>
        </div>
        <div class="ap-form-group pq-edit-full">
          <label class="ap-form-label">Rationale</label>
          <textarea class="ap-form-textarea" id="pqfRationale" rows="4"></textarea>
        </div>
        <div class="ap-form-group">
          <label class="ap-form-label">Topic</label>
          <input class="ap-form-input" id="pqfTopic">
        </div>
        <div class="ap-form-group">
          <label class="ap-form-label">Concept</label>
          <input class="ap-form-input" id="pqfConcept">
        </div>
        <div class="ap-form-group">
          <label class="ap-form-label">Difficulty</label>
          <select class="ap-form-select" id="pqfDlevel">
            <option>Easy</option><option>Medium</option><option>Hard</option>
          </select>
        </div>
        <div class="ap-form-group">
          <label class="ap-form-label">Difficulty logit</label>
          <input class="ap-form-input" id="pqfLogit" type="number" step="0.01">
        </div>
        <div class="ap-form-group">
          <label class="ap-form-label">System</label>
          <input class="ap-form-input" id="pqfSystem">
        </div>
        <div class="ap-form-group">
          <label class="ap-form-label">CNC</label>
          <input class="ap-form-input" id="pqfCnc">
        </div>
        <div class="ap-form-group pq-edit-full">
          <label class="ap-form-label">NARC / Additional notes</label>
          <input class="ap-form-input" id="pqfNarcan">
        </div>
        <div class="ap-form-group pq-edit-full">
          <label class="ap-form-label">
            Type-specific fields (raw JSON)
            <span style="font-weight:400;color:var(--ap-text-3);font-size:0.74rem;">— choices, items, passage, correct answers, tabs…</span>
          </label>
          <textarea id="pqAdvanced" spellcheck="false"></textarea>
        </div>
      </div>
    </div>
    <div class="ap-modal-footer">
      <button type="button" class="ap-btn ap-btn-ghost ap-btn-sm" onclick="document.getElementById('pqEditModal').classList.remove('open')">Cancel</button>
      <button type="button" class="ap-btn ap-btn-accent ap-btn-sm" onclick="pqEditCheck()"><i class="bi bi-check2-circle"></i> Check</button>
      <button type="button" class="ap-btn ap-btn-primary ap-btn-sm" onclick="pqEditSave()"><i class="bi bi-save"></i> Save</button>
    </div>
  </div>
</div>

<!-- Duplicate found on approve — compare both versions and decide -->
<div class="ap-modal-overlay" id="pqCmpModal">
  <div class="ap-modal" style="max-width:960px;width:95%;">
    <div class="ap-modal-header">
      <span class="ap-modal-title"><i class="bi bi-files" style="color:#b45309;margin-right:6px;"></i>This question is already in the question bank</span>
      <span class="pq-cmp-progress" id="pqCmpProgress"></span>
    </div>
    <div class="ap-modal-body" style="max-height:66vh;overflow-y:auto;">
      <div class="pq-cmp-head" id="pqCmpHead"></div>
      <div id="pqCmpTable"></div>
      <label style="display:flex;align-items:center;gap:8px;margin-top:14px;font-size:0.78rem;color:var(--ap-text-2);cursor:pointer;" id="pqCmpAllWrap">
        <input type="checkbox" id="pqCmpAll" style="width:16px;height:16px;accent-color:#7C3AED;">
        Do the same for all remaining duplicates
      </label>
    </div>
    <div class="ap-modal-footer" style="flex-wrap:wrap;gap:8px;">
      <button type="button" class="ap-btn ap-btn-ghost ap-btn-sm" onclick="pqCmpChoose('skip')">
        <i class="bi bi-skip-forward"></i> Skip — leave it in the queue
      </button>
      <button type="button" class="ap-btn ap-btn-accent ap-btn-sm" onclick="pqCmpChoose('keep_both')">
        <i class="bi bi-plus-square"></i> Keep both
      </button>
      <button type="button" class="ap-btn ap-btn-primary ap-btn-sm" onclick="pqCmpChoose('replace')">
        <i class="bi bi-arrow-repeat"></i> Replace the one in the bank
      </button>
    </div>
  </div>
</div>

<script src="question_preview.js"></script>
<script>
const TYPE   = <?= json_encode($type) ?>;
const STATUS = <?= json_encode($status) ?>;
const ROWS   = <?= json_encode(array_map(fn($r) => ['id' => (int)$r['id'], 'payload' => $r['payload_obj']], $rows), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;

/** The nine fields every type shares — edited through labelled inputs rather than raw JSON. */
const COMMON_KEYS = ['question','rationale','topic','concept','dlevel','difficulty_logit','system','cnc','narcan'];

function payloadOf(id) {
  const row = ROWS.find(r => r.id === id);
  return row ? row.payload : null;
}

/* ── Selection ─────────────────────────────────── */
function pqSelected() {
  return Array.from(document.querySelectorAll('.pq-sel:checked')).map(cb => parseInt(cb.value, 10));
}
function pqSync() {
  const n = pqSelected().length;
  document.getElementById('pqSelCount').textContent = n + ' selected';
  ['pqApproveSel','pqRejectSel','pqRestoreSel','pqDeleteSel'].forEach(id => {
    const b = document.getElementById(id);
    if (b) b.disabled = n === 0;
  });
  document.querySelectorAll('.pq-sel').forEach(cb => {
    cb.closest('.pq-row').classList.toggle('checked', cb.checked);
  });
  const all = document.querySelectorAll('.pq-sel');
  const box = document.getElementById('pqSelectAll');
  box.checked = all.length > 0 && n === all.length;
  box.indeterminate = n > 0 && n < all.length;
}
function pqToggleAll(on) {
  document.querySelectorAll('.pq-sel').forEach(cb => { cb.checked = on; });
  pqSync();
}

/* ── Actions ───────────────────────────────────── */
const VERB = {
  approve: { title: 'Approve', past: 'approved', color: '#0d9488' },
  reject:  { title: 'Reject',  past: 'rejected', color: '#f59e0b' },
  restore: { title: 'Move back to queue', past: 'restored', color: '#0d9488' },
  delete:  { title: 'Delete',  past: 'deleted', color: '#ef4444' },
};

function pqSend(action, ids, extra) {
  return fetch('pending_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(Object.assign({ action, type: TYPE, ids }, extra || {}))
  }).then(r => r.json());
}

function pqRun(action, ids, extra) {
  const v = VERB[action];
  Swal.fire({ title: 'Working…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
  pqSend(action, ids, extra).then(data => {
    // Duplicates are never approved behind the admin's back — settle each one first.
    if (data.conflicts && data.conflicts.length) {
      Swal.close();
      pqCmpOpen(data.conflicts, data.approved || 0);
      return;
    }
    const n = data.approved ?? data.rejected ?? data.restored ?? data.deleted ?? 0;
    if (data.success) {
      Swal.fire({
        icon: data.skipped ? 'warning' : 'success',
        title: n + ' question(s) ' + v.past,
        text: data.skipped ? data.skipped + ' were skipped — check the errors in the list.' : '',
        confirmButtonColor: v.color
      }).then(() => location.reload());
    } else {
      Swal.fire({ icon: 'error', title: v.title + ' failed', text: data.error || 'Unknown error.', confirmButtonColor: '#ef4444' });
    }
  }).catch(err => Swal.fire({ icon: 'error', title: 'Request failed', text: String(err) }));
}

function pqOne(action, id) { pqRun(action, [id]); }

function pqBulk(action) {
  const ids = pqSelected();
  if (!ids.length) return;
  const v = VERB[action];

  if (action === 'reject') {
    Swal.fire({
      title: 'Reject ' + ids.length + ' question(s)?',
      input: 'textarea',
      inputLabel: 'Why? (optional)',
      inputPlaceholder: 'Example: wrong correct answer, missing rationale…',
      showCancelButton: true,
      confirmButtonText: 'Reject',
      confirmButtonColor: '#f59e0b'
    }).then(r => { if (r.isConfirmed) pqRun(action, ids, { reason: r.value || '' }); });
    return;
  }

  Swal.fire({
    title: v.title + ' ' + ids.length + ' question(s)?',
    text: action === 'delete'
      ? 'This permanently removes them from the queue. Questions already approved into the question bank are not affected.'
      : (action === 'approve' ? 'They will go into the live question bank.' : ''),
    icon: action === 'delete' ? 'warning' : 'question',
    showCancelButton: true,
    confirmButtonText: v.title,
    confirmButtonColor: v.color
  }).then(r => { if (r.isConfirmed) pqRun(action, ids); });
}

function pqApproveAllValid() {
  Swal.fire({
    title: 'Approve all valid questions?',
    text: 'Every question with a "Ready" badge goes into the live question bank. Questions with errors are skipped.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Approve all valid',
    confirmButtonColor: '#0d9488'
  }).then(r => { if (r.isConfirmed) pqRun('approve', [], { approve_all: true }); });
}

/* ══════════════════════════════════════════════════
   Duplicate resolution — one conflict at a time, then
   the approve is retried carrying the admin's choices.
   ══════════════════════════════════════════════════ */
let cmpQueue = [];
let cmpIdx   = 0;
let cmpChoices = {};
let cmpApprovedSoFar = 0;

/** Fields that carry no editorial meaning in a side-by-side comparison. */
const CMP_HIDE = ['id', 'tabs', 'image', 'furtherinfo'];

function cmpValue(v) {
  if (v === null || v === undefined || v === '') return null;
  if (typeof v === 'object') return JSON.stringify(v);
  return String(v);
}

function pqCmpOpen(conflicts, approvedSoFar) {
  cmpQueue = conflicts;
  cmpIdx = 0;
  cmpChoices = {};
  cmpApprovedSoFar = approvedSoFar;
  document.getElementById('pqCmpAll').checked = false;
  pqCmpRender();
  document.getElementById('pqCmpModal').classList.add('open');
}

function pqCmpRender() {
  const c = cmpQueue[cmpIdx];
  const total = cmpQueue.length;

  document.getElementById('pqCmpProgress').textContent = total > 1 ? ('Duplicate ' + (cmpIdx + 1) + ' of ' + total) : '';
  document.getElementById('pqCmpAllWrap').style.display = (total - cmpIdx > 1) ? '' : 'none';

  document.getElementById('pqCmpHead').innerHTML =
    'This question is already in the bank as <strong>#' + c.existing_id + '</strong>. ' +
    'It cannot be approved until you choose what to do.<br>' +
    '<span style="font-weight:700;">' + escapeHtml(c.question) + '</span>';

  const keys = Array.from(new Set(Object.keys(c.incoming || {}).concat(Object.keys(c.existing || {}))))
    .filter(k => !CMP_HIDE.includes(k));

  let body = '';
  keys.forEach(k => {
    const oldV = cmpValue((c.existing || {})[k]);
    const newV = cmpValue((c.incoming || {})[k]);
    if (oldV === null && newV === null) return;
    const differs = oldV !== newV;
    const cell = v => v === null
      ? '<span class="pq-cmp-empty">(empty)</span>'
      : escapeHtml(v.length > 400 ? v.slice(0, 400) + '…' : v);
    body += '<tr class="' + (differs ? 'diff' : '') + '">' +
            '<td class="pq-cmp-field">' + escapeHtml(k) + '</td>' +
            '<td>' + cell(oldV) + '</td>' +
            '<td class="' + (differs ? 'pq-cmp-new' : '') + '">' + cell(newV) + '</td>' +
            '</tr>';
  });

  document.getElementById('pqCmpTable').innerHTML =
    '<table class="pq-cmp-tbl"><thead><tr>' +
    '<th>Field</th><th>Already in the bank (#' + c.existing_id + ')</th><th>New — waiting for approval</th>' +
    '</tr></thead><tbody>' + (body || '<tr><td colspan="3" class="pq-cmp-empty">All fields are identical.</td></tr>') + '</tbody></table>';
}

function pqCmpChoose(choice) {
  const applyAll = document.getElementById('pqCmpAll').checked;

  if (applyAll) {
    for (let i = cmpIdx; i < cmpQueue.length; i++) cmpChoices[cmpQueue[i].pending_id] = choice;
    cmpIdx = cmpQueue.length;
  } else {
    cmpChoices[cmpQueue[cmpIdx].pending_id] = choice;
    cmpIdx++;
  }

  if (cmpIdx < cmpQueue.length) { pqCmpRender(); return; }

  document.getElementById('pqCmpModal').classList.remove('open');

  if (Object.values(cmpChoices).every(v => v === 'skip')) {
    Swal.fire({
      icon: cmpApprovedSoFar ? 'success' : 'info',
      title: cmpApprovedSoFar ? (cmpApprovedSoFar + ' question(s) approved') : 'Nothing was approved',
      text: 'The duplicates were left in the queue.',
      confirmButtonColor: '#0d9488'
    }).then(() => location.reload());
    return;
  }

  // Only the conflicted ids go back — the rest were already approved on the first pass.
  pqRun('approve', cmpQueue.map(c => c.pending_id), { resolutions: cmpChoices });
}

document.getElementById('pqCmpModal').addEventListener('click', function (e) {
  if (e.target === this) this.classList.remove('open');
});

/* ── Preview ───────────────────────────────────── */
function pqPreview(id) {
  const raw = payloadOf(id);
  if (!raw) return;
  const result = TYPE_CONFIG[TYPE].validate(raw, 0);
  if (!result.valid) {
    Swal.fire({ icon: 'error', title: 'Cannot preview', html: result.errors.map(escapeHtml).join('<br>') });
    return;
  }
  document.getElementById('pqPreviewFrame').srcdoc = TYPE_CONFIG[TYPE].buildPreview(result.normalized);
  document.getElementById('pqPreviewModal').classList.add('open');
}

/* ── Edit ──────────────────────────────────────── */
let pqEditingId = null;

function pqEdit(id) {
  const raw = payloadOf(id) || {};
  pqEditingId = id;

  document.getElementById('pqfQuestion').value  = raw.question  ?? '';
  document.getElementById('pqfRationale').value = raw.rationale ?? '';
  document.getElementById('pqfTopic').value     = raw.topic     ?? '';
  document.getElementById('pqfConcept').value   = raw.concept   ?? '';
  document.getElementById('pqfSystem').value    = raw.system    ?? '';
  document.getElementById('pqfCnc').value       = raw.cnc       ?? '';
  document.getElementById('pqfNarcan').value    = raw.narcan    ?? '';
  document.getElementById('pqfLogit').value     = raw.difficulty_logit ?? 0;
  document.getElementById('pqfDlevel').value    = ['Easy','Medium','Hard'].includes(raw.dlevel) ? raw.dlevel : 'Medium';

  const rest = {};
  Object.keys(raw).forEach(k => { if (!COMMON_KEYS.includes(k)) rest[k] = raw[k]; });
  document.getElementById('pqAdvanced').value = JSON.stringify(rest, null, 2);

  pqEditCheck();
  document.getElementById('pqEditModal').classList.add('open');
}

/** Merges the labelled inputs back over the raw-JSON remainder. Returns null if the JSON is malformed. */
function pqBuildPayload() {
  const box = document.getElementById('pqEditStatus');
  let rest;
  try {
    const txt = document.getElementById('pqAdvanced').value.trim();
    rest = txt === '' ? {} : JSON.parse(txt);
  } catch (e) {
    box.className = 'pq-edit-status bad';
    box.textContent = 'Invalid JSON in the type-specific fields: ' + e.message;
    return null;
  }
  return Object.assign({}, rest, {
    question:  document.getElementById('pqfQuestion').value,
    rationale: document.getElementById('pqfRationale').value,
    topic:     document.getElementById('pqfTopic').value,
    concept:   document.getElementById('pqfConcept').value,
    dlevel:    document.getElementById('pqfDlevel').value,
    system:    document.getElementById('pqfSystem').value,
    cnc:       document.getElementById('pqfCnc').value,
    narcan:    document.getElementById('pqfNarcan').value,
    difficulty_logit: parseFloat(document.getElementById('pqfLogit').value) || 0,
  });
}

function pqEditCheck() {
  const payload = pqBuildPayload();
  if (!payload) return null;
  const box = document.getElementById('pqEditStatus');
  const result = TYPE_CONFIG[TYPE].validate(payload, 0);
  if (result.valid) {
    box.className = 'pq-edit-status ok';
    box.textContent = 'Valid — ready to approve.';
  } else {
    box.className = 'pq-edit-status bad';
    box.innerHTML = 'Not valid yet:<br>' + result.errors.map(escapeHtml).join('<br>');
  }
  return payload;
}

function pqEditSave() {
  const payload = pqEditCheck();
  if (!payload) return;
  pqSend('update', [pqEditingId], { payload }).then(data => {
    if (data.success) location.reload();
    else Swal.fire({ icon: 'error', title: 'Save failed', text: data.error || 'Unknown error.' });
  }).catch(err => Swal.fire({ icon: 'error', title: 'Request failed', text: String(err) }));
}

[['pqPreviewModal'], ['pqEditModal']].forEach(([id]) => {
  document.getElementById(id).addEventListener('click', function (e) {
    if (e.target === this) this.classList.remove('open');
  });
});

pqSync();
</script>

<?php require_once '../partials/footer.php'; ?>
