// Pamulihan E-Library — script umum
document.addEventListener('DOMContentLoaded', function () {
  // Tooltip Bootstrap (jika ada elemen data-bs-toggle="tooltip")
  const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));

  // Fade-in halus untuk gambar cover saat sudah selesai dimuat,
  // supaya transisi dari kartu kosong -> cover terasa lebih rapi.
  document.querySelectorAll('.book-cover-wrap img').forEach(img => {
    if (img.complete) return;
    img.style.opacity = 0;
    img.style.transition = 'opacity .25s ease';
    img.addEventListener('load', () => { img.style.opacity = 1; });
  });
});
