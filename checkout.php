<?php
// checkout.php
require_once 'auth_checker.php';
require_once 'db_config.php';

// Get cart items
$cart_items = [];
$subtotal = 0;
$discount = 0;
$total = 0;

// Initialize customer variables
$customer_name = '';
$customer_phone = '';
$customer_address = '';
$customer_email = '';
$load_from_saved = false;
$save_customer_info = false;
$selected_customer_id = null;

// Initialize success message
$success_message = '';

// Fetch all customers for the dropdown
$customers = [];
$customer_query = "SELECT customer_id, name, phone, email, address FROM customers";
$customer_result = $conn->query($customer_query);
if ($customer_result && $customer_result->num_rows > 0) {
    while ($row = $customer_result->fetch_assoc()) {
        $customers[] = $row;
    }
}

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    $cart_query = "
        SELECT c.cart_id as cart_id, p.product_id, p.name, p.price, p.purchase_price, c.quantity,
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
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Get customer info from form
        $customer_name = trim($_POST['customer_name']);
        $customer_phone = trim($_POST['customer_phone']);
        $customer_address = trim($_POST['customer_address']);
        $customer_email = trim($_POST['customer_email']);
        $load_from_saved = isset($_POST['load_from_saved']) ? true : false;
        $save_customer_info = isset($_POST['save_customer_info']) ? true : false;
        
        // Apply discount if submitted
        if (isset($_POST['discount'])) {
            $discount = floatval($_POST['discount']);
            if ($discount > $subtotal) {
                $discount = $subtotal;
            }
        }
        
        // If loading from saved customer
        if ($load_from_saved && isset($_POST['customer_select']) && !empty($_POST['customer_select'])) {
            $selected_customer_id = intval($_POST['customer_select']);
            
            // Fetch customer details
            $customer_detail_query = "SELECT * FROM customers WHERE customer_id = ?";
            $customer_detail_stmt = $conn->prepare($customer_detail_query);
            $customer_detail_stmt->bind_param("i", $selected_customer_id);
            $customer_detail_stmt->execute();
            $customer_result = $customer_detail_stmt->get_result();
            
            if ($customer_result->num_rows > 0) {
                $customer_data = $customer_result->fetch_assoc();
                $customer_name = $customer_data['name'];
                $customer_phone = $customer_data['phone'];
                $customer_address = $customer_data['address'];
                $customer_email = $customer_data['email'];
            }
            $customer_detail_stmt->close();
        }
        
        $total = $subtotal - $discount;
        if ($total < 0) $total = 0;
        
        // Process order placement
        if (isset($_POST['place_order'])) {
            // Validate required customer fields
            if (empty($customer_name) || empty($customer_phone) || empty($customer_address)) {
                $error_message = "Please fill in all required customer information (Name, Phone, and Address).";
            } else {
                // Calculate total costing and profit
                $total_costing = 0;
                $total_profit = 0;
                
                foreach ($cart_items as $item) {
                    $purchase_price = floatval($item['purchase_price']);
                    $item_cost = $purchase_price * $item['quantity'];
                    $item_profit = ($item['price'] - $purchase_price) * $item['quantity'];
                    $total_costing += $item_cost;
                    $total_profit += $item_profit;
                }
                
                // Generate invoice number
                $invoice_no = 'INV-'. rand(10000, 99999);

                $item_count = count($cart_items);
                
                // Insert customer info if needed
                $customer_id = null;
                if ($save_customer_info) {
                    // Check if customer already exists by email or phone
                    $existing_customer_query = "SELECT customer_id FROM customers WHERE email = ? OR phone = ?";
                    $existing_customer_stmt = $conn->prepare($existing_customer_query);
                    $existing_customer_stmt->bind_param("ss", $customer_email, $customer_phone);
                    $existing_customer_stmt->execute();
                    $existing_result = $existing_customer_stmt->get_result();
                    
                    if ($existing_result->num_rows > 0) {
                        // Customer exists, get the ID
                        $existing_customer = $existing_result->fetch_assoc();
                        $customer_id = $existing_customer['customer_id'];
                        
                        // Update customer info
                        $update_customer_query = "UPDATE customers SET name = ?, address = ?, phone = ?, email = ? WHERE customer_id = ?";
                        $update_customer_stmt = $conn->prepare($update_customer_query);
                        $update_customer_stmt->bind_param("ssssi", $customer_name, $customer_address, $customer_phone, $customer_email, $customer_id);
                        $update_customer_stmt->execute();
                        $update_customer_stmt->close();
                    } else {
                        // Insert new customer
                        $insert_customer_query = "INSERT INTO customers (name, address, phone, email, order_date, invoice_no, amount) VALUES (?, ?, ?, ?, CURDATE(), ?, ?)";
                        $insert_customer_stmt = $conn->prepare($insert_customer_query);
                        $insert_customer_stmt->bind_param("sssssd", $customer_name, $customer_address, $customer_phone, $customer_email, $invoice_no, $total);
                        
                        if ($insert_customer_stmt->execute()) {
                            $customer_id = $conn->insert_id;
                        }
                        $insert_customer_stmt->close();
                    }
                    $existing_customer_stmt->close();
                }
                
                // Insert sale into database
                $sale_query = "INSERT INTO sales (invoice_no, total_price, total_costing, total_profit, total_discount, total_items, sales_person_id, customer_id, coustomer_name) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $sale_stmt = $conn->prepare($sale_query);
                $sale_stmt->bind_param("sddddiiis", $invoice_no, $total, $total_costing, $total_profit, $discount, $item_count, $user_id, $customer_id, $customer_name);
                
                if ($sale_stmt->execute()) {
                    $sale_id = $conn->insert_id;
                    
                    // Insert order items
                    $order_query = "INSERT INTO orders (sale_id, product_id, quantity, unit_price, total_price, profit, discount, delivery_address, phone, delivery_status, payment_status, sales_person_id, invoice_no,coustomer_name) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, ?, ?)";
                    $order_stmt = $conn->prepare($order_query);
                    
                    foreach ($cart_items as $item) {
                        $purchase_price = floatval($item['purchase_price']);
                        $item_total_price = $item['price'] * $item['quantity'];
                        $item_profit = ($item['price'] - $purchase_price) * $item['quantity'];
                        $item_discount = 0; // You can implement per-item discount if needed
                        
                        $order_stmt->bind_param("iiiddddssiss", $sale_id, $item['product_id'], $item['quantity'], $item['price'], 
                                               $item_total_price, $item_profit, $item_discount, $customer_address, $customer_phone, $user_id,$invoice_no,$customer_name);
                        $order_stmt->execute();
                    }
                    
                    // Clear cart
                    $clear_cart_query = "DELETE FROM cart WHERE user_id = ?";
                    $clear_cart_stmt = $conn->prepare($clear_cart_query);
                    $clear_cart_stmt->bind_param("i", $user_id);
                    $clear_cart_stmt->execute();
                    $clear_cart_stmt->close();
                    
                    // Set success message
                    $success_message = "Order placed successfully! Invoice #: " . $invoice_no;
                    
                    // Redirect to order confirmation after showing success message
                    echo '<script>
                        setTimeout(function() {
                            window.location.href = "order_confirmation.php?sale_id=' . $sale_id . '";
                        }, 2000);
                    </script>';
                }
                
                $sale_stmt->close();
            }
        }
    } else {
        // Set default values for the form if not submitted
        $total = $subtotal;
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
        
        .customer-select-container {
            display: <?php echo $load_from_saved ? 'block' : 'none'; ?>;
            margin-top: 10px;
        }
        
        .save-customer-fields {
            display: <?php echo $save_customer_info ? 'block' : 'none'; ?>;
            margin-top: 15px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        /* Loading overlay styles */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.9);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            display: none;
        }
        
        .loading-gif {
            width: 200px;
            height: 200px;
        }
        
        .loading-text {
            margin-top: 20px;
            font-size: 18px;
            color: var(--primary-color);
            font-weight: bold;
        }
        
        .success-message {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <img src="giphy5.gif" alt="Loading..." class="loading-gif">
        <div class="loading-text">Processing Your Order...</div>
    </div>

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
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="checkout.php" id="checkoutForm">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Customer Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_name" class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="customer_name" name="customer_name" value="<?php echo htmlspecialchars($customer_name); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="customer_phone" name="customer_phone" value="<?php echo htmlspecialchars($customer_phone); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="customer_address" class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="customer_address" name="customer_address" rows="2" required><?php echo htmlspecialchars($customer_address); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="customer_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="customer_email" name="customer_email" value="<?php echo htmlspecialchars($customer_email); ?>">
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="load_from_saved" name="load_from_saved" <?php echo $load_from_saved ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="load_from_saved">
                                Load from saved customers
                            </label>
                        </div>
                        
                        <div class="customer-select-container" id="customer_select_container">
                            <div class="mb-3">
                                <label for="customer_select" class="form-label">Select Customer</label>
                                <select class="form-select" id="customer_select" name="customer_select">
                                    <option value="">Search for a customer...</option>
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?php echo $customer['customer_id']; ?>" 
                                            <?php echo ($selected_customer_id == $customer['customer_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($customer['name'] . ' - ' . $customer['phone'] . ' - ' . $customer['email']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="save_customer_info" name="save_customer_info" <?php echo $save_customer_info ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="save_customer_info">
                                Save customer information
                            </label>
                        </div>
                    </div>
                </div>
            
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
                                
                                <button type="submit" class="btn btn-primary w-100" name="place_order" id="placeOrderBtn">Place Order</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
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
        
        // Toggle customer select dropdown
        document.getElementById('load_from_saved').addEventListener('change', function() {
            document.getElementById('customer_select_container').style.display = this.checked ? 'block' : 'none';
        });
        
        // Auto-fill form when customer is selected
        document.getElementById('customer_select').addEventListener('change', function() {
            if (this.value) {
                // In a real application, you would fetch customer details via AJAX
                // For now, we'll just submit the form to reload with the selected customer
                this.form.submit();
            }
        });
        
        // Toggle save customer fields
        document.getElementById('save_customer_info').addEventListener('change', function() {
            // This is just for visual feedback - the actual saving happens on the server
            if (this.checked) {
                // You could show additional fields here if needed
            }
        });
        
        // Show loading overlay when place order button is clicked
        document.getElementById('placeOrderBtn').addEventListener('click', function(e) {
            // Only show loading if form is valid
            if (document.getElementById('checkoutForm').checkValidity()) {
                document.getElementById('loadingOverlay').style.display = 'flex';
                
                // Hide loading after 2 seconds (in case redirect doesn't happen)
                setTimeout(function() {
                    document.getElementById('loadingOverlay').style.display = 'none';
                }, 2000);
            }
        });
        
        // If there's a success message, it means the order was placed
        // We'll keep the loading overlay visible for 2 seconds then redirect
        <?php if (!empty($success_message)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('loadingOverlay').style.display = 'flex';
                
                // Change the loading text to success
                document.querySelector('.loading-text').textContent = 'Order Placed Successfully!';
                
                // Hide after 2 seconds (redirect will happen via PHP script)
                setTimeout(function() {
                    document.getElementById('loadingOverlay').style.display = 'none';
                }, 2000);
            });
        <?php endif; ?>
    </script>
</body>
</html>