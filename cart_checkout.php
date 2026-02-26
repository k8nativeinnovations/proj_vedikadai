<?php
include 'config.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/* REMOVE ITEM (with confirmation handled client-side) */
if (isset($_POST['remove_item'])) {
    $removeId = $_POST['remove_item'];
    foreach ($_SESSION['cart'] as $k => $v) {
        if ($v['unique_id'] == $removeId) {
            unset($_SESSION['cart'][$k]);
        }
    }
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    header("Location: cart_checkout.php");
    exit();
}

/* UPDATE QUANTITIES */
if (isset($_POST['update_cart'])) {
    foreach ($_SESSION['cart'] as &$item) {
        $key = 'qty_' . $item['unique_id'];
        if (isset($_POST[$key])) {
            $newQty = max(1, (int)$_POST[$key]);
            $item['qty'] = $newQty;
            $item['total'] = $item['price'] * $newQty;
        }
    }
    unset($item);
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
$orderData = null;
$orderItems = [];

if (isset($_POST['place_order']) && count($_SESSION['cart']) > 0) {

    $name    = trim($_POST['name']);
    $email   = trim($_POST['email']);
    $phone   = trim($_POST['phone']);
    $pincode = trim($_POST['pincode']);
    $address = trim($_POST['address']);

    /* INSERT INTO ORDERS */
    $orderSql = "INSERT INTO orders
        (customer_name, email, phone, pincode, shipping_address, total_amount)
        VALUES (?, ?, ?, ?, ?, ?)";

    $orderStmt = mysqli_prepare($conn, $orderSql);
    if (!$orderStmt) {
        die("Order Prepare Failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $orderStmt, "sssssd",
        $name, $email, $phone, $pincode, $address, $total
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

    // Save items for confirmation display
    $orderItems = $_SESSION['cart'];

    foreach ($_SESSION['cart'] as $item) {
        mysqli_stmt_bind_param(
            $itemStmt, "iiid",
            $order_id, $item['product_id'], $item['qty'], $item['price']
        );
        mysqli_stmt_execute($itemStmt);
    }

    mysqli_stmt_close($itemStmt);

    $orderData = [
        'id'      => $order_id,
        'name'    => $name,
        'email'   => $email,
        'phone'   => $phone,
        'pincode' => $pincode,
        'address' => $address,
        'total'   => $total
    ];

    $_SESSION['cart'] = [];
    $orderSuccess = true;
}

$cartCount = count($_SESSION['cart']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cart &amp; Checkout - Murugan Vedikadai</title>
<link rel="stylesheet" href="styles.css">
</head>

<body>

<!-- Navigation -->
<nav class="site-nav" aria-label="Main navigation">
  <div class="nav-inner">
    <a href="index.php" class="nav-brand" aria-label="Home - Thiruchendur Murugan Vedikadai">
      <img src="murugan_logo.png" alt="Murugan Vedikadai Logo">
      <span class="nav-brand-text">
        <span>Murugan Vedikadai</span>
        <span class="nav-brand-ta" lang="ta">முருகன் வெடிகடை</span>
      </span>
    </a>
    <div class="nav-links">
      <a href="index.php" class="nav-link nav-link--admin">Shop</a>
      <a href="cart_checkout.php" class="nav-link nav-link--cart" aria-label="Shopping cart, <?php echo $cartCount; ?> items">
        Cart <span class="cart-badge"><?php echo $cartCount; ?></span>
      </a>
    </div>
  </div>
</nav>

<main class="container">

<?php if ($orderSuccess && $orderData): ?>
<!-- ========== ORDER CONFIRMATION ========== -->
<div class="order-success">
  <div class="order-success-icon" aria-hidden="true">&#x1F389;</div>
  <h2>Order Placed Successfully!</h2>
  <p class="order-number">Order #<?php echo (int)$orderData['id']; ?></p>

  <!-- Order Summary -->
  <table class="order-summary-table">
    <thead>
      <tr>
        <th>Product</th>
        <th>Qty</th>
        <th>Price</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($orderItems as $item): ?>
      <tr>
        <td><?php echo htmlspecialchars($item['name']); ?></td>
        <td><?php echo (int)$item['qty']; ?></td>
        <td><?php echo CURRENCY_SYMBOL . ' ' . number_format($item['price'], 2); ?></td>
        <td><?php echo CURRENCY_SYMBOL . ' ' . number_format($item['price'] * $item['qty'], 2); ?></td>
      </tr>
      <?php endforeach; ?>
      <tr>
        <td colspan="3" style="text-align:right;font-weight:700;">Grand Total</td>
        <td style="font-weight:700;color:#28a745;"><?php echo CURRENCY_SYMBOL . ' ' . number_format($orderData['total'], 2); ?></td>
      </tr>
    </tbody>
  </table>

  <!-- Delivery Info -->
  <div class="order-delivery-info">
    <h3>Delivery Details</h3>
    <p><strong>Name:</strong> <?php echo htmlspecialchars($orderData['name']); ?></p>
    <p><strong>Phone:</strong> <?php echo htmlspecialchars($orderData['phone']); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($orderData['email']); ?></p>
    <p><strong>Pincode:</strong> <?php echo htmlspecialchars($orderData['pincode']); ?></p>
    <p><strong>Address:</strong> <?php echo htmlspecialchars($orderData['address']); ?></p>
  </div>

  <p style="color:#666;margin-bottom:20px;">
    For order inquiries, contact us at <a href="tel:+918610466629">+91 86104 66629</a>
  </p>

  <a href="index.php" class="btn btn--primary btn--lg">Continue Shopping</a>
</div>

<?php elseif ($cartCount === 0): ?>
<!-- ========== EMPTY CART ========== -->
<div class="empty-state">
  <div class="empty-state-icon" aria-hidden="true">&#x1F6D2;</div>
  <h2>Your cart is empty</h2>
  <p>Add some crackers to your cart and come back!</p>
  <a href="index.php" class="btn btn--primary btn--lg mt-2">Shop Now</a>
</div>

<?php else: ?>
<!-- ========== CART ITEMS ========== -->
<h2 style="margin:0 0 16px;font-size:1.3rem;color:#8b0000;">Your Cart (<?php echo $cartCount; ?> items)</h2>

<form method="POST" id="cart-form">
  <div class="cart-items">
    <?php foreach ($_SESSION['cart'] as $i => $item): ?>
    <div class="cart-item">
      <img src="<?php echo htmlspecialchars($item['image']); ?>"
           alt="<?php echo htmlspecialchars($item['name']); ?>"
           class="cart-item-img"
           onerror="this.onerror=null;this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 70 70%22><rect fill=%22%23f0f0f0%22 width=%2270%22 height=%2270%22/><text x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%23999%22 font-size=%2210%22>No Img</text></svg>'">

      <div>
        <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
        <div class="cart-item-price"><?php echo CURRENCY_SYMBOL . ' ' . number_format($item['price'], 2); ?> each</div>
      </div>

      <div class="cart-item-qty">
        <label for="qty_<?php echo $item['unique_id']; ?>" class="sr-only">Quantity</label>
        <input type="number" id="qty_<?php echo $item['unique_id']; ?>"
               name="qty_<?php echo $item['unique_id']; ?>"
               value="<?php echo (int)$item['qty']; ?>" min="1"
               class="qty-input"
               aria-label="Quantity for <?php echo htmlspecialchars($item['name']); ?>">
      </div>

      <div class="cart-item-total">
        <?php echo CURRENCY_SYMBOL . ' ' . number_format($item['price'] * $item['qty'], 2); ?>
      </div>

      <button type="submit" name="remove_item" value="<?php echo htmlspecialchars($item['unique_id']); ?>"
              class="btn btn--danger" onclick="return confirm('Remove this item from cart?')">
        Remove
      </button>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="cart-summary">
    <div class="cart-total-row">
      <span>Grand Total</span>
      <span class="cart-total-amount"><?php echo CURRENCY_SYMBOL . ' ' . number_format($total, 2); ?></span>
    </div>
    <button type="submit" name="update_cart" class="btn btn--info btn--block mt-2">Update Cart</button>
  </div>
</form>

<!-- ========== CHECKOUT FORM ========== -->
<section class="checkout-section">
  <h2>Delivery Details</h2>

  <form method="POST" id="checkout-form">
    <div class="form-row">
      <div class="form-group">
        <label for="checkout-name">Full Name</label>
        <input type="text" id="checkout-name" name="name" class="form-input" required
               autocomplete="name" minlength="2" placeholder="Your full name">
      </div>
      <div class="form-group">
        <label for="checkout-email">Email</label>
        <input type="email" id="checkout-email" name="email" class="form-input" required
               autocomplete="email" placeholder="your@email.com">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="checkout-phone">Phone Number</label>
        <input type="tel" id="checkout-phone" name="phone" class="form-input" required
               autocomplete="tel" pattern="[6-9][0-9]{9}" maxlength="10"
               placeholder="10-digit mobile number">
      </div>
      <div class="form-group">
        <label for="checkout-pincode">Pincode</label>
        <input type="text" id="checkout-pincode" name="pincode" class="form-input" required
               autocomplete="postal-code" pattern="[0-9]{6}" maxlength="6"
               placeholder="6-digit pincode">
      </div>
    </div>

    <div class="form-group">
      <label for="checkout-address">Full Address</label>
      <textarea id="checkout-address" name="address" class="form-input" required
                rows="3" minlength="10" autocomplete="street-address"
                placeholder="House no, street, area, city, state"></textarea>
    </div>

    <button type="submit" name="place_order" class="btn btn--primary btn--block btn--lg"
            id="place-order-btn">
      Place Order Now
    </button>
  </form>
</section>

<a href="index.php" class="btn btn--outline mt-3">Continue Shopping</a>

<?php endif; ?>

</main>

<script>
/* Prevent double-submit on Place Order */
(function() {
  var form = document.getElementById('checkout-form');
  if (!form) return;
  form.addEventListener('submit', function() {
    var btn = document.getElementById('place-order-btn');
    if (btn) {
      btn.disabled = true;
      btn.textContent = 'Placing Order...';
    }
  });
})();
</script>

</body>
</html>
