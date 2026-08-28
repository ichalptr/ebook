<footer class="pl-footer py-4 mt-5">
  <div class="container text-center">
    <p class="mb-1"><i class="bi bi-book-half"></i> <strong>Pamulihan E-Library</strong></p>
    <p class="small mb-0">
      Perpustakaan Digital Interaktif Desa Pamulihan, Kecamatan Pamulihan, Kabupaten Sumedang.<br>
      "Buka Gadget, Buka Buku." — Program KKN &copy; <?= date('Y') ?>
    </p>
  </div>
</footer>

<!-- Bottom nav mobile ala aplikasi -->
<nav class="mobile-bottom-nav">
  <div class="row g-0">
    <a href="<?= BASE_URL ?>/index.php" class="<?= $currentPage === 'index.php' ? 'active-link' : '' ?>">
      <i class="bi bi-house<?= $currentPage === 'index.php' ? '-fill' : '' ?>"></i> Beranda
    </a>
    <a href="<?= BASE_URL ?>/katalog.php" class="<?= $currentPage === 'katalog.php' ? 'active-link' : '' ?>">
      <i class="bi bi-grid<?= $currentPage === 'katalog.php' ? '-fill' : '' ?>"></i> Katalog
    </a>
    <?php if ($user): ?>
    <a href="<?= BASE_URL ?>/rak_saya.php" class="<?= $currentPage === 'rak_saya.php' ? 'active-link' : '' ?>">
      <i class="bi bi-bookmark<?= $currentPage === 'rak_saya.php' ? '-fill' : '' ?>"></i> Rak Saya
    </a>
    <a href="<?= $user['role'] === 'admin' ? BASE_URL . '/admin/dashboard.php' : BASE_URL . '/index.php' ?>">
      <i class="bi bi-person-circle"></i> Akun
    </a>
    <?php else: ?>
    <a href="<?= BASE_URL ?>/login.php" class="<?= $currentPage === 'login.php' ? 'active-link' : '' ?>">
      <i class="bi bi-box-arrow-in-right"></i> Masuk
    </a>
    <a href="<?= BASE_URL ?>/register.php" class="<?= $currentPage === 'register.php' ? 'active-link' : '' ?>">
      <i class="bi bi-person-plus"></i> Daftar
    </a>
    <?php endif; ?>
  </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
