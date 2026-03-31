<?php
require_once '../../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not authenticated']);
    exit;
}

$student_id = intval($_SESSION['user_id']);
$body = json_decode(file_get_contents('php://input'), true);

if (!$body || !isset($body['examTaken'])) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid payload']);
    exit;
}

$examTaken = intval($body['examTaken']);

mysqli_begin_transaction($con);

try {
    // Delete from temporary_exam_result (Drop the answers)
    $del_result = mysqli_prepare($con, "DELETE FROM temporary_exam_result WHERE student_id = ? AND examTaken = ?");
    mysqli_stmt_bind_param($del_result, 'ii', $student_id, $examTaken);
    mysqli_stmt_execute($del_result);
    mysqli_stmt_close($del_result);

    // Delete from temporary_exam_state (Drop the state)
    $del_state = mysqli_prepare($con, "DELETE FROM temporary_exam_state WHERE student_id = ? AND examTaken = ?");
    mysqli_stmt_bind_param($del_state, 'ii', $student_id, $examTaken);
    mysqli_stmt_execute($del_state);
    mysqli_stmt_close($del_state);

    // Commit Transaction
    mysqli_commit($con);
    
    // Clear session variables
    if (isset($_SESSION['current_ngn_examTaken'])) {
        unset($_SESSION['current_ngn_examTaken']);
    }
    if (isset($_SESSION['ngn_exam_set'])) {
        unset($_SESSION['ngn_exam_set']);
    }

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    mysqli_rollback($con);
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
