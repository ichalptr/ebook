<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Rate limiting: kunci akun sementara setelah beberapa kali gagal login
const MAX_LOGIN_ATTEMPTS = 5;
const LOCKOUT_MINUTES = 5;

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    $isLocked = $user && $user['locked_until'] && strtotime($user['locked_until']) > time();

    if ($isLocked) {
        $minutesLeft = (int)ceil((strtotime($user['locked_until']) - time()) / 60);
        $error = "Akun ini sementara dikunci karena terlalu banyak percobaan gagal. Coba lagi dalam {$minutesLeft} menit.";
    } elseif ($user && password_verify($password, $user['password'])) {
        if (!$user['email_verified_at']) {
            $error = 'Email kamu belum diverifikasi.';
            $unverifiedEmail = $user['email'];
        } else {
            // Login berhasil — reset counter percobaan gagal
            $pdo->prepare("UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = :id")
                ->execute([':id' => $user['id']]);

            login_user($user);
            $redirect = match ($user['role']) {
                'admin'   => BASE_URL . '/admin/dashboard.php',
                'teacher' => BASE_URL . '/guru/dashboard.php',
                default   => BASE_URL . '/index.php',
            };
            header('Location: ' . $redirect);
            exit;
        }
    } else {
        if ($user) {
            $attempts = (int)$user['failed_attempts'] + 1;
            if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                $lockUntil = date('Y-m-d H:i:s', time() + LOCKOUT_MINUTES * 60);
                $pdo->prepare("UPDATE users SET failed_attempts = :a, locked_until = :l WHERE id = :id")
                    ->execute([':a' => $attempts, ':l' => $lockUntil, ':id' => $user['id']]);
                $error = "Terlalu banyak percobaan gagal. Akun dikunci selama " . LOCKOUT_MINUTES . " menit.";
            } else {
                $pdo->prepare("UPDATE users SET failed_attempts = :a WHERE id = :id")
                    ->execute([':a' => $attempts, ':id' => $user['id']]);
                $sisa = MAX_LOGIN_ATTEMPTS - $attempts;
                $error = "Email atau password salah. Sisa percobaan: {$sisa}.";
            }
        } else {
            // Email tidak ditemukan — pesan generik biar tidak bocorin email mana yang terdaftar
            $error = 'Email atau password salah.';
        }
    }
}

$page_title = 'Masuk';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container py-5" style="max-width:420px;">
  <h3 class="mb-4 text-center"><i class="bi bi-box-arrow-in-right"></i> Masuk</h3>
  <?php if ($error): ?>
    <div class="alert alert-danger">
      <?= htmlspecialchars($error) ?>
      <?php if (!empty($unverifiedEmail)): ?>
        Cek email untuk link verifikasi, atau
        <a href="<?= BASE_URL ?>/resend_verification.php?email=<?= urlencode($unverifiedEmail) ?>">kirim ulang link verifikasi</a>.
      <?php endif; ?>
    </div>
  <?php endif; ?>
  <form method="post" class="card p-4 shadow-sm">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" required autofocus>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required>
      <div class="text-end mt-1"><a href="<?= BASE_URL ?>/forgot_password.php" class="small">Lupa password?</a></div>
    </div>
    <button class="btn btn-success w-100">Masuk</button>
    <p class="text-center small mt-3 mb-0">Belum punya akun? <a href="<?= BASE_URL ?>/register.php">Daftar di sini</a></p>
  </form>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
