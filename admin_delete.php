<?php 
/**
 * Admin Delete User
 * 
 * DELETE endpoint for removing users. Uses new secure architecture.
 */

require_once __DIR__ . '/../config.php';

// Require admin access
requireAdmin();

// Get and validate user ID
$userId = getInt('id');

if (!$userId) {
    redirect('../admin', 'Invalid user ID', 'error');
}

// Check if user exists before deleting
$user = getUserById($userId);

if (!$user) {
    redirect('../admin', 'User not found', 'error');
}

// Prevent deleting self
if ($userId === getCurrentUserId()) {
    redirect('../admin', 'Cannot delete yourself', 'error');
}

// Perform deletion
if (deleteUser($userId)) {
    logActivity('delete_user', getCurrentUserId(), "Deleted user: {$user['email']}");
    redirect('../admin', 'User deleted successfully', 'success');
} else {
    redirect('../admin', 'Failed to delete user', 'error');
}
?>