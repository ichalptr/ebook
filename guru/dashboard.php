<?php
$page_title = 'Dashboard Guru';
require_once __DIR__ . '/../includes/guru_header.php';

$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$totalBooks    = (int)$pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();

$recCountStmt = $pdo->prepare("SELECT COUNT(*) FROM recommendations WHERE teacher_id = :t");
$recCountStmt->execute([':t' => $user['id']]);
$myRecommendationCount = (int)$recCountStmt->fetchColumn();

$recentStmt = $pdo->prepare("SELECT r.*, b.title AS book_title, u.name AS student_name
                              FROM recommendations r
                              JOIN books b ON b.id = r.book_id
                              JOIN users u ON u.id = r.student_id
                              WHERE r.teacher_id = :t
                              ORDER BY r.created_at DESC LIMIT 8");
$recentStmt->execute([':t' => $user['id']]);
$recentRecs = $recentStmt->fetchAll();
?>

<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="card p-3"><div class="text-muted small">Siswa Terdaftar</div><div class="fs-3 fw-bold text-success"><?= $totalStudents ?></div></div></div>
  <div class="col-md-4"><div class="card p-3"><div class="text-muted small">Buku di Katalog</div><div class="fs-3 fw-bold text-success"><?= $totalBooks ?></div></div></div>
  <div class="col-md-4"><div class="card p-3"><div class="text-muted small">Rekomendasi Terkirim</div><div class="fs-3 fw-bold text-success"><?= $myRecommendationCount ?></div></div></div>
</div>

<div class="card p-3 mb-4 d-flex flex-row justify-content-between align-items-center flex-wrap gap-2">
  <div>
    <strong>Rekomendasikan Buku ke Siswa</strong>
    <p class="text-muted small mb-0">Pilih buku yang cocok untuk siswa tertentu, lengkap dengan catatan singkat.</p>
  </div>
  <a href="<?= BASE_URL ?>/guru/rekomendasi.php" class="btn btn-success btn-sm"><i class="bi bi-plus-circle"></i> Buat Rekomendasi</a>
</div>

<div class="card p-3">
  <h6><i class="bi bi-clock-history text-success"></i> Rekomendasi Terbaru Saya</h6>
  <table class="table table-sm mb-0">
    <?php foreach ($recentRecs as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['book_title']) ?></td>
        <td class="text-muted">untuk <?= htmlspecialchars($r['student_name']) ?></td>
        <td class="text-muted small text-end"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$recentRecs): ?><tr><td class="text-muted">Kamu belum memberikan rekomendasi buku apa pun.</td></tr><?php endif; ?>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/guru_footer.php'; ?>
