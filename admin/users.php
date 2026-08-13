<?php
$page_title = 'Pengguna';
require_once __DIR__ . '/../includes/admin_header.php';

if (($_GET['delete'] ?? null)) {
    $delId = (int)$_GET['delete'];
    if ($delId !== (int)$_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM users WHERE id = :id")->execute([':id' => $delId]);
    }
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

$role = $_GET['role'] ?? '';
$sql = "SELECT * FROM users";
$params = [];
if ($role) { $sql .= " WHERE role = :role"; $params[':role'] = $role; }
$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<div class="card p-3">
  <div class="d-flex gap-2 mb-3">
    <a href="?role=" class="btn btn-sm <?= $role === '' ? 'btn-success' : 'btn-outline-success' ?>">Semua</a>
    <a href="?role=student" class="btn btn-sm <?= $role === 'student' ? 'btn-success' : 'btn-outline-success' ?>">Siswa</a>
    <a href="?role=teacher" class="btn btn-sm <?= $role === 'teacher' ? 'btn-success' : 'btn-outline-success' ?>">Guru</a>
    <a href="?role=admin" class="btn btn-sm <?= $role === 'admin' ? 'btn-success' : 'btn-outline-success' ?>">Admin</a>
  </div>

  <table class="table table-hover align-middle">
    <thead class="table-light"><tr><th>Nama</th><th>Email</th><th>Role</th><th>Sekolah</th><th>Jenjang</th><th>Terdaftar</th><th>Aksi</th></tr></thead>
    <tbody>
      <?php foreach ($users as $u): ?>
      <tr>
        <td><?= htmlspecialchars($u['name']) ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td><span class="badge bg-secondary"><?= htmlspecialchars($u['role']) ?></span></td>
        <td><?= htmlspecialchars($u['school'] ?? '-') ?></td>
        <td><?= htmlspecialchars($u['grade_level'] ?? '-') ?></td>
        <td class="small text-muted"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
        <td>
          <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
          <a href="?delete=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger"
             onclick="return confirm('Hapus pengguna ini?')"><i class="bi bi-trash"></i></a>
          <?php else: ?>
            <span class="text-muted small">(kamu)</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
