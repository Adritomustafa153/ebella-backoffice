<?php
// order_confirmation.php
require_once 'auth_checker.php';
require_once 'db_config.php';

// Check if sale_id is provided
if (!isset($_GET['sale_id']) || empty($_GET['sale_id'])) {
    header("Location: checkout.php");
    exit;
}

$sale_id = intval($_GET['sale_id']);
$user_id = $_SESSION['user_id'];

// Get sale details - FIXED QUERY
$sale_query = "
    SELECT s.*, s.coustomer_name as customer_name, 
           COALESCE(c.email, '') as customer_email,
           COALESCE(c.phone, '') as customer_phone,
           COALESCE(c.address, '') as customer_address
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.customer_id
    WHERE s.sale_id = ? AND s.sales_person_id = ?
";
$sale_stmt = $conn->prepare($sale_query);
$sale_stmt->bind_param("ii", $sale_id, $user_id);
$sale_stmt->execute();
$sale_result = $sale_stmt->get_result();

if ($sale_result->num_rows === 0) {
    header("Location: checkout.php");
    exit;
}

$sale = $sale_result->fetch_assoc();
$sale_stmt->close();

// Get order items
$orders_query = "
    SELECT o.*, p.name as product_name, 
           (SELECT image_url FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as image
    FROM orders o
    JOIN products p ON o.product_id = p.product_id
    WHERE o.sale_id = ?
";
$orders_stmt = $conn->prepare($orders_query);
$orders_stmt->bind_param("i", $sale_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();
$order_items = $orders_result->fetch_all(MYSQLI_ASSOC);
$orders_stmt->close();

$page_title = "Ebella Management - Order Confirmation";
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
        
        .confirmation-animation {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
            margin: 20px 0;
        }
        
        .confirmation-animation img {
            max-width: 100%;
            max-height: 100%;
        }
        
        .success-icon {
            font-size: 80px;
            color: #28a745;
            text-align: center;
            margin: 20px 0;
        }
        
        .print-btn {
            margin-top: 20px;
        }
        
        @media print {
            .sidebar, .navbar, .print-btn {
                display: none !important;
            }
            .main-content {
                margin-left: 0;
                padding-top: 20px;
            }
            .card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
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
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            
            <div class="text-center mb-4">
                <h2>Order Confirmed!</h2>
                <p class="lead">Thank you for your order. Your invoice has been generated successfully.</p>
            </div>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Order Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Invoice Number:</strong> <?php echo htmlspecialchars($sale['invoice_no']); ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Order Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($sale['sale_date'])); ?>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Customer Name:</strong> <?php echo htmlspecialchars($sale['customer_name']); ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Phone:</strong> <?php echo htmlspecialchars($sale['customer_phone']); ?>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Email:</strong> <?php echo htmlspecialchars($sale['customer_email']); ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Address:</strong> <?php echo htmlspecialchars($sale['customer_address']); ?>
                                </div>
                            </div>
                            
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
                                    <?php foreach ($order_items as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($item['image'])): ?>
                                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" class="product-image me-3" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                                    <?php else: ?>
                                                        <div class="default-product-image me-3">
                                                            <i class="fas fa-image fa-2x"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div><?php echo htmlspecialchars($item['product_name']); ?></div>
                                                </div>
                                            </td>
                                            <td>৳<?php echo number_format($item['unit_price'], 2); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td class="price-align">৳<?php echo number_format($item['total_price'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span>৳<?php echo number_format($sale['total_price'] + $sale['total_discount'], 2); ?></span>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Discount:</span>
                                <span class="text-danger">-৳<?php echo number_format($sale['total_discount'], 2); ?></span>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Costing:</span>
                                <span>৳<?php echo number_format($sale['total_costing'], 2); ?></span>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Profit:</span>
                                <span class="text-success">৳<?php echo number_format($sale['total_profit'], 2); ?></span>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between fw-bold fs-5">
                                <span>Total Amount:</span>
                                <span>৳<?php echo number_format($sale['total_price'], 2); ?></span>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-2">
                                <span>Items Count:</span>
                                <span><?php echo $sale['total_items']; ?> items</span>
                            </div>
                            
                            <div class="print-btn">
                                <button class="btn btn-primary w-100 mt-3" onclick="window.print()">
                                    <i class="fas fa-print me-2"></i>Print Invoice
                                </button>
                                
                                <a href="checkout.php" class="btn btn-outline-primary w-100 mt-2">
                                    <i class="fas fa-shopping-cart me-2"></i>New Order
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>