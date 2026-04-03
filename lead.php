<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
    exit;
}

include 'partials/_header.php';
include 'partials/_dbconnect.php';

$user_id = $_SESSION['user_id'];
$grade_level = $_SESSION['grade_level'];

// Fetch user info
$info_ftch_sql = "SELECT * FROM `employees` WHERE `user_id` = '$user_id'";
$result_ftch_sql = mysqli_query($conn, $info_ftch_sql);
$info_row = mysqli_fetch_assoc($result_ftch_sql);
$name = $info_row['user_name'];
$userName = $info_row['user_id'];
$level = $info_row['grade_level'];

// Build query based on user level
if ($level == 4) {
    // Level 4: Agents - Only their assigned leads
    if (isset($_GET['leadType']) && $_GET['leadType'] != "All Leads") {
        $type = mysqli_real_escape_string($conn, $_GET['leadType']);
        $lead_ftch_sql = "SELECT * FROM `customerleads` WHERE `assigned_to` = '$user_id' AND `status` = '$type' AND (`matelize` = '0' OR `matelize` IS NULL) AND `status` != 'Block' order by sno desc";
    } else {
        $lead_ftch_sql = "SELECT * FROM `customerleads` WHERE `assigned_to` = '$user_id' AND (`matelize` = '0' OR `matelize` IS NULL) AND `status` != 'Block' order by sno desc";
        $type = "All Leads";
    }
} elseif ($level == 3) {
    // Level 3: Team Leaders - Their team's leads
    if (isset($_GET['leadType']) && $_GET['leadType'] != "All Leads") {
        $type = mysqli_real_escape_string($conn, $_GET['leadType']);
        $lead_ftch_sql = "SELECT * FROM `customerleads` WHERE `reporting` = '$user_id' AND `status` = '$type' AND (`matelize` = '0' OR `matelize` IS NULL) order by sno desc";
    } else {
        $lead_ftch_sql = "SELECT * FROM `customerleads` WHERE `reporting` = '$user_id' AND (`matelize` = '0' OR `matelize` IS NULL) order by sno desc";
        $type = "All Leads";
    }
} else {
    // Level 1 & 2: Admin & Managers - All leads
    if (isset($_GET['leadType']) && $_GET['leadType'] != "All Leads") {
        $type = mysqli_real_escape_string($conn, $_GET['leadType']);
        $lead_ftch_sql = "SELECT * FROM `customerleads` WHERE `status` = '$type' AND (`matelize` = '0' OR `matelize` IS NULL) order by sno desc";
    } else {
        $lead_ftch_sql = "SELECT * FROM `customerleads` WHERE (`matelize` = '0' OR `matelize` IS NULL) order by sno desc";
        $type = "All Leads";
    }
}

$lead_result = mysqli_query($conn, $lead_ftch_sql);
$total_leads = mysqli_num_rows($lead_result);

// Get statistics
$stats_query = "";
if ($level == 4) {
    $stats_query = "SELECT 
        SUM(CASE WHEN status = 'Fresh Lead' THEN 1 ELSE 0 END) as fresh_leads,
        SUM(CASE WHEN status = 'Follow Up' THEN 1 ELSE 0 END) as follow_up_leads,
        SUM(CASE WHEN status = 'Committed' THEN 1 ELSE 0 END) as committed_leads,
        SUM(CASE WHEN status = 'Positive' THEN 1 ELSE 0 END) as positive_leads
        FROM customerleads WHERE assigned_to = '$user_id' AND (matelize = '0' OR matelize IS NULL)";
} elseif ($level == 3) {
    $stats_query = "SELECT 
        SUM(CASE WHEN status = 'Fresh Lead' THEN 1 ELSE 0 END) as fresh_leads,
        SUM(CASE WHEN status = 'Follow Up' THEN 1 ELSE 0 END) as follow_up_leads,
        SUM(CASE WHEN status = 'Committed' THEN 1 ELSE 0 END) as committed_leads,
        SUM(CASE WHEN status = 'Positive' THEN 1 ELSE 0 END) as positive_leads
        FROM customerleads WHERE reporting = '$user_id' AND (matelize = '0' OR matelize IS NULL)";
} else {
    $stats_query = "SELECT 
        SUM(CASE WHEN status = 'Fresh Lead' THEN 1 ELSE 0 END) as fresh_leads,
        SUM(CASE WHEN status = 'Follow Up' THEN 1 ELSE 0 END) as follow_up_leads,
        SUM(CASE WHEN status = 'Committed' THEN 1 ELSE 0 END) as committed_leads,
        SUM(CASE WHEN status = 'Positive' THEN 1 ELSE 0 END) as positive_leads
        FROM customerleads WHERE (matelize = '0' OR matelize IS NULL)";
}

$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Lead Management | GO2EXPORT MART</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once "includes/link.php"; ?>

    <style>
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
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

        .lead-status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }

        .status-fresh { background: #e3f2fd; color: #1976d2; }
        .status-followup { background: #fff3e0; color: #f57c00; }
        .status-positive { background: #e8f5e9; color: #388e3c; }
        .status-committed { background: #e1f5fe; color: #0288d1; }
        .status-not-interested { background: #ffebee; color: #d32f2f; }

        .action-buttons .btn {
            margin: 2px;
            padding: 4px 8px;
            font-size: 12px;
        }

        .table-container {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .filter-btn {
            transition: all 0.3s;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
        }

        .bulk-actions {
            background: white;
            padding: 15px 20px;
            border-radius: 16px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: none;
            animation: slideUp 0.3s ease-out;
        }

        .bulk-actions.show {
            display: block;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .custom-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideInRight 0.3s ease-out;
        }

        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
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
            .stat-card { margin-bottom: 15px; }
            .table-container { overflow-x: auto; padding: 15px; }
            .filter-section .btn { margin-bottom: 8px; }
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
        
        .comment-badge {
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .comment-badge:hover {
            transform: scale(1.05);
        }
        
        .modal-lg-custom {
            max-width: 800px;
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
                        <h4 class="mb-1">Lead Management</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Leads</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
                        <a href="create_lead.php" class="btn btn-primary">
                            <i class="ti ti-plus me-2"></i>Create New Lead
                        </a>
                        <button onclick="window.location.reload()" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" title="Refresh">
                            <i class="ti ti-refresh"></i>
                        </button>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $total_leads; ?></div>
                                    <p class="stat-label">Total Leads</p>
                                </div>
                                <div class="stat-icon bg-primary bg-opacity-10">
                                    <i class="ti ti-users text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value text-primary"><?php echo $stats['fresh_leads'] ?? 0; ?></div>
                                    <p class="stat-label">Fresh Leads</p>
                                </div>
                                <div class="stat-icon bg-info bg-opacity-10">
                                    <i class="ti ti-droplet text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value text-warning"><?php echo $stats['follow_up_leads'] ?? 0; ?></div>
                                    <p class="stat-label">Follow Up</p>
                                </div>
                                <div class="stat-icon bg-warning bg-opacity-10">
                                    <i class="ti ti-clock text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value text-success"><?php echo $stats['committed_leads'] ?? 0; ?></div>
                                    <p class="stat-label">Committed</p>
                                </div>
                                <div class="stat-icon bg-success bg-opacity-10">
                                    <i class="ti ti-checkbox text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="filter-section">
                    <div class="row align-items-center g-3">
                        <div class="col-lg-8">
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="lead.php" class="btn <?php echo $type == 'All Leads' ? 'btn-primary' : 'btn-outline-secondary'; ?> filter-btn">
                                    <i class="ti ti-list me-1"></i>All Leads
                                </a>
                                <a href="lead.php?leadType=Fresh+Lead" class="btn <?php echo $type == 'Fresh Lead' ? 'btn-primary' : 'btn-outline-secondary'; ?> filter-btn">
                                    <i class="ti ti-droplet me-1"></i>Fresh Leads
                                </a>
                                <a href="lead.php?leadType=Follow+Up" class="btn <?php echo $type == 'Follow Up' ? 'btn-primary' : 'btn-outline-secondary'; ?> filter-btn">
                                    <i class="ti ti-clock me-1"></i>Follow Up
                                </a>
                                <a href="lead.php?leadType=Positive" class="btn <?php echo $type == 'Positive' ? 'btn-primary' : 'btn-outline-secondary'; ?> filter-btn">
                                    <i class="ti ti-thumb-up me-1"></i>Positive
                                </a>
                                <a href="lead.php?leadType=Committed" class="btn <?php echo $type == 'Committed' ? 'btn-primary' : 'btn-outline-secondary'; ?> filter-btn">
                                    <i class="ti ti-check me-1"></i>Committed
                                </a>
                                <a href="lead.php?leadType=Not+Interested" class="btn <?php echo $type == 'Not Interested' ? 'btn-primary' : 'btn-outline-secondary'; ?> filter-btn">
                                    <i class="ti ti-thumb-down me-1"></i>Not Interested
                                </a>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="ti ti-search"></i></span>
                                <input type="text" id="searchInput" class="form-control" placeholder="Search by name, mobile, company...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Leads Table -->
                <div class="table-container">
                    <div class="table-responsive">
                        <table id="leadsTable" class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="30"><input type="checkbox" id="selectAll" class="lead-checkbox"></th>
                                    <th>S No.</th>
                                    <th>Mobile No.</th>
                                    <th>Customer Name</th>
                                    <th>Company</th>
                                    <th>Email</th>
                                    <th>Assigned To</th>
                                    <th>Status</th>
                                    <th width="70">History</th>
                                    <th width="150">Actions</th>
                                </thead>
                            <tbody>
                                <?php if ($total_leads > 0): ?>
                                    <?php 
                                    $sno = 1;
                                    while ($lead_row = mysqli_fetch_assoc($lead_result)):
                                        $customerId = $lead_row['sno'];
                                        $status = $lead_row['status'];
                                        $statusClass = '';

                                        switch ($status) {
                                            case 'Fresh Lead': $statusClass = 'status-fresh'; break;
                                            case 'Follow Up': $statusClass = 'status-followup'; break;
                                            case 'Positive': $statusClass = 'status-positive'; break;
                                            case 'Committed': $statusClass = 'status-committed'; break;
                                            case 'Not Interested': $statusClass = 'status-not-interested'; break;
                                            default: $statusClass = 'status-fresh';
                                        }
                                        
                                        // Get comment count from call_logs
                                        $comment_count_sql = "SELECT COUNT(*) as total FROM call_logs WHERE lead_id = '$customerId'";
                                        $comment_count_result = $conn->query($comment_count_sql);
                                        $comment_count = $comment_count_result->fetch_assoc()['total'];
                                    ?>
                                        <tr>
                                            <td><input type="checkbox" class="lead-checkbox lead-select" value="<?php echo $customerId; ?>"></td>
                                            <td><?= $sno++ ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($lead_row['customer_num']); ?></strong>
                                                <?php if (!empty($lead_row['alt_number'])): ?>
                                                    <br><small class="text-muted">Alt: <?php echo htmlspecialchars($lead_row['alt_number']); ?></small>
                                                <?php endif; ?>
                                             </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-primary bg-opacity-10 me-2">
                                                        <span class="text-primary"><?php echo strtoupper(substr($lead_row['customer_name'], 0, 2)); ?></span>
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold"><?php echo htmlspecialchars($lead_row['customer_name']); ?></div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($lead_row['cust_state']); ?></small>
                                                    </div>
                                                </div>
                                             </td>
                                            <td><?php echo htmlspecialchars($lead_row['cust_company'] ?: '—'); ?></td>
                                            <td>
                                                <?php if (!empty($lead_row['cust_mail'])): ?>
                                                    <a href="mailto:<?php echo htmlspecialchars($lead_row['cust_mail']); ?>" class="text-decoration-none">
                                                        <i class="ti ti-mail"></i> <?php echo htmlspecialchars(substr($lead_row['cust_mail'], 0, 20)); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                             </td>
                                            <td>
                                                <span class="badge bg-info bg-opacity-10 text-info">
                                                    <i class="ti ti-user"></i> <?php echo htmlspecialchars($lead_row['assigned_to']); ?>
                                                </span>
                                             </td>
                                            <td>
                                                <span class="lead-status-badge <?php echo $statusClass; ?>">
                                                    <?php echo $status; ?>
                                                </span>
                                             </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary comment-badge" onclick="viewCallHistory(<?php echo $customerId; ?>)" data-bs-toggle="tooltip" title="View Call History">
                                                    <i class="ti ti-history"></i> <?php echo $comment_count; ?>
                                                </button>
                                             </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button onclick="quickAction('comment', <?php echo $customerId; ?>)" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Add Call Log">
                                                        <i class="ti ti-phone-call"></i>
                                                        <?php if ($comment_count > 0): ?>
                                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 8px;">
                                                                <?php echo $comment_count; ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </button>

                                                    <button onclick="quickAction('status', <?php echo $customerId; ?>)" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Update Status">
                                                        <i class="ti ti-edit"></i>
                                                    </button>

                                                    <?php if ($status == "Committed"): ?>
                                                        <button onclick="quickAction('matelize', <?php echo $customerId; ?>)" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Matelize">
                                                            <i class="ti ti-star"></i>
                                                        </button>
                                                        <button onclick="quickAction('proforma', <?php echo $customerId; ?>)" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Create Proforma">
                                                            <i class="ti ti-file-invoice"></i>
                                                        </button>
                                                    <?php endif; ?>

                                                    <?php if ($level < 4): ?>
                                                        <a href="edit_customer.php?customerID=<?php echo $customerId; ?>" class="btn btn-sm btn-outline-warning" data-bs-toggle="tooltip" title="Edit Lead">
                                                            <i class="ti ti-edit"></i>
                                                        </a>
                                                    <?php endif; ?>

                                                    <button onclick="viewLeadDetails(<?php echo $customerId; ?>)" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="View Details">
                                                        <i class="ti ti-eye"></i>
                                                    </button>
                                                </div>
                                             </td>
                                         </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-5">
                                            <i class="ti ti-inbox fs-48 text-muted"></i>
                                            <h5 class="mt-3">No Leads Found</h5>
                                            <p class="text-muted">Click "Create New Lead" to get started.</p>
                                            <a href="create_lead.php" class="btn btn-primary mt-2">
                                                <i class="ti ti-plus me-1"></i>Create Your First Lead
                                            </a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Bulk Actions Panel -->
                <div class="bulk-actions" id="bulkActions">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <strong><span id="selectedCount">0</span> leads selected</strong>
                        </div>
                        <div class="d-flex gap-2">
                            <form action="assign.php" method="post" class="d-flex gap-2">
                                <input type="hidden" name="transfer_lead" id="transferLead">
                                <select name="assign_to" class="form-select form-select-sm" required style="width: auto;">
                                    <option value="">Assign to...</option>
                                    <?php
                                    $agent_sql = $conn->query("SELECT * FROM `employees` WHERE `grade_level` = '4' AND `department` = 'Sales' AND `status` = 1");
                                    while ($agentname = mysqli_fetch_assoc($agent_sql)) {
                                        echo '<option value="' . $agentname['user_id'] . '">' . htmlspecialchars($agentname['user_name']) . '</option>';
                                    }
                                    ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="ti ti-user-plus"></i> Assign Selected
                                </button>
                            </form>
                            <button onclick="bulkStatusUpdate()" class="btn btn-sm btn-info">
                                <i class="ti ti-edit"></i> Update Status
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- Lead Details Modal -->
    <div class="modal fade" id="leadDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Lead Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="leadDetailsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Call History Modal -->
    <div class="modal fade" id="callHistoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Call History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="callHistoryContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Action Modal -->
    <div class="modal fade" id="quickActionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="quickActionTitle">Quick Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="quickActionForm" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="customerId" id="quickCustomerId">
                        <div id="quickActionContent"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Status Update Modal -->
    <div class="modal fade" id="bulkStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Status for Selected Leads</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="bulkStatusForm" method="post" action="ajax/bulk-status-update.php">
                    <div class="modal-body">
                        <input type="hidden" id="bulkLeadIds" name="lead_ids">
                        <div class="mb-3">
                            <label class="form-label">New Status</label>
                            <select name="new_status" class="form-select" required>
                                <option value="">Select Status</option>
                                <option value="Fresh Lead">Fresh Lead</option>
                                <option value="Follow Up">Follow Up</option>
                                <option value="Positive">Positive</option>
                                <option value="Committed">Committed</option>
                                <option value="Not Interested">Not Interested</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Comment (Optional)</label>
                            <textarea name="comment" class="form-control" rows="3" placeholder="Add a comment..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include_once "includes/footer-link.php"; ?>

    <script>
        $(document).ready(function() {
            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();

            // Initialize DataTable with proper search
            if ($('#leadsTable tbody tr').length > 0) {
                var table = $('#leadsTable').DataTable({
                    pageLength: 25,
                    order: [[1, 'desc']],
                    language: {
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ leads",
                        infoEmpty: "Showing 0 to 0 of 0 leads",
                        emptyTable: "No leads found"
                    },
                    columnDefs: [
                        { orderable: false, targets: [0, 7, 8] }
                    ]
                });
                
                // Custom search across multiple columns
                $('#searchInput').on('keyup', function() {
                    table.search(this.value).draw();
                });
            }

            // Select All functionality
            $('#selectAll').change(function() {
                $('.lead-select').prop('checked', $(this).prop('checked'));
                updateBulkActions();
            });

            // Individual checkbox change
            $(document).on('change', '.lead-select', function() {
                updateBulkActions();
                $('#selectAll').prop('checked', $('.lead-select:checked').length === $('.lead-select').length);
            });
        });

        function updateBulkActions() {
            const selectedCount = $('.lead-select:checked').length;
            const selectedIds = $('.lead-select:checked').map(function() {
                return $(this).val();
            }).get().join(',');

            $('#selectedCount').text(selectedCount);
            $('#transferLead').val(selectedIds);
            $('#bulkLeadIds').val(selectedIds);

            if (selectedCount > 0) {
                $('#bulkActions').addClass('show');
            } else {
                $('#bulkActions').removeClass('show');
            }
        }

        function viewLeadDetails(leadId) {
            $('#leadDetailsModal').modal('show');
            $('#leadDetailsContent').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');

            $.ajax({
                url: 'ajax/get-lead-details.php',
                type: 'GET',
                data: { id: leadId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const lead = response.data;
                        let html = `
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="border-bottom pb-2 mb-3">Personal Information</h6>
                                    <table class="table table-sm">
                                         <tr><th width="40%">Customer Name:</th><td><strong>${escapeHtml(lead.customer_name)}</strong></td></tr>
                                         <tr><th>Mobile Number:</th><td>${escapeHtml(lead.customer_num)}</td></tr>
                                         <tr><th>Alternate Number:</th><td>${escapeHtml(lead.alt_number) || '—'}</td></tr>
                                         <tr><th>Email:</th><td>${escapeHtml(lead.cust_mail) || '—'}</td></tr>
                                         <tr><th>State:</th><td>${escapeHtml(lead.cust_state) || '—'}</td></tr>
                                         <tr><th>Pincode:</th><td>${escapeHtml(lead.cust_pincode) || '—'}</td></tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="border-bottom pb-2 mb-3">Company & Business Info</h6>
                                    <table class="table table-sm">
                                         <tr><th width="40%">Company Name:</th><td>${escapeHtml(lead.cust_company) || '—'}</td></tr>
                                         <tr><th>Website:</th><td>${lead.website ? '<a href="http://'+escapeHtml(lead.website)+'" target="_blank">'+escapeHtml(lead.website)+'</a>' : '—'}</td></tr>
                                         <tr><th>Address:</th><td>${escapeHtml(lead.cust_address) || '—'}</td></tr>
                                         <tr><th>GST:</th><td>${escapeHtml(lead.GST) || '—'}</td></tr>
                                         <tr><th>PAN:</th><td>${escapeHtml(lead.pan) || '—'}</td></tr>
                                         <tr><th>Status:</th><td><span class="lead-status-badge status-${lead.status.toLowerCase().replace(' ', '-')}">${escapeHtml(lead.status)}</span></td></tr>
                                    </table>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="mb-3">Financial Details</h6>
                                    <table class="table table-sm">
                                         <tr><th width="30%">Amount:</th><td class="fw-bold text-success">₹${parseFloat(lead.amount).toLocaleString('en-IN')}</td></tr>
                                         <tr><th>Discount:</th><td>₹${parseFloat(lead.discount).toLocaleString('en-IN')}</td></tr>
                                         <tr><th>Balance Amount:</th><td class="fw-bold ${lead.bal_amt > 0 ? 'text-danger' : 'text-success'}">₹${parseFloat(lead.bal_amt).toLocaleString('en-IN')}</td></tr>
                                         <tr><th>Payment Mode:</th><td>${escapeHtml(lead.pay_mode) || '—'}</td></tr>
                                         <tr><th>Transaction ID:</th><td><code>${escapeHtml(lead.transaction) || '—'}</code></td></tr>
                                    </table>
                                </div>
                            </div>
                        `;
                        $('#leadDetailsContent').html(html);
                    } else {
                        $('#leadDetailsContent').html('<div class="alert alert-danger">' + response.message + '</div>');
                    }
                },
                error: function() {
                    $('#leadDetailsContent').html('<div class="alert alert-danger">Error loading lead details</div>');
                }
            });
        }

        function viewCallHistory(leadId) {
            $('#callHistoryModal').modal('show');
            $('#callHistoryContent').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');

            $.ajax({
                url: 'ajax/get-call-history.php',
                type: 'GET',
                data: { lead_id: leadId },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        let html = '<div class="timeline">';
                        response.data.forEach(call => {
                            html += `
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <strong>${escapeHtml(call.created_by)}</strong>
                                                <small class="text-muted ms-2">${new Date(call.created_at).toLocaleString()}</small>
                                            </div>
                                            ${call.call_duration ? `<span class="badge bg-info">Duration: ${call.call_duration}</span>` : ''}
                                        </div>
                                        <div class="mb-2">
                                            <span class="badge bg-${call.call_status === 'positive' ? 'success' : (call.call_status === 'follow_up' ? 'warning' : 'secondary')}">
                                                ${call.call_status.replace('_', ' ').toUpperCase()}
                                            </span>
                                            ${call.follow_up_date ? `<span class="badge bg-warning ms-2">Follow-up: ${new Date(call.follow_up_date).toLocaleDateString()}</span>` : ''}
                                        </div>
                                        <p class="mb-0">${escapeHtml(call.call_notes)}</p>
                                        ${call.source ? `<small class="text-muted">Source: ${call.source}</small>` : ''}
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                        $('#callHistoryContent').html(html);
                    } else {
                        $('#callHistoryContent').html('<div class="text-center py-4"><i class="ti ti-phone-off fs-48 text-muted"></i><p class="mt-2">No call history found for this lead</p></div>');
                    }
                },
                error: function() {
                    $('#callHistoryContent').html('<div class="alert alert-danger">Error loading call history</div>');
                }
            });
        }

        function quickAction(action, customerId) {
            $('#quickCustomerId').val(customerId);

            if (action === 'comment') {
                $('#quickActionTitle').text('Add Call Log');
                $('#quickActionContent').html(`
                    <div class="mb-3">
                        <label class="form-label">Call Status</label>
                        <select name="call_status" class="form-select" required>
                            <option value="">Select Status</option>
                            <option value="call_picked">Call Picked</option>
                            <option value="call_not_picked">Not Picked</option>
                            <option value="call_busy">Busy</option>
                            <option value="call_switched_off">Switched Off</option>
                            <option value="call_wrong_number">Wrong Number</option>
                            <option value="call_cut">Call Cut</option>
                            <option value="positive">Positive</option>
                            <option value="follow_up">Follow Up</option>
                            <option value="committed">Committed</option>
                            <option value="not_interested">Not Interested</option>
                            <option value="payment_done">Payment Done</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Call Duration</label>
                        <input type="text" name="call_duration" class="form-control" placeholder="e.g., 2:30 or 150 seconds">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Next Follow-up Date</label>
                        <input type="date" name="follow_up_date" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Call Notes / Remarks</label>
                        <textarea name="call_notes" class="form-control" rows="4" required placeholder="Enter call notes here..."></textarea>
                    </div>
                `);
                $('#quickActionForm').attr('action', 'add_call_log.php');
            } else if (action === 'status') {
                $('#quickActionTitle').text('Update Status');
                $('#quickActionContent').html(`
                    <div class="mb-3">
                        <label class="form-label">Select Status</label>
                        <select name="status" class="form-select" required>
                            <option value="">Select Status</option>
                            <option value="Fresh Lead">Fresh Lead</option>
                            <option value="Follow Up">Follow Up</option>
                            <option value="Positive">Positive</option>
                            <option value="Committed">Committed</option>
                            <option value="Not Interested">Not Interested</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Comment (Optional)</label>
                        <textarea name="comment" class="form-control" rows="3" placeholder="Add a comment..."></textarea>
                    </div>
                `);
                $('#quickActionForm').attr('action', 'update_status.php');
            } else if (action === 'matelize') {
                $('#quickActionTitle').text('Matelize Lead');
                $('#quickActionContent').html(`
                    <div class="alert alert-info">
                        <i class="ti ti-info-circle"></i> Marking this lead as matelized will move it to the matelized section.
                    </div>
                    <input type="hidden" name="matelize" value="1">
                    <div class="mb-3">
                        <label class="form-label">Amount (₹)</label>
                        <input type="number" name="amount" class="form-control" required placeholder="Enter amount">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Mode</label>
                        <select name="pay_mode" class="form-select">
                            <option value="">Select Payment Mode</option>
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="UPI">UPI</option>
                            <option value="Card">Card</option>
                        </select>
                    </div>
                `);
                $('#quickActionForm').attr('action', 'add_matelize.php');
            } else if (action === 'proforma') {
                $('#quickActionTitle').text('Create Proforma Invoice');
                $('#quickActionContent').html(`
                    <div class="alert alert-info">
                        <i class="ti ti-info-circle"></i> You will be redirected to the proforma invoice page.
                    </div>
                `);
                $('#quickActionForm').attr('action', 'proforma.php');
            }
            
            $('#quickActionModal').modal('show');
        }

        function bulkStatusUpdate() {
            const selectedCount = $('.lead-select:checked').length;
            if (selectedCount === 0) {
                showNotification('error', 'Please select at least one lead');
                return;
            }
            $('#bulkStatusModal').modal('show');
        }

        $('#bulkStatusForm').on('submit', function(e) {
            e.preventDefault();
            const formData = $(this).serialize();

            $.ajax({
                url: 'ajax/bulk-status-update.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#bulkStatusModal').modal('hide');
                        showNotification('success', response.message);
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showNotification('error', response.message);
                    }
                },
                error: function() {
                    showNotification('error', 'Error updating status');
                }
            });
        });

        function showNotification(type, message) {
            const notification = $(`
                <div class="custom-notification alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show" role="alert">
                    <i class="ti ti-${type === 'success' ? 'check-circle' : 'alert-circle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);
            $('body').append(notification);
            setTimeout(() => notification.fadeOut(() => notification.remove()), 3000);
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }
    </script>
</body>
</html>