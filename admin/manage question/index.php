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

$counts = [];
foreach ($tableMap as $key => $tbl) {
    $r = mysqli_query($con, "SELECT COUNT(*) c FROM `$tbl`");
    $counts[$key] = $r ? (int)mysqli_fetch_assoc($r)['c'] : 0;
}

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

<div class="ap-card">
  <div class="ap-card-header">
    <div class="ap-card-title">
      <div class="ap-card-title-icon"><i class="bi bi-patch-question-fill"></i></div>
      Question Banks
    </div>
    <span style="font-size:0.8rem;color:var(--ap-text-3);">
      <?= number_format(array_sum($counts)) ?> questions across all types
    </span>
  </div>

  <?php foreach ($groups as $grp): ?>
  <div style="padding:20px 24px 4px;">
    <div style="font-size:0.68rem;font-weight:700;letter-spacing:0.09em;text-transform:uppercase;color:var(--ap-text-3);margin-bottom:14px;display:flex;align-items:center;gap:8px;">
      <span style="display:inline-block;width:3px;height:13px;background:<?= $grp['accent'] ?>;border-radius:2px;"></span>
      <?= htmlspecialchars($grp['label']) ?>
    </div>
    <div class="ap-box-grid" style="padding:0;margin-bottom:20px;">
      <?php foreach ($grp['items'] as $it): ?>
      <div class="ap-box-item">
        <div class="ap-box-item-icon" style="background:linear-gradient(135deg,<?= $it['grad'] ?>);">
          <i class="<?= $it['icon'] ?>"></i>
        </div>
        <h3 style="font-size:0.82rem;"><?= htmlspecialchars($it['label']) ?></h3>
        <p style="font-size:0.72rem;color:var(--ap-text-3);text-align:center;margin:0;"><?= htmlspecialchars($it['desc']) ?></p>
        <div style="font-size:0.75rem;font-weight:700;color:var(--ap-primary);margin-top:2px;">
          <?= number_format($counts[$it['key']]) ?> questions
        </div>
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
