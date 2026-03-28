<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Manila');

include_once '../config.php';

$query = "
    SELECT id, studentnumber, fullname, dateenrolled, subMonth, dateexpired
    FROM login 
    WHERE dateenrolled >= '2025-08-01 00:00:00' 
    ORDER BY id DESC
";

$result = $con->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Access History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
      <link rel="shortcut icon" type="text/css" href="../img/logo1.svg">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
   <style>
    :root {
        --main-color: #1B4965;
        --main-hover: #15394f; /* Darker shade for hover */
    }
    body {
        background-color: #f5f7fa;
        font-family: 'Segoe UI', sans-serif;
    }
    .card-header {
        background-color: var(--main-color);
        color: white;
    }
    .btn-home {
        background-color: var(--main-color);
        color: white;
        border-radius: 50px;
        font-weight: 500;
        padding: 8px 20px;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }
    .btn-home:hover {
        background-color: var(--main-hover);
        color: white;
        transform: translateY(-2px); /* slight lift on hover */
    }
</style>

</head>
<body>
    <div class="container mt-4">
        <!-- Home Button -->
        <div class="d-flex justify-content-end mb-3">
            <a href="index.php" class="btn btn-home px-4">
                <i class="bi bi-house-door-fill me-1"></i> Home
            </a>
        </div>

        <div class="card shadow">
            <div class="card-header text-center py-3">
                <h5 class="mb-0">Access History (From August 1, 2025)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="accessTable" class="table table-bordered table-striped">
                        <thead class="table-primary">
                            <tr>
                                <th>Sort #</th>
                                <th>Student Number</th>
                                <th>Full Name</th>
                                <th>Date Enrolled</th>
                                <th>Month/s</th>
                              <th>Emails</th>
                            </tr>
                        </thead>
                        <tbody>

<?php while($row = $result->fetch_assoc()): ?>

<?php
// CHECK IF EMAIL ALREADY SENT
$check = $con->prepare("SELECT id FROM email_sent_status WHERE student_id = ? LIMIT 1");
$check->bind_param("i", $row['id']);
$check->execute();
$check_result = $check->get_result();
$email_already_sent = $check_result->num_rows > 0;
?>

<tr>
       <td><?= $row['id'] ?></td>
    <td><?= htmlspecialchars($row['studentnumber']) ?></td>
    <td><?= htmlspecialchars($row['fullname']) ?></td>
    <td><?= date('F d, Y | h:i A', strtotime($row['dateenrolled'])) ?></td>

    <td>
        <?php
        $subMonth = $row['subMonth'] ?? null;
        $dateExpired = $row['dateexpired'] ?? null;

        date_default_timezone_set('Asia/Manila');
        $now = new DateTime('now', new DateTimeZone('Asia/Manila'));

        if (!empty($subMonth)) {
            echo htmlspecialchars($subMonth) . " Month/s";
        } else {
            if (!empty($dateExpired) && $dateExpired !== '0000-00-00 00:00:00') {
                $exp = new DateTime($dateExpired, new DateTimeZone('Asia/Manila'));

                if ($exp < $now) {
                    echo "Expired";
                } else {
                    $diff = $now->diff($exp);
                    $monthsLeft = ($diff->y * 12) + $diff->m;
                    $daysLeft = $diff->d;

                    echo "Activated — {$monthsLeft} month(s), {$daysLeft} day(s) left";
                }
            } else {
                echo "No Data";
            }
        }
        ?>
    </td>

    <td>
        <?php if ($email_already_sent): ?>
            <span style="color: gray; opacity: .5; cursor: not-allowed;">Sent</span>
        <?php else: ?>
            <a href="send_email.php?id=<?= $row['id'] ?>" 
               style="text-decoration: none; color: #0d6efd;">
               Send Email
            </a>
        <?php endif; ?>
    </td>
</tr>

<?php endwhile; ?>

</tbody>

                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- JS dependencies -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $('#accessTable').DataTable({
    "order": [[0, "desc"]], // hidden column index
    "columnDefs": [
        { "targets": 0, "visible": false } // hide raw date column
    ]
});

    </script>

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</body>
</html>
