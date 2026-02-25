<?php
include 'config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

/* Ensure uploads folder */
$uploadDir = "uploads/";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

/* DELETE product */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $imgRes = mysqli_query($conn, "SELECT image FROM products WHERE id = $id");
    if ($imgRow = mysqli_fetch_assoc($imgRes)) {
        if (!empty($imgRow['image']) && file_exists($imgRow['image'])) {
            unlink($imgRow['image']);
        }
    }
    mysqli_query($conn, "DELETE FROM products WHERE id = $id");
    header("Location: admin2.php");
    exit();
}

/* FETCH products */
$products = [];
$res = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
while ($r = mysqli_fetch_assoc($res)) $products[] = $r;
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Admin Panel | Murugan Vedikadai</title>

<style>
body{
    margin:0;
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg,#fff3d6,#ffd966);
}

.header{
    background:#8b0000;
    color:gold;
    padding:30px;
    text-align:center;
    font-size:36px;
    font-weight:bold;
    letter-spacing:1px;
}

.container{
    width:95%;
    max-width:1600px;
    margin:30px auto;
    background:#fff;
    padding:30px;
    border-radius:18px;
    box-shadow:0 15px 35px rgba(0,0,0,0.25);
}

.top-actions{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
    font-size:22px;
    font-weight:bold;
}

.top-actions a{
    text-decoration:none;
    padding:12px 18px;
    border-radius:10px;
    color:white;
    margin-left:10px;
}

.shop-btn{ background:#0057ff; }
.order-btn{ background:#28a745; }
.logout-btn{ background:#c82333; }

h2{
    font-size:32px;
    color:#8b0000;
    margin-bottom:20px;
    text-align:center;
}

.form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:18px;
}

label{
    font-size:22px;
    font-weight:bold;
    color:#333;
}

input[type=text],
input[type=number],
input[type=file]{
    width:100%;
    padding:16px;
    font-size:22px;
    border-radius:10px;
    border:2px solid #ccc;
}

input[readonly]{ background:#f0f0f0; }

.add-btn{
    width:100%;
    margin-top:25px;
    padding:20px;
    font-size:28px;
    background:linear-gradient(135deg,#ff9800,#ff5722);
    color:white;
    border:none;
    border-radius:14px;
    font-weight:bold;
    cursor:pointer;
}

h3{
    margin-top:40px;
    font-size:30px;
    color:#8b0000;
}

table{
    width:100%;
    border-collapse:collapse;
    margin-top:15px;
    font-size:22px;
}

th{
    background:#8b0000;
    color:white;
    padding:16px;
    font-size:24px;
}

td{
    padding:16px;
    border-bottom:1px solid #ddd;
    text-align:center;
}

img.thumb{
    width:90px;
    height:90px;
    object-fit:cover;
    border-radius:10px;
    border:3px solid #eee;
}

.delete-btn{
    background:#dc3545;
    color:white;
    padding:12px 18px;
    border-radius:10px;
    text-decoration:none;
    font-size:20px;
    font-weight:bold;
}
</style>

<script>
function calculateOfferPrice() {
    const oldP = parseFloat(document.getElementById('old_price').value) || 0;
    let offer = parseFloat(document.getElementById('offer').value) || 0;
    if (offer < 0) offer = 0;
    if (offer > 100) offer = 100;
    const newP = oldP - (oldP * offer / 100);
    document.getElementById('new_price').value = newP.toFixed(2);
}
</script>
</head>

<body>

<div class="header">
    🧨 Murugan Vedikadai – Admin Panel
</div>

<div class="container">

    <div class="top-actions">
        <div>👨‍💼 Welcome, Admin</div>
        <div>
            <a href="index1.php" class="shop-btn">🏪 View Shop</a>
            <a href="admin_orders.php" class="order-btn">📦 View Orders</a>
            <a href="logout.php" class="logout-btn">🚪 Logout</a>
        </div>
    </div>

    <h2>➕ Add New Cracker Product</h2>

    <form method="POST" action="save_product.php" enctype="multipart/form-data">
        <div class="form-grid">
            <div>
                <label>English Name</label>
                <input type="text" name="name_en" required>
            </div><br>

            <div>
                <label>Tamil Name</label>
                <input type="text" name="name_ta" required>
            </div><br>

            <div>
                <label>Pack Details</label>
                <input type="text" name="pack" required>
            </div><br>

            <div>
                <label>Old Price (₹)</label>
                <input type="number" id="old_price" name="old_price" step="0.01"
                       oninput="calculateOfferPrice()" required>
            </div><br>

            <div>
                <label>Offer (%)</label>
                <input type="number" id="offer" name="offer"
                       oninput="calculateOfferPrice()" required>
            </div><br>

            <div>
                <label>New Price (Auto)</label>
                <input type="number" id="new_price" name="new_price"
                       step="0.01" readonly required>
            </div><br>

            <div>
                <label>Product Image</label>
                <input type="file" name="image" accept="image/*" required>
            </div><br>
        </div>

        <button type="submit" class="add-btn">💾 SAVE PRODUCT</button>
    </form>

    <h3>📦 Existing Products (<?php echo count($products); ?>)</h3>

    <table>
        <tr>
            <th>ID</th>
            <th>Image</th>
            <th>English</th>
            <th>Tamil</th>
            <th>Pack</th>
            <th>Old</th>
            <th>Offer</th>
            <th>New</th>
            <th>Action</th>
        </tr>

        <?php foreach ($products as $p): ?>
        <tr>
            <td><?php echo $p['id']; ?></td>
            <td><?php if ($p['image']): ?><img src="<?php echo $p['image']; ?>" class="thumb"><?php endif; ?></td>
            <td><?php echo htmlspecialchars($p['name_en']); ?></td>
            <td><?php echo htmlspecialchars($p['name_ta']); ?></td>
            <td><?php echo htmlspecialchars($p['pack']); ?></td>
            <td><?php echo CURRENCY_SYMBOL . number_format($p['old_price'],2); ?></td>
            <td><?php echo intval($p['offer']); ?>%</td>
            <td><?php echo CURRENCY_SYMBOL . number_format($p['new_price'],2); ?></td>
            <td>
                <a href="admin2.php?delete=<?php echo $p['id']; ?>"
                   class="delete-btn"
                   onclick="return confirm('Delete this product?')">
                   🗑 Delete
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

</div>

</body>
</html>
