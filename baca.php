<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_login(); // hanya user login yang bisa baca (progress tersimpan per akun)

$user = current_user();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM books WHERE id = :id");
$stmt->execute([':id' => $id]);
$book = $stmt->fetch();

if (!$book || !$book['file_path']) {
    http_response_code(404);
    die('File buku tidak ditemukan.');
}

// Ambil progress terakhir
$r = $pdo->prepare("SELECT current_page FROM reading_history WHERE user_id=:u AND book_id=:b");
$r->execute([':u' => $user['id'], ':b' => $id]);
$progress = $r->fetch();
$startPage = $progress ? (int)$progress['current_page'] : 1;

$fileUrl = filter_var($book['file_path'], FILTER_VALIDATE_URL)
    ? $book['file_path']
    : UPLOAD_BOOK_URL . $book['file_path'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Membaca — <?= htmlspecialchars($book['title']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body { background:#2b2b2b; margin:0; }
  .reader-toolbar { background:#1c1c1c; color:#fff; padding:.6rem 1rem; display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; }
  .reader-toolbar .title { font-weight:600; flex:1; min-width:150px; }
  #pdf-container { display:flex; justify-content:center; padding:2rem 1rem; min-height:calc(100vh - 60px); overflow:auto; }
  .page-flip-wrap { background:#fff; box-shadow:0 10px 40px rgba(0,0,0,.5); border-radius:4px; transition:opacity .2s ease; }
  #pageCanvas { display:block; max-width:100%; height:auto; }
  .page-flip-wrap.flipping { animation: flipPage .35s ease; }
  @keyframes flipPage {
    0%   { transform: rotateY(0deg);   opacity: 1; }
    45%  { transform: rotateY(8deg);   opacity: .6; }
    100% { transform: rotateY(0deg);   opacity: 1; }
  }
  .btn-nav:disabled { opacity:.3; }
</style>
</head>
<body>

<div class="reader-toolbar">
  <a href="<?= BASE_URL ?>/detail.php?id=<?= $book['id'] ?>" class="btn btn-sm btn-outline-light"><i class="bi bi-arrow-left"></i> Kembali</a>
  <span class="title text-truncate"><?= htmlspecialchars($book['title']) ?></span>

  <button id="prevPage" class="btn btn-sm btn-light btn-nav"><i class="bi bi-chevron-left"></i></button>
  <span class="small">
    Hal <input id="pageNum" type="number" value="<?= $startPage ?>" min="1" style="width:60px" class="text-center rounded">
    / <span id="pageCount">-</span>
  </span>
  <button id="nextPage" class="btn btn-sm btn-light btn-nav"><i class="bi bi-chevron-right"></i></button>

  <button id="zoomOut" class="btn btn-sm btn-outline-light"><i class="bi bi-zoom-out"></i></button>
  <button id="zoomIn" class="btn btn-sm btn-outline-light"><i class="bi bi-zoom-in"></i></button>
  <button id="fullscreenBtn" class="btn btn-sm btn-outline-light"><i class="bi bi-arrows-fullscreen"></i></button>
  <span id="saveStatus" class="small text-success"></span>
</div>

<div class="progress progress-thin" style="height:4px;">
  <div id="readProgress" class="progress-bar bg-success" style="width:0%"></div>
</div>

<div id="pdf-container">
  <div class="page-flip-wrap" id="flipWrap">
    <canvas id="pageCanvas"></canvas>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

const url = <?= json_encode($fileUrl) ?>;
const bookId = <?= (int)$book['id'] ?>;
let pdfDoc = null;
let pageNum = <?= (int)$startPage ?>;
let scale = 1.3;
let rendering = false;
let saveTimeout = null;

const canvas = document.getElementById('pageCanvas');
const ctx = canvas.getContext('2d');
const flipWrap = document.getElementById('flipWrap');

function renderPage(num, animate = true) {
  if (rendering) return;
  rendering = true;
  pdfDoc.getPage(num).then(function (page) {
    const viewport = page.getViewport({ scale });
    canvas.width = viewport.width;
    canvas.height = viewport.height;

    const renderTask = page.render({ canvasContext: ctx, viewport });
    renderTask.promise.then(function () {
      rendering = false;
      document.getElementById('pageNum').value = num;
      document.getElementById('readProgress').style.width = (num / pdfDoc.numPages * 100) + '%';
      if (animate) {
        flipWrap.classList.add('flipping');
        setTimeout(() => flipWrap.classList.remove('flipping'), 350);
      }
      scheduleSaveProgress(num);
    });
  });
}

function scheduleSaveProgress(page) {
  clearTimeout(saveTimeout);
  saveTimeout = setTimeout(() => saveProgress(page), 800);
}

function saveProgress(page) {
  fetch('<?= BASE_URL ?>/ajax/save_progress.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `book_id=${bookId}&page=${page}`
  }).then(r => r.json()).then(data => {
    const el = document.getElementById('saveStatus');
    if (data.status === 'ok') {
      el.textContent = 'Tersimpan';
      setTimeout(() => el.textContent = '', 1200);
    }
  }).catch(() => {});
}

document.getElementById('prevPage').addEventListener('click', () => {
  if (pageNum <= 1) return;
  pageNum--; renderPage(pageNum);
});
document.getElementById('nextPage').addEventListener('click', () => {
  if (pageNum >= pdfDoc.numPages) return;
  pageNum++; renderPage(pageNum);
});
document.getElementById('pageNum').addEventListener('change', (e) => {
  let n = parseInt(e.target.value) || 1;
  n = Math.max(1, Math.min(n, pdfDoc.numPages));
  pageNum = n; renderPage(pageNum);
});
document.getElementById('zoomIn').addEventListener('click', () => { scale += 0.2; renderPage(pageNum, false); });
document.getElementById('zoomOut').addEventListener('click', () => { scale = Math.max(0.5, scale - 0.2); renderPage(pageNum, false); });
document.getElementById('fullscreenBtn').addEventListener('click', () => {
  document.documentElement.requestFullscreen?.();
});

// Navigasi keyboard: panah kiri/kanan
document.addEventListener('keydown', (e) => {
  if (e.key === 'ArrowRight') document.getElementById('nextPage').click();
  if (e.key === 'ArrowLeft') document.getElementById('prevPage').click();
});

pdfjsLib.getDocument(url).promise.then(function (doc) {
  pdfDoc = doc;
  document.getElementById('pageCount').textContent = doc.numPages;
  pageNum = Math.min(pageNum, doc.numPages);
  renderPage(pageNum, false);
}).catch(function (err) {
  document.getElementById('pdf-container').innerHTML =
    '<p class="text-light">Gagal memuat file PDF: ' + err.message + '</p>';
});
</script>
</body>
</html>
