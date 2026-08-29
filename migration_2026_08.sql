USE pamulihan_elibrary;

-- --- 1. Kolom rate-limit login, verifikasi email, reset password ---
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS failed_attempts INT NOT NULL DEFAULT 0 AFTER grade_level,
    ADD COLUMN IF NOT EXISTS locked_until TIMESTAMP NULL DEFAULT NULL AFTER failed_attempts,
    ADD COLUMN IF NOT EXISTS email_verified_at TIMESTAMP NULL DEFAULT NULL AFTER locked_until,
    ADD COLUMN IF NOT EXISTS verification_token VARCHAR(64) DEFAULT NULL AFTER email_verified_at,
    ADD COLUMN IF NOT EXISTS reset_token VARCHAR(64) DEFAULT NULL AFTER verification_token,
    ADD COLUMN IF NOT EXISTS reset_expires TIMESTAMP NULL DEFAULT NULL AFTER reset_token;

-- Akun yang sudah ada sebelumnya dianggap terverifikasi otomatis,
-- supaya tidak ke-lock saat login setelah migrasi ini.
UPDATE users SET email_verified_at = created_at WHERE email_verified_at IS NULL;

-- --- 2. Tabel rekomendasi buku (guru -> siswa) ---
CREATE TABLE IF NOT EXISTS recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    student_id INT NOT NULL,
    book_id INT NOT NULL,
    note VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_recommendation (teacher_id, student_id, book_id),
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --- 3. Perluas ENUM `source` di tabel books ---
-- Supaya hasil Import CSV & Import Resmi Kemendikdasmen tidak lagi
-- ke-tag 'manual' begitu saja (sebelumnya ENUM belum punya slot ini).
ALTER TABLE books
    MODIFY source ENUM('manual','google_books','open_library','csv_import','kemendikdasmen') DEFAULT 'manual';
