<?php
require_once '../../../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$concepts = json_decode($_POST['concepts'] ?? '[]', true);
$topics   = json_decode($_POST['topics']   ?? '[]', true);

$_SESSION['ngn_concept_filter'] = is_array($concepts) ? $concepts : [];
$_SESSION['ngn_topic_filter']   = is_array($topics)   ? $topics   : [];

// Force a brand-new exam attempt with the chosen filter
unset($_SESSION['ngn_exam_set']);
unset($_SESSION['current_ngn_examTaken']);

header('Location: index.php');
exit;
