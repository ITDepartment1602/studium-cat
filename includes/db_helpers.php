<?php
/**
 * Common Database Operations
 * 
 * Reusable functions for common database operations across the application.
 */

/**
 * Get user by ID
 * 
 * @param int $userId User ID
 * @return array|null User data or null
 */
function getUserById(int $userId): ?array {
    return db()->fetchOne("SELECT * FROM login WHERE id = ?", [$userId]);
}

/**
 * Get user by email
 * 
 * @param string $email User email
 * @return array|null User data or null
 */
function getUserByEmail(string $email): ?array {
    return db()->fetchOne("SELECT * FROM login WHERE email = ?", [$email]);
}

/**
 * Check if email exists
 * 
 * @param string $email Email to check
 * @return bool True if exists
 */
function emailExists(string $email): bool {
    $result = db()->fetchOne("SELECT id FROM login WHERE email = ? LIMIT 1", [$email]);
    return $result !== null;
}

/**
 * Update user last login time
 * 
 * @param int $userId User ID
 * @return bool Success
 */
function updateLastLogin(int $userId): bool {
    return db()->execute(
        "UPDATE login SET lastlogin = NOW() WHERE id = ?",
        [$userId]
    );
}

/**
 * Get user exam history
 * 
 * @param string $email User email
 * @param string|null $bundleName Optional bundle filter
 * @return array Exam history
 */
function getUserExamHistory(string $email, ?string $bundleName = null): array {
    if ($bundleName) {
        return db()->fetchAll(
            "SELECT * FROM history WHERE email = ? AND kilanlan = ? ORDER BY date DESC",
            [$email, $bundleName]
        );
    }
    return db()->fetchAll(
        "SELECT * FROM history WHERE email = ? ORDER BY date DESC",
        [$email]
    );
}

/**
 * Get user's total correct answers
 * 
 * @param string $email User email
 * @return int Total correct answers
 */
function getUserTotalCorrect(string $email): int {
    $result = db()->fetchOne(
        "SELECT SUM(sahi) as total FROM history WHERE email = ?",
        [$email]
    );
    return $result['total'] ?? 0;
}

/**
 * Get all active students (not expired)
 * 
 * @return array List of active students
 */
function getActiveStudents(): array {
    return db()->fetchAll(
        "SELECT * FROM login WHERE dateexpired IS NOT NULL AND dateexpired != '' AND dateexpired > NOW()"
    );
}

/**
 * Get expired students
 * 
 * @return array List of expired students
 */
function getExpiredStudents(): array {
    return db()->fetchAll(
        "SELECT * FROM login WHERE dateexpired IS NOT NULL AND dateexpired != '' AND dateexpired < NOW()"
    );
}

/**
 * Get students without expiration (not activated)
 * 
 * @return array List of students
 */
function getNotActivatedStudents(): array {
    return db()->fetchAll(
        "SELECT * FROM login WHERE (dateexpired IS NULL OR dateexpired = '') AND status = 'user'"
    );
}

/**
 * Count total students
 * 
 * @return int Total count
 */
function countTotalStudents(): int {
    $result = db()->fetchOne("SELECT COUNT(*) as count FROM login");
    return $result['count'] ?? 0;
}

/**
 * Count total questions in quiz database
 * 
 * @return int Total count
 */
function countTotalQuestions(): int {
    $result = db()->fetchOne("SELECT COUNT(*) as count FROM question", [], true);
    return $result['count'] ?? 0;
}

/**
 * Count questions by topic/system
 * 
 * @param string $system Topic/system name
 * @return int Count
 */
function countQuestionsBySystem(string $system): int {
    $result = db()->fetchOne(
        "SELECT COUNT(*) as count FROM question WHERE system = ?",
        [$system],
        true
    );
    return $result['count'] ?? 0;
}

/**
 * Get question by ID
 * 
 * @param int $questionId Question ID
 * @return array|null Question data
 */
function getQuestionById(int $questionId): ?array {
    return db()->fetchOne(
        "SELECT * FROM question WHERE id = ?",
        [$questionId],
        true
    );
}

/**
 * Get random questions by topic
 * 
 * @param string $topic Topic name
 * @param int $limit Number of questions
 * @return array Questions
 */
function getRandomQuestionsByTopic(string $topic, int $limit = 10): array {
    return db()->fetchAll(
        "SELECT * FROM question WHERE topic = ? ORDER BY RAND() LIMIT ?",
        [$topic, $limit],
        true
    );
}

/**
 * Save exam result
 * 
 * @param array $data Exam result data
 * @return bool Success
 */
function saveExamResult(array $data): bool {
    return db()->execute(
        "INSERT INTO history (email, eid, score, sahi, wrong, date, kilanlan) VALUES (?, ?, ?, ?, ?, NOW(), ?)",
        [
            $data['email'],
            $data['eid'],
            $data['score'],
            $data['sahi'],
            $data['wrong'],
            $data['kilanlan']
        ]
    );
}

/**
 * Delete user by ID
 * 
 * @param int $userId User ID
 * @return bool Success
 */
function deleteUser(int $userId): bool {
    return db()->execute("DELETE FROM login WHERE id = ?", [$userId]);
}

/**
 * Delete exam mode entries for user
 * 
 * @param string $email User email
 * @param string|null $bundle Bundle name filter
 * @return bool Success
 */
function deleteExamModeEntries(string $email, ?string $bundle = null): bool {
    if ($bundle) {
        return db()->execute(
            "DELETE FROM exam_mode WHERE email = ? AND kilanlan = ?",
            [$email, $bundle],
            true
        );
    }
    return db()->execute(
        "DELETE FROM exam_mode WHERE email = ?",
        [$email],
        true
    );
}

/**
 * Get all notes for a user
 * 
 * @param int $loginId Login ID
 * @return array Notes
 */
function getUserNotes(int $loginId): array {
    return db()->fetchAll(
        "SELECT * FROM tbl_notes WHERE login_id = ? ORDER BY created_at DESC",
        [$loginId],
        true
    );
}

/**
 * Delete note by ID
 * 
 * @param int $noteId Note ID
 * @return bool Success
 */
function deleteNote(int $noteId): bool {
    return db()->execute(
        "DELETE FROM tbl_notes WHERE tbl_notes_id = ?",
        [$noteId],
        true
    );
}

/**
 * Check if user has valid subscription
 * 
 * @param int $userId User ID
 * @return bool True if subscription is valid
 */
function hasValidSubscription(int $userId): bool {
    $user = getUserById($userId);
    if (!$user || empty($user['dateexpired'])) {
        return false;
    }
    return strtotime($user['dateexpired']) > time();
}

/**
 * Update user subscription
 * 
 * @param int $userId User ID
 * @param string $expirationDate New expiration date (Y-m-d format)
 * @return bool Success
 */
function updateSubscription(int $userId, string $expirationDate): bool {
    return db()->execute(
        "UPDATE login SET dateexpired = ? WHERE id = ?",
        [$expirationDate, $userId]
    );
}

/**
 * Log activity (for audit trail)
 * 
 * @param string $action Action performed
 * @param int|null $userId User who performed action
 * @param string|null $details Additional details
 * @return bool Success
 */
function logActivity(string $action, ?int $userId = null, ?string $details = null): bool {
    return db()->execute(
        "INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())",
        [$userId, $action, $details]
    );
}

/**
 * Get activity log
 * 
 * @param int $limit Number of entries
 * @return array Activity log entries
 */
function getActivityLog(int $limit = 50): array {
    return db()->fetchAll(
        "SELECT * FROM activity_log ORDER BY created_at DESC LIMIT ?",
        [$limit]
    );
}
?>
