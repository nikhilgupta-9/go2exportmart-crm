<?php
session_start();

// Check if user is logged in and is admin (Grade Level 1)
if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
    exit();
}

if ($_SESSION['grade_level'] != 1) {
    header('location: dashboard.php');
    exit();
}

include_once "partials/_dbconnect.php";
include_once "partials/_header.php";

$user_id = $_SESSION['user_id'];
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$selected_user = isset($_GET['user_id']) ? $_GET['user_id'] : '';
$selected_type = isset($_GET['type']) ? $_GET['type'] : 'all'; // all, executive, teamlead

// Get date range for filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Fetch all executives (Grade Level 4)
$executives_sql = "SELECT emp_id, user_id, user_name, department, user_role, Reporting 
                   FROM employees 
                   WHERE grade_level = 4 AND status = 1 
                   ORDER BY user_name";
$executives_result = $conn->query($executives_sql);

// Fetch all team leads (Grade Level 3)
$teamleads_sql = "SELECT emp_id, user_id, user_name, department, user_role 
                  FROM employees 
                  WHERE grade_level = 3 AND status = 1 
                  ORDER BY user_name";
$teamleads_result = $conn->query($teamleads_sql);

// Build query for daily activity
$where_conditions = [];
$where_conditions[] = "DATE(c.todaydate) BETWEEN '$start_date' AND '$end_date'";

if ($selected_user && $selected_user != 'all') {
    if ($selected_type == 'executive') {
        $where_conditions[] = "c.assigned_to = '$selected_user'";
    } elseif ($selected_type == 'teamlead') {
        $where_conditions[] = "c.reporting = '$selected_user'";
    }
}

$where_clause = implode(" AND ", $where_conditions);

// Daily activity query
$daily_activity_sql = "SELECT 
    DATE(c.todaydate) as activity_date,
    c.assigned_to,
    e.user_name as executive_name,
    e.user_id as executive_id,
    COUNT(*) as total_leads,
    SUM(CASE WHEN c.status = 'Fresh Lead' THEN 1 ELSE 0 END) as fresh_leads,
    SUM(CASE WHEN c.status = 'Follow Up' THEN 1 ELSE 0 END) as follow_up_leads,
    SUM(CASE WHEN c.status = 'Positive' THEN 1 ELSE 0 END) as positive_leads,
    SUM(CASE WHEN c.status = 'Committed' THEN 1 ELSE 0 END) as committed_leads,
    SUM(CASE WHEN c.status = 'Not Interested' THEN 1 ELSE 0 END) as not_interested_leads,
    SUM(CASE WHEN c.matelize = '1' THEN 1 ELSE 0 END) as matelized_leads,
    SUM(c.amount) as total_amount
FROM customerleads c
LEFT JOIN employees e ON c.assigned_to = e.user_id
WHERE $where_clause
GROUP BY DATE(c.todaydate), c.assigned_to
ORDER BY activity_date DESC, total_leads DESC";

$daily_activity_result = $conn->query($daily_activity_sql);

// Summary statistics
$summary_sql = "SELECT 
    COUNT(DISTINCT c.assigned_to) as active_executives,
    COUNT(*) as total_leads,
    SUM(CASE WHEN c.status = 'Fresh Lead' THEN 1 ELSE 0 END) as total_fresh,
    SUM(CASE WHEN c.status = 'Follow Up' THEN 1 ELSE 0 END) as total_followup,
    SUM(CASE WHEN c.status = 'Positive' THEN 1 ELSE 0 END) as total_positive,
    SUM(CASE WHEN c.status = 'Committed' THEN 1 ELSE 0 END) as total_committed,
    SUM(CASE WHEN c.matelize = '1' THEN 1 ELSE 0 END) as total_matelized,
    SUM(c.amount) as total_revenue
FROM customerleads c
WHERE $where_clause";
$summary_result = $conn->query($summary_sql);
$summary = $summary_result->fetch_assoc();

// Get team-wise summary
$team_summary_sql = "SELECT 
    e.Reporting as team_lead_id,
    (SELECT user_name FROM employees WHERE user_id = e.Reporting) as team_lead_name,
    COUNT(*) as team_total_leads,
    SUM(CASE WHEN c.status = 'Fresh Lead' THEN 1 ELSE 0 END) as team_fresh,
    SUM(CASE WHEN c.status = 'Follow Up' THEN 1 ELSE 0 END) as team_followup,
    SUM(CASE WHEN c.status = 'Positive' THEN 1 ELSE 0 END) as team_positive,
    SUM(CASE WHEN c.status = 'Committed' THEN 1 ELSE 0 END) as team_committed,
    SUM(CASE WHEN c.matelize = '1' THEN 1 ELSE 0 END) as team_matelized,
    SUM(c.amount) as team_revenue
FROM customerleads c
LEFT JOIN employees e ON c.assigned_to = e.user_id
WHERE $where_clause AND e.Reporting IS NOT NULL
GROUP BY e.Reporting
ORDER BY team_total_leads DESC";
$team_summary_result = $conn->query($team_summary_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Daily Activity Report | GO2EXPORT MART</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once "includes/link.php"; ?>
    
    <style>
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
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 13px;
            margin-bottom: 0;
        }
        
        .filter-section {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .activity-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        
        .activity-card:hover {
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .activity-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 20px;
        }
        
        .activity-date {
            font-size: 18px;
            font-weight: bold;
        }
        
        .executive-row {
            padding: 12px 20px;
            border-bottom: 1px solid #eef2f6;
            transition: background 0.3s;
        }
        
        .executive-row:hover {
            background: #f8f9fa;
        }
        
        .executive-row:last-child {
            border-bottom: none;
        }
        
        .rank-badge {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 12px;
        }
        
        .rank-1 { background: #ffd700; color: #856404; }
        .rank-2 { background: #c0c0c0; color: #495057; }
        .rank-3 { background: #cd7f32; color: white; }
        
        .progress-sm {
            height: 6px;
        }
        
        .team-card {
            background: white;
            border-radius: 16px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .team-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #eef2f6;
        }
        
        @media (max-width: 768px) {
            .stat-card {
                margin-bottom: 15px;
            }
            .filter-section .row > div {
                margin-bottom: 10px;
            }
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.375rem 0.75rem;
            margin: 0 2px;
            border-radius: 8px;
        }
        
        .table > :not(caption) > * > * {
            padding: 12px 15px;
            vertical-align: middle;
        }
        
        .badge-count {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 12px;
        }
        
        .badge-fresh { background: #e3f2fd; color: #1976d2; }
        .badge-followup { background: #fff3e0; color: #f57c00; }
        .badge-positive { background: #e8f5e9; color: #388e3c; }
        .badge-committed { background: #e1f5fe; color: #0288d1; }
        .badge-matelized { background: #f3e5f5; color: #7b1fa2; }
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
                        <h4 class="mb-1">Daily Activity Report</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="reports.php">Reports</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Daily Activity</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
                        <button onclick="window.print()" class="btn btn-primary">
                            <i class="ti ti-printer me-2"></i>Print Report
                        </button>
                        <button onclick="exportToExcel()" class="btn btn-success">
                            <i class="ti ti-file-spreadsheet me-2"></i>Export to Excel
                        </button>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="filter-section">
                    <form method="GET" action="" class="row align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">User Type</label>
                            <select name="type" class="form-select" id="userType">
                                <option value="all" <?php echo $selected_type == 'all' ? 'selected' : ''; ?>>All Users</option>
                                <option value="executive" <?php echo $selected_type == 'executive' ? 'selected' : ''; ?>>Executives (Grade 4)</option>
                                <option value="teamlead" <?php echo $selected_type == 'teamlead' ? 'selected' : ''; ?>>Team Leads (Grade 3)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Select User</label>
                            <select name="user_id" class="form-select" id="userSelect">
                                <option value="all">All Users</option>
                                <?php if ($selected_type != 'teamlead'): ?>
                                    <optgroup label="Executives (Grade 4)">
                                        <?php 
                                        $execs = $conn->query("SELECT user_id, user_name FROM employees WHERE grade_level = 4 AND status = 1 ORDER BY user_name");
                                        while($exec = $execs->fetch_assoc()): ?>
                                            <option value="<?php echo $exec['user_id']; ?>" <?php echo $selected_user == $exec['user_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($exec['user_name']); ?> (Executive)
                                            </option>
                                        <?php endwhile; ?>
                                    </optgroup>
                                <?php endif; ?>
                                <?php if ($selected_type != 'executive'): ?>
                                    <optgroup label="Team Leads (Grade 3)">
                                        <?php 
                                        $leads = $conn->query("SELECT user_id, user_name FROM employees WHERE grade_level = 3 AND status = 1 ORDER BY user_name");
                                        while($lead = $leads->fetch_assoc()): ?>
                                            <option value="<?php echo $lead['user_id']; ?>" <?php echo $selected_user == $lead['user_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($lead['user_name']); ?> (Team Lead)
                                            </option>
                                        <?php endwhile; ?>
                                    </optgroup>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-12 mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-filter me-2"></i>Apply Filter
                            </button>
                            <a href="daily-activity.php" class="btn btn-secondary">
                                <i class="ti ti-refresh me-2"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo number_format($summary['total_leads'] ?? 0); ?></div>
                                    <p class="stat-label">Total Leads</p>
                                </div>
                                <div class="stat-icon bg-primary bg-opacity-10">
                                    <i class="ti ti-chart-arcs text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo number_format($summary['total_fresh'] ?? 0); ?></div>
                                    <p class="stat-label">Fresh Leads</p>
                                </div>
                                <div class="stat-icon bg-info bg-opacity-10">
                                    <i class="ti ti-droplet text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo number_format($summary['total_matelized'] ?? 0); ?></div>
                                    <p class="stat-label">Matelized</p>
                                </div>
                                <div class="stat-icon bg-success bg-opacity-10">
                                    <i class="ti ti-star text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value">₹<?php echo number_format($summary['total_revenue'] ?? 0, 2); ?></div>
                                    <p class="stat-label">Total Revenue</p>
                                </div>
                                <div class="stat-icon bg-warning bg-opacity-10">
                                    <i class="ti ti-currency-rupee text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Team Summary Section -->
                <?php if ($team_summary_result->num_rows > 0 && $selected_type != 'executive'): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-bottom">
                        <h5 class="mb-0"><i class="ti ti-users-group me-2"></i>Team Wise Summary</h5>
                        <small>Performance by team lead</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Team Lead</th>
                                        <th>Total Leads</th>
                                        <th>Fresh</th>
                                        <th>Follow Up</th>
                                        <th>Positive</th>
                                        <th>Committed</th>
                                        <th>Matelized</th>
                                        <th>Revenue (₹)</th>
                                        <th>Conversion Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($team = $team_summary_result->fetch_assoc()): 
                                        $total = $team['team_total_leads'];
                                        $conversion_rate = $total > 0 ? round(($team['team_matelized'] / $total) * 100, 1) : 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm rounded-circle bg-primary bg-opacity-10 me-2">
                                                    <span class="text-primary"><?php echo substr($team['team_lead_name'], 0, 2); ?></span>
                                                </div>
                                                <?php echo htmlspecialchars($team['team_lead_name']); ?>
                                            </div>
                                        </td>
                                        <td><span class="fw-bold"><?php echo $team['team_total_leads']; ?></span></td>
                                        <td><?php echo $team['team_fresh']; ?></td>
                                        <td><?php echo $team['team_followup']; ?></td>
                                        <td><?php echo $team['team_positive']; ?></td>
                                        <td><?php echo $team['team_committed']; ?></td>
                                        <td><?php echo $team['team_matelized']; ?></td>
                                        <td class="fw-bold text-success">₹<?php echo number_format($team['team_revenue'], 2); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress progress-sm flex-grow-1">
                                                    <div class="progress-bar bg-success" style="width: <?php echo $conversion_rate; ?>%"></div>
                                                </div>
                                                <span class="small"><?php echo $conversion_rate; ?>%</span>
                                            </div>
                                         </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                             </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Daily Activity Table -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-bottom">
                        <h5 class="mb-0"><i class="ti ti-calendar me-2"></i>Daily Activity Log</h5>
                        <small>Detailed breakdown by date and executive</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="activityTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Executive Name</th>
                                        <th>Total Leads</th>
                                        <th>Fresh</th>
                                        <th>Follow Up</th>
                                        <th>Positive</th>
                                        <th>Committed</th>
                                        <th>Not Interested</th>
                                        <th>Matelized</th>
                                        <th>Amount (₹)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($daily_activity_result->num_rows > 0): ?>
                                        <?php 
                                        $current_date = '';
                                        $row_span = 0;
                                        $date_rows = [];
                                        
                                        // Group by date
                                        while($row = $daily_activity_result->fetch_assoc()) {
                                            $date_rows[$row['activity_date']][] = $row;
                                        }
                                        
                                        foreach($date_rows as $date => $executives):
                                            $date_count = count($executives);
                                        ?>
                                            <?php foreach($executives as $index => $row): ?>
                                                <tr>
                                                    <?php if($index == 0): ?>
                                                        <td rowspan="<?php echo $date_count; ?>" class="align-middle bg-light">
                                                            <strong><?php echo date('d M Y', strtotime($date)); ?></strong>
                                                            <br><small class="text-muted"><?php echo date('l', strtotime($date)); ?></small>
                                                        </td>
                                                    <?php endif; ?>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar avatar-sm rounded-circle bg-primary bg-opacity-10 me-2">
                                                                <span class="text-primary"><?php echo substr($row['executive_name'], 0, 2); ?></span>
                                                            </div>
                                                            <?php echo htmlspecialchars($row['executive_name']); ?>
                                                            <br><small class="text-muted"><?php echo $row['executive_id']; ?></small>
                                                        </div>
                                                    </td>
                                                    <td class="fw-bold"><?php echo $row['total_leads']; ?></td>
                                                    <td><span class="badge badge-fresh"><?php echo $row['fresh_leads']; ?></span></td>
                                                    <td><span class="badge badge-followup"><?php echo $row['follow_up_leads']; ?></span></td>
                                                    <td><span class="badge badge-positive"><?php echo $row['positive_leads']; ?></span></td>
                                                    <td><span class="badge badge-committed"><?php echo $row['committed_leads']; ?></span></td>
                                                    <td><?php echo $row['not_interested_leads']; ?></td>
                                                    <td><span class="badge badge-matelized"><?php echo $row['matelized_leads']; ?></span></td>
                                                    <td class="text-success fw-bold">₹<?php echo number_format($row['total_amount'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="10" class="text-center py-5">
                                                <i class="ti ti-chart-bar-off fs-48 text-muted"></i>
                                                <h5 class="mt-3">No Data Found</h5>
                                                <p class="text-muted">No leads found for the selected date range</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr class="fw-bold">
                                        <td colspan="2" class="text-end">Total:</td>
                                        <td><?php echo number_format($summary['total_leads'] ?? 0); ?></td>
                                        <td><?php echo number_format($summary['total_fresh'] ?? 0); ?></td>
                                        <td><?php echo number_format($summary['total_followup'] ?? 0); ?></td>
                                        <td><?php echo number_format($summary['total_positive'] ?? 0); ?></td>
                                        <td><?php echo number_format($summary['total_committed'] ?? 0); ?></td>
                                        <td>-</td>
                                        <td><?php echo number_format($summary['total_matelized'] ?? 0); ?></td>
                                        <td>₹<?php echo number_format($summary['total_revenue'] ?? 0, 2); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <?php include_once "includes/footer-link.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            if ($('#activityTable').length) {
                $('#activityTable').DataTable({
                    pageLength: 25,
                    order: [[0, 'desc']],
                    language: {
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        infoEmpty: "No entries found",
                        emptyTable: "No data available"
                    }
                });
            }
            
            // Update user select based on user type
            $('#userType').on('change', function() {
                var type = $(this).val();
                window.location.href = 'daily-activity.php?type=' + type + '&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>';
            });
        });
        
        function exportToExcel() {
            var table = document.getElementById('activityTable');
            var wb = XLSX.utils.book_new();
            var ws = XLSX.utils.table_to_sheet(table, { raw: true });
            XLSX.utils.book_append_sheet(wb, ws, 'Daily_Activity_Report');
            XLSX.writeFile(wb, 'daily_activity_report_' + new Date().toISOString().slice(0,19) + '.xlsx');
        }
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>