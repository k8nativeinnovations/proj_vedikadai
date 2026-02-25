<?php
include 'config.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/* REMOVE ITEM */
if (isset($_GET['remove'])) {
    foreach ($_SESSION['cart'] as $k => $v) {
        if ($v['unique_id'] == $_GET['remove']) {
            unset($_SESSION['cart'][$k]);
        }
    }
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    header("Location: cart_checkout.php");
    exit();
}

/* CALCULATE TOTAL */
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['qty'];
}

/* PLACE ORDER */
$orderSuccess = false;

if (isset($_POST['place_order']) && count($_SESSION['cart']) > 0) {

    $name    = $_POST['name'];
    $email   = $_POST['email'];
    $phone   = $_POST['phone'];
    $pincode = $_POST['pincode'];
    $address = $_POST['address'];

    /* INSERT INTO ORDERS */
    $orderSql = "INSERT INTO orders
        (customer_name, email, phone, pincode, shipping_address, total_amount)
        VALUES (?, ?, ?, ?, ?, ?)";

    $orderStmt = mysqli_prepare($conn, $orderSql);
    if (!$orderStmt) {
        die("Order Prepare Failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $orderStmt,
        "sssssd",
        $name,
        $email,
        $phone,
        $pincode,
        $address,
        $total
    );

    mysqli_stmt_execute($orderStmt);

    $order_id = mysqli_insert_id($conn);
    mysqli_stmt_close($orderStmt);

    /* INSERT ORDER ITEMS */
    $itemSql = "INSERT INTO order_items
        (order_id, product_id, quantity, price)
        VALUES (?, ?, ?, ?)";

    $itemStmt = mysqli_prepare($conn, $itemSql);
    if (!$itemStmt) {
        die("Item Prepare Failed: " . mysqli_error($conn));
    }

    foreach ($_SESSION['cart'] as $item) {
        mysqli_stmt_bind_param(
            $itemStmt,
            "iiid",
            $order_id,
            $item['product_id'],
            $item['qty'],
            $item['price']
        );
        mysqli_stmt_execute($itemStmt);
    }

    mysqli_stmt_close($itemStmt);

    $_SESSION['cart'] = [];
    $orderSuccess = true;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Cart & Checkout - Murugan Vedikadai</title>

<style>
body{
    margin:0;
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg,#fff3d6,#ffd966);
}

.header{
    background:#8b0000;
    color:gold;
    padding:25px;
    text-align:center;
    font-size:36px;
    font-weight:bold;
}

.container{
    width:95%;
    max-width:1100px;
    margin:30px auto;
    background:white;
    padding:25px;
    border-radius:15px;
    box-shadow:0 10px 25px rgba(0,0,0,0.25);
}

table{
    width:100%;
    border-collapse:collapse;
    font-size:22px;
}

th{
    background:#c41e3d;
    color:white;
    padding:15px;
    font-size:24px;
}

td{
    padding:15px;
    border-bottom:1px solid #ddd;
    text-align:center;
}

.remove-btn{
    background:#dc3545;
    color:white;
    padding:10px 16px;
    text-decoration:none;
    border-radius:8px;
    font-size:18px;
}

.total-box{
    text-align:right;
    font-size:30px;
    color:#008000;
    margin-top:20px;
    font-weight:bold;
}

.checkout-title{
    text-align:center;
    font-size:32px;
    color:#8b0000;
    margin:30px 0 15px;
}

input, textarea{
    width:100%;
    padding:14px;
    font-size:20px;
    margin-bottom:15px;
    border-radius:8px;
    border:1px solid #ccc;
}

.place-btn{
    width:100%;
    background:#28a745;
    color:white;
    padding:18px;
    font-size:26px;
    border:none;
    border-radius:10px;
    cursor:pointer;
}

.place-btn:hover{
    background:#1e7e34;
}

.success{
    text-align:center;
    font-size:30px;
    color:green;
    font-weight:bold;
}

.back-btn{
    display:inline-block;
    margin-top:20px;
    background:#0057ff;
    color:white;
    padding:12px 20px;
    border-radius:8px;
    text-decoration:none;
    font-size:20px;
}
</style>
</head>

<body>

<div class="header">🛒 Your Shopping Cart</div>

<div class="container">

<?php if ($orderSuccess): ?>
    <div class="success">
        🎉 Order Placed Successfully! 🎉 <br><br>
        Thank you for shopping with us 🙏
        <br><br>
        <a href="index1.php" class="back-btn">🏠 Back to Home</a>
    </div>

<?php elseif (count($_SESSION['cart']) == 0): ?>
    <div class="success">
        Your cart is empty 😔 <br><br>
        <a href="index1.php" class="back-btn">🛍️ Shop Now</a>
    </div>

<?php else: ?>

<table>
<tr>
    <th>No</th>
    <th>Product</th>
    <th>Price</th>
    <th>Qty</th>
    <th>Total</th>
    <th>Action</th>
</tr>

<?php foreach ($_SESSION['cart'] as $i => $item): ?>
<tr>
    <td><?php echo $i+1; ?></td>
    <td><?php echo $item['name']; ?></td>
    <td>₹ <?php echo number_format($item['price'],2); ?></td>
    <td><?php echo $item['qty']; ?></td>
    <td>₹ <?php echo number_format($item['price']*$item['qty'],2); ?></td>
    <td>
        <a href="?remove=<?php echo $item['unique_id']; ?>" class="remove-btn">Remove</a>
    </td>
</tr>
<?php endforeach; ?>
</table>

<div class="total-box">
    Grand Total: ₹ <?php echo number_format($total,2); ?>
</div>

<div class="checkout-title">🚚 Delivery Details</div>

<form method="POST">
    <input type="text" name="name" placeholder="Full Name" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="text" name="phone" placeholder="Phone Number" required>
    <input type="text" name="pincode" placeholder="Pincode" required>
    <textarea name="address" placeholder="Full Address" rows="4" required></textarea>

    <button type="submit" name="place_order" class="place-btn">
        🧾 Place Order Now
    </button>
</form>

<a href="index1.php" class="back-btn">⬅ Continue Shopping</a>

<?php endif; ?>

</div>

</body>
</html>
