<?php
/**
 * Motif "sawah berundak" (terraced hills) — elemen signature visual
 * yang menghubungkan identitas Desa Pamulihan (dataran tinggi, sawah
 * berundak) dengan tema buku/perpustakaan. Dipakai di bawah hero
 * dan di atas footer (dibalik dengan class .flip).
 *
 * Pakai: render_terrace_divider(bool $flip = false, string $extraClass = '')
 */
function render_terrace_divider(bool $flip = false, string $extraClass = ''): void {
    $cls = 'terrace-divider' . ($flip ? ' flip' : '') . ($extraClass ? ' ' . $extraClass : '');
    ?>
    <div class="<?= $cls ?>" aria-hidden="true">
      <svg viewBox="0 0 1440 120" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M0,80 L120,68 L240,86 L360,60 L480,78 L600,52 L720,74 L840,50 L960,72 L1080,48 L1200,70 L1320,54 L1440,68 L1440,120 L0,120 Z" fill="rgba(255,255,255,0.10)"/>
        <path d="M0,96 L110,86 L230,100 L350,80 L470,98 L590,76 L710,94 L830,74 L950,92 L1070,72 L1190,90 L1310,76 L1440,90 L1440,120 L0,120 Z" fill="rgba(255,255,255,0.18)"/>
        <path d="M0,110 L130,102 L260,114 L390,98 L520,112 L650,96 L780,110 L910,96 L1040,110 L1170,98 L1300,112 L1440,104 L1440,120 L0,120 Z" fill="#f7f2e7"/>
      </svg>
    </div>
    <?php
}
