<?php
// Exam Mode — In-exam feedback widget → admin inbox + email notification
require_once '../../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not authenticated']);
    exit;
}

$student_id = intval($_SESSION['user_id']);
$body = json_decode(file_get_contents('php://input'), true);

if (!is_array($body) || !isset($body['examTaken'])) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid payload']);
    exit;
}

$examTaken     = intval($body['examTaken']);
$question_uid  = isset($body['question_uid'])  ? trim((string)$body['question_uid'])  : null;
$question_type = isset($body['question_type']) ? trim((string)$body['question_type']) : null;
$category      = isset($body['category'])      ? trim((string)$body['category'])      : 'General';
$message       = isset($body['message'])       ? trim((string)$body['message'])       : '';

// Ownership: must match this student's current active exam attempt
if (!isset($_SESSION['current_exam_examTaken']) || intval($_SESSION['current_exam_examTaken']) !== $examTaken) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

$ok = db()->execute(
    "INSERT INTO exam_feedback (student_id, examTaken, question_uid, question_type, category, message)
     VALUES (?, ?, ?, ?, ?, ?)",
    [$student_id, $examTaken, $question_uid, $question_type, $category, $message]
);

if (!$ok) {
    http_response_code(500);
    echo json_encode(['error' => 'db_insert_failed']);
    exit;
}

// Best-effort email notification — never blocks the saved feedback on failure
try {
    require_once '../../../admin/PHPMailer/src/Exception.php';
    require_once '../../../admin/PHPMailer/src/PHPMailer.php';
    require_once '../../../admin/PHPMailer/src/SMTP.php';

    $student = db()->fetchOne("SELECT fullname FROM login WHERE id = ? LIMIT 1", [$student_id]);
    $fullname = $student['fullname'] ?? 'Unknown Student';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'nclexamplified@gmail.com';
    $mail->Password   = 'xpcw mohk pzyj mlux';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;
    $mail->setFrom('nclexamplified@gmail.com', 'Studium CAT Exam Mode');
    $mail->addAddress('nclexamplifiedtechnical@gmail.com');
    $mail->isHTML(true);
    $mail->Subject = "Exam Mode Feedback — {$category}";
    $mail->Body = "
        <p><strong>Student:</strong> " . htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8') . "</p>
        <p><strong>Exam Attempt:</strong> {$examTaken}</p>
        <p><strong>Question:</strong> " . htmlspecialchars($question_uid ?? 'N/A', ENT_QUOTES, 'UTF-8') . "</p>
        <p><strong>Category:</strong> " . htmlspecialchars($category, ENT_QUOTES, 'UTF-8') . "</p>
        <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . "</p>
    ";
    $mail->send();
} catch (Throwable $e) {
    error_log('exam feedback email notification failed: ' . $e->getMessage());
}

echo json_encode(['ok' => true]);
