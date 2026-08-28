<?php
require_once __DIR__ . '/cover_helper.php';

function render_book_card(array $book, string $wrapClass = 'col-6 col-md-3'): void {
    $synopsis = trim(strip_tags($book['description'] ?? ''));
    ?>
    <div class="<?= htmlspecialchars($wrapClass) ?>">
        <a href="<?= BASE_URL ?>/detail.php?id=<?= (int)$book['id'] ?>" class="text-decoration-none text-dark">
            <div class="card book-card">
                <div class="book-cover-wrap">
                    <?= book_cover_html($book) ?>
                    <?php if ($synopsis): ?>
                        <div class="card-synopsis"><?= htmlspecialchars(mb_strimwidth($synopsis, 0, 160, '…')) ?></div>
                    <?php endif; ?>
                </div>
                <div class="card-body p-2">
                    <span class="badge bg-success-subtle text-success-emphasis badge-grade mb-1"><?= htmlspecialchars($book['grade_level']) ?></span>
                    <h6 class="mb-0 text-truncate"><?= htmlspecialchars($book['title']) ?></h6>
                    <small class="text-muted text-truncate d-block"><?= htmlspecialchars($book['author'] ?? '-') ?></small>
                </div>
            </div>
        </a>
    </div>
    <?php
}