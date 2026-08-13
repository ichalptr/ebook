<?php
$page_title = 'Import Buku';
require_once __DIR__ . '/../includes/admin_header.php';

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$results = [];
$query = trim($_GET['q'] ?? '');
$importMsg = '';

function search_google_books(string $query): array {
    $url = 'https://www.googleapis.com/books/v1/volumes?q=' . urlencode($query) . '&maxResults=12&langRestrict=id';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || !$response) return [];

    $data = json_decode($response, true);
    $items = $data['items'] ?? [];
    $books = [];
    foreach ($items as $item) {
        $info = $item['volumeInfo'] ?? [];
        $books[] = [
            'external_id'  => $item['id'],
            'title'        => $info['title'] ?? 'Tanpa Judul',
            'author'       => implode(', ', $info['authors'] ?? []),
            'publisher'    => $info['publisher'] ?? '',
            'year'         => isset($info['publishedDate']) ? substr($info['publishedDate'], 0, 4) : '',
            'description'  => $info['description'] ?? '',
            'page_count'   => $info['pageCount'] ?? 0,
            'cover'        => $info['imageLinks']['thumbnail'] ?? ($info['imageLinks']['smallThumbnail'] ?? ''),
            'isbn'         => $info['industryIdentifiers'][0]['identifier'] ?? '',
        ];
    }
    return $books;
}

if ($query) {
    $results = search_google_books($query);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'import') {
    csrf_check();
    $categoryId = $_POST['category_id'] ?: null;
    $grade = $_POST['grade_level'] ?? 'Umum';

    $stmt = $pdo->prepare("INSERT INTO books (title, author, publisher, year_published, isbn, description,
        grade_level, category_id, cover_image, page_count, source, external_id)
        VALUES (:t, :a, :pub, :y, :isbn, :d, :g, :c, :cov, :pc, 'google_books', :ext)");

    $stmt->execute([
        ':t'    => $_POST['title'],
        ':a'    => $_POST['author'],
        ':pub'  => $_POST['publisher'],
        ':y'    => $_POST['year'] ?: null,
        ':isbn' => $_POST['isbn'],
        ':d'    => $_POST['description'],
        ':g'    => $grade,
        ':c'    => $categoryId,
        ':cov'  => $_POST['cover'],
        ':pc'   => (int)($_POST['page_count'] ?: 0),
        ':ext'  => $_POST['external_id'],
    ]);
    $importMsg = 'Metadata buku "' . htmlspecialchars($_POST['title']) . '" berhasil diimpor. Silakan lengkapi jenjang/kategori dan unggah file PDF melalui menu Edit.';
}
?>

<div class="card p-3 mb-4">
  <h6><i class="bi bi-cloud-download"></i> Import Metadata Buku dari Google Books</h6>
  <p class="text-muted small mb-3">
    Cari buku, lalu import metadatanya (judul, penulis, sinopsis, cover) secara otomatis ke database lokal.
    Setelah diimpor, kamu tetap perlu mengunggah file PDF-nya sendiri (dari sumber legal/berizin) lewat menu Edit Buku,
    karena Google Books API hanya menyediakan metadata &mdash; bukan file lengkap.
  </p>
  <form method="get" class="d-flex gap-2">
    <input type="text" name="q" class="form-control" placeholder="Cari judul buku, contoh: Laskar Pelangi" value="<?= htmlspecialchars($query) ?>">
    <button class="btn btn-success"><i class="bi bi-search"></i> Cari</button>
  </form>
</div>

<?php if ($importMsg): ?>
  <div class="alert alert-success"><?= $importMsg ?></div>
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
        <form method="post" class="mt-2">
          <input type="hidden" name="action" value="import">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <input type="hidden" name="title" value="<?= htmlspecialchars($r['title']) ?>">
          <input type="hidden" name="author" value="<?= htmlspecialchars($r['author']) ?>">
          <input type="hidden" name="publisher" value="<?= htmlspecialchars($r['publisher']) ?>">
          <input type="hidden" name="year" value="<?= htmlspecialchars($r['year']) ?>">
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
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
