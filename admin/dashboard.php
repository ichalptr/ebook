<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/../includes/admin_header.php';

$totalBooks      = (int)$pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$totalCategories = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalStudents   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$totalTeachers   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn();
$missingFiles    = (int)$pdo->query("SELECT COUNT(*) FROM books WHERE file_path IS NULL OR file_path = ''")->fetchColumn();
$completePct     = $totalBooks > 0 ? (int)round((($totalBooks - $missingFiles) / $totalBooks) * 100) : 0;

$mostRead     = $pdo->query("SELECT id, title, author, views FROM books ORDER BY views DESC LIMIT 5")->fetchAll();
$recentBooks  = $pdo->query("SELECT id, title, author, created_at, source FROM books ORDER BY created_at DESC LIMIT 6")->fetchAll();
$catBreakdown = $pdo->query("SELECT c.name, COUNT(b.id) AS cnt FROM categories c
                              LEFT JOIN books b ON b.category_id = c.id
                              GROUP BY c.id, c.name ORDER BY cnt DESC")->fetchAll();

$sourceLabels = [
    'manual' => ['Manual', 'pill-forest'],
    'google_books' => ['Google Books', 'pill-turmeric'],
    'open_library' => ['Open Library', 'pill-ink'],
];

/** Waktu relatif sederhana untuk activity feed ("3 hari lalu", dst). */
function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'Baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    if ($diff < 7 * 86400) return floor($diff / 86400) . ' hari lalu';
    return date('d M Y', strtotime($datetime));
}

/** Donut kategori dibangun murni dari CSS conic-gradient — tanpa JS/library chart. */
$donutColors = ['#276048', '#D89526', '#C1512E', '#6FAE8E', '#8a5e12', '#A8451F', '#1F4D3A', '#7D8574'];
$catTotal = (int)array_sum(array_column($catBreakdown, 'cnt'));
$cursor = 0;
$gradientParts = [];
foreach ($catBreakdown as $i => $cb) {
    if ($catTotal <= 0 || (int)$cb['cnt'] <= 0) continue;
    $pct = $cb['cnt'] / $catTotal * 100;
    $color = $donutColors[$i % count($donutColors)];
    $gradientParts[] = "$color {$cursor}% " . ($cursor + $pct) . "%";
    $cursor += $pct;
}
$donutGradient = $gradientParts ? implode(', ', $gradientParts) : 'var(--sand-300) 0% 100%';

$viewCounts = array_map('intval', array_column($mostRead, 'views'));
$maxViews = $viewCounts ? max(array_merge($viewCounts, [1])) : 1;
?>

<div class="kpi-grid mb-4">
  <div class="kpi-card" style="--kpi-accent: var(--forest-500);">
    <span class="icon-chip chip-forest"><i class="bi bi-journal-bookmark"></i></span>
    <div class="body"><div class="number"><?= $totalBooks ?></div><div class="label">Total Buku</div></div>
  </div>
  <div class="kpi-card" style="--kpi-accent: var(--turmeric-400);">
    <span class="icon-chip chip-turmeric"><i class="bi bi-tags"></i></span>
    <div class="body"><div class="number"><?= $totalCategories ?></div><div class="label">Kategori</div></div>
  </div>
  <div class="kpi-card" style="--kpi-accent: var(--clay-500);">
    <span class="icon-chip chip-clay"><i class="bi bi-mortarboard"></i></span>
    <div class="body"><div class="number"><?= $totalStudents ?></div><div class="label">Siswa Terdaftar</div></div>
  </div>
  <div class="kpi-card" style="--kpi-accent: var(--ink-400);">
    <span class="icon-chip chip-ink"><i class="bi bi-easel"></i></span>
    <div class="body"><div class="number"><?= $totalTeachers ?></div><div class="label">Guru Terdaftar</div></div>
  </div>
</div>

<div class="admin-card admin-card-pad mb-4">
  <div class="completion-banner">
    <div class="completion-ring" style="background: conic-gradient(var(--forest-600) <?= $completePct ?>%, var(--sand-200) 0);">
      <span class="completion-ring-inner"><?= $completePct ?>%</span>
    </div>
    <div class="completion-track">
      <strong style="font-size:.92rem; color:var(--ink-900);">Kelengkapan File PDF</strong>
      <p class="small text-muted mb-2 mt-1">
        <?= $totalBooks - $missingFiles ?> dari <?= $totalBooks ?> buku sudah punya file.
        <?php if ($missingFiles > 0): ?>
          <strong><?= $missingFiles ?> buku</strong> masih metadata saja — lengkapi lewat
          <a href="<?= BASE_URL ?>/admin/books.php">Semua Buku &rarr; Edit</a>.
        <?php else: ?>
          Semua buku sudah lengkap. 🎉
        <?php endif; ?>
      </p>
      <div class="bar"><span style="width:<?= $completePct ?>%"></span></div>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <div class="col-lg-5">
    <div class="admin-card admin-card-pad h-100">
      <div class="admin-section-title mb-3"><i class="bi bi-pie-chart" style="color:var(--forest-600);"></i> Distribusi Kategori</div>
      <?php if ($catTotal > 0): ?>
      <div class="donut-wrap">
        <div class="donut-chart" style="background:conic-gradient(<?= $donutGradient ?>);">
          <div class="donut-center"><span class="num"><?= $totalBooks ?></span><span class="lbl">Buku</span></div>
        </div>
        <ul class="donut-legend">
          <?php foreach (array_slice($catBreakdown, 0, 6) as $i => $cb): if ((int)$cb['cnt'] <= 0) continue; ?>
            <li><span class="dot" style="background:<?= $donutColors[$i % count($donutColors)] ?>"></span> <?= htmlspecialchars($cb['name']) ?> <span class="val"><?= (int)$cb['cnt'] ?></span></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php else: ?>
        <div class="admin-empty"><i class="bi bi-pie-chart"></i><p>Belum ada data kategori.</p></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="admin-card admin-card-pad h-100">
      <div class="admin-section-title mb-3"><i class="bi bi-fire" style="color:var(--clay-500);"></i> Paling Banyak Dibaca</div>
      <?php if ($mostRead): ?>
        <?php foreach ($mostRead as $i => $b): $pct = $maxViews > 0 ? round((int)$b['views'] / $maxViews * 100) : 0; ?>
          <div class="rank-row">
            <span class="rank-no">#<?= $i + 1 ?></span>
            <span class="rank-title" title="<?= htmlspecialchars($b['title']) ?>"><?= htmlspecialchars($b['title']) ?></span>
            <span class="rank-bar-track"><span class="rank-bar-fill" style="width:<?= max($pct, 3) ?>%"></span></span>
            <span class="rank-value"><?= (int)$b['views'] ?></span>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="admin-empty"><i class="bi bi-bar-chart"></i><p>Belum ada data pembacaan.</p></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="admin-card mb-4">
  <div class="admin-card-pad pb-2 d-flex justify-content-between align-items-center">
    <div class="admin-section-title mb-0"><i class="bi bi-clock-history" style="color:var(--forest-600);"></i> Buku Terbaru Ditambahkan</div>
    <a href="<?= BASE_URL ?>/admin/books.php" class="small fw-semibold text-decoration-none">Lihat semua &rarr;</a>
  </div>
  <div class="admin-card-pad pt-2">
    <?php if ($recentBooks): ?>
      <div class="activity-list">
      <?php foreach ($recentBooks as $b): $src = $sourceLabels[$b['source']] ?? [$b['source'], 'pill-ink']; ?>
        <div class="activity-row">
          <div class="thumb"><?= htmlspecialchars(mb_strtoupper(mb_substr($b['title'], 0, 1))) ?></div>
          <div class="info">
            <strong><?= htmlspecialchars($b['title']) ?></strong>
            <span class="pill <?= $src[1] ?>"><?= htmlspecialchars($src[0]) ?></span>
          </div>
          <span class="time"><?= time_ago($b['created_at']) ?></span>
        </div>
      <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="admin-empty"><i class="bi bi-inbox"></i><p>Belum ada buku.</p></div>
    <?php endif; ?>
  </div>
</div>

<div class="admin-section-title mb-3"><i class="bi bi-lightning-charge" style="color:var(--turmeric-500);"></i> Tambah Konten Cepat</div>
<div class="row g-3">
  <div class="col-md-3 col-6">
    <a href="<?= BASE_URL ?>/admin/book_form.php" class="quick-tile">
      <div class="admin-card admin-card-pad">
        <i class="bi bi-plus-circle fs-4" style="color:var(--forest-600);"></i>
        <div class="fw-semibold mt-2" style="color:var(--ink-900);">Buku Manual</div>
        <div class="small text-muted">Isi satu per satu</div>
      </div>
    </a>
  </div>
  <div class="col-md-3 col-6">
    <a href="<?= BASE_URL ?>/admin/import_official.php" class="quick-tile">
      <div class="admin-card admin-card-pad">
        <i class="bi bi-bank fs-4" style="color:var(--forest-600);"></i>
        <div class="fw-semibold mt-2" style="color:var(--ink-900);">Impor Resmi BTP</div>
        <div class="small text-muted">Data Kemendikdasmen</div>
      </div>
    </a>
  </div>
  <div class="col-md-3 col-6">
    <a href="<?= BASE_URL ?>/admin/bulk_import.php" class="quick-tile">
      <div class="admin-card admin-card-pad">
        <i class="bi bi-cloud-arrow-down fs-4" style="color:var(--forest-600);"></i>
        <div class="fw-semibold mt-2" style="color:var(--ink-900);">Bulk Import</div>
        <div class="small text-muted">Google Books, banyak topik</div>
      </div>
    </a>
  </div>
  <div class="col-md-3 col-6">
    <a href="<?= BASE_URL ?>/admin/import_csv.php" class="quick-tile">
      <div class="admin-card admin-card-pad">
        <i class="bi bi-filetype-csv fs-4" style="color:var(--forest-600);"></i>
        <div class="fw-semibold mt-2" style="color:var(--ink-900);">Import CSV</div>
        <div class="small text-muted">Efisien untuk buku paket</div>
      </div>
    </a>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
