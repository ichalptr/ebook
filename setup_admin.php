<?php
/**
 * JALANKAN SEKALI SAJA setelah import database.sql, lalu HAPUS file ini.
 * Akses lewat browser: http://localhost/pamulihan-elibrary/setup_admin.php
 */
require_once __DIR__ . '/config/db.php';

$message = '';
$done = false;

$check = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
if ($check > 0) {
    $message = 'Akun admin sudah ada. Demi keamanan, hapus file setup_admin.php ini dari server.';
    $done = true;
}

if (!$done && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name && $email && strlen($password) >= 6) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (:n, :e, :p, 'admin')");
        $stmt->execute([':n' => $name, ':e' => $email, ':p' => $hash]);
        $message = 'Akun admin berhasil dibuat! Silakan hapus file setup_admin.php sekarang, lalu login di login.php.';
        $done = true;
    } else {
        $message = 'Isi semua kolom, password minimal 6 karakter.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Setup Admin — Pamulihan E-Library</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;1,9..144,500&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-body">
<div class="container py-5" style="max-width:480px;">
  <div class="auth-card">
    <h4 class="mb-3 auth-title">Setup Akun Admin</h4>
    <p class="text-muted small mb-4">Pamulihan E-Library — jalankan sekali saja.</p>
    <?php if ($message): ?><div class="alert alert-<?= $done ? 'success' : 'danger' ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <?php if (!$done): ?>
    <form method="post">
      <div class="mb-3">
        <label class="form-label">Nama Admin</label>
        <input type="text" name="name" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" minlength="6" required>
      </div>
      <button class="btn btn-brand w-100">Buat Akun Admin</button>
    </form>
    <?php else: ?>
      <a href="login.php" class="btn btn-brand">Ke Halaman Login</a>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
