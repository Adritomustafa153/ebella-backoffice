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

// Fetch all products with their primary images
$sql = "SELECT p.*, pi.image_url 
        FROM products p 
        LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1 
        ORDER BY p.created_at DESC";
$result = $conn->query($sql);
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
                        <a class="nav-link active" href="view_products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_products.php">Add Product</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="me-3">Welcome, <?php echo $_SESSION["username"]; ?></span>
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
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php
                                // Get product statistics
                                $total_products = $result->num_rows;
                                $active_products = $conn->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetch_row()[0];
                                $out_of_stock = $conn->query("SELECT COUNT(*) FROM products WHERE stock_quantity = 0")->fetch_row()[0];
                                ?>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <h3><?php echo $total_products; ?></h3>
                                        <p class="text-muted">Total Products</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <h3 class="text-success"><?php echo $active_products; ?></h3>
                                        <p class="text-muted">Active Products</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <h3 class="text-danger"><?php echo $out_of_stock; ?></h3>
                                        <p class="text-muted">Out of Stock</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($product = $result->fetch_assoc()): ?>
                    <div class="col-md-4 col-lg-3 mb-4">
                        <div class="card product-card">
                            <?php if ($product['image_url']): ?>
                                <img src="<?php echo $product['image_url']; ?>" class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php else: ?>
                                <div class="no-image">
                                    <i class="fas fa-image fa-3x"></i>
                                </div>
                            <?php endif; ?>
                            
                            <span class="status-badge badge bg-<?php echo $product['is_active'] ? 'success' : 'danger'; ?>">
                                <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                            
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text product-code">Code: <?php echo htmlspecialchars($product['product_code']); ?></p>
                                
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="product-price">৳<?php echo number_format($product['price'], 2); ?></span>
                                    <span class="product-stock text-<?php echo $product['stock_quantity'] > 0 ? 'success' : 'danger'; ?>">
                                        Stock: <?php echo $product['stock_quantity']; ?>
                                    </span>
                                </div>
                                
                                <p class="card-text small text-muted">
                                    <?php 
                                    if (!empty($product['description'])) {
                                        echo strlen($product['description']) > 100 
                                            ? substr($product['description'], 0, 100) . '...' 
                                            : $product['description'];
                                    } else {
                                        echo 'No description available.';
                                    }
                                    ?>
                                </p>
                                
                                <div class="action-buttons mt-3">
                                    <a href="edit_product.php?id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-outline-primary w-100">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </a>
                                    <a href="view_products.php?toggle_status=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-<?php echo $product['is_active'] ? 'warning' : 'success'; ?>">
                                        <i class="fas fa-power-off me-1"></i> <?php echo $product['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                    </a>
                                    <a href="view_products.php?delete_id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.')">
                                        <i class="fas fa-trash me-1"></i> Delete
                                    </a>
                                </div>
                            </div>
                            <div class="card-footer text-muted small">
                                Added: <?php echo date('M j, Y', strtotime($product['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle me-2"></i> No products found. 
                            <a href="add_products.php" class="alert-link">Add your first product</a>.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>