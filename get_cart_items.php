<?php
require_once 'db_config.php';
require_once 'auth_checker.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get cart items with product details
    $sql = "SELECT c.cart_id, c.quantity, c.selling_price, p.name, p.product_code, pi.image_url 
            FROM cart c 
            JOIN products p ON c.product_id = p.product_id 
            LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1 
            WHERE c.user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($cart_items)) {
        echo json_encode([
            'success' => true, 
            'html' => '<div class="text-center py-4"><i class="fas fa-shopping-cart fa-3x mb-3 text-muted"></i><p>Your cart is empty</p></div>',
            'total' => 0
        ]);
        exit();
    }
    
    // Generate HTML for cart items
    $html = '';
    $total = 0;
    
    foreach ($cart_items as $item) {
        $item_total = $item['quantity'] * $item['selling_price'];
        $total += $item_total;
        
        $html .= '<div class="cart-item" data-cart-id="' . $item['cart_id'] . '">';
        if (!empty($item['image_url'])) {
            $html .= '<img src="' . htmlspecialchars($item['image_url']) . '" class="cart-item-img" alt="' . htmlspecialchars($item['name']) . '">';
        } else {
            $html .= '<div class="cart-item-img bg-light d-flex align-items-center justify-content-center"><i class="fas fa-image text-muted"></i></div>';
        }
        $html .= '<div class="cart-item-details">';
        $html .= '<h6>' . htmlspecialchars($item['name']) . '</h6>';
        $html .= '<p class="mb-1">Code: ' . htmlspecialchars($item['product_code']) . '</p>';
        $html .= '<p class="mb-1">Price: ৳' . number_format($item['selling_price'], 2) . '</p>';
        $html .= '<div class="quantity-controls">';
        $html .= '<button class="quantity-minus"><i class="fas fa-minus"></i></button>';
        $html .= '<input type="number" class="quantity-input" value="' . $item['quantity'] . '" min="1" max="99">';
        $html .= '<button class="quantity-plus"><i class="fas fa-plus"></i></button>';
        $html .= '</div>';
        $html .= '<div class="cart-item-actions">';
        $html .= '<span>Subtotal: ৳<span class="item-total">' . number_format($item_total, 2) . '</span></span>';
        $html .= '<button class="btn btn-sm btn-outline-danger remove-item"><i class="fas fa-trash"></i></button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'total' => $total
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>