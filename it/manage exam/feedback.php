<?php
// Handle status update / delete before any output
include('../../config.php');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['admin_login'])) {
    header('Location: ../index.php');
    exit;
}
if (isset($_GET['id']) && isset($_GET['action'])) {
    $id     = intval($_GET['id']);
    $action = $_GET['action'];
    if ($action === 'reviewed') {
        db()->execute("UPDATE exam_feedback SET status = 'reviewed' WHERE id = ?", [$id]);
    } elseif ($action === 'delete') {
        db()->execute("DELETE FROM exam_feedback WHERE id = ?", [$id]);
    }
    header("location:feedback.php");
    exit;
}

$ap_base    = '../';
$active_nav = 'exam_mode';
$ap_title   = 'Exam Mode — Feedback Inbox';
$ap_extra_head = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="../../table css/dataTables.bootstrap5.min.css">';
require_once '../partials/sidebar.php';
date_default_timezone_set('Asia/Manila');

$rows = db()->fetchAll(
    "SELECT ef.*, l.fullname, l.studentnumber
     FROM exam_feedback ef
     LEFT JOIN login l ON l.id = ef.student_id
     ORDER BY ef.created_at DESC"
);
?>

<div class="ap-card">
  <div class="ap-card-header">
    <div class="ap-card-title">
      <div class="ap-card-title-icon"><i class="bi bi-chat-heart-fill"></i></div>
      Exam Mode — Feedback Inbox
    </div>
    <a href="index.php" class="ap-btn ap-btn-ghost ap-btn-xs"><i class="bi bi-clipboard-data-fill"></i> Omission Report</a>
  </div>

  <div style="padding:16px 20px; overflow-x:auto;">
    <table class="table data-table" style="width:100%;">
      <thead>
        <tr>
          <th>Student</th>
          <th>Attempt</th>
          <th>Question</th>
          <th>Category</th>
          <th>Message</th>
          <th>Status</th>
          <th>Submitted</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row):
            $preview = htmlspecialchars(substr((string)$row['message'], 0, 60) . (strlen((string)$row['message']) > 60 ? '...' : ''));
            $statusBadge = $row['status'] === 'reviewed' ? 'ap-badge-success' : 'ap-badge-warning';
        ?>
        <tr>
          <td style="font-weight:500;"><?= htmlspecialchars($row['fullname'] ?? 'Unknown') ?> <span style="color:#94a3b8;font-size:0.75em;">(#<?= htmlspecialchars($row['studentnumber'] ?? '') ?>)</span></td>
          <td>#<?= htmlspecialchars($row['examTaken']) ?></td>
          <td><?= htmlspecialchars($row['question_uid'] ?? '—') ?></td>
          <td><?= htmlspecialchars($row['category'] ?? 'General') ?></td>
          <td title="<?= htmlspecialchars((string)$row['message']) ?>"><?= $preview ?: '<em>(no message)</em>' ?></td>
          <td><span class="ap-badge <?= $statusBadge ?>"><?= htmlspecialchars($row['status']) ?></span></td>
          <td data-order="<?= strtotime($row['created_at']) ?>"><?= date('M j, Y g:i A', strtotime($row['created_at'])) ?></td>
          <td>
            <?php if ($row['status'] !== 'reviewed'): ?>
            <a href="feedback.php?id=<?= $row['id'] ?>&action=reviewed" class="ap-btn ap-btn-teal ap-btn-xs">
              <i class="bi bi-check-lg"></i> Mark Reviewed
            </a>
            <?php endif; ?>
            <a href="feedback.php?id=<?= $row['id'] ?>&action=delete" class="ap-btn ap-btn-danger ap-btn-xs"
               onclick="return confirm('Delete this feedback?')">
              <i class="bi bi-trash"></i> Delete
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$ap_extra_js = '
<script src="../.././table js/jquery-3.5.1.js"></script>
<script src="../.././table js/jquery.dataTables.min.js"></script>
<script src="../.././table js/script.js"></script>
<script>
$(document).ready(function () {
  // script.js initializes .data-table with no explicit order, which defaults to
  // sorting by column 0 (Student) — override so the latest feedback always shows first.
  $(".data-table").DataTable().order([[6, "desc"]]).draw();
});
</script>';
require_once '../partials/footer.php';
?>
