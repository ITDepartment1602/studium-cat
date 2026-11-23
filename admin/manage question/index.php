<?php
$ap_base    = '../';
$active_nav = 'questions';
$ap_title   = 'Manage Questions';
require_once '../partials/sidebar.php';
include('../../config.php');

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

/* Live bank size and how many of those went in today, per type. */
$counts = $newToday = [];
foreach ($tableMap as $key => $tbl) {
    $r = db()->fetchOne("SELECT COUNT(*) c, SUM(created_at >= CURDATE()) t FROM `$tbl`");
    $counts[$key]   = (int)($r['c'] ?? 0);
    $newToday[$key] = (int)($r['t'] ?? 0);
}

/* The approval queue: everything still waiting, and how much of it was queued today. */
$pending = $pendingToday = array_fill_keys(array_keys($tableMap), 0);
$queue = db()->fetchAll("SELECT `type`, COUNT(*) c, SUM(created_at >= CURDATE()) t FROM `pending_questions` WHERE status = 'pending' GROUP BY `type`");
foreach ($queue as $r) {
    if (!isset($pending[$r['type']])) continue;
    $pending[$r['type']]      = (int)$r['c'];
    $pendingToday[$r['type']] = (int)$r['t'];
}

/** Totals for a list of question type keys. */
function bankTotals(array $keys, array $counts, array $newToday, array $pending, array $pendingToday): array {
    $pick = fn($src) => array_sum(array_intersect_key($src, array_flip($keys)));
    return ['total' => $pick($counts), 'new' => $pick($newToday), 'pending' => $pick($pending), 'pendingNew' => $pick($pendingToday)];
}
$allTotals = bankTotals(array_keys($tableMap), $counts, $newToday, $pending, $pendingToday);

$groups = [
    [
        'label' => 'Traditional',
        'accent' => '#007CBF',
        'items' => [
            ['key'=>'traditional','label'=>'Multiple Choice (MCQ)', 'icon'=>'bi-list-check',    'desc'=>'4-option single-answer questions', 'grad'=>'#007CBF,#0090db'],
            ['key'=>'sata',       'label'=>'SATA',                 'icon'=>'bi-check2-square', 'desc'=>'Select All That Apply',           'grad'=>'#0D9488,#14b8a6'],
        ]
    ],
    [
        'label' => 'NGN — Next Generation NCLEX',
        'accent' => '#7C3AED',
        'items' => [
            ['key'=>'mpr',      'label'=>'Matrix / Priority Response',  'icon'=>'bi-grid-3x3',         'desc'=>'Ranked or prioritized item selection',   'grad'=>'#7C3AED,#8b5cf6'],
            ['key'=>'mmr',      'label'=>'Matrix Multiple Response',    'icon'=>'bi-table',            'desc'=>'Row-column matrix, multiple answers',    'grad'=>'#6D28D9,#7c3aed'],
            ['key'=>'btq',      'label'=>'Bow-Tie',                    'icon'=>'bi-arrow-left-right',  'desc'=>'Actions, conditions & monitoring params', 'grad'=>'#DC2626,#ef4444'],
            ['key'=>'dragndrop','label'=>'Drag & Drop',                'icon'=>'bi-arrows-move',       'desc'=>'Fill blanks by dragging answer choices',  'grad'=>'#D97706,#f59e0b'],
            ['key'=>'dropdown', 'label'=>'Dropdown Cloze',             'icon'=>'bi-menu-button-wide',  'desc'=>'Fill blanks with dropdown selections',    'grad'=>'#059669,#10b981'],
            ['key'=>'highlight','label'=>'Highlight',                  'icon'=>'bi-highlighter',       'desc'=>'Highlight correct phrases in a passage',  'grad'=>'#DB2777,#ec4899'],
        ]
    ],
];
?>

<style>
  /* Each card shares the grid's rows, so every line sits level with the same line on the cards
     beside it, even when a title wraps or a +N today badge drops to a second line. */
  .ap-box-item.qb-card{display:grid;grid-row:span 6;grid-template-rows:subgrid;row-gap:10px;justify-items:center;align-items:start;}
  .qb-card h3{font-size:0.82rem;line-height:1.3;min-height:2.6em;display:flex;align-items:center;}
  .qb-card p{font-size:0.72rem;line-height:1.4;min-height:2.8em;color:var(--ap-text-3);text-align:center;margin:0;}
  .qb-count{display:flex;align-items:center;justify-content:center;gap:5px;flex-wrap:wrap;font-size:0.75rem;font-weight:700;color:var(--ap-primary);}
  .qb-new{display:inline-block;font-size:0.66rem;font-weight:800;color:#15803d;background:#dcfce7;border-radius:100px;padding:1px 7px;white-space:nowrap;}
  .qb-new.wait{color:#b45309;background:#fef3c7;}
  .qb-pending{display:flex;align-items:center;justify-content:center;gap:5px;flex-wrap:wrap;font-size:0.72rem;font-weight:700;color:#b45309;text-decoration:none;}
  .qb-pending:hover{text-decoration:underline;}
  .qb-pending.none{color:var(--ap-text-3);font-weight:500;}
  .qb-totals{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-left:auto;font-size:0.72rem;font-weight:700;letter-spacing:0;text-transform:none;color:var(--ap-text-2);}
</style>

<div class="ap-card">
  <div class="ap-card-header">
    <div class="ap-card-title">
      <div class="ap-card-title-icon"><i class="bi bi-patch-question-fill"></i></div>
      Question Banks
    </div>
    <span class="qb-totals">
      <?= number_format($allTotals['total']) ?> questions
      <?php if ($allTotals['new']): ?><span class="qb-new">+<?= number_format($allTotals['new']) ?> today</span><?php endif; ?>
      &middot; <?= number_format($allTotals['pending']) ?> pending approval
      <?php if ($allTotals['pendingNew']): ?><span class="qb-new wait">+<?= number_format($allTotals['pendingNew']) ?> today</span><?php endif; ?>
    </span>
  </div>

  <?php foreach ($groups as $grp):
    $grpTotals = bankTotals(array_column($grp['items'], 'key'), $counts, $newToday, $pending, $pendingToday);
  ?>
  <div style="padding:20px 24px 4px;">
    <div style="font-size:0.68rem;font-weight:700;letter-spacing:0.09em;text-transform:uppercase;color:var(--ap-text-3);margin-bottom:14px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
      <span style="display:inline-block;width:3px;height:13px;background:<?= $grp['accent'] ?>;border-radius:2px;"></span>
      <?= htmlspecialchars($grp['label']) ?>
      <span class="qb-totals">
        <?= number_format($grpTotals['total']) ?> questions
        <?php if ($grpTotals['new']): ?><span class="qb-new">+<?= number_format($grpTotals['new']) ?> today</span><?php endif; ?>
        &middot; <?= number_format($grpTotals['pending']) ?> pending approval
        <?php if ($grpTotals['pendingNew']): ?><span class="qb-new wait">+<?= number_format($grpTotals['pendingNew']) ?> today</span><?php endif; ?>
      </span>
    </div>
    <div class="ap-box-grid" style="padding:0;margin-bottom:20px;">
      <?php foreach ($grp['items'] as $it): ?>
      <div class="ap-box-item qb-card">
        <div class="ap-box-item-icon" style="background:linear-gradient(135deg,<?= $it['grad'] ?>);">
          <i class="<?= $it['icon'] ?>"></i>
        </div>
        <h3><?= htmlspecialchars($it['label']) ?></h3>
        <p><?= htmlspecialchars($it['desc']) ?></p>
        <div class="qb-count">
          <?= number_format($counts[$it['key']]) ?> questions
          <?php if ($newToday[$it['key']]): ?><span class="qb-new">+<?= number_format($newToday[$it['key']]) ?> today</span><?php endif; ?>
        </div>
        <a href="pending.php?type=<?= $it['key'] ?>" class="qb-pending<?= $pending[$it['key']] ? '' : ' none' ?>">
          <i class="bi bi-hourglass-split"></i> <?= number_format($pending[$it['key']]) ?> pending approval
          <?php if ($pendingToday[$it['key']]): ?><span class="qb-new wait">+<?= number_format($pendingToday[$it['key']]) ?> today</span><?php endif; ?>
        </a>
        <div class="ap-box-actions">
          <a href="list.php?type=<?= $it['key'] ?>" class="ap-btn ap-btn-accent ap-btn-sm">
            <i class="bi bi-folder2-open"></i> Manage
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- Exam Mode informational section -->
  <div style="padding:4px 24px 24px;">
    <div style="font-size:0.68rem;font-weight:700;letter-spacing:0.09em;text-transform:uppercase;color:var(--ap-text-3);margin-bottom:14px;display:flex;align-items:center;gap:8px;">
      <span style="display:inline-block;width:3px;height:13px;background:#64748b;border-radius:2px;"></span>
      Exam Mode
    </div>
    <div style="background:var(--ap-bg-2);border-radius:10px;padding:14px 18px;display:flex;align-items:center;gap:14px;">
      <i class="bi bi-cpu-fill" style="font-size:1.4rem;color:#0891B2;flex-shrink:0;"></i>
      <div>
        <div style="font-size:0.84rem;font-weight:600;color:var(--ap-text-1);margin-bottom:3px;">CAT / IRT Adaptive Testing</div>
        <div style="font-size:0.77rem;color:var(--ap-text-3);line-height:1.5;">
          Exam mode uses Computer Adaptive Testing (IRT) and selects questions from <strong>all types above</strong> based on each student's live performance. There is no separate question bank for Exam mode — manage questions in their respective types above.
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once '../partials/footer.php'; ?>
