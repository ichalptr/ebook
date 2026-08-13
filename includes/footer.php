<?php require_once __DIR__ . '/terrace_divider.php'; ?>
<footer class="site-footer mt-5">
  <?php render_terrace_divider(true); ?>
  <div class="container text-center pb-4">
    <p class="mb-1 font-display fst-italic" style="color:#fff;font-size:1.1rem;">
      <i class="bi bi-book-half"></i> Pamulihan E-Library
    </p>
    <p class="small mb-0" style="color:rgba(255,255,255,.65);">
      Perpustakaan Digital Interaktif Desa Pamulihan, Kecamatan Pamulihan, Kabupaten Sumedang.<br>
      "Buka Gadget, Buka Buku." — Program KKN Universitas Muhammadiyah Bandung &copy; <?= date('Y') ?>
    </p>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
