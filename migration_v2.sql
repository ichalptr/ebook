-- =====================================================
-- Migration: rate limit login + fitur rekomendasi guru
-- Jalankan file ini SATU KALI kalau database kamu sudah pernah
-- di-import dari database.sql versi lama.
-- Kalau baru mau setup dari nol, tinggal import database.sql
-- (sudah termasuk perubahan ini) — tidak perlu file ini.
-- =====================================================
USE pamulihan_elibrary;

ALTER TABLE users
    ADD COLUMN failed_attempts INT NOT NULL DEFAULT 0 AFTER grade_level,
    ADD COLUMN locked_until TIMESTAMP NULL DEFAULT NULL AFTER failed_attempts,
    ADD COLUMN email_verified_at TIMESTAMP NULL DEFAULT NULL AFTER locked_until,
    ADD COLUMN verification_token VARCHAR(64) DEFAULT NULL AFTER email_verified_at,
    ADD COLUMN reset_token VARCHAR(64) DEFAULT NULL AFTER verification_token,
    ADD COLUMN reset_expires TIMESTAMP NULL DEFAULT NULL AFTER reset_token;

-- Akun yang sudah ada sebelumnya dianggap terverifikasi otomatis,
-- supaya kamu (admin/guru/siswa lama) tidak ke-lock saat login setelah migrasi ini.
UPDATE users SET email_verified_at = created_at WHERE email_verified_at IS NULL;

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
