<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

require_once __DIR__ . '/includes/mailer.php';

$error = '';
$registered = false;
$devVerifyLink = null; // hanya terisi kalau SMTP belum dikonfigurasi (mode dev)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = in_array($_POST['role'] ?? '', ['student', 'teacher']) ? $_POST['role'] : 'student';
    $school   = trim($_POST['school'] ?? '');
    $grade    = trim($_POST['grade_level'] ?? '');

    if (!$name || !$email || !$password) {
        $error = 'Semua kolom wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $check->execute([':email' => $email]);
        if ($check->fetch()) {
            $error = 'Email sudah terdaftar.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(32));
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, school, grade_level, verification_token)
                                    VALUES (:name, :email, :pass, :role, :school, :grade, :token)");
            $stmt->execute([
                ':name' => $name, ':email' => $email, ':pass' => $hash,
                ':role' => $role, ':school' => $school ?: null, ':grade' => $grade ?: null,
                ':token' => $token,
            ]);

            $verifyLink = BASE_URL . '/verify.php?token=' . $token;
            $bodyHtml = "<p>Halo <strong>" . htmlspecialchars($name) . "</strong>,</p>
                <p>Terima kasih sudah mendaftar di Pamulihan E-Library. Klik tombol di bawah untuk memverifikasi email kamu:</p>
                <p><a href=\"{$verifyLink}\" style=\"background:#1f7a3d;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;\">Verifikasi Email</a></p>
                <p>Atau salin link ini: {$verifyLink}</p>";

            $sent = send_email($email, 'Verifikasi Email — Pamulihan E-Library', $bodyHtml);
            if (!$sent) {
                $devVerifyLink = $verifyLink; // SMTP belum dikonfigurasi, tampilkan link manual
            }
            $registered = true;
        }
    }
}

$page_title = 'Daftar';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container py-5" style="max-width:480px;">
  <?php if ($registered): ?>
    <div class="card p-4 shadow-sm text-center">
      <i class="bi bi-envelope-check display-4 text-success mb-3"></i>
      <h4>Cek Email Kamu</h4>
      <p class="text-muted">Kami sudah mengirim link verifikasi ke email kamu. Klik link tersebut untuk mengaktifkan akun sebelum bisa login.</p>
      <?php if ($devVerifyLink): ?>
        <div class="alert alert-warning text-start small mt-3">
          <strong>Mode Dev:</strong> SMTP belum dikonfigurasi di <code>config/db.php</code>, jadi email tidak benar-benar terkirim.
          Klik link berikut untuk verifikasi manual:<br>
          <a href="<?= htmlspecialchars($devVerifyLink) ?>"><?= htmlspecialchars($devVerifyLink) ?></a>
        </div>
      <?php endif; ?>
      <a href="<?= BASE_URL ?>/login.php" class="btn btn-success mt-2">Ke Halaman Masuk</a>
    </div>
  <?php else: ?>
  <h3 class="mb-4 text-center"><i class="bi bi-person-plus"></i> Daftar Akun</h3>
  <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="post" class="card p-4 shadow-sm">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="mb-3">
      <label class="form-label">Nama Lengkap</label>
      <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" minlength="6" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Daftar sebagai</label>
      <select name="role" class="form-select">
        <option value="student">Siswa</option>
        <option value="teacher">Guru</option>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Sekolah</label>
      <input type="text" name="school" class="form-control" placeholder="Contoh: SDN 1 Pamulihan" value="<?= htmlspecialchars($_POST['school'] ?? '') ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Jenjang</label>
      <select name="grade_level" class="form-select">
        <option value="">-</option>
        <option value="SD">SD</option>
        <option value="SMP">SMP</option>
        <option value="SMA/SMK">SMA/SMK</option>
      </select>
    </div>
    <button class="btn btn-success w-100">Daftar</button>
    <p class="text-center small mt-3 mb-0">Sudah punya akun? <a href="<?= BASE_URL ?>/login.php">Masuk</a></p>
  </form>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
