<?php
/**
 * Security Helper Functions
 * 
 * Provides safe input handling to prevent SQL injection and XSS attacks
 */

/**
 * Sanitize input string - prevents XSS
 * 
 * @param string $input Raw input
 * @return string Sanitized output
 */
function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Get sanitized GET parameter
 * 
 * @param string $key Parameter name
 * @param mixed $default Default value if not found
 * @return mixed Sanitized value
 */
function get(string $key, mixed $default = null): mixed {
    if (!isset($_GET[$key])) {
        return $default;
    }
    
    $value = $_GET[$key];
    
    // Handle arrays
    if (is_array($value)) {
        return array_map('sanitize', $value);
    }
    
    return sanitize($value);
}

/**
 * Get raw GET parameter (for IDs that need to be integers)
 * 
 * @param string $key Parameter name
 * @param int|null $default Default value
 * @return int|null Integer value or null
 */
function getInt(string $key, ?int $default = null): ?int {
    if (!isset($_GET[$key]) || !is_numeric($_GET[$key])) {
        return $default;
    }
    return (int) $_GET[$key];
}

/**
 * Get sanitized POST parameter
 * 
 * @param string $key Parameter name
 * @param mixed $default Default value if not found
 * @return mixed Sanitized value
 */
function post(string $key, mixed $default = null): mixed {
    if (!isset($_POST[$key])) {
        return $default;
    }
    
    $value = $_POST[$key];
    
    // Handle arrays
    if (is_array($value)) {
        return array_map('sanitize', $value);
    }
    
    return sanitize($value);
}

/**
 * Get raw POST parameter as integer
 * 
 * @param string $key Parameter name
 * @param int|null $default Default value
 * @return int|null Integer value or null
 */
function postInt(string $key, ?int $default = null): ?int {
    if (!isset($_POST[$key]) || !is_numeric($_POST[$key])) {
        return $default;
    }
    return (int) $_POST[$key];
}

/**
 * Validate email address
 * 
 * @param string $email Email to validate
 * @return bool True if valid
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate CSRF token
 * 
 * @return string CSRF token
 */
function generateCsrfToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 * 
 * @param string $token Token to validate
 * @return bool True if valid
 */
function validateCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output CSRF token field for forms
 * 
 * @return string HTML input field
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
}

/**
 * Redirect with message
 * 
 * @param string $url URL to redirect to
 * @param string|null $message Optional message to flash
 * @param string $type Message type (success, error, warning)
 */
function redirect(string $url, ?string $message = null, string $type = 'success'): void {
    if ($message !== null) {
        $_SESSION['flash_' . $type] = $message;
    }
    header("Location: $url");
    exit;
}

/**
 * Get flash message and clear it
 * 
 * @param string $type Message type
 * @return string|null Message or null
 */
function getFlash(string $type = 'success'): ?string {
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $message = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $message;
    }
    return null;
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require login - redirect if not logged in
 * 
 * @param string $redirect URL to redirect to if not logged in
 */
function requireLogin(string $redirect = '../login/'): void {
    if (!isLoggedIn()) {
        redirect($redirect, 'Please login first', 'error');
    }
}

/**
 * Get current user ID
 * 
 * @return int|null User ID or null
 */
function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Hash password securely
 * 
 * @param string $password Plain password
 * @return string Hashed password
 */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 * 
 * @param string $password Plain password
 * @param string $hash Hashed password
 * @return bool True if match
 */
function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Generate random string
 * 
 * @param int $length Length of string
 * @return string Random string
 */
function randomString(int $length = 16): string {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Format date for display
 * 
 * @param string $date Date string
 * @param string $format Output format
 * @return string Formatted date
 */
function formatDate(string $date, string $format = 'M d, Y h:i A'): string {
    return date($format, strtotime($date));
}

/**
 * Check if string contains only alphanumeric and spaces
 * 
 * @param string $string Input string
 * @return bool True if valid
 */
function isAlphanumeric(string $string): bool {
    return ctype_alnum(str_replace(' ', '', $string));
}
?>
