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
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Orders | Murugan Vedikadai</title>
<link rel="stylesheet" href="styles.css">
</head>

<body>

<!-- Admin Navigation -->
<nav class="admin-nav" aria-label="Admin navigation">
  <div class="admin-nav-inner">
    <span class="admin-nav-title">Murugan Vedikadai — Orders</span>
    <div class="admin-nav-links">
      <a href="admin2.php" class="nav-link">Dashboard</a>
      <a href="index.php" class="nav-link">Shop</a>
      <a href="logout.php" class="nav-link" style="background:var(--danger);">Logout</a>
    </div>
  </div>
</nav>

<main class="admin-container">

  <h2 style="margin:0 0 20px;font-size:1.3rem;color:var(--primary);">
    Customer Orders (<?php echo count($orders); ?>)
  </h2>

  <?php if (empty($orders)): ?>
  <div class="empty-state">
    <div class="empty-state-icon" aria-hidden="true">&#x1F4E6;</div>
    <h2>No orders yet</h2>
    <p>Customer orders will appear here once they start placing orders.</p>
  </div>
  <?php endif; ?>

  <?php foreach ($orders as $order): ?>
  <div class="order-card">

    <div class="order-card-header">
      <span class="order-card-id">Order #<?php echo (int)$order['id']; ?></span>
      <span class="order-card-date"><?php echo htmlspecialchars($order['order_date']); ?></span>
    </div>

    <div class="order-customer-info">
      <div><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></div>
      <div><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></div>
      <div><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></div>
      <div><strong>Pincode:</strong> <?php echo htmlspecialchars($order['pincode']); ?></div>
      <div style="grid-column: 1 / -1;"><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></div>
    </div>

    <!-- ORDER ITEMS -->
    <div style="overflow-x:auto;">
      <table class="order-items-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Image</th>
            <th>Product</th>
            <th>Qty</th>
            <th>Price</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $itemStmt = mysqli_prepare($conn,
            "SELECT oi.quantity, oi.price, p.name_en, p.name_ta, p.image
             FROM order_items oi
             JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = ?");
        mysqli_stmt_bind_param($itemStmt, "i", $order['id']);
        mysqli_stmt_execute($itemStmt);
        $itemRes = mysqli_stmt_get_result($itemStmt);
        $i = 1;

        while ($item = mysqli_fetch_assoc($itemRes)):
        ?>
          <tr>
            <td><?php echo $i++; ?></td>
            <td>
              <?php if ($item['image']): ?>
                <img src="<?php echo htmlspecialchars($item['image']); ?>"
                     alt="<?php echo htmlspecialchars($item['name_en']); ?>"
                     onerror="this.onerror=null;this.style.display='none'">
              <?php endif; ?>
            </td>
            <td>
              <?php echo htmlspecialchars($item['name_en']); ?>
              <br><small lang="ta" style="color:#666;"><?php echo htmlspecialchars($item['name_ta']); ?></small>
            </td>
            <td><?php echo (int)$item['quantity']; ?></td>
            <td><?php echo CURRENCY_SYMBOL . ' ' . number_format($item['price'], 2); ?></td>
            <td><?php echo CURRENCY_SYMBOL . ' ' . number_format($item['price'] * $item['quantity'], 2); ?></td>
          </tr>
        <?php endwhile;
        mysqli_stmt_close($itemStmt);
        ?>
        </tbody>
      </table>
    </div>

    <div class="order-total">
      Grand Total: <?php echo CURRENCY_SYMBOL . ' ' . number_format($order['total_amount'], 2); ?>
    </div>

  </div>
  <?php endforeach; ?>

</main>

</body>
</html>
