<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = current_user();
$page_title = 'Rak Saya';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/cover_helper.php';
require_once __DIR__ . '/includes/book_card_helper.php';

$favStmt = $pdo->prepare("SELECT b.*, c.name AS category_name FROM favorites f
                           JOIN books b ON b.id = f.book_id
                           LEFT JOIN categories c ON c.id = b.category_id
                           WHERE f.user_id = :u ORDER BY f.created_at DESC");
$favStmt->execute([':u' => $user['id']]);
$favorites = $favStmt->fetchAll();

$histStmt = $pdo->prepare("SELECT b.*, rh.current_page, rh.last_read_at FROM reading_history rh
                            JOIN books b ON b.id = rh.book_id
                            WHERE rh.user_id = :u ORDER BY rh.last_read_at DESC");
$histStmt->execute([':u' => $user['id']]);
$history = $histStmt->fetchAll();
?>
<div class="container py-4">
  <h3 class="mb-4"><i class="bi bi-bookshelf"></i> Rak Saya</h3>

  <ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-lanjut">Lanjutkan Membaca <span class="badge bg-secondary rounded-pill ms-1"><?= count($history) ?></span></a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-favorit">Favorit <span class="badge bg-secondary rounded-pill ms-1"><?= count($favorites) ?></span></a></li>
  </ul>

  <div class="tab-content">
    <div class="tab-pane fade show active" id="tab-lanjut">
      <?php if (!$history): ?>
        <div class="text-center text-muted py-5">
          <i class="bi bi-journal-bookmark display-4"></i>
          <p class="mt-2 mb-3">Kamu belum mulai membaca buku apa pun.</p>
          <a href="<?= BASE_URL ?>/katalog.php" class="btn btn-forest btn-sm">Jelajahi Katalog</a>
        </div>
      <?php else: ?>
      <div class="row g-3">
        <?php foreach ($history as $h): ?>
          <div class="col-md-6">
            <div class="card p-3 d-flex flex-row gap-3 align-items-center">
              <div class="rounded shadow-sm overflow-hidden flex-shrink-0" style="width:60px;height:90px;">
                <?= book_cover_html($h) ?>
              </div>
              <div class="flex-grow-1 min-w-0">
                <h6 class="mb-1 text-truncate"><?= htmlspecialchars($h['title']) ?></h6>
                <small class="text-muted">Halaman <?= (int)$h['current_page'] ?><?= $h['page_count'] ? ' / ' . (int)$h['page_count'] : '' ?></small>
                <div class="mt-2">
                  <a href="<?= BASE_URL ?>/baca.php?id=<?= $h['id'] ?>" class="btn btn-sm btn-forest">Lanjutkan</a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="tab-pane fade" id="tab-favorit">
      <?php if (!$favorites): ?>
        <div class="text-center text-muted py-5">
          <i class="bi bi-heart display-4"></i>
          <p class="mt-2 mb-3">Belum ada buku favorit.</p>
          <a href="<?= BASE_URL ?>/katalog.php" class="btn btn-forest btn-sm">Jelajahi Katalog</a>
        </div>
      <?php else: ?>
      <div class="row g-3">
        <?php foreach ($favorites as $b): render_book_card($b); endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
