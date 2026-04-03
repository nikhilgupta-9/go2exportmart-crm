<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
    exit;
}

include_once "partials/_dbconnect.php";

$user_id = $_SESSION['user_id'];
$grade_level = $_SESSION['grade_level'];

// Fetch user info
$user_info = $conn->query("SELECT * FROM employees WHERE user_id = '$user_id'")->fetch_assoc();

// Handle Lead Allocation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['allocate_leads'])) {
    $selected_leads = isset($_POST['selected_leads']) ? $_POST['selected_leads'] : [];
    $assign_to = $_POST['assign_to'];
    
    if (!empty($selected_leads) && !empty($assign_to)) {
        $success_count = 0;
        foreach ($selected_leads as $lead_id) {
            $lead_id = intval($lead_id);
            
            // Get reporting for the assignee
            $reporting_sql = "SELECT Reporting FROM employees WHERE user_id = '$assign_to'";
            $reporting_result = $conn->query($reporting_sql);
            $reporting_data = $reporting_result->fetch_assoc();
            $reporting = $reporting_data['Reporting'] ?? 'admin';
            
            $update_sql = "UPDATE fresh_leads SET 
                           assigned_to = '$assign_to',
                           reporting = '$reporting',
                           allocated_by = '$user_id',
                           allocated_at = NOW(),
                           status = 'allocated'
                           WHERE id = '$lead_id'";
            
            if ($conn->query($update_sql)) {
                $success_count++;
            }
        }
        $_SESSION['success_message'] = "$success_count leads allocated successfully!";
        header('location: lead-allocation.php');
        exit;
    } else {
        $_SESSION['error_message'] = "Please select leads and assignee";
    }
}

// Handle Bulk Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_upload'])) {
    if (isset($_FILES['lead_file']) && $_FILES['lead_file']['error'] == 0) {
        $file = $_FILES['lead_file']['tmp_name'];
        $extension = pathinfo($_FILES['lead_file']['name'], PATHINFO_EXTENSION);
        
        $success_count = 0;
        $error_count = 0;
        $errors = [];
        
        if ($extension == 'csv') {
            $handle = fopen($file, 'r');
            // Skip header row if exists
            $header = fgetcsv($handle);
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                // Skip empty rows
                if (empty($data[0]) && empty($data[2])) continue;
                
                $customer_num = mysqli_real_escape_string($conn, trim($data[0] ?? ''));
                $alt_number = mysqli_real_escape_string($conn, trim($data[1] ?? ''));
                $customer_name = mysqli_real_escape_string($conn, trim($data[2] ?? ''));
                $cust_company = mysqli_real_escape_string($conn, trim($data[3] ?? ''));
                $website = mysqli_real_escape_string($conn, trim($data[4] ?? ''));
                $cust_mail = mysqli_real_escape_string($conn, trim($data[5] ?? ''));
                $cust_address = mysqli_real_escape_string($conn, trim($data[6] ?? ''));
                $cust_state = mysqli_real_escape_string($conn, trim($data[7] ?? ''));
                $cust_pincode = mysqli_real_escape_string($conn, trim($data[8] ?? ''));
                $service = mysqli_real_escape_string($conn, trim($data[9] ?? ''));
                
                // Validate mobile number
                if (empty($customer_num) || !preg_match('/^[6-9][0-9]{9}$/', $customer_num)) {
                    $error_count++;
                    $errors[] = "Invalid mobile number: $customer_num";
                    continue;
                }
                
                if (empty($customer_name)) {
                    $error_count++;
                    $errors[] = "Customer name required for: $customer_num";
                    continue;
                }
                
                // Check if lead already exists in fresh_leads
                // $check_sql = "SELECT id FROM fresh_leads WHERE customer_num = '$customer_num'";
                $check_sql = "SELECT id FROM fresh_leads WHERE customer_num COLLATE utf8mb4_general_ci = '$customer_num'";
                $check_result = $conn->query($check_sql);
                if ($check_result->num_rows > 0) {
                    $error_count++;
                    $errors[] = "Duplicate lead: $customer_num";
                    continue;
                }
                
                // Check if lead already exists in customerleads
                $check_main = "SELECT sno FROM customerleads WHERE customer_num = '$customer_num'";
                $check_main_result = $conn->query($check_main);
                if ($check_main_result->num_rows > 0) {
                    $error_count++;
                    $errors[] = "Lead already exists in main leads: $customer_num";
                    continue;
                }
                
                // Insert into fresh_leads table
                $insert_sql = "INSERT INTO fresh_leads (
                    customer_num, alt_number, customer_name, cust_company, website,
                    cust_mail, cust_address, cust_state, cust_pincode, service,
                    source, created_by, created_at
                ) VALUES (
                    '$customer_num', '$alt_number', '$customer_name', '$cust_company', '$website',
                    '$cust_mail', '$cust_address', '$cust_state', '$cust_pincode', '$service',
                    'Meta Ads', '$user_id', NOW()
                )";
                
                if ($conn->query($insert_sql)) {
                    $success_count++;
                } else {
                    $error_count++;
                    $errors[] = "DB Error for: $customer_num";
                }
            }
            fclose($handle);
        } else {
            $_SESSION['error_message'] = "Please upload a CSV file only";
            header('location: lead-allocation.php');
            exit;
        }
        
        $message = "$success_count leads imported successfully! $error_count failed.";
        if (!empty($errors) && count($errors) <= 5) {
            $message .= "<br>Errors: " . implode(", ", array_slice($errors, 0, 5));
        }
        $_SESSION['success_message'] = $message;
        header('location: lead-allocation.php');
        exit;
    } else {
        $_SESSION['error_message'] = "Please upload a valid CSV file";
        header('location: lead-allocation.php');
        exit;
    }
}

// Fetch fresh leads based on user level
if ($grade_level == 1) {
    $fresh_leads_sql = "SELECT * FROM fresh_leads WHERE status = 'pending' ORDER BY created_at DESC";
} elseif ($grade_level == 2) {
    $fresh_leads_sql = "SELECT * FROM fresh_leads WHERE status = 'pending' ORDER BY created_at DESC";
} elseif ($grade_level == 3) {
    $fresh_leads_sql = "SELECT * FROM fresh_leads WHERE status = 'pending' ORDER BY created_at DESC";
} else {
    $fresh_leads_sql = "SELECT * FROM fresh_leads WHERE assigned_to = '$user_id' AND status = 'allocated' ORDER BY created_at DESC";
}

$fresh_leads_result = $conn->query($fresh_leads_sql);
$total_fresh = $fresh_leads_result->num_rows;

// Fetch users for allocation based on hierarchy
$users_sql = "";
if ($grade_level == 1) {
    $users_sql = "SELECT user_id, user_name, grade_level, user_role FROM employees WHERE grade_level IN (2,3,4) AND status = 1 ORDER BY grade_level, user_name";
} elseif ($grade_level == 2) {
    $users_sql = "SELECT user_id, user_name, grade_level, user_role FROM employees WHERE grade_level IN (3,4) AND status = 1 ORDER BY grade_level, user_name";
} elseif ($grade_level == 3) {
    $users_sql = "SELECT user_id, user_name, grade_level, user_role FROM employees WHERE grade_level = 4 AND status = 1 ORDER BY user_name";
}
$users_result = $conn->query($users_sql);

// Get allocated leads for view
$allocated_leads_sql = "";
if ($grade_level == 1) {
    $allocated_leads_sql = "SELECT fl.*, e.user_name as assigned_to_name 
                            FROM fresh_leads fl 
                            LEFT JOIN employees e ON fl.assigned_to = e.user_id 
                            WHERE fl.status = 'allocated' 
                            ORDER BY fl.allocated_at DESC LIMIT 50";
} elseif ($grade_level == 2) {
    $allocated_leads_sql = "SELECT fl.*, e.user_name as assigned_to_name 
                            FROM fresh_leads fl 
                            LEFT JOIN employees e ON fl.assigned_to = e.user_id 
                            WHERE fl.allocated_by = '$user_id' OR fl.assigned_to = '$user_id'
                            ORDER BY fl.allocated_at DESC LIMIT 50";
} elseif ($grade_level == 3) {
    $allocated_leads_sql = "SELECT fl.*, e.user_name as assigned_to_name 
                            FROM fresh_leads fl 
                            LEFT JOIN employees e ON fl.assigned_to = e.user_id 
                            WHERE fl.assigned_to = '$user_id' OR fl.allocated_by = '$user_id'
                            ORDER BY fl.allocated_at DESC LIMIT 50";
} else {
    $allocated_leads_sql = "SELECT fl.*, e.user_name as assigned_to_name 
                            FROM fresh_leads fl 
                            LEFT JOIN employees e ON fl.assigned_to = e.user_id 
                            WHERE fl.assigned_to = '$user_id'
                            ORDER BY fl.allocated_at DESC LIMIT 50";
}
$allocated_leads_result = $conn->query($allocated_leads_sql);

// Get statistics
$total_allocated = $conn->query("SELECT COUNT(*) as total FROM fresh_leads WHERE status = 'allocated'")->fetch_assoc()['total'];
$total_meta = $conn->query("SELECT COUNT(*) as total FROM fresh_leads WHERE source = 'Meta Ads'")->fetch_assoc()['total'];
$total_google = $conn->query("SELECT COUNT(*) as total FROM fresh_leads WHERE source = 'Google'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Lead Allocation | GO2EXPORT MART</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once "includes/link.php"; ?>
    
    <style>
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
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
            font-size: 13px;
            margin-bottom: 0;
        }
        
        .allocation-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .source-badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .source-meta { background: #e3f2fd; color: #1976d2; }
        .source-google { background: #e8f5e9; color: #388e3c; }
        .source-manual { background: #fff3e0; color: #f57c00; }
        
        .dropzone {
            border: 2px dashed #dee2e6;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .dropzone:hover {
            border-color: #667eea;
            background: #f8f9fa;
        }
        
        .dropzone.dragover {
            border-color: #28a745;
            background: #d4edda;
        }
        
        .file-name {
            margin-top: 10px;
            font-size: 14px;
            color: #28a745;
        }
        
        @media (max-width: 768px) {
            .stat-card {
                margin-bottom: 15px;
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
                        <h4 class="mb-1">Lead Allocation</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Lead Allocation</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bulkUploadModal">
                            <i class="ti ti-upload me-2"></i>Bulk Upload
                        </button>
                        <button onclick="window.location.reload()" class="btn btn-icon btn-outline-light shadow">
                            <i class="ti ti-refresh"></i>
                        </button>
                    </div>
                </div>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="ti ti-check-circle me-2"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="ti ti-alert-circle me-2"></i> <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $total_fresh; ?></div>
                                    <p class="stat-label">Pending Allocation</p>
                                </div>
                                <div class="stat-icon bg-primary bg-opacity-10">
                                    <i class="ti ti-clock text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $total_allocated; ?></div>
                                    <p class="stat-label">Allocated Leads</p>
                                </div>
                                <div class="stat-icon bg-success bg-opacity-10">
                                    <i class="ti ti-check-circle text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $total_meta; ?></div>
                                    <p class="stat-label">Meta Leads</p>
                                </div>
                                <div class="stat-icon bg-info bg-opacity-10">
                                    <i class="ti ti-brand-facebook text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $total_google; ?></div>
                                    <p class="stat-label">Google Leads</p>
                                </div>
                                <div class="stat-icon bg-warning bg-opacity-10">
                                    <i class="ti ti-brand-google text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Allocation Form -->
                <?php if ($grade_level < 4 && $total_fresh > 0): ?>
                <div class="allocation-card">
                    <h5 class="mb-3"><i class="ti ti-user-plus me-2"></i>Allocate Leads</h5>
                    <form action="" method="POST" id="allocationForm">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Select Leads (Hold Ctrl/Cmd for multiple)</label>
                                <select class="form-select" id="leadSelect" multiple size="6" name="selected_leads[]" style="height: auto; min-height: 150px;">
                                    <?php 
                                    $fresh_leads_result = $conn->query($fresh_leads_sql);
                                    while($lead = $fresh_leads_result->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $lead['id']; ?>">
                                            <?php echo htmlspecialchars($lead['customer_name']) . ' - ' . $lead['customer_num'] . ' (' . $lead['source'] . ')'; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <small class="text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Assign To</label>
                                <select class="form-select" name="assign_to" required>
                                    <option value="">Select User</option>
                                    <?php 
                                    $users_result = $conn->query($users_sql);
                                    while($user = $users_result->fetch_assoc()): 
                                        $grade_name = '';
                                        switch($user['grade_level']) {
                                            case 2: $grade_name = '(Manager)'; break;
                                            case 3: $grade_name = '(Team Lead)'; break;
                                            case 4: $grade_name = '(Executive)'; break;
                                        }
                                    ?>
                                        <option value="<?php echo $user['user_id']; ?>">
                                            <?php echo htmlspecialchars($user['user_name']); ?> <?php echo $grade_name; ?> - <?php echo $user['user_role']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" name="allocate_leads" class="btn btn-primary w-100">
                                    <i class="ti ti-user-plus me-2"></i>Allocate Selected Leads
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Fresh Leads Table -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-bottom">
                        <h5 class="mb-0"><i class="ti ti-inbox me-2"></i>Fresh Leads (Pending Allocation)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="freshLeadsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="30"><input type="checkbox" id="selectAllFresh"></th>
                                        <th>Mobile No.</th>
                                        <th>Customer Name</th>
                                        <th>Company</th>
                                        <th>Email</th>
                                        <th>Service</th>
                                        <th>Source</th>
                                        <th>Created Date</th>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $fresh_leads_result = $conn->query($fresh_leads_sql);
                                        while($lead = $fresh_leads_result->fetch_assoc()): 
                                        ?>
                                        <tr>
                                            <td><input type="checkbox" class="lead-checkbox" value="<?php echo $lead['id']; ?>"></td>
                                            <td><strong><?php echo htmlspecialchars($lead['customer_num']); ?></strong><br><small class="text-muted">Alt: <?php echo htmlspecialchars($lead['alt_number'] ?: 'N/A'); ?></small></td>
                                            <td><?php echo htmlspecialchars($lead['customer_name']); ?></td>
                                            <td><?php echo htmlspecialchars($lead['cust_company'] ?: '—'); ?></td>
                                            <td><?php echo htmlspecialchars($lead['cust_mail'] ?: '—'); ?></td>
                                            <td><?php echo htmlspecialchars($lead['service'] ?: '—'); ?></td>
                                            <td><span class="source-badge source-<?php echo strtolower(str_replace(' ', '-', $lead['source'])); ?>"><?php echo $lead['source']; ?></span></td>
                                            <td><?php echo date('d M Y', strtotime($lead['created_at'])); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Allocated Leads Table -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-bottom">
                            <h5 class="mb-0"><i class="ti ti-user-check me-2"></i>Allocated Leads</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="allocatedLeadsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Mobile No.</th>
                                            <th>Customer Name</th>
                                            <th>Company</th>
                                            <th>Service</th>
                                            <th>Assigned To</th>
                                            <th>Allocated By</th>
                                            <th>Allocated Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($lead = $allocated_leads_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($lead['customer_num']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($lead['customer_name']); ?></td>
                                            <td><?php echo htmlspecialchars($lead['cust_company'] ?: '—'); ?></td>
                                            <td><?php echo htmlspecialchars($lead['service'] ?: '—'); ?></td>
                                            <td><span class="badge bg-info"><?php echo htmlspecialchars($lead['assigned_to_name']); ?></span></td>
                                            <td><?php echo htmlspecialchars($lead['allocated_by']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($lead['allocated_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="reallocateLead(<?php echo $lead['id']; ?>)">
                                                    <i class="ti ti-refresh"></i> Reallocate
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php include_once "includes/footer.php"; ?>
            </div>
        </div>

        <!-- Bulk Upload Modal -->
        <div class="modal fade" id="bulkUploadModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Bulk Upload Leads</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="" method="POST" enctype="multipart/form-data" id="bulkUploadForm">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Upload CSV File</label>
                                <div class="dropzone" id="dropzone">
                                    <i class="ti ti-cloud-upload fs-48 text-muted"></i>
                                    <p class="mt-2 mb-0">Drag & drop your CSV file here or click to browse</p>
                                    <input type="file" name="lead_file" id="leadFile" accept=".csv" style="display: none;">
                                    <div id="fileNameDisplay" class="file-name"></div>
                                </div>
                                <small class="text-muted d-block mt-2">Supported format: CSV only</small>
                            </div>
                            <div class="alert alert-info">
                                <i class="ti ti-info-circle me-2"></i>
                                <strong>CSV Format Required (in this order):</strong>
                                <ol class="mb-0 mt-2">
                                    <li><strong>Mobile Number</strong> (10 digits starting with 6-9) - Required</li>
                                    <li>Alternate Number (Optional)</li>
                                    <li><strong>Customer Name</strong> - Required</li>
                                    <li>Company Name (Optional)</li>
                                    <li>Website (Optional)</li>
                                    <li>Email (Optional)</li>
                                    <li>Address (Optional)</li>
                                    <li>State (Optional)</li>
                                    <li>Pincode (Optional)</li>
                                    <li>Service Type (Optional)</li>
                                </ol>
                            </div>
                            <div class="mt-3">
                                <a href="#" id="downloadSample" class="btn btn-sm btn-outline-secondary">
                                    <i class="ti ti-download me-1"></i> Download Sample CSV
                                </a>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="bulk_upload" class="btn btn-primary" id="uploadBtn">Upload & Import</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Reallocate Modal -->
        <div class="modal fade" id="reallocateModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Reallocate Lead</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="lead-reallocate.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="lead_id" id="reallocate_lead_id">
                            <div class="mb-3">
                                <label class="form-label">Assign To</label>
                                <select class="form-select" name="assign_to" required>
                                    <option value="">Select User</option>
                                    <?php 
                                    $reallocate_users = $conn->query($users_sql);
                                    while($user = $reallocate_users->fetch_assoc()): 
                                        $grade_name = '';
                                        switch($user['grade_level']) {
                                            case 2: $grade_name = '(Manager)'; break;
                                            case 3: $grade_name = '(Team Lead)'; break;
                                            case 4: $grade_name = '(Executive)'; break;
                                        }
                                    ?>
                                        <option value="<?php echo $user['user_id']; ?>">
                                            <?php echo htmlspecialchars($user['user_name']); ?> <?php echo $grade_name; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reason for Reallocation</label>
                                <textarea class="form-control" name="reason" rows="2" placeholder="Enter reason for reallocation..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="reallocate" class="btn btn-primary">Reallocate</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php include_once "includes/footer-link.php"; ?>

        <script>
        $(document).ready(function() {
            // Initialize DataTables
            if ($('#freshLeadsTable tbody tr').length > 0) {
                $('#freshLeadsTable').DataTable({
                    pageLength: 10,
                    order: [[7, 'desc']],
                    language: {
                        search: "Search fresh leads:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ fresh leads"
                    }
                });
            }
            
            if ($('#allocatedLeadsTable tbody tr').length > 0) {
                $('#allocatedLeadsTable').DataTable({
                    pageLength: 10,
                    order: [[6, 'desc']],
                    language: {
                        search: "Search allocated leads:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ allocated leads"
                    }
                });
            }
            
            // Select All functionality
            $('#selectAllFresh').change(function() {
                $('.lead-checkbox').prop('checked', $(this).prop('checked'));
            });
            
            // Update select all based on individual checkboxes
            $(document).on('change', '.lead-checkbox', function() {
                $('#selectAllFresh').prop('checked', $('.lead-checkbox:checked').length === $('.lead-checkbox').length);
            });
        });
        
        // Dropzone functionality - FIXED
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('leadFile');
        const fileNameDisplay = document.getElementById('fileNameDisplay');
        
        if (dropzone && fileInput) {
            dropzone.addEventListener('click', function(e) {
                e.preventDefault();
                fileInput.click();
            });
            
            dropzone.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('dragover');
            });
            
            dropzone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('dragover');
            });
            
            dropzone.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    if (fileNameDisplay) {
                        fileNameDisplay.innerHTML = '<i class="ti ti-file-text"></i> Selected: ' + files[0].name;
                        fileNameDisplay.style.color = '#28a745';
                    }
                }
            });
            
            fileInput.addEventListener('change', function() {
                if (fileInput.files.length > 0) {
                    if (fileNameDisplay) {
                        fileNameDisplay.innerHTML = '<i class="ti ti-file-text"></i> Selected: ' + fileInput.files[0].name;
                        fileNameDisplay.style.color = '#28a745';
                    }
                } else {
                    if (fileNameDisplay) {
                        fileNameDisplay.innerHTML = '';
                    }
                }
            });
        }
        
        function reallocateLead(leadId) {
            $('#reallocate_lead_id').val(leadId);
            $('#reallocateModal').modal('show');
        }
        
        // Download sample CSV
        document.getElementById('downloadSample')?.addEventListener('click', function(e) {
            e.preventDefault();
            const sampleData = [
                ['Mobile Number', 'Alternate Number', 'Customer Name', 'Company Name', 'Website', 'Email', 'Address', 'State', 'Pincode', 'Service'],
                ['9876543210', '9123456780', 'John Doe', 'ABC Corp', 'https://abccorp.com', 'john@abccorp.com', '123 Main Street', 'Maharashtra', '400001', 'Import Services'],
                ['9876543211', '', 'Jane Smith', 'XYZ Ltd', '', 'jane@xyzltd.com', '456 Park Avenue', 'Delhi', '110001', 'Export Services'],
                ['9876543212', '9988776655', 'Mike Johnson', 'Tech Solutions', 'https://techsolutions.com', 'mike@techsolutions.com', '789 Tech Park', 'Karnataka', '560001', 'Consulting']
            ];
            
            let csvContent = sampleData.map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'sample_leads.csv');
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        });
        </script>
    </body>
    </html>