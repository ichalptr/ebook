<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = current_user();
$page_title = 'Rak Saya';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/cover_helper.php';
require_once __DIR__ . '/includes/book_card_helper.php'; // <-- NEW

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
  <div class="section-heading">
    <span class="kicker">Halo, <?= htmlspecialchars($user['name']) ?></span>
  </div>
  <h3 class="mb-4"><i class="bi bi-bookshelf"></i> Rak Saya</h3>

  <ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-lanjut">Lanjutkan Membaca</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-favorit">Favorit</a></li>
  </ul>

  <div class="tab-content">
    <div class="tab-pane fade show active" id="tab-lanjut">
      <?php if (!$history): ?>
        <div class="empty-state">
          <i class="bi bi-journal-bookmark"></i>
          <p class="mt-2 mb-1 fw-semibold">Belum ada buku yang sedang kamu baca.</p>
          <p class="small mb-3">Yuk mulai baca buku pertamamu.</p>
          <a href="<?= BASE_URL ?>/katalog.php" class="btn btn-brand btn-sm">Jelajahi Katalog</a>
        </div>
      <?php else: ?>
      <div class="row g-3">
        <?php foreach ($history as $h): ?>
          <div class="col-md-6">
            <div class="card p-3 d-flex flex-row gap-3 align-items-center">
              <div class="book-cover-wrap rounded-3" style="width:60px;height:90px;flex-shrink:0;">
                <?= book_cover_html($h) ?>
              </div>
              <div class="flex-grow-1">
                <h6 class="mb-1"><?= htmlspecialchars($h['title']) ?></h6>
                <small class="text-muted">Halaman <?= (int)$h['current_page'] ?><?= $h['page_count'] ? ' / ' . (int)$h['page_count'] : '' ?></small>
                <div class="mt-2">
                  <a href="<?= BASE_URL ?>/baca.php?id=<?= $h['id'] ?>" class="btn btn-sm btn-brand">Lanjutkan</a>
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
        <div class="empty-state">
          <i class="bi bi-heart"></i>
          <p class="mt-2 mb-1 fw-semibold">Belum ada buku favorit.</p>
          <p class="small mb-0">Tandai buku dengan tombol favorit di halaman detail.</p>
        </div>
      <?php else: ?>
      <div class="row g-3">
        <?php foreach ($favorites as $b): render_book_card($b, 'col-6 col-md-3'); endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>