<?php
include 'config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

/* Ensure uploads folder */
$uploadDir = "uploads/";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

/* DELETE product (now via POST for safety) */
if (isset($_POST['delete_product'])) {
    $id = (int)$_POST['delete_product'];
    $stmt = mysqli_prepare($conn, "SELECT image FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $imgRes = mysqli_stmt_get_result($stmt);
    if ($imgRow = mysqli_fetch_assoc($imgRes)) {
        if (!empty($imgRow['image']) && file_exists($imgRow['image'])) {
            unlink($imgRow['image']);
        }
    }
    $delStmt = mysqli_prepare($conn, "DELETE FROM products WHERE id = ?");
    mysqli_stmt_bind_param($delStmt, "i", $id);
    mysqli_stmt_execute($delStmt);
    header("Location: admin2.php");
    exit();
}

/* FETCH products */
$products = [];
$res = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
while ($r = mysqli_fetch_assoc($res)) $products[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Panel | Murugan Vedikadai</title>
<link rel="stylesheet" href="styles.css">
</head>

<body>

<!-- Admin Navigation -->
<nav class="admin-nav" aria-label="Admin navigation">
  <div class="admin-nav-inner">
    <span class="admin-nav-title">Murugan Vedikadai — Admin</span>
    <div class="admin-nav-links">
      <a href="index.php" class="nav-link">Shop</a>
      <a href="admin_orders.php" class="nav-link btn--primary" style="background:var(--success);">Orders</a>
      <a href="logout.php" class="nav-link" style="background:var(--danger);">Logout</a>
    </div>
  </div>
</nav>

<main class="admin-container">

  <!-- Add Product Form -->
  <div class="admin-card">
    <h2>Add New Product</h2>

    <form method="POST" action="save_product.php" enctype="multipart/form-data" id="add-product-form">
      <div class="admin-form-grid">
        <div class="form-group">
          <label for="prod-name-en">English Name</label>
          <input type="text" id="prod-name-en" name="name_en" class="form-input" required>
        </div>
        <div class="form-group">
          <label for="prod-name-ta">Tamil Name</label>
          <input type="text" id="prod-name-ta" name="name_ta" class="form-input" required>
        </div>
        <div class="form-group">
          <label for="prod-pack">Pack Details</label>
          <input type="text" id="prod-pack" name="pack" class="form-input" required>
        </div>
        <div class="form-group">
          <label for="old_price">Old Price (<?php echo CURRENCY_SYMBOL; ?>)</label>
          <input type="number" id="old_price" name="old_price" class="form-input"
                 step="0.01" min="0" oninput="calculateOfferPrice()" required>
        </div>
        <div class="form-group">
          <label for="offer">Offer (%)</label>
          <input type="number" id="offer" name="offer" class="form-input"
                 min="0" max="100" oninput="calculateOfferPrice()" required>
        </div>
        <div class="form-group">
          <label for="new_price">New Price (Auto)</label>
          <input type="number" id="new_price" name="new_price" class="form-input"
                 step="0.01" readonly required>
        </div>
        <div class="form-group" style="grid-column: 1 / -1;">
          <label for="prod-image">Product Image</label>
          <input type="file" id="prod-image" name="image" class="form-input"
                 accept="image/jpeg,image/png,image/webp" required>
        </div>
      </div>

      <button type="submit" class="btn btn--accent btn--block btn--lg mt-2" id="save-product-btn">
        Save Product
      </button>
    </form>
  </div>

  <!-- Product List -->
  <div class="admin-card">
    <h2>Existing Products (<?php echo count($products); ?>)</h2>

    <?php if (empty($products)): ?>
    <div class="empty-state">
      <div class="empty-state-icon" aria-hidden="true">&#x1F4E6;</div>
      <h2>No products yet</h2>
      <p>Use the form above to add your first product.</p>
    </div>
    <?php else: ?>
    <div class="admin-product-list">
      <?php foreach ($products as $p): ?>
      <div class="admin-product-item">
        <?php if ($p['image']): ?>
          <img src="<?php echo htmlspecialchars($p['image']); ?>"
               alt="<?php echo htmlspecialchars($p['name_en']); ?>"
               class="admin-product-thumb"
               onerror="this.onerror=null;this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 70 70%22><rect fill=%22%23f0f0f0%22 width=%2270%22 height=%2270%22/><text x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%23999%22 font-size=%2210%22>No Img</text></svg>'">
        <?php else: ?>
          <div class="admin-product-thumb" style="background:#f0f0f0;display:flex;align-items:center;justify-content:center;font-size:0.7rem;color:#999;">No Img</div>
        <?php endif; ?>

        <div class="admin-product-info">
          <strong><?php echo htmlspecialchars($p['name_en']); ?></strong>
          <small lang="ta"><?php echo htmlspecialchars($p['name_ta']); ?></small>
          <small>Pack: <?php echo htmlspecialchars($p['pack']); ?></small>
        </div>

        <div class="admin-product-price">
          <div style="text-decoration:line-through;color:#999;font-size:0.8rem;">
            <?php echo CURRENCY_SYMBOL . number_format($p['old_price'], 2); ?>
          </div>
          <div style="color:var(--danger);font-size:0.8rem;"><?php echo intval($p['offer']); ?>% OFF</div>
          <div style="color:var(--success);font-weight:700;">
            <?php echo CURRENCY_SYMBOL . number_format($p['new_price'], 2); ?>
          </div>
        </div>

        <form method="POST" style="margin:0;">
          <button type="submit" name="delete_product" value="<?php echo (int)$p['id']; ?>"
                  class="btn btn--danger"
                  onclick="return confirm('Delete this product?')">
            Delete
          </button>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</main>

<script>
function calculateOfferPrice() {
  var oldP = parseFloat(document.getElementById('old_price').value) || 0;
  var offer = parseFloat(document.getElementById('offer').value) || 0;
  if (offer < 0) offer = 0;
  if (offer > 100) offer = 100;
  var newP = oldP - (oldP * offer / 100);
  document.getElementById('new_price').value = newP.toFixed(2);
}

/* Prevent double-submit */
(function() {
  var form = document.getElementById('add-product-form');
  if (!form) return;
  form.addEventListener('submit', function() {
    var btn = document.getElementById('save-product-btn');
    if (btn) {
      btn.disabled = true;
      btn.textContent = 'Saving...';
    }
  });
})();
</script>

</body>
</html>
