<?php
// Add / delete SATA (select-all-that-apply) questions (from manage question/questionlistsata.php).
session_start();
include('../../config.php');

// Add a SATA question (one row per selected topic)
if (isset($_POST['submit'])) {
    $topics    = $_POST['topic'] ?? [];
    $concept   = $_POST['concept']   ?? '';
    $qnumber   = $_POST['qnumber']   ?? '1';
    $question  = $_POST['question']  ?? '';
    $wrong     = $_POST['wrong']     ?? '';
    $rationale = $_POST['rationale'] ?? '';
    $narcan    = $_POST['narcan']    ?? '';
    $dlevel    = $_POST['dlevel']    ?? '';
    $cnc       = $_POST['cnc']       ?? '';
    $system    = $_POST['system']    ?? '';
    $type      = 'SATA';

    $options_json = json_encode($_POST['options'] ?? []);

    // Correct answers: "0, 2, 3" -> JSON array of ints
    $correctans = array_map('trim', explode(',', $_POST['correctans'] ?? ''));
    $correctans_json = json_encode(array_map('intval', $correctans));

    $stmt = mysqli_prepare(
        $con,
        "INSERT INTO question
           (topic, topics1, qnumber, question, options, correctans, wrong,
            rationale, narcan, dlevel, cnc, system, type)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
    );
    foreach ($topics as $topic) {
        mysqli_stmt_bind_param(
            $stmt, 'sssssssssssss',
            $topic, $concept, $qnumber, $question,
            $options_json, $correctans_json, $wrong,
            $rationale, $narcan, $dlevel, $cnc, $system, $type
        );
        mysqli_stmt_execute($stmt);
    }
    header("Location: questionlistsata.php");
    exit;
}

// Delete a question
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = mysqli_prepare($con, "DELETE FROM question WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    header("Location: questionlistsata.php");
    exit;
}
