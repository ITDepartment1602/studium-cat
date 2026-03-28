<!DOCTYPE html>
<!-- Website - www.codingnepalweb.com -->
<html lang="en" dir="ltr">

<head>
  <meta charset="UTF-8" />
  <title>Studium Admin</title>
  <link rel="shortcut icon" type="text/css" href="../../img/logo1.svg">
  <link rel="stylesheet" href="../adminstyles.css" />
  <!-- Boxicons CDN Link -->
  <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <link rel="stylesheet" href="../../table css/dataTables.bootstrap5.min.css" />

  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css"
    integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
  <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
</head>
<style type="text/css">
  /* Full-width input fields */
  input[type=text],
  input[type=file] {
    width: 100%;
    padding: 12px 20px;
    margin: 8px 0;
    display: inline-block;
    border: 1px solid #ccc;
    box-sizing: border-box;
  }

  select {
    width: 100%;
    padding: 12px 20px;
    margin: 8px 0;
    display: inline-block;
    border: 1px solid #ccc;
    box-sizing: border-box;
  }

  textarea {
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
    width: 50%;
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

<body>
  <div class="sidebar">
    <div class="logo-details">
      <center><img src="../../img/logo1.svg" width="30%"></center>
    </div>
    <ul class="nav-links">
      <li>
        <a href="../">
          <i class="bx bx-grid-alt"></i>
          <span class="links_name">Dashboard</span>
        </a>
      </li>
      <li>
        <a href="../manage topics">
          <i class="bx bx-box"></i>
          <span class="links_name">Manage Topics</span>
        </a>
      </li>
      <li>
        <a href="#" class="active">
          <i class="bx bx-list-ul"></i>
          <span class="links_name">Manage Question</span>
        </a>
      </li>
      <li>
        <a href="../manage bundle">
          <i class="bx bx-pie-chart-alt-2"></i>
          <span class="links_name">Manage Bundle</span>
        </a>
      </li>
      <li>
        <a href="../manage group">
          <i class="bx bx-user"></i>
          <span class="links_name">Manage Group</span>
        </a>
      </li>
      <li>
        <a href="../manage result">
          <i class="bx bx-coin-stack"></i>
          <span class="links_name">Manage Result</span>
        </a>
      </li>

      <li>
        <a href="../manage feedback">
          <i class="bx bx-heart"></i>
          <span class="links_name">Feedback</span>
        </a>
      </li>

      <li class="log_out">
        <a href="../../index.php">
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
      <div class="sales-boxes">
        <div class="recent-sales box">
          <div class="title">Question List</div>
          <div class="title" style="float:right; margin-top: -40px;"><i class='bx bx-plus-medical'
              style="font-size: 1.5rem;" onclick="document.getElementById('id01').style.display='block'"></i></div>

          <div id="id01" class="modal">
            <form class="modal-content animate" action="actionsata.php" method="post" enctype="multipart/form-data">
              <div class="imgcontainer">
                <span onclick="document.getElementById('id01').style.display='none'" class="close"
                  title="Close Modal">&times;</span>
              </div>

              <div class="container">
                <label><b>Topic:</b></label>
                <input type="hidden" name="topic[]" value="NARC Intermediate and Advance QBanks">

                <label><b>Concept:</b></label>
                <select name="concept" class="form-control" required>
                  <option disabled selected>Select concept..</option>
                  <option value="Adult Health">Adult Health</option>
                  <option value="Child Health">Child Health</option>
                  <option value="Critical Care">Critical Care</option>
                  <option value="Fundamentals">Fundamentals</option>
                  <option value="Leadership & Management">Leadership & Management</option>
                  <option value="Maternal & Newborn Health">Maternal & Newborn Health</option>
                  <option value="Mental Health">Mental Health</option>
                  <option value="Pharmacology">Pharmacology</option>
                </select>


                <input type="hidden" class="form-control" placeholder="Input Question Number" name="qnumber" value="1">

                <label><b>Question:</b></label>
                <input type="text" class="form-control" placeholder="Input Question" name="question" required>

                <label><b>Choices:</b></label>
                <div id="options-container">
                  <div class="option-input" style="display: flex; align-items: center;">
                    <input type="text" class="form-control" placeholder="Input Choices" name="options[]" required
                      style="flex: 1; width: 100%;">
                    <button type="button" class="remove-option" onclick="removeOption(this)"
                      style="background-color: red; border: none; color: white; width: 20px; height: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                      x
                    </button>
                  </div>
                </div>
                <button type="button" class="add-option" onclick="addOption()"
                  style="background-color: #28a745; color: white; padding: 10px; margin-top: 10px; border: none; cursor: pointer;">Add
                  Choices</button>

                <label><b>Correct Answer (ex: [0, 2, 3]):</b></label>
                <label><b>A = 0 <br> B = 1 <br> C = 2 <br> D = 3 <br> E = 4 <br> F - 5 </b></label>
                <input type="text" class="form-control" placeholder="Input indices of correct answers" value="[]"
                  name="correctans" required>

                <label><b>Rationale:</b></label>
                <textarea name="rationale" required></textarea>

                <label><b>NARC Additional Notes:</b></label>
                <textarea name="narcan" required></textarea>

                <label><b>DLevel (ex: Hard level):</b></label>
                <input type="text" class="form-control" placeholder="Input D Level" name="dlevel" required>

                <label><b>Client Needs:</b></label>
                <input type="text" class="form-control" placeholder="Input CNC" name="cnc" required>

                <label><b>System:</b></label>
                <input type="text" class="form-control" placeholder="Input System" name="system" required>

                <button type="submit" name="submit">Submit</button>
              </div>

              <div class="container" style="background-color:#f1f1f1">
                <button type="button" onclick="document.getElementById('id01').style.display='none'"
                  class="cancelbtn">Cancel</button>
              </div>
            </form>
          </div>

          <script>
            function addOption() {
              const container = document.getElementById('options-container');
              const newOption = document.createElement('div');
              newOption.classList.add('option-input');
              newOption.style.display = 'flex';
              newOption.style.alignItems = 'center';
              newOption.innerHTML = `
      <input type="text" class="form-control" placeholder="Input Option" name="options[]" required style="flex: 1; width: 100%;">
      <button type="button" class="remove-option" onclick="removeOption(this)" style="background-color: red; border: none; color: white; width: 20px; height: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center;">
        x
      </button>
    `;
              container.appendChild(newOption);
            }

            function removeOption(button) {
              const optionInput = button.parentNode;
              optionInput.parentNode.removeChild(optionInput);
            }
          </script>

          <table class="table table-striped data-table">
            <thead>
              <tr>
                <th>Question</th>
                <th>Concept</th>

                <th>Question</th>
                <th>Options</th>

                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
              include('../../config.php');

              $query = "SELECT * FROM `question` WHERE `type` IS NOT NULL AND `type` = 'SATA'";
              $data = mysqli_query($con, $query);

              while ($rows = mysqli_fetch_array($data)) {
                // Decode the options and correct answers
                $options = json_decode($rows['options']); // Assuming choices are stored as JSON
                $correctAnswers = json_decode($rows['correctans']);

                ?>
                <tr>
                  <td><?php echo $rows['id']; ?></td>
                  <td><?php echo $rows['topics1']; ?></td>

                  <td><?php echo $rows['question']; ?></td>
                  <td>
                    <?php foreach ($options as $index => $option): ?>
                      <div>
                        <input disabled type="checkbox" name="options[<?php echo $index; ?>]" value="<?php echo $option; ?>"
                          <?php echo in_array($index, $correctAnswers) ? 'checked' : ''; ?>>
                        <?php echo $option; ?>
                      </div>
                    <?php endforeach; ?>
                  </td>

                  <td>
                    <a href="#" style="color: red;" onclick="confirmDelete('<?php echo $rows['id']; ?>')">
                      Delete<i class='bx bx-trash'></i>
                    </a>
                  </td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
  <script>
    function confirmDelete(id) {
      swal({
        title: "Delete Confirmation",
        text: "Are you sure you want to delete this question?",
        icon: "warning",
        buttons: true,
        dangerMode: true,
      }).then((willDelete) => {
        if (willDelete) {
          window.location.href = `actionsata.php?id=${id}&action=delete`;
        }
      });
    }
  </script>
  <script>
    let sidebar = document.querySelector(".sidebar");
    let sidebarBtn = document.querySelector(".sidebarBtn");
    sidebarBtn.onclick = function () {
      sidebar.classList.toggle("active");
      if (sidebar.classList.contains("active")) {
        sidebarBtn.classList.replace("bx-menu", "bx-menu-alt-right");
      } else sidebarBtn.classList.replace("bx-menu-alt-right", "bx-menu");
    };
  </script>

  <script src="../.././table js/jquery-3.5.1.js"></script>
  <script src="../.././table js/jquery.dataTables.min.js"></script>
  <script src="../.././table js/dataTables.bootstrap5.min.js"></script>
  <script src="../.././table js/script.js"></script>
</body>

</html>