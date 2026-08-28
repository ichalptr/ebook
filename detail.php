<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/cover_helper.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT b.*, c.name AS category_name FROM books b
                        LEFT JOIN categories c ON c.id = b.category_id
                        WHERE b.id = :id");
$stmt->execute([':id' => $id]);
$book = $stmt->fetch();

if (!$book) {
    http_response_code(404);
    echo '<div class="container py-5 text-center"><h4>Buku tidak ditemukan.</h4>
          <a href="' . BASE_URL . '/katalog.php" class="btn btn-forest mt-3">Kembali ke Katalog</a></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pdo->prepare("UPDATE books SET views = views + 1 WHERE id = :id")->execute([':id' => $id]);

$isFavorite = false;
$progress = null;
if ($user) {
    $f = $pdo->prepare("SELECT id FROM favorites WHERE user_id=:u AND book_id=:b");
    $f->execute([':u' => $user['id'], ':b' => $id]);
    $isFavorite = (bool)$f->fetch();

    $r = $pdo->prepare("SELECT current_page FROM reading_history WHERE user_id=:u AND book_id=:b");
    $r->execute([':u' => $user['id'], ':b' => $id]);
    $progress = $r->fetch();
}
?>

<div class="container py-4">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small mb-0">
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/katalog.php" class="text-decoration-none">Katalog</a></li>
      <?php if ($book['category_name']): ?>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/katalog.php?category=<?= (int)$book['category_id'] ?>" class="text-decoration-none"><?= htmlspecialchars($book['category_name']) ?></a></li>
      <?php endif; ?>
      <li class="breadcrumb-item active text-truncate" style="max-width:260px;" aria-current="page"><?= htmlspecialchars($book['title']) ?></li>
    </ol>
  </nav>

  <div class="detail-panel">
    <div class="row g-4">
      <div class="col-md-3">
        <div class="rounded-4 shadow w-100 overflow-hidden" style="aspect-ratio:2/3;">
          <?= book_cover_html($book) ?>
        </div>
        <?php if ($book['is_downloadable'] && $book['file_path']): ?>
          <a href="<?= filter_var($book['file_path'], FILTER_VALIDATE_URL) ? htmlspecialchars($book['file_path']) : UPLOAD_BOOK_URL . htmlspecialchars($book['file_path']) ?>" class="btn btn-outline-forest w-100 mt-3" download>
            <i class="bi bi-download"></i> Unduh PDF
          </a>
        <?php endif; ?>
      </div>
      <div class="col-md-9">
        <div class="d-flex gap-2 flex-wrap mb-3">
          <span class="badge badge-grade"><?= htmlspecialchars($book['category_name'] ?? 'Tanpa kategori') ?></span>
          <span class="badge" style="background:var(--clay-100); color:var(--clay-600);"><?= htmlspecialchars($book['grade_level']) ?></span>
        </div>
        <h2 class="mb-2"><?= htmlspecialchars($book['title']) ?></h2>

        <ul class="detail-meta">
          <li><i class="bi bi-person"></i> <?= htmlspecialchars($book['author'] ?: 'Penulis tidak diketahui') ?></li>
          <?php if ($book['publisher']): ?><li><i class="bi bi-building"></i> <?= htmlspecialchars($book['publisher']) ?></li><?php endif; ?>
          <?php if ($book['year_published']): ?><li><i class="bi bi-calendar3"></i> <?= htmlspecialchars((string)$book['year_published']) ?></li><?php endif; ?>
          <li><i class="bi bi-eye"></i> <?= (int)$book['views'] ?> dibaca</li>
        </ul>

        <p class="detail-synopsis"><?= nl2br(htmlspecialchars($book['description'] ?: 'Belum ada sinopsis.')) ?></p>

        <?php if ($progress): ?>
          <div class="progress-note mb-3">
            <i class="bi bi-bookmark-fill"></i> Kamu terakhir membaca sampai halaman <strong><?= (int)$progress['current_page'] ?></strong>.
          </div>
        <?php endif; ?>

        <div class="d-flex gap-2 flex-wrap">
          <?php if ($book['file_path']): ?>
            <a href="<?= BASE_URL ?>/baca.php?id=<?= $book['id'] ?>" class="btn btn-forest btn-lg">
              <i class="bi bi-book"></i> <?= $progress ? 'Lanjutkan Membaca' : 'Baca Buku' ?>
            </a>
          <?php else: ?>
            <button class="btn btn-secondary btn-lg" disabled>File belum tersedia</button>
          <?php endif; ?>

          <?php if ($user): ?>
            <button id="favBtn" data-book="<?= $book['id'] ?>" data-active="<?= $isFavorite ? 1 : 0 ?>" class="btn btn-lg btn-fav <?= $isFavorite ? 'is-active' : '' ?>">
              <i class="bi <?= $isFavorite ? 'bi-heart-fill' : 'bi-heart' ?>"></i> Favorit
            </button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('favBtn')?.addEventListener('click', async function () {
  const btn = this;
  const res = await fetch('<?= BASE_URL ?>/ajax/toggle_favorite.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'book_id=' + btn.dataset.book
  });
  const data = await res.json();
  if (data.status === 'ok') {
    btn.dataset.active = data.active ? 1 : 0;
    btn.classList.toggle('is-active', data.active);
    btn.querySelector('i').className = 'bi ' + (data.active ? 'bi-heart-fill' : 'bi-heart');
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
