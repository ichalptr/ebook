<?php
$page_title = 'Quick Add dari Bookmarklet';
require_once __DIR__ . '/../includes/admin_header.php';

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$error = '';
$success = false;

$prefill = [
    'title'       => trim($_GET['title'] ?? $_POST['title'] ?? ''),
    'cover'       => trim($_GET['cover'] ?? $_POST['cover'] ?? ''),
    'pdf_url'     => trim($_GET['pdf_url'] ?? $_POST['pdf_url'] ?? ''),
    'description' => trim($_GET['description'] ?? $_POST['description'] ?? ''),
    'source_url'  => trim($_GET['source_url'] ?? $_POST['source_url'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    csrf_check();
    $title = trim($_POST['title'] ?? '');

    if (!$title) {
        $error = 'Judul wajib diisi.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO books (title, author, description, grade_level, category_id,
            cover_image, file_path, source)
            VALUES (:t, :a, :d, :g, :c, :cov, :f, 'manual')");
        $stmt->execute([
            ':t'   => $title,
            ':a'   => trim($_POST['author'] ?? ''),
            ':d'   => trim($_POST['description'] ?? ''),
            ':g'   => $_POST['grade_level'] ?? 'Umum',
            ':c'   => $_POST['category_id'] ?: null,
            ':cov' => trim($_POST['cover'] ?? '') ?: null,
            ':f'   => trim($_POST['pdf_url'] ?? '') ?: null,
        ]);
        $success = true;
        $newId = $pdo->lastInsertId();
    }
}
?>

<div class="card p-4" style="max-width:640px;">
  <h6><i class="bi bi-bookmark-star"></i> Quick Add — Data dari Bookmarklet</h6>

  <?php if ($success): ?>
    <div class="alert alert-success">
      Buku <strong><?= htmlspecialchars($_POST['title']) ?></strong> berhasil ditambahkan.
    </div>
    <div class="d-flex gap-2">
      <a href="<?= BASE_URL ?>/admin/book_form.php?id=<?= $newId ?>" class="btn btn-outline-success btn-sm">Lengkapi Detail Buku Ini</a>
      <a href="<?= BASE_URL ?>/admin/books.php" class="btn btn-success btn-sm">Lihat Semua Buku</a>
      <button onclick="window.close()" class="btn btn-outline-secondary btn-sm">Tutup Tab</button>
    </div>
  <?php else: ?>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <?php if (!$prefill['pdf_url']): ?>
      <div class="alert alert-warning small">
        Link PDF tidak otomatis terdeteksi dari halaman sumber. Salin manual link unduh/PDF dari halaman
        aslinya dan tempel di kolom "Link PDF" di bawah.
      </div>
    <?php endif; ?>

    <?php if ($prefill['source_url']): ?>
      <p class="small text-muted">Diambil dari: <a href="<?= htmlspecialchars($prefill['source_url']) ?>" target="_blank"><?= htmlspecialchars($prefill['source_url']) ?></a></p>
    <?php endif; ?>

    <?php if ($prefill['cover']): ?>
      <img src="<?= htmlspecialchars($prefill['cover']) ?>" style="width:80px;border-radius:6px;" class="mb-3">
    <?php endif; ?>

    <form method="post">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <input type="hidden" name="cover" value="<?= htmlspecialchars($prefill['cover']) ?>">

      <div class="mb-3">
        <label class="form-label">Judul *</label>
        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($prefill['title']) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Penulis</label>
        <input type="text" name="author" class="form-control">
      </div>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Jenjang</label>
          <select name="grade_level" class="form-select">
            <?php foreach (['SD','SMP','SMA/SMK','Umum'] as $g): ?>
              <option value="<?= $g ?>"><?= $g ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Kategori</label>
          <select name="category_id" class="form-select">
            <option value="">- Pilih -</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Sinopsis</label>
        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($prefill['description']) ?></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Link PDF</label>
        <input type="url" name="pdf_url" class="form-control" value="<?= htmlspecialchars($prefill['pdf_url']) ?>" placeholder="https://...">
      </div>

      <button class="btn btn-success"><i class="bi bi-save"></i> Simpan Buku</button>
    </form>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
