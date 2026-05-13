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
mysqli_set_charset($conn, "utf8mb4");

define("CURRENCY_SYMBOL", "₹");

/* ---------- Settings (JSON-backed) ---------- */
define("SETTINGS_FILE", __DIR__ . "/settings.json");

function get_settings(): array {
    if (!file_exists(SETTINGS_FILE)) return [];
    $raw = file_get_contents(SETTINGS_FILE);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function get_setting(string $key, $default = null) {
    $s = get_settings();
    return $s[$key] ?? $default;
}

function save_setting(string $key, $value): void {
    $s = get_settings();
    $s[$key] = $value;
    file_put_contents(SETTINGS_FILE, json_encode($s, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}
