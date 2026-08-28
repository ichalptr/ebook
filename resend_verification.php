<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer.php';

$email = trim($_GET['email'] ?? '');
$devVerifyLink = null;
$message = '';

if ($email) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND email_verified_at IS NULL");
    $stmt->execute([':email' => $email]);
    $u = $stmt->fetch();

    if ($u) {
        $token = bin2hex(random_bytes(32));
        $pdo->prepare("UPDATE users SET verification_token = :t WHERE id = :id")
            ->execute([':t' => $token, ':id' => $u['id']]);

        $verifyLink = BASE_URL . '/verify.php?token=' . $token;
        $bodyHtml = "<p>Halo <strong>" . htmlspecialchars($u['name']) . "</strong>,</p>
            <p>Klik tombol di bawah untuk memverifikasi email kamu:</p>
            <p><a href=\"{$verifyLink}\" style=\"background:#1f7a3d;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;\">Verifikasi Email</a></p>
            <p>Atau salin link ini: {$verifyLink}</p>";

        $sent = send_email($email, 'Verifikasi Email — Pamulihan E-Library', $bodyHtml);
        if (!$sent) $devVerifyLink = $verifyLink;
        $message = 'Link verifikasi baru sudah dikirim ke email kamu.';
    } else {
        $message = 'Email tidak ditemukan atau sudah terverifikasi.';
    }
}

$page_title = 'Kirim Ulang Verifikasi';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container py-5 text-center" style="max-width:480px;">
  <i class="bi bi-envelope-arrow-up display-4 text-success mb-3"></i>
  <h4>Kirim Ulang Verifikasi</h4>
  <p class="text-muted"><?= htmlspecialchars($message) ?></p>
  <?php if ($devVerifyLink): ?>
    <div class="alert alert-warning text-start small mt-3">
      <strong>Mode Dev:</strong> SMTP belum dikonfigurasi. Klik link berikut untuk verifikasi manual:<br>
      <a href="<?= htmlspecialchars($devVerifyLink) ?>"><?= htmlspecialchars($devVerifyLink) ?></a>
    </div>
  <?php endif; ?>
  <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-success mt-2">Kembali ke Login</a>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
