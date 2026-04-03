<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
    exit;
}

include_once "partials/_dbconnect.php";

$user_id = $_SESSION['user_id'];
$grade_level = $_SESSION['grade_level'];
$user_name = $_SESSION['user_name'];

// Fetch user info
$user_info = $conn->query("SELECT * FROM employees WHERE user_id = '$user_id'")->fetch_assoc();

// Get leads based on grade level
$leads = [];
$total_leads = 0;

if ($grade_level == 1) {
    // Admin: View all allocated leads
    $leads_sql = "SELECT fl.*, 
                  e.user_name as assigned_to_name, 
                  e.grade_level as assigned_grade,
                  e.user_role as assigned_role,
                  a.user_name as allocated_by_name
                  FROM fresh_leads fl 
                  LEFT JOIN employees e ON fl.assigned_to = e.user_id 
                  LEFT JOIN employees a ON fl.allocated_by = a.user_id 
                  WHERE fl.status = 'allocated' 
                  ORDER BY fl.allocated_at DESC";
    $leads_result = $conn->query($leads_sql);
    
    while ($lead = $leads_result->fetch_assoc()) {
        $leads[] = $lead;
        $total_leads++;
    }
    
} elseif ($grade_level == 2) {
    // Manager: View leads allocated to their team (Grade 3 & 4) AND their own leads
    $leads_sql = "SELECT fl.*, 
                  e.user_name as assigned_to_name, 
                  e.grade_level as assigned_grade,
                  e.user_role as assigned_role,
                  a.user_name as allocated_by_name
                  FROM fresh_leads fl 
                  LEFT JOIN employees e ON fl.assigned_to = e.user_id 
                  LEFT JOIN employees a ON fl.allocated_by = a.user_id 
                  WHERE fl.status = 'allocated' 
                  AND (e.Reporting = '$user_id' OR fl.assigned_to = '$user_id')
                  ORDER BY fl.allocated_at DESC";
    $leads_result = $conn->query($leads_sql);
    
    while ($lead = $leads_result->fetch_assoc()) {
        $leads[] = $lead;
        $total_leads++;
    }
    
} elseif ($grade_level == 3) {
    // Team Lead: View leads allocated to their team members (Grade 4) AND their own leads
    $leads_sql = "SELECT fl.*, 
                  e.user_name as assigned_to_name, 
                  e.grade_level as assigned_grade,
                  e.user_role as assigned_role,
                  a.user_name as allocated_by_name
                  FROM fresh_leads fl 
                  LEFT JOIN employees e ON fl.assigned_to = e.user_id 
                  LEFT JOIN employees a ON fl.allocated_by = a.user_id 
                  WHERE fl.status = 'allocated' 
                  AND (e.Reporting = '$user_id' OR fl.assigned_to = '$user_id')
                  ORDER BY fl.allocated_at DESC";
    $leads_result = $conn->query($leads_sql);
    
    while ($lead = $leads_result->fetch_assoc()) {
        $leads[] = $lead;
        $total_leads++;
    }
    
} elseif ($grade_level == 4) {
    // Executive: View only their own allocated leads
    $leads_sql = "SELECT fl.*, 
                  e.user_name as assigned_to_name, 
                  e.grade_level as assigned_grade,
                  e.user_role as assigned_role,
                  a.user_name as allocated_by_name
                  FROM fresh_leads fl 
                  LEFT JOIN employees e ON fl.assigned_to = e.user_id 
                  LEFT JOIN employees a ON fl.allocated_by = a.user_id 
                  WHERE fl.status = 'allocated' 
                  AND fl.assigned_to = '$user_id'
                  ORDER BY fl.allocated_at DESC";
    $leads_result = $conn->query($leads_sql);
    
    while ($lead = $leads_result->fetch_assoc()) {
        $leads[] = $lead;
        $total_leads++;
    }
}

// Get statistics
$total_allocated = $total_leads;
$pending_followups = 0;

foreach ($leads as $lead) {
    // Calculate pending followups
    if ($lead['status'] == 'Follow Up') {
        $pending_followups++;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Allocated Leads | GO2EXPORT MART</title>
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
        
        .grade-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
        }
        
        .grade-1 { background: #ffd700; color: #856404; }
        .grade-2 { background: #c0c0c0; color: #495057; }
        .grade-3 { background: #cd7f32; color: white; }
        .grade-4 { background: #667eea; color: white; }
        
        .source-badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .source-meta { background: #e3f2fd; color: #1976d2; }
        .source-google { background: #e8f5e9; color: #388e3c; }
        .source-manual { background: #fff3e0; color: #f57c00; }
        
        .action-buttons .btn {
            margin: 2px;
            padding: 4px 8px;
        }
        
        @media (max-width: 768px) {
            .stat-card {
                margin-bottom: 15px;
            }
            .table-container {
                overflow-x: auto;
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
                        <h4 class="mb-1">Allocated Leads</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="lead-allocation.php">Lead Allocation</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Allocated Leads</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
                        <a href="lead-allocation.php" class="btn btn-primary">
                            <i class="ti ti-user-plus me-2"></i>Allocate New Leads
                        </a>
                        <button onclick="window.location.reload()" class="btn btn-icon btn-outline-light shadow">
                            <i class="ti ti-refresh"></i>
                        </button>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $total_allocated; ?></div>
                                    <p class="stat-label">Total Allocated Leads</p>
                                </div>
                                <div class="stat-icon bg-primary bg-opacity-10">
                                    <i class="ti ti-user-check text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $pending_followups; ?></div>
                                    <p class="stat-label">Pending Follow-ups</p>
                                </div>
                                <div class="stat-icon bg-warning bg-opacity-10">
                                    <i class="ti ti-clock text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo count($leads); ?></div>
                                    <p class="stat-label">Active Leads</p>
                                </div>
                                <div class="stat-icon bg-success bg-opacity-10">
                                    <i class="ti ti-chart-arcs text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Leads Table -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-bottom">
                        <h5 class="mb-0"><i class="ti ti-list me-2"></i>Allocated Leads List</h5>
                        <small class="text-muted">View leads allocated based on your grade level</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="leadsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Customer Details</th>
                                        <th>Company</th>
                                        <th>Service</th>
                                        <th>Source</th>
                                        <th>Assigned To</th>
                                        <th>Grade</th>
                                        <th>Allocated By</th>
                                        <th>Allocated Date</th>
                                        <th>Actions</th>
                                    </thead>
                                <tbody>
                                    <?php if (!empty($leads)): ?>
                                        <?php $count = 1; foreach ($leads as $lead): 
                                            $grade_class = '';
                                            switch($lead['assigned_grade']) {
                                                case 1: $grade_class = 'grade-1'; break;
                                                case 2: $grade_class = 'grade-2'; break;
                                                case 3: $grade_class = 'grade-3'; break;
                                                case 4: $grade_class = 'grade-4'; break;
                                                default: $grade_class = 'grade-4';
                                            }
                                            
                                            $source_class = '';
                                            switch($lead['source']) {
                                                case 'Meta Ads': $source_class = 'source-meta'; break;
                                                case 'Google': $source_class = 'source-google'; break;
                                                default: $source_class = 'source-manual';
                                            }
                                        ?>
                                        <tr>
                                            <td><?php echo $count++; ?></td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($lead['customer_name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="ti ti-phone"></i> <?php echo htmlspecialchars($lead['customer_num']); ?>
                                                        <?php if (!empty($lead['alt_number'])): ?>
                                                            <br><i class="ti ti-phone-call"></i> <?php echo htmlspecialchars($lead['alt_number']); ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($lead['cust_company'] ?: '—'); ?></td>
                                            <td><?php echo htmlspecialchars($lead['service'] ?: '—'); ?></td>
                                            <td>
                                                <span class="source-badge <?php echo $source_class; ?>">
                                                    <?php echo $lead['source']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-primary bg-opacity-10 me-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                                        <span class="text-primary" style="font-size: 12px;"><?php echo substr($lead['assigned_to_name'], 0, 2); ?></span>
                                                    </div>
                                                    <?php echo htmlspecialchars($lead['assigned_to_name']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="grade-badge <?php echo $grade_class; ?>">
                                                    Level <?php echo $lead['assigned_grade']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($lead['allocated_by_name'] ?: 'System'); ?></td>
                                            <td><?php echo date('d M Y', strtotime($lead['allocated_at'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button onclick="viewLeadDetails(<?php echo $lead['id']; ?>)" class="btn btn-sm btn-outline-info" title="View Details">
                                                        <i class="ti ti-eye"></i>
                                                    </button>
                                                    <?php if ($grade_level <= 2): ?>
                                                        <button onclick="reallocateLead(<?php echo $lead['id']; ?>)" class="btn btn-sm btn-outline-warning" title="Reallocate">
                                                            <i class="ti ti-refresh"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($grade_level == 4 && $lead['assigned_to'] == $user_id): ?>
                                                        <a href="comment.php?customerId=<?php echo $lead['id']; ?>" class="btn btn-sm btn-outline-primary" title="Add Comment">
                                                            <i class="ti ti-message-circle"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                         </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="10" class="text-center py-5">
                                                <i class="ti ti-inbox fs-48 text-muted"></i>
                                                <h5 class="mt-3">No Allocated Leads Found</h5>
                                                <p class="text-muted">No leads have been allocated to you or your team yet.</p>
                                                <?php if ($grade_level == 1): ?>
                                                    <a href="lead-allocation.php" class="btn btn-primary mt-2">
                                                        <i class="ti ti-user-plus"></i> Allocate Leads
                                                    </a>
                                                <?php endif; ?>
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
                                // Fetch users based on hierarchy
                                if ($grade_level == 1) {
                                    $users_sql = "SELECT user_id, user_name, grade_level, user_role FROM employees WHERE grade_level IN (2,3,4) AND status = 1 ORDER BY grade_level, user_name";
                                } elseif ($grade_level == 2) {
                                    $users_sql = "SELECT user_id, user_name, grade_level, user_role FROM employees WHERE grade_level IN (3,4) AND status = 1 ORDER BY grade_level, user_name";
                                } elseif ($grade_level == 3) {
                                    $users_sql = "SELECT user_id, user_name, grade_level, user_role FROM employees WHERE grade_level = 4 AND status = 1 ORDER BY user_name";
                                }
                                
                                if (isset($users_sql)) {
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
                                <?php endwhile; } ?>
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
            // Initialize DataTable
            if ($('#leadsTable tbody tr').length > 0) {
                $('#leadsTable').DataTable({
                    pageLength: 10,
                    order: [[8, 'desc']],
                    language: {
                        search: "Search leads:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ leads"
                    }
                });
            }
        });
        
        function viewLeadDetails(leadId) {
            $('#leadDetailsModal').modal('show');
            $('#leadDetailsContent').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
            
            $.ajax({
                url: 'ajax/get-lead-details.php',
                type: 'GET',
                data: { id: leadId, type: 'fresh' },
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
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="border-bottom pb-2 mb-3">Company Information</h6>
                                    <table class="table table-sm">
                                        <tr><th width="40%">Company Name:</th><td>${escapeHtml(lead.cust_company) || '—'}</td></tr>
                                        <tr><th>Website:</th><td>${lead.website ? '<a href="http://'+escapeHtml(lead.website)+'" target="_blank">'+escapeHtml(lead.website)+'</a>' : '—'}</td></tr>
                                        <tr><th>Address:</th><td>${escapeHtml(lead.cust_address) || '—'}</td></tr>
                                        <tr><th>Service:</th><td>${escapeHtml(lead.service) || '—'}</td></tr>
                                        <tr><th>Source:</th><td>${escapeHtml(lead.source)}</td></tr>
                                    </table>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="mb-3">Allocation Details</h6>
                                    <table class="table table-sm">
                                        <tr><th width="30%">Assigned To:</th><td>${escapeHtml(lead.assigned_to_name)} (Level ${lead.assigned_grade})</td></tr>
                                        <tr><th>Allocated By:</th><td>${escapeHtml(lead.allocated_by_name) || 'System'}</td></tr>
                                        <tr><th>Allocated Date:</th><td>${new Date(lead.allocated_at).toLocaleDateString()}</td></tr>
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
        
        function reallocateLead(leadId) {
            $('#reallocate_lead_id').val(leadId);
            $('#reallocateModal').modal('show');
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
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>