<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
    exit();
}

include 'partials/_dbconnect.php';
include 'partials/_header.php';

$user_id = $_SESSION['user_id'];
$grade_level = $_SESSION['grade_level'];
$user_name = $_SESSION['user_name'];

// Fetch user info
$user_info = $conn->query("SELECT * FROM employees WHERE user_id = '$user_id'")->fetch_assoc();

// Handle Part Payment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['make_payment'])) {
    $customer_id = $_POST['customer_id'];
    $amount = floatval($_POST['amount']);
    $payment_mode = mysqli_real_escape_string($conn, $_POST['payment_mode']);
    $transaction_id = mysqli_real_escape_string($conn, $_POST['transaction_id'] ?? '');
    
    // Get current customer details
    $customer_sql = "SELECT * FROM customerleads WHERE sno = '$customer_id'";
    $customer_result = $conn->query($customer_sql);
    $customer = $customer_result->fetch_assoc();
    
    $current_balance = floatval($customer['bal_amt']);
    $new_balance = $current_balance - $amount;
    
    // Update the customer's balance
    $update_sql = "UPDATE customerleads SET 
                   bal_amt = '$new_balance',
                   pay_mode = '$payment_mode',
                   transaction = '$transaction_id'
                   WHERE sno = '$customer_id'";
    
    if ($conn->query($update_sql)) {
        $_SESSION['success_message'] = "Payment of ₹" . number_format($amount) . " recorded successfully! New balance: ₹" . number_format($new_balance);
        
        // Optional: Insert into payment_log if table exists (check first)
        $check_table = $conn->query("SHOW TABLES LIKE 'payment_log'");
        if ($check_table->num_rows > 0) {
            $log_sql = "INSERT INTO payment_log (customer_id, amount, payment_mode, transaction_id, created_by, created_at) 
                        VALUES ('$customer_id', '$amount', '$payment_mode', '$transaction_id', '$user_id', NOW())";
            $conn->query($log_sql);
        }
    } else {
        $_SESSION['error_message'] = "Error processing payment: " . $conn->error;
    }
    
    header('location: matelize.php');
    exit();
}

// Handle Full Payment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['full_payment'])) {
    $customer_id = $_POST['customer_id'];
    
    $update_sql = "UPDATE customerleads SET bal_amt = '0' WHERE sno = '$customer_id'";
    if ($conn->query($update_sql)) {
        $_SESSION['success_message'] = "Payment completed successfully!";
    } else {
        $_SESSION['error_message'] = "Error processing payment: " . $conn->error;
    }
    
    header('location: matelize.php');
    exit();
}

// Query based on user level
if ($grade_level == 4) {
    $matelize_sql = "SELECT DISTINCT customer_num FROM customerleads WHERE matelize = '1' AND assigned_to = '$user_id'";
} else if ($grade_level == 3) {
    $matelize_sql = "SELECT DISTINCT customer_num FROM customerleads WHERE matelize = '1' AND reporting = '$user_id'";
} else {
    $matelize_sql = "SELECT DISTINCT customer_num FROM customerleads WHERE matelize = '1'";
}

$matelize_result = $conn->query($matelize_sql);

// Calculate statistics
$total_customers = $matelize_result->num_rows;
$total_revenue = 0;

$customers_data = [];
while ($row = $matelize_result->fetch_assoc()) {
    $number = $row['customer_num'];
    
    // Get total paid amount for this customer
    $amount_sql = "SELECT SUM(amount) as total FROM customerleads WHERE customer_num = '$number' AND matelize = '1'";
    $amount_result = $conn->query($amount_sql);
    $amount_data = $amount_result->fetch_assoc();
    $paid_amount = $amount_data['total'] ?? 0;
    $total_revenue += $paid_amount;
    
    // Get customer details
    $cust_sql = "SELECT * FROM customerleads WHERE customer_num = '$number' ORDER BY sno DESC LIMIT 1";
    $cust_result = $conn->query($cust_sql);
    $customer = $cust_result->fetch_assoc();
    
    if ($customer) {
        $customers_data[] = [
            'sno' => $customer['sno'],
            'customer_num' => $customer['customer_num'],
            'alt_number' => $customer['alt_number'],
            'customer_name' => $customer['customer_name'],
            'cust_company' => $customer['cust_company'],
            'website' => $customer['website'],
            'cust_address' => $customer['cust_address'],
            'cust_state' => $customer['cust_state'],
            'GST' => $customer['GST'],
            'amount' => $customer['amount'],
            'discount' => $customer['discount'],
            'bal_amt' => $customer['bal_amt'],
            'paid_amount' => $paid_amount,
            'Aadhar' => $customer['Aadhar']
        ];
    }
}

// Display messages from session
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
    <title>Matelized Customers | GO2EXPORT MART</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once "includes/link.php"; ?>
    
    <style>
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 0;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-matelized {
            background: #d4edda;
            color: #155724;
        }
        
        .payment-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .payment-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .payment-partial {
            background: #cfe2ff;
            color: #084298;
        }
        
        .payment-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .action-buttons .btn {
            margin: 0 2px;
            padding: 4px 8px;
        }
        
        .modal-content {
            border-radius: 16px;
        }
        
        .modal-header {
            border-bottom: 1px solid #eef2f6;
            padding: 20px;
        }
        
        .modal-footer {
            border-top: 1px solid #eef2f6;
            padding: 20px;
        }
        
        .avatar-sm {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        @media (max-width: 768px) {
            .stat-card {
                margin-bottom: 15px;
            }
            .action-buttons {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
            }
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
                        <h4 class="mb-1">Matelized Customers</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Matelized</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
                        <div id="reportrange" class="reportrange-picker d-flex align-items-center shadow">
                            <i class="ti ti-calendar-due text-dark fs-14 me-1"></i>
                            <span class="reportrange-picker-field"><?php echo date('d M Y'); ?></span>
                        </div>
                        <button onclick="window.location.reload()" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" title="Refresh">
                            <i class="ti ti-refresh"></i>
                        </button>
                        <button onclick="window.print()" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" title="Print">
                            <i class="ti ti-printer"></i>
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

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $total_customers; ?></div>
                                    <p class="stat-label">Total Matelized Customers</p>
                                </div>
                                <div class="stat-icon bg-primary bg-opacity-10">
                                    <i class="ti ti-star text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value">₹<?php echo number_format($total_revenue); ?></div>
                                    <p class="stat-label">Total Revenue</p>
                                </div>
                                <div class="stat-icon bg-success bg-opacity-10">
                                    <i class="ti ti-currency-rupee text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $total_customers; ?></div>
                                    <p class="stat-label">Total Deals</p>
                                </div>
                                <div class="stat-icon bg-info bg-opacity-10">
                                    <i class="ti ti-chart-arcs text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Matelized Customers Table -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h5 class="mb-0">Matelized Customers List</h5>
                            <small class="text-muted">Customers who have been matelized and their payment status</small>
                        </div>
                        <div class="d-flex gap-2 mt-2 mt-sm-0">
                            <div class="input-group input-group-sm" style="width: 250px;">
                                <span class="input-group-text"><i class="ti ti-search"></i></span>
                                <input type="text" id="tableSearch" class="form-control" placeholder="Search customers...">
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="matelizeTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>S No.</th>
                                        <th>Customer No.</th>
                                        <th>Customer Name</th>
                                        <th>Company</th>
                                        <th>Location</th>
                                        <th>Total Amount</th>
                                        <th>Paid Amount</th>
                                        <th>Balance</th>
                                        <th>Payment Status</th>
                                        <th class="no-sort">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($customers_data)): ?>
                                        <?php
                                        $sno = 1; 
                                        foreach ($customers_data as $customer): 
                                            $balance = floatval($customer['bal_amt']);
                                            $paid = floatval($customer['paid_amount']);
                                            $total = floatval($customer['amount']);
                                            $payment_status = '';
                                            if ($balance <= 0) {
                                                $payment_status = 'completed';
                                                $status_text = 'Completed';
                                                $status_class = 'payment-completed';
                                            } elseif ($paid > 0 && $balance > 0) {
                                                $payment_status = 'partial';
                                                $status_text = 'Partial';
                                                $status_class = 'payment-partial';
                                            } else {
                                                $payment_status = 'pending';
                                                $status_text = 'Pending';
                                                $status_class = 'payment-pending';
                                            }
                                        ?>
                                            <tr>
                                                <td><?= $sno++ ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($customer['customer_num']); ?></strong>
                                                    <?php if (!empty($customer['alt_number'])): ?>
                                                        <br><small class="text-muted">Alt: <?php echo htmlspecialchars($customer['alt_number']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm bg-primary bg-opacity-10 me-2">
                                                            <span class="text-primary"><?php echo strtoupper(substr($customer['customer_name'], 0, 2)); ?></span>
                                                        </div>
                                                        <div>
                                                            <div class="fw-semibold"><?php echo htmlspecialchars($customer['customer_name']); ?></div>
                                                            <div class="small text-muted"><?php echo htmlspecialchars($customer['GST']); ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($customer['cust_company']); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($customer['cust_state']); ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($customer['cust_address'], 0, 30)); ?>...</small>
                                                 </td>
                                                <td class="fw-semibold">₹<?php echo number_format($total); ?></td>
                                                <td class="text-success fw-semibold">₹<?php echo number_format($paid); ?></td>
                                                <td class="<?php echo $balance > 0 ? 'text-danger' : 'text-success'; ?> fw-semibold">
                                                    ₹<?php echo number_format($balance); ?>
                                                </td>
                                                <td>
                                                    <span class="payment-status <?php echo $status_class; ?>">
                                                        <?php echo $status_text; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <?php if ($balance > 0): ?>
                                                            <button class="btn btn-sm btn-outline-success" onclick="makePayment(<?php echo $customer['sno']; ?>, '<?php echo htmlspecialchars($customer['customer_name']); ?>', <?php echo $balance; ?>)">
                                                                <i class="ti ti-coin"></i> Pay
                                                            </button>
                                                        <?php endif; ?>
                                                        <a href="history.php?no=<?php echo $customer['customer_num']; ?>" class="btn btn-sm btn-outline-info">
                                                            <i class="ti ti-history"></i> History
                                                        </a>
                                                        <?php if ($grade_level < 4): ?>
                                                            <a href="edit_customer.php?customerID=<?php echo $customer['sno']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="ti ti-edit"></i> Edit
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php if (($customer['Aadhar'] == "0" || empty($customer['Aadhar'])) && $grade_level < 4): ?>
                                                            <a href="support.php?customerID=<?php echo $customer['sno']; ?>" class="btn btn-sm btn-outline-warning">
                                                                <i class="ti ti-send"></i> Support
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-5">
                                                <i class="ti ti-star-off fs-48 text-muted"></i>
                                                <h5 class="mt-3">No Matelized Customers Found</h5>
                                                <p class="text-muted">Customers who are matelized will appear here</p>
                                             </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                             </table>
                        </div>
                    </div>
                </div>
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
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="customer_id" id="payment_customer_id">
                        <div class="mb-3">
                            <label class="form-label">Customer Name</label>
                            <input type="text" class="form-control" id="payment_customer_name" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Balance Amount</label>
                            <input type="text" class="form-control" id="payment_balance" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="amount" id="payment_amount" required step="0.01" min="1">
                            <small class="text-muted">Enter amount to pay (Maximum: <span id="maxAmount">0</span>)</small>
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
                                Pay Full Balance
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
        let currentBalance = 0;
        
        $(document).ready(function() {
            // Initialize DataTable
            if ($('#matelizeTable').length) {
                var table = $('#matelizeTable').DataTable({
                    pageLength: 10,
                    order: [[0, 'desc']],
                    language: {
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ matelized customers",
                        infoEmpty: "No matelized customers found",
                        emptyTable: "No data available"
                    },
                    columnDefs: [
                        { orderable: false, targets: 'no-sort' }
                    ]
                });
                
                // Custom search
                $('#tableSearch').on('keyup', function() {
                    table.search(this.value).draw();
                });
            }
            
            // Date range picker
            if ($('#reportrange').length > 0) {
                var start = moment().subtract(29, 'days');
                var end = moment();
                
                function cb(start, end) {
                    $('#reportrange span').html(start.format('D MMM YYYY') + ' - ' + end.format('D MMM YYYY'));
                }
                
                $('#reportrange').daterangepicker({
                    startDate: start,
                    endDate: end,
                    ranges: {
                        'Today': [moment(), moment()],
                        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                        'This Month': [moment().startOf('month'), moment().endOf('month')],
                        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                    }
                }, cb);
                
                cb(start, end);
            }
        });
        
        function makePayment(customerId, customerName, balance) {
            currentBalance = balance;
            $('#payment_customer_id').val(customerId);
            $('#payment_customer_name').val(customerName);
            $('#payment_balance').val('₹' + parseFloat(balance).toLocaleString('en-IN', {minimumFractionDigits: 2}));
            $('#payment_amount').val('');
            $('#payment_amount').attr('max', balance);
            $('#maxAmount').text('₹' + balance.toLocaleString('en-IN'));
            
            $('#fullPaymentCheck').off('change').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#payment_amount').val(currentBalance);
                    $('#payment_amount').prop('readonly', true);
                } else {
                    $('#payment_amount').val('');
                    $('#payment_amount').prop('readonly', false);
                }
            });
            
            $('#paymentModal').modal('show');
        }
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
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
    </script>
</body>
</html>