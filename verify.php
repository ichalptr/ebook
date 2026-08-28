<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$token = $_GET['token'] ?? '';
$status = 'invalid';
$verifiedUser = null;

if ($token) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE verification_token = :t");
    $stmt->execute([':t' => $token]);
    $u = $stmt->fetch();

    if ($u) {
        $pdo->prepare("UPDATE users SET email_verified_at = NOW(), verification_token = NULL WHERE id = :id")
            ->execute([':id' => $u['id']]);
        $status = 'ok';
        $verifiedUser = $u;
    }
}

$page_title = 'Verifikasi Email';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container py-5 text-center" style="max-width:480px;">
  <?php if ($status === 'ok'): ?>
    <i class="bi bi-check-circle display-4 text-success mb-3"></i>
    <h4>Email Berhasil Diverifikasi!</h4>
    <p class="text-muted">Akun kamu, <?= htmlspecialchars($verifiedUser['name']) ?>, sudah aktif. Silakan masuk untuk mulai membaca.</p>
    <a href="<?= BASE_URL ?>/login.php" class="btn btn-success mt-2">Masuk Sekarang</a>
  <?php else: ?>
    <i class="bi bi-x-circle display-4 text-danger mb-3"></i>
    <h4>Link Verifikasi Tidak Valid</h4>
    <p class="text-muted">Link ini sudah tidak berlaku, mungkin karena akun sudah pernah diverifikasi sebelumnya.</p>
    <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-success mt-2">Coba Masuk</a>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
