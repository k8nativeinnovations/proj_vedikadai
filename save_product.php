<?php
include 'config.php';

// Simple server-side validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin2.php");
    exit();
}

$name_en   = trim($_POST['name_en'] ?? '');
$name_ta   = trim($_POST['name_ta'] ?? '');
$pack      = trim($_POST['pack'] ?? '');
$old_price = floatval($_POST['old_price'] ?? 0);
$offer     = intval($_POST['offer'] ?? 0);
$new_price = floatval($_POST['new_price'] ?? 0);

$uploadDir = "uploads/";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$imagePath = "";
if (!empty($_FILES['image']['name'])) {
    $filename = time() . "_" . preg_replace('/[^a-zA-Z0-9_\.\-]/', '_', basename($_FILES['image']['name']));
    $imagePath = $uploadDir . $filename;
    move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
}

// Insert into DB using prepared statement
$stmt = mysqli_prepare($conn, "INSERT INTO products (name_en,name_ta,pack,old_price,offer,new_price,image) VALUES (?,?,?,?,?,?,?)");
if (!$stmt) {
    die("Prepare failed: " . mysqli_error($conn));
}

// types: s s s d i d s -> "sssdids"
mysqli_stmt_bind_param($stmt, "sssdids", $name_en, $name_ta, $pack, $old_price, $offer, $new_price, $imagePath);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

header("Location: admin2.php");
exit();
