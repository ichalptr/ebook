// Pamulihan E-Library — script umum
// Tambahkan interaksi tambahan di sini jika diperlukan (tooltip, animasi, dsb.)
document.addEventListener('DOMContentLoaded', function () {
  const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));
});
