<?php
/**
 * ============================================================
 *  STUDIUM-CAT — MASTER CONFIGURATION FILE
 * ============================================================
 *  This is the SINGLE config file used by every PHP file
 *  in this project.  All other files just do:
 *      include '../../config.php';   // adjust relative path
 *
 *  ✅ Auto-detects environment (localhost vs dev.studium.cat)
 *  ✅ Sets DB credentials per environment
 *  ✅ Starts session safely (no double-start errors)
 *  ✅ Provides $con and db() for ALL code (legacy & modern)
 *  ✅ Auto-calculates BASE_URL for all redirects
 *  ✅ Auto-creates required tables if missing on Hostinger
 * ============================================================
 */

// Prevent loading twice
if (defined('STUDIUM_CONFIG_LOADED')) {
    return;
}
define('STUDIUM_CONFIG_LOADED', true);

// ── 1. ENVIRONMENT DETECTION ──────────────────────────────────
$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
define('IS_PRODUCTION', !in_array($host, ['localhost', '127.0.0.1', '::1', '']));
define('APP_ENV', IS_PRODUCTION ? 'production' : 'local');

// ── 1b. BASE URL & BASE PATH ─────────────────────────────────
// Automatically calculates the correct web root for this project.
if (!defined('BASE_URL')) {
    $scheme   = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host_url = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Project root is the folder containing this file (config.php)
    $configDir = str_replace('\\', '/', dirname(__FILE__));
    $docRoot   = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    $subPath   = ltrim(str_replace($docRoot, '', $configDir), '/');
    $base      = $subPath ? '/' . $subPath . '/' : '/';
    
    define('BASE_URL', $scheme . '://' . $host_url . $base);
    define('BASE_PATH', __DIR__);
}

// ── 2. ERROR DISPLAY & LOGGING ────────────────────────────────
if (IS_PRODUCTION) {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// ── 3. SESSION CLEANUP & START ──────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 0,
        'cookie_httponly'  => true,
        'cookie_secure'    => IS_PRODUCTION, // HTTPS only on prod
        'use_strict_mode'  => true,
    ]);
}

// ── 4. DATABASE CREDENTIALS ───────────────────────────────────
if (IS_PRODUCTION) {
    // ── Production (Hostinger) ──
    define('DB_HOST',      '127.0.0.1');
    define('DB_USER',      'u436962267_studium');
    define('DB_PASS',      'Nclexamplified2023');
    define('DB_NAME',      'u436962267_studium');
    
    define('QUIZ_DB_HOST', '127.0.0.1');
    define('QUIZ_DB_USER', 'u436962267_studium');
    define('QUIZ_DB_PASS', 'Nclexamplified2023');
    define('QUIZ_DB_NAME', 'u436962267_studium');
} else {
    // ── Local (XAMPP localhost) ──
    define('DB_HOST',      'localhost');
    define('DB_USER',      'root');
    define('DB_PASS',      '');
    define('DB_NAME',      'u436962267_studium');
    
    define('QUIZ_DB_HOST', 'localhost');
    define('QUIZ_DB_USER', 'root');
    define('QUIZ_DB_PASS', '');
    define('QUIZ_DB_NAME', 'u436962267_studium');
}

// ── 5. CORE SYSTEM LOAD ──────────────────────────────────────
// Load modern Database class
require_once __DIR__ . '/core/Database.php';

// Initialize the master connection for legacy code support ($con)
try {
    $con = db()->getConnection();
} catch (Exception $e) {
    if (!IS_PRODUCTION) {
        die('Initialization error: ' . $e->getMessage());
    } else {
        die('Service temporarily unavailable.');
    }
}

// Legacy quiz support
if (!function_exists('getQuizConnection')) {
    function getQuizConnection() {
        return db()->getQuizConnection();
    }
}

// ── 6. AUTO-CREATE REQUIRED TABLES ───────────────────────────
// Ensures the Hostinger database stays in sync automatically
if (isset($con)) {
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
}

// ── 7. HELPER FUNCTIONS ───────────────────────────────────────

if (!function_exists('redirect')) {
    function redirect(string $url, ?string $message = null, string $type = 'success'): void {
        if ($message) {
            $_SESSION['flash_' . $type] = $message;
        }
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('post')) {
    function post(string $key, $default = null) {
        if (!isset($_POST[$key])) return $default;
        $v = $_POST[$key];
        if (is_array($v)) {
            return array_map(fn($i) => htmlspecialchars(strip_tags(trim($i)), ENT_QUOTES, 'UTF-8'), $v);
        }
        return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn(): bool {
        return isset($_SESSION['user_id']) || isset($_SESSION['admin_id']);
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin(): void {
        if (!isLoggedIn()) {
            redirect(BASE_URL . 'index.php', 'Please log in first.', 'error');
        }
    }
}

if (!function_exists('authenticateUser')) {
    function authenticateUser(string $email, string $password): ?array {
        $user = db()->fetchOne("SELECT * FROM login WHERE email = ? LIMIT 1", [$email]);
        if (!$user) return null;
        if (password_verify($password, $user['password'])) return $user;
        if ($password === $user['password']) return $user; // legacy support
        return null;
    }
}

if (!function_exists('loginUser')) {
    function loginUser(array $user): void {
        $_SESSION['user_id']     = $user['id'];
        $_SESSION['user_email']  = $user['email'];
        $_SESSION['user_name']   = $user['fullname'] ?? '';
        $_SESSION['user_status'] = $user['status']   ?? 'user';
    }
}

if (!function_exists('debug')) {
    function debug($data, bool $die = false): void {
        if (!IS_PRODUCTION) {
            echo '<pre style="background:#f3f4f6;padding:10px;border-radius:6px;font-size:12px;margin:10px;">';
            print_r($data);
            echo '</pre>';
            if ($die) exit;
        }
    }
}
?>
