<?php
require_once 'auth_checker.php';
require_once 'db_config.php'; // Assuming you have a database connection file

$page_title = "Ebella Management - Add Product";
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    try {
        // Get form data
        $product_code = $_POST['product_code'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $stock_quantity = $_POST['stock_quantity'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Check if product code already exists
        $check_stmt = $conn->prepare("SELECT product_id FROM products WHERE product_code = ?");
        $check_stmt->bind_param("s", $product_code);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            throw new Exception("Product code already exists. Please use a unique product code.");
        }
        $check_stmt->close();
        
        // Insert product into database
        $stmt = $conn->prepare("INSERT INTO products (product_code, name, description, price, stock_quantity, is_active) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdii", $product_code, $name, $description, $price, $stock_quantity, $is_active);
        
        if ($stmt->execute()) {
            $product_id = $stmt->insert_id;
            $message = "Product added successfully!";
            
            // Handle image uploads if any
            if (!empty($_FILES['product_images']['name'][0])) {
                $image_count = count($_FILES['product_images']['name']);
                $uploaded_count = 0;
                
                for ($i = 0; $i < $image_count; $i++) {
                    if ($_FILES['product_images']['error'][$i] === UPLOAD_ERR_OK) {
                        $file_name = $_FILES['product_images']['name'][$i];
                        $file_tmp = $_FILES['product_images']['tmp_name'][$i];
                        $file_size = $_FILES['product_images']['size'][$i];
                        
                        // Generate unique filename
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        $new_filename = uniqid('img_', true) . '.' . $file_ext;
                        
                        // Define upload path (create this directory if it doesn't exist)
                        $upload_dir = 'uploads/products/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        $destination = $upload_dir . $new_filename;
                        
                        // Check if file is an image
                        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        if (in_array($file_ext, $allowed_ext)) {
                            // Check file size (max 5MB)
                            if ($file_size > 5000000) {
                                $message .= " Image " . ($i+1) . " was too large (max 5MB).";
                                continue;
                            }
                            
                            // Move uploaded file
                            if (move_uploaded_file($file_tmp, $destination)) {
                                // Check if this is the first image to set as primary
                                $is_primary = ($i === 0) ? 1 : 0;
                                
                                // Insert image record into database
                                $image_stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, is_primary) 
                                                             VALUES (?, ?, ?)");
                                $image_stmt->bind_param("isi", $product_id, $destination, $is_primary);
                                $image_stmt->execute();
                                $image_stmt->close();
                                
                                $uploaded_count++;
                            }
                        }
                    }
                }
                
                if ($uploaded_count > 0) {
                    $message .= " and " . $uploaded_count . " image(s) uploaded";
                }
            }
            
            // Redirect to avoid form resubmission
            header("Location: add_products.php?message=" . urlencode($message));
            exit();
        } else {
            throw new Exception("Error adding product: " . $stmt->error);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}
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
        
        .user-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        
        .user-card:hover {
            transform: translateY(-5px);
        }
        
        .user-card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px;
            text-align: center;
        }
        
        .user-card-body {
            padding: 20px;
            background-color: white;
        }
        
        .table th {
            background-color: var(--primary-color);
            color: white;
        }
        
        .modal-header {
            background-color: var(--primary-color);
            color: white;
        }
        
        .preview-image {
            max-width: 100px;
            max-height: 100px;
            margin: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 3px;
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
                        <a class="nav-link" href="view_products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="add_products.php">Add Product</a>
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
            <h2 class="mb-4">Add New Product</h2>
            
            <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($message)): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header" style="background-color: var(--primary-color); color: white;">
                            <h5 class="mb-0">Product Details</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="add_products.php" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Product Code <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="product_code" required>
                                            <div class="form-text">Must be unique. This will be used to identify the product.</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Product Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="name" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="3"></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Price <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">৳</span>
                                                <input type="number" class="form-control" name="price" step="0.01" min="0" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="stock_quantity" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <div class="form-check form-switch mt-2">
                                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                                                <label class="form-check-label" for="is_active">Active (product will be visible to customers)</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Product Images</label>
                                    <input type="file" class="form-control" name="product_images[]" multiple accept="image/*" id="imageUpload">
                                    <div class="form-text">You can select multiple images (max 5MB each). The first image will be set as primary.</div>
                                    <div id="image-preview" class="mt-2"></div>
                                </div>
                                
                                <div class="d-flex justify-content-end">
                                    <button type="reset" class="btn btn-secondary me-2">Reset</button>
                                    <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview functionality
        document.getElementById('imageUpload').addEventListener('change', function(e) {
            const previewContainer = document.getElementById('image-preview');
            previewContainer.innerHTML = '';
            
            const files = e.target.files;
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                
                if (file.type.match('image.*')) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'preview-image';
                        previewContainer.appendChild(img);
                    }
                    
                    reader.readAsDataURL(file);
                }
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>