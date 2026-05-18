<?php
/**
 * Studium Root Entry Point — Login Page
 * Bootstrap-free redesign with premium split-screen layout.
 */

require_once 'config.php';

// Redirect already-authenticated users
if (isset($_SESSION['user_id']) || isset($_SESSION['admin_id'])) {

    if (isset($_SESSION['user_id'])) {
        $user_id = (int)$_SESSION['user_id'];
        $userRow = db()->fetchOne("SELECT bundle_name FROM login WHERE id = ?", [$user_id]);
        $bundle_name = $userRow['bundle_name'] ?? '';
    } else {
        $bundle_name = '';
    }

    header('Location: student/dashboard/index.php?bundle_name=' . urlencode($bundle_name));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — Studium</title>
  <link rel="shortcut icon" type="image/svg+xml" href="img/logo1.svg">

  <!-- Google Fonts: Poppins -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap Icons (icon library only — not Bootstrap framework) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <!-- Login page styles -->
  <link rel="stylesheet" href="login/style.css?v=3">
</head>
<body class="auth-body">

  <div class="auth-split">

    <!-- ══ LEFT PANEL — Brand identity ══ -->
    <div class="auth-left">
      <div class="auth-left-content">

        <!-- Logo -->
        <div class="auth-logo">
          <img src="assets/STUDIUM.svg" alt="Studium" class="auth-logo-img" style="height:34px;width:auto;display:block;">
        </div>

        <!-- Headline -->
        <h1 class="auth-headline">
          Pass the NCLEX.<br>
          Achieve your<br>
          <span>American Dream.</span>
        </h1>

        <p class="auth-subline">
          The most advanced adaptive learning platform built for Filipino nurses preparing for NCLEX licensure.
        </p>

        <!-- Feature highlights -->
        <div class="auth-features">
          <div class="auth-feature">
            <div class="auth-feature-icon">
              <i class="bi bi-lightning-charge-fill"></i>
            </div>
            <div>
              <div class="auth-feature-title">NGN-Ready Question Bank</div>
              <div class="auth-feature-desc">Next Generation NCLEX clinical judgment scenarios</div>
            </div>
          </div>
          <div class="auth-feature">
            <div class="auth-feature-icon">
              <i class="bi bi-activity"></i>
            </div>
            <div>
              <div class="auth-feature-title">CAT Adaptive Engine</div>
              <div class="auth-feature-desc">IRT-powered difficulty adjustment in real time</div>
            </div>
          </div>
          <div class="auth-feature">
            <div class="auth-feature-icon">
              <i class="bi bi-bar-chart-line-fill"></i>
            </div>
            <div>
              <div class="auth-feature-title">Performance Analytics</div>
              <div class="auth-feature-desc">Track concept mastery with detailed visual insights</div>
            </div>
          </div>
        </div>

      </div>

      <!-- Left footer -->
      <div class="auth-left-footer">
        <a href="https://www.facebook.com/NCLEXAmplifiedReviewCenter" target="_blank" rel="noopener noreferrer" class="auth-support-link">
          <i class="bi bi-facebook"></i> NCLEX Amplified Official
        </a>
      </div>
    </div>

    <!-- ══ RIGHT PANEL — Login form ══ -->
    <div class="auth-right">
      <div class="auth-form-container">

        <div class="auth-form-header">
          <h2 class="auth-form-title">Welcome back</h2>
          <p class="auth-form-sub">Sign in to your Studium account to continue</p>
        </div>

        <?php if (isset($_SESSION['flash_error'])): ?>
          <div class="auth-alert auth-alert--error">
            <i class="bi bi-exclamation-circle-fill"></i>
            <span><?php echo htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['flash_error']); ?></span>
          </div>
        <?php elseif (isset($_SESSION['error'])): ?>
          <div class="auth-alert auth-alert--error">
            <i class="bi bi-exclamation-circle-fill"></i>
            <span><?php echo htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?></span>
          </div>
        <?php endif; ?>

        <form method="POST" action="login/validation.php" onsubmit="return validateLoginForm()" autocomplete="on">

          <!-- Email -->
          <div class="auth-field">
            <label class="auth-label" for="email">Email address</label>
            <div class="auth-input-wrap">
              <i class="bi bi-envelope auth-input-icon"></i>
              <input
                type="text"
                id="email"
                name="email"
                class="auth-input"
                placeholder="your@email.com"
                required
                autocomplete="email"
              >
            </div>
          </div>

          <!-- Password -->
          <div class="auth-field">
            <label class="auth-label" for="password">Password</label>
            <div class="auth-input-wrap">
              <i class="bi bi-lock auth-input-icon"></i>
              <input
                type="password"
                id="password"
                name="password"
                class="auth-input"
                placeholder="••••••••"
                required
                autocomplete="current-password"
              >
              <button type="button" class="auth-eye-btn" id="togglePassword" aria-label="Toggle password visibility">
                <i class="bi bi-eye" id="eyeIcon"></i>
              </button>
            </div>
          </div>

          <!-- Hint -->
          <div class="auth-hint">
            <i class="bi bi-info-circle" style="margin-right:5px;"></i>
            NCLEX Amplified students receive their login credentials from support. Contact us if you need help accessing your account.
          </div>

          <button type="submit" class="auth-submit-btn">
            <span>Sign In</span>
            <i class="bi bi-arrow-right"></i>
          </button>

        </form>

        <div class="auth-contact">
          Need assistance? <a href="https://www.facebook.com/NCLEX.Amplified.Technical" target="_blank" rel="noopener noreferrer">Contact support</a>
        </div>

      </div>
    </div>

  </div>

  <script>
    // Password visibility toggle
    document.getElementById('togglePassword').addEventListener('click', function() {
      var input = document.getElementById('password');
      var icon  = document.getElementById('eyeIcon');
      var isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
    });

    // Basic front-end validation
    function validateLoginForm() {
      var email    = document.getElementById('email').value.trim();
      var password = document.getElementById('password').value.trim();
      return email !== '' && password !== '';
    }
  </script>

</body>
</html>
