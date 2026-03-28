<?php
/**
 * Authentication Helper Functions
 * 
 * Common functions for user authentication and authorization.
 */

/**
 * Authenticate user with email and password
 * 
 * @param string $email User email
 * @param string $password Plain text password
 * @return array|null User data if authenticated, null otherwise
 */
function authenticateUser(string $email, string $password): ?array {
    $user = getUserByEmail($email);
    
    if (!$user) {
        return null;
    }
    
    // Check password - supports both old md5 and new password_hash
    if (isset($user['password'])) {
        if (password_verify($password, $user['password'])) {
            return $user;
        }
        // Legacy md5 check (for backwards compatibility)
        if (md5($password) === $user['password']) {
            return $user;
        }
    }
    
    return null;
}

/**
 * Login user and set session
 * 
 * @param array $user User data
 * @return void
 */
function loginUser(array $user): void {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['name'] ?? '';
    $_SESSION['user_group'] = $user['groupname'] ?? 'user';
    $_SESSION['bundle_name'] = $user['bundle_name'] ?? '';
    $_SESSION['login_time'] = time();
    
    // Update last login in database
    updateLastLogin($user['id']);
    
    // Log the activity
    logActivity('login', $user['id'], 'User logged in');
}

/**
 * Logout user and destroy session
 * 
 * @return void
 */
function logoutUser(): void {
    if (isset($_SESSION['user_id'])) {
        logActivity('logout', $_SESSION['user_id'], 'User logged out');
    }
    
    // Clear session data
    $_SESSION = [];
    
    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }
    
    session_destroy();
}

/**
 * Check if current user is admin
 * 
 * @return bool True if admin
 */
function isAdmin(): bool {
    return isset($_SESSION['user_group']) && $_SESSION['user_group'] === 'Admin';
}

/**
 * Require admin access
 * 
 * @param string $redirect URL to redirect if not admin
 * @return void
 */
function requireAdmin(string $redirect = '../login/'): void {
    if (!isAdmin()) {
        redirect($redirect, 'Admin access required', 'error');
    }
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
 * Require login
 * 
 * @param string $redirect URL to redirect if not logged in
 * @return void
 */
function requireLogin(string $redirect = '../login/'): void {
    if (!isLoggedIn()) {
        redirect($redirect, 'Please login first', 'error');
    }
}

/**
 * Get current user ID from session
 * 
 * @return int|null User ID or null
 */
function getCurrentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user email from session
 * 
 * @return string|null Email or null
 */
function getCurrentUserEmail(): ?string {
    return $_SESSION['user_email'] ?? null;
}

/**
 * Get current user data
 * 
 * @return array|null Full user data or null
 */
function getCurrentUser(): ?array {
    $userId = getCurrentUserId();
    if (!$userId) {
        return null;
    }
    return getUserById($userId);
}

/**
 * Check if user has access to specific bundle
 * 
 * @param string $bundleName Bundle name to check
 * @return bool True if has access
 */
function hasBundleAccess(string $bundleName): bool {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    // Admin has access to all bundles
    if (isAdmin()) {
        return true;
    }
    
    // Check if user's bundle matches
    return isset($user['bundle_name']) && $user['bundle_name'] === $bundleName;
}

/**
 * Require bundle access
 * 
 * @param string $bundleName Bundle name required
 * @param string $redirect URL to redirect if no access
 * @return void
 */
function requireBundleAccess(string $bundleName, string $redirect = '../dashboard/'): void {
    if (!hasBundleAccess($bundleName)) {
        redirect($redirect, 'You do not have access to this bundle', 'error');
    }
}

/**
 * Generate remember me token
 * 
 * @param int $userId User ID
 * @return string Token
 */
function generateRememberToken(int $userId): string {
    $token = bin2hex(random_bytes(32));
    $hash = password_hash($token, PASSWORD_BCRYPT);
    
    // Store hash in database
    db()->execute(
        "UPDATE login SET remember_token = ? WHERE id = ?",
        [$hash, $userId]
    );
    
    return $token;
}

/**
 * Validate remember me token
 * 
 * @param int $userId User ID
 * @param string $token Token to validate
 * @return bool True if valid
 */
function validateRememberToken(int $userId, string $token): bool {
    $user = getUserById($userId);
    
    if (!$user || empty($user['remember_token'])) {
        return false;
    }
    
    return password_verify($token, $user['remember_token']);
}

/**
 * Clear remember me token
 * 
 * @param int $userId User ID
 * @return void
 */
function clearRememberToken(int $userId): void {
    db()->execute(
        "UPDATE login SET remember_token = NULL WHERE id = ?",
        [$userId]
    );
}

/**
 * Change user password
 * 
 * @param int $userId User ID
 * @param string $newPassword New plain text password
 * @return bool Success
 */
function changePassword(int $userId, string $newPassword): bool {
    $hash = hashPassword($newPassword);
    
    return db()->execute(
        "UPDATE login SET password = ? WHERE id = ?",
        [$hash, $userId]
    );
}

/**
 * Verify current password
 * 
 * @param int $userId User ID
 * @param string $currentPassword Current plain text password
 * @return bool True if correct
 */
function verifyCurrentPassword(int $userId, string $currentPassword): bool {
    $user = getUserById($userId);
    
    if (!$user || empty($user['password'])) {
        return false;
    }
    
    return password_verify($currentPassword, $user['password']) ||
           md5($currentPassword) === $user['password'];
}
?>
