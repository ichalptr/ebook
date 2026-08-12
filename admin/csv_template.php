<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="template_import_buku.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['title','author','publisher','year','isbn','description','grade_level','category','cover_url','pdf_url','downloadable']);
fputcsv($out, [
    'Matematika untuk SMP Kelas VII',
    'Tim Kemendikdasmen',
    'Pusat Perbukuan',
    '2024',
    '',
    'Buku teks pelajaran Matematika Kurikulum Merdeka untuk siswa SMP kelas VII.',
    'SMP',
    'Matematika',
    '',
    'https://buku.kemendikdasmen.go.id/katalog/contoh-link-pdf',
    '1',
]);
fputcsv($out, [
    'Dongeng Si Kancil dan Buaya',
    'Cerita Rakyat Nusantara',
    '',
    '',
    '',
    'Kumpulan dongeng fabel Nusantara untuk anak SD.',
    'SD',
    'Cerita',
    '',
    '',
    '0',
]);
fclose($out);
