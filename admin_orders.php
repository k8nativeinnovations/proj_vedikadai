<?php
include 'config.php';

/* ADMIN AUTH CHECK */
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

/* FETCH ORDERS */
$orders = [];
$orderRes = mysqli_query($conn, "SELECT * FROM orders ORDER BY id DESC");

while ($row = mysqli_fetch_assoc($orderRes)) {
    $orders[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Admin Orders | Murugan Vedikadai</title>

<style>
body{
    margin:0;
    font-family: Arial, sans-serif;
    background:#fff7e6;
}

.header{
    background:#8b0000;
    color:gold;
    padding:25px;
    text-align:center;
    font-size:34px;
    font-weight:bold;
}

.container{
    width:95%;
    max-width:1300px;
    margin:30px auto;
    background:#fff;
    padding:25px;
    border-radius:16px;
    box-shadow:0 10px 25px rgba(0,0,0,0.25);
}

.order-box{
    border:2px solid #ddd;
    border-radius:14px;
    padding:20px;
    margin-bottom:30px;
}

.order-title{
    font-size:28px;
    color:#8b0000;
    font-weight:bold;
    margin-bottom:10px;
}

.order-info{
    font-size:20px;
    line-height:1.6;
    margin-bottom:15px;
}

table{
    width:100%;
    border-collapse:collapse;
    font-size:20px;
}

th{
    background:#c41e3d;
    color:white;
    padding:14px;
    font-size:22px;
}

td{
    padding:14px;
    border-bottom:1px solid #ddd;
    text-align:center;
}

.product-img{
    width:80px;
    height:80px;
    object-fit:cover;
    border-radius:10px;
    border:2px solid #ccc;
}

.total{
    text-align:right;
    font-size:26px;
    font-weight:bold;
    color:#008000;
    margin-top:10px;
}

.back-btn{
    display:inline-block;
    margin-bottom:20px;
    background:#0057ff;
    color:white;
    padding:12px 18px;
    border-radius:10px;
    text-decoration:none;
    font-size:20px;
}
</style>
</head>

<body>

<div class="header">📦 Customer Orders</div>

<div class="container">

<a href="admin2.php" class="back-btn">⬅ Back to Admin Panel</a>

<?php if (empty($orders)): ?>
    <p style="font-size:26px;text-align:center;">No orders found</p>
<?php endif; ?>

<?php foreach ($orders as $order): ?>

<div class="order-box">

    <div class="order-title">
        Order #<?php echo $order['id']; ?>
    </div>

    <div class="order-info">
        <b>Name:</b> <?php echo htmlspecialchars($order['customer_name']); ?><br>
        <b>Phone:</b> <?php echo htmlspecialchars($order['phone']); ?><br>
        <b>Email:</b> <?php echo htmlspecialchars($order['email']); ?><br>
        <b>Pincode:</b> <?php echo htmlspecialchars($order['pincode']); ?><br>
        <b>Address:</b> <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?><br>
        <b>Date:</b> <?php echo $order['order_date']; ?>
    </div>

    <!-- ORDER ITEMS -->
    <table>
        <tr>
            <th>No</th>
            <th>Image</th>
            <th>English Name</th>
            <th>Tamil Name</th>
            <th>Qty</th>
            <th>Price</th>
            <th>Total</th>
        </tr>

        <?php
        $itemSql = "
            SELECT 
                oi.quantity,
                oi.price,
                p.name_en,
                p.name_ta,
                p.image
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ".$order['id'];

        $itemRes = mysqli_query($conn, $itemSql);
        $i = 1;

        while ($item = mysqli_fetch_assoc($itemRes)):
        ?>
        <tr>
            <td><?php echo $i++; ?></td>
            <td>
                <?php if ($item['image']): ?>
                    <img src="<?php echo htmlspecialchars($item['image']); ?>" class="product-img">
                <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($item['name_en']); ?></td>
            <td><?php echo htmlspecialchars($item['name_ta']); ?></td>
            <td><?php echo $item['quantity']; ?></td>
            <td>₹ <?php echo number_format($item['price'],2); ?></td>
            <td>₹ <?php echo number_format($item['price'] * $item['quantity'],2); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <div class="total">
        Grand Total: ₹ <?php echo number_format($order['total_amount'],2); ?>
    </div>

</div>

<?php endforeach; ?>

</div>

</body>
</html>
