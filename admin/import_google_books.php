<?php
$page_title = 'Google Books';
require_once __DIR__ . '/../includes/admin_header.php';

/**
 * Sumber: Google Books API (gratis, tanpa API key untuk pemakaian ringan).
 * Dokumentasi: https://developers.google.com/books/docs/v1/using
 * Hanya metadata (judul, penulis, sinopsis, cover) — TIDAK ada file PDF.
 * File tetap perlu diunggah/ditempel manual lewat Edit Buku.
 *
 * Halaman ini gabungan dari 2 halaman yang dulu terpisah (import_books.php +
 * bulk_import.php) — keduanya satu sumber data & satu batasan yang sama,
 * cuma beda cara pencariannya (manual satu-satu vs topik siap pakai/bulk).
 */
function search_google_books(string $query, int $maxResults = 12): array {
    $url = 'https://www.googleapis.com/books/v1/volumes?q=' . urlencode($query)
         . '&maxResults=' . $maxResults . '&langRestrict=id&printType=books';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError || !$response) return [];

    $data = json_decode($response, true);
    $items = $data['items'] ?? [];
    $books = [];
    foreach ($items as $item) {
        $info = $item['volumeInfo'] ?? [];
        if (empty($info['title'])) continue;
        $books[] = [
            'external_id' => $item['id'],
            'title'       => $info['title'],
            'author'      => implode(', ', $info['authors'] ?? []),
            'publisher'   => $info['publisher'] ?? '',
            'year'        => isset($info['publishedDate']) ? (int)substr($info['publishedDate'], 0, 4) : null,
            'description' => $info['description'] ?? '',
            'page_count'  => $info['pageCount'] ?? 0,
            'cover'       => $info['imageLinks']['thumbnail'] ?? ($info['imageLinks']['smallThumbnail'] ?? ''),
            'isbn'        => $info['industryIdentifiers'][0]['identifier'] ?? '',
        ];
    }
    return $books;
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$catMap = [];
foreach ($categories as $c) $catMap[$c['name']] = $c['id'];

$checkStmt = $pdo->prepare("SELECT id FROM books WHERE external_id = :ext");
$insertStmt = $pdo->prepare("INSERT INTO books (title, author, publisher, year_published, isbn,
    description, grade_level, category_id, cover_image, page_count, source, external_id)
    VALUES (:t, :a, :pub, :y, :isbn, :d, :g, :c, :cov, :pc, 'google_books', :ext)");

$activeTab = (($_GET['tab'] ?? $_POST['tab'] ?? 'manual') === 'bulk') ? 'bulk' : 'manual';

/* ================= MODE MANUAL: cari & pilih satu per satu ================= */
$results = [];
$query = trim($_GET['q'] ?? '');
$singleMsg = '';
$singleImportedId = null;

if ($activeTab === 'manual' && $query) {
    $results = search_google_books($query);
    // Tandai hasil yang sudah pernah diimpor (dicek dari external_id) supaya
    // kelihatan di kartu SEBELUM diklik — dulu di import_books.php lama tidak
    // ada pengecekan ini sama sekali sebelum insert, beda dengan mode bulk yang
    // sudah cek duplikat. Sekarang keduanya konsisten.
    foreach ($results as &$r) {
        $checkStmt->execute([':ext' => $r['external_id']]);
        $r['already_imported'] = (bool)$checkStmt->fetch();
    }
    unset($r);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'import_single') {
    csrf_check();
    $checkStmt->execute([':ext' => $_POST['external_id']]);
    if ($checkStmt->fetch()) {
        $singleMsg = 'Buku "' . htmlspecialchars($_POST['title']) . '" sudah ada di katalog — dilewati.';
    } else {
        $insertStmt->execute([
            ':t' => $_POST['title'], ':a' => $_POST['author'], ':pub' => $_POST['publisher'],
            ':y' => $_POST['year'] ?: null, ':isbn' => $_POST['isbn'], ':d' => $_POST['description'],
            ':g' => $_POST['grade_level'] ?? 'Umum', ':c' => $_POST['category_id'] ?: null,
            ':cov' => $_POST['cover'], ':pc' => (int)($_POST['page_count'] ?: 0),
            ':ext' => $_POST['external_id'],
        ]);
        $singleImportedId = $pdo->lastInsertId();
        $singleMsg = 'Metadata buku "' . htmlspecialchars($_POST['title']) . '" berhasil diimpor.';
    }
}

/* ================= MODE BULK: topik siap pakai (preset) ================= */
/**
 * Query pencarian per kategori — silakan tambah/ubah sesuai kebutuhan sekolah.
 * Setiap entri: [query pencarian, nama kategori (harus ada di tabel categories), jenjang]
 */
$presetQueries = [
    ['dongeng anak indonesia', 'Cerita', 'SD'],
    ['cerita rakyat nusantara', 'Cerita', 'SD'],
    ['fabel anak', 'Cerita', 'SD'],
    ['sains untuk anak', 'Sains', 'SD'],
    ['ilmu pengetahuan alam sekolah dasar', 'Sains', 'SD'],
    ['matematika sekolah dasar', 'Matematika', 'SD'],
    ['matematika sekolah menengah pertama', 'Matematika', 'SMP'],
    ['sejarah indonesia untuk pelajar', 'Sejarah', 'SMP'],
    ['ilmu pengetahuan sosial', 'IPS', 'SMP'],
    ['pendidikan karakter anak', 'Literasi', 'SD'],
    ['motivasi belajar siswa', 'Literasi', 'SMP'],
    ['budaya sunda', 'Muatan Lokal', 'Umum'],
    ['sejarah sumedang', 'Muatan Lokal', 'Umum'],
    ['pengetahuan umum anak', 'Pengetahuan Umum', 'SD'],
    ['ensiklopedia sains populer', 'Pengetahuan Umum', 'SMP'],
];

$bulkLog = [];
$bulkImported = 0;
$bulkSkipped = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bulk_run') {
    csrf_check();
    $selectedIndexes = $_POST['queries'] ?? array_keys($presetQueries);

    foreach ($selectedIndexes as $idx) {
        $idx = (int)$idx;
        if (!isset($presetQueries[$idx])) continue;
        [$pq, $catName, $grade] = $presetQueries[$idx];
        $categoryId = $catMap[$catName] ?? null;

        $found = search_google_books($pq, 6);
        if (!$found) {
            $bulkLog[] = "⚠️ \"$pq\" — tidak ada hasil atau gagal terhubung ke Google Books API.";
            continue;
        }

        foreach ($found as $r) {
            $checkStmt->execute([':ext' => $r['external_id']]);
            if ($checkStmt->fetch()) { $bulkSkipped++; continue; }
            $insertStmt->execute([
                ':t' => $r['title'], ':a' => $r['author'], ':pub' => $r['publisher'],
                ':y' => $r['year'], ':isbn' => $r['isbn'], ':d' => $r['description'],
                ':g' => $grade, ':c' => $categoryId, ':cov' => $r['cover'],
                ':pc' => $r['page_count'], ':ext' => $r['external_id'],
            ]);
            $bulkImported++;
        }
        $bulkLog[] = "✅ \"$pq\" → kategori $catName ($grade): " . count($found) . " buku diproses.";
    }
}
?>

<ul class="nav nav-tabs mb-4">
  <li class="nav-item"><a class="nav-link <?= $activeTab === 'manual' ? 'active' : '' ?>" data-bs-toggle="tab" href="#tab-manual"><i class="bi bi-search"></i> Cari Manual</a></li>
  <li class="nav-item"><a class="nav-link <?= $activeTab === 'bulk' ? 'active' : '' ?>" data-bs-toggle="tab" href="#tab-bulk"><i class="bi bi-collection"></i> Topik Siap Pakai (Bulk)</a></li>
</ul>

<div class="alert alert-light border small mb-4">
  <i class="bi bi-info-circle"></i> Google Books cuma nyediain metadata (judul, penulis, sinopsis, cover) — <strong>bukan file PDF</strong>.
  Setelah diimpor, buka <a href="<?= BASE_URL ?>/admin/books.php?missing_file=1">Buku Belum Lengkap</a> untuk unggah PDF-nya satu per satu, dari sumber yang legal.
</div>

<div class="tab-content">

  <div class="tab-pane fade <?= $activeTab === 'manual' ? 'show active' : '' ?>" id="tab-manual">
    <div class="card p-3 mb-4">
      <form method="get" class="d-flex gap-2">
        <input type="hidden" name="tab" value="manual">
        <input type="text" name="q" class="form-control" placeholder="Cari judul buku, contoh: Laskar Pelangi" value="<?= htmlspecialchars($query) ?>">
        <button class="btn btn-success"><i class="bi bi-search"></i> Cari</button>
      </form>
    </div>

    <?php if ($singleMsg): ?>
      <div class="alert alert-success">
        <?= $singleMsg ?>
        <?php if ($singleImportedId): ?>
          <div class="mt-2"><a href="<?= BASE_URL ?>/admin/book_form.php?id=<?= $singleImportedId ?>" class="btn btn-forest btn-sm"><i class="bi bi-file-earmark-arrow-up"></i> Lengkapi File PDF Sekarang</a></div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($query && !$results): ?>
      <div class="alert alert-warning">Tidak ada hasil, atau koneksi ke Google Books API gagal (periksa akses internet server).</div>
    <?php endif; ?>

    <div class="row g-3">
      <?php foreach ($results as $r): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card p-3 h-100">
            <div class="d-flex gap-3">
              <img src="<?= htmlspecialchars($r['cover'] ?: 'https://via.placeholder.com/80x120?text=No+Cover') ?>"
                   style="width:70px;height:100px;object-fit:cover;border-radius:4px;">
              <div class="flex-grow-1">
                <h6 class="mb-1"><?= htmlspecialchars($r['title']) ?></h6>
                <small class="text-muted d-block mb-1"><?= htmlspecialchars($r['author'] ?: '-') ?></small>
                <small class="text-muted"><?= htmlspecialchars($r['year'] ?: '-') ?></small>
              </div>
            </div>
            <p class="small text-muted mt-2" style="max-height:60px;overflow:hidden;">
              <?= htmlspecialchars(mb_strimwidth(strip_tags($r['description']), 0, 140, '...')) ?>
            </p>

            <?php if ($r['already_imported']): ?>
              <div class="pill pill-forest mt-2 d-inline-flex"><i class="bi bi-check-circle"></i> Sudah ada di katalog</div>
            <?php else: ?>
              <form method="post" class="mt-2">
                <input type="hidden" name="action" value="import_single">
                <input type="hidden" name="tab" value="manual">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="title" value="<?= htmlspecialchars($r['title']) ?>">
                <input type="hidden" name="author" value="<?= htmlspecialchars($r['author']) ?>">
                <input type="hidden" name="publisher" value="<?= htmlspecialchars($r['publisher']) ?>">
                <input type="hidden" name="year" value="<?= htmlspecialchars((string)($r['year'] ?? '')) ?>">
                <input type="hidden" name="isbn" value="<?= htmlspecialchars($r['isbn']) ?>">
                <input type="hidden" name="description" value="<?= htmlspecialchars($r['description']) ?>">
                <input type="hidden" name="cover" value="<?= htmlspecialchars($r['cover']) ?>">
                <input type="hidden" name="page_count" value="<?= (int)$r['page_count'] ?>">
                <input type="hidden" name="external_id" value="<?= htmlspecialchars($r['external_id']) ?>">

                <div class="d-flex gap-2 mb-2">
                  <select name="grade_level" class="form-select form-select-sm">
                    <?php foreach (['SD','SMP','SMA/SMK','Umum'] as $g): ?>
                      <option value="<?= $g ?>"><?= $g ?></option>
                    <?php endforeach; ?>
                  </select>
                  <select name="category_id" class="form-select form-select-sm">
                    <option value="">Kategori...</option>
                    <?php foreach ($categories as $cat): ?>
                      <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <button class="btn btn-sm btn-success w-100"><i class="bi bi-download"></i> Import Metadata</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="tab-pane fade <?= $activeTab === 'bulk' ? 'show active' : '' ?>" id="tab-bulk">
    <div class="card p-3 mb-4">
      <p class="text-muted small mb-3">
        Isi katalog dengan cepat: pilih topik pencarian di bawah, sistem akan mengambil beberapa buku
        per topik sekaligus (metadata saja — judul, penulis, sinopsis, cover). Buku yang sudah pernah
        diimpor otomatis dilewati (tidak dobel).
      </p>

      <?php if ($bulkLog): ?>
        <div class="alert alert-success">
          <strong><?= $bulkImported ?> buku baru diimpor</strong><?= $bulkSkipped ? ", $bulkSkipped dilewati karena duplikat" : '' ?>.
          <?php if ($bulkImported > 0): ?>
            <div class="mt-1">Semuanya masih metadata saja — masih butuh file PDF sebelum bisa dibaca siswa.</div>
          <?php endif; ?>
          <ul class="mb-0 mt-2 small">
            <?php foreach ($bulkLog as $line): ?><li><?= htmlspecialchars($line) ?></li><?php endforeach; ?>
          </ul>
        </div>
        <div class="d-flex gap-2 mb-3">
          <?php if ($bulkImported > 0): ?>
            <a href="<?= BASE_URL ?>/admin/books.php?missing_file=1" class="btn btn-forest btn-sm">
              <i class="bi bi-file-earmark-arrow-up"></i> Lengkapi PDF (<?= $bulkImported ?> buku)
            </a>
          <?php endif; ?>
          <a href="<?= BASE_URL ?>/admin/books.php" class="btn btn-outline-forest btn-sm">Lihat Semua Buku</a>
        </div>
      <?php endif; ?>

      <form method="post">
        <input type="hidden" name="action" value="bulk_run">
        <input type="hidden" name="tab" value="bulk">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="mb-3">
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllQueries(true)">Pilih Semua</button>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllQueries(false)">Kosongkan</button>
        </div>

        <div class="row g-2 mb-3">
          <?php foreach ($presetQueries as $i => [$pq, $cat, $grade]): ?>
            <div class="col-md-6">
              <div class="form-check border rounded p-2">
                <input class="form-check-input query-check" type="checkbox" name="queries[]" value="<?= $i ?>" id="q<?= $i ?>" checked>
                <label class="form-check-label" for="q<?= $i ?>">
                  <strong><?= htmlspecialchars($pq) ?></strong>
                  <span class="badge bg-success-subtle text-success-emphasis ms-1"><?= htmlspecialchars($cat) ?></span>
                  <span class="badge bg-secondary ms-1"><?= htmlspecialchars($grade) ?></span>
                </label>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <button class="btn btn-success"><i class="bi bi-play-fill"></i> Jalankan Import</button>
      </form>
    </div>

    <div class="alert alert-info small">
      <i class="bi bi-info-circle"></i> Ingin topik pencarian lain? Edit langsung array <code>$presetQueries</code>
      di file <code>admin/import_google_books.php</code> — tambahkan baris baru <code>['kata kunci pencarian', 'Nama Kategori', 'Jenjang']</code>.
    </div>
  </div>

</div>

<script>
function toggleAllQueries(state) {
  document.querySelectorAll('.query-check').forEach(el => el.checked = state);
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
