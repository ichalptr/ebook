<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $stmt = $pdo->prepare("SELECT cover_image, file_path FROM books WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $book = $stmt->fetch();

    if ($book) {
        if ($book['cover_image'] && !filter_var($book['cover_image'], FILTER_VALIDATE_URL)) {
            @unlink(UPLOAD_COVER_DIR . $book['cover_image']);
        }
        if ($book['file_path'] && !filter_var($book['file_path'], FILTER_VALIDATE_URL)) {
            @unlink(UPLOAD_BOOK_DIR . $book['file_path']);
        }
        $pdo->prepare("DELETE FROM books WHERE id = :id")->execute([':id' => $id]);
    }
}
header('Location: ' . BASE_URL . '/admin/books.php?deleted=1');
exit;
