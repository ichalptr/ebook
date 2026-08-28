<?php
/**
 * Koneksi database — Pamulihan E-Library
 * Sesuaikan kredensial di bawah dengan environment kamu (XAMPP/Laragon/hosting).
 */

define('DB_HOST', 'localhost:3306');
define('DB_NAME', 'pamulihan_elibrary');
define('DB_USER', 'root');
define('DB_PASS', 'Sandi12@');

// Path dasar untuk upload (dipakai di admin & reader)
define('BASE_URL', 'http://localhost/ebook');
define('UPLOAD_COVER_DIR', __DIR__ . '/../uploads/covers/');
define('UPLOAD_BOOK_DIR', __DIR__ . '/../uploads/books/');
define('UPLOAD_COVER_URL', BASE_URL . '/uploads/covers/');
define('UPLOAD_BOOK_URL', BASE_URL . '/uploads/books/');

// =====================================================
// Konfigurasi SMTP — untuk verifikasi email & lupa password.
// Kosongkan SMTP_USER kalau belum mau pakai email asli:
// sistem otomatis tampilkan link verifikasi/reset di layar (mode lokal/dev).
// Contoh pakai Gmail: aktifkan "App Password" di akun Google kamu,
// isi SMTP_USER dengan email Gmail, SMTP_PASS dengan App Password (16 digit).
// =====================================================
define('SMTP_HOST', '');           // contoh: 'smtp.gmail.com'
define('SMTP_PORT', 465);          // 465 = SSL, 587 = TLS
define('SMTP_USER', '');           // contoh: 'namamu@gmail.com'
define('SMTP_PASS', '');           // App Password, BUKAN password akun biasa
define('SMTP_FROM_NAME', 'Pamulihan E-Library');

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
