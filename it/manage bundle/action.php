<?php
// Add / delete bundles and bundle contents (from manage bundle/index.php and bundlelist.php).
session_start();
include('../../config.php');

// Add a bundle
if (isset($_POST['submit'])) {
    $stmt = mysqli_prepare($con, "INSERT INTO bundle (bundle_name) VALUES (?)");
    mysqli_stmt_bind_param($stmt, 's', $_POST['bundle_name']);
    mysqli_stmt_execute($stmt);
    header("Location: index.php");
    exit;
}

// Add topics to a bundle
if (isset($_POST['createpackage'])) {
    $bundle_name = $_POST['bundle_name'];
    $items = $_POST['bundlelist_name'] ?? [];
    $stmt = mysqli_prepare($con, "INSERT INTO bundlelist (bundle_name, bundlelist_name) VALUES (?, ?)");
    foreach ($items as $item) {
        mysqli_stmt_bind_param($stmt, 'ss', $bundle_name, $item);
        mysqli_stmt_execute($stmt);
    }
    $_SESSION['status'] = "Courses Inserted";
    header("Location: bundlelist.php?bundle_name=" . urlencode($bundle_name) . "&status=added");
    exit;
}

// Delete: scope=item removes a single topic from a bundle; otherwise remove the bundle itself.
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if (($_GET['scope'] ?? '') === 'item') {
        $stmt = mysqli_prepare($con, "DELETE FROM bundlelist WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        header("Location: bundlelist.php?bundle_name=" . urlencode($_GET['bundle_name'] ?? ''));
        exit;
    }
    $stmt = mysqli_prepare($con, "DELETE FROM bundle WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
}

header("Location: index.php");
exit;
