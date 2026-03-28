<?php
include 'config.php';
session_start();

// Check current schema of temporary_exam_result table
$result = mysqli_query($con, "DESCRIBE temporary_exam_result");
echo "Current temporary_exam_result schema:\n";
echo str_repeat("=", 80) . "\n";
while($row = mysqli_fetch_assoc($result)) {
    echo sprintf("%-20s %-25s %-10s %-10s %-15s\n", 
        $row['Field'], 
        $row['Type'], 
        $row['Null'] ?? 'YES',
        $row['Key'] ?? '',
        $row['Extra'] ?? '');
}
?>
