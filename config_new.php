<?php
/**
 * Studium-CAT Unified Configuration
 * 
 * This is the ONLY file you need to include in your PHP files.
 * It handles everything: database, security, sessions, and environment detection.
 * 
 * USAGE: Simply put this at the top of your PHP files:
 *   require_once __DIR__ . '/../config.php';  // Adjust path as needed
 * 
 * No need to include config.php multiple times. This file handles it all.
 */

// Prevent multiple inclusions
if (defined('STUDIUM_CONFIG_LOADED')) {
    return;
}
define('STUDIUM_CONFIG_LOADED', true);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 0,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_httponly' => true,
        'use_strict_mode' => true
    ]);
}

// Define base paths
define('BASE_PATH', dirname(__FILE__));
define('CORE_PATH', BASE_PATH . '/core');
define('INCLUDES_PATH', BASE_PATH . '/includes');

// Load core classes
require_once CORE_PATH . '/Database.php';
require_once CORE_PATH . '/Security.php';

// Legacy compatibility - provide $con and $conn variables for old code
$db = Database::getInstance();
$con = $db->getConnection();  // MySQLi connection for legacy code

// Quiz database connection (lazy loaded)
function getQuizConnection() {
    return Database::getInstance()->getQuizConnection();
}

// Legacy PDO compatibility
$conn = new class($db) {
    private $db;
    public function __construct($db) { $this->db = $db; }
    public function prepare($sql) { return new class($this->db, $sql) {
        private $db, $sql;
        public function __construct($db, $sql) { $this->db = $db; $this->sql = $sql; }
        public function execute($params = []) { return $this->db->query($this->sql, $params); }
    };}
    public function query($sql, $params = []) { return $this->db->query($sql, $params); }
};

// Helper function to safely include files - prevents multiple includes
function studium_include($path) {
    static $included = [];
    $realPath = realpath($path);
    if ($realPath && !isset($included[$realPath])) {
        $included[$realPath] = true;
        return include $realPath;
    }
    return false;
}

// Environment info function
function getEnvironment(): array {
    $db = Database::getInstance();
    return [
        'is_production' => $db->isProduction(),
        'environment' => $db->getEnvironment(),
        'db_host' => $db->isProduction() ? '127.0.0.1' : 'localhost'
    ];
}

// Debug helper (only works in local environment)
function debug($data, $die = false): void {
    $db = Database::getInstance();
    if (!$db->isProduction()) {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        if ($die) die;
    }
}

// Error handler - logs errors but doesn't expose them in production
set_error_handler(function($severity, $message, $file, $line) {
    $db = Database::getInstance();
    
    // Always log the error
    error_log("Error [$severity]: $message in $file on line $line");
    
    // Only show detailed errors in local environment
    if (!$db->isProduction()) {
        echo "<div style='background:#ffebee;padding:10px;margin:10px;border-left:4px solid #f44336;'>";
        echo "<strong>Error:</strong> $message<br>";
        echo "<strong>File:</strong> $file<br>";
        echo "<strong>Line:</strong> $line";
        echo "</div>";
    } else {
        // In production, show generic message
        echo "<div style='background:#ffebee;padding:10px;margin:10px;'>An error occurred. Please try again later.</div>";
    }
    
    return true;
});

// Exception handler
set_exception_handler(function($e) {
    error_log("Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    $db = Database::getInstance();
    if (!$db->isProduction()) {
        echo "<div style='background:#ffebee;padding:10px;margin:10px;border-left:4px solid #f44336;'>";
        echo "<strong>Exception:</strong> " . $e->getMessage() . "<br>";
        echo "<strong>File:</strong> " . $e->getFile() . "<br>";
        echo "<strong>Line:</strong> " . $e->getLine();
        echo "</div>";
    } else {
        echo "<div style='background:#ffebee;padding:10px;margin:10px;'>An unexpected error occurred. Please try again later.</div>";
    }
});

// Load any custom includes
if (is_dir(INCLUDES_PATH)) {
    foreach (glob(INCLUDES_PATH . '/*.php') as $file) {
        require_once $file;
    }
}
?>
