<?php
require_once __DIR__ . '/includes/header.php';

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

function book_cover_src(array $book): string {
    if (!empty($book['cover_image'])) {
        if (filter_var($book['cover_image'], FILTER_VALIDATE_URL)) return htmlspecialchars($book['cover_image']);
        return UPLOAD_COVER_URL . htmlspecialchars($book['cover_image']);
    }
    return 'https://via.placeholder.com/300x450/1F4D3A/FAF6EC?text=' . urlencode($book['title']);
}
?>

<div class="container py-4">
  <div class="row g-4">
    <div class="col-md-3">
      <div class="position-relative">
        <img src="<?= book_cover_src($book) ?>" class="img-fluid rounded shadow w-100" alt="<?= htmlspecialchars($book['title']) ?>" style="aspect-ratio:2/3;object-fit:cover;">
      </div>
    </div>
    <div class="col-md-9">
      <span class="badge badge-grade me-1"><?= htmlspecialchars($book['category_name'] ?? 'Tanpa kategori') ?></span>
      <span class="badge" style="background:var(--clay-100); color:var(--clay-600);"><?= htmlspecialchars($book['grade_level']) ?></span>
      <h2 class="mt-2"><?= htmlspecialchars($book['title']) ?></h2>
      <p class="text-muted mb-1">Penulis: <?= htmlspecialchars($book['author'] ?? '-') ?></p>
      <p class="text-muted mb-3">
        <?= $book['publisher'] ? 'Penerbit: ' . htmlspecialchars($book['publisher']) . ' &middot; ' : '' ?>
        <?= $book['year_published'] ? 'Tahun: ' . htmlspecialchars((string)$book['year_published']) : '' ?>
        &middot; <i class="bi bi-eye"></i> <?= (int)$book['views'] ?> dibaca
      </p>
      <p><?= nl2br(htmlspecialchars($book['description'] ?? 'Belum ada sinopsis.')) ?></p>

      <?php if ($progress): ?>
        <div class="alert py-2 small" style="background:var(--turmeric-100); border:1px solid var(--turmeric-400); color:var(--ink-800);">
          <i class="bi bi-bookmark-fill" style="color:var(--clay-500);"></i> Kamu terakhir membaca sampai halaman <strong><?= (int)$progress['current_page'] ?></strong>.
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

        <?php if ($book['is_downloadable'] && $book['file_path']): ?>
          <a href="<?= filter_var($book['file_path'], FILTER_VALIDATE_URL) ? htmlspecialchars($book['file_path']) : UPLOAD_BOOK_URL . htmlspecialchars($book['file_path']) ?>" class="btn btn-outline-forest btn-lg" download>
            <i class="bi bi-download"></i> Unduh
          </a>
        <?php endif; ?>

        <?php if ($user): ?>
          <button id="favBtn" data-book="<?= $book['id'] ?>" data-active="<?= $isFavorite ? 1 : 0 ?>"
                  class="btn btn-lg" style="border:1.5px solid var(--clay-500); color:var(--clay-600); background:transparent; border-radius:999px;">
            <i class="bi <?= $isFavorite ? 'bi-heart-fill' : 'bi-heart' ?>"></i> Favorit
          </button>
        <?php endif; ?>
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
    btn.querySelector('i').className = 'bi ' + (data.active ? 'bi-heart-fill' : 'bi-heart');
    btn.style.background = data.active ? 'var(--clay-500)' : 'transparent';
    btn.style.color = data.active ? '#fff' : 'var(--clay-600)';
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
