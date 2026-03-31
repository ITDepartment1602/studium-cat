<?php
/**
 * Studium Root Entry Point
 * 
 * Handles initial login check and basic branding logic.
 * config.php handles session management and database connection ($con/db).
 */

require_once 'config.php';

// Check if user is already logged in
if (isset($_SESSION['user_id']) || isset($_SESSION['admin_id'])) {
    
    // User logic: fetch current bundle name for redirection
    if (isset($_SESSION['user_id'])) {
        $user_id = (int)$_SESSION['user_id'];
        
        // Use modern db() helper for cleaner and more secure logic
        $userRow = db()->fetchOne("SELECT bundle_name FROM login WHERE id = ?", [$user_id]);
        $bundle_name = $userRow['bundle_name'] ?? '';
    } else {
        $bundle_name = ''; // Default for admins
    }

    // Redirect to relevant dashboard
    header('Location: student/dashboard/index.php?bundle_name=' . urlencode($bundle_name));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="login/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css">
    <link rel="stylesheet" href="student/dashboard/css/footer.css">
    <title>Studium Login</title>
    <link rel="shortcut icon" type="image/svg+xml" href="img/logo1.svg">
</head>
<body>
    <header>
        <nav>
            <ul class='nav-bar' style="display: flex; justify-content: center; background-color: #1B4965;">
                <li class='logo'>
                    <a href='https://www.facebook.com/NCLEXAmplifiedReviewCenter' target="_blank">
                        <img src='img/logo3.png' alt="Logo">
                    </a>
                </li>
            </ul>
        </nav>
    </header>

    <main class="wrapper" style="margin-top: 50px;">
        <div class="container main">
            <div class="row">
                <div class="col-md-6 side-image">
                    <img src="login/logos/logo.png" alt="Logo" width="30%" style="display:block; margin:20px auto;">
                    <div style="text-align: center;">
                        <p style="font-size:28px"><b>Achieve your American Dream!</b></p>
                    </div>
                    <?php 
                        // Only include if file actually exists
                        if (file_exists("login/testi/Testimonial.php")) {
                            include "login/testi/Testimonial.php";
                        }
                    ?>
                </div>

                <div class="col-md-6 right">
                    <div class="input-box">
                        <header style="font-size: 36px; color: #1B4965; text-align: center;">Login</header>
                        <p style="font-size: 14px; text-align: center;">Sign in using your Studium Account</p>
                        
                        <form method="POST" action="login/validation.php" onsubmit="return validation()">
                            <?php if (isset($_SESSION['flash_error'])): ?>
                                <div class="alert alert-danger text-center" style="font-size: 13px;" role="alert">
                                    <?php 
                                        echo $_SESSION['flash_error']; 
                                        unset($_SESSION['flash_error']); 
                                    ?>
                                </div>
                            <?php elseif (isset($_SESSION['error'])): // Legacy support ?>
                                <div class="alert alert-danger text-center" style="font-size: 13px;" role="alert">
                                    <?php 
                                        echo $_SESSION['error']; 
                                        unset($_SESSION['error']); 
                                    ?>
                                </div>
                            <?php endif; ?>

                            <div class="input-field mt-4">
                                <input type="text" class="input" required name="email" id="email" autocomplete="email">
                                <label for="email">Email</label>
                            </div>

                            <div class="input-field mt-4">
                                <input type="password" class="input" name="password" id="password" required autocomplete="current-password">
                                <i class="far fa-eye" id="togglePassword" style="position: absolute; right: 10px; top: 15px; cursor: pointer;"></i>
                                <label for="password">Password</label>
                            </div>

                            <div class="mt-4 text-center">
                                <p style="font-size: 12px; color: #1B4965; font-weight: 500;">
                                    Are you an NCLEX Amplified student? <br>
                                    <span style="font-weight: 400; color: #666;">Contact support to receive your email and password.</span>
                                </p>
                            </div>

                            <div class="text-end">
                                <button class="btn btn-primary px-4 py-2" type="submit" style="background-color: #1B4965; border: none; font-weight: bold;">
                                    Login
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="mt-5">
        <?php 
            if (file_exists("student/dashboard/footer.php")) {
                include "student/dashboard/footer.php"; 
            }
        ?>
    </footer>

    <script>
        // Password toggle logic
        const togglePassword = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#password');

        togglePassword.addEventListener('click', function (e) {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });

        // Basic front-end validation
        function validation() {
            var email = document.getElementById('email').value;
            var password = document.getElementById('password').value;
            if (email === "" || password === "") {
                return false;
            }
        }
    </script>
</body>
</html>