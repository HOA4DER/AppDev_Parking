<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (empty($_SESSION['is_signed_in']) || empty($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE user_id = ? ORDER BY issued_at DESC");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll();
    echo json_encode($bookings);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
exit;
?>
