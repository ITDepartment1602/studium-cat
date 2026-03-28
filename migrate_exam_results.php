<?php
include 'config.php';
session_start();

// Check if exam_results table exists and add columns if needed
$checkTable = mysqli_query($con, "SHOW TABLES LIKE 'exam_results'");
if (mysqli_num_rows($checkTable) > 0) {
    // Table exists, check if columns exist
    $checkCol1 = mysqli_query($con, "SHOW COLUMNS FROM exam_results LIKE 'initial_answer'");
    $checkCol2 = mysqli_query($con, "SHOW COLUMNS FROM exam_results LIKE 'changes'");
    
    if (mysqli_num_rows($checkCol1) == 0) {
        if (mysqli_query($con, "ALTER TABLE exam_results ADD COLUMN initial_answer TEXT NULL DEFAULT NULL")) {
            echo "✅ initial_answer column added to exam_results\n";
        } else {
            echo "❌ Error: " . mysqli_error($con) . "\n";
        }
    } else {
        echo "✓ initial_answer already exists\n";
    }
    
    if (mysqli_num_rows($checkCol2) == 0) {
        if (mysqli_query($con, "ALTER TABLE exam_results ADD COLUMN changes JSON NULL DEFAULT NULL")) {
            echo "✅ changes column added to exam_results\n";
        } else {
            echo "❌ Error: " . mysqli_error($con) . "\n";
        }
    } else {
        echo "✓ changes already exists\n";
    }
} else {
    echo "⚠️ exam_results table does not exist yet\n";
}
?>
