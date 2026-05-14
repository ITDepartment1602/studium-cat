<?php
require_once '../../../config.php';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$table = isset($_GET['table']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['table']) : '';

if (!$id || !$table) { echo 'N/A'; exit; }

$row = db()->fetchOne("SELECT * FROM `$table` WHERE id = ? LIMIT 1", [$id]);
if (!$row) { echo 'N/A'; exit; }

$ans = '';
if (isset($row['correct'])) $ans = is_array(json_decode($row['correct'])) ? implode(', ', json_decode($row['correct'])) : $row['correct'];
elseif (isset($row['correctans'])) $ans = $row['correctans'];
elseif (isset($row['correct_words'])) $ans = $row['correct_words'];
elseif (isset($row['correct_pairs'])) $ans = $row['correct_pairs'];
elseif (isset($row['correctA']) || isset($row['correctC']) || isset($row['correctP'])) {
    $ans = "Act: " . ($row['correctA']??'') . " | Cond: " . ($row['correctC']??'') . " | Par: " . ($row['correctP']??'');
}
elseif (isset($row['actions']) && isset($row['conditions'])) { // Bowtie sometimes defined like this
    $ans = "See bowtie logic"; 
}
else {
    // If not found above, try typical JSON ones
    foreach (['correct_answers', 'correct_items'] as $col) {
        if (isset($row[$col])) {
            $ans = is_array(json_decode($row[$col])) ? implode(', ', json_decode($row[$col])) : $row[$col];
            break;
        }
    }
}

if (!$ans) $ans = 'Unknown format';
echo strip_tags($ans);
