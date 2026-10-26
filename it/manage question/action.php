<?php
// Add / delete standard (single-answer) questions (from manage question/questionlist.php).
session_start();
include('../../config.php');

// Add a question (one row per selected topic)
if (isset($_POST['submit'])) {
    $topics     = $_POST['topic'] ?? [];
    $concept    = $_POST['concept']    ?? '';
    $qnumber    = $_POST['qnumber']    ?? '';
    $question   = $_POST['question']   ?? '';
    $choiceA    = $_POST['choiceA']    ?? '';
    $choiceB    = $_POST['choiceB']    ?? '';
    $choiceC    = $_POST['choiceC']    ?? '';
    $choiceD    = $_POST['choiceD']    ?? '';
    $correctans = $_POST['correctans'] ?? '';
    $wrong      = $_POST['wrong']      ?? '1';
    $rationale  = $_POST['rationale']  ?? '';
    $narcan     = $_POST['narcan']     ?? '';
    $dlevel     = $_POST['dlevel']     ?? '';
    $cnc        = $_POST['cnc']        ?? '';
    $system     = $_POST['system']     ?? '';

    $stmt = mysqli_prepare(
        $con,
        "INSERT INTO question
           (topic, topics1, qnumber, question, choiceA, choiceB, choiceC, choiceD,
            correctans, wrong, rationale, narcan, dlevel, cnc, system)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
    );
    foreach ($topics as $topic) {
        mysqli_stmt_bind_param(
            $stmt, 'sssssssssssssss',
            $topic, $concept, $qnumber, $question,
            $choiceA, $choiceB, $choiceC, $choiceD,
            $correctans, $wrong, $rationale, $narcan, $dlevel, $cnc, $system
        );
        mysqli_stmt_execute($stmt);
    }
    header("Location: questionlist.php");
    exit;
}

// Delete a question
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = mysqli_prepare($con, "DELETE FROM question WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    header("Location: questionlist.php");
    exit;
}
