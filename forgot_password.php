<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$submitted = false;
$devResetLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $u = $stmt->fetch();

    if ($u) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // berlaku 1 jam
        $pdo->prepare("UPDATE users SET reset_token = :t, reset_expires = :e WHERE id = :id")
            ->execute([':t' => $token, ':e' => $expires, ':id' => $u['id']]);

        $resetLink = BASE_URL . '/reset_password.php?token=' . $token;
        $bodyHtml = "<p>Halo <strong>" . htmlspecialchars($u['name']) . "</strong>,</p>
            <p>Ada permintaan reset password untuk akun kamu. Klik tombol di bawah (berlaku 1 jam):</p>
            <p><a href=\"{$resetLink}\" style=\"background:#1f7a3d;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;\">Reset Password</a></p>
            <p>Atau salin link ini: {$resetLink}</p>
            <p>Kalau kamu tidak meminta ini, abaikan saja email ini.</p>";

        $sent = send_email($email, 'Reset Password — Pamulihan E-Library', $bodyHtml);
        if (!$sent) $devResetLink = $resetLink;
    }
    // Pesan sukses ditampilkan sama aja baik email ketemu atau tidak,
    // supaya orang lain gak bisa nebak email mana yang terdaftar.
    $submitted = true;
}

$page_title = 'Lupa Password';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container py-5" style="max-width:420px;">
  <?php if ($submitted): ?>
    <div class="card p-4 shadow-sm text-center">
      <i class="bi bi-envelope-check display-4 text-success mb-3"></i>
      <h4>Cek Email Kamu</h4>
      <p class="text-muted">Kalau email tersebut terdaftar, kami sudah mengirim link reset password ke sana. Link berlaku 1 jam.</p>
      <?php if ($devResetLink): ?>
        <div class="alert alert-warning text-start small mt-3">
          <strong>Mode Dev:</strong> SMTP belum dikonfigurasi. Klik link berikut untuk reset manual:<br>
          <a href="<?= htmlspecialchars($devResetLink) ?>"><?= htmlspecialchars($devResetLink) ?></a>
        </div>
      <?php endif; ?>
      <a href="<?= BASE_URL ?>/login.php" class="btn btn-success mt-2">Kembali ke Login</a>
    </div>
  <?php else: ?>
  <h3 class="mb-4 text-center"><i class="bi bi-key"></i> Lupa Password</h3>
  <p class="text-muted text-center small">Masukkan email akun kamu, kami akan kirim link untuk reset password.</p>
  <form method="post" class="card p-4 shadow-sm">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" required autofocus>
    </div>
    <button class="btn btn-success w-100">Kirim Link Reset</button>
    <p class="text-center small mt-3 mb-0"><a href="<?= BASE_URL ?>/login.php">Kembali ke Login</a></p>
  </form>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
