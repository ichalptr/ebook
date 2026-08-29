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

$roleLabels = [
    'admin' => ['Admin', 'pill-clay'],
    'teacher' => ['Guru', 'pill-turmeric'],
    'student' => ['Siswa', 'pill-forest'],
];
$tabs = ['' => 'Semua', 'student' => 'Siswa', 'teacher' => 'Guru', 'admin' => 'Admin'];
?>

<div class="admin-card">
  <div class="admin-card-pad pb-0">
    <div class="d-flex gap-2 mb-3 flex-wrap">
      <?php foreach ($tabs as $val => $label): ?>
        <a href="?role=<?= $val ?>" class="btn btn-sm <?= $role === $val ? 'btn-forest' : 'btn-outline-forest' ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Sekolah</th><th>Jenjang</th><th>Terdaftar</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): $rl = $roleLabels[$u['role']] ?? [$u['role'], 'pill-ink']; ?>
        <tr>
          <td class="fw-semibold"><?= htmlspecialchars($u['name']) ?></td>
          <td class="text-muted"><?= htmlspecialchars($u['email']) ?></td>
          <td><span class="pill <?= $rl[1] ?>"><?= htmlspecialchars($rl[0]) ?></span></td>
          <td><?= htmlspecialchars($u['school'] ?? '-') ?></td>
          <td><?= htmlspecialchars($u['grade_level'] ?? '-') ?></td>
          <td class="small text-muted"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
          <td class="text-end">
            <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
            <a href="?delete=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger"
               onclick="return confirm('Hapus pengguna ini?')"><i class="bi bi-trash"></i></a>
            <?php else: ?>
              <span class="pill pill-ink">Kamu</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$users): ?>
          <tr><td colspan="7">
            <div class="admin-empty"><i class="bi bi-people"></i><p>Belum ada pengguna untuk filter ini.</p></div>
          </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
