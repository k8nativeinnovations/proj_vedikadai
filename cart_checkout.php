<?php
include 'config.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/* BULK ADD FROM SHOP PAGE — rebuilds cart from submitted qty map */
if (isset($_POST['bulk_add_to_cart']) && isset($_POST['qty']) && is_array($_POST['qty'])) {
    $_SESSION['cart'] = [];
    foreach ($_POST['qty'] as $pid => $q) {
        $pid = (int)$pid;
        $q   = (int)$q;
        if ($pid <= 0 || $q <= 0) continue;

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, name_en, name_ta, new_price, image FROM products WHERE id = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "i", $pid);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($p = mysqli_fetch_assoc($res)) {
            $_SESSION['cart'][] = [
                'product_id' => (int)$p['id'],
                'name'       => $p['name_en'],
                'price'      => (float)$p['new_price'],
                'image'      => $p['image'],
                'qty'        => $q,
                'total'      => (float)$p['new_price'] * $q,
                'unique_id'  => time() . rand(100, 999) . $pid
            ];
        }
        mysqli_stmt_close($stmt);
    }
    header("Location: cart_checkout.php");
    exit();
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
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Cart updated successfully.'];
    header("Location: cart_checkout.php");
    exit();
}

/* PULL + CLEAR ONE-SHOT FLASH MESSAGE */
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/* CALCULATE TOTAL */
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['qty'];
}

/* Preserve form values across a failed POST.
   When the form has never been submitted yet, prefill with test defaults
   so the developer doesn't have to retype while testing locally. */
$form = [
    'name'    => $_POST['name']    ?? 'Vijesh',
    'email'   => $_POST['email']   ?? 'vijehskrishna115@gmail.com',
    'phone'   => $_POST['phone']   ?? '9008108650',
    'pincode' => $_POST['pincode'] ?? '560054',
    'address' => $_POST['address'] ?? 'mathikere, bangalore',
];

/* PLACE ORDER */
$orderSuccess = false;
$orderError   = null;
$orderData    = null;
$orderItems   = [];

if (isset($_POST['place_order'])) {

    if (count($_SESSION['cart']) === 0) {
        $orderError = "Your cart is empty. Please add items before placing an order.";
    } else {

    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $address = trim($_POST['address'] ?? '');

    /* Server-side validation */
    if ($name === '' || $email === '' || $phone === '' || $pincode === '' || $address === '') {
        $orderError = "Please fill in all delivery fields.";
    } elseif (!preg_match('/^[6-9][0-9]{9}$/', $phone)) {
        $orderError = "Phone number must be a 10-digit Indian mobile number.";
    } elseif (!preg_match('/^[0-9]{6}$/', $pincode)) {
        $orderError = "Pincode must be exactly 6 digits.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $orderError = "Please enter a valid email address.";
    } else {

    mysqli_begin_transaction($conn);
    try {
        $orderSql = "INSERT INTO orders
            (customer_name, email, phone, pincode, shipping_address, total_amount)
            VALUES (?, ?, ?, ?, ?, ?)";

        $orderStmt = mysqli_prepare($conn, $orderSql);
        if (!$orderStmt) {
            throw new Exception("Order prepare failed: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param(
            $orderStmt, "sssssd",
            $name, $email, $phone, $pincode, $address, $total
        );
        if (!mysqli_stmt_execute($orderStmt)) {
            throw new Exception("Order insert failed: " . mysqli_stmt_error($orderStmt));
        }
        $order_id = mysqli_insert_id($conn);
        mysqli_stmt_close($orderStmt);

        $itemSql = "INSERT INTO order_items
            (order_id, product_id, quantity, price)
            VALUES (?, ?, ?, ?)";
        $itemStmt = mysqli_prepare($conn, $itemSql);
        if (!$itemStmt) {
            throw new Exception("Item prepare failed: " . mysqli_error($conn));
        }

        $orderItems = $_SESSION['cart'];
        foreach ($_SESSION['cart'] as $item) {
            mysqli_stmt_bind_param(
                $itemStmt, "iiid",
                $order_id, $item['product_id'], $item['qty'], $item['price']
            );
            if (!mysqli_stmt_execute($itemStmt)) {
                throw new Exception("Item insert failed: " . mysqli_stmt_error($itemStmt));
            }
        }
        mysqli_stmt_close($itemStmt);

        mysqli_commit($conn);

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
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $orderError = $e->getMessage();
        error_log("[place_order] " . $orderError);
    }
    } // end else (validation passed)
    } // end else (cart not empty)
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

<?php if ($flash): ?>
<div class="flash-banner flash-<?php echo htmlspecialchars($flash['type']); ?>" role="status">
  <?php echo htmlspecialchars($flash['msg']); ?>
</div>
<?php endif; ?>

<?php if ($orderError): ?>
<div class="flash-banner flash-error" role="alert">
  <strong>Order could not be placed.</strong> <?php echo htmlspecialchars($orderError); ?>
</div>
<?php endif; ?>

<?php if ($orderSuccess && $orderData): ?>
<!-- ========== ORDER CONFIRMATION ========== -->
<div class="order-success">
  <div class="order-success-icon" aria-hidden="true">&#x1F389;</div>
  <h2>Order Placed Successfully!</h2>
  <p class="order-number">Order #<?php echo (int)$orderData['id']; ?></p>

  <?php $paymentQr = get_setting('payment_qr_image'); ?>
  <?php if ($paymentQr && file_exists($paymentQr)): ?>
  <div class="payment-qr-block">
    <h3>Scan to Pay <?php echo CURRENCY_SYMBOL . ' ' . number_format($orderData['total'], 2); ?></h3>
    <img src="<?php echo htmlspecialchars($paymentQr); ?>" alt="Payment QR Code" class="payment-qr-img">
    <p class="payment-qr-note">After payment, please share the screenshot with us on <a href="tel:+918610466629">+91 86104 66629</a>.</p>
  </div>
  <?php endif; ?>

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

  <?php if ($orderError): ?>
  <div class="flash-banner flash-error" role="alert" style="margin-bottom:18px;">
    <strong>Order could not be placed.</strong> <?php echo htmlspecialchars($orderError); ?>
  </div>
  <?php endif; ?>
  <?php if ($flash && $flash['type'] === 'success'): ?>
  <div class="flash-banner flash-success" role="status" style="margin-bottom:18px;">
    <?php echo htmlspecialchars($flash['msg']); ?>
  </div>
  <?php endif; ?>

  <form method="POST" id="checkout-form">
    <div class="form-row">
      <div class="form-group">
        <label for="checkout-name">Full Name</label>
        <input type="text" id="checkout-name" name="name" class="form-input" required
               autocomplete="name" minlength="2" placeholder="Your full name"
               value="<?php echo htmlspecialchars($form['name']); ?>">
      </div>
      <div class="form-group">
        <label for="checkout-email">Email</label>
        <input type="email" id="checkout-email" name="email" class="form-input" required
               autocomplete="email" placeholder="your@email.com"
               value="<?php echo htmlspecialchars($form['email']); ?>">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="checkout-phone">Phone Number</label>
        <input type="tel" id="checkout-phone" name="phone" class="form-input" required
               autocomplete="tel" pattern="[6-9][0-9]{9}" maxlength="10" inputmode="numeric"
               title="Enter a 10-digit mobile number starting with 6, 7, 8 or 9"
               placeholder="10-digit mobile number"
               value="<?php echo htmlspecialchars($form['phone']); ?>">
        <small class="form-hint">10 digits, starts with 6/7/8/9</small>
      </div>
      <div class="form-group">
        <label for="checkout-pincode">Pincode</label>
        <input type="text" id="checkout-pincode" name="pincode" class="form-input" required
               autocomplete="postal-code" pattern="[0-9]{6}" maxlength="6" inputmode="numeric"
               title="Enter a 6-digit pincode"
               placeholder="6-digit pincode"
               value="<?php echo htmlspecialchars($form['pincode']); ?>">
        <small class="form-hint">Must be exactly 6 digits</small>
      </div>
    </div>

    <div class="form-group">
      <label for="checkout-address">Full Address</label>
      <textarea id="checkout-address" name="address" class="form-input" required
                rows="3" minlength="10" autocomplete="street-address"
                placeholder="House no, street, area, city, state"><?php echo htmlspecialchars($form['address']); ?></textarea>
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

<!-- Footer -->
<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-col">
      <h3>Murugan Vedikadai</h3>
      <p class="footer-ta" lang="ta">திருச்செந்தூர் முருகன் வெடிகடை</p>
      <p>Quality crackers at the best prices for every celebration.</p>
    </div>
    <div class="footer-col">
      <h3>Contact</h3>
      <p><a href="tel:+918610466629">+91 86104 66629</a></p>
      <p><a href="mailto:thiruchendurmuruganvedikadai@gmail.com">thiruchendurmuruganvedikadai@gmail.com</a></p>
    </div>
    <div class="footer-col">
      <h3>Quick Links</h3>
      <ul class="footer-links">
        <li><a href="index.php">Shop</a></li>
        <li><a href="cart_checkout.php">Cart</a></li>
        <li><a href="admin_login.php">Admin</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; <?php echo date('Y'); ?> Thiruchendur Murugan Vedikadai. All rights reserved.</p>
  </div>
</footer>

<script>
/* Auto-scroll to any flash/error banner that came back with this page render */
(function() {
  var banner = document.querySelector('.flash-banner');
  if (banner) {
    window.scrollTo({ top: 0, behavior: 'auto' });
    banner.scrollIntoView({ block: 'start', behavior: 'smooth' });
  }
})();

/* Friendly validation + double-submit guard for Place Order */
(function() {
  var form = document.getElementById('checkout-form');
  if (!form) return;
  var btn = document.getElementById('place-order-btn');

  function fieldLabel(field) {
    var fg = field.closest('.form-group');
    if (!fg) return 'A field';
    var lbl = fg.querySelector('label');
    return lbl ? lbl.textContent.trim() : 'A field';
  }

  function flash(msg) {
    var existing = document.getElementById('client-flash');
    if (existing) existing.remove();
    var box = document.createElement('div');
    box.id = 'client-flash';
    box.className = 'flash-banner flash-error';
    box.setAttribute('role', 'alert');
    box.textContent = msg;
    var main = document.querySelector('main.container');
    if (main) main.insertBefore(box, main.firstChild);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  form.addEventListener('invalid', function(e) {
    e.preventDefault();
    var field = e.target;
    flash(fieldLabel(field) + ': ' + (field.title || field.validationMessage || 'please check this field.'));
    field.focus();
  }, true);

  form.addEventListener('submit', function(e) {
    if (!form.checkValidity()) { e.preventDefault(); return; }
    // Defer disabling so the button's name/value is included in the form data.
    setTimeout(function() {
      if (btn) {
        btn.disabled = true;
        btn.textContent = 'Placing Order...';
      }
    }, 0);
  });
})();

/* Auto-dismiss success flash after 4s */
(function() {
  var f = document.querySelector('.flash-success');
  if (!f) return;
  setTimeout(function() { f.style.transition = 'opacity .4s'; f.style.opacity = '0'; setTimeout(function(){ f.remove(); }, 400); }, 4000);
})();
</script>

</body>
</html>
