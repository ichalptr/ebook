<?php
$page_title = 'Import Resmi Kemendikdasmen';
require_once __DIR__ . '/../includes/admin_header.php';

const SIPLAH_BASE = 'https://siplah.kemendikdasmen.go.id/sds/lookup-tables/msts/books/';

function fetch_json(string $path): array {
    $ch = curl_init(SIPLAH_BASE . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    if (!$res) return [];
    $data = json_decode($res, true);
    return is_array($data) ? $data : [];
}

function cache_get(string $key, callable $fetcher): array {
    $file = sys_get_temp_dir() . '/siplah_' . $key . '.json';
    if (file_exists($file) && (time() - filemtime($file) < 86400)) {
        return json_decode(file_get_contents($file), true) ?: [];
    }
    $data = $fetcher();
    if ($data) @file_put_contents($file, json_encode($data));
    return $data;
}

$schoolLevels = cache_get('school_levels', fn() => fetch_json('text_book_school_levels.json'));
$subjects     = cache_get('subjects', fn() => fetch_json('text_book_subjects.json'));
$publishers   = cache_get('publishers', fn() => fetch_json('publishers.json'));

$schoolLevelMap = array_column($schoolLevels, 'name', 'id');
$subjectMap     = array_column($subjects, 'name', 'id');
$publisherMap   = array_column($publishers, 'name', 'id');

function map_grade(string $levelName): string {
    if (str_contains($levelName, 'SD')) return 'SD';
    if (str_contains($levelName, 'SMP')) return 'SMP';
    if (str_contains($levelName, 'SMA') || str_contains($levelName, 'SMK')) return 'SMA/SMK';
    return 'Umum';
}

$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$catMap = [];
foreach ($categories as $c) $catMap[strtolower($c['name'])] = $c['id'];

$selectedLevel = $_GET['level'] ?? '';
$selectedSubjectKeyword = trim($_GET['subject'] ?? '');
$searchTitle = trim($_GET['q'] ?? '');

$results = [];
$importMsg = '';
$imported = 0;
$skipped = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'import_official') {
    csrf_check();

    $books = cache_get('text_books', fn() => fetch_json('text_books.json'));
    $checkStmt = $pdo->prepare("SELECT id FROM books WHERE external_id = :ext");
    $insertStmt = $pdo->prepare("INSERT INTO books (title, author, publisher, year_published, isbn,
        description, grade_level, category_id, cover_image, source, external_id)
        VALUES (:t, :a, :pub, :y, :isbn, :d, :g, :c, :cov, 'manual', :ext)");

    $ids = $_POST['book_ids'] ?? [];
    foreach ($ids as $bookId) {
        $book = null;
        foreach ($books as $b) { if ($b['id'] === $bookId) { $book = $b; break; } }
        if (!$book) continue;

        $checkStmt->execute([':ext' => 'siplah_' . $book['id']]);
        if ($checkStmt->fetch()) { $skipped++; continue; }

        $levelName = $schoolLevelMap[$book['schoolLevelId'] ?? ''] ?? '';
        $subjectName = $subjectMap[$book['subjectId'] ?? ''] ?? '';
        $publisherName = $publisherMap[$book['publisherId'] ?? ''] ?? '';
        $cover = $book['physicalDescription']['coverImage'] ?? '';
        if (str_contains($cover, 'no-image-available')) $cover = '';

        $categoryId = $catMap[strtolower($subjectName)] ?? null;

        $insertStmt->execute([
            ':t'    => $book['title'],
            ':a'    => $book['author'] ?? '',
            ':pub'  => $publisherName,
            ':y'    => $book['publicationYear'] ?? null,
            ':isbn' => $book['isbn'] ?? '',
            ':d'    => trim(($book['description'] ?? '') . ($subjectName ? " ($subjectName)" : '')),
            ':g'    => map_grade($levelName),
            ':c'    => $categoryId,
            ':cov'  => $cover ?: null,
            ':ext'  => 'siplah_' . $book['id'],
        ]);
        $imported++;
    }
    $importMsg = "$imported buku resmi berhasil diimpor" . ($skipped ? ", $skipped dilewati (duplikat)" : '') . ". File PDF tetap perlu ditambahkan manual dari SIBI lewat menu Edit Buku.";
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($selectedLevel || $selectedSubjectKeyword || $searchTitle)) {
    $books = cache_get('text_books', fn() => fetch_json('text_books.json'));
    foreach ($books as $b) {
        if ($selectedLevel && ($b['schoolLevelId'] ?? '') !== $selectedLevel) continue;
        if ($selectedSubjectKeyword) {
            $subjName = $subjectMap[$b['subjectId'] ?? ''] ?? '';
            if (stripos($subjName, $selectedSubjectKeyword) === false) continue;
        }
        if ($searchTitle && stripos($b['title'], $searchTitle) === false) continue;
        $results[] = $b;
        if (count($results) >= 60) break;
    }
}
?>

<div class="card p-3 mb-4">
  <h6><i class="bi bi-bank"></i> Import Resmi — Data Buku Teks Pelajaran Kemendikdasmen</h6>
  <p class="text-muted small mb-3">
    Sumber data terbuka resmi dari Pusat Perbukuan (via Data Acuan SIPLah) — mencakup ribuan Buku Teks
    Pelajaran (BTP) kurikulum nasional se-Indonesia: judul, penulis, penerbit, mata pelajaran, jenjang,
    kelas, ISBN, dan cover. <strong>Hanya metadata</strong> — file PDF tetap perlu ditambahkan manual
    dari SIBI (<a href="https://buku.kemendikdasmen.go.id/katalog" target="_blank">buku.kemendikdasmen.go.id</a>)
    lewat menu Edit Buku, karena data ini disiapkan untuk pengadaan, bukan distribusi file.
  </p>

  <?php if ($importMsg): ?>
    <div class="alert alert-success"><?= htmlspecialchars($importMsg) ?></div>
    <a href="<?= BASE_URL ?>/admin/books.php" class="btn btn-success btn-sm mb-3">Lihat Semua Buku</a>
  <?php endif; ?>

  <form method="get" class="row g-2 mb-3">
    <div class="col-md-4">
      <select name="level" class="form-select">
        <option value="">Semua Jenjang</option>
        <?php foreach ($schoolLevels as $lvl): ?>
          <option value="<?= htmlspecialchars($lvl['id']) ?>" <?= $selectedLevel === $lvl['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($lvl['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <input type="text" name="subject" class="form-control" placeholder="Mata pelajaran, contoh: Matematika" value="<?= htmlspecialchars($selectedSubjectKeyword) ?>">
    </div>
    <div class="col-md-3">
      <input type="text" name="q" class="form-control" placeholder="Cari judul..." value="<?= htmlspecialchars($searchTitle) ?>">
    </div>
    <div class="col-md-1">
      <button class="btn btn-success w-100"><i class="bi bi-search"></i></button>
    </div>
  </form>

  <?php if ($results): ?>
  <form method="post">
    <input type="hidden" name="action" value="import_official">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

    <div class="mb-2 d-flex justify-content-between align-items-center">
      <span class="small text-muted"><?= count($results) ?> hasil ditampilkan (maks. 60 per pencarian)</span>
      <div>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllBooks(true)">Pilih Semua</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllBooks(false)">Kosongkan</button>
      </div>
    </div>

    <div class="table-responsive mb-3" style="max-height:500px;overflow-y:auto;">
      <table class="table table-sm table-hover align-middle">
        <thead class="table-light sticky-top"><tr><th></th><th>Judul</th><th>Penulis</th><th>Mapel</th><th>Jenjang</th><th>Tahun</th></tr></thead>
        <tbody>
          <?php foreach ($results as $b):
            $levelName = $schoolLevelMap[$b['schoolLevelId'] ?? ''] ?? '-';
            $subjectName = $subjectMap[$b['subjectId'] ?? ''] ?? '-';
          ?>
          <tr>
            <td><input type="checkbox" class="book-check" name="book_ids[]" value="<?= htmlspecialchars($b['id']) ?>" checked></td>
            <td><?= htmlspecialchars($b['title']) ?></td>
            <td class="small text-muted"><?= htmlspecialchars($b['author'] ?? '-') ?></td>
            <td><span class="badge bg-info-subtle text-info-emphasis"><?= htmlspecialchars($subjectName) ?></span></td>
            <td><span class="badge bg-secondary"><?= htmlspecialchars($levelName) ?></span></td>
            <td class="small"><?= htmlspecialchars($b['publicationYear'] ?? '-') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <button class="btn btn-success"><i class="bi bi-cloud-arrow-down"></i> Import Buku Terpilih</button>
  </form>
  <?php elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && ($selectedLevel || $selectedSubjectKeyword || $searchTitle)): ?>
    <div class="alert alert-warning">Tidak ada hasil, atau gagal mengambil data dari server Kemendikdasmen (periksa koneksi internet server).</div>
  <?php else: ?>
    <div class="alert alert-light border">Gunakan filter di atas untuk mencari buku (contoh: pilih jenjang <strong>SD</strong>, mata pelajaran <strong>Matematika</strong>).</div>
  <?php endif; ?>
</div>

<script>
function toggleAllBooks(state) {
  document.querySelectorAll('.book-check').forEach(el => el.checked = state);
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
