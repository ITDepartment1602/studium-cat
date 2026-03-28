<?php

include '../../config.php';
session_start();
$user_id = $_SESSION['user_id'];
?>
<?php
include('my note/conn/conn.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $id = $_GET["id"];
  $noteTitle = $_POST["note_title"];
  $noteContent = $_POST["note_content"];
  $dateTime = date("Y-m-d H:i:s");

  try {
    $stmt = $conn->prepare("INSERT INTO tbl_notes (login_id,note_title, note, date_time) VALUES (:id,:note_title, :note, :date_time)");
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':note_title', $noteTitle);
    $stmt->bindParam(':note', $noteContent);
    $stmt->bindParam(':date_time', $dateTime);
    $stmt->execute();
  } catch (PDOException $e) {
  }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../ty/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../ty/css/dataTables.bootstrap5.min.css" />
  <link rel="stylesheet" href="../ty/css/style.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="../pchart/pchart.css">
  <link rel="stylesheet" href="../pricing/moda.css">
  <link rel="stylesheet" href="../pricing/exam.css">
  <title>studium</title>
  <link rel="shortcut icon" type="text/css" href="../../img/logo1.svg">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css"
    integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
</head>
<style>
  /* Custom CSS */
  .main-panel,
  .card {
    margin: auto;
    height: 90vh;
    overflow-y: auto;
  }

  .note-content {
    max-height: 20em;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  @media (max-width:768px) {
    .modal-content {
      width: 100%;
    }
  }

  .nurse a:hover {
    color: black;
    text-decoration: none;
  }

  .product a:hover {
    color: red;
    text-decoration: none;
  }
</style>

<body>

  <!-- top navigation bar -->
  <!-- top navigation bar -->
  <nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background-color:#1B4965;">
    <div class="container-fluid">
      <button class="navbar-toggler" style="color: #fff; font-size: 10px" type="button" data-bs-toggle="offcanvas"
        data-bs-target="#sidebar" aria-controls="offcanvasExample">
        <span class="navbar-toggler-icon" data-bs-target="#sidebar"></span>
      </button>
      <?php
      $select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$user_id'") or die('query failed');
      if (mysqli_num_rows($select) > 0) {
        $fetch = mysqli_fetch_assoc($select);
      }
      date_default_timezone_set('Asia/Manila');
      $dateExpired = strtotime($fetch['dateexpired']);
      $today = strtotime(date('Y-m-d H:i:s'));
      $diff = $dateExpired - $today;
      $daysLeft = floor($diff / (60 * 60 * 24));

      $interval = '';
      if ($daysLeft == 7) {
        $interval = '1 week';
      } else if ($daysLeft > 1) {
        $interval = $daysLeft . ' days';
      } else if ($daysLeft == 1) {
        $interval = '1 day';
      } else if ($daysLeft < 0) {
        header('Location: ../../logout.php');
        exit;
      }

      if ($daysLeft == 0 && $diff > 0) {
        echo '<span class="text-white notif1" style="color: #fff;"> <i class="fa fa-bell"></i> Your account is expiring today.</span>';
      } else if ($daysLeft <= 7 && $daysLeft > 0) {
        echo '<span class="text-white notif2" style="color: #fff;"> <i class="fa fa-bell"> </i> ' . $interval . ' remaining until expiration.</span>';
      }
      ?>
      <a class="navbar-brand me-auto ms-lg-0 ms-3 text-uppercase fw-bold" style="font-size: 20px"></a>
      <a class="nav-link text-white" href="../../logout.php">

        <i class="fa fa-sign-out logouts" style="color: #fff; " aria-hidden="true"></i>
      </a>

      <style>
        .logouts {

          font-size: 13px;
        }

        .notif1 {

          font-size: 10px;
        }

        .notif2 {

          font-size: 11px;
        }

        @media (min-width: 768px) {
          .logouts {
            font-size: 32px;
          }

          .notif1 {

            font-size: 14px;
          }

          .notif2 {

            font-size: 14px;
          }
        }
      </style>
    </div>
  </nav>
  <!-- top navigation bar -->

  <!-- offcanvas -->
  <div class="offcanvas offcanvas-start sidebar-nav ml-6" tabindex="-1" id="sidebar">
    <div class="offcanvas-body p-0" style=" background-color: #62B6CB;">
      <nav class="navbar-dark" style="width: 100%; ">
        <ul class="navbar-nav" style=" background-color: #62B6CB; padding-bottom: 80%;">

          <!---<form action="" method=" POST">
            <div class="col-md-auto">
              <input type="text" name="search" class='form-control' placeholder="Search By Name" value="">
  
  
              </form>--->
          <?php
          $select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$user_id'") or die('query failed');
          if (mysqli_num_rows($select) > 0) {
            $fetch = mysqli_fetch_assoc($select);
          }
          ?>



          <li style="width: 100%; background-color: #62B6CB;">
            <table id="table" style="margin-top: 20px; width: 100%; background-color: #1B4965;">
              <tr>
                <td class="nav-link px-1">
                </td>
                <img src="../../img/logo2.svg" style="width:100px; margin-left: 50px; margin-bottom: 50px;">

              </tr>
              <tr>
                <td class="nav-link px-1">
                </td>
                <div>
                  <p style="margin-top: -10%; color: black; font-weight: normal; text-align: center;">
                    Hello
                    <span style="font-weight: bold; ">
                      <?php echo explode(' ', trim($fetch['fullname']))[0]; ?>!
                    </span>

                  </p>

                </div>
              </tr>

              <tr>
                <td class="nav-link px-1">
                </td>
                <td>
                  <a href="index.php?bundle_name=<?php echo $fetch['bundle_name']; ?>" id="myVideo" class="nav-link">
                    <p style="font-size: 14px;"> <i class="bi bi-house" style="font-size: 17px;"></i> Home ></p>
                  </a>
                </td>
              </tr>

              <tr>
                <td class="nav-link px-1">
                </td>
                <td>
                  <a href="profile.php" id="myVideo" class="nav-link">
                    <p style="font-size: 14px;"><i class="bi bi-person-square" style="font-size: 17px;"></i> View
                      Profile ></p>
                  </a>
                </td>
              </tr>
              <tr>
                <td class="nav-link px-1">
                </td>
                <?php
                $select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$user_id'") or die('query failed');
                if (mysqli_num_rows($select) > 0) {
                  $fetch = mysqli_fetch_assoc($select);
                }
                ?>
                <td>
                  <a href="note.php?id=<?php echo $fetch['id'] ?>" id="myVideo" class="nav-link">
                    <p style="font-size: 14px;"><i class="bi bi-journal" style="font-size: 17px;"></i> My Notes ></p>
                  </a>
                </td>
              </tr>
              <tr>
                <td class="nav-link px-1">
                </td>
                <td>
                  <a href="../../img/userguide.mp4" target="_blank" rel="noopener noreferrer" id="myVideo"
                    class="nav-link">
                    <p style="font-size: 14px;"><i class="bi bi-question-circle" style="font-size: 17px;"></i></i> User
                      Guide ></p>
                  </a>
                </td>
              </tr>


              <tr>
                <td class="nav-link px-1">
                </td>
                <td>
                  <a href="subscription.php" id="myVideo" class="nav-link">
                    <p style="font-size: 14px;"><i class="bi bi-calendar-check" style="font-size: 17px;"></i>
                      Subscription ></p>
                  </a>
                </td>
              </tr>

              <tr>
                <td class="nav-link px-1">
                </td>
                <td>
                  <a href="package.php" id="myVideo" class="nav-link">
                    <p style="font-size: 14px;"><i class="bi bi-box-seam" style="font-size: 17px;"></i> Package >
                    </p>
                  </a>
                </td>
              </tr>




              <tr>
                <td class="nav-link px-1">
                </td>
                <td>
                  <a href="https://www.facebook.com/NCLEX.Amplified.Official" target="_blank" id="myVideo"
                    class="nav-link">
                    <p style="font-size: 14px;"><i class="bi bi-telephone" style="font-size: 17px;"></i> Contact Us
                      >
                    </p>
                  </a>
                </td>
              </tr>
            </table>

          </li>
          <tr>
            <td class="nav-link px-1">
            </td>

          </tr>
          <div
            style="position: absolute; bottom: 0; left: 0; right: 0; text-align: center; padding-bottom: 20px; background-color: #62B6CB;">
            <li style="background-color: #62B6CB; margin-top: -8px;">
              <hr class="dropdown-divider bg-dark" style="" />
            </li>
            <?php
            $select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$user_id'") or die('query failed');
            if (mysqli_num_rows($select) > 0) {
              $fetch = mysqli_fetch_assoc($select);
            }
            ?>
            <p style="color: black; font-weight: bold; font-size: 14px; text-align: center; ">
              Expiration
              Date</p>
            <p style="color: black; font-size: 14px; text-align: center; margin-top: -20px;">
              <?php echo date('F d, Y', strtotime($fetch['dateexpired'])); ?><br>
              <?php echo date('h:i A', strtotime($fetch['dateexpired'])); ?>
            </p>
            <a href="https://www.facebook.com/NCLEX.Amplified.Official" target="_blank">
              Upgrade Now!
            </a>
          </div>
      </nav>
    </div>
  </div>

  <main class="mt-5 pt-4">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12 mb-3">
          <div class="card-body">



            <div class="row">

              <!-- Add Note -->
              <div class="col-md-4">
                <div class="card">
                  <div class="card-header" style="color: #FFF; background: #1B4965;">
                    Add Note
                  </div>
                  <div class="card-body">
                    <form method="post">
                      <div class="form-group">
                        <label for="noteTitle" style="color: #0A2558;">Title</label>
                        <input type="text" class="form-control" id="noteTitle" name="note_title" placeholder="Title">
                        <small id="emailHelp" class="form-text text-muted">Title of your note</small>
                        <input type="hidden" name="login_id" value=" ">
                      </div>
                      <div class="form-group">
                        <label for="note" style="color: #0A2558;">Note</label>
                        <textarea class="form-control" id="note" name="note_content" rows="20"></textarea>
                      </div>
                      <button type="submit" class="btn btn-secondary"
                        style="float: right; background-color:#1B4965;">Submit</button>
                    </form>
                  </div>
                </div>
              </div>


              <!--  Update and Delete Notes -->
              <div class="col-md-8">
                <div class="card">
                  <div class="card-header" style="color: #FFF; background: #1B4965;">
                    Notes Details
                  </div>

                  <div class="card-body">
                    <div class="data-item">
                      <ul class="list-group">

                        <?php
                        include('my note/conn/conn.php');
                        $id = $_GET['id'];
                        $stmt = $conn->prepare("SELECT * FROM `tbl_notes` where login_id = $id");
                        $stmt->execute();

                        $result = $stmt->fetchAll();

                        foreach ($result as $row) {
                          $login_id = $row['login_id'];
                          $noteID = $row['tbl_notes_id'];
                          $noteTitle = $row['note_title'];
                          $noteContent = $row['note'];
                          $noteDateTime = $row['date_time'];

                          // Convert the date_time value to a formatted date and time string
                          $formattedDateTime = date('F j, Y H:i A', strtotime($noteDateTime));
                          ?>
                          <li class="list-group-item mt-2" style="color: #000; cursor: pointer;">
                            <div class="btn-group float-right">
                              <a
                                href="my note/endpoint/update_note.php?edit=<?php echo $noteID ?>&id=<?php echo $login_id ?>">
                                <button type="button" class="btn btn-sm btn-light" title="Edit">
                                  <i class="fa fa-pencil"></i>
                                </button>
                              </a>

                              <a
                                href="my note/endpoint/delete_note.php?id=<?php echo $noteID ?>&login_id=<?php echo $login_id ?>">
                                <button type="button" class="btn btn-sm btn-light" title="Delete">
                                  <i class="fa fa-trash"></i>
                                </button>
                              </a>
                            </div>
                            <h5 style="text-transform:uppercase;"><b><?php echo $noteTitle ?></b></h5>
                            <p class="note-content"><?php echo $noteContent ?></p>
                            <small class="block text-muted text-info">Created: <i class="fa fa-clock-o text-info"></i>
                              <?php echo $formattedDateTime ?></small>
                            <div style="display: flex; justify-content: end;"> <a
                                style="color: #1B4965; text-decoration: underline;"
                                onclick="showFullNote('<?php echo addslashes($noteTitle); ?>', '<?php echo addslashes($noteContent); ?>')">View
                                Note</a></div>

                          </li>

                          <script>
                            function showFullNote(title, content) {
                              Swal.fire({
                                title: title,
                                html: `<p style="text-align: left;">${content}</p>`,
                                showCloseButton: false,
                                showCancelButton: false,
                                showConfirmButton: false,

                                customClass: {
                                  popup: 'swal2-note-popup'
                                }
                              });
                            }
                          </script>
                          <?php
                        }
                        ?>

                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>


          </div>
        </div>
      </div>
    </div>
  </main>

  <br>

  <div class="copy"
    style="background-color: #1B4965; height: 30px; position: fixed; bottom: 0; left: 0; right: 0; text-align: center;">
    <center><span style="color:white;">© Studium 2025, All Right Reserved.</span></center>
  </div>

  <script src="../ty/./js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.0.2/dist/chart.min.js"></script>
  <script src="../ty/./js/jquery-3.5.1.js"></script>
  <script src="../ty/./js/jquery.dataTables.min.js"></script>
  <script src="../ty/./js/dataTables.bootstrap5.min.js"></script>
  <script src="../ty/./js/script.js"></script>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js" charset="utf-8"></script>

  <!--=============== MAIN JS ===============-->
  <script src="assets/js/main.js"></script>

  <!--=============== Scroll ===============-->
  <script>
    document.querySelector("#btm").addEventListener("click", () => {
      window.scrollTo(0, document.body.scrollHeight);
    })
  </script>

  <!--=============== GRAPH ===============-->
  <script>
    $(function () {
      $('[data-toggle="tooltip"]').tooltip({ trigger: 'manual' }).tooltip('show');
    });

    // $( window ).scroll(function() {   
    // if($( window ).scrollTop() > 10){  // scroll down abit and get the action   
    $(".progress-bar").each(function () {
      each_bar_width = $(this).attr('aria-valuenow');
      $(this).width(each_bar_width + 'px');
    });

    //  }  
    // });
  </script>

  <!--=============== Pchart ===============-->
  <script src="../pchart/plugins/jquery-2.2.4.min.js"></script>
  <script src="../pchart/plugins/jquery.appear.min.js"></script>
  <script src="../pchart/plugins/jquery.easypiechart.min.js"></script>
  <script>
    'use strict';

    var $window = $(window);

    function run() {
      var fName = arguments[0],
        aArgs = Array.prototype.slice.call(arguments, 1);
      try {
        fName.apply(window, aArgs);
      } catch (err) {

      }
    };

    /* ===================== chart ============================= */
    function _chart() {
      $('.b-skills').appear(function () {
        setTimeout(function () {
          $('.chart').easyPieChart({
            easing: 'easeOutElastic',
            delay: 3000,
            barColor: '#369670',
            trackColor: '#E5E6E6',
            scaleColor: false,
            lineWidth: 11,
            trackWidth: 11,
            size: 250,
            lineCap: 'round',
            onStep: function (from, to, percent) {
              this.el.children[0].innerHTML = Math.round(percent);
            }
          });
        }, 150);
      });
    };


    $(document).ready(function () {
      run(_chart);
    });
  </script>

  <script>
    /* ===================== drag ============================= */
    const wrapper = document.querySelector(".wrappera"),
      header = wrapper.querySelector("header");
    function onDrag({ movementX, movementY }) {
      let getStyle = window.getComputedStyle(wrapper);
      let leftVal = parseInt(getStyle.left);
      let topVal = parseInt(getStyle.top);
      wrapper.style.left = `${leftVal + movementX}px`;
      wrapper.style.top = `${topVal + movementY}px`;
    }
    header.addEventListener("mousedown", () => {
      header.classList.add("active");
      header.addEventListener("mousemove", onDrag);
    });
    document.addEventListener("mouseup", () => {
      header.classList.remove("active");
      header.removeEventListener("mousemove", onDrag);
    });
  </script>

  <script>
    const wrappera = document.querySelector(".wrapper"),
      headera = wrappera.querySelector(".modal-header");
    function onDraga({ movementX, movementY }) {
      let getStyle = window.getComputedStyle(wrappera);
      let leftVal = parseInt(getStyle.left);
      let topVal = parseInt(getStyle.top);
      wrappera.style.left = `${leftVal + movementX}px`;
      wrappera.style.top = `${topVal + movementY}px`;
    }
    headera.addEventListener("mousedown", () => {
      headera.classList.add("active");
      headera.addEventListener("mousemove", onDraga);
    });
    document.addEventListener("mouseup", () => {
      headera.classList.remove("active");
      headera.removeEventListener("mousemove", onDraga);
    });
  </script>

  <script>
    /* ===================== modal ============================= */
    // Get the modal
    var modal = document.getElementById("myModal");

    // Get the button that opens the modal
    var btn = document.getElementById("myBtn");

    // Get the <span> element that closes the modal
    var span = document.getElementsByClassName("close")[0];

    // When the user clicks the button, open the modal 
    btn.onclick = function () {
      modal.style.display = "block";
    }

    // When the user clicks on <span> (x), close the modal
    span.onclick = function () {
      modal.style.display = "none";
    }
  </script>
</body>

</html>