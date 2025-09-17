<?php
// sidebar.php - Left side menu for Ebella Management System
?>
<div class="sidebar" style="width: 250px;">
    <div class="text-center mb-4">
        <h4 class="brand-logo"><img src="logo.png" alt="Ebella" style="height: 100px; margin-right: 5px; border-radius: 50%;"></h4>
        <span class="me-3"><?php echo $_SESSION["username"]; ?></span><br>
        <span class="me-3"><?php echo $_SESSION["user_role"]; ?></span>
    </div>
    <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-home me-2"></i> Dashboard</a>
    <a href="view_users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'view_users.php' ? 'active' : ''; ?>"><i class="fas fa-users me-2"></i> Users</a>
    <a href="view_products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'view_products.php' ? 'active' : ''; ?>"><i class="fas fa-shopping-bag me-2"></i> Products</a>
    <a href="add_products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'add_product.php' ? 'active' : ''; ?>"><i class="fas fa-plus-circle me-2"></i> Add Product</a>
    <a href="view_sales.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'view_sales.php' ? 'active' : ''; ?>"><i class="fas fa-chart-line me-2"></i> Sales</a>
    <a href="#"><i class="fas fa-shopping-cart me-2"></i> Orders</a>
    <a href="#"><i class="fas fa-chart-bar me-2"></i> Analytics</a>
    <a href="#"><i class="fas fa-cog me-2"></i> Settings</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
</div>

