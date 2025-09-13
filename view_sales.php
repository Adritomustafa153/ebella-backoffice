<?php
require_once 'auth_checker.php';
$page_title = "Ebella Management - Sales Records";
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
        
        .filter-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }
        
        .filter-card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px;
        }
        
        .sales-table th {
            background-color: var(--primary-color);
            color: white;
        }
        
        .action-buttons .btn {
            margin-left: 5px;
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
<?php include "sidebar.php" ?>
    
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
                        <a class="nav-link" href="#">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="sales.php">Sales</a>
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
            <h2 class="mb-4">Sales Records</h2>
            
            <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Filter Section -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card filter-card">
                        <div class="filter-card-header">
                            <h5 class="mb-0">Filter Sales</h5>
                        </div>
                        <div class="card-body">
                            <form method="get" action="">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Search (Sale ID/Customer/Phone)</label>
                                            <input type="text" class="form-control" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label class="form-label">From Date</label>
                                            <input type="date" class="form-control" name="from_date" value="<?php echo isset($_GET['from_date']) ? htmlspecialchars($_GET['from_date']) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label class="form-label">To Date</label>
                                            <input type="date" class="form-control" name="to_date" value="<?php echo isset($_GET['to_date']) ? htmlspecialchars($_GET['to_date']) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Sales Person</label>
                                            <select class="form-select" name="sales_person">
                                                <option value="">All Sales Persons</option>
                                                <?php
                                                $sales_persons_sql = "SELECT user_id, username FROM users WHERE user_role = 'sales'";
                                                $sales_persons_result = $conn->query($sales_persons_sql);
                                                if ($sales_persons_result->num_rows > 0) {
                                                    while($person = $sales_persons_result->fetch_assoc()) {
                                                        $selected = (isset($_GET['sales_person']) && $_GET['sales_person'] == $person['user_id']) ? 'selected' : '';
                                                        echo "<option value='{$person['user_id']}' $selected>{$person['username']}</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <div class="mb-3 w-100">
                                            <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-2">
                                        <a href="sales.php" class="btn btn-secondary">Clear Filters</a>
                                    </div>
                                    <div class="col-md-2 ms-auto">
                                        <a href="add_sale.php" class="btn btn-success"><i class="fas fa-plus me-1"></i> Add Sale</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sales Table -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header" style="background-color: var(--primary-color); color: white;">
                            <h5 class="mb-0">Sales List</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover sales-table">
                                    <thead>
                                        <tr>
                                            <th>Sale ID</th>
                                            <th>Invoice No</th>
                                            <th>Date</th>
                                            <th>Customer</th>
                                            <th>Total Items</th>
                                            <th>Total Price</th>
                                            <th>Total Profit</th>
                                            <th>Discount</th>
                                            <th>Sales Person</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Build query with filters
                                        $sql = "SELECT s.sale_id, s.invoice_no, s.sale_date, s.total_price, s.total_profit, 
                                                s.total_discount, s.total_items, 
                                                c.username as customer_name, 
                                                sp.username as sales_person_name
                                                FROM sales s
                                                LEFT JOIN users c ON s.customer_id = c.user_id
                                                LEFT JOIN users sp ON s.sales_person_id = sp.user_id
                                                WHERE 1=1";
                                        
                                        $params = [];
                                        $types = "";
                                        
                                        if (isset($_GET['search']) && !empty($_GET['search'])) {
                                            $search = "%{$_GET['search']}%";
                                            $sql .= " AND (s.sale_id LIKE ? OR c.username LIKE ? OR c.phone LIKE ?)";
                                            array_push($params, $search, $search, $search);
                                            $types .= "sss";
                                        }
                                        
                                        if (isset($_GET['from_date']) && !empty($_GET['from_date'])) {
                                            $sql .= " AND s.sale_date >= ?";
                                            array_push($params, $_GET['from_date']);
                                            $types .= "s";
                                        }
                                        
                                        if (isset($_GET['to_date']) && !empty($_GET['to_date'])) {
                                            $sql .= " AND s.sale_date <= ?";
                                            array_push($params, $_GET['to_date'] . ' 23:59:59');
                                            $types .= "s";
                                        }
                                        
                                        if (isset($_GET['sales_person']) && !empty($_GET['sales_person'])) {
                                            $sql .= " AND s.sales_person_id = ?";
                                            array_push($params, $_GET['sales_person']);
                                            $types .= "i";
                                        }
                                        
                                        $sql .= " ORDER BY s.sale_date DESC";
                                        
                                        // Prepare and execute query
                                        $stmt = $conn->prepare($sql);
                                        
                                        if (!empty($params)) {
                                            $stmt->bind_param($types, ...$params);
                                        }
                                        
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        
                                        if ($result->num_rows > 0) {
                                            while($sale = $result->fetch_assoc()):
                                        ?>
                                        <tr>
                                            <td><?php echo $sale['sale_id']; ?></td>
                                            <td><?php echo htmlspecialchars($sale['invoice_no']); ?></td>
                                            <td><?php echo date('M j, Y h:i A', strtotime($sale['sale_date'])); ?></td>
                                            <td><?php echo isset($sale['customer_name']) ? htmlspecialchars($sale['customer_name']) : 'N/A'; ?></td>
                                            <td><?php echo $sale['total_items']; ?></td>
                                            <td><?php echo number_format($sale['total_price'], 2); ?></td>
                                            <td><?php echo number_format($sale['total_profit'], 2); ?></td>
                                            <td><?php echo number_format($sale['total_discount'], 2); ?></td>
                                            <td><?php echo isset($sale['sales_person_name']) ? htmlspecialchars($sale['sales_person_name']) : 'N/A'; ?></td>
                                            <td class="action-buttons">
                                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editSaleModal" 
                                                    data-id="<?php echo $sale['sale_id']; ?>"
                                                    data-invoice="<?php echo htmlspecialchars($sale['invoice_no']); ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteSaleModal" 
                                                    data-id="<?php echo $sale['sale_id']; ?>"
                                                    data-invoice="<?php echo htmlspecialchars($sale['invoice_no']); ?>">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                                <a href="generate_invoice.php?id=<?php echo $sale['sale_id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-file-invoice"></i> Invoice
                                                </a>
                                            </td>
                                        </tr>
                                        <?php
                                            endwhile;
                                        } else {
                                            echo "<tr><td colspan='10' class='text-center'>No sales records found</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Sale Modal -->
    <div class="modal fade" id="editSaleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Sale</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" action="edit_sale.php">
                    <div class="modal-body">
                        <input type="hidden" id="edit_sale_id" name="sale_id">
                        <div class="mb-3">
                            <label class="form-label">Invoice Number</label>
                            <input type="text" class="form-control" id="edit_invoice_no" name="invoice_no" required>
                        </div>
                        <!-- Additional fields would go here -->
                        <p class="text-muted">Note: More editing options available on the detailed edit page.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <a href="#" id="edit_sale_link" class="btn btn-primary">Go to Edit Page</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Sale Modal -->
    <div class="modal fade" id="deleteSaleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" action="delete_sale.php">
                    <div class="modal-body">
                        <input type="hidden" id="delete_sale_id" name="sale_id">
                        <p>Are you sure you want to delete sale with invoice number: <strong id="delete_invoice_no"></strong>?</p>
                        <p class="text-danger">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_sale" class="btn btn-danger">Delete Sale</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle edit modal data
        var editSaleModal = document.getElementById('editSaleModal');
        editSaleModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var invoiceNo = button.getAttribute('data-invoice');
            
            var modal = this;
            modal.querySelector('#edit_sale_id').value = id;
            modal.querySelector('#edit_invoice_no').value = invoiceNo;
            modal.querySelector('#edit_sale_link').href = 'edit_sale.php?id=' + id;
        });
        
        // Handle delete modal data
        var deleteSaleModal = document.getElementById('deleteSaleModal');
        deleteSaleModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var invoiceNo = button.getAttribute('data-invoice');
            
            var modal = this;
            modal.querySelector('#delete_sale_id').value = id;
            modal.querySelector('#delete_invoice_no').textContent = invoiceNo;
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>