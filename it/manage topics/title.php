<?php
$ap_base    = '../';
$active_nav = 'topics';
$ap_title   = 'Manage Concept';
require_once '../partials/sidebar.php';
include('../../config.php');

$title = $_GET['title'] ?? '';

$stmt = mysqli_prepare($con, "SELECT * FROM `topics1` WHERE kilanlan = ?");
mysqli_stmt_bind_param($stmt, 's', $title);
mysqli_stmt_execute($stmt);
$query = mysqli_stmt_get_result($stmt);
?>

<div class="ap-card">
  <div class="ap-card-header">
    <div class="ap-card-title">
      <div class="ap-card-title-icon"><i class="bi bi-collection-fill"></i></div>
      <?= htmlspecialchars($title ?: 'Manage Concept') ?>
    </div>
    <a href="index.php" class="ap-btn ap-btn-ghost ap-btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
  </div>

  <div class="ap-topic-grid">
    <?php while ($row = mysqli_fetch_assoc($query)): ?>
    <div class="ap-topic-item">
      <img src="<?= htmlspecialchars($row['img']) ?>" alt="" class="ap-topic-img">
      <div class="ap-topic-body">
        <div class="ap-topic-name"><?= htmlspecialchars($row['title']) ?></div>
        <div class="ap-topic-actions">
          <a href="questionlist.php?title=<?= urlencode($title) ?>&concept=<?= urlencode($row['title']) ?>"
             class="ap-btn ap-btn-accent ap-btn-sm">
            <i class="bi bi-folder2-open"></i> Open
          </a>
        </div>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
</div>

<?php require_once '../partials/footer.php'; ?>
