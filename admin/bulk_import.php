<?php
$page_title = 'Bulk Import Buku';
require_once __DIR__ . '/../includes/admin_header.php';

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

function search_google_books(string $query, int $maxResults = 6): array {
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

$log = [];
$imported = 0;
$skipped = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bulk_run') {
    csrf_check();

    $catStmt = $pdo->query("SELECT id, name FROM categories");
    $catMap = [];
    foreach ($catStmt->fetchAll() as $c) $catMap[$c['name']] = $c['id'];

    $checkStmt = $pdo->prepare("SELECT id FROM books WHERE external_id = :ext");
    $insertStmt = $pdo->prepare("INSERT INTO books (title, author, publisher, year_published, isbn,
        description, grade_level, category_id, cover_image, page_count, source, external_id)
        VALUES (:t, :a, :pub, :y, :isbn, :d, :g, :c, :cov, :pc, 'google_books', :ext)");

    $selectedIndexes = $_POST['queries'] ?? array_keys($presetQueries);

    foreach ($selectedIndexes as $idx) {
        $idx = (int)$idx;
        if (!isset($presetQueries[$idx])) continue;
        [$query, $catName, $grade] = $presetQueries[$idx];
        $categoryId = $catMap[$catName] ?? null;

        $results = search_google_books($query, 6);
        if (!$results) {
            $log[] = "⚠️ \"$query\" — tidak ada hasil atau gagal terhubung ke Google Books API.";
            continue;
        }

        foreach ($results as $r) {
            $checkStmt->execute([':ext' => $r['external_id']]);
            if ($checkStmt->fetch()) {
                $skipped++;
                continue;
            }
            $insertStmt->execute([
                ':t' => $r['title'], ':a' => $r['author'], ':pub' => $r['publisher'],
                ':y' => $r['year'], ':isbn' => $r['isbn'], ':d' => $r['description'],
                ':g' => $grade, ':c' => $categoryId, ':cov' => $r['cover'],
                ':pc' => $r['page_count'], ':ext' => $r['external_id'],
            ]);
            $imported++;
        }
        $log[] = "✅ \"$query\" → kategori $catName ($grade): " . count($results) . " buku diproses.";
    }
}
?>

<div class="card p-3 mb-4">
  <h6><i class="bi bi-cloud-arrow-down"></i> Bulk Import dari Google Books</h6>
  <p class="text-muted small mb-3">
    Isi katalog dengan cepat: pilih topik pencarian di bawah, sistem akan mengambil beberapa buku
    per topik sekaligus (metadata saja — judul, penulis, sinopsis, cover). File PDF tetap perlu
    diunggah manual per buku lewat menu <em>Semua Buku &rarr; Edit</em>, dari sumber yang legal.
    Buku yang sudah pernah diimpor otomatis dilewati (tidak dobel).
  </p>

  <?php if ($log): ?>
    <div class="alert alert-success">
      <strong><?= $imported ?> buku baru diimpor</strong><?= $skipped ? ", $skipped dilewati karena duplikat" : '' ?>.
      <ul class="mb-0 mt-2 small">
        <?php foreach ($log as $line): ?><li><?= htmlspecialchars($line) ?></li><?php endforeach; ?>
      </ul>
    </div>
    <a href="<?= BASE_URL ?>/admin/books.php" class="btn btn-success btn-sm mb-3">Lihat Semua Buku</a>
  <?php endif; ?>

  <form method="post">
    <input type="hidden" name="action" value="bulk_run">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

    <div class="mb-3">
      <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAll(true)">Pilih Semua</button>
      <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAll(false)">Kosongkan</button>
    </div>

    <div class="row g-2 mb-3">
      <?php foreach ($presetQueries as $i => [$q, $cat, $grade]): ?>
        <div class="col-md-6">
          <div class="form-check border rounded p-2">
            <input class="form-check-input query-check" type="checkbox" name="queries[]" value="<?= $i ?>" id="q<?= $i ?>" checked>
            <label class="form-check-label" for="q<?= $i ?>">
              <strong><?= htmlspecialchars($q) ?></strong>
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
  di file <code>admin/bulk_import.php</code> — tambahkan baris baru <code>['kata kunci pencarian', 'Nama Kategori', 'Jenjang']</code>.
</div>

<script>
function toggleAll(state) {
  document.querySelectorAll('.query-check').forEach(el => el.checked = state);
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
