<?php
require_once 'auth_checker.php';
require_once 'db_config.php';

$page_title = "Ebella Management - View Products";

// Handle product deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // First check if product exists
    $check_stmt = $conn->prepare("SELECT product_id FROM products WHERE product_id = ?");
    $check_stmt->bind_param("i", $delete_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        // Delete product (this will cascade to product_images due to foreign key constraint)
        $delete_stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $delete_stmt->bind_param("i", $delete_id);
        
        if ($delete_stmt->execute()) {
            $message = "Product deleted successfully!";
        } else {
            $message = "Error deleting product: " . $delete_stmt->error;
        }
        $delete_stmt->close();
    } else {
        $message = "Product not found!";
    }
    $check_stmt->close();
    
    // Redirect to avoid resubmission
    header("Location: view_products.php?message=" . urlencode($message));
    exit();
}

// Handle status toggle
if (isset($_GET['toggle_status'])) {
    $toggle_id = intval($_GET['toggle_status']);
    
    // Get current status
    $status_stmt = $conn->prepare("SELECT is_active FROM products WHERE product_id = ?");
    $status_stmt->bind_param("i", $toggle_id);
    $status_stmt->execute();
    $status_stmt->bind_result($current_status);
    $status_stmt->fetch();
    $status_stmt->close();
    
    // Toggle status
    $new_status = $current_status ? 0 : 1;
    
    $toggle_stmt = $conn->prepare("UPDATE products SET is_active = ? WHERE product_id = ?");
    $toggle_stmt->bind_param("ii", $new_status, $toggle_id);
    
    if ($toggle_stmt->execute()) {
        $message = "Product status updated!";
    } else {
        $message = "Error updating product status: " . $toggle_stmt->error;
    }
    $toggle_stmt->close();
    
    // Redirect to avoid resubmission
    header("Location: view_products.php?message=" . urlencode($message));
    exit();
}

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $user_id = $_SESSION['user_id'];
    
    // Get product details to set selling price
    $product_stmt = $conn->prepare("SELECT price FROM products WHERE product_id = ?");
    $product_stmt->bind_param("i", $product_id);
    $product_stmt->execute();
    $product_stmt->bind_result($product_price);
    $product_stmt->fetch();
    $product_stmt->close();
    
    // Check if product already in cart
    $check_cart = $conn->prepare("SELECT cart_id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $check_cart->bind_param("ii", $user_id, $product_id);
    $check_cart->execute();
    $check_cart->store_result();
    
    if ($check_cart->num_rows > 0) {
        // Update quantity
        $check_cart->bind_result($cart_id, $quantity);
        $check_cart->fetch();
        $new_quantity = $quantity + 1;
        
        $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ?");
        $update_stmt->bind_param("ii", $new_quantity, $cart_id);
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        // Add to cart with product price
        $insert_stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, selling_price) VALUES (?, ?, 1, ?)");
        $insert_stmt->bind_param("iid", $user_id, $product_id, $product_price);
        $insert_stmt->execute();
        $insert_stmt->close();
    }
    $check_cart->close();
    
    // Return JSON response for AJAX
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Product added to cart!']);
    exit();
}

// Get cart count
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $cart_stmt = $conn->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_stmt->bind_result($cart_count);
    $cart_stmt->fetch();
    $cart_stmt->close();
}

// Fetch all products with their primary images
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT p.*, pi.image_url 
        FROM products p 
        LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1 
        WHERE p.name LIKE ? OR p.product_code LIKE ? OR p.product_category LIKE ?
        ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
$search_param = "%$search_term%";
$stmt->bind_param("sss", $search_param, $search_param, $search_param);
$stmt->execute();
$result = $stmt->get_result();
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
        
        .product-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 20px;
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .product-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        
        .product-card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px;
            text-align: center;
        }
        
        .product-price {
            font-size: 1.25rem;
            font-weight: bold;
            color: var(--dark-color);
        }
        
        .product-stock {
            font-size: 0.9rem;
        }
        
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }
        
        .no-image {
            height: 200px;
            background-color: var(--secondary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark-color);
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .action-buttons .btn {
            flex: 1;
            min-width: 80px;
        }
        
        .category {
            margin-left: 55px;
            color: green;
        }
        
        /* Cart sidebar */
        .cart-sidebar {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100vh;
            background: white;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
            transition: right 0.3s ease;
            z-index: 1050;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        
        .cart-sidebar.open {
            right: 0;
        }
        
        .cart-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1040;
            display: none;
        }
        
        .cart-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--primary-color);
            color: white;
        }
        
        .cart-items {
            padding: 20px;
            flex: 1;
            overflow-y: auto;
        }
        
        .cart-item {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 15px;
        }
        
        .cart-item-details {
            flex: 1;
        }
        
        .cart-footer {
            padding: 20px;
            border-top: 1px solid #eee;
            background-color: #f8f9fa;
        }
        
        .cart-total {
            font-weight: bold;
            font-size: 1.2rem;
            margin-bottom: 15px;
        }
        
        /* Search suggestions */
        .search-container {
            position: relative;
            margin-right: 15px;
        }
        
        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            z-index: 1000;
            display: none;
        
        }
        
        .search-suggestion-item {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            color: black;
        }
        
        .search-suggestion-item:hover {
            background-color: #f8f9fa;
        }
        
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: red;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }
        
        .quantity-btn {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #ddd;
            background: #f8f9fa;
            cursor: pointer;
        }
        
        .quantity-input {
            width: 50px;
            height: 30px;
            text-align: center;
            border: 1px solid #ddd;
            margin: 0 5px;
        }
        
        .cart-item-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
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
            
            .cart-sidebar {
                width: 100%;
                right: -100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
<?php include 'nav.php' ?>
    
    <!-- Cart Sidebar -->
    <div class="cart-overlay" id="cartOverlay"></div>
    <div class="cart-sidebar" id="cartSidebar">
        <div class="cart-header">
            <h5 class="mb-0">Your Cart</h5>
            <button type="button" class="btn-close btn-close-white" id="closeCart"></button>
        </div>
        <div class="cart-items" id="cartItems">
            <!-- Cart items will be loaded here -->
        </div>
        <div class="cart-footer">
            <div class="cart-total">
                Total: ৳<span id="cartTotal">0.00</span>
            </div>
            <button class="btn btn-primary w-100" id="checkoutBtn">Proceed to Checkout</button>
        </div>
    </div>
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Product Management</h2>
                <a href="add_products.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-1"></i> Add New Product
                </a>
            </div>
            
            <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header" style="background-color: var(--primary-color); color: white;">
                            <h5 class="mb-0">Product Statistics</h5>
                                                <div class="search-container me-3">
                        <input type="text" id="productSearch" class="form-control" placeholder="Search products..." 
                               value="<?php echo htmlspecialchars($search_term); ?>">
                        <div class="search-suggestions" id="searchSuggestions"></div>
                    </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php
                                // Get product statistics
                                $stats_sql = "SELECT 
                                    COUNT(*) as total_products,
                                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_products,
                                    SUM(CASE WHEN stock_quantity <= 0 THEN 1 ELSE 0 END) as out_of_stock
                                    FROM products";
                                $stats_result = $conn->query($stats_sql);
                                $stats = $stats_result->fetch_assoc();
                                ?>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h3><?php echo $stats['total_products']; ?></h3>
                                            <p class="mb-0">Total Products</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h3><?php echo $stats['active_products']; ?></h3>
                                            <p class="mb-0">Active Products</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h3><?php echo $stats['out_of_stock']; ?></h3>
                                            <p class="mb-0">Out of Stock</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="col-md-4 col-lg-3 mb-4">
                        <div class="card product-card">
                            <?php if (!empty($row['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($row['image_url']); ?>" class="product-image" alt="<?php echo htmlspecialchars($row['name']); ?>">
                            <?php else: ?>
                                <div class="no-image">
                                    <i class="fas fa-image fa-3x"></i>
                                </div>
                            <?php endif; ?>
                            
                            <span class="status-badge badge <?php echo $row['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                            
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($row['name']); ?></h5>
                                <p class="card-text">
                                    <small class="text-muted">Code: <?php echo htmlspecialchars($row['product_code']); ?></small>
                                </p>
                                <p class="card-text product-price">৳<?php echo number_format($row['price'], 2); ?></p>
                                <p class="card-text product-stock <?php echo $row['stock_quantity'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                    Stock: <?php echo $row['stock_quantity']; ?>
                                </p>
                                <p class="card-text">
                                    <small class="text-muted">Category: <?php echo htmlspecialchars($row['product_category']); ?></small>
                                </p>
                                
                                <div class="action-buttons mt-3">
                                    <a href="edit_product.php?id=<?php echo $row['product_id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="view_products.php?toggle_status=<?php echo $row['product_id']; ?>" 
                                       class="btn btn-sm <?php echo $row['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                        <i class="fas <?php echo $row['is_active'] ? 'fa-eye-slash' : 'fa-eye'; ?>"></i> 
                                        <?php echo $row['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                    </a>
                                    <a href="view_products.php?delete_id=<?php echo $row['product_id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this product?');">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                    <?php if ($row['is_active'] && $row['stock_quantity'] > 0): ?>
                                    <button class="btn btn-sm btn-success add-to-cart" data-product-id="<?php echo $row['product_id']; ?>">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center">
                            No products found. <a href="add_products.php">Add your first product</a>.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cart functionality
        const cartIcon = document.getElementById('cartIcon');
        const cartSidebar = document.getElementById('cartSidebar');
        const cartOverlay = document.getElementById('cartOverlay');
        const closeCart = document.getElementById('closeCart');
        const cartItems = document.getElementById('cartItems');
        const cartTotal = document.getElementById('cartTotal');
        
        // Function to open cart
        function openCart() {
            cartSidebar.classList.add('open');
            cartOverlay.style.display = 'block';
            loadCartItems();
        }
        
        // Function to close cart
        function closeCartSidebar() {
            cartSidebar.classList.remove('open');
            cartOverlay.style.display = 'none';
        }
        
        // Event listeners
        cartIcon.addEventListener('click', openCart);
        closeCart.addEventListener('click', closeCartSidebar);
        cartOverlay.addEventListener('click', closeCartSidebar);
        
        // Load cart items via AJAX
        function loadCartItems() {
            fetch('get_cart_items.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        cartItems.innerHTML = data.html;
                        cartTotal.textContent = data.total.toFixed(2);
                        
                        // Add event listeners to cart items
                        addCartItemEventListeners();
                    } else {
                        cartItems.innerHTML = '<div class="text-center py-4"><i class="fas fa-exclamation-circle fa-3x mb-3 text-danger"></i><p>' + data.message + '</p></div>';
                        cartTotal.textContent = '0.00';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    cartItems.innerHTML = '<div class="text-center py-4"><i class="fas fa-exclamation-circle fa-3x mb-3 text-danger"></i><p>Error loading cart items</p></div>';
                });
        }
        
        // Add event listeners to cart items
        function addCartItemEventListeners() {
            // Quantity increase buttons
            document.querySelectorAll('.quantity-plus').forEach(button => {
                button.addEventListener('click', function() {
                    const input = this.parentElement.querySelector('.quantity-input');
                    const max = parseInt(input.getAttribute('max')) || 999;
                    let value = parseInt(input.value) + 1;
                    if (value > max) value = max;
                    input.value = value;
                    updateCartItem(this.closest('.cart-item'));
                });
            });
            
            // Quantity decrease buttons
            document.querySelectorAll('.quantity-minus').forEach(button => {
                button.addEventListener('click', function() {
                    const input = this.parentElement.querySelector('.quantity-input');
                    let value = parseInt(input.value) - 1;
                    if (value < 1) value = 1;
                    input.value = value;
                    updateCartItem(this.closest('.cart-item'));
                });
            });
            
            // Quantity input change
            document.querySelectorAll('.quantity-input').forEach(input => {
                input.addEventListener('change', function() {
                    const max = parseInt(this.getAttribute('max')) || 999;
                    const min = parseInt(this.getAttribute('min')) || 1;
                    let value = parseInt(this.value);
                    
                    if (isNaN(value) || value < min) value = min;
                    if (value > max) value = max;
                    
                    this.value = value;
                    updateCartItem(this.closest('.cart-item'));
                });
            });
            
            // Remove item buttons
            document.querySelectorAll('.remove-item').forEach(button => {
                button.addEventListener('click', function() {
                    const cartItem = this.closest('.cart-item');
                    removeCartItem(cartItem);
                });
            });
        }
        
        // Update cart item quantity
        function updateCartItem(cartItem) {
            const cartId = cartItem.getAttribute('data-cart-id');
            const quantity = cartItem.querySelector('.quantity-input').value;
            
            fetch('update_cart_item.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `cart_id=${cartId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload cart to update totals
                    loadCartItems();
                    updateCartBadge();
                } else {
                    alert('Error updating cart: ' + data.message);
                    loadCartItems(); // Reload to reset values
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating cart item');
                loadCartItems(); // Reload to reset values
            });
        }
        
        // Remove cart item
        function removeCartItem(cartItem) {
            if (!confirm('Are you sure you want to remove this item from your cart?')) {
                return;
            }
            
            const cartId = cartItem.getAttribute('data-cart-id');
            
            fetch('remove_cart_item.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `cart_id=${cartId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove item from UI
                    cartItem.remove();
                    
                    // Check if cart is empty
                    if (document.querySelectorAll('.cart-item').length === 0) {
                        cartItems.innerHTML = '<div class="text-center py-4"><i class="fas fa-shopping-cart fa-3x mb-3 text-muted"></i><p>Your cart is empty</p></div>';
                        cartTotal.textContent = '0.00';
                    } else {
                        // Reload cart to update totals
                        loadCartItems();
                    }
                    
                    updateCartBadge();
                } else {
                    alert('Error removing item: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error removing item');
            });
        }
        
        // Add to cart functionality
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                
                fetch('view_products.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'add_to_cart=true&product_id=' + productId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update cart badge
                        updateCartBadge();
                        
                        // Open cart automatically
                        openCart();
                    } else {
                        alert('Error adding to cart: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error adding to cart');
                });
            });
        });
        
        // Update cart badge count
        function updateCartBadge() {
            fetch('get_cart_count.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let cartBadge = document.querySelector('.cart-badge');
                        if (data.count > 0) {
                            if (cartBadge) {
                                cartBadge.textContent = data.count;
                            } else {
                                // Create badge if it doesn't exist
                                cartBadge = document.createElement('span');
                                cartBadge.className = 'cart-badge';
                                cartBadge.textContent = data.count;
                                cartIcon.appendChild(cartBadge);
                            }
                        } else {
                            // Remove badge if count is 0
                            if (cartBadge) {
                                cartBadge.remove();
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
        
        // Search functionality
        const searchInput = document.getElementById('productSearch');
        const searchSuggestions = document.getElementById('searchSuggestions');
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                searchSuggestions.style.display = 'none';
                return;
            }
            
            searchTimeout = setTimeout(() => {
                fetch('search_products.php?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0) {
                            searchSuggestions.innerHTML = '';
                            data.forEach(product => {
                                const item = document.createElement('div');
                                item.className = 'search-suggestion-item';
                                item.textContent = product.name + ' (' + product.product_code + ')';
                                item.addEventListener('click', () => {
                                    window.location.href = 'view_products.php?search=' + encodeURIComponent(product.name);
                                });
                                searchSuggestions.appendChild(item);
                            });
                            searchSuggestions.style.display = 'block';
                        } else {
                            searchSuggestions.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        searchSuggestions.style.display = 'none';
                    });
            }, 300);
        });
        
        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchSuggestions.contains(e.target)) {
                searchSuggestions.style.display = 'none';
            }
        });
        
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            updateCartBadge();
        });
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>