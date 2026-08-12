<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['status' => 'error', 'message' => 'Belum login']);
    exit;
}

$bookId = (int)($_POST['book_id'] ?? 0);
$page   = (int)($_POST['page'] ?? 1);
$userId = $_SESSION['user_id'];

if ($bookId <= 0 || $page <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO reading_history (user_id, book_id, current_page)
    VALUES (:u, :b, :p)
    ON DUPLICATE KEY UPDATE current_page = :p2, last_read_at = CURRENT_TIMESTAMP
");
$stmt->execute([':u' => $userId, ':b' => $bookId, ':p' => $page, ':p2' => $page]);

echo json_encode(['status' => 'ok']);
