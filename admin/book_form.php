<?php
$page_title = 'Form Buku';
require_once __DIR__ . '/../includes/admin_header.php';

$id = (int)($_GET['id'] ?? 0);
$book = [
    'id' => 0, 'title' => '', 'author' => '', 'publisher' => '', 'year_published' => '',
    'isbn' => '', 'description' => '', 'grade_level' => 'Umum', 'category_id' => '',
    'cover_image' => '', 'file_path' => '', 'is_downloadable' => 0, 'source' => 'manual',
];
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $found = $stmt->fetch();
    if ($found) $book = $found;
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $title       = trim($_POST['title'] ?? '');
    $author      = trim($_POST['author'] ?? '');
    $publisher   = trim($_POST['publisher'] ?? '');
    $year        = $_POST['year_published'] ?: null;
    $isbn        = trim($_POST['isbn'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $grade       = $_POST['grade_level'] ?? 'Umum';
    $categoryId  = $_POST['category_id'] ?: null;
    $downloadable = isset($_POST['is_downloadable']) ? 1 : 0;
    $coverImage  = $book['cover_image'];
    $filePath    = $book['file_path'];

    if (!$title) {
        $error = 'Judul wajib diisi.';
    } else {
        // Upload cover (opsional)
        if (!empty($_FILES['cover']['name'])) {
            $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $newName = 'cover_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['cover']['tmp_name'], UPLOAD_COVER_DIR . $newName)) {
                    $coverImage = $newName;
                }
            } else {
                $error = 'Format cover harus jpg/png/webp.';
            }
        }

        // Upload file PDF (opsional) — atau pakai link eksternal (misal dari SIBI)
        $fileUrlInput = trim($_POST['file_url'] ?? '');
        if ($fileUrlInput) {
            if (filter_var($fileUrlInput, FILTER_VALIDATE_URL)) {
                $filePath = $fileUrlInput;
            } else {
                $error = 'Link PDF tidak valid.';
            }
        } elseif (!$error && !empty($_FILES['book_file']['name'])) {
            $ext = strtolower(pathinfo($_FILES['book_file']['name'], PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                $newName = 'book_' . time() . '_' . bin2hex(random_bytes(4)) . '.pdf';
                if (move_uploaded_file($_FILES['book_file']['tmp_name'], UPLOAD_BOOK_DIR . $newName)) {
                    $filePath = $newName;
                }
            } else {
                $error = 'File buku harus berformat PDF.';
            }
        }

        if (!$error) {
            if ($book['id']) {
                $stmt = $pdo->prepare("UPDATE books SET title=:t, author=:a, publisher=:pub, year_published=:y,
                    isbn=:isbn, description=:d, grade_level=:g, category_id=:c, cover_image=:cov,
                    file_path=:f, is_downloadable=:dl WHERE id=:id");
                $stmt->execute([
                    ':t' => $title, ':a' => $author, ':pub' => $publisher, ':y' => $year, ':isbn' => $isbn,
                    ':d' => $description, ':g' => $grade, ':c' => $categoryId, ':cov' => $coverImage,
                    ':f' => $filePath, ':dl' => $downloadable, ':id' => $book['id'],
                ]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO books (title, author, publisher, year_published, isbn,
                    description, grade_level, category_id, cover_image, file_path, is_downloadable, source)
                    VALUES (:t, :a, :pub, :y, :isbn, :d, :g, :c, :cov, :f, :dl, 'manual')");
                $stmt->execute([
                    ':t' => $title, ':a' => $author, ':pub' => $publisher, ':y' => $year, ':isbn' => $isbn,
                    ':d' => $description, ':g' => $grade, ':c' => $categoryId, ':cov' => $coverImage,
                    ':f' => $filePath, ':dl' => $downloadable,
                ]);
            }
            header('Location: ' . BASE_URL . '/admin/books.php?saved=1');
            exit;
        }
    }
}
?>

<div class="card p-4" style="max-width:700px;">
  <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

    <div class="mb-3">
      <label class="form-label">Judul Buku *</label>
      <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($book['title']) ?>" required>
    </div>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Penulis</label>
        <input type="text" name="author" class="form-control" value="<?= htmlspecialchars($book['author'] ?? '') ?>">
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Penerbit</label>
        <input type="text" name="publisher" class="form-control" value="<?= htmlspecialchars($book['publisher'] ?? '') ?>">
      </div>
    </div>
    <div class="row">
      <div class="col-md-4 mb-3">
        <label class="form-label">Tahun Terbit</label>
        <input type="number" name="year_published" class="form-control" value="<?= htmlspecialchars((string)($book['year_published'] ?? '')) ?>">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">ISBN</label>
        <input type="text" name="isbn" class="form-control" value="<?= htmlspecialchars($book['isbn'] ?? '') ?>">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Jenjang</label>
        <select name="grade_level" class="form-select">
          <?php foreach (['SD','SMP','SMA/SMK','Umum'] as $g): ?>
            <option value="<?= $g ?>" <?= $book['grade_level'] === $g ? 'selected' : '' ?>><?= $g ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Kategori</label>
      <select name="category_id" class="form-select">
        <option value="">- Pilih Kategori -</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= (string)$book['category_id'] === (string)$cat['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Sinopsis</label>
      <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($book['description'] ?? '') ?></textarea>
    </div>

    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Cover Buku (jpg/png/webp)</label>
        <input type="file" name="cover" class="form-control" accept="image/*">
        <?php if ($book['cover_image']): ?>
          <small class="text-muted">Sudah ada: <?= htmlspecialchars($book['cover_image']) ?></small>
        <?php endif; ?>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">File Buku (PDF)</label>
        <input type="file" name="book_file" class="form-control" accept="application/pdf">
        <?php if ($book['file_path']): ?>
          <small class="text-muted">Sudah ada: <?= htmlspecialchars($book['file_path']) ?></small>
        <?php endif; ?>
        <div class="text-center small text-muted my-1">atau</div>
        <input type="url" name="file_url" class="form-control" placeholder="Tempel link PDF resmi (contoh: dari SIBI/buku.kemendikdasmen.go.id)"
               value="<?= filter_var($book['file_path'] ?? '', FILTER_VALIDATE_URL) ? htmlspecialchars($book['file_path']) : '' ?>">
        <small class="text-muted">Jika diisi, link ini dipakai langsung (tanpa upload) — server buku tetap di sumber resminya.</small>
      </div>
    </div>

    <div class="form-check mb-4">
      <input type="checkbox" name="is_downloadable" class="form-check-input" id="dl" <?= $book['is_downloadable'] ? 'checked' : '' ?>>
      <label class="form-check-label" for="dl">Izinkan siswa mengunduh file PDF</label>
    </div>

    <button class="btn btn-success"><i class="bi bi-save"></i> Simpan Buku</button>
    <a href="<?= BASE_URL ?>/admin/books.php" class="btn btn-outline-secondary">Batal</a>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
