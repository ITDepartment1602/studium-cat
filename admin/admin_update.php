<?php
include("count.php");
$count = new count;
$userd = $count->show_users();
?>
<?php
include("../config.php");

// Handle Update Submission
if (count($_POST) > 0) {
    $bundle_name = implode(",", $_POST['bundle_name']); // Convert array to comma-separated string
    $subMonth = !empty($_POST['subMonth']) ? $_POST['subMonth'] : null;

    // Handle dateexpired (NULL if empty or 0000-00-00 00:00:00)
    $dateexpired = $_POST['dateexpired'];
    if (empty($dateexpired) || $dateexpired === '0000-00-00 00:00:00') {
        $dateexpired = null;
    }

    $query = "UPDATE login SET 
        studentnumber='" . $_POST['studentnumber'] . "',
        fullname='" . $_POST['fullname'] . "',
        bundle_name='" . $bundle_name . "',
        groupname='" . $_POST['groupname'] . "', 
        dateexpired=" . (is_null($dateexpired) ? "NULL" : "'" . $dateexpired . "'") . ",
        dateenrolled='" . $_POST['dateenrolled'] . "',
        subMonth=" . (is_null($subMonth) ? "NULL" : "'" . $subMonth . "'") . ",
        password='" . $_POST['password'] . "', 
        email='" . $_POST['email'] . "' 
        WHERE id='" . $_POST['id'] . "'";

    mysqli_query($con, $query);
    $message = "<p style='color:green;'>Record Modified Successfully!</p>";
}

$result = mysqli_query($con, "SELECT * FROM login WHERE id='" . $_GET['id'] . "'");
$row = mysqli_fetch_array($result);
$selected_bundles = explode(",", $row['bundle_name']); // Convert string back to array
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Studium</title>
    <link rel="shortcut icon" type="text/css" href="../img/logo1.svg">
    <link rel="stylesheet" href="adminstyles.css" />
    <!-- Boxicons CDN Link -->
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../table css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" type="text/css"
        href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css"
        integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
</head>
<style>
    .submit-button {
        background-color: #1B4965;
        color: white;
        border: none;
        padding: 10px 20px;
        font-size: 16px;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .submit-button:hover {
        background-color: #62B6CB;
    }

    .submit-button:focus {
        outline: none;
        box-shadow: 0 0 5px #62B6CB;
    }

    .submit-button,
    .back-button {
        background-color: #1B4965;
        color: white;
        border: none;
        padding: 10px 20px;
        font-size: 16px;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        margin: 5px;
        transition: background-color 0.3s ease;
    }

    .submit-button:hover,
    .back-button:hover {
        background-color: #62B6CB;
    }

    .submit-button:focus,
    .back-button:focus {
        outline: none;
        color: white;
        text-decoration: none;
        box-shadow: 0 0 5px #62B6CB;
    }
</style>

<body>
    <div class="sidebar">
        <div class="logo-details">
            <center><img src="../img/logo1.svg" width="30%"></center>
        </div>
        <ul class="nav-links">
            <li>
                <a href="../admin" class="active">
                    <i class="bx bx-grid-alt"></i>
                    <span class="links_name">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="manage topics/">
                    <i class="bx bx-box"></i>
                    <span class="links_name">Manage Topics</span>
                </a>
            </li>
            <li>
                <a href="manage question/">
                    <i class="bx bx-list-ul"></i>
                    <span class="links_name">Manage Question</span>
                </a>
            </li>
            <li>
                <a href="manage bundle/">
                    <i class="bx bx-pie-chart-alt-2"></i>
                    <span class="links_name">Manage Bundle</span>
                </a>
            </li>
            <li>
                <a href="manage group/">
                    <i class="bx bx-user"></i>
                    <span class="links_name">Manage Group</span>
                </a>
            </li>
            <li>
                <a href="manage result/">
                    <i class="bx bx-coin-stack"></i>
                    <span class="links_name">Manage Result</span>
                </a>
            </li>

            <li>
                <a href="manage feedback">
                    <i class="bx bx-heart"></i>
                    <span class="links_name">Feedback</span>
                </a>
            </li>

            <li class="log_out">
                <a href="../index.php">
                    <i class="bx bx-log-out"></i>
                    <span class="links_name">Log out</span>
                </a>
            </li>
        </ul>
    </div>

    <section class="home-section">
        <nav>
            <div class="sidebar-button">
                <i class="bx bx-menu sidebarBtn"></i>
                <span class="dashboard">Dashboard</span>
            </div>
        </nav>

        <div class="home-content">
            <div class="overview-boxes">
                <div class="box">
                    <div class="right-side">
                        <div class="box-topic">Number of Avail</div>
                        <div class="number"><?php $count->user(); ?></div>
                    </div>
                </div>

                <div class="box">
                    <div class="right-side">
                        <div class="box-topic">Total of Concept</div>
                        <div class="number"><?php $count->concept(); ?></div>
                    </div>
                </div>
                <div class="box">
                    <div class="right-side">
                        <div class="box-topic">Total of Questions</div>
                        <div class="number"><?php $count->questions(); ?></div>
                    </div>
                </div>
                <div class="box">
                    <div class="right-side">
                        <div class="box-topic">Total of Bundles</div>
                        <div class="number"><?php $count->bundles(); ?></div>
                    </div>
                </div>
            </div>

            <main class="mt-5 pt-3">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <b style="font-size: 30px;">UPDATE STUDENT DETAILS</b>
                            <div class="card">
                                <div class="modal-body">
                                    <form method="POST" action="">
                                        <div><?php if (isset($message)) { echo $message; } ?></div>
                                        <div class="form-group">
                                            <input type="hidden" name="id" class="form-control"
                                                value="<?php echo $row['id']; ?>">

                                            <b><label><i class="fa fa-id-card-o fa-2x"></i>&nbsp; Student ID:</label></b>
                                            <input type="varchar" name="studentnumber" class="form-control"
                                                value="<?php echo $row['studentnumber']; ?>">

                                            <b><label><i class="fa fa-user fa-2x"></i>&nbsp; Fullname:</label></b>
                                            <input type="varchar" name="fullname" class="form-control"
                                                value="<?php echo $row['fullname']; ?>">

                                            <b><label><i class="fa fa-book fa-2x"></i>&nbsp; Bundle:</label></b>
                                            <select name="bundle_name[]" class="form-control multiple-select"
                                                style="width: 100%; height: auto;">
                                                <?php
                                                $query = "SELECT * FROM bundle WHERE type='1'";
                                                $query_run = mysqli_query($con, $query);
                                                if (mysqli_num_rows($query_run) > 0) {
                                                    foreach ($query_run as $rowa) {
                                                        ?>
                                                        <option value="<?php echo $rowa['bundle_name']; ?>"
                                                            <?php echo in_array($rowa['bundle_name'], $selected_bundles) ? 'selected' : ''; ?>>
                                                            <?php echo $rowa['bundle_name']; ?>
                                                        </option>
                                                        <?php
                                                    }
                                                } else {
                                                    echo "No Record Found";
                                                }
                                                ?>
                                            </select>

                                            <b><label><i class="fa fa-users fa-2x"></i>&nbsp; Group:</label></b>
                                            <input type="text" name="groupname" class="form-control"
                                                value="<?php echo $row['groupname']; ?>">

                                            <b><label><i class="fa fa-calendar fa-2x"></i>&nbsp; Date Expired:</label></b>
                                            <input type="datetime-local" name="dateexpired" class="form-control"
                                                value="<?php echo date('Y-m-d\TH:i', strtotime($row['dateexpired'])); ?>">
                                                <label style="font-size: 10px;  font-style: italic;  font-weight: bold;">1970-01-01 08:00 AM is considered as NULL</label></br>

                                            <b><label><i class="fa fa-calendar fa-2x"></i>&nbsp; Date Enrolled:</label></b>
                                            <input type="datetime-local" name="dateenrolled" class="form-control"
                                                value="<?php echo date('Y-m-d\TH:i', strtotime($row['dateenrolled'])); ?>">

                                            <b><label><i class="fa fa-clock-o fa-2x"></i>&nbsp; Activation Button:</label></b>
                                            <input type="number" name="subMonth" class="form-control"
                                                value="<?php echo $row['subMonth']; ?>"  placeholder="NULL">

                                            <b><label><i class="fa fa-envelope fa-2x"></i>&nbsp; Gmail:</label></b>
                                            <input type="email" name="email" class="form-control"
                                                value="<?php echo $row['email']; ?>">

                                            <b><label><i class="fa fa-lock fa-2x"></i>&nbsp; Password:</label></b>
                                            <input type="text" name="password" class="form-control"
                                                value="<?php echo $row['password']; ?>">

                                            <div id="errorlabel"></div><br>
                                            <div class="add">
                                                <center>
                                                    <button id="btn-login" type="submit" name="submit"
                                                        class="submit-button"
                                                        onclick="return checksubmit()">Submit</button>
                                                    <a href="index.php" class="back-button">Back</a>
                                                </center>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <script>
                function checksubmit() {
                    return confirm('Are You Sure Want to UPDATE?');
                }
            </script>
            <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
            <script>
                $(".multiple-select").select2({});
            </script>
            <script src="https://code.jquery.com/jquery-1.12.4.min.js"
                integrity="sha384-nvAa0+6Qg9clwYCGGPpDQLVpLNn0fRaROjHqs13t4Ggj3Ez50XnGQqc/r8MhnRDZ"
                crossorigin="anonymous"></script>
            <script>
                window.jQuery || document.write('<script src="../../assets/js/vendor/jquery.min.js"><\/script>')
            </script>
            <script src="../../dist/js/bootstrap.min.js"></script>
            <script src="../../assets/js/vendor/holder.min.js"></script>
            <script src="../../assets/js/ie10-viewport-bug-workaround.js"></script>
            <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
            <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
</body>

</html>