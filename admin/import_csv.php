<?php
$page_title = 'Import CSV';
require_once __DIR__ . '/../includes/admin_header.php';

$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$catMap = [];
foreach ($categories as $c) $catMap[strtolower($c['name'])] = $c['id'];

$log = [];
$imported = 0;
$skipped = 0;
$failed = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'import_csv') {
    csrf_check();

    if (empty($_FILES['csv_file']['tmp_name'])) {
        $log[] = "⚠️ Tidak ada file yang diunggah.";
    } else {
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$handle) {
            $log[] = "⚠️ Gagal membuka file CSV.";
        } else {
            $header = fgetcsv($handle);
            // Normalisasi nama kolom: lowercase, trim
            $header = array_map(fn($h) => strtolower(trim($h)), $header);
            $expected = ['title','author','publisher','year','isbn','description','grade_level','category','cover_url','pdf_url','downloadable'];

            // Fix: :a dipakai 2x di query asli (author = :a OR :a = '') memicu
            // "SQLSTATE[HY093]: Invalid parameter number" di MySQL native prepared
            // statement (PDO::ATTR_EMULATE_PREPARES => false di config/db.php).
            // Solusi: beri nama placeholder terpisah utk tiap kemunculan, walau
            // nilainya sama.
            $checkStmt = $pdo->prepare("SELECT id FROM books WHERE title = :t AND (author = :a1 OR :a2 = '')");
            $insertStmt = $pdo->prepare("INSERT INTO books (title, author, publisher, year_published, isbn,
                description, grade_level, category_id, cover_image, file_path, is_downloadable, source)
                VALUES (:t, :a, :pub, :y, :isbn, :d, :g, :c, :cov, :f, :dl, 'manual')");

            $rowNum = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                if (count(array_filter($row)) === 0) continue; // baris kosong

                $data = array_combine($header, array_pad($row, count($header), ''));

                $title = trim($data['title'] ?? '');
                if (!$title) {
                    $log[] = "⚠️ Baris $rowNum dilewati: kolom title kosong.";
                    $failed++;
                    continue;
                }

                $author = trim($data['author'] ?? '');
                $checkStmt->execute([':t' => $title, ':a1' => $author, ':a2' => $author]);
                if ($checkStmt->fetch()) {
                    $log[] = "⏭️ Baris $rowNum \"$title\" dilewati (judul+penulis sudah ada).";
                    $skipped++;
                    continue;
                }

                $categoryName = strtolower(trim($data['category'] ?? ''));
                $categoryId = $catMap[$categoryName] ?? null;

                $grade = trim($data['grade_level'] ?? 'Umum');
                if (!in_array($grade, ['SD','SMP','SMA/SMK','Umum'])) $grade = 'Umum';

                $pdfUrl = trim($data['pdf_url'] ?? '');
                $coverUrl = trim($data['cover_url'] ?? '');
                $downloadable = in_array(strtolower(trim($data['downloadable'] ?? '')), ['1','ya','yes','true']) ? 1 : 0;

                $insertStmt->execute([
                    ':t' => $title,
                    ':a' => $author,
                    ':pub' => trim($data['publisher'] ?? ''),
                    ':y' => trim($data['year'] ?? '') ?: null,
                    ':isbn' => trim($data['isbn'] ?? ''),
                    ':d' => trim($data['description'] ?? ''),
                    ':g' => $grade,
                    ':c' => $categoryId,
                    ':cov' => $coverUrl ?: null,
                    ':f' => $pdfUrl ?: null,
                    ':dl' => $downloadable,
                ]);
                $imported++;
                $log[] = "✅ Baris $rowNum \"$title\" berhasil ditambahkan" . (!$categoryId && $categoryName ? " (kategori \"$categoryName\" tidak ditemukan, dikosongkan)" : "") . ".";
            }
            fclose($handle);
        }
    }
}
?>

<div class="card p-3 mb-4">
  <h6><i class="bi bi-filetype-csv"></i> Import Banyak Buku dari CSV</h6>
  <p class="text-muted small">
    Cara paling efisien untuk memasukkan banyak buku sekaligus — termasuk buku paket dari SIBI yang
    linknya sudah kamu kumpulkan. Siapkan daftar buku di Excel/Google Sheets, ekspor sebagai CSV, lalu
    unggah di sini. Baris dengan judul + penulis yang sama persis dengan yang sudah ada akan otomatis dilewati.
  </p>

  <div class="alert alert-light border small">
    <strong>Format kolom CSV (baris pertama = header, urutan bebas):</strong>
    <code>title, author, publisher, year, isbn, description, grade_level, category, cover_url, pdf_url, downloadable</code>
    <ul class="mb-0 mt-2">
      <li><code>title</code> — wajib diisi.</li>
      <li><code>grade_level</code> — isi salah satu: <code>SD</code>, <code>SMP</code>, <code>SMA/SMK</code>, atau <code>Umum</code>.</li>
      <li><code>category</code> — isi nama kategori persis seperti di menu Kategori (contoh: <code>Matematika</code>). Jika tidak cocok, akan dikosongkan.</li>
      <li><code>pdf_url</code> — tempel link PDF resmi (misal dari SIBI). Boleh dikosongkan dan diisi belakangan lewat Edit Buku.</li>
      <li><code>cover_url</code> — link gambar cover (opsional).</li>
      <li><code>downloadable</code> — isi <code>1</code> atau <code>ya</code> jika siswa boleh unduh, kosongkan jika tidak.</li>
    </ul>
  </div>

  <a href="<?= BASE_URL ?>/admin/csv_template.php" class="btn btn-sm btn-outline-secondary mb-3">
    <i class="bi bi-download"></i> Unduh Contoh Template CSV
  </a>

  <?php if ($log): ?>
    <div class="alert alert-success">
      <strong><?= $imported ?> buku berhasil ditambahkan</strong>,
      <?= $skipped ?> dilewati (duplikat), <?= $failed ?> gagal (data tidak lengkap).
      <div class="mt-2 small" style="max-height:250px;overflow-y:auto;">
        <?php foreach ($log as $line): ?><div><?= htmlspecialchars($line) ?></div><?php endforeach; ?>
      </div>
    </div>
    <a href="<?= BASE_URL ?>/admin/books.php" class="btn btn-success btn-sm mb-3">Lihat Semua Buku</a>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="import_csv">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="mb-3">
      <input type="file" name="csv_file" class="form-control" accept=".csv" required>
    </div>
    <button class="btn btn-success"><i class="bi bi-upload"></i> Import CSV</button>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
