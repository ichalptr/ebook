# Pamulihan E-Library
Perpustakaan Digital Interaktif Desa Pamulihan, Kecamatan Pamulihan, Kabupaten Sumedang.

Project KKN — PHP Native + MySQL + Bootstrap 5, dengan reader PDF ala buku asli (PDF.js) dan
import metadata buku otomatis dari Google Books API.

## 1. Kebutuhan
- PHP 8.0+ (pakai `match`, jadi minimal PHP 8.0)
- MySQL/MariaDB
- Ekstensi PHP: `pdo_mysql`, `curl`
- Server lokal: XAMPP / Laragon / MAMP

## 2. Instalasi

1. Salin folder `pamulihan-elibrary` ke `htdocs` (XAMPP) atau `www` (Laragon).
2. Buat database baru lalu import `database.sql`:
   ```
   mysql -u root -p < database.sql
   ```
   atau lewat phpMyAdmin: buat database `pamulihan_elibrary`, lalu Import file `database.sql`.
3. Buka `config/db.php`, sesuaikan:
   - `DB_USER`, `DB_PASS` sesuai MySQL kamu
   - `BASE_URL` sesuai alamat lokal, contoh: `http://localhost/pamulihan-elibrary`
4. Buka browser ke `http://localhost/pamulihan-elibrary/setup_admin.php` dan buat akun admin pertama.
   **Setelah berhasil, hapus file `setup_admin.php` dari server** (alasan keamanan).
5. Login di `login.php` dengan akun admin yang baru dibuat.
6. Pastikan folder berikut punya izin tulis (write permission) untuk PHP:
   - `uploads/covers/`
   - `uploads/books/`

## 3. Struktur Folder

```
pamulihan-elibrary/
├── config/db.php          # koneksi database & konstanta path
├── includes/               # header, footer, auth, admin & guru layout
├── admin/                  # dashboard admin (CRUD buku, kategori, user, import)
├── guru/                   # dashboard guru (rekomendasi buku ke siswa)
├── ajax/                   # endpoint AJAX (simpan progress baca, favorit)
├── uploads/covers/         # cover buku ter-upload
├── uploads/books/          # file PDF buku ter-upload
├── assets/                 # CSS & JS
├── index.php                Beranda
├── katalog.php              Pencarian & filter buku
├── detail.php                Detail buku
├── baca.php                  Reader PDF (PDF.js, progress otomatis tersimpan)
├── login.php / register.php / logout.php
├── verify.php                Verifikasi email dari link
├── resend_verification.php   Kirim ulang link verifikasi
├── forgot_password.php       Form minta link reset password
├── reset_password.php        Form set password baru dari link reset
├── rak_saya.php              Favorit, lanjutkan membaca & rekomendasi guru (siswa)
├── setup_admin.php           Setup admin pertama (hapus setelah dipakai!)
├── database.sql              Skema database + data awal (setup baru)
└── migration_v2.sql          Migrasi buat database lama (guru + rate limit login)
```

## 4. Alur Pemakaian

**Admin:**
1. Tambah kategori di menu *Kategori*.
2. Tambah buku manual (*Tambah Buku*) atau cari & import metadata dari Google Books
   di menu *Import Buku* — lalu lengkapi kategori/jenjang dan **upload file PDF sendiri**
   dari sumber yang legal (buku open-license, izin penerbit, atau sumber resmi pemerintah).
3. Pantau statistik di *Dashboard*.

**Siswa/Guru:**
1. Daftar akun di `register.php`.
2. Jelajahi *Katalog*, buka *Detail Buku*, lalu klik **Baca Buku**.
3. Progress halaman otomatis tersimpan — saat buku dibuka lagi, akan lanjut dari halaman terakhir.
4. Buku favorit & riwayat baca bisa dilihat di *Rak Saya*.

## 5. Dashboard Guru & Keamanan Login

Folder `guru/` berisi dashboard khusus role `teacher`:
- `guru/dashboard.php` — statistik siswa terdaftar & rekomendasi yang sudah dikirim.
- `guru/rekomendasi.php` — form untuk merekomendasikan buku ke siswa tertentu (dengan catatan opsional).

Siswa melihat rekomendasi yang masuk di `rak_saya.php`, tab **Rekomendasi Guru**.

Selain itu, `login.php` sekarang punya rate limiting sederhana: akun otomatis terkunci 5 menit
setelah 5 kali percobaan password salah berturut-turut (kolom `failed_attempts` & `locked_until`
di tabel `users`).

**Verifikasi Email & Lupa Password:**
- Saat daftar (`register.php`), akun belum aktif sampai email diverifikasi lewat link di `verify.php`.
- Login diblokir kalau email belum diverifikasi (ada tombol "kirim ulang" via `resend_verification.php`).
- Lupa password lewat `forgot_password.php` → link reset (berlaku 1 jam) → `reset_password.php`.
- Pengiriman email pakai SMTP sederhana di `includes/mailer.php` (tanpa Composer/PHPMailer).
  **Kosongkan `SMTP_USER` di `config/db.php`** kalau belum mau setting email asli — sistem otomatis
  menampilkan link verifikasi/reset **langsung di layar** (mode dev, aman buat development di
  localhost). Untuk email beneran, isi `SMTP_HOST`, `SMTP_USER`, `SMTP_PASS` di `config/db.php`
  (misal pakai Gmail App Password).
- Akun admin yang dibuat lewat `setup_admin.php` otomatis terverifikasi, tidak perlu cek email.

**Pagination Katalog:** `katalog.php` sekarang menampilkan 12 buku per halaman, dengan navigasi
halaman di bagian bawah (filter pencarian/kategori/jenjang tetap terbawa saat pindah halaman).

**Setup kalau database kamu sudah pernah di-import dari `database.sql` versi lama** (belum ada
kolom `failed_attempts`/`locked_until`/`email_verified_at`/dst atau tabel `recommendations`),
jalankan migrasi ini SATU KALI:
```
mysql -u root -p pamulihan_elibrary < migration_v2.sql
```
Kalau baru setup dari nol, cukup import `database.sql` — perubahan ini sudah termasuk di dalamnya,
tidak perlu jalankan `migration_v2.sql` lagi.

## 6. Pengembangan Lanjutan (opsional)
- Tantangan membaca / badge, statistik per sekolah.
- Flipbook animasi 2 halaman sekaligus (saat ini reader sudah pakai animasi flip 1 halaman + page turning via PDF.js).
- Ganti Google Books API dengan Open Library API sebagai sumber tambahan.

## 7. Bookmarklet "Clip ke E-Library" (ala Mendeley Web Importer)

Untuk mengambil **metadata + link PDF sekaligus** dengan sekali klik saat kamu sedang membuka halaman
buku di SIBI (atau situs buku lain), gunakan menu **Admin → Bookmarklet**.

**Cara kerjanya (penting untuk dipahami):**
- Ini **bukan** scraper/crawler otomatis yang jalan sendiri di server.
- Ini adalah skrip kecil yang berjalan **di browser kamu sendiri**, dipicu manual, saat kamu sedang
  membuka satu halaman buku. Ia membaca apa yang sedang tampil di layar kamu saat itu, lalu membuka
  form Tambah Buku dengan data (judul, cover, link PDF jika ketemu) sudah terisi.
- Ini sama seperti cara kerja Mendeley Web Importer atau Zotero Connector — bedanya dengan scraper
  otomatis: tetap perlu manusia membuka tiap halaman satu per satu, hanya saja tidak perlu copy-paste
  manual semua informasinya.

**Keterbatasan jujur:** karena SIBI adalah aplikasi React modern, link unduhan PDF-nya kadang
disembunyikan di balik tombol JavaScript (bukan tag link `<a href="...pdf">` biasa), sehingga
bookmarklet tidak selalu berhasil menangkap link PDF secara otomatis. Judul dan cover biasanya lebih
konsisten terbaca. Kalau link PDF kosong, tinggal salin manual dari tombol unduh di halaman itu.

**Kenapa tidak dibuat scraper otomatis penuh?** SIBI secara eksplisit memblokir akses otomatis lewat
robots.txt. Bookmarklet ini menghormati batas itu karena tetap dijalankan oleh manusia yang mengakses
halaman secara normal lewat browser — persis seperti kamu klik kanan → save as, hanya lebih cepat.

## 8. Import Otomatis Metadata Resmi (Kemendikdasmen)

**Ini beda dari SIBI** — Pusat Perbukuan Kemendikdasmen ternyata menyediakan **data terbuka** (bukan
lewat SIBI, tapi lewat Data Acuan SIPLah) yang BISA diakses otomatis oleh program:

- Endpoint: `https://siplah.kemendikdasmen.go.id/sds/lookup-tables/msts/books/text_books.json`
- Dokumentasi: https://wartek-id.gitlab.io/sds/sds/siplah/docs/references/books/
- Berisi ribuan Buku Teks Pelajaran (BTP) kurikulum nasional: judul, penulis, penerbit, mata pelajaran,
  jenjang, kelas, ISBN, cover — **tapi tanpa file PDF** (data ini disiapkan untuk keperluan pengadaan
  sekolah, bukan distribusi file).

Gunakan menu **Admin → Import Resmi (Kemendikdasmen)** untuk mengambil metadata ini otomatis:
filter jenjang/mapel, pilih buku, import sekali klik. Setelah metadata masuk, file PDF-nya tetap perlu
ditambahkan manual dari SIBI lewat menu Edit Buku (tempel link atau upload) — karena kedua sumber ini
saling melengkapi: data resmi untuk metadata, SIBI untuk file bacanya.

## 9. Sumber Buku Paket Sekolah GRATIS & LEGAL

Untuk mengisi katalog dengan **buku paket sekolah** (bukan hanya cerita anak), gunakan **SIBI (Sistem
Informasi Perbukuan Indonesia)** — portal resmi Pusat Perbukuan, Kemendikdasmen:

**https://buku.kemendikdasmen.go.id/katalog**

Ini adalah sumber Buku Sekolah Elektronik (BSE): pemerintah sudah membeli hak cipta buku-buku ini dari
penulis/penerbit, sehingga boleh diunduh dan disebarluaskan gratis secara legal untuk kebutuhan
pendidikan seperti perpustakaan sekolah/desa.

**Cara memasukkannya ke E-Library:**
1. Buka SIBI, filter jenjang (SD/SMP/SMA/SMK) dan mata pelajaran.
2. Buka buku yang dicari, unduh PDF-nya (atau salin link download langsung jika tersedia).
3. Di Admin → *Tambah Buku* / *Edit Buku*, ada dua opsi mengisi file:
   - **Upload PDF** yang sudah diunduh, atau
   - **Tempel link PDF** langsung di kolom "atau tempel link PDF resmi" — server kamu tidak perlu
     menyimpan filenya sendiri, cukup mengarah ke sumber resmi.

Karena SIBI berupa aplikasi web modern tanpa API publik sederhana, proses ini masih manual per buku
(tidak bisa di-bulk-import otomatis seperti Google Books). Tapi karena satu buku mata pelajaran biasanya
dipakai lintas tahun ajaran, ini investasi sekali kerja yang tahan lama.

Sumber tambahan (gunakan dengan bijak, selalu utamakan yang resmi Kemendikdasmen):
- Rumah Belajar (bse.kemdikbud.go.id) — arsip BSE lama, kurikulum KTSP/K13.
- Mirror non-resmi seperti bukusekolahdigital.com — bersumber dari BSE resmi, tapi verifikasi dulu
  kesesuaian kurikulumnya sebelum dipakai.

