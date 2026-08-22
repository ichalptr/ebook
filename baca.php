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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600&family=Plus+Jakarta+Sans:wght@500;600&display=swap" rel="stylesheet">
<style>
  :root { --forest-900:#14261C; --forest-700:#1F4D3A; --turmeric-400:#E3A93E; --clay-500:#C1512E; }
  body { background:#20302A; margin:0; font-family:'Plus Jakarta Sans',system-ui,sans-serif; overscroll-behavior:none; }
  .reader-toolbar { background:var(--forest-900); color:#fff; padding:.6rem .9rem; display:flex; align-items:center; gap:.6rem; flex-wrap:wrap; }
  .reader-toolbar .title { font-family:'Fraunces',serif; font-weight:600; flex:1; min-width:120px; font-size:1rem; }
  .reader-toolbar .btn { border-radius:999px; }
  #pdf-container { display:flex; justify-content:center; align-items:flex-start; padding:1.25rem .75rem 5.5rem; min-height:calc(100vh - 60px); overflow:auto; touch-action:pan-y; }
  .page-flip-wrap { display:flex; gap:2px; background:#8f7660; box-shadow:0 20px 50px rgba(0,0,0,.45); border-radius:6px; transition:opacity .2s ease; }
  .page-sheet { display:flex; justify-content:center; align-items:flex-start; min-width:0; background:#fff; overflow:hidden; }
  .page-sheet:first-child { border-radius:6px 0 0 6px; }
  .page-sheet:last-child { border-radius:0 6px 6px 0; }
  .page-sheet.is-empty { background:#e7dfd4; }
  .page-canvas { display:block; width:min(42vw, 560px); max-width:100%; height:auto; }
  .page-flip-wrap.flipping { animation: flipPage .35s ease; }
  @keyframes flipPage {
    0%   { transform: rotateY(0deg) scale(1);   opacity: 1; }
    45%  { transform: rotateY(8deg) scale(.99);   opacity: .6; }
    100% { transform: rotateY(0deg) scale(1);   opacity: 1; }
  }
  .btn-nav:disabled { opacity:.3; }
  #pageNum { background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.25); color:#fff; border-radius:6px; }
  .swipe-hint { position:fixed; bottom:70px; left:50%; transform:translateX(-50%); background:rgba(20,38,28,.85); color:#fff; font-size:.75rem; padding:.5rem 1rem; border-radius:999px; opacity:0; transition:opacity .3s ease; pointer-events:none; z-index:50; }
  .swipe-hint.show { opacity:1; }
  @media (max-width:576px) {
    .reader-toolbar .title { order:1; width:100%; text-align:center; margin-bottom:.25rem; }
    .reader-toolbar { justify-content:center; }
    .page-canvas { width:46vw; }
  }
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
    <div class="page-sheet" id="leftSheet"><canvas id="leftCanvas" class="page-canvas"></canvas></div>
    <div class="page-sheet" id="rightSheet"><canvas id="rightCanvas" class="page-canvas"></canvas></div>
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

const leftCanvas = document.getElementById('leftCanvas');
const rightCanvas = document.getElementById('rightCanvas');
const leftSheet = document.getElementById('leftSheet');
const rightSheet = document.getElementById('rightSheet');
const flipWrap = document.getElementById('flipWrap');

function renderCanvas(page, canvas) {
  const viewport = page.getViewport({ scale });
  canvas.width = viewport.width;
  canvas.height = viewport.height;
  return page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
}

function renderSpread(num, animate = true) {
  if (rendering) return;
  rendering = true;
  const rightPageNum = num + 1;
  Promise.all([
    pdfDoc.getPage(num).then(page => renderCanvas(page, leftCanvas)),
    rightPageNum <= pdfDoc.numPages
      ? pdfDoc.getPage(rightPageNum).then(page => renderCanvas(page, rightCanvas))
      : Promise.resolve()
  ]).then(function () {
    rightSheet.classList.toggle('is-empty', rightPageNum > pdfDoc.numPages);
    rightCanvas.style.display = rightPageNum <= pdfDoc.numPages ? 'block' : 'none';
    rendering = false;
    document.getElementById('pageNum').value = num;
    document.getElementById('readProgress').style.width = (Math.min(rightPageNum, pdfDoc.numPages) / pdfDoc.numPages * 100) + '%';
    if (animate) {
      flipWrap.classList.add('flipping');
      setTimeout(() => flipWrap.classList.remove('flipping'), 350);
    }
    scheduleSaveProgress(num);
  }).catch(function () {
    rendering = false;
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
  pageNum = Math.max(1, pageNum - 2); renderSpread(pageNum);
});
document.getElementById('nextPage').addEventListener('click', () => {
  if (pageNum >= pdfDoc.numPages) return;
  pageNum = Math.min(pdfDoc.numPages, pageNum + 2); renderSpread(pageNum);
});
document.getElementById('pageNum').addEventListener('change', (e) => {
  let n = parseInt(e.target.value) || 1;
  n = Math.max(1, Math.min(n, pdfDoc.numPages));
  pageNum = n; renderSpread(pageNum);
});
document.getElementById('zoomIn').addEventListener('click', () => { scale += 0.2; renderSpread(pageNum, false); });
document.getElementById('zoomOut').addEventListener('click', () => { scale = Math.max(0.5, scale - 0.2); renderSpread(pageNum, false); });
document.getElementById('fullscreenBtn').addEventListener('click', () => {
  document.documentElement.requestFullscreen?.();
});

// Navigasi keyboard: panah kiri/kanan
document.addEventListener('keydown', (e) => {
  if (e.key === 'ArrowRight') document.getElementById('nextPage').click();
  if (e.key === 'ArrowLeft') document.getElementById('prevPage').click();
});

// Navigasi swipe: satu geseran membalik satu spread.
(function () {
  const container = document.getElementById('pdf-container');
  const hint = document.createElement('div');
  hint.className = 'swipe-hint';
  hint.textContent = 'Geser untuk ganti halaman';
  document.body.appendChild(hint);

  let startX = 0, startY = 0, tracking = false;

  container.addEventListener('touchstart', (e) => {
    startX = e.touches[0].clientX;
    startY = e.touches[0].clientY;
    tracking = true;
  }, { passive: true });

  container.addEventListener('touchend', (e) => {
    if (!tracking) return;
    tracking = false;
    const dx = e.changedTouches[0].clientX - startX;
    const dy = e.changedTouches[0].clientY - startY;
    if (Math.abs(dx) > 60 && Math.abs(dx) > Math.abs(dy) * 1.5) {
      if (dx < 0) document.getElementById('nextPage').click();
      else document.getElementById('prevPage').click();
    }
  }, { passive: true });

  // Tampilkan hint sekali di perangkat sentuh
  if ('ontouchstart' in window) {
    setTimeout(() => hint.classList.add('show'), 800);
    setTimeout(() => hint.classList.remove('show'), 3200);
  }
})();

pdfjsLib.getDocument(url).promise.then(function (doc) {
  pdfDoc = doc;
  document.getElementById('pageCount').textContent = doc.numPages;
  pageNum = Math.min(pageNum, doc.numPages);
  renderSpread(pageNum, false);
}).catch(function (err) {
  document.getElementById('pdf-container').innerHTML =
    '<p class="text-light">Gagal memuat file PDF: ' + err.message + '</p>';
});
</script>
</body>
</html>
