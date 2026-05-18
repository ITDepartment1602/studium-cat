<?php
// ─────────────────────────────────────────────────────────────────────
// dlevel Data Readiness Audit — EXAM_MODE_ENHANCEMENT_PLAN.md §T0.3 + Appendix A
// Verifies that every question-source table has usable `dlevel` distributions
// before the Exam Mode adaptive CAT engine goes live.
// ─────────────────────────────────────────────────────────────────────
require_once '../../../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Access denied. Login required.');
}

$qc = getQuizConnection();

// Table → description + recommended minimums per level (see plan Appendix A)
$tables = [
    'mcq'       => ['label' => 'Multiple Choice (mcq)',                 'min' => 20, 'extra' => ''],
    'cat'       => ['label' => 'CAT (mc-only, type NULL/empty)',        'min' => 10, 'extra' => "(type IS NULL OR type = '')"],
    'sata'      => ['label' => 'Select All That Apply (sata)',          'min' => 10, 'extra' => ''],
    'mmr'       => ['label' => 'Matrix Multiple Response (mmr)',        'min' => 7,  'extra' => ''],
    'mpr'       => ['label' => 'Multiple Priority Response (mpr)',      'min' => 3,  'extra' => ''],
    'btq'       => ['label' => 'Bow-Tie (btq)',                         'min' => 3,  'extra' => ''],
    'dragndrop' => ['label' => 'Drag & Drop (dragndrop)',               'min' => 3,  'extra' => ''],
    'dropdown'  => ['label' => 'Drop-Down (dropdown)',                  'min' => 3,  'extra' => ''],
    'highlight' => ['label' => 'Highlight (highlight)',                 'min' => 5,  'extra' => ''],
];

function table_exists_qc($qc, $table) {
    $safe = mysqli_real_escape_string($qc, $table);
    $r = mysqli_query($qc, "SHOW TABLES LIKE '{$safe}'");
    return $r && mysqli_num_rows($r) > 0;
}

$report = [];
foreach ($tables as $tbl => $meta) {
    if (!table_exists_qc($qc, $tbl)) {
        $report[$tbl] = ['missing' => true, 'label' => $meta['label']];
        continue;
    }
    $where = $meta['extra'] !== '' ? "WHERE {$meta['extra']}" : '';
    $sql = "SELECT dlevel, COUNT(*) AS cnt FROM `{$tbl}` {$where} GROUP BY dlevel";
    $res = mysqli_query($qc, $sql);
    $levels = ['easy' => 0, 'moderate' => 0, 'medium' => 0, 'hard' => 0, 'NULL' => 0, 'other' => 0];
    $total = 0;
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $k = $row['dlevel'];
            $c = intval($row['cnt']);
            $total += $c;
            if ($k === null || $k === '')      $levels['NULL']   += $c;
            elseif (isset($levels[strtolower($k)])) $levels[strtolower($k)] += $c;
            else                                $levels['other']  += $c;
        }
    }
    $report[$tbl] = ['missing' => false, 'label' => $meta['label'], 'min' => $meta['min'], 'levels' => $levels, 'total' => $total];
}
?><!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><title>dlevel Readiness Audit</title>
<style>
body { font-family:-apple-system,Segoe UI,sans-serif; background:#f8fafc; color:#1e293b; padding:30px; max-width:1100px; margin:0 auto; }
h1 { color:#0a1628; font-size:1.6rem; margin:0 0 6px; }
.sub { color:#64748b; margin:0 0 24px; font-size:0.92rem; }
table { width:100%; border-collapse:collapse; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.05); }
th { background:#0a1628; color:#fff; padding:12px 14px; text-align:left; font-weight:600; font-size:0.85rem; }
td { padding:11px 14px; border-bottom:1px solid #f1f5f9; font-size:0.9rem; vertical-align:middle; }
tr:last-child td { border-bottom:none; }
.pill { display:inline-block; padding:3px 10px; border-radius:20px; font-size:0.75rem; font-weight:700; color:#fff; }
.ok { background:#10b981; } .warn { background:#f59e0b; } .bad { background:#ef4444; } .muted { background:#94a3b8; }
.missing-row { background:#fef2f2; }
.legend { margin-top:20px; padding:14px 18px; background:#fff; border-radius:10px; font-size:0.85rem; color:#475569; }
.back { display:inline-block; margin-bottom:16px; color:#3b82f6; text-decoration:none; }
</style></head><body>
<a class="back" href="../">← Back to dashboard</a>
<h1>📊 dlevel Data Readiness Audit</h1>
<p class="sub">Verifies the 9 exam-source tables have sufficient <code>dlevel</code>-tagged questions before Exam Mode goes live. Run once before enabling CAT. (Plan §T0.3 + Appendix A)</p>

<table>
<thead><tr>
  <th>Table</th><th>Easy</th><th>Moderate/Medium</th><th>Hard</th><th>Untagged</th><th>Other</th><th>Total</th><th>Status</th>
</tr></thead>
<tbody>
<?php foreach ($report as $tbl => $r): ?>
  <?php if ($r['missing']): ?>
    <tr class="missing-row">
      <td><strong><?= htmlspecialchars($tbl) ?></strong><br><small><?= htmlspecialchars($r['label']) ?></small></td>
      <td colspan="6"><span class="pill bad">TABLE MISSING</span> — cannot contribute to the 150-item pool.</td>
      <td><span class="pill bad">FAIL</span></td>
    </tr>
  <?php else:
      $lv = $r['levels']; $min = $r['min'];
      $easyOK = $lv['easy'] >= $min;
      $modOK  = ($lv['moderate'] + $lv['medium']) >= $min;
      $hardOK = $lv['hard'] >= $min;
      $untagged = $lv['NULL'];
      if ($easyOK && $modOK && $hardOK) { $status = '<span class="pill ok">READY</span>'; }
      elseif ($untagged > 0 || !($easyOK && $modOK && $hardOK)) { $status = '<span class="pill warn">NEEDS TAGGING</span>'; }
      else { $status = '<span class="pill bad">LOW VOLUME</span>'; }
  ?>
    <tr>
      <td><strong><?= htmlspecialchars($tbl) ?></strong><br><small><?= htmlspecialchars($r['label']) ?></small></td>
      <td><span class="pill <?= $easyOK?'ok':'bad' ?>"><?= $lv['easy'] ?></span></td>
      <td><span class="pill <?= $modOK?'ok':'bad' ?>"><?= $lv['moderate']+$lv['medium'] ?></span></td>
      <td><span class="pill <?= $hardOK?'ok':'bad' ?>"><?= $lv['hard'] ?></span></td>
      <td><span class="pill <?= $untagged>0?'warn':'muted' ?>"><?= $untagged ?></span></td>
      <td><span class="pill muted"><?= $lv['other'] ?></span></td>
      <td><strong><?= $r['total'] ?></strong></td>
      <td><?= $status ?></td>
    </tr>
  <?php endif; ?>
<?php endforeach; ?>
</tbody></table>

<div class="legend">
  <strong>Status legend:</strong>
  <span class="pill ok">READY</span> = all three levels meet min count ·
  <span class="pill warn">NEEDS TAGGING</span> = NULL/unknown dlevel values exist ·
  <span class="pill bad">LOW VOLUME</span> = a level is below recommended minimum.<br><br>
  <strong>Fix missing tags:</strong>
  <code>UPDATE tablename SET dlevel = 'medium' WHERE dlevel IS NULL OR dlevel = '';</code>
</div>
</body></html>
