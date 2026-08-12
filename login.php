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
<div class="container py-5" style="max-width:420px;">
  <h3 class="mb-4 text-center"><i class="bi bi-box-arrow-in-right"></i> Masuk</h3>
  <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="post" class="card p-4 shadow-sm">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" required autofocus>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <button class="btn btn-success w-100">Masuk</button>
    <p class="text-center small mt-3 mb-0">Belum punya akun? <a href="<?= BASE_URL ?>/register.php">Daftar di sini</a></p>
  </form>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
