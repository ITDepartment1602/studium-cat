<?php 

include '../../config.php';
session_start();
$user_id = $_SESSION['user_id'];
 ?>

 <!DOCTYPE html>
<html lang="en">
  <head>
    <link rel="shortcut icon" type="text/css" href="../../img/mylogo3.png">
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="ty/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css"/>
    <link rel="stylesheet" href="ty/css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="ty/css/style.css" />


    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">




    <title>NCLEX Amplified</title>
  </head>
  <style>
.button {
  border: none;
  color: white;
  padding: 10px 32px;
  text-align: center;
  text-decoration: none;
  display: inline-block;
  font-size: 16px;
  margin: 4px 2px;
  cursor: pointer;
  background-color: #2076fe;
}
.button:hover {
  background-color: #0253a1;
}
  </style>
  <body>
    <!-- top navigation bar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background-color:#2d3246;">
      <div class="container-fluid">
        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="offcanvas"
          data-bs-target="#sidebar"
          aria-controls="offcanvasExample"
        >
          <span class="navbar-toggler-icon" data-bs-target="#sidebar"></span>
        </button>
        <a class="navbar-brand me-auto ms-lg-0 ms-3 text-uppercase fw-bold" href="#"><?php echo $_GET['course_name']; ?></a>
        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#topNavBar"
          aria-controls="topNavBar"
          aria-expanded="false"
          aria-label="Toggle navigation"
        >
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="topNavBar">
          <form class="d-flex ms-auto my-3 my-lg-0">
            <div class="input-group">


            </div>
          </form>
          <ul class="navbar-nav">
            <li class="nav-item dropdown">
              <a
                class="nav-link dropdown-toggle ms-2"
                href="#"
                role="button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
              >
                <i class="bi bi-person-fill"></i>
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <?php
         $select = mysqli_query($con, "SELECT * FROM `packagelist`") or die('query failed');
         if(mysqli_num_rows($select) > 0){
            $fetch = mysqli_fetch_assoc($select);
         }
         ?>
                <li><a class="dropdown-item" href="display_video_courses?bundle_name=<?php echo $fetch['bundle_name']; ?>">Lectures</a></li>
                <li><a class="dropdown-item" href="../../index">Home</a></li>
                <li>
                  <a class="dropdown-item" href="../../login">Logout</a>
                </li>
              </ul>
            </li>
          </ul>
        </div>
      </div>
    </nav>
    <!-- top navigation bar -->
    <!-- offcanvas -->
    <div
      class="offcanvas offcanvas-start sidebar-nav" style="background-color:#2d3246;" tabindex="-1" id="sidebar">
      <div class="offcanvas-body p-0">
        <nav class="navbar-dark">
          <ul class="navbar-nav">
            <li>
              <div class="text-muted small fw-bold text-uppercase px-3">
                SET OF LECTURE
              </div>
            </li>
            <li>

<?php   

    $course_name=$_GET['course_name'];

    $sql="select * from videos where course_name='$course_name'";
    $result=mysqli_query($con,$sql);

    while ($row=mysqli_fetch_array($result))
     {
        
?>                 
              <a href="index.php?video_id=<?php echo $row['video_id'] ?>&course_name=<?php echo $row['course_name'] ?>"id="myVideo" class="nav-link px-3 active">
                <span class="me-2"><i class="bi bi-camera-video"></i></span>
                <span><?php echo $row['video_name']; ?></span>
              </a>
<?php } ?>
            </li>
            <li class="my-4"><hr class="dropdown-divider bg-light" /></li>
          </ul>
        </nav>
      </div>
    </div>


    <!-- offcanvas -->
    <main class="mt-5 pt-3">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12 mb-3">
            <div class="card">
              <div class="card-body">

    <div class="container show-controls">
        <div class="wrapper">
            <div class="video-timeline">
                <div class="progress-area">
                    <span>00:00</span>
                    <div class="progress-bar"></div>
                </div>
            </div>
            <ul class="video-controls">
                <li class="options left">
                    <button class="volume"><i class="fa-solid fa-volume-high"></i></button>
                    <input type="range" min="0" max="1" step="any">
                    <div class="video-timer">
                        <p class="current-time">00:00</p>
                        <p class="separator"> / </p>
                        <p class="video-duration">00:00</p>
                    </div>
                </li>
                <li class="options center">
                    <button class="skip-backward"><i class="fas fa-backward"></i></button>
                    <button class="play-pause"><i class="fas fa-play"></i></button>
                    <button class="skip-forward"><i class="fas fa-forward"></i></button>
                </li>
                <li class="options right">
                    <div class="playback-content">
                        <button class="playback-speed"><span class="material-symbols-rounded">slow_motion_video</span></button>
                        <ul class="speed-options">
                            <li data-speed="2">2x</li>
                            <li data-speed="1.5">1.5x</li>
                            <li data-speed="1" class="active">Normal</li>
                            <li data-speed="0.75">0.75x</li>
                            <li data-speed="0.5">0.5x</li>
                        </ul>
                    </div>
                    <button class="pic-in-pic"><span class="material-icons">picture_in_picture_alt</span></button>
                    <button class="fullscreen"><i class="fa-solid fa-expand"></i></button>
                </li>
            </ul>
        </div>
<video autoplay class="main-video" id="cspd_video" controlsList="nodownload"
  <?php  
        $_SESSION['vid']=$_GET['video_id'];
        $video_id=$_GET['video_id'];
        $sql="select * from videos where video_id='$video_id'";
        $result=mysqli_query($con,$sql);
        while ($row=mysqli_fetch_array($result))
         {
            ?>
            src=<?php echo $row['video_image'];    //fetching youtube video path from database & storing into src attribute
        
            
        }
        ?> 
></video>
</div>

  <p style="font-size: 20px;">
  <?php  
        $_SESSION['vid']=$_GET['video_id'];
        $video_id=$_GET['video_id'];
        $sql="select * from videos where video_id='$video_id'";
        $result=mysqli_query($con,$sql);
        while ($row=mysqli_fetch_array($result))
         {
                
            echo $row['video_name']; 
    }
        ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

 
     <main class="mt-1 pt-1">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12 mb-3">
            <div class="card">
              <div class="card-body">



  <p style="font-size: 15px;">
  <?php  
        $_SESSION['vid']=$_GET['video_id'];
        $video_id=$_GET['video_id'];
        $sql="select * from videos where video_id='$video_id'";
        $result=mysqli_query($con,$sql);
        while ($row=mysqli_fetch_array($result))
         {
                
            echo $row['video_description']; 
    }
        ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>   
    <script src="ty/./js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.0.2/dist/chart.min.js"></script>
    <script src="ty/./js/jquery-3.5.1.js"></script>
    <script src="ty/./js/jquery.dataTables.min.js"></script>
    <script src="ty/./js/dataTables.bootstrap5.min.js"></script>
    <script src="ty/./js/script.js"></script>
    <script src="script.js"></script>
  </body>
</html>
