<?php
/**
 * Helper autentikasi & proteksi halaman
 * Include file ini SETELAH config/db.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function current_user(): ?array {
    if (!is_logged_in()) return null;
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name'],
        'role'  => $_SESSION['user_role'],
        'email' => $_SESSION['user_email'],
    ];
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function require_role(string $role): void {
    require_login();
    if ($_SESSION['user_role'] !== $role) {
        http_response_code(403);
        die('Akses ditolak. Halaman ini khusus untuk role: ' . htmlspecialchars($role));
    }
}

function login_user(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['user_email'] = $user['email'];
}

function logout_user(): void {
    $_SESSION = [];
    session_destroy();
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_check(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Token keamanan tidak valid. Silakan muat ulang halaman.');
    }
}
