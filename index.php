<?php
include 'config.php';

/* ---------- INITIALIZE CART ---------- */
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/* ---------- FETCH PRODUCTS ---------- */
$data = [];
$result = mysqli_query($conn, "SELECT * FROM products ORDER BY id ASC");
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

/* ---------- MAP EXISTING CART QTYS BY PRODUCT ---------- */
$cartQtyMap = [];
$cartItemCount = 0;
foreach ($_SESSION['cart'] as $item) {
    $pid = (int)$item['product_id'];
    $cartQtyMap[$pid] = ($cartQtyMap[$pid] ?? 0) + (int)$item['qty'];
    $cartItemCount += (int)$item['qty'];
}

$cartCount = count($_SESSION['cart']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Thiruchendur Murugan Vedikadai</title>
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
      <a href="cart_checkout.php" class="nav-link nav-link--cart" aria-label="Shopping cart, <?php echo $cartCount; ?> items">
        Cart <span class="cart-badge" id="cart-badge"><?php echo $cartCount; ?></span>
      </a>
      <a href="admin_login.php" class="nav-link nav-link--admin">Admin</a>
    </div>
  </div>
</nav>

<!-- Hero Header -->
<header class="hero">
  <img src="murugan_logo.png" alt="Thiruchendur Murugan Vedikadai" class="hero-logo">
  <h1 lang="ta">திருச்செந்தூர் முருகன் வெடிகடை</h1>
  <p class="hero-subtitle">Thiruchendur Murugan Vedikadai</p>
  <div class="hero-contact">
    <a href="tel:+918610466629">+91 86104 66629</a>
    <a href="mailto:thiruchendurmuruganvedikadai@gmail.com">thiruchendurmuruganvedikadai@gmail.com</a>
  </div>
</header>

<!-- Announcement Bar -->
<div class="announce-bar" role="banner">
  Welcome to Thiruchendur Murugan Vedikadai — Quality crackers at the best prices!
</div>

<?php if (count($data) > 0): ?>
<!-- Sticky Top Total Bar -->
<div class="total-bar total-bar--top" id="total-bar-top">
  <div class="total-bar-inner">
    <span class="total-bar-items"><strong id="top-items-count">0</strong> items</span>
    <span class="total-bar-amount">Total: <strong><?php echo CURRENCY_SYMBOL; ?> <span id="top-total-amount">0.00</span></strong></span>
    <button type="submit" form="bulk-order-form" class="btn btn--primary total-bar-btn" id="top-view-cart" disabled>
      View Cart
    </button>
  </div>
</div>
<?php endif; ?>

<main class="container">

  <?php if (count($data) === 0): ?>
  <!-- Empty State -->
  <div class="empty-state">
    <div class="empty-state-icon" aria-hidden="true">&#x1F9E8;</div>
    <h2>Products are being updated</h2>
    <p>Please check back soon! We're adding new products to our collection.</p>
  </div>

  <?php else: ?>

  <!-- Search Bar -->
  <div class="search-bar mb-2">
    <input type="search" class="search-input" id="product-search"
           placeholder="Search products by name..." aria-label="Search products by name">
    <span class="product-count" id="product-count"><?php echo count($data); ?> products</span>
  </div>

  <!-- Bulk Order Form: one form wraps all products -->
  <form id="bulk-order-form" action="cart_checkout.php" method="POST">
    <input type="hidden" name="bulk_add_to_cart" value="1">

    <div class="product-grid" id="product-grid">
      <?php foreach ($data as $item):
        $pid = (int)$item['id'];
        $existingQty = (int)($cartQtyMap[$pid] ?? 0);
      ?>
      <article class="product-card" data-name="<?php echo htmlspecialchars(strtolower($item['name_en'] . ' ' . $item['name_ta'])); ?>">
        <img src="<?php echo htmlspecialchars($item['image']); ?>"
             alt="<?php echo htmlspecialchars($item['name_en']); ?>"
             class="product-card-img"
             loading="lazy"
             onerror="this.onerror=null;this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 200 200%22><rect fill=%22%23f0f0f0%22 width=%22200%22 height=%22200%22/><text x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%23999%22 font-size=%2214%22>No Image</text></svg>'">

        <div class="product-card-body">
          <h3 class="product-card-name"><?php echo htmlspecialchars($item['name_en']); ?></h3>
          <p class="product-card-name-ta" lang="ta"><?php echo htmlspecialchars($item['name_ta']); ?></p>

          <div class="product-card-prices">
            <span class="price-old"><?php echo CURRENCY_SYMBOL . ' ' . number_format($item['old_price'], 2); ?></span>
            <span class="price-offer"><?php echo intval($item['offer']); ?>% OFF</span>
            <span class="price-new"><?php echo CURRENCY_SYMBOL . ' ' . number_format($item['new_price'], 2); ?></span>
          </div>

          <div class="qty-stepper">
            <button type="button" class="qty-btn qty-minus" aria-label="Decrease quantity" data-target="qty-<?php echo $pid; ?>">&minus;</button>
            <label for="qty-<?php echo $pid; ?>" class="sr-only">Quantity</label>
            <input type="number"
                   id="qty-<?php echo $pid; ?>"
                   name="qty[<?php echo $pid; ?>]"
                   class="qty-input product-qty"
                   min="0"
                   value="<?php echo $existingQty; ?>"
                   data-price="<?php echo (float)$item['new_price']; ?>"
                   aria-label="Quantity for <?php echo htmlspecialchars($item['name_en']); ?>">
            <button type="button" class="qty-btn qty-plus" aria-label="Increase quantity" data-target="qty-<?php echo $pid; ?>">+</button>
          </div>

          <div class="product-card-subtotal" id="subtotal-<?php echo $pid; ?>" aria-live="polite">
            <?php if ($existingQty > 0): ?>
              Subtotal: <strong><?php echo CURRENCY_SYMBOL . ' ' . number_format($item['new_price'] * $existingQty, 2); ?></strong>
            <?php endif; ?>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </form>

  <?php endif; ?>

</main>

<?php if (count($data) > 0): ?>
<!-- Sticky Bottom Total Bar (also visible on mobile) -->
<div class="total-bar total-bar--bottom" id="total-bar-bottom">
  <div class="total-bar-inner">
    <span class="total-bar-items"><strong id="bottom-items-count">0</strong> items</span>
    <span class="total-bar-amount">Total: <strong><?php echo CURRENCY_SYMBOL; ?> <span id="bottom-total-amount">0.00</span></strong></span>
    <button type="submit" form="bulk-order-form" class="btn btn--primary total-bar-btn" id="bottom-view-cart" disabled>
      View Cart
    </button>
  </div>
</div>
<?php endif; ?>

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
(function() {
  var form = document.getElementById('bulk-order-form');
  if (!form) return;

  var qtyInputs = form.querySelectorAll('.product-qty');
  var topItems   = document.getElementById('top-items-count');
  var topTotal   = document.getElementById('top-total-amount');
  var topBtn     = document.getElementById('top-view-cart');
  var botItems   = document.getElementById('bottom-items-count');
  var botTotal   = document.getElementById('bottom-total-amount');
  var botBtn     = document.getElementById('bottom-view-cart');

  function formatMoney(n) {
    return n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }

  function recalc() {
    var totalItems = 0;
    var totalAmt = 0;
    qtyInputs.forEach(function(inp) {
      var q = parseInt(inp.value, 10);
      if (isNaN(q) || q < 0) q = 0;
      var price = parseFloat(inp.getAttribute('data-price')) || 0;
      totalItems += q;
      totalAmt += price * q;

      var pid = inp.id.replace('qty-', '');
      var sub = document.getElementById('subtotal-' + pid);
      if (sub) {
        if (q > 0) {
          sub.innerHTML = 'Subtotal: <strong><?php echo CURRENCY_SYMBOL; ?> ' + formatMoney(price * q) + '</strong>';
        } else {
          sub.innerHTML = '';
        }
      }

      // Highlight active cards
      var card = inp.closest('.product-card');
      if (card) {
        if (q > 0) card.classList.add('is-active'); else card.classList.remove('is-active');
      }
    });

    if (topItems) topItems.textContent = totalItems;
    if (topTotal) topTotal.textContent = formatMoney(totalAmt);
    if (botItems) botItems.textContent = totalItems;
    if (botTotal) botTotal.textContent = formatMoney(totalAmt);

    var disabled = totalItems === 0;
    if (topBtn) topBtn.disabled = disabled;
    if (botBtn) botBtn.disabled = disabled;
  }

  // Stepper buttons (event delegation)
  form.addEventListener('click', function(e) {
    var target = e.target;
    if (!target.classList.contains('qty-plus') && !target.classList.contains('qty-minus')) return;
    var id = target.getAttribute('data-target');
    var inp = document.getElementById(id);
    if (!inp) return;
    var q = parseInt(inp.value, 10);
    if (isNaN(q) || q < 0) q = 0;
    if (target.classList.contains('qty-plus')) q += 1;
    else q = Math.max(0, q - 1);
    inp.value = q;
    recalc();
  });

  // Manual typing
  qtyInputs.forEach(function(inp) {
    inp.addEventListener('input', recalc);
    inp.addEventListener('change', function() {
      var q = parseInt(inp.value, 10);
      if (isNaN(q) || q < 0) inp.value = 0;
      recalc();
    });
  });

  recalc();

  // Search filter
  var searchInput = document.getElementById('product-search');
  var grid = document.getElementById('product-grid');
  var countEl = document.getElementById('product-count');
  if (searchInput && grid) {
    searchInput.addEventListener('input', function() {
      var query = this.value.toLowerCase().trim();
      var cards = grid.querySelectorAll('.product-card');
      var visible = 0;
      cards.forEach(function(card) {
        var name = card.getAttribute('data-name') || '';
        if (!query || name.indexOf(query) !== -1) {
          card.style.display = '';
          visible++;
        } else {
          card.style.display = 'none';
        }
      });
      if (countEl) countEl.textContent = visible + ' product' + (visible !== 1 ? 's' : '');
    });
  }
})();
</script>

</body>
</html>
