<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
    exit;
}

// Check if user is sales executive (grade level 4)
if ($_SESSION['grade_level'] != 4) {
    header('location: dashboard.php');
    exit;
}

include_once "partials/_dbconnect.php";

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Fetch user details
$user_sql = "SELECT * FROM employees WHERE user_id = '$user_id'";
$user_result = mysqli_query($conn, $user_sql);
$user_data = mysqli_fetch_assoc($user_result);

// Get statistics for the sales executive
// Total leads assigned
$total_leads_sql = "SELECT COUNT(*) as total FROM customerleads WHERE assigned_to = '$user_id' AND matelize != '1'";
$total_leads_result = mysqli_query($conn, $total_leads_sql);
$total_leads = mysqli_fetch_assoc($total_leads_result)['total'];

// Fresh leads
$fresh_leads_sql = "SELECT COUNT(*) as total FROM customerleads WHERE assigned_to = '$user_id' AND status = 'Fresh Lead' AND matelize != '1'";
$fresh_leads_result = mysqli_query($conn, $fresh_leads_sql);
$fresh_leads = mysqli_fetch_assoc($fresh_leads_result)['total'];

// Follow up leads
$followup_sql = "SELECT COUNT(*) as total FROM customerleads WHERE assigned_to = '$user_id' AND status = 'Follow Up' AND matelize != '1'";
$followup_result = mysqli_query($conn, $followup_sql);
$followup_leads = mysqli_fetch_assoc($followup_result)['total'];

// Positive leads
$positive_sql = "SELECT COUNT(*) as total FROM customerleads WHERE assigned_to = '$user_id' AND status = 'Positive' AND matelize != '1'";
$positive_result = mysqli_query($conn, $positive_sql);
$positive_leads = mysqli_fetch_assoc($positive_result)['total'];

// Committed leads
$committed_sql = "SELECT COUNT(*) as total FROM customerleads WHERE assigned_to = '$user_id' AND status = 'Committed' AND matelize != '1'";
$committed_result = mysqli_query($conn, $committed_sql);
$committed_leads = mysqli_fetch_assoc($committed_result)['total'];

// Not interested leads
$not_interested_sql = "SELECT COUNT(*) as total FROM customerleads WHERE assigned_to = '$user_id' AND status = 'Not Interested' AND matelize != '1'";
$not_interested_result = mysqli_query($conn, $not_interested_sql);
$not_interested_leads = mysqli_fetch_assoc($not_interested_result)['total'];

// Matelized leads
$matelized_sql = "SELECT COUNT(*) as total FROM customerleads WHERE assigned_to = '$user_id' AND matelize = '1'";
$matelized_result = mysqli_query($conn, $matelized_sql);
$matelized_leads = mysqli_fetch_assoc($matelized_result)['total'];

// This month's leads
$current_month = date('m');
$current_year = date('Y');
$this_month_sql = "SELECT COUNT(*) as total FROM customerleads WHERE assigned_to = '$user_id' AND MONTH(todaydate) = '$current_month' AND YEAR(todaydate) = '$current_year'";
$this_month_result = mysqli_query($conn, $this_month_sql);
$this_month_leads = mysqli_fetch_assoc($this_month_result)['total'];

// Today's leads
$today_date = date('Y-m-d');
$today_sql = "SELECT COUNT(*) as total FROM customerleads WHERE assigned_to = '$user_id' AND DATE(todaydate) = '$today_date'";
$today_result = mysqli_query($conn, $today_sql);
$today_leads = mysqli_fetch_assoc($today_result)['total'];

// Get recent leads (last 5)
$recent_leads_sql = "SELECT * FROM customerleads WHERE assigned_to = '$user_id' ORDER BY sno DESC LIMIT 5";
$recent_leads_result = mysqli_query($conn, $recent_leads_sql);

// Get leads by status for chart
$status_data = [
    'Fresh Lead' => $fresh_leads,
    'Follow Up' => $followup_leads,
    'Positive' => $positive_leads,
    'Committed' => $committed_leads,
    'Not Interested' => $not_interested_leads,
    'Matelized' => $matelized_leads
];

// Calculate conversion rate (Committed + Matelized) / Total Leads * 100
$converted = $committed_leads + $matelized_leads;
$conversion_rate = $total_leads > 0 ? round(($converted / $total_leads) * 100, 1) : 0;

// Get monthly data for chart
$monthly_data = [];
for ($i = 1; $i <= 12; $i++) {
    $month_sql = "SELECT COUNT(*) as total FROM customerleads WHERE assigned_to = '$user_id' AND MONTH(todaydate) = '$i' AND YEAR(todaydate) = '$current_year'";
    $month_result = mysqli_query($conn, $month_sql);
    $month_count = mysqli_fetch_assoc($month_result)['total'];
    $monthly_data[] = $month_count;
}

// Get upcoming follow-ups (next 7 days)
$followup_upcoming_sql = "SELECT * FROM customerleads WHERE assigned_to = '$user_id' AND status = 'Follow Up' AND DATE(todaydate) >= '$today_date' AND DATE(todaydate) <= DATE_ADD('$today_date', INTERVAL 7 DAY) ORDER BY todaydate ASC LIMIT 5";
$followup_upcoming_result = mysqli_query($conn, $followup_upcoming_sql);

include_once "includes/link.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Sales Dashboard | GO2EXPORT MART</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sales Executive Dashboard - Track your leads and performance">
    <meta name="author" content="GO2EXPORT MART">
    <?php include_once "includes/link.php"; ?>
    
    <style>
        /* Custom Dashboard Styles */
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
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
        
        .stat-trend {
            font-size: 12px;
            margin-top: 8px;
        }
        
        .trend-up { color: #28a745; }
        .trend-down { color: #dc3545; }
        
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 25px;
            color: white;
            margin-bottom: 25px;
        }
        
        .lead-status-badge {
            padding: 4px 12px;
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
        .status-matelized { background: #f3e5f5; color: #7b1fa2; }
        
        .quick-action-btn {
            padding: 12px;
            border-radius: 12px;
            background: #f8f9fa;
            transition: all 0.3s;
            text-align: center;
        }
        
        .quick-action-btn:hover {
            background: #e9ecef;
            transform: translateY(-3px);
        }
        
        .recent-lead-item {
            padding: 12px;
            border-bottom: 1px solid #eef2f6;
            transition: all 0.3s;
        }
        
        .recent-lead-item:hover {
            background: #f8f9fa;
        }
        
        .recent-lead-item:last-child {
            border-bottom: none;
        }
        
        .chart-container {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .progress-custom {
            height: 8px;
            border-radius: 4px;
        }
        
        @media (max-width: 768px) {
            .stat-value {
                font-size: 22px;
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
                        <h4 class="mb-1">Sales Dashboard</h4>
                        <p class="text-muted mb-0">Welcome back! Track your leads and performance</p>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
                        <div id="reportrange" class="reportrange-picker d-flex align-items-center shadow">
                            <i class="ti ti-calendar-due text-dark fs-14 me-1"></i>
                            <span class="reportrange-picker-field"><?php echo date('d M Y'); ?></span>
                        </div>
                        <button onclick="window.location.reload()" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" title="Refresh">
                            <i class="ti ti-refresh"></i>
                        </button>
                    </div>
                </div>

                <!-- Welcome Card -->
                <div class="welcome-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2">Welcome Back, <?php echo htmlspecialchars($user_data['user_name']); ?>!</h2>
                            <p class="mb-0 opacity-75">Great to see you! You have <?php echo $followup_leads; ?> follow-ups pending today. Keep up the momentum!</p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <div class="d-flex gap-2 justify-content-md-end">
                                <a href="create_lead.php" class="btn btn-light">
                                    <i class="ti ti-plus me-1"></i> New Lead
                                </a>
                                <a href="lead.php?leadType=Follow+Up" class="btn btn-outline-light">
                                    <i class="ti ti-clock me-1"></i> Follow-ups
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-xl-3 col-md-6">
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
                            <div class="stat-trend">
                                <span class="trend-up"><i class="ti ti-arrow-up"></i> +<?php echo $this_month_leads; ?> this month</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $fresh_leads; ?></div>
                                    <p class="stat-label">Fresh Leads</p>
                                </div>
                                <div class="stat-icon bg-success bg-opacity-10">
                                    <i class="ti ti-droplet text-success"></i>
                                </div>
                            </div>
                            <div class="stat-trend">
                                <span>New leads to contact</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $followup_leads; ?></div>
                                    <p class="stat-label">Follow Up</p>
                                </div>
                                <div class="stat-icon bg-warning bg-opacity-10">
                                    <i class="ti ti-clock text-warning"></i>
                                </div>
                            </div>
                            <div class="stat-trend">
                                <span>Pending follow-ups</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $conversion_rate; ?>%</div>
                                    <p class="stat-label">Conversion Rate</p>
                                </div>
                                <div class="stat-icon bg-info bg-opacity-10">
                                    <i class="ti ti-chart-arcs text-info"></i>
                                </div>
                            </div>
                            <div class="stat-trend">
                                <span><?php echo $converted; ?> leads converted</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Secondary Stats Row -->
                <div class="row">
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $positive_leads; ?></div>
                                    <p class="stat-label">Positive Response</p>
                                </div>
                                <div class="stat-icon bg-success bg-opacity-10">
                                    <i class="ti ti-thumb-up text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $committed_leads; ?></div>
                                    <p class="stat-label">Committed</p>
                                </div>
                                <div class="stat-icon bg-primary bg-opacity-10">
                                    <i class="ti ti-check text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $matelized_leads; ?></div>
                                    <p class="stat-label">Matelized</p>
                                </div>
                                <div class="stat-icon bg-info bg-opacity-10">
                                    <i class="ti ti-star text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $today_leads; ?></div>
                                    <p class="stat-label">Today's Activity</p>
                                </div>
                                <div class="stat-icon bg-danger bg-opacity-10">
                                    <i class="ti ti-calendar text-danger"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="mb-3">Quick Actions</h5>
                        <div class="row g-3">
                            <div class="col-md-3 col-6">
                                <a href="create_lead.php" class="quick-action-btn d-block text-decoration-none">
                                    <i class="ti ti-plus-circle fs-32 text-primary mb-2 d-block"></i>
                                    <span class="text-dark">Add New Lead</span>
                                </a>
                            </div>
                            <div class="col-md-3 col-6">
                                <a href="lead.php?leadType=Fresh+Lead" class="quick-action-btn d-block text-decoration-none">
                                    <i class="ti ti-droplet fs-32 text-success mb-2 d-block"></i>
                                    <span class="text-dark">Fresh Leads</span>
                                </a>
                            </div>
                            <div class="col-md-3 col-6">
                                <a href="lead.php?leadType=Follow+Up" class="quick-action-btn d-block text-decoration-none">
                                    <i class="ti ti-clock fs-32 text-warning mb-2 d-block"></i>
                                    <span class="text-dark">Follow-ups</span>
                                </a>
                            </div>
                            <div class="col-md-3 col-6">
                                <a href="matelize.php" class="quick-action-btn d-block text-decoration-none">
                                    <i class="ti ti-star fs-32 text-info mb-2 d-block"></i>
                                    <span class="text-dark">Matelized Leads</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Lead Distribution Chart -->
                    <div class="col-xl-6">
                        <div class="chart-container">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="mb-0">Lead Distribution</h6>
                                <select id="chartType" class="form-select form-select-sm w-auto">
                                    <option value="pie">Pie Chart</option>
                                    <option value="bar">Bar Chart</option>
                                </select>
                            </div>
                            <div id="leadDistributionChart"></div>
                        </div>
                    </div>
                    
                    <!-- Monthly Performance -->
                    <div class="col-xl-6">
                        <div class="chart-container">
                            <h6 class="mb-3">Monthly Performance</h6>
                            <div id="monthlyChart"></div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Leads -->
                    <div class="col-xl-6">
                        <div class="chart-container">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="mb-0">Recent Leads</h6>
                                <a href="lead.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="recent-leads-list">
                                <?php if (mysqli_num_rows($recent_leads_result) > 0): ?>
                                    <?php while($lead = mysqli_fetch_assoc($recent_leads_result)): 
                                        $statusClass = '';
                                        switch($lead['status']) {
                                            case 'Fresh Lead': $statusClass = 'status-fresh'; break;
                                            case 'Follow Up': $statusClass = 'status-followup'; break;
                                            case 'Positive': $statusClass = 'status-positive'; break;
                                            case 'Committed': $statusClass = 'status-committed'; break;
                                            case 'Not Interested': $statusClass = 'status-not-interested'; break;
                                        }
                                    ?>
                                    <div class="recent-lead-item d-flex align-items-center justify-content-between">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($lead['customer_name']); ?></h6>
                                            <p class="mb-0 text-muted small">
                                                <i class="ti ti-phone"></i> <?php echo htmlspecialchars($lead['customer_num']); ?>
                                                <span class="mx-2">•</span>
                                                <i class="ti ti-building"></i> <?php echo htmlspecialchars($lead['cust_company']); ?>
                                            </p>
                                        </div>
                                        <div class="text-end">
                                            <span class="lead-status-badge <?php echo $statusClass; ?> mb-2 d-inline-block">
                                                <?php echo $lead['status']; ?>
                                            </span>
                                            <p class="mb-0 text-muted small">
                                                <?php echo date('d M Y', strtotime($lead['todaydate'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="ti ti-inbox fs-48 text-muted"></i>
                                        <p class="mt-2 text-muted">No leads found</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Upcoming Follow-ups -->
                    <div class="col-xl-6">
                        <div class="chart-container">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="mb-0">Upcoming Follow-ups (Next 7 Days)</h6>
                                <a href="lead.php?leadType=Follow+Up" class="btn btn-sm btn-warning">View All</a>
                            </div>
                            <div class="followup-list">
                                <?php if (mysqli_num_rows($followup_upcoming_result) > 0): ?>
                                    <?php while($followup = mysqli_fetch_assoc($followup_upcoming_result)): ?>
                                    <div class="recent-lead-item d-flex align-items-center justify-content-between">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($followup['customer_name']); ?></h6>
                                            <p class="mb-0 text-muted small">
                                                <i class="ti ti-phone"></i> <?php echo htmlspecialchars($followup['customer_num']); ?>
                                            </p>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-warning text-dark mb-2 d-inline-block">
                                                <i class="ti ti-clock"></i> <?php echo date('d M', strtotime($followup['todaydate'])); ?>
                                            </span>
                                            <a href="comment.php?customerId=<?php echo $followup['sno']; ?>" class="btn btn-sm btn-outline-primary d-block">
                                                <i class="ti ti-message-circle"></i> Follow Up
                                            </a>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="ti ti-calendar-check fs-48 text-muted"></i>
                                        <p class="mt-2 text-muted">No upcoming follow-ups</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Tips -->
                <div class="row">
                    <div class="col-12">
                        <div class="chart-container bg-primary bg-opacity-10">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="mb-2">💡 Pro Tip</h5>
                                    <p class="mb-0">Follow up with leads within 24 hours for better conversion rates. Your current response time is great! Keep maintaining it.</p>
                                </div>
                                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                    <div class="progress progress-custom w-100 mb-2">
                                        <div class="progress-bar bg-success" style="width: <?php echo $conversion_rate; ?>%"></div>
                                    </div>
                                    <small class="text-muted">Conversion Rate: <?php echo $conversion_rate; ?>%</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <?php include_once "includes/footer-link.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        $(document).ready(function() {
            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();
            
            // Date range picker
            if($('#reportrange').length > 0) {
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
            
            // Lead Distribution Chart
            var statusData = <?php echo json_encode(array_values($status_data)); ?>;
            var statusLabels = <?php echo json_encode(array_keys($status_data)); ?>;
            
            var colors = ['#1976d2', '#f57c00', '#388e3c', '#0288d1', '#d32f2f', '#7b1fa2'];
            
            var distributionChart = new ApexCharts(document.querySelector("#leadDistributionChart"), {
                series: statusData,
                chart: {
                    type: 'pie',
                    height: 350,
                    toolbar: {
                        show: true
                    }
                },
                labels: statusLabels,
                colors: colors,
                legend: {
                    position: 'bottom',
                    horizontalAlign: 'center'
                },
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            width: '100%'
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }],
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val + " leads";
                        }
                    }
                }
            });
            
            distributionChart.render();
            
            // Monthly Performance Chart
            var monthlyData = <?php echo json_encode($monthly_data); ?>;
            var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            
            var monthlyChart = new ApexCharts(document.querySelector("#monthlyChart"), {
                series: [{
                    name: 'Leads',
                    data: monthlyData
                }],
                chart: {
                    type: 'bar',
                    height: 350,
                    toolbar: {
                        show: true
                    }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        dataLabels: {
                            position: 'top'
                        },
                        columnWidth: '50%'
                    }
                },
                dataLabels: {
                    enabled: true,
                    offsetY: -20,
                    style: {
                        fontSize: '12px',
                        colors: ["#304758"]
                    }
                },
                xaxis: {
                    categories: months,
                    position: 'bottom',
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    },
                    crosshairs: {
                        fill: {
                            type: 'gradient',
                            gradient: {
                                colorFrom: '#D8E3F0',
                                colorTo: '#BED1E6',
                                stops: [0, 100],
                                opacityFrom: 0.4,
                                opacityTo: 0.5,
                            }
                        }
                    },
                    tooltip: {
                        enabled: true,
                    }
                },
                yaxis: {
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false,
                    },
                    labels: {
                        show: true,
                        formatter: function(val) {
                            return val;
                        }
                    }
                },
                title: {
                    text: 'Monthly Lead Acquisition',
                    floating: true,
                    offsetY: 330,
                    align: 'center',
                    style: {
                        color: '#444'
                    }
                },
                colors: ['#667eea'],
                grid: {
                    row: {
                        colors: ['#f3f3f3', 'transparent'],
                        opacity: 0.5
                    },
                }
            });
            
            monthlyChart.render();
            
            // Chart type toggle
            $('#chartType').change(function() {
                var type = $(this).val();
                if (type === 'pie') {
                    distributionChart.updateOptions({
                        chart: {
                            type: 'pie'
                        }
                    });
                } else {
                    distributionChart.updateOptions({
                        chart: {
                            type: 'bar'
                        },
                        plotOptions: {
                            bar: {
                                borderRadius: 4,
                                horizontal: false,
                                columnWidth: '55%',
                            }
                        },
                        dataLabels: {
                            enabled: true
                        }
                    });
                    distributionChart.updateSeries([{
                        data: statusData
                    }]);
                }
            });
        });
        
        // Auto-refresh every 5 minutes (optional)
        setTimeout(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>