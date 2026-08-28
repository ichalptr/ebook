<?php
$page_title = 'Rekomendasi Buku';
require_once __DIR__ . '/../includes/guru_header.php';

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $studentId = (int)($_POST['student_id'] ?? 0);
    $bookId    = (int)($_POST['book_id'] ?? 0);
    $note      = trim($_POST['note'] ?? '');

    if (!$studentId || !$bookId) {
        $error = 'Pilih siswa dan buku terlebih dahulu.';
    } else {
        $checkStudent = $pdo->prepare("SELECT id FROM users WHERE id = :id AND role = 'student'");
        $checkStudent->execute([':id' => $studentId]);
        $checkBook = $pdo->prepare("SELECT id FROM books WHERE id = :id");
        $checkBook->execute([':id' => $bookId]);

        if (!$checkStudent->fetch() || !$checkBook->fetch()) {
            $error = 'Siswa atau buku tidak valid.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO recommendations (teacher_id, student_id, book_id, note)
                                    VALUES (:t, :s, :b, :n)
                                    ON DUPLICATE KEY UPDATE note = VALUES(note), created_at = CURRENT_TIMESTAMP");
            $stmt->execute([
                ':t' => $user['id'], ':s' => $studentId, ':b' => $bookId,
                ':n' => $note ?: null,
            ]);
            $success = true;
        }
    }
}

$students = $pdo->query("SELECT id, name, school, grade_level FROM users WHERE role='student' ORDER BY name")->fetchAll();
$books    = $pdo->query("SELECT id, title, author FROM books ORDER BY title")->fetchAll();

$myRecs = $pdo->prepare("SELECT r.*, b.title AS book_title, u.name AS student_name
                          FROM recommendations r
                          JOIN books b ON b.id = r.book_id
                          JOIN users u ON u.id = r.student_id
                          WHERE r.teacher_id = :t
                          ORDER BY r.created_at DESC");
$myRecs->execute([':t' => $user['id']]);
$allRecs = $myRecs->fetchAll();
?>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="card p-3">
      <h6><i class="bi bi-send-check text-success"></i> Rekomendasi Baru</h6>

      <?php if ($success): ?>
        <div class="alert alert-success">Rekomendasi berhasil dikirim ke siswa.</div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if (!$students): ?>
        <p class="text-muted">Belum ada siswa terdaftar.</p>
      <?php elseif (!$books): ?>
        <p class="text-muted">Belum ada buku di katalog.</p>
      <?php else: ?>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="mb-3">
          <label class="form-label">Siswa</label>
          <select name="student_id" class="form-select" required>
            <option value="">Pilih siswa...</option>
            <?php foreach ($students as $s): ?>
              <option value="<?= $s['id'] ?>">
                <?= htmlspecialchars($s['name']) ?><?= $s['school'] ? ' — ' . htmlspecialchars($s['school']) : '' ?><?= $s['grade_level'] ? ' (' . htmlspecialchars($s['grade_level']) . ')' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Buku</label>
          <select name="book_id" class="form-select" required>
            <option value="">Pilih buku...</option>
            <?php foreach ($books as $b): ?>
              <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['title']) ?><?= $b['author'] ? ' — ' . htmlspecialchars($b['author']) : '' ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Catatan (opsional)</label>
          <textarea name="note" class="form-control" rows="3" maxlength="255" placeholder="Misal: cocok buat latihan literasi kelas 5"></textarea>
        </div>
        <button class="btn btn-success w-100"><i class="bi bi-send"></i> Kirim Rekomendasi</button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card p-3 h-100">
      <h6><i class="bi bi-list-check text-success"></i> Semua Rekomendasi Saya</h6>
      <?php if ($allRecs): ?>
        <table class="table table-sm align-middle mb-0">
          <thead>
            <tr><th>Siswa</th><th>Buku</th><th>Catatan</th><th>Dikirim</th></tr>
          </thead>
          <tbody>
            <?php foreach ($allRecs as $r): ?>
              <tr>
                <td><?= htmlspecialchars($r['student_name']) ?></td>
                <td><?= htmlspecialchars($r['book_title']) ?></td>
                <td class="text-muted small"><?= htmlspecialchars($r['note'] ?: '-') ?></td>
                <td class="text-muted small"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="text-muted mb-0">Belum ada rekomendasi yang dikirim.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/guru_footer.php'; ?>
