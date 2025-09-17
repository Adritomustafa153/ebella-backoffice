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
        
        .product-row {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-color);
        }
        
        .search-results {
            position: absolute;
            background: white;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            border: 1px solid #ddd;
            border-radius: 0 0 5px 5px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            display: none;
        }
        
        .search-results .list-group-item {
            cursor: pointer;
            padding: 10px;
            border: none;
            border-bottom: 1px solid #eee;
        }
        
        .search-results .list-group-item:hover {
            background-color: #f8f9fa;
        }
        
        .search-results .list-group-item:last-child {
            border-bottom: none;
        }
        
        .total-display {
            background-color: var(--light-color);
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-weight: bold;
            border-left: 4px solid var(--accent-color);
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
    
    <?php include 'nav.php' ?>
    
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
                                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSaleModal">
                                            <i class="fas fa-plus me-1"></i> Add Sale
                                        </button>
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
    
    <!-- Add Sale Modal -->
    <div class="modal fade" id="addSaleModal" tabindex="-1" aria-labelledby="addSaleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background-color: var(--primary-color); color: white;">
                    <h5 class="modal-title" id="addSaleModalLabel">Add New Sale</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="saleForm" method="post" action="process_sale.php">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Customer Name</label>
                                    <input type="text" class="form-control" name="customer_name" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" name="phone" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Address</label>
                                    <input type="text" class="form-control" name="address" required>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h5 class="mb-3">Products</h5>
                        
                        <div id="products-container">
                            <!-- Product rows will be added here -->
                            <div class="product-row" data-row="0">
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="mb-3">
                                            <label class="form-label">Search Product (Name or ID)</label>
                                            <input type="text" class="form-control product-search" name="products[0][search]" placeholder="Search by product name or ID" autocomplete="off">
                                            <div class="search-results" id="search-results-0"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label class="form-label">Quantity</label>
                                            <input type="number" class="form-control quantity" name="products[0][quantity]" min="1" value="1" required>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label class="form-label">Unit Price</label>
                                            <input type="number" class="form-control unit-price" name="products[0][unit_price]" step="0.01" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label class="form-label">Total</label>
                                            <input type="text" class="form-control row-total" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        <div class="mb-3">
                                            <button type="button" class="btn btn-danger remove-row" style="display: none;">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" class="product-id" name="products[0][product_id]">
                                <input type="hidden" class="product-name" name="products[0][product_name]">
                                <input type="hidden" class="product-code" name="products[0][product_code]">
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <button type="button" id="add-product" class="btn btn-secondary">
                                    <i class="fas fa-plus me-1"></i> Add More Products
                                </button>
                            </div>
                        </div>
                        
                        <div class="total-display">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-2">Subtotal: <span id="subtotal">0.00</span></div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-2">Discount: 
                                        <input type="number" id="discount" name="discount" value="0" min="0" step="0.01" style="width: 80px; display: inline-block;" class="form-control form-control-sm">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-2">Total: <span id="total">0.00</span></div>
                                </div>
                                <div class="col-md-3 text-end">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-file-invoice me-1"></i> Generate Order Bill
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Handle edit modal data
            var editSaleModal = document.getElementById('editSaleModal');
            if (editSaleModal) {
                editSaleModal.addEventListener('show.bs.modal', function (event) {
                    var button = event.relatedTarget;
                    var id = button.getAttribute('data-id');
                    var invoiceNo = button.getAttribute('data-invoice');
                    
                    var modal = this;
                    modal.querySelector('#edit_sale_id').value = id;
                    modal.querySelector('#edit_invoice_no').value = invoiceNo;
                    modal.querySelector('#edit_sale_link').href = 'edit_sale.php?id=' + id;
                });
            }
            
            // Handle delete modal data
            var deleteSaleModal = document.getElementById('deleteSaleModal');
            if (deleteSaleModal) {
                deleteSaleModal.addEventListener('show.bs.modal', function (event) {
                    var button = event.relatedTarget;
                    var id = button.getAttribute('data-id');
                    var invoiceNo = button.getAttribute('data-invoice');
                    
                    var modal = this;
                    modal.querySelector('#delete_sale_id').value = id;
                    modal.querySelector('#delete_invoice_no').textContent = invoiceNo;
                });
            }
            
            // Add Sale Modal functionality
            let rowCount = 1;
            const productsContainer = document.getElementById('products-container');
            
            if (document.getElementById('add-product')) {
                // Add product row
                document.getElementById('add-product').addEventListener('click', function() {
                    const newRow = document.querySelector('.product-row').cloneNode(true);
                    newRow.setAttribute('data-row', rowCount);
                    
                    // Update all input names
                    const inputs = newRow.querySelectorAll('input, select');
                    inputs.forEach(input => {
                        if (input.name) {
                            input.name = input.name.replace('[0]', `[${rowCount}]`);
                        }
                    });
                    
                    // Clear values
                    newRow.querySelector('.product-search').value = '';
                    newRow.querySelector('.quantity').value = '1';
                    newRow.querySelector('.unit-price').value = '';
                    newRow.querySelector('.row-total').value = '';
                    newRow.querySelector('.product-id').value = '';
                    newRow.querySelector('.product-name').value = '';
                    newRow.querySelector('.product-code').value = '';
                    
                    // Show remove button
                    newRow.querySelector('.remove-row').style.display = 'block';
                    
                    // Update search results ID
                    newRow.querySelector('.search-results').id = `search-results-${rowCount}`;
                    
                    productsContainer.appendChild(newRow);
                    
                    // Add event listeners to new row
                    addRowEventListeners(newRow, rowCount);
                    
                    rowCount++;
                });
                
                // Add event listeners to initial row
                addRowEventListeners(document.querySelector('.product-row'), 0);
                
                // Calculate totals when discount changes
                if (document.getElementById('discount')) {
                    document.getElementById('discount').addEventListener('input', calculateTotals);
                }
                
                // Form submission
                if (document.getElementById('saleForm')) {
                    document.getElementById('saleForm').addEventListener('submit', function(e) {
                        // Validate at least one product is added
                        const productIds = document.querySelectorAll('.product-id');
                        let hasProduct = false;
                        
                        productIds.forEach(input => {
                            if (input.value) hasProduct = true;
                        });
                        
                        if (!hasProduct) {
                            e.preventDefault();
                            alert('Please add at least one product to the sale.');
                            return false;
                        }
                    });
                }
            }
            
            function addRowEventListeners(row, rowIndex) {
                const searchInput = row.querySelector('.product-search');
                const searchResults = row.querySelector('.search-results');
                const quantityInput = row.querySelector('.quantity');
                const priceInput = row.querySelector('.unit-price');
                const totalInput = row.querySelector('.row-total');
                const productIdInput = row.querySelector('.product-id');
                const productNameInput = row.querySelector('.product-name');
                const productCodeInput = row.querySelector('.product-code');
                const removeBtn = row.querySelector('.remove-row');
                
                // Product search functionality
                if (searchInput) {
                    searchInput.addEventListener('input', function() {
                        const query = this.value.trim();
                        
                        if (query.length < 2) {
                            if (searchResults) searchResults.style.display = 'none';
                            return;
                        }
                        
                        // AJAX request to search products
                        fetch('search_products.php?q=' + encodeURIComponent(query))
                            .then(response => response.json())
                            .then(products => {
                                if (searchResults) {
                                    searchResults.innerHTML = '';
                                    
                                    if (products.length === 0) {
                                        searchResults.innerHTML = '<div class="list-group-item">No products found</div>';
                                    } else {
                                        products.forEach(product => {
                                            const item = document.createElement('div');
                                            item.className = 'list-group-item';
                                            item.innerHTML = `
                                                <div><strong>${product.name}</strong> (ID: ${product.id})</div>
                                                <div>Code: ${product.code} | Category: ${product.category}</div>
                                                <div>Price: $${product.price} | Stock: ${product.stock}</div>
                                            `;
                                            item.dataset.id = product.id;
                                            item.dataset.name = product.name;
                                            item.dataset.code = product.code;
                                            item.dataset.price = product.price;
                                            
                                            item.addEventListener('click', function() {
                                                searchInput.value = product.name;
                                                if (productIdInput) productIdInput.value = product.id;
                                                if (productNameInput) productNameInput.value = product.name;
                                                if (productCodeInput) productCodeInput.value = product.code;
                                                if (priceInput) priceInput.value = product.price;
                                                if (searchResults) searchResults.style.display = 'none';
                                                calculateRowTotal(row);
                                                calculateTotals();
                                            });
                                            
                                            searchResults.appendChild(item);
                                        });
                                    }
                                    
                                    searchResults.style.display = 'block';
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                            });
                    });
                }
                
                // Hide search results when clicking outside
                document.addEventListener('click', function(e) {
                    if (searchInput && searchResults && !searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                        searchResults.style.display = 'none';
                    }
                });
                
                // Calculate row total when quantity or price changes
                if (quantityInput) {
                    quantityInput.addEventListener('input', function() {
                        calculateRowTotal(row);
                        calculateTotals();
                    });
                }
                
                if (priceInput) {
                    priceInput.addEventListener('input', function() {
                        calculateRowTotal(row);
                        calculateTotals();
                    });
                }
                
                // Remove row
                if (removeBtn) {
                    removeBtn.addEventListener('click', function() {
                        row.remove();
                        calculateTotals();
                    });
                }
            }
            
            function calculateRowTotal(row) {
                const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
                const price = parseFloat(row.querySelector('.unit-price').value) || 0;
                const total = quantity * price;
                
                row.querySelector('.row-total').value = total.toFixed(2);
            }
            
            function calculateTotals() {
                const rowTotals = document.querySelectorAll('.row-total');
                let subtotal = 0;
                
                rowTotals.forEach(input => {
                    subtotal += parseFloat(input.value) || 0;
                });
                
                const discount = parseFloat(document.getElementById('discount').value) || 0;
                const total = subtotal - discount;
                
                document.getElementById('subtotal').textContent = subtotal.toFixed(2);
                document.getElementById('total').textContent = total.toFixed(2);
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>