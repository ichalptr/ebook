</main>

<footer class="pl-footer">
  <div class="container">
    <div class="row g-4 pb-4">
      <div class="col-lg-4 col-6">
        <div class="footer-brand">
          <span class="brand-mark"><i class="bi bi-book-half"></i></span>
          Pamulihan E-Library
        </div>
        <p class="small mb-0" style="max-width:280px;">
          Perpustakaan digital interaktif Desa Pamulihan, Kecamatan Pamulihan, Kabupaten Sumedang — gratis untuk siswa dan guru.
        </p>
      </div>
      <div class="col-lg-2 col-6">
        <h6>Jelajahi</h6>
        <ul>
          <li><a href="<?= BASE_URL ?>/index.php">Beranda</a></li>
          <li><a href="<?= BASE_URL ?>/katalog.php">Katalog Buku</a></li>
          <?php if ($user): ?><li><a href="<?= BASE_URL ?>/rak_saya.php">Rak Saya</a></li><?php endif; ?>
        </ul>
      </div>
      <div class="col-lg-3 col-6">
        <h6>Akun</h6>
        <ul>
          <?php if ($user): ?>
            <li><a href="<?= $user['role'] === 'admin' ? BASE_URL . '/admin/dashboard.php' : BASE_URL . '/index.php' ?>">Akun Saya</a></li>
            <li><a href="<?= BASE_URL ?>/logout.php">Keluar</a></li>
          <?php else: ?>
            <li><a href="<?= BASE_URL ?>/login.php">Masuk</a></li>
            <li><a href="<?= BASE_URL ?>/register.php">Daftar Gratis</a></li>
          <?php endif; ?>
        </ul>
      </div>
      <div class="col-lg-3 col-6">
        <h6>Program</h6>
        <ul>
          <li><span style="color:rgba(250,246,236,.68);">KKN Desa Pamulihan</span></li>
          <li><span style="color:rgba(250,246,236,.68);">Kec. Pamulihan, Kab. Sumedang</span></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom d-flex flex-wrap justify-content-between gap-2">
      <span>&copy; <?= date('Y') ?> <strong>Pamulihan E-Library</strong> — Program KKN.</span>
      <span>"Buka Gadget, Buka Buku."</span>
    </div>
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
