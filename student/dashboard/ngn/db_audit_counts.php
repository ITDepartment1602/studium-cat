<?php
include '../../../config.php';
$t = ['highlight', 'btq', 'mmr', 'mpr', 'dragndrop', 'dropdown', 'sata', 'ngncolumn'];
foreach($t as $table) {
    if ($res = mysqli_query($con, "SELECT COUNT(*) as c FROM `$table`")) {
        $r = mysqli_fetch_assoc($res);
        echo "$table: " . $r['c'] . "\n";
    } else { echo "$table: TABLE MISSING\n"; }
}
?>
