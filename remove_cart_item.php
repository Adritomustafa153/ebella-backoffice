<?php
require_once 'db_config.php';
require_once 'auth_checker.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['cart_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_id = intval($_POST['cart_id']);

try {
    // Verify the cart item belongs to the user
    $check_stmt = $pdo->prepare("SELECT cart_id FROM cart WHERE cart_id = ? AND user_id = ?");
    $check_stmt->execute([$cart_id, $user_id]);
    
    if ($check_stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Cart item not found']);
        exit();
    }
    
    // Delete the cart item
    $delete_stmt = $pdo->prepare("DELETE FROM cart WHERE cart_id = ?");
    $delete_stmt->execute([$cart_id]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>