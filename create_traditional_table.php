<?php
include 'config.php';
session_start();

$sql = "CREATE TABLE IF NOT EXISTS traditional (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question TEXT,
    choices JSON,
    correct VARCHAR(10),
    rationale TEXT,
    topic VARCHAR(100),
    system VARCHAR(100),
    cnc VARCHAR(100),
    dlevel VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($con, $sql)) {
    echo "SUCCESS: 'traditional' table created successfully!\n";
    echo "Schema: id, question, choices (JSON), correct, rationale, topic, system, cnc, dlevel\n";
} else {
    echo "ERROR: " . mysqli_error($con);
}
?>
