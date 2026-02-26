<?php
include 'config.php';

/* ---------- INITIALIZE CART ---------- */
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/* ---------- AJAX ADD TO CART ---------- */
if (isset($_POST['ajax_add_to_cart'])) {
    header('Content-Type: application/json');

    $product_id = (int)$_POST['product_id'];
    $quantity   = (int)$_POST['quantity'];

    if ($product_id <= 0 || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Please select at least 1 item.']);
        exit();
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, name_en, name_ta, old_price, offer, new_price, image
         FROM products WHERE id = ? LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if ($product = mysqli_fetch_assoc($res)) {
        // Merge quantities if product already in cart
        $found = false;
        foreach ($_SESSION['cart'] as &$cartItem) {
            if ($cartItem['product_id'] == $product['id']) {
                $cartItem['qty'] += $quantity;
                $cartItem['total'] = (float)$cartItem['price'] * $cartItem['qty'];
                $found = true;
                break;
            }
        }
        unset($cartItem);

        if (!$found) {
            $_SESSION['cart'][] = [
                'product_id' => $product['id'],
                'name'       => $product['name_en'],
                'price'      => (float)$product['new_price'],
                'image'      => $product['image'],
                'qty'        => $quantity,
                'total'      => (float)$product['new_price'] * $quantity,
                'unique_id'  => time() . rand(100, 999)
            ];
        }

        echo json_encode([
            'success'    => true,
            'message'    => htmlspecialchars($product['name_en']) . ' added to cart!',
            'cart_count' => count($_SESSION['cart'])
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found.']);
    }
    exit();
}

/* ---------- FALLBACK: Regular POST add to cart ---------- */
$addedMessage = "";
if (isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity   = (int)$_POST['quantity'];

    if ($product_id > 0 && $quantity > 0) {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, name_en, name_ta, old_price, offer, new_price, image
             FROM products WHERE id = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        if ($product = mysqli_fetch_assoc($res)) {
            $found = false;
            foreach ($_SESSION['cart'] as &$cartItem) {
                if ($cartItem['product_id'] == $product['id']) {
                    $cartItem['qty'] += $quantity;
                    $cartItem['total'] = (float)$cartItem['price'] * $cartItem['qty'];
                    $found = true;
                    break;
                }
            }
            unset($cartItem);

            if (!$found) {
                $_SESSION['cart'][] = [
                    'product_id' => $product['id'],
                    'name'       => $product['name_en'],
                    'price'      => (float)$product['new_price'],
                    'image'      => $product['image'],
                    'qty'        => $quantity,
                    'total'      => (float)$product['new_price'] * $quantity,
                    'unique_id'  => time() . rand(100, 999)
                ];
            }
            $addedMessage = htmlspecialchars($product['name_en']) . " added to cart!";
        }
    }
}

/* ---------- FETCH PRODUCTS ---------- */
$data = [];
$result = mysqli_query($conn, "SELECT * FROM products ORDER BY id ASC");
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
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

<!-- Announcement Bar (replaces deprecated marquee) -->
<div class="announce-bar" role="banner">
  Welcome to Thiruchendur Murugan Vedikadai — Quality crackers at the best prices!
</div>

<!-- Toast Container for AJAX notifications -->
<div class="toast-container" id="toast-container" aria-live="polite"></div>

<main class="container">

  <!-- Search Bar -->
  <div class="search-bar mb-2">
    <input type="search" class="search-input" id="product-search"
           placeholder="Search products by name..." aria-label="Search products by name">
    <span class="product-count" id="product-count"><?php echo count($data); ?> products</span>
  </div>

  <?php if (count($data) === 0): ?>
  <!-- Empty State -->
  <div class="empty-state">
    <div class="empty-state-icon" aria-hidden="true">&#x1F9E8;</div>
    <h2>Products are being updated</h2>
    <p>Please check back soon! We're adding new products to our collection.</p>
  </div>

  <?php else: ?>
  <!-- Product Grid -->
  <div class="product-grid" id="product-grid">

    <?php foreach ($data as $i => $item): ?>
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

        <form class="product-card-actions add-to-cart-form" method="POST">
          <input type="hidden" name="product_id" value="<?php echo (int)$item['id']; ?>">
          <label for="qty-<?php echo (int)$item['id']; ?>" class="sr-only">Quantity</label>
          <input type="number" id="qty-<?php echo (int)$item['id']; ?>"
                 name="quantity" min="1" value="1"
                 class="qty-input" aria-label="Quantity for <?php echo htmlspecialchars($item['name_en']); ?>">
          <button type="submit" name="add_to_cart" class="btn btn--primary btn--add-to-cart">
            Add to Cart
          </button>
        </form>
      </div>
    </article>
    <?php endforeach; ?>

  </div>
  <?php endif; ?>

</main>

<script>
/* --- Search/Filter --- */
(function() {
  var searchInput = document.getElementById('product-search');
  var grid = document.getElementById('product-grid');
  var countEl = document.getElementById('product-count');

  if (!searchInput || !grid) return;

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

    if (countEl) {
      countEl.textContent = visible + ' product' + (visible !== 1 ? 's' : '');
    }
  });
})();

/* --- Toast Notification --- */
function showToast(message, isError) {
  var container = document.getElementById('toast-container');
  var toast = document.createElement('div');
  toast.className = 'toast';
  if (isError) toast.style.background = '#dc3545';
  toast.textContent = message;
  container.appendChild(toast);
  setTimeout(function() { toast.remove(); }, 3000);
}

/* --- AJAX Add to Cart (no page reload) --- */
(function() {
  var forms = document.querySelectorAll('.add-to-cart-form');

  forms.forEach(function(form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      var btn = form.querySelector('button[type="submit"]');
      var originalText = btn.textContent;
      btn.disabled = true;
      btn.textContent = 'Adding...';

      var formData = new FormData(form);
      formData.append('ajax_add_to_cart', '1');

      fetch('index.php', {
        method: 'POST',
        body: formData
      })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (data.success) {
          showToast(data.message);
          var badge = document.getElementById('cart-badge');
          if (badge) badge.textContent = data.cart_count;
          form.querySelector('input[name="quantity"]').value = 1;
        } else {
          showToast(data.message, true);
        }
      })
      .catch(function() {
        showToast('Something went wrong. Please try again.', true);
      })
      .finally(function() {
        btn.disabled = false;
        btn.textContent = originalText;
      });
    });
  });
})();

<?php if ($addedMessage): ?>
showToast('<?php echo addslashes($addedMessage); ?>');
<?php endif; ?>
</script>

</body>
</html>
