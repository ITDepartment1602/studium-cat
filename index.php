<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id']) || isset($_SESSION['admin_id'])) {
    include 'config.php'; // Include your database configuration

    // If user is logged in, fetch their bundle name
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $query = "SELECT bundle_name FROM login WHERE id = '$user_id'";
        $result = mysqli_query($con, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $bundle_name = $row['bundle_name'];
        } else {
            $bundle_name = ''; // Default value if not found
        }
    } else {
        $bundle_name = ''; // Default value for admin or other sessions
    }

    header('Location: student/dashboard/index.php?bundle_name=' . $bundle_name);
    exit;
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="login/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css">
    <link rel="stylesheet" href="student/dashboard/css/footer.css">
    <title>Studium Login</title>
    <link rel="shortcut icon" type="text/css" href="img/logo1.svg">
</head>

<body>
    <header>
        <nav>
            <ul class='nav-bar' style="display: flex; justify-content: center; background-color: #1B4965;">
                <li class='logo'><a href='https://www.facebook.com/NCLEXAmplifiedReviewCenter' target="_blank"><img
                            src='img/logo3.png'></a></li>

            </ul>
        </nav>
    </header>

    <br><br><br>

    <div class="wrapper">
        <div class="container main">
            <div class="row">
                <div class="col-md-6 side-image">

                    <!-------------      image     ------------->

                    <img src="login/logos/logo.png" alt="" width="30%" style="margin-left: 35%;margin-top: 10%;">
                    <br>
                    <center>
                        <p style="font-size:30px"><b>Achieve your American Dream!</b></p>
                    </center>

                    <?php include "login/testi/Testimonial.php"; ?>

                </div>

                <div class="col-md-6 right">

                    <div class="input-box">

                        <header style="font-size: 40px; color: #1B4965;">Login</header>
                        <p style=" margin-top: -40px; font-size: 15px; text-align: center;">Sign in using your Studium
                            Account</p>
                        <br>
                        <form method="POST" action="login/validation.php" onsubmit="return validation()">
                            <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger"
                                style="margin-top: -50px; text-align:center; margin-bottom: -5px; font-size: <?php echo ($_SESSION['error'] === "You can't log in right now because you're already logged in on another device or browser. If you closed the browser without logging out, please wait 2 minutes before trying again for security reasons.") ? '12px' : '15px'; ?>;"
                                role="alert">
                                <?php
                                echo $_SESSION['error']; // Display the error message
                                unset($_SESSION['error']); // Clear the error message after displaying
                                ?>
                            </div>
                            <?php endif; ?><br>

                            <div class="input-field">
                                <input type="text" class="input" required name="email" id="email" onkeypress="clear()"
                                    autocomplete="off">
                                <label for="email">Email:</label>
                            </div>

                            <div class="input-field">
                                <input type="password" class="input" name="password" id="password" required>
                                <i class="far fa-eye" id="togglePassword"
                                    style="margin-left: 95%; margin-top: -50px; cursor: pointer;"></i>
                                <label for="pass">Password:</label>

                            </div>

                            <br>
                            <p
                                style="font-size: 13px; color: #1B4965; padding-left: 10px; padding-right: 10px; font-weight: bold;">
                                Are you an NCLEX Amplified student? <span style="font-weight: normal;">You need to
                                    avail
                                    first to get your email and password.</span> </p>

                            <button class="submit" type="submit"
                                style="width:90px; float: right; background-color: #1B4965"><b>Login</b></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <script type="text/javascript">

        function validation() {
            var email = document.getElementById('email').value;
            var password = document.getElementById('password').value;

            if ((email == "") || (password == "")) {
                document.getElementById('perror').innerHTML = "Please Fill the Details";
                return false;
            }
        }


        function clear() {
            document.getElementById('perror').innerHTML = "ksdfisdhfg";
        }

    </script>

    <script>

        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function (e) {
            // toggle the type attribute
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            // toggle the eye slash icon
            this.classList.toggle('fa-eye-slash');
        });
    </script>
    <br><br><br><br>
    <?php include "student/dashboard/footer.php"; ?>
</body>

</html>