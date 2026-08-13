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
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = in_array($_POST['role'] ?? '', ['student', 'teacher']) ? $_POST['role'] : 'student';
    $school   = trim($_POST['school'] ?? '');
    $grade    = trim($_POST['grade_level'] ?? '');

    if (!$name || !$email || !$password) {
        $error = 'Semua kolom wajib diisi.';
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $check->execute([':email' => $email]);
        if ($check->fetch()) {
            $error = 'Email sudah terdaftar.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, school, grade_level)
                                    VALUES (:name, :email, :pass, :role, :school, :grade)");
            $stmt->execute([
                ':name' => $name, ':email' => $email, ':pass' => $hash,
                ':role' => $role, ':school' => $school ?: null, ':grade' => $grade ?: null,
            ]);
            $newUser = ['id' => $pdo->lastInsertId(), 'name' => $name, 'role' => $role, 'email' => $email];
            login_user($newUser);
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
    }
}

$page_title = 'Daftar';
require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-body">
<div class="container py-5" style="max-width:480px;">
  <a href="<?= BASE_URL ?>/index.php" class="auth-brand justify-content-center">
    <i class="bi bi-book-half"></i> Pamulihan E-Library
  </a>
  <div class="auth-card">
    <h4 class="mb-1 auth-title text-center">Buat Akun Baru</h4>
    <p class="text-muted small text-center mb-4">Gratis, khusus untuk siswa &amp; guru Desa Pamulihan.</p>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="mb-3">
        <label class="form-label">Nama Lengkap</label>
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
      <div class="mb-3">
        <label class="form-label">Daftar sebagai</label>
        <select name="role" class="form-select">
          <option value="student">Siswa</option>
          <option value="teacher">Guru</option>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Sekolah</label>
        <input type="text" name="school" class="form-control" placeholder="Contoh: SDN 1 Pamulihan">
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
      <button class="btn btn-brand w-100">Daftar</button>
      <p class="text-center small mt-3 mb-0">Sudah punya akun? <a href="<?= BASE_URL ?>/login.php">Masuk</a></p>
    </form>
  </div>
</div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
