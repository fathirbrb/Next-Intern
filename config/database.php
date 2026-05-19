<?php
// Konfigurasi database NextIntern
// Sesuaikan username/password jika MySQL Anda berbeda.

$DB_HOST = 'localhost';
$DB_NAME = 'nextintern_db';
$DB_USER = 'root';
$DB_PASS = '';
$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo '<h1>Database tidak terhubung</h1>';
    echo '<p>Pastikan database <b>nextintern_db</b> sudah di-import dan konfigurasi di <code>config/database.php</code> sudah benar.</p>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    exit;
}
