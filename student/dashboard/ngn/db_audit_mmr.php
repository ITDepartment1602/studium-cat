<?php
include '../../../config.php';
$q = mysqli_query($con, "SELECT correct FROM mmr WHERE id=6");
$r = mysqli_fetch_assoc($q);
echo "###JSON###" . $r['correct'] . "###JSON###";
?>
