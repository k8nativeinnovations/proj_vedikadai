<?php
include 'config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

/* Ensure uploads folder */
$uploadDir = "uploads/";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

/* DELETE product (via POST) */
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
    $_SESSION['admin_flash'] = ['type' => 'success', 'msg' => 'Product deleted.'];
    header("Location: admin2.php");
    exit();
}

/* UPLOAD payment QR */
if (isset($_POST['upload_qr']) && !empty($_FILES['payment_qr']['name'])) {
    $tmp  = $_FILES['payment_qr']['tmp_name'];
    $name = $_FILES['payment_qr']['name'];
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowed, true)) {
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'QR must be a JPG, PNG or WebP image.'];
    } else {
        // Remove any previous QR file
        $prev = get_setting('payment_qr_image');
        if ($prev && file_exists($prev)) @unlink($prev);

        $target = $uploadDir . 'payment_qr_' . time() . '.' . $ext;
        if (move_uploaded_file($tmp, $target)) {
            save_setting('payment_qr_image', $target);
            $_SESSION['admin_flash'] = ['type' => 'success', 'msg' => 'Payment QR updated.'];
        } else {
            $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'QR upload failed.'];
        }
    }
    header("Location: admin2.php");
    exit();
}

/* REMOVE payment QR */
if (isset($_POST['remove_qr'])) {
    $prev = get_setting('payment_qr_image');
    if ($prev && file_exists($prev)) @unlink($prev);
    save_setting('payment_qr_image', null);
    $_SESSION['admin_flash'] = ['type' => 'success', 'msg' => 'Payment QR removed.'];
    header("Location: admin2.php");
    exit();
}

/* EDIT MODE — fetch product to prefill the form */
$editProduct = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $editId);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    $editProduct = mysqli_fetch_assoc($r) ?: null;
    mysqli_stmt_close($stmt);
}

/* FETCH products */
$products = [];
$res = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
while ($r = mysqli_fetch_assoc($res)) $products[] = $r;

$currentQr = get_setting('payment_qr_image');
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

  <?php if ($flash): ?>
  <div class="flash-banner flash-<?php echo htmlspecialchars($flash['type']); ?>" role="status">
    <?php echo htmlspecialchars($flash['msg']); ?>
  </div>
  <?php endif; ?>

  <!-- Payment QR Code Card -->
  <div class="admin-card">
    <h2>Payment QR Code</h2>
    <p style="text-align:center;color:var(--text-muted);margin:0 0 16px;">Shown to customers on the order confirmation page after they place an order.</p>

    <div style="display:flex;gap:24px;align-items:center;flex-wrap:wrap;justify-content:center;">
      <?php if ($currentQr && file_exists($currentQr)): ?>
        <img src="<?php echo htmlspecialchars($currentQr); ?>?v=<?php echo @filemtime($currentQr); ?>" alt="Current payment QR"
             style="width:180px;height:180px;object-fit:contain;border:2px solid var(--border);border-radius:var(--radius-sm);background:#fff;padding:6px;">
      <?php else: ?>
        <div style="width:180px;height:180px;display:flex;align-items:center;justify-content:center;border:2px dashed var(--border);border-radius:var(--radius-sm);color:var(--text-muted);font-size:0.9rem;text-align:center;padding:10px;">
          No QR uploaded yet
        </div>
      <?php endif; ?>

      <div style="flex:1;min-width:260px;">
        <form method="POST" enctype="multipart/form-data" style="margin:0 0 12px;">
          <div class="form-group">
            <label for="payment-qr">Upload <?php echo $currentQr ? 'new' : ''; ?> QR image (JPG / PNG / WebP)</label>
            <input type="file" id="payment-qr" name="payment_qr" class="form-input"
                   accept="image/jpeg,image/png,image/webp" required>
          </div>
          <button type="submit" name="upload_qr" value="1" class="btn btn--primary btn--block">
            <?php echo $currentQr ? 'Replace QR' : 'Upload QR'; ?>
          </button>
        </form>
        <?php if ($currentQr): ?>
        <form method="POST" style="margin:0;">
          <button type="submit" name="remove_qr" value="1" class="btn btn--danger btn--block"
                  onclick="return confirm('Remove the payment QR code?')">
            Remove QR
          </button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Add / Edit Product Form -->
  <div class="admin-card">
    <h2><?php echo $editProduct ? 'Edit Product #' . (int)$editProduct['id'] : 'Add New Product'; ?></h2>

    <?php if ($editProduct): ?>
    <p style="text-align:center;margin:0 0 12px;">
      <a href="admin2.php" class="btn btn--outline">Cancel edit · Switch to Add mode</a>
    </p>
    <?php endif; ?>

    <form method="POST" action="save_product.php" enctype="multipart/form-data" id="add-product-form">
      <?php if ($editProduct): ?>
      <input type="hidden" name="id" value="<?php echo (int)$editProduct['id']; ?>">
      <?php endif; ?>

      <div class="admin-form-grid">
        <div class="form-group">
          <label for="prod-name-en">English Name</label>
          <input type="text" id="prod-name-en" name="name_en" class="form-input" required
                 value="<?php echo htmlspecialchars($editProduct['name_en'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <label for="prod-name-ta">Tamil Name</label>
          <input type="text" id="prod-name-ta" name="name_ta" class="form-input" required
                 value="<?php echo htmlspecialchars($editProduct['name_ta'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <label for="prod-pack">Pack Details</label>
          <input type="text" id="prod-pack" name="pack" class="form-input" required
                 value="<?php echo htmlspecialchars($editProduct['pack'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <label for="old_price">Old Price (<?php echo CURRENCY_SYMBOL; ?>)</label>
          <input type="number" id="old_price" name="old_price" class="form-input"
                 step="0.01" min="0" oninput="calculateOfferPrice()" required
                 value="<?php echo $editProduct ? htmlspecialchars($editProduct['old_price']) : ''; ?>">
        </div>
        <div class="form-group">
          <label for="offer">Offer (%)</label>
          <input type="number" id="offer" name="offer" class="form-input"
                 min="0" max="100" oninput="calculateOfferPrice()" required
                 value="<?php echo $editProduct ? (int)$editProduct['offer'] : ''; ?>">
        </div>
        <div class="form-group">
          <label for="new_price">New Price (Auto)</label>
          <input type="number" id="new_price" name="new_price" class="form-input"
                 step="0.01" readonly required
                 value="<?php echo $editProduct ? htmlspecialchars($editProduct['new_price']) : ''; ?>">
        </div>
        <div class="form-group" style="grid-column: 1 / -1;">
          <label for="prod-image">Product Image
            <?php if ($editProduct): ?><small style="color:var(--text-muted);font-weight:400;">(leave blank to keep current)</small><?php endif; ?>
          </label>
          <?php if ($editProduct && !empty($editProduct['image']) && file_exists($editProduct['image'])): ?>
            <div style="margin-bottom:8px;">
              <img src="<?php echo htmlspecialchars($editProduct['image']); ?>" alt=""
                   style="width:90px;height:90px;object-fit:cover;border-radius:var(--radius-sm);border:2px solid var(--border);">
            </div>
          <?php endif; ?>
          <input type="file" id="prod-image" name="image" class="form-input"
                 accept="image/jpeg,image/png,image/webp" <?php echo $editProduct ? '' : 'required'; ?>>
        </div>
      </div>

      <button type="submit" class="btn btn--accent btn--block btn--lg mt-2" id="save-product-btn">
        <?php echo $editProduct ? 'Update Product' : 'Save Product'; ?>
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

        <div style="display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end;">
          <a href="admin2.php?edit=<?php echo (int)$p['id']; ?>#add-product-form"
             class="btn btn--info">Edit</a>
          <form method="POST" style="margin:0;">
            <button type="submit" name="delete_product" value="<?php echo (int)$p['id']; ?>"
                    class="btn btn--danger"
                    onclick="return confirm('Delete this product?')">
              Delete
            </button>
          </form>
        </div>
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

/* Prevent double-submit (defer disable so the button name is included) */
(function() {
  var form = document.getElementById('add-product-form');
  if (!form) return;
  form.addEventListener('submit', function() {
    setTimeout(function() {
      var btn = document.getElementById('save-product-btn');
      if (btn) {
        btn.disabled = true;
        btn.textContent = 'Saving...';
      }
    }, 0);
  });
})();
</script>

</body>
</html>
