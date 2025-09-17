<?php
// checkout.php
require_once 'auth_checker.php';
require_once 'db_config.php';

// Get cart items
$cart_items = [];
$subtotal = 0;
$discount = 0;
$total = 0;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    $cart_query = "
        SELECT c.cart_id as cart_id, p.product_id, p.name, p.price, c.quantity,
               (SELECT image_url FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as image
        FROM cart c 
        JOIN products p ON c.product_id = p.product_id  
        WHERE c.user_id = ?
    ";
    
    $cart_stmt = $conn->prepare($cart_query);
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $result = $cart_stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $item_total = $row['price'] * $row['quantity'];
        $subtotal += $item_total;
        $cart_items[] = $row;
    }
    
    $cart_stmt->close();
    
    // Apply discount if submitted
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['discount'])) {
        $discount = floatval($_POST['discount']);
        if ($discount > $subtotal) {
            $discount = $subtotal;
        }
    }
    
    $total = $subtotal - $discount;
    if ($total < 0) $total = 0;
    
    // Process order placement
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
        // Insert order into database
        $order_query = "INSERT INTO orders (total_price, discount, payment_status ) VALUES (?, ?, 'pending')";
        $order_stmt = $conn->prepare($order_query);
        $order_stmt->bind_param("dd", $user_id, $subtotal, $discount, $total);
        
        if ($order_stmt->execute()) {
            $order_id = $conn->insert_id;
            
            // Insert order items
            $order_item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $order_item_stmt = $conn->prepare($order_item_query);
            
            foreach ($cart_items as $item) {
                $order_item_stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
                $order_item_stmt->execute();
            }
            
            // Clear cart
            $clear_cart_query = "DELETE FROM cart WHERE user_id = ?";
            $clear_cart_stmt = $conn->prepare($clear_cart_query);
            $clear_cart_stmt->bind_param("i", $user_id);
            $clear_cart_stmt->execute();
            $clear_cart_stmt->close();
            
            // Redirect to order confirmation
            header("Location: order_confirmation.php?order_id=$order_id");
            exit;
        }
        
        $order_stmt->close();
    }
}

$page_title = "Ebella Management - Checkout";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #d291bc;
            --secondary-color: #f7ebec;
            --accent-color: #a0d2db;
            --dark-color: #846267;
            --light-color: #fff9fb;
        }
        
        .brand-logo {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .sidebar {
            background-color: var(--dark-color);
            color: white;
            height: 100vh;
            position: fixed;
            padding-top: 60px;
        }
        
        .sidebar a {
            color: white;
            padding: 15px 20px;
            display: block;
            text-decoration: none;
            transition: 0.3s;
        }
        
        .sidebar a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar a.active {
            background-color: var(--primary-color);
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            padding-top: 80px;
        }
        
        .navbar {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: calc(100% - 250px);
            margin-left: 250px;
            z-index: 1000;
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .price-align {
            text-align: right;
        }
        
        .summary-box {
            background-color: var(--secondary-color);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }
        
        .card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 20px;
            border: none;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--dark-color);
            border-color: var(--dark-color);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .table th {
            background-color: var(--secondary-color);
            color: var(--dark-color);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(210, 145, 188, 0.25);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding-top: 15px;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .navbar {
                width: 100%;
                margin-left: 0;
            }
        }
        
        .default-product-image {
            width: 80px;
            height: 80px;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_users.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_products.php">Add Product</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="checkout.php">Checkout</a>
                    </li>
                </ul>
                
                <div class="d-flex align-items-center">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?php echo $_SESSION["user_role"]; ?>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Profile</a></li>
                            <li><a class="dropdown-item" href="#">Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="main-content">
        <div class="container-fluid">
            <h2 class="mb-4">Checkout</h2>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Order Items</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($cart_items) > 0): ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th class="price-align">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cart_items as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if (!empty($item['image'])): ?>
                                                            <img src="<?php echo htmlspecialchars($item['image']); ?>" class="product-image me-3" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                        <?php else: ?>
                                                            <div class="default-product-image me-3">
                                                                <i class="fas fa-image fa-2x"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div><?php echo htmlspecialchars($item['name']); ?></div>
                                                    </div>
                                                </td>
                                                <td>৳<?php echo number_format($item['price'], 2); ?></td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td class="price-align">৳<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="text-center">Your cart is empty.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="checkout.php">
                                <div class="mb-3">
                                    <label for="subtotal" class="form-label">Subtotal</label>
                                    <input type="text" class="form-control price-align" id="subtotal" value="৳<?php echo number_format($subtotal, 2); ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="discount" class="form-label">Discount</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="discount" name="discount" min="0" max="<?php echo $subtotal; ?>" step="0.01" value="<?php echo $discount; ?>">
                                        <button class="btn btn-outline-primary" type="submit">Apply</button>
                                    </div>
                                    <div class="form-text">Enter discount amount to apply</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="total" class="form-label">Total</label>
                                    <input type="text" class="form-control price-align fw-bold" id="total" value="৳<?php echo number_format($total, 2); ?>" readonly>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100" name="place_order">Place Order</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update total when discount changes
        document.getElementById('discount').addEventListener('input', function() {
            const subtotal = <?php echo $subtotal; ?>;
            const discount = parseFloat(this.value) || 0;
            const total = Math.max(0, subtotal - discount);
            document.getElementById('total').value = '৳' + total.toFixed(2);
        });
    </script>
</body>
</html>