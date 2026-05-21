<?php
$ap_base    = '../';
$active_nav = 'feedback';
$ap_title   = 'Feedback';
$ap_extra_head = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="../../table css/dataTables.bootstrap5.min.css">';
require_once '../partials/sidebar.php';
include('../../config.php');
?>

<div class="ap-card">
  <div class="ap-card-header">
    <div class="ap-card-title">
      <div class="ap-card-title-icon"><i class="bi bi-chat-heart-fill"></i></div>
      Student Feedback
    </div>
  </div>

  <div style="padding:16px 20px; overflow-x:auto;">
    <table class="table data-table" style="width:100%;">
      <thead>
        <tr>
          <th>Full Name</th>
          <th>Concept</th>
          <th>Message</th>
          <th>Question ID</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $query = "SELECT * FROM feedback LEFT JOIN login ON feedback.login_id = login.id WHERE login.groupname != 'Admin'";
        $data = mysqli_query($con, $query);
        while ($rows = mysqli_fetch_array($data)):
          $preview = htmlspecialchars(substr($rows['message'], 0, 40) . (strlen($rows['message']) > 40 ? '...' : ''));
        ?>
        <tr>
          <td style="font-weight:500;"><?= htmlspecialchars($rows['fullname']) ?></td>
          <td><?= htmlspecialchars($rows['name'] ?? '') ?></td>
          <td title="<?= htmlspecialchars($rows['message']) ?>"><?= $preview ?></td>
          <td><?= htmlspecialchars($rows['question_id'] ?? '') ?></td>
          <td>
            <a href="action.php?id=<?= $rows['id'] ?>&action=delete" class="ap-btn ap-btn-danger ap-btn-xs"
               onclick="return confirm('Delete this feedback?')">
              <i class="bi bi-trash"></i> Delete
            </a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$ap_extra_js = '
<script src="../.././table js/jquery-3.5.1.js"></script>
<script src="../.././table js/jquery.dataTables.min.js"></script>
<script src="../.././table js/dataTables.bootstrap5.min.js"></script>
<script src="../.././table js/script.js"></script>';
require_once '../partials/footer.php';
?>
