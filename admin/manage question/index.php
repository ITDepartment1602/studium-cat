<?php
$ap_base    = '../';
$active_nav = 'questions';
$ap_title   = 'Manage Questions';
require_once '../partials/sidebar.php';
?>

<div class="ap-card">
  <div class="ap-card-header">
    <div class="ap-card-title">
      <div class="ap-card-title-icon"><i class="bi bi-patch-question-fill"></i></div>
      Question Banks
    </div>
    <span class="text-muted fs-sm">Select a question type to manage</span>
  </div>

  <div class="ap-box-grid" style="padding:24px;">
    <div class="ap-box-item">
      <div class="ap-box-item-icon" style="background:linear-gradient(135deg,#007CBF,#0090db);">
        <i class="bi bi-list-check"></i>
      </div>
      <h3>Traditional</h3>
      <p class="text-muted fs-sm" style="text-align:center;">Multiple choice questions</p>
      <div class="ap-box-actions">
        <a href="questionlist.php" class="ap-btn ap-btn-accent ap-btn-sm">
          <i class="bi bi-folder2-open"></i> Open
        </a>
      </div>
    </div>

    <div class="ap-box-item">
      <div class="ap-box-item-icon" style="background:linear-gradient(135deg,#0D9488,#14b8a6);">
        <i class="bi bi-check2-square"></i>
      </div>
      <h3>SATA</h3>
      <p class="text-muted fs-sm" style="text-align:center;">Select all that apply</p>
      <div class="ap-box-actions">
        <a href="questionlistsata.php" class="ap-btn ap-btn-teal ap-btn-sm">
          <i class="bi bi-folder2-open"></i> Open
        </a>
      </div>
    </div>
  </div>
</div>

<?php require_once '../partials/footer.php'; ?>
