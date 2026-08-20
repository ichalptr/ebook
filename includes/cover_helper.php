<?php
/**
 * Cover buku dengan fallback "generated cover" (gradient + inisial judul)
 * — menggantikan placeholder abu-abu via.placeholder.com dengan sesuatu
 * yang lebih menarik dilihat siswa, konsisten dengan pendekatan yang
 * sudah dipakai di versi Next.js (GeneratedCover.js).
 *
 * Pakai: echo book_cover_html($book, 'lazy');
 */

/** Palet gradient bertema alam Pamulihan — dipilih hash dari judul agar konsisten per buku. */
function cover_palettes(): array {
    return [
        ['#1f6b3a', '#2f8a4d'], // kanopi
        ['#123321', '#1f6b3a'], // hutan dalam
        ['#8a5a3b', '#b9822a'], // tanah & panen
        ['#d99f34', '#b9822a'], // padi keemasan
        ['#2f8a4d', '#d99f34'], // kanopi ke sawah
        ['#123321', '#8a5a3b'], // malam & tanah
        ['#1f6b3a', '#d99f34'], // hijau ke emas
        ['#8a5a3b', '#1f6b3a'], // tanah ke hijau
    ];
}

function cover_src(array $book): ?string {
    if (empty($book['cover_image'])) return null;
    if (filter_var($book['cover_image'], FILTER_VALIDATE_URL)) {
        return htmlspecialchars($book['cover_image']);
    }
    return UPLOAD_COVER_URL . htmlspecialchars($book['cover_image']);
}

function book_initial(string $title): string {
    $title = trim($title);
    if ($title === '') return '?';
    $words = preg_split('/\s+/', $title);
    $chars = mb_strtoupper(mb_substr($words[0], 0, 1));
    if (count($words) > 1) {
        $chars .= mb_strtoupper(mb_substr($words[1], 0, 1));
    }
    return $chars;
}

/**
 * $imgClass: kelas tambahan (opsional), contoh: 'row-thumb'.
 * Semua output SELALU membawa kelas dasar "cover-media" (lihat style.css)
 * supaya cover/generated-cover selalu mengisi penuh wrapper-nya
 * (width/height 100%, object-fit cover) — tidak bergantung pada pemanggil
 * mengingat untuk menambahkan sizing sendiri.
 */
function book_cover_html(array $book, string $imgClass = ''): string {
    $src = cover_src($book);
    $title = htmlspecialchars($book['title'] ?? '');
    $classes = trim('cover-media ' . $imgClass);

    if ($src) {
        return '<img src="' . $src . '" class="' . htmlspecialchars($classes) . '" alt="' . $title . '" loading="lazy">';
    }

    $palettes = cover_palettes();
    $hash = crc32((string)($book['title'] ?? ''));
    [$c1, $c2] = $palettes[$hash % count($palettes)];
    $initial = htmlspecialchars(book_initial($book['title'] ?? ''));
    $genClasses = trim($classes . ' generated-cover');

    return '<div class="' . htmlspecialchars($genClasses) . '" style="background:linear-gradient(150deg,' . $c1 . ',' . $c2 . ');">'
         . '<span class="initial">' . $initial . '</span>'
         . '<span class="cover-title">' . $title . '</span>'
         . '</div>';
}
