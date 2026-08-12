<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['status' => 'error', 'message' => 'Belum login']);
    exit;
}

$bookId = (int)($_POST['book_id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($bookId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']);
    exit;
}

$check = $pdo->prepare("SELECT id FROM favorites WHERE user_id=:u AND book_id=:b");
$check->execute([':u' => $userId, ':b' => $bookId]);
$existing = $check->fetch();

if ($existing) {
    $pdo->prepare("DELETE FROM favorites WHERE id = :id")->execute([':id' => $existing['id']]);
    echo json_encode(['status' => 'ok', 'active' => false]);
} else {
    $pdo->prepare("INSERT INTO favorites (user_id, book_id) VALUES (:u, :b)")
        ->execute([':u' => $userId, ':b' => $bookId]);
    echo json_encode(['status' => 'ok', 'active' => true]);
}
