<?php
include 'config.php';
session_start();

// Add new columns to track omitted answers
$alterQueries = [
    "ALTER TABLE temporary_exam_result ADD COLUMN initial_answer TEXT NULL DEFAULT NULL COMMENT 'First answer selected by student'",
    "ALTER TABLE temporary_exam_result ADD COLUMN changes JSON NULL DEFAULT NULL COMMENT 'Change tracking: {added: [], removed: [], modified_count: n}'"
];

foreach ($alterQueries as $sql) {
    // Check if column already exists
    $column = strpos($sql, 'initial_answer') !== false ? 'initial_answer' : 'changes';
    
    $checkCol = mysqli_query($con, "SHOW COLUMNS FROM temporary_exam_result LIKE '$column'");
    
    if (mysqli_num_rows($checkCol) > 0) {
        echo "Column '$column' already exists.\n";
    } else {
        if (mysqli_query($con, $sql)) {
            echo "✅ Column '$column' added successfully!\n";
        } else {
            echo "❌ Error adding column '$column': " . mysqli_error($con) . "\n";
        }
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "Updated schema:\n";
echo str_repeat("=", 80) . "\n";

$result = mysqli_query($con, "DESCRIBE temporary_exam_result");
while($row = mysqli_fetch_assoc($result)) {
    if (in_array($row['Field'], ['initial_answer', 'changes', 'user_answer', 'correct_answer'])) {
        echo sprintf("%-20s %-25s\n", $row['Field'], $row['Type']);
    }
}
?>
