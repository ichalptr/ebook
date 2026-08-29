<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$user = current_user();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM books WHERE id = :id");
$stmt->execute([':id' => $id]);
$book = $stmt->fetch();

if (!$book || !$book['file_path']) {
    http_response_code(404);
    die('File buku tidak ditemukan.');
}

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
  .mode-indicator { font-size:.68rem; color:rgba(255,255,255,.55); letter-spacing:.02em; }
  #pdf-container { display:flex; justify-content:center; align-items:flex-start; padding:1.25rem .75rem 5.5rem; min-height:calc(100vh - 60px); overflow:auto; touch-action:pan-y; }

  /* Mode 2 halaman spread*/
  .page-flip-wrap { display:flex; gap:2px; background:#8f7660; box-shadow:0 20px 50px rgba(0,0,0,.45); border-radius:6px; transition:opacity .2s ease; }
  .page-sheet { display:flex; justify-content:center; align-items:flex-start; min-width:0; background:#fff; overflow:hidden; }
  .page-sheet:first-child { border-radius:6px 0 0 6px; }
  .page-sheet:last-child { border-radius:0 6px 6px 0; }
  .page-sheet.is-empty { background:#e7dfd4; }
  .page-canvas { display:block; width:min(42vw, 560px); max-width:100%; height:auto; }

  /* Mode satu halaman — default di HP/tablet sempit.*/
  .page-flip-wrap.single-mode { background:transparent; gap:0; box-shadow:none; }
  .page-flip-wrap.single-mode .page-sheet:first-child { border-radius:6px; box-shadow:0 20px 50px rgba(0,0,0,.45); }
  .page-flip-wrap.single-mode .page-canvas { width:min(92vw, 760px); }

  .page-flip-wrap.flipping { animation: flipPage .35s ease; }
  @keyframes flipPage {
    0%   { transform: rotateY(0deg) scale(1);   opacity: 1; }
    45%  { transform: rotateY(8deg) scale(.99);   opacity: .6; }
    100% { transform: rotateY(0deg) scale(1);   opacity: 1; }
  }
  .btn-nav:disabled { opacity:.3; }
  #pageNum { background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.25); color:#fff; border-radius:6px; text-align:center; }
  .swipe-hint { position:fixed; bottom:70px; left:50%; transform:translateX(-50%); background:rgba(20,38,28,.85); color:#fff; font-size:.75rem; padding:.5rem 1rem; border-radius:999px; opacity:0; transition:opacity .3s ease; pointer-events:none; z-index:50; }
  .swipe-hint.show { opacity:1; }
  @media (max-width:576px) {
    .reader-toolbar .title { order:1; width:100%; text-align:center; margin-bottom:.25rem; }
    .reader-toolbar { justify-content:center; }
  }
</style>
</head>
<body>

<div class="reader-toolbar">
  <a href="<?= BASE_URL ?>/detail.php?id=<?= $book['id'] ?>" class="btn btn-sm btn-outline-light"><i class="bi bi-arrow-left"></i> Kembali</a>
  <span class="title text-truncate"><?= htmlspecialchars($book['title']) ?></span>

  <button id="prevPage" class="btn btn-sm btn-light btn-nav"><i class="bi bi-chevron-left"></i></button>
  <span class="small">
    Hal <input id="pageNum" type="text" inputmode="text" autocomplete="off" value="<?= $startPage ?>" maxlength="6" style="width:70px" class="rounded">
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
let pageLabels = null;
const labelToPhysical = new Map();

// Mode 2-halaman (spread, ala buku terbuka) otomatis dipakai di layar >=992px.
const spreadQuery = window.matchMedia('(min-width: 992px)');
let spreadMode = spreadQuery.matches;

const leftCanvas = document.getElementById('leftCanvas');
const rightCanvas = document.getElementById('rightCanvas');
const leftSheet = document.getElementById('leftSheet');
const rightSheet = document.getElementById('rightSheet');
const flipWrap = document.getElementById('flipWrap');
const pageNumInput = document.getElementById('pageNum');

function normalizeLabel(s) { return String(s).trim().toLowerCase(); }

function buildLabelMap(labels) {
  labelToPhysical.clear();
  if (!labels) return;
  labels.forEach((label, idx) => labelToPhysical.set(normalizeLabel(label), idx + 1));
}

/** Label yang benar2 TERCETAK di halaman itu (bisa romawi/angka/dll sesuai PDF), fallback ke nomor fisik kalau PDF tak punya label custom. */
function labelFor(physicalPage) {
  if (pageLabels && pageLabels[physicalPage - 1] !== undefined) return pageLabels[physicalPage - 1];
  return String(physicalPage);
}

/**
 * Ubah apa pun yang diketik user (label asli spt "iv"/"IV", atau nomor biasa)
 * jadi nomor halaman FISIK yang valid. Ini akar perbaikan bug "cari halaman
 * acak": sebelumnya input selalu dianggap = nomor fisik mentah, padahal kalau
 * buku punya kata pengantar berlabel romawi sebelum bab 1, nomor cetak "5"
 * bisa jadi halaman fisik ke-15 (bukan ke-5) — bedanya beda2 tiap buku
 * tergantung berapa banyak halaman romawi di depannya.
 */
function resolveTargetPage(rawInput) {
  const raw = String(rawInput).trim();
  if (!raw) return null;
  const norm = normalizeLabel(raw);
  if (labelToPhysical.has(norm)) return labelToPhysical.get(norm);
  const n = parseInt(raw, 10);
  if (!isNaN(n)) return Math.max(1, Math.min(n, pdfDoc.numPages));
  return null;
}

function applyModeClass() {
  flipWrap.classList.toggle('single-mode', !spreadMode);
  rightSheet.style.display = spreadMode ? '' : 'none';
}

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
    const hasRight = rightPageNum <= pdfDoc.numPages;
    rightSheet.classList.toggle('is-empty', !hasRight);
    rightCanvas.style.display = hasRight ? 'block' : 'none';
    rendering = false;
    pageNumInput.value = labelFor(num);
    document.getElementById('readProgress').style.width = (Math.min(rightPageNum, pdfDoc.numPages) / pdfDoc.numPages * 100) + '%';
    if (animate) {
      flipWrap.classList.add('flipping');
      setTimeout(() => flipWrap.classList.remove('flipping'), 350);
    }
    scheduleSaveProgress(num);
  }).catch(function () { rendering = false; });
}

function renderSingle(num, animate = true) {
  if (rendering) return;
  rendering = true;
  pdfDoc.getPage(num).then(page => renderCanvas(page, leftCanvas)).then(function () {
    rendering = false;
    pageNumInput.value = labelFor(num);
    document.getElementById('readProgress').style.width = (num / pdfDoc.numPages * 100) + '%';
    if (animate) {
      flipWrap.classList.add('flipping');
      setTimeout(() => flipWrap.classList.remove('flipping'), 350);
    }
    scheduleSaveProgress(num);
  }).catch(function () { rendering = false; });
}

function renderCurrent(animate = true) {
  if (spreadMode) renderSpread(pageNum, animate);
  else renderSingle(pageNum, animate);
}

function scheduleSaveProgress(page) {
  clearTimeout(saveTimeout);
  saveTimeout = setTimeout(() => saveProgress(page), 800);
}

function saveProgress(page) {
  // Progress selalu disimpan sbg nomor halaman FISIK (kolom current_page INT
  // di DB) — bukan label cetak — supaya tetap unambiguous & konsisten dipakai
  // ulang oleh pdfDoc.getPage() saat dibuka lagi nanti.
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
  const step = spreadMode ? 2 : 1;
  pageNum = Math.max(1, pageNum - step);
  renderCurrent();
});
document.getElementById('nextPage').addEventListener('click', () => {
  if (pageNum >= pdfDoc.numPages) return;
  const step = spreadMode ? 2 : 1;
  pageNum = Math.min(pdfDoc.numPages, pageNum + step);
  renderCurrent();
});
pageNumInput.addEventListener('change', (e) => {
  const target = resolveTargetPage(e.target.value);
  if (target === null) { e.target.value = labelFor(pageNum); return; } // input tak valid, kembalikan spt semula
  pageNum = target;
  renderCurrent();
});
pageNumInput.addEventListener('keydown', (e) => {
  if (e.key === 'Enter') e.target.blur(); // langsung pindah tanpa perlu klik keluar kotak dulu
});

document.getElementById('zoomIn').addEventListener('click', () => { scale += 0.2; renderCurrent(false); });
document.getElementById('zoomOut').addEventListener('click', () => { scale = Math.max(0.5, scale - 0.2); renderCurrent(false); });
document.getElementById('fullscreenBtn').addEventListener('click', () => {
  document.documentElement.requestFullscreen?.();
});

// Ganti mode otomatis kalau lebar layar berubah (rotasi tablet, resize jendela).
spreadQuery.addEventListener('change', (e) => {
  spreadMode = e.matches;
  applyModeClass();
  renderCurrent(false);
});

// Navigasi keyboard: panah kiri/kanan (diabaikan saat user sedang mengetik di kotak halaman)
document.addEventListener('keydown', (e) => {
  if (e.target === pageNumInput) return;
  if (e.key === 'ArrowRight') document.getElementById('nextPage').click();
  if (e.key === 'ArrowLeft') document.getElementById('prevPage').click();
});

// Navigasi swipe (mobile): geser kiri = halaman berikutnya, geser kanan = sebelumnya
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
  return pdfDoc.getPageLabels();
}).then(function (labels) {
  pageLabels = labels;
  buildLabelMap(labels);
  document.getElementById('pageCount').textContent = pdfDoc.numPages;
  pageNum = Math.min(pageNum, pdfDoc.numPages);
  applyModeClass();
  renderCurrent(false);
}).catch(function (err) {
  document.getElementById('pdf-container').innerHTML =
    '<p class="text-light">Gagal memuat file PDF: ' + err.message + '</p>';
});
</script>
</body>
</html>
