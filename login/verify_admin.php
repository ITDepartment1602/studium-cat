<?php
session_start();
include '../config.php';
mysqli_select_db($con, 'u436962267_studium');

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_code'])) {
    echo "No admin login attempt found. Please log in first.";
    exit;
}

if (isset($_POST['verify_btn'])) {
    
    $enteredCode = $_POST['code'];

    if ($enteredCode == $_SESSION['admin_code']) {
        $adminId = $_SESSION['admin_id'];

        $status = "Active now";
        mysqli_query($con, "UPDATE login SET loginstatus = '$status' WHERE id = $adminId");
        mysqli_query($con, "UPDATE login SET lastlogin = NOW() WHERE id = $adminId");

        $_SESSION['admin_login'] = $adminId;

        unset($_SESSION['admin_code']);

        header("location:../admin/");
    } else {
        echo "<script>alert('Invalid code. Please try again.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../login/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css">
    <link rel="stylesheet" href="../student/dashboard/css/footer.css">
    <title>NCLEX Amplified</title>
    <link rel="shortcut icon" type="image/png" href="../img/logo.png">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .wrapper {
            margin-top: 100px;
        }
        .input-box {
            background: #fff;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .otp-input {
            width: 60px;
            height: 60px;
            font-size: 24px;
            text-align: center;
            margin: 0 5px;
            border: 2px solid #5598C6;
            border-radius: 5px;
        }
        .otp-container {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }
        .btn-verify {
            width: 100%;
            margin-top: 20px;
        }
    
    .responsive1 {
        margin-top: 50px; margin-bottom: 50px; width: 100%; display: flex; justify-content: center;}

    #textsss{
        font-size: 15px; font-weight: bold; color: black;
    }
        @media (min-width: 768px) {
        .responsive1 {
            margin-top: 100px;
            margin-bottom: 100px;
        }
        #textsss{
            font-size: 30px;
        }
    }

      @media (min-width: 1440px) {
        .responsive1 {
            margin-top: 200px;
            margin-bottom: 200px;
        }
        #textsss{
            font-size: 30px;
        }
    }
    </style>
</head>
<body>
    <header>
        <nav>
            <ul class='nav-bar d-flex justify-content-center'>
                <li class='logo'><a href='https://www.facebook.com/NCLEX.Amplified.Official' target="_blank"><img src='../img/logo2.svg' alt="Logo"></a></li>
            </ul>
        </nav>
    </header>

    <div class="responsive1" >
  
        <div>
            <div style="width: %;">
                <header id="textsss" class="text-center" >Enter the verification code sent to your registered email address</header>
             

                <?php if (!empty($error)) echo "<p class='text-danger text-center'>$error</p>"; ?>
<img src="../img/otp.png" style="width: 300px; margin: 0 auto; display: block;" alt="">
                    <form method="POST" class="text-center">
                            <label>Enter 6-digit code:</label>
                            <div class="otp-container" >
                                <input type="text" name="code" style="width: 40%;" class="otp-input" required maxlength="6">
                            </div>
                            <button type="submit" name="verify_btn"  style="background-color: #5598C6; border: none; width: 30%;" class="btn-verify btn btn-primary">Verify</button>
                        </form>
            </div> 
        </div>  
    </div>
    <br><br><br><br><br>
    <?php include "../student/dashboard/footer.php"; ?>

  
</body>
</html>