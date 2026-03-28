<?php
include("count.php");
$count = new count;
$userd = $count->show_users();

?>

<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../'); // Redirect to login page
    exit();
}

?>

<!DOCTYPE html>
<!-- Website - www.codingnepalweb.com -->
<html lang="en" dir="ltr">

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<style type="text/css">
    /* Full-width input fields */
    input[type=text],
    input[type=number],
    input[type=email] {
        width: 100%;
        padding: 12px 20px;
        margin: 8px 0;
        display: inline-block;
        border: 1px solid #ccc;
        box-sizing: border-box;
    }

    /* Set a style for all buttons */
    button {
        background-color: #04AA6D;
        color: white;
        padding: 14px 20px;
        margin: 8px 0;
        border: none;
        cursor: pointer;
        width: 100%;
    }

    button:hover {
        opacity: 0.8;
    }

    /* Extra styles for the cancel button */
    .cancelbtn {
        width: auto;
        padding: 10px 18px;
        background-color: #f44336;
    }

    /* Center the image and position the close button */
    .imgcontainer {
        text-align: center;
        margin: 24px 0 12px 0;
        position: relative;
    }

    .container {
        padding: 16px;
    }


    /* The Modal (background) */
    .modal {
        display: none;
        /* Hidden by default */
        position: fixed;
        /* Stay in place */
        z-index: 1;
        /* Sit on top */
        left: 0;
        top: 0;
        width: 100%;
        /* Full width */
        height: 100%;
        /* Full height */
        overflow: auto;
        /* Enable scroll if needed */
        background-color: rgb(0, 0, 0);
        /* Fallback color */
        background-color: rgba(0, 0, 0, 0.4);
        /* Black w/ opacity */
        padding-top: 60px;
    }

    /* Modal Content/Box */
    .modal-content {
        background-color: #fefefe;
        margin: 5% auto 15% auto;
        /* 5% from the top, 15% from the bottom and centered */
        border: 1px solid #888;
        width: 30%;
        /* Could be more or less, depending on screen size */
    }

    /* The Close Button (x) */
    .close {
        position: absolute;
        right: 25px;
        top: 0;
        color: #000;
        font-size: 35px;
        font-weight: bold;
    }

    .close:hover,
    .close:focus {
        color: red;
        cursor: pointer;
    }

    /* Add Zoom Animation */
    .animate {
        -webkit-animation: animatezoom 0.6s;
        animation: animatezoom 0.6s
    }

    @-webkit-keyframes animatezoom {
        from {
            -webkit-transform: scale(0)
        }

        to {
            -webkit-transform: scale(1)
        }
    }

    @keyframes animatezoom {
        from {
            transform: scale(0)
        }

        to {
            transform: scale(1)
        }
    }

    /* Change styles for span and cancel button on extra small screens */
    @media screen and (max-width: 300px) {
        .cancelbtn {
            width: 100%;
        }
    }
</style>
<!--==================== Side Bar ====================-->

<body>
    <div class="sidebar">
        <div class="logo-details">
            <center><img src="../img/logo1.svg" width="30%"></center>
        </div>
        <ul class="nav-links">
            <li>
                <a href="index.php" class="active">
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

    <!--==================== Counts ====================-->
    <section class="home-section">
        <nav>
            <div class="sidebar-button">
                <i class="bx bx-menu sidebarBtn"></i>
                <span class="dashboard">Expired Subscriptions</span>
            </div>
        </nav>

        <div class="home-content">





            <!--==================== Modal Start ====================-->
            <div class="sales-boxes">
                <div class="recent-sales box">
                    <div class="title" style="margin-bottom: 20px;">Expired Subscriptions List</div>
                  

                  
                    <!--==================== Modal End ====================-->

                    <table class="table table-striped data-table">
                        <thead>
                            <tr>
                                <th>Student Number</th>
                                <th>Full Name</th>
                                <th>Bundle</th>
                                <th>Group</th>
                                <th>Date Expired</th>

                                <th>Gmail</th>
                                <th>Password</th>
                                <th>Update</th>
                                <th>Delete</th>

                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            include('../config.php');

                            // Set the timezone to the Philippines
                            date_default_timezone_set('Asia/Manila');

                            // Get the current date
                            $current_date = date('Y-m-d H:i:s'); // Adjust the date format if necessary
                            
                            // Modify the query to exclude expired users
                            $query = "SELECT * FROM `login` WHERE status = 'user' AND groupname != 'Admin' AND dateexpired < '$current_date'";
                            $data = mysqli_query($con, $query);

                            while ($rows = mysqli_fetch_array($data)) {
                                ?>
                                <tr>
                                    <td><?php echo $rows['studentnumber']; ?></td>
                                    <td><?php echo $rows['fullname']; ?></td>
                                    <td><?php echo str_replace('Packege', 'Package', $rows['bundle_name']); ?></td>
                                    <td><?php echo $rows['groupname']; ?></td>
                                    <td><?php echo $rows['dateexpired']; ?></td>

                                    <td><?php echo $rows['email']; ?></td>
                                    <td><?php echo $rows['password']; ?></td>
                                    <td><a href="#"
                                            onclick="confirmAction('update', '<?php echo $rows['fullname']; ?>', '<?php echo $rows['id']; ?>')"
                                            class='no-gutters text-primary'>Update<i class='fa fa-pencil-square'
                                                aria-hidden='true'></i></a>
                                    </td>
                                    <td><a href="#"
                                            onclick="confirmAction('delete', '<?php echo $rows['fullname']; ?>', '<?php echo $rows['id']; ?>')"
                                            class='no-gutters text-danger'>Delete<i class='fa fa-trash-o'
                                                aria-hidden='true'></i></a></td>
                                   
                                        <?php

                            }
                            ?>
                              
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <script>

        function confirmAction(action, name, id) {
            let actionText = action.charAt(0).toUpperCase() + action.slice(1);
            let message = `Are you sure you want to ${action} ${name}?`;

            Swal.fire({
                title: actionText + " Confirmation",
                text: message,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'Cancel',
                customClass: {
                    confirmButton: 'swal-confirm-button', // Custom class for confirm button
                },
                dangerMode: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    if (action === 'delete') {
                        window.location.href = `admin_delete.php?id=${id}`;
                    } else if (action === 'update') {
                        window.location.href = `admin_update.php?id=${id}`;
                    } else if (action === 'enable' || action === 'disable') {
                        const typeValue = action === 'enable' ? 0 : 1; // Enable sets to 0, Disable sets to 1
                        console.log(`Redirecting to type.php?id=${id}&type=${typeValue}`); // Debugging line
                        window.location.href = `type.php?id=${id}&type=${typeValue}`;
                    }
                }
            });
        }

        // Add this CSS to style the button
        const style = document.createElement('style');
        style.innerHTML = `
    .swal-confirm-button {
        background-color: #1B4965 !important; /* Button background color */
        color: white !important; /* Button text color */
    }
`;
        document.head.appendChild(style);
        let sidebar = document.querySelector(".sidebar");
        let sidebarBtn = document.querySelector(".sidebarBtn");
        sidebarBtn.onclick = function () {
            sidebar.classList.toggle("active");
            if (sidebar.classList.contains("active")) {
                sidebarBtn.classList.replace("bx-menu", "bx-menu-alt-right");
            } else sidebarBtn.classList.replace("bx-menu-alt-right", "bx-menu");
        };
    </script>
    <script src=".././table js/jquery-3.5.1.js"></script>
    <script src=".././table js/jquery.dataTables.min.js"></script>
    <script src=".././table js/dataTables.bootstrap5.min.js"></script>
    <script src=".././table js/script.js"></script>
</body>

</html>