

<?php
include 'config.php';

/* ---------- INITIALIZE CART ---------- */
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/* ---------- ADD PRODUCT TO CART ---------- */
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

            $_SESSION['cart'][] = [
                'product_id' => $product['id'],
                'name'       => $product['name_en'],
                'price'      => (float)$product['new_price'],
                'image'      => $product['image'],
                'qty'        => $quantity,
                'total'      => (float)$product['new_price'] * $quantity,
                'unique_id'  => time() . rand(100, 999)
            ];

            $addedMessage = "✔ Product Added Successfully!";
        }
    }
}

/* ---------- FETCH PRODUCTS ---------- */
$data = [];
$result = mysqli_query($conn, "SELECT * FROM products ORDER BY id ASC");
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Thiruchendur Murugan Vedikadai</title>

<style>
body{margin:0;font-family:Arial,sans-serif;background:#fff7e6}

/* HEADER */
.header{
    background:#8b0000;
    padding:30px;
    text-align:center;
    color:gold;
    font-weight:bold;
    font-size:40px;
    position:relative;
}
.header img{
    width:150px;height:180px;
    border:4px solid gold;
    border-radius:12px;
    margin-top:10px;
}
.admin-btn,.cart-btn{
    position:absolute;top:25px;
    padding:14px 24px;
    font-size:24px;
    border-radius:10px;
    text-decoration:none;
    color:white;
}
.admin-btn{right:20px;background:black}
.cart-btn{right:200px;background:#0057ff}

/* CONTACT */
.contact-header{
    position:absolute;
    right:200px;
    top:150px;
    width:320px;
    color:white;
    font-size:22px;
    text-align:right;
}

/* MARQUEE */
.marquee-box{
    background:#222;
    color:#ffcc00;
    font-size:30px;
    padding:14px;
    font-weight:bold;
}

/* TABLE */
table{
    width:96%;
    margin:30px auto;
    border-collapse:collapse;
    font-size:26px;
    font-weight:bold;
}
th{
    background:#003d80;
    color:white;
    padding:22px;
    font-size:28px;
}
td{
    padding:22px;
    background:white;
    border:1px solid #ccc;
    text-align:center;
}

/* IMAGE */
.product-img{
    width:150px;height:150px;
    border:4px solid #000;
    border-radius:14px;
    object-fit:cover;
}

/* PRICE */
.price-box{
    padding:16px;
    border-radius:14px;
    background:#fff0d6;
    border:3px solid #ff8800;
}
.old-price{font-size:22px;color:#555;text-decoration:line-through}
.offer-text{font-size:26px;color:red}
.new-price{font-size:32px;color:#008000}

/* INPUT */
input[type=number]{
    font-size:24px;
    padding:10px;
    width:90px;
    border-radius:8px;
}

/* BUTTON */
.add-btn{
    background:#28a745;
    color:white;
    padding:14px 22px;
    border:none;
    border-radius:14px;
    font-size:26px;
    cursor:pointer;
}
.add-btn:hover{background:#1e7e34}

/* MESSAGE */
.success-msg{
    text-align:center;
    color:green;
    font-size:34px;
    font-weight:bold;
    margin-top:25px;
}
</style>

<script>
function updateTotal(row, price){
    let qty = document.getElementById("qty"+row).value;
    document.getElementById("total"+row).innerHTML =
        "₹ " + ((qty||0)*price).toFixed(2);
}
</script>
</head>

<body>

<div class="header">
    <a href="logout.php" class="admin-btn">Admin</a>
    <a href="cart_checkout.php" class="cart-btn">
        Cart (<?php echo count($_SESSION['cart']); ?>)
    </a>

    <div class="contact-header">
        <p>📞 +91 86104 66629</p>
        <p>📧 thiruchendurmuruganvedikadai@gmail.com</p>
    </div>

    <img src="murugan_logo.png">
    <div>திருச்செந்தூர் முருகன் வெடிகடை</div>
    <div style="font-size:24px;">Thiruchendur Murugan Vedikadai</div>
</div>

<div class="marquee-box">
<marquee>🔥 Welcome to Thiruchendur Murugan Vedikadai 🔥</marquee>
</div>

<?php if ($addedMessage): ?>
<div class="success-msg"><?php echo $addedMessage; ?></div>
<?php endif; ?>

<table>
<tr>
    <th>No</th>
    <th>Image</th>
    <th>Product</th>
    <th>Price</th>
    <th>Qty</th>
    <th>Total</th>
    <th>Add</th>
</tr>

<?php foreach ($data as $i=>$item): ?>
<form method="POST">
<tr>
<td><?php echo $i+1; ?></td>

<td><img src="<?php echo $item['image']; ?>" class="product-img"></td>

<td>
<?php echo $item['name_en']; ?><br>
<span style="font-size:22px;color:#555"><?php echo $item['name_ta']; ?></span>
</td>

<td>
<div class="price-box">
<div class="old-price">₹ <?php echo number_format($item['old_price'],2); ?></div>
<div class="offer-text"><?php echo $item['offer']; ?>% OFF</div>
<div class="new-price">₹ <?php echo number_format($item['new_price'],2); ?></div>
</div>
</td>

<td>
<input type="number" id="qty<?php echo $i; ?>" name="quantity" min="0" value="0"
oninput="updateTotal(<?php echo $i; ?>,<?php echo $item['new_price']; ?>)">
</td>

<td id="total<?php echo $i; ?>">₹ <?php echo number_format($item['new_price'],2); ?></td>

<td>
<input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
<button type="submit" name="add_to_cart" class="add-btn">✓ Add</button>
</td>
</tr>
</form>
<?php endforeach; ?>
</table>

</body>
</html>
