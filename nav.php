<?php
// navbar.php
require_once 'auth_checker.php';
require_once 'db_config.php';

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
?>

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
                    <a class="nav-link active" href="view_products.php">Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="add_products.php">Add Product</a>
                </li>
            </ul>
            
            <div class="d-flex align-items-center">
                <!-- Search Bar -->
                <!-- <div class="search-container me-3">
                    <input type="text" id="productSearch" class="form-control" placeholder="Search products..." 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <div class="search-suggestions" id="searchSuggestions"></div>
                </div> -->
                
                <!-- Cart Icon on the right side -->
                <div class="me-3 position-relative">
                    <button class="btn btn-outline-primary position-relative" id="cartIcon">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-badge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </button>
                </div>
                
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

<!-- Cart Sidebar - Positioned on the right side -->
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
        <div class="cart-total d-flex justify-content-between">
            <span>Total:</span>
            <span>৳<span id="cartTotal">0.00</span></span>
        </div>
        <button class="btn btn-primary w-100 mt-2" id="checkoutBtn">Proceed to Checkout</button>
    </div>
</div>

<style>
    /* Cart sidebar - Right side with animation from right to left */
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
        animation: slideInRight 0.3s ease;
    }
    
    @keyframes slideInRight {
        from {
            right: -400px;
            opacity: 0;
        }
        to {
            right: 0;
            opacity: 1;
        }
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
        animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
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
    
    .cart-item-price {
        text-align: right;
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .cart-footer {
        padding: 20px;
        border-top: 1px solid #eee;
        background-color: #f8f9fa;
    }
    
    .cart-total {
        font-weight: bold;
        font-size: 1.2rem;
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
        .cart-sidebar {
            width: 100%;
            right: -100%;
        }
        
        .cart-sidebar.open {
            right: 0;
            animation: slideInRightMobile 0.3s ease;
        }
        
        @keyframes slideInRightMobile {
            from {
                right: -100%;
                opacity: 0;
            }
            to {
                right: 0;
                opacity: 1;
            }
        }
    }
</style>

<script>
    // Cart functionality for navbar
    document.addEventListener('DOMContentLoaded', function() {
        const cartIcon = document.getElementById('cartIcon');
        const cartSidebar = document.getElementById('cartSidebar');
        const cartOverlay = document.getElementById('cartOverlay');
        const closeCart = document.getElementById('closeCart');
        const cartItems = document.getElementById('cartItems');
        const cartTotal = document.getElementById('cartTotal');
        const checkoutBtn = document.getElementById('checkoutBtn');
        
        // Function to open cart with animation
        function openCart() {
            cartSidebar.classList.add('open');
            cartOverlay.style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent scrolling when cart is open
            loadCartItems();
        }
        
        // Function to close cart with animation
        function closeCartSidebar() {
            cartSidebar.classList.remove('open');
            cartOverlay.style.display = 'none';
            document.body.style.overflow = ''; // Re-enable scrolling
        }
        
        // Event listeners
        cartIcon.addEventListener('click', openCart);
        closeCart.addEventListener('click', closeCartSidebar);
        cartOverlay.addEventListener('click', closeCartSidebar);
        
        // Redirect to checkout page
        checkoutBtn.addEventListener('click', function() {
            window.location.href = 'checkout.php';
        });
        
        // Close cart with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && cartSidebar.classList.contains('open')) {
                closeCartSidebar();
            }
        });
        
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
        
        // Initialize cart badge on page load
        updateCartBadge();
    });
</script>