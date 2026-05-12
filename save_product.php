<?php
include 'config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin2.php");
    exit();
}

$id        = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$name_en   = trim($_POST['name_en'] ?? '');
$name_ta   = trim($_POST['name_ta'] ?? '');
$pack      = trim($_POST['pack'] ?? '');
$old_price = floatval($_POST['old_price'] ?? 0);
$offer     = intval($_POST['offer'] ?? 0);
$new_price = floatval($_POST['new_price'] ?? 0);

$uploadDir = "uploads/";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

/* Handle (optional) new image upload */
$imagePath = "";
if (!empty($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
    $filename = time() . "_" . preg_replace('/[^a-zA-Z0-9_\.\-]/', '_', basename($_FILES['image']['name']));
    $imagePath = $uploadDir . $filename;
    move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
}

if ($id > 0) {
    /* UPDATE existing product */
    if ($imagePath !== "") {
        // New image uploaded — delete old image file, update everything
        $old = mysqli_prepare($conn, "SELECT image FROM products WHERE id = ?");
        mysqli_stmt_bind_param($old, "i", $id);
        mysqli_stmt_execute($old);
        $r = mysqli_stmt_get_result($old);
        if ($row = mysqli_fetch_assoc($r)) {
            if (!empty($row['image']) && file_exists($row['image'])) @unlink($row['image']);
        }
        mysqli_stmt_close($old);

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE products SET name_en=?, name_ta=?, pack=?, old_price=?, offer=?, new_price=?, image=? WHERE id=?"
        );
        mysqli_stmt_bind_param($stmt, "sssdidsi",
            $name_en, $name_ta, $pack, $old_price, $offer, $new_price, $imagePath, $id);
    } else {
        // Keep existing image
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE products SET name_en=?, name_ta=?, pack=?, old_price=?, offer=?, new_price=? WHERE id=?"
        );
        mysqli_stmt_bind_param($stmt, "sssdidi",
            $name_en, $name_ta, $pack, $old_price, $offer, $new_price, $id);
    }

    if (!$stmt || !mysqli_stmt_execute($stmt)) {
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Update failed: ' . mysqli_error($conn)];
    } else {
        $_SESSION['admin_flash'] = ['type' => 'success', 'msg' => 'Product updated.'];
    }
    if ($stmt) mysqli_stmt_close($stmt);
} else {
    /* INSERT new product */
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO products (name_en,name_ta,pack,old_price,offer,new_price,image) VALUES (?,?,?,?,?,?,?)"
    );
    if (!$stmt) {
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Insert prepare failed: ' . mysqli_error($conn)];
        header("Location: admin2.php");
        exit();
    }
    mysqli_stmt_bind_param($stmt, "sssdids",
        $name_en, $name_ta, $pack, $old_price, $offer, $new_price, $imagePath);
    if (!mysqli_stmt_execute($stmt)) {
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Insert failed: ' . mysqli_stmt_error($stmt)];
    } else {
        $_SESSION['admin_flash'] = ['type' => 'success', 'msg' => 'Product added.'];
    }
    mysqli_stmt_close($stmt);
}

header("Location: admin2.php");
exit();
