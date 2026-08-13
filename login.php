<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        login_user($user);
        $redirect = match ($user['role']) {
            'admin'   => BASE_URL . '/admin/dashboard.php',
            'teacher' => BASE_URL . '/guru/dashboard.php',
            default   => BASE_URL . '/index.php',
        };
        header('Location: ' . $redirect);
        exit;
    } else {
        $error = 'Email atau password salah.';
    }
}

$page_title = 'Masuk';
require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-body">
<div class="container py-5" style="max-width:420px;">
  <a href="<?= BASE_URL ?>/index.php" class="auth-brand justify-content-center">
    <i class="bi bi-book-half"></i> Pamulihan E-Library
  </a>
  <div class="auth-card">
    <h4 class="mb-1 auth-title text-center">Selamat Datang Kembali</h4>
    <p class="text-muted small text-center mb-4">Masuk untuk melanjutkan membaca.</p>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button class="btn btn-brand w-100">Masuk</button>
      <p class="text-center small mt-3 mb-0">Belum punya akun? <a href="<?= BASE_URL ?>/register.php">Daftar di sini</a></p>
    </form>
  </div>
</div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
