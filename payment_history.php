<?php
include 'auth_check.php';
include 'db.php';

$sale_id = $_GET['id'] ?? 0;

// Fetch sale details
$sale_sql = "SELECT * FROM sales WHERE SaleID = ?";
$stmt = $conn->prepare($sale_sql);
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$sale = $stmt->get_result()->fetch_assoc();

// Fetch payment history
$payments_sql = "SELECT * FROM payments WHERE SaleID = ? ORDER BY PaymentDate DESC";
$stmt = $conn->prepare($payments_sql);
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$payments = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container { max-width: 1000px; margin-top: 30px; }
        .card { box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); border: none; border-radius: 10px; }
        .card-header { background: linear-gradient(135deg, #17a2b8, #2c3e50); color: white; border-radius: 10px 10px 0 0 !important; }
        .badge-paid { background-color: #28a745; }
        .badge-partial { background-color: #ffc107; color: #000; }
        .badge-refund { background-color: #dc3545; }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-history me-2"></i>Payment History</h4>
            </div>
            <div class="card-body">
                <?php if ($sale): ?>
                    <!-- Sale Information -->
                    <div class="alert alert-info">
                        <h5>Sale Details</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Passenger:</strong> <?php echo htmlspecialchars($sale['PassengerName']); ?></p>
                                <p><strong>PNR:</strong> <?php echo htmlspecialchars($sale['PNR']); ?></p>
                                <p><strong>Ticket No:</strong> <?php echo htmlspecialchars($sale['TicketNumber']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Bill Amount:</strong> ৳<?php echo number_format($sale['BillAmount'], 2); ?></p>
                                <p><strong>Paid Amount:</strong> ৳<?php echo number_format($sale['PaidAmount'], 2); ?></p>
                                <p><strong>Due Amount:</strong> ৳<?php echo number_format($sale['DueAmount'], 2); ?></p>
                                <p><strong>Status:</strong> 
                                    <span class="badge bg-<?php echo $sale['PaymentStatus'] == 'Paid' ? 'success' : ($sale['PaymentStatus'] == 'Partially Paid' ? 'warning' : 'danger'); ?>">
                                        <?php echo $sale['PaymentStatus']; ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment History Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Payment Date</th>
                                    <th>Amount</th>
                                    <th>Type</th>
                                    <th>Method</th>
                                    <th>Bank</th>
                                    <th>Notes</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($payments->num_rows > 0): ?>
                                    <?php while($payment = $payments->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($payment['PaymentDate']); ?></td>
                                            <td><strong>৳<?php echo number_format($payment['Amount'], 2); ?></strong></td>
                                            <td>
                                                <span class="badge badge-<?php echo strtolower($payment['PaymentType']); ?>">
                                                    <?php echo $payment['PaymentType']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($payment['PaymentMethod']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['BankName']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['Notes']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($payment['CreatedAt'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">No payment records found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                        <a href="receiveable.php" class="btn btn-secondary me-md-2">
                            <i class="fas fa-arrow-left me-1"></i> Back to List
                        </a>
                        <a href="edit_receivable.php?id=<?php echo $sale_id; ?>" class="btn btn-primary">
                            <i class="fas fa-money-bill-wave me-1"></i> Add Payment
                        </a>
                    </div>
                    
                <?php else: ?>
                    <div class="alert alert-danger">Sale record not found!</div>
                    <a href="receiveable.php" class="btn btn-secondary">Back to List</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>