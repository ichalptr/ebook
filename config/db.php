<?php
/**
 * Koneksi database — Pamulihan E-Library
 * Sesuaikan kredensial di bawah dengan environment kamu (XAMPP/Laragon/hosting).
 */

define('DB_HOST', 'localhost:3311');
define('DB_NAME', 'pamulihan_elibrary');
define('DB_USER', 'root');
define('DB_PASS', '');

// Path dasar untuk upload (dipakai di admin & reader)
define('BASE_URL', 'http://localhost/pamulihan-elibrary');
define('UPLOAD_COVER_DIR', __DIR__ . '/../uploads/covers/');
define('UPLOAD_BOOK_DIR', __DIR__ . '/../uploads/books/');
define('UPLOAD_COVER_URL', BASE_URL . '/uploads/covers/');
define('UPLOAD_BOOK_URL', BASE_URL . '/uploads/books/');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('Koneksi database gagal: ' . htmlspecialchars($e->getMessage()));
}
