<?php
include '../../config.php';
$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Data</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 50px auto;
            margin-top: 4%;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header img {
            border-radius: 50%;
            width: 80px;
            height: 80px;
            margin-bottom: 10px;
        }

        .header h2 {
            margin: 10px 0;
            font-size: 24px;
            color: #333;
        }

        .info {
            margin-bottom: 20px;
        }

        .info b {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }

        .info input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 10px;
            background-color: #f9f9f9;
            color: #333;
        }

        .info input:disabled {
            background-color: #e9ecef;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #555;
        }

        .scroll {
            text-align: center;
            margin: 20px 0;
        }

        .scroll button {
            display: none;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="header">
            <img src="../../img/studiumlogo.png" alt="Logo">
            <h2><?php echo $fetch['fullname']; ?></h2>
            <a href="history.php" style="color: #1B4965;">View My History</a>
        </div>

        <div class="info">
            <b>Student Number:</b>
            <input type="text" value="<?php echo $fetch['studentnumber']; ?>" disabled>
            <b>Package Avail:</b>
            <input type="text" value="<?php echo str_replace('Packege', 'Package', $fetch['bundle_name']); ?>" disabled>
            <b>Date Avail:</b>
            <input type="text" value="<?php echo date('F d, Y h:i A', strtotime($fetch['dateenrolled'])); ?>" disabled>
            <b>Date Expired:</b>
            <input type="text" value="<?php echo date('F d, Y h:i A', strtotime($fetch['dateexpired'])); ?>" disabled>
            <b>Exam Taken:</b>
            <input type="text" value="<?php echo $fetch['examTaken']; ?>" disabled>
            <b>Email:</b>
            <input type="text" value="<?php echo $fetch['email']; ?>" disabled>
            <b>Password:</b>
            <input type="text" value="<?php echo $fetch['password']; ?>" disabled>
        </div>

        <p class="footer">All Times Are UTC (i.e., Philippines Time)</p>
    </div>

    <div class="scroll" id="btm">
        <button><i class="fa fa-chevron-circle-down fa-3x" aria-hidden="true"></i></button>
    </div>

    <div class="footer"
        style="position: fixed; bottom: 0; left: 0; right: 0; background-color: #1B4965; color: white; text-align: center; padding: 5px 0;">
        © Studium 2025, All Right Reserved.
    </div>
</body>

</html>