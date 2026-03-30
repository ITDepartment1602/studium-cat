<?php
/**
 * Database Configuration
 * Simple version with helper functions
 */

// Detect environment
$isProduction = !in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1']);

if ($isProduction) {
    // Hostinger Production
    $con = mysqli_connect('127.0.0.1', 'u436962267_studium', 'Nclexamplified2023', 'u436962267_studium') 
        or die('connection failed');
} else {
    // Local Development
    $con = mysqli_connect('localhost', 'root', '', 'u436962267_studium') 
        or die('connection failed');
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============== HELPER FUNCTIONS ==============

/**
 * Get sanitized POST parameter
 */
function post(string $key, mixed $default = null): mixed {
    if (!isset($_POST[$key])) {
        return $default;
    }
    $value = $_POST[$key];
    if (is_array($value)) {
        return array_map(fn($v) => htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8'), $value);
    }
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect with optional flash message
 */
function redirect(string $url, ?string $message = null, string $type = 'success'): void {
    if ($message) {
        $_SESSION['flash_' . $type] = $message;
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Database wrapper function
 */
function db(): object {
    global $con;
    return new class($con) {
        private $con;
        public function __construct($con) { $this->con = $con; }
        
        public function execute(string $sql, array $params = []): bool {
            $stmt = $this->con->prepare($sql);
            if (!$stmt) return false;
            if (!empty($params)) {
                $types = '';
                foreach ($params as $p) {
                    $types .= is_int($p) ? 'i' : 's';
                }
                $stmt->bind_param($types, ...$params);
            }
            return $stmt->execute();
        }
        
        public function fetchOne(string $sql, array $params = []): ?array {
            $stmt = $this->con->prepare($sql);
            if (!$stmt) return null;
            if (!empty($params)) {
                $types = '';
                foreach ($params as $p) {
                    $types .= is_int($p) ? 'i' : 's';
                }
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        }
    };
}

/**
 * Authenticate user
 */
function authenticateUser(string $email, string $password): ?array {
    $user = db()->fetchOne("SELECT * FROM login WHERE email = ?", [$email]);
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    // Fallback for legacy passwords (plain text comparison)
    if ($user && $password === $user['password']) {
        return $user;
    }
    return null;
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Login user - set session variables
 */
function loginUser(array $user): void {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['fullname'] ?? '';
    $_SESSION['user_status'] = $user['status'] ?? 'user';
}

/**
 * Require login
 */
function requireLogin(string $redirect = '../login/'): void {
    if (!isLoggedIn()) {
        redirect($redirect, 'Please login first', 'error');
    }
}

// ============== QUIZ DATABASE ==============

/**
 * Get quiz database connection
 * On production, quiz tables are in the main database
 */
function getQuizConnection() {
    global $con;
    return $con;
}
?>
