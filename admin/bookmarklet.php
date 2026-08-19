<?php
$page_title = 'Bookmarklet Import';
require_once __DIR__ . '/../includes/admin_header.php';

// Bookmarklet: dijalankan di BROWSER admin sendiri, saat admin sedang membuka
// halaman detail buku (misal di SIBI). Membaca info di halaman yang SEDANG DIBUKA
// (bukan crawling otomatis), lalu membuka tab baru ke quick_add.php dengan data terisi.
$bookmarkletJs = <<<'JS'
(function(){
  function meta(name){
    var el = document.querySelector('meta[property="'+name+'"]') || document.querySelector('meta[name="'+name+'"]');
    return el ? el.getAttribute('content') : '';
  }
  function firstPdfLink(){
    var links = Array.from(document.querySelectorAll('a[href]'));
    var pdf = links.find(function(a){ return /\.pdf($|\?)/i.test(a.href); });
    if (pdf) return pdf.href;
    var dl = links.find(function(a){
      var t = (a.textContent||'').toLowerCase();
      return t.indexOf('unduh') > -1 || t.indexOf('download') > -1;
    });
    return dl ? dl.href : '';
  }
  var title = meta('og:title') || document.title || '';
  var cover = meta('og:image') || '';
  var pdf = firstPdfLink();
  var desc = meta('og:description') || '';
  var params = new URLSearchParams({
    title: title.replace(/\s*-\s*SIBI.*$/i,'').trim(),
    cover: cover,
    pdf_url: pdf,
    description: desc,
    source_url: location.href
  });
  window.open('BASE_URL_PLACEHOLDER/admin/quick_add.php?' + params.toString(), '_blank');
})();
JS;
$bookmarkletJs = str_replace('BASE_URL_PLACEHOLDER', BASE_URL, $bookmarkletJs);
$bookmarkletHref = 'javascript:' . rawurlencode(preg_replace('/\s+/', ' ', $bookmarkletJs));
?>

<div class="card p-4" style="max-width:720px;">
  <h6><i class="bi bi-bookmark-star"></i> Bookmarklet "Clip ke E-Library"</h6>
  <p class="text-muted small">
    Cara kerjanya seperti Mendeley Web Importer: tombol ini jalan di browser kamu sendiri saat kamu
    sedang membuka halaman buku (misalnya di SIBI). Ia membaca info yang <em>sedang tampil di layar
    kamu</em> — judul, cover, dan link PDF/unduh kalau ada — lalu membuka tab baru ke form Tambah Buku
    dengan data itu sudah terisi otomatis. Kamu tinggal cek dan simpan.
  </p>

  <div class="alert alert-light border small">
    <strong>Ini BUKAN scraper otomatis</strong> — tidak ada proses yang jalan sendiri di server tanpa kamu
    klik. Tetap satu klik per buku, tapi kamu tidak perlu copy-paste manual judul/cover/link satu-satu.
  </div>

  <div class="text-center my-4 p-4" style="background:#f4f6f5;border-radius:8px;">
    <a href="<?= htmlspecialchars($bookmarkletHref) ?>" class="btn btn-lg btn-success" onclick="return false;" draggable="true">
      <i class="bi bi-bookmark-plus"></i> 📚 Clip ke E-Library
    </a>
    <p class="small text-muted mt-2 mb-0"><strong>Seret tombol ini</strong> ke bar bookmark browser kamu (jangan diklik di sini).</p>
  </div>

  <h6 class="mt-4">Cara pakai</h6>
  <ol class="small">
    <li>Seret tombol hijau di atas ke bookmarks bar browser (Ctrl+Shift+B / Cmd+Shift+B untuk menampilkannya kalau tersembunyi).</li>
    <li>Buka <a href="https://buku.kemendikdasmen.go.id/katalog" target="_blank">SIBI</a>, cari buku, buka halaman detailnya sampai bukunya kelihatan penuh di layar.</li>
    <li>Klik bookmarklet "📚 Clip ke E-Library" di bookmarks bar.</li>
    <li>Tab baru terbuka ke form Tambah Buku di E-Library dengan judul/cover/link (jika ketemu) sudah terisi — lengkapi sisanya (kategori, jenjang) lalu simpan.</li>
  </ol>

  <div class="alert alert-warning small mb-0">
    <i class="bi bi-exclamation-triangle"></i> <strong>Catatan jujur:</strong> SIBI adalah aplikasi web modern (React)
    yang kadang menyembunyikan link unduhan di balik tombol/JavaScript, bukan tag <code>&lt;a&gt;</code> biasa —
    jadi bookmarklet ini tidak selalu berhasil menangkap link PDF secara otomatis. Kalau link PDF tidak
    otomatis terisi, salin manual dari tombol unduh di halaman itu dan tempel ke kolom "link PDF" di form.
    Judul dan cover biasanya lebih konsisten terbaca.
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
