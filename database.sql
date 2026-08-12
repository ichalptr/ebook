-- =====================================================
-- Database: pamulihan_elibrary
-- Perpustakaan Digital Interaktif Desa Pamulihan
-- =====================================================

CREATE DATABASE IF NOT EXISTS pamulihan_elibrary
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pamulihan_elibrary;

-- ---------------------------------------------------
-- Tabel: users
-- ---------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','teacher','student') NOT NULL DEFAULT 'student',
    school VARCHAR(150) DEFAULT NULL,
    grade_level VARCHAR(20) DEFAULT NULL,   -- SD/SMP/SMA/SMK
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Tabel: categories
-- ---------------------------------------------------
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Tabel: books
-- ---------------------------------------------------
CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(150) DEFAULT NULL,
    publisher VARCHAR(150) DEFAULT NULL,
    year_published YEAR DEFAULT NULL,
    isbn VARCHAR(30) DEFAULT NULL,
    description TEXT,
    grade_level ENUM('SD','SMP','SMA/SMK','Umum') DEFAULT 'Umum',
    category_id INT DEFAULT NULL,
    cover_image VARCHAR(255) DEFAULT NULL,     -- path lokal atau URL cover
    file_path VARCHAR(255) DEFAULT NULL,       -- path PDF di uploads/books
    page_count INT DEFAULT 0,
    source ENUM('manual','google_books','open_library') DEFAULT 'manual',
    external_id VARCHAR(100) DEFAULT NULL,     -- id dari Google Books/Open Library
    is_downloadable TINYINT(1) DEFAULT 0,
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Tabel: reading_history (progress baca per siswa)
-- ---------------------------------------------------
CREATE TABLE reading_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    current_page INT DEFAULT 1,
    last_read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_book (user_id, book_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Tabel: favorites
-- ---------------------------------------------------
CREATE TABLE favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_fav (user_id, book_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- Data awal: kategori
-- ---------------------------------------------------
INSERT INTO categories (name, slug) VALUES
('Cerita', 'cerita'),
('Sains', 'sains'),
('Matematika', 'matematika'),
('Sejarah', 'sejarah'),
('IPS', 'ips'),
('Literasi', 'literasi'),
('Muatan Lokal', 'muatan-lokal'),
('Pengetahuan Umum', 'pengetahuan-umum');

-- ---------------------------------------------------
-- Admin default
-- JANGAN insert admin manual di sini.
-- Setelah import database ini, buka setup_admin.php SATU KALI
-- di browser untuk membuat akun admin dengan password ter-hash
-- dengan benar. Hapus file setup_admin.php setelah dipakai.
-- ---------------------------------------------------

-- ---------------------------------------------------
-- Contoh buku (boleh dihapus/diganti data asli)
-- ---------------------------------------------------
INSERT INTO books (title, author, description, grade_level, category_id, cover_image, source, is_downloadable)
VALUES
('Laskar Pelangi', 'Andrea Hirata', 'Kisah perjuangan anak-anak Belitung mengejar pendidikan.', 'SMA/SMK', 1, NULL, 'manual', 0),
('Dongeng Si Kancil', 'Cerita Rakyat Nusantara', 'Kumpulan dongeng fabel Nusantara untuk anak SD.', 'SD', 1, NULL, 'manual', 1);
