<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error = '';
$success = false;
$validToken = false;
$tokenUser = null;

if ($token) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = :t");
    $stmt->execute([':t' => $token]);
    $tokenUser = $stmt->fetch();
    $validToken = $tokenUser && $tokenUser['reset_expires'] && strtotime($tokenUser['reset_expires']) > time();
}

if ($validToken && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = :p, reset_token = NULL, reset_expires = NULL,
                        failed_attempts = 0, locked_until = NULL WHERE id = :id")
            ->execute([':p' => $hash, ':id' => $tokenUser['id']]);
        $success = true;
    }
}

$page_title = 'Reset Password';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container py-5" style="max-width:420px;">
  <?php if ($success): ?>
    <div class="card p-4 shadow-sm text-center">
      <i class="bi bi-check-circle display-4 text-success mb-3"></i>
      <h4>Password Berhasil Diubah</h4>
      <p class="text-muted">Silakan masuk pakai password baru kamu.</p>
      <a href="<?= BASE_URL ?>/login.php" class="btn btn-success mt-2">Masuk Sekarang</a>
    </div>
  <?php elseif (!$validToken): ?>
    <div class="card p-4 shadow-sm text-center">
      <i class="bi bi-x-circle display-4 text-danger mb-3"></i>
      <h4>Link Reset Tidak Valid</h4>
      <p class="text-muted">Link ini sudah kedaluwarsa (berlaku 1 jam) atau sudah pernah dipakai.</p>
      <a href="<?= BASE_URL ?>/forgot_password.php" class="btn btn-outline-success mt-2">Minta Link Baru</a>
    </div>
  <?php else: ?>
    <h3 class="mb-4 text-center"><i class="bi bi-key"></i> Atur Password Baru</h3>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post" class="card p-4 shadow-sm">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
      <div class="mb-3">
        <label class="form-label">Password Baru</label>
        <input type="password" name="password" class="form-control" minlength="6" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label">Konfirmasi Password Baru</label>
        <input type="password" name="password_confirm" class="form-control" minlength="6" required>
      </div>
      <button class="btn btn-success w-100">Simpan Password Baru</button>
    </form>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
