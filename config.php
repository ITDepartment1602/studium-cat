<?php
/**
 * ============================================================
 *  STUDIUM-CAT — MASTER CONFIGURATION FILE
 * ============================================================
 *  This is the SINGLE config file used by every PHP file
 *  in this project.  All other files just do:
 *      include '../../config.php';   // adjust path
 *
 *  ✅ Auto-detects localhost vs production (dev.studium.cat)
 *  ✅ Sets DB credentials per environment
 *  ✅ Starts session safely (no double-start errors)
 *  ✅ Hides error details from users on production
 *  ✅ Provides $con and $quizCon for all legacy code
 *  ✅ Auto-creates required temporary tables if missing
 * ============================================================
 */

// Prevent loading twice
if (defined('STUDIUM_CONFIG_LOADED')) {
    return;
}
define('STUDIUM_CONFIG_LOADED', true);

// ── 1. ENVIRONMENT DETECTION ──────────────────────────────────
// Production  = anything that isn't localhost
$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
define('IS_PRODUCTION', !in_array($host, ['localhost', '127.0.0.1', '::1', '']));
define('APP_ENV', IS_PRODUCTION ? 'production' : 'local');

// ── 2. ERROR DISPLAY ──────────────────────────────────────────
// Never expose PHP errors to end-users on production
if (IS_PRODUCTION) {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// ── 3. SESSION ────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 0,
        'cookie_httponly'  => true,
        'cookie_secure'    => IS_PRODUCTION,   // HTTPS on prod
        'use_strict_mode'  => true,
    ]);
}

// ── 4. DATABASE CREDENTIALS ───────────────────────────────────
if (IS_PRODUCTION) {
    // ── Hostinger / live server ──
    define('DB_HOST',      '127.0.0.1');
    define('DB_USER',      'u436962267_studium');
    define('DB_PASS',      'Nclexamplified2023');
    define('DB_NAME',      'u436962267_studium');
    // On Hostinger both the main app and quiz tables live in the same DB
    define('QUIZ_DB_HOST', '127.0.0.1');
    define('QUIZ_DB_USER', 'u436962267_studium');
    define('QUIZ_DB_PASS', 'Nclexamplified2023');
    define('QUIZ_DB_NAME', 'u436962267_studium');
} else {
    // ── Local (XAMPP / localhost) ──
    define('DB_HOST',      'localhost');
    define('DB_USER',      'root');
    define('DB_PASS',      '');
    define('DB_NAME',      'u436962267_studium');
    // Local quiz DB — change 'quiz' if your local DB name differs
    define('QUIZ_DB_HOST', 'localhost');
    define('QUIZ_DB_USER', 'root');
    define('QUIZ_DB_PASS', '');
    define('QUIZ_DB_NAME', 'u436962267_studium');
}

// ── 5. CONNECT TO MAIN DB ($con) ──────────────────────────────
$con = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$con) {
    if (!IS_PRODUCTION) {
        die('Database connection failed: ' . mysqli_connect_error());
    } else {
        die('Service temporarily unavailable. Please try again later.');
    }
}
mysqli_set_charset($con, 'utf8mb4');
mysqli_query($con, "SET time_zone = '+08:00'");

// ── 6. QUIZ DB ($quizCon) ─────────────────────────────────────
// Lazy-loaded via getQuizConnection() — same connection on prod
function getQuizConnection() {
    static $quizCon = null;
    if ($quizCon !== null) {
        return $quizCon;
    }
    // On Hostinger, quiz tables are in the same DB as main
    if (IS_PRODUCTION || QUIZ_DB_NAME === DB_NAME) {
        global $con;
        $quizCon = $con;
    } else {
        $quizCon = @mysqli_connect(QUIZ_DB_HOST, QUIZ_DB_USER, QUIZ_DB_PASS, QUIZ_DB_NAME);
        if (!$quizCon) {
            if (!IS_PRODUCTION) {
                die('Quiz DB connection failed: ' . mysqli_connect_error());
            }
            global $con;
            $quizCon = $con; // fallback to main
        }
        mysqli_set_charset($quizCon, 'utf8mb4');
        mysqli_query($quizCon, "SET time_zone = '+08:00'");
    }
    return $quizCon;
}

// ── 7. AUTO-CREATE REQUIRED TEMPORARY TABLES ─────────────────
// These tables are needed by the NGN exam engine.
// If they are missing on the production server this will create them.
mysqli_query($con, "
    CREATE TABLE IF NOT EXISTS `temporary_exam_state` (
        `student_id`       int(11)      NOT NULL,
        `examTaken`        int(11)      NOT NULL,
        `question_set`     text         NOT NULL,
        `current_question` int(11)      NOT NULL DEFAULT 0,
        `timer`            int(11)      NOT NULL DEFAULT 0,
        `updated_at`       datetime     NOT NULL,
        PRIMARY KEY (`student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

mysqli_query($con, "
    CREATE TABLE IF NOT EXISTS `temporary_exam_result` (
        `id`              int(11)       NOT NULL AUTO_INCREMENT,
        `student_id`      int(11)       NOT NULL,
        `examTaken`       int(11)       NOT NULL,
        `question_uid`    varchar(100)  NOT NULL,
        `question_type`   varchar(50)   DEFAULT NULL,
        `question_id`     int(11)       DEFAULT NULL,
        `user_answer`     text,
        `correct_answer`  text,
        `isCorrect`       tinyint(1)    DEFAULT 0,
        `score`           float         DEFAULT 0,
        `max_points`      int(11)       DEFAULT 1,
        `earned_points`   int(11)       DEFAULT 0,
        `rationale`       text,
        `topic`           varchar(255)  DEFAULT NULL,
        `system`          varchar(255)  DEFAULT NULL,
        `cnc`             varchar(255)  DEFAULT NULL,
        `dlevel`          varchar(100)  DEFAULT NULL,
        `time_taken`      int(11)       DEFAULT 0,
        `totalTime`       int(11)       DEFAULT 0,
        `initial_answer`  text          DEFAULT NULL,
        `changes`         json          DEFAULT NULL,
        `question_number` int(11)       DEFAULT NULL,
        `timestamp`       datetime      DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `student_exam` (`student_id`, `examTaken`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

// ── 8. HELPER FUNCTIONS ───────────────────────────────────────

/**
 * Safe redirect with optional flash message
 */
function redirect(string $url, ?string $message = null, string $type = 'success'): void {
    if ($message) {
        $_SESSION['flash_' . $type] = $message;
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Get sanitized POST value
 */
function post(string $key, $default = null) {
    if (!isset($_POST[$key])) return $default;
    $v = $_POST[$key];
    if (is_array($v)) {
        return array_map(fn($i) => htmlspecialchars(strip_tags(trim($i)), ENT_QUOTES, 'UTF-8'), $v);
    }
    return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8');
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Require the user to be logged in; redirect otherwise
 */
function requireLogin(string $loginUrl = '../login/'): void {
    if (!isLoggedIn()) {
        redirect($loginUrl, 'Please log in first.', 'error');
    }
}

/**
 * Authenticate user (supports both hashed and legacy plain-text passwords)
 */
function authenticateUser(string $email, string $password): ?array {
    global $con;
    $stmt = mysqli_prepare($con, "SELECT * FROM login WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if (!$user) return null;
    if (password_verify($password, $user['password'])) return $user;
    if ($password === $user['password']) return $user; // legacy plain-text fallback
    return null;
}

/**
 * Log user in (set session)
 */
function loginUser(array $user): void {
    $_SESSION['user_id']     = $user['id'];
    $_SESSION['user_email']  = $user['email'];
    $_SESSION['user_name']   = $user['fullname'] ?? '';
    $_SESSION['user_status'] = $user['status']   ?? 'user';
}

/**
 * Debug dump — only shown on local env
 */
function debug($data, bool $die = false): void {
    if (!IS_PRODUCTION) {
        echo '<pre style="background:#f3f4f6;padding:10px;border-radius:6px;">';
        print_r($data);
        echo '</pre>';
        if ($die) exit;
    }
}
?>
