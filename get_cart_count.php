<?php
require_once 'db_config.php';
require_once 'auth_checker.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    
    echo json_encode(['success' => true, 'count' => $result['count']]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'count' => 0, 'message' => $e->getMessage()]);
}
?>