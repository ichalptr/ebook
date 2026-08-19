// Pamulihan E-Library — interaksi umum
document.addEventListener('DOMContentLoaded', function () {
  // Tooltip Bootstrap
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

  // Scroll reveal untuk elemen .reveal (kartu buku, dsb.)
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
    document.querySelectorAll('.reveal').forEach((el, i) => {
      el.style.transitionDelay = Math.min(i * 40, 300) + 'ms';
      io.observe(el);
    });
  } else {
    document.querySelectorAll('.reveal').forEach(el => el.classList.add('is-visible'));
  }

  // Highlight kategori aktif (chip) berdasarkan query string
  const params = new URLSearchParams(location.search);
  const activeCat = params.get('category');
  if (activeCat) {
    document.querySelectorAll('.chip').forEach(chip => {
      if (chip.href.includes('category=' + activeCat)) chip.classList.add('active');
    });
  }
});
