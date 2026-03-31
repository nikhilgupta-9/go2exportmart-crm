<?php
session_start();
include 'partials/_dbconnect.php';
include 'partials/_header.php';

if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$grade_level = $_SESSION['grade_level'];
$user_name = $_SESSION['user_name'];

// Fetch user info
$user_info = $conn->query("SELECT * FROM employees WHERE user_id = '$user_id'")->fetch_assoc();

// Get customer number from URL
$customer_number = isset($_GET['no']) ? mysqli_real_escape_string($conn, $_GET['no']) : '';
$customer_id = isset($_GET['customerID']) ? mysqli_real_escape_string($conn, $_GET['customerID']) : '';

if (empty($customer_number) && !empty($customer_id)) {
    // Get customer number from ID
    $cust_sql = "SELECT customer_num FROM customerleads WHERE sno = '$customer_id' LIMIT 1";
    $cust_result = $conn->query($cust_sql);
    if ($cust_data = $cust_result->fetch_assoc()) {
        $customer_number = $cust_data['customer_num'];
    }
}

if (empty($customer_number)) {
    $_SESSION['error_message'] = "Customer not found!";
    header('location: matelize.php');
    exit();
}

// Fetch customer basic information
$customer_sql = "SELECT * FROM customerleads WHERE customer_num = '$customer_number' ORDER BY sno DESC LIMIT 1";
$customer_result = $conn->query($customer_sql);
$customer = $customer_result->fetch_assoc();

if (!$customer) {
    $_SESSION['error_message'] = "Customer not found!";
    header('location: matelize.php');
    exit();
}

// Fetch all payment history for this customer (all matelized entries)
$history_sql = "SELECT * FROM customerleads 
                WHERE customer_num = '$customer_number' AND matelize = '1' 
                ORDER BY todaydate DESC, sno DESC";
$history_result = $conn->query($history_sql);

// Calculate totals
$total_amount = 0;
$total_paid = 0;
$payment_records = [];

while ($row = $history_result->fetch_assoc()) {
    $amount = floatval($row['amount']);
    $total_amount += $amount;
    $total_paid += $amount;
    
    // Get agent name
    $agent_name = $row['assigned_to'];
    $agent_sql = "SELECT user_name FROM employees WHERE user_id = '{$row['assigned_to']}'";
    $agent_result = $conn->query($agent_sql);
    if ($agent_data = $agent_result->fetch_assoc()) {
        $agent_name = $agent_data['user_name'];
    }
    
    $payment_records[] = [
        'sno' => $row['sno'],
        'todaydate' => $row['todaydate'],
        'amount' => $amount,
        'pay_mode' => $row['pay_mode'],
        'service' => $row['service'],
        'assigned_to' => $agent_name,
        'transaction' => $row['transaction'],
        'invoice' => $row['invoice'],
        'status' => $row['status'],
        'discount' => $row['discount']
    ];
}

// Get balance from latest record
$current_balance = floatval($customer['bal_amt']);

// Display messages
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Payment History | GO2EXPORT MART</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once "includes/link.php"; ?>
    
    <style>
        .customer-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 13px;
            margin-bottom: 0;
        }
        
        .payment-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .customer-avatar {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            font-weight: bold;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 12px;
            padding: 5px 0;
        }
        
        .info-label {
            width: 120px;
            font-weight: 600;
            color: #666;
            font-size: 13px;
        }
        
        .info-value {
            flex: 1;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .payment-method-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .method-cash { background: #d4edda; color: #155724; }
        .method-bank { background: #cfe2ff; color: #084298; }
        .method-upi { background: #d1ecf1; color: #0c5460; }
        .method-card { background: #f8d7da; color: #721c24; }
        .method-cheque { background: #fff3cd; color: #856404; }
        
        .print-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 100;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            .page-wrapper {
                margin: 0;
                padding: 0;
            }
            .stat-card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <?php include_once "includes/header.php"; ?>
        <?php include_once "includes/sidebar.php"; ?>

        <div class="page-wrapper">
            <div class="content pb-0">
                <!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-1">Payment History</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="matelize.php">Matelized</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Payment History</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap no-print">
                        <a href="matelize.php" class="btn btn-outline-secondary">
                            <i class="ti ti-arrow-left me-1"></i> Back
                        </a>
                        <button onclick="window.print()" class="btn btn-primary">
                            <i class="ti ti-printer me-1"></i> Print
                        </button>
                    </div>
                </div>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="ti ti-check-circle me-2"></i> <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="ti ti-alert-circle me-2"></i> <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Customer Information Card -->
                <div class="customer-card">
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center text-md-start mb-3 mb-md-0">
                            <div class="customer-avatar mx-auto mx-md-0">
                                <?php echo strtoupper(substr($customer['customer_name'], 0, 2)); ?>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <h4 class="mb-2"><?php echo htmlspecialchars($customer['customer_name']); ?></h4>
                            <div class="d-flex flex-wrap gap-3">
                                <span><i class="ti ti-phone"></i> <?php echo htmlspecialchars($customer['customer_num']); ?></span>
                                <?php if (!empty($customer['alt_number'])): ?>
                                    <span><i class="ti ti-phone-call"></i> <?php echo htmlspecialchars($customer['alt_number']); ?></span>
                                <?php endif; ?>
                                <span><i class="ti ti-mail"></i> <?php echo htmlspecialchars($customer['cust_mail']); ?></span>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="row">
                                <div class="col-6">
                                    <div class="info-row">
                                        <div class="info-label">Company:</div>
                                        <div class="info-value"><?php echo htmlspecialchars($customer['cust_company']); ?></div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="info-row">
                                        <div class="info-label">GST:</div>
                                        <div class="info-value"><?php echo htmlspecialchars($customer['GST']); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value">₹<?php echo number_format($total_amount, 2); ?></div>
                                    <p class="stat-label">Total Amount</p>
                                </div>
                                <div class="stat-icon bg-primary bg-opacity-10">
                                    <i class="ti ti-currency-rupee text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value">₹<?php echo number_format($total_paid, 2); ?></div>
                                    <p class="stat-label">Total Paid</p>
                                </div>
                                <div class="stat-icon bg-success bg-opacity-10">
                                    <i class="ti ti-wallet text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value">₹<?php echo number_format($current_balance, 2); ?></div>
                                    <p class="stat-label">Balance Amount</p>
                                </div>
                                <div class="stat-icon bg-warning bg-opacity-10">
                                    <i class="ti ti-clock text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $history_result->num_rows; ?></div>
                                    <p class="stat-label">Total Transactions</p>
                                </div>
                                <div class="stat-icon bg-info bg-opacity-10">
                                    <i class="ti ti-receipt text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment History Table -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-bottom">
                        <h5 class="mb-0">
                            <i class="ti ti-history me-2"></i> Transaction History
                        </h5>
                        <small class="text-muted">Complete payment history for this customer</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="historyTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Invoice/Ref No</th>
                                        <th>Service</th>
                                        <th>Amount (₹)</th>
                                        <th>Discount (₹)</th>
                                        <th>Payment Mode</th>
                                        <th>Transaction ID</th>
                                        <th>Agent</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($payment_records)): ?>
                                        <?php foreach ($payment_records as $record): 
                                            $method_class = '';
                                            switch(strtolower($record['pay_mode'])) {
                                                case 'cash': $method_class = 'method-cash'; break;
                                                case 'bank transfer': $method_class = 'method-bank'; break;
                                                case 'upi': $method_class = 'method-upi'; break;
                                                case 'card': $method_class = 'method-card'; break;
                                                case 'cheque': $method_class = 'method-cheque'; break;
                                                default: $method_class = 'method-cash';
                                            }
                                        ?>
                                            <tr>
                                                <td>
                                                    <i class="ti ti-calendar text-muted me-1"></i>
                                                    <?php echo date('d M Y', strtotime($record['todaydate'])); ?>
                                                    <br><small class="text-muted"><?php echo date('h:i A', strtotime($record['todaydate'])); ?></small>
                                                </td>
                                                <td>
                                                    <span class="fw-semibold">#INV-<?php echo str_pad($record['sno'], 6, '0', STR_PAD_LEFT); ?></span>
                                                    <?php if (!empty($record['invoice'])): ?>
                                                        <br><small class="text-muted">Ref: <?php echo htmlspecialchars($record['invoice']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($record['service'] ?? 'N/A'); ?></td>
                                                <td class="fw-semibold text-success">₹<?php echo number_format($record['amount'], 2); ?></td>
                                                <td class="text-muted">
                                                    <?php echo !empty($record['discount']) ? '₹' . number_format($record['discount'], 2) : '-'; ?>
                                                </td>
                                                <td>
                                                    <span class="payment-method-badge <?php echo $method_class; ?>">
                                                        <i class="ti ti-<?php echo strtolower($record['pay_mode']) == 'cash' ? 'cash' : (strtolower($record['pay_mode']) == 'upi' ? 'qrcode' : 'credit-card'); ?> me-1"></i>
                                                        <?php echo htmlspecialchars($record['pay_mode'] ?? 'N/A'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($record['transaction'])): ?>
                                                        <code><?php echo htmlspecialchars($record['transaction']); ?></code>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-sm rounded-circle bg-primary bg-opacity-10 me-2" style="width: 28px; height: 28px;">
                                                            <span class="text-primary" style="font-size: 12px;"><?php echo substr($record['assigned_to'], 0, 2); ?></span>
                                                        </div>
                                                        <?php echo htmlspecialchars($record['assigned_to']); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="payment-status <?php echo $record['status'] == 'Committed' ? 'status-completed' : 'status-pending'; ?>">
                                                        <?php echo htmlspecialchars($record['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-5">
                                                <i class="ti ti-receipt-off fs-48 text-muted"></i>
                                                <h5 class="mt-3">No Payment History Found</h5>
                                                <p class="text-muted">No transactions recorded for this customer yet</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr class="fw-bold">
                                        <td colspan="3" class="text-end">Total:</td>
                                        <td>₹<?php echo number_format($total_amount, 2); ?></td>
                                        <td>-</td>
                                        <td colspan="4"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons (if balance pending) -->
                <?php if ($current_balance > 0 && $grade_level < 4): ?>
                <div class="text-center mt-4 no-print">
                    <button class="btn btn-success btn-lg" onclick="makePayment()">
                        <i class="ti ti-coin me-2"></i> Make Payment (₹<?php echo number_format($current_balance, 2); ?>)
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Make Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="matelize.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="customer_id" id="payment_customer_id" value="<?php echo $customer['sno']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Customer Name</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($customer['customer_name']); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Balance Amount</label>
                            <input type="text" class="form-control" value="₹<?php echo number_format($current_balance, 2); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="amount" id="payment_amount" required step="0.01" min="1" max="<?php echo $current_balance; ?>">
                            <small class="text-muted">Enter amount to pay (Max: ₹<?php echo number_format($current_balance, 2); ?>)</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                            <select class="form-select" name="payment_mode" required>
                                <option value="">Select Payment Mode</option>
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="UPI">UPI</option>
                                <option value="Card">Card</option>
                                <option value="Cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Transaction ID</label>
                            <input type="text" class="form-control" name="transaction_id" placeholder="Enter transaction reference">
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="fullPaymentCheck">
                            <label class="form-check-label" for="fullPaymentCheck">
                                Pay Full Balance (₹<?php echo number_format($current_balance, 2); ?>)
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="make_payment" class="btn btn-primary">Make Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include_once "includes/footer-link.php"; ?>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            if ($('#historyTable').length) {
                $('#historyTable').DataTable({
                    pageLength: 10,
                    order: [[0, 'desc']],
                    language: {
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ transactions",
                        infoEmpty: "No transactions found",
                        emptyTable: "No payment history available"
                    }
                });
            }
        });
        
        function makePayment() {
            $('#paymentModal').modal('show');
        }
        
        // Full payment checkbox
        $('#fullPaymentCheck').on('change', function() {
            if ($(this).is(':checked')) {
                $('#payment_amount').val(<?php echo $current_balance; ?>);
                $('#payment_amount').prop('readonly', true);
            } else {
                $('#payment_amount').val('');
                $('#payment_amount').prop('readonly', false);
            }
        });
        
        // Validate payment amount
        document.getElementById('payment_amount')?.addEventListener('input', function(e) {
            let value = parseFloat(this.value);
            let max = parseFloat(this.getAttribute('max'));
            if (value > max) {
                this.value = max;
                alert('Payment amount cannot exceed balance amount!');
            }
            if (value < 0) {
                this.value = 0;
            }
        });
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
    
    <style>
        .avatar-sm {
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.375rem 0.75rem;
            margin: 0 2px;
            border-radius: 8px;
        }
        
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 6px 12px;
        }
        
        .table > :not(caption) > * > * {
            padding: 12px 15px;
            vertical-align: middle;
        }
        
        .customer-avatar {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            font-weight: bold;
        }
    </style>
</body>
</html>