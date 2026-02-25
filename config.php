<?php
// config.php - DB connection + session
session_start();

$DB_HOST = "localhost";
$DB_USER = "u495609946_admin";
$DB_PASS = "K8admin@";
$DB_NAME = "u495609946_crackers_db";

$conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

define("CURRENCY_SYMBOL", "₹");
?>
