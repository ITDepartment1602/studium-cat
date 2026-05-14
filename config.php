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

// Buffer any PHP warnings/notices during setup so they don't corrupt JSON API
// responses (PHP 8.1+ emits deprecation notices that break fetch().json()).
ob_start();

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
require_once __DIR__ . '/core/ScoringEngine.php';

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
            `theta_ability`    float        NOT NULL DEFAULT 0.0,
            `standard_error`   float        NOT NULL DEFAULT 1.0,
            `question_count`   int(11)      NOT NULL DEFAULT 0,
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
            `earned_points`   float         DEFAULT 0,
            `max_points`      int(11)       DEFAULT 1,
            `omitted`         tinyint(1)    DEFAULT 0,
            `changes_count`   int(11)       DEFAULT 0,
            `rationale`       text,
            `topic`           varchar(255)  DEFAULT NULL,
            `system`          varchar(255)  DEFAULT NULL,
            `cnc`             varchar(255)  DEFAULT NULL,
            `dlevel`          varchar(100)  DEFAULT NULL,
            `narcan`          varchar(255)  DEFAULT NULL,
            `concept`         varchar(255)  DEFAULT NULL,
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

    // Exam Mode (150-item adaptive CAT) — permanent results table
    // See EXAM_MODE_ENHANCEMENT_PLAN.md §4.1 — separate from exam_results so NGN practice and Exam Mode stay fully isolated
    mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS `exammoderesults` (
            `id`                   int(11)       NOT NULL AUTO_INCREMENT,
            `student_id`           int(11)       NOT NULL,
            `examTaken`            int(11)       NOT NULL,

            -- Question identification
            `question_uid`         varchar(100)  NOT NULL,
            `question_type`        varchar(50)   DEFAULT NULL,
            `question_id`          int(11)       DEFAULT NULL,
            `question_number`      int(11)       DEFAULT NULL,
            `source_table`         varchar(50)   DEFAULT NULL,

            -- Answer data
            `user_answer`          text,
            `correct_answer`       text,
            `initial_answer`       text          DEFAULT NULL,
            `changes`              json          DEFAULT NULL,

            -- Scoring
            `isCorrect`            tinyint(1)    DEFAULT 0,
            `score`                float         DEFAULT 0,
            `earned_points`        float         DEFAULT 0,
            `max_points`           int(11)       DEFAULT 1,
            `weighted_score`       float         DEFAULT 0,

            -- IRT state captured at answer time
            `theta_before`         float         DEFAULT 0.0,
            `theta_after`          float         DEFAULT 0.0,
            `sem_after`            float         DEFAULT 1.0,
            `item_difficulty`      float         DEFAULT 0.0,
            `item_information`     float         DEFAULT 0.0,

            -- Metadata
            `topic`                varchar(255)  DEFAULT NULL,
            `system`               varchar(255)  DEFAULT NULL,
            `cnc`                  varchar(255)  DEFAULT NULL,
            `dlevel`               varchar(100)  DEFAULT NULL,
            `narcan`               varchar(255)  DEFAULT NULL,
            `concept`              varchar(255)  DEFAULT NULL,
            `rationale`            text,

            -- Tracking
            `omitted`              tinyint(1)    DEFAULT 0,
            `changes_count`        int(11)       DEFAULT 0,
            `time_taken`           int(11)       DEFAULT 0,
            `totalTime`            int(11)       DEFAULT 0,
            `timestamp`            datetime      DEFAULT current_timestamp(),

            -- Running totals (denormalized for quick lookup)
            `running_correct`      int(11)       DEFAULT 0,
            `running_total`        int(11)       DEFAULT 0,
            `running_percent`      float         DEFAULT 0,

            -- Termination metadata (populated only on final/terminal row)
            `is_terminal`          tinyint(1)    DEFAULT 0,
            `termination_reason`   varchar(100)  DEFAULT NULL,
            `final_result`         varchar(20)   DEFAULT NULL,
            `final_percent`        float         DEFAULT NULL,
            `final_theta`          float         DEFAULT NULL,
            `final_sem`            float         DEFAULT NULL,
            `total_items_answered` int(11)       DEFAULT NULL,
            `exam_duration_sec`    int(11)       DEFAULT NULL,

            PRIMARY KEY (`id`),
            KEY `student_exam`   (`student_id`, `examTaken`),
            KEY `student_id`     (`student_id`),
            KEY `exam_terminal`  (`student_id`, `examTaken`, `is_terminal`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    // Permanent exam results table
    mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS `exam_results` (
            `id`              int(11)       NOT NULL AUTO_INCREMENT,
            `student_id`      int(11)       NOT NULL,
            `examTaken`       int(11)       NOT NULL,
            `question_uid`    varchar(100)  NOT NULL,
            `question_type`   varchar(50)   DEFAULT NULL,
            `question_id`     int(11)       DEFAULT NULL,
            `user_answer`     text,
            `correct_answer`  text,
            `initial_answer`  text          DEFAULT NULL,
            `changes`         json          DEFAULT NULL,
            `isCorrect`       tinyint(1)    DEFAULT 0,
            `score`           float         DEFAULT 0,
            `earned_points`   float         DEFAULT 0,
            `max_points`      int(11)       DEFAULT 1,
            `omitted`         tinyint(1)    DEFAULT 0,
            `changes_count`   int(11)       DEFAULT 0,
            `rationale`       text,
            `topic`           varchar(255)  DEFAULT NULL,
            `system`          varchar(255)  DEFAULT NULL,
            `cnc`             varchar(255)  DEFAULT NULL,
            `dlevel`          varchar(100)  DEFAULT NULL,
            `narcan`          varchar(255)  DEFAULT NULL,
            `concept`         varchar(255)  DEFAULT NULL,
            `time_taken`      int(11)       DEFAULT 0,
            `totalTime`       int(11)       DEFAULT 0,
            `question_number` int(11)       DEFAULT NULL,
            `timestamp`       datetime      DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `student_exam` (`student_id`, `examTaken`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    // ── SCHEMA ALIGNMENT — adds columns to existing tables on live DB ──
    // Helper: add column only if it does not exist (MySQL + MariaDB compatible)
    function _addColIfMissing($con, $table, $col, $definition) {
        $safe_table = mysqli_real_escape_string($con, $table);
        $safe_col   = mysqli_real_escape_string($con, $col);
        // Guard: skip silently if the table doesn't exist yet (prevents
        // mysqli_sql_exception in PHP 8.1+ where errors throw by default)
        $tbl = mysqli_query($con, "SHOW TABLES LIKE '{$safe_table}'");
        if (!$tbl || mysqli_num_rows($tbl) === 0) return;
        $r = mysqli_query($con, "SHOW COLUMNS FROM `{$safe_table}` LIKE '{$safe_col}'");
        if ($r && mysqli_num_rows($r) === 0) {
            mysqli_query($con, "ALTER TABLE `{$safe_table}` ADD COLUMN `{$safe_col}` {$definition}");
        }
    }

    $questionTables = ['traditional', 'sata', 'mpr', 'mmr', 'btq', 'dragndrop', 'dropdown', 'highlight', '`column`'];
    $resultTables   = ['temporary_exam_result', 'exam_results'];

    // 6.1 — difficulty_logit enables CAT adaptive selection in fetch_question.php
    foreach ($questionTables as $t) {
        _addColIfMissing($con, trim($t, '`'), 'difficulty_logit', 'DECIMAL(5,2) DEFAULT 0.0');
    }

    // 6.2 — topic/system/cnc/dlevel missing from btq and mmr
    foreach (['btq', 'mmr'] as $t) {
        _addColIfMissing($con, $t, 'topic',  'VARCHAR(255) DEFAULT NULL');
        _addColIfMissing($con, $t, 'system', 'VARCHAR(255) DEFAULT NULL');
        _addColIfMissing($con, $t, 'cnc',    'VARCHAR(255) DEFAULT NULL');
        _addColIfMissing($con, $t, 'dlevel', 'VARCHAR(100) DEFAULT NULL');
    }

    // 6.3 — narcan and concept per NGN Question Bank spec (PDF)
    foreach (array_merge(['traditional', 'sata', 'mpr', 'mmr', 'btq', 'dragndrop', 'dropdown', 'highlight', 'column'], $resultTables) as $t) {
        _addColIfMissing($con, $t, 'narcan',  'VARCHAR(255) DEFAULT NULL');
        _addColIfMissing($con, $t, 'concept', 'VARCHAR(255) DEFAULT NULL');
    }

    // 6.4 — result table columns that may be missing on older DB instances
    foreach ($resultTables as $t) {
        _addColIfMissing($con, $t, 'initial_answer',  'TEXT DEFAULT NULL');
        _addColIfMissing($con, $t, 'changes',         'JSON DEFAULT NULL');
        _addColIfMissing($con, $t, 'question_number', 'INT(11) DEFAULT NULL');
    }

    // 6.4b — Exam Mode (CAT/IRT) pause-resume state columns on temporary_exam_state
    // See EXAM_MODE_ENHANCEMENT_PLAN.md §4.2 — differentiates NGN practice (exam_mode='ngn') from Exam Mode (exam_mode='exam')
    _addColIfMissing($con, 'temporary_exam_state', 'exam_mode',           "VARCHAR(20) DEFAULT 'ngn'");
    _addColIfMissing($con, 'temporary_exam_state', 'adaptive_difficulty', "VARCHAR(10) DEFAULT 'medium'");
    _addColIfMissing($con, 'temporary_exam_state', 'correct_streak',      'INT(11) DEFAULT 0');
    _addColIfMissing($con, 'temporary_exam_state', 'wrong_streak',        'INT(11) DEFAULT 0');
    _addColIfMissing($con, 'temporary_exam_state', 'irt_theta',           'FLOAT DEFAULT 0.0');
    _addColIfMissing($con, 'temporary_exam_state', 'irt_sem',             'FLOAT DEFAULT 1.0');
    _addColIfMissing($con, 'temporary_exam_state', 'irt_history',         'LONGTEXT DEFAULT NULL');

    // 6.5 — testimonial table (may be absent from DB exports)
    mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS `testimonial` (
            `id`        int(11)      NOT NULL AUTO_INCREMENT,
            `message`   text         NOT NULL,
            `name`      varchar(255) DEFAULT NULL,
            `position`  varchar(255) DEFAULT NULL,
            `image`     varchar(500) DEFAULT NULL,
            `stars`     int(11)      DEFAULT 5,
            `created_at` datetime    DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    // 6.6 — table name aliases: Hostinger DB uses different names than codebase expects.
    //        Create views so both old and new names work without code changes.
    $tableAliases = [
        'traditional' => 'mcq',          // code uses 'traditional', DB has 'mcq'
        'sata'        => 'sata',          // may or may not exist
        'dropdown'    => 'dropdown_questions', // code uses 'dropdown', DB has 'dropdown_questions'
        'column'      => 'ngncolumn',     // code uses 'column', DB has 'ngncolumn'
    ];
    foreach ($tableAliases as $alias => $actual) {
        // Only create the alias view if the actual table exists but the alias doesn't
        $actualExists = mysqli_query($con, "SHOW TABLES LIKE '{$actual}'");
        $aliasExists  = mysqli_query($con, "SHOW TABLES LIKE '{$alias}'");
        if ($actualExists && mysqli_num_rows($actualExists) > 0 &&
            $aliasExists  && mysqli_num_rows($aliasExists) === 0) {
            mysqli_query($con, "CREATE OR REPLACE VIEW `{$alias}` AS SELECT * FROM `{$actual}`");
        }
    }
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

// ── END OF SETUP — flush the output buffer ───────────────────
// Discard any PHP notices/warnings that leaked during init so they can't
// corrupt JSON API responses.  Log them for debugging.
$_cfgBuf = ob_get_clean();
if ($_cfgBuf !== '' && $_cfgBuf !== false) {
    error_log('[config.php] PHP output during init: ' . substr(strip_tags($_cfgBuf), 0, 500));
}
?>
