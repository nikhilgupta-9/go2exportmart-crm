<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
    exit;
}

// Check if user is Team Lead (Grade Level 3)
if ($_SESSION['grade_level'] != 3) {
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

// Get all executives under this team lead (Grade Level 4)
$executives_sql = "SELECT * FROM employees WHERE Reporting = '$user_id' AND grade_level = 4 AND status = 1 ORDER BY user_name";
$executives_result = mysqli_query($conn, $executives_sql);
$total_executives = mysqli_num_rows($executives_result);

// Get statistics for team
$current_month = date('m');
$current_year = date('Y');

// Total leads in team
$total_leads_sql = "SELECT COUNT(*) as total FROM customerleads WHERE reporting = '$user_id' AND (matelize = '0' OR matelize IS NULL)";
$total_leads_result = mysqli_query($conn, $total_leads_sql);
$total_leads = mysqli_fetch_assoc($total_leads_result)['total'];

// Fresh leads
$fresh_leads_sql = "SELECT COUNT(*) as total FROM customerleads WHERE reporting = '$user_id' AND status = 'Fresh Lead' AND (matelize = '0' OR matelize IS NULL)";
$fresh_leads_result = mysqli_query($conn, $fresh_leads_sql);
$fresh_leads = mysqli_fetch_assoc($fresh_leads_result)['total'];

// Follow up leads
$followup_sql = "SELECT COUNT(*) as total FROM customerleads WHERE reporting = '$user_id' AND status = 'Follow Up' AND (matelize = '0' OR matelize IS NULL)";
$followup_result = mysqli_query($conn, $followup_sql);
$followup_leads = mysqli_fetch_assoc($followup_result)['total'];

// Positive leads
$positive_sql = "SELECT COUNT(*) as total FROM customerleads WHERE reporting = '$user_id' AND status = 'Positive' AND (matelize = '0' OR matelize IS NULL)";
$positive_result = mysqli_query($conn, $positive_sql);
$positive_leads = mysqli_fetch_assoc($positive_result)['total'];

// Committed leads
$committed_sql = "SELECT COUNT(*) as total FROM customerleads WHERE reporting = '$user_id' AND status = 'Committed' AND (matelize = '0' OR matelize IS NULL)";
$committed_result = mysqli_query($conn, $committed_sql);
$committed_leads = mysqli_fetch_assoc($committed_result)['total'];

// Matelized leads
$matelized_sql = "SELECT COUNT(*) as total FROM customerleads WHERE reporting = '$user_id' AND matelize = '1'";
$matelized_result = mysqli_query($conn, $matelized_sql);
$matelized_leads = mysqli_fetch_assoc($matelized_result)['total'];

// Total revenue
$revenue_sql = "SELECT SUM(amount) as total FROM customerleads WHERE reporting = '$user_id' AND matelize = '1'";
$revenue_result = mysqli_query($conn, $revenue_sql);
$total_revenue = mysqli_fetch_assoc($revenue_result)['total'] ?? 0;

// This month's performance
$this_month_sql = "SELECT COUNT(*) as total FROM customerleads WHERE reporting = '$user_id' AND MONTH(todaydate) = '$current_month' AND YEAR(todaydate) = '$current_year' AND (matelize = '0' OR matelize IS NULL)";
$this_month_result = mysqli_query($conn, $this_month_sql);
$this_month_leads = mysqli_fetch_assoc($this_month_result)['total'];

// Executive performance data
$executive_performance = [];
while ($exec = mysqli_fetch_assoc($executives_result)) {
    $exec_leads = $conn->query("SELECT COUNT(*) as total FROM customerleads WHERE assigned_to = '{$exec['user_id']}' AND (matelize = '0' OR matelize IS NULL)")->fetch_assoc()['total'];
    $exec_matelized = $conn->query("SELECT COUNT(*) as total FROM customerleads WHERE assigned_to = '{$exec['user_id']}' AND matelize = '1'")->fetch_assoc()['total'];
    $exec_revenue = $conn->query("SELECT SUM(amount) as total FROM customerleads WHERE assigned_to = '{$exec['user_id']}' AND matelize = '1'")->fetch_assoc()['total'] ?? 0;
    
    $executive_performance[] = [
        'id' => $exec['user_id'],
        'name' => $exec['user_name'],
        'total_leads' => $exec_leads,
        'matelized' => $exec_matelized,
        'revenue' => $exec_revenue,
        'conversion_rate' => $exec_leads > 0 ? round(($exec_matelized / $exec_leads) * 100, 1) : 0
    ];
}

// Today's pending follow-ups
$today_date = date('Y-m-d');
$pending_followups_sql = "SELECT c.*, e.user_name as executive_name 
                          FROM customerleads c 
                          LEFT JOIN employees e ON c.assigned_to = e.user_id 
                          WHERE c.reporting = '$user_id' AND c.status = 'Follow Up' 
                          AND DATE(c.todaydate) = '$today_date' 
                          ORDER BY c.todaydate ASC LIMIT 10";
$pending_followups_result = mysqli_query($conn, $pending_followups_sql);

// Weekly performance data
$weekly_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_name = date('D', strtotime($date));
    $day_sql = "SELECT COUNT(*) as total FROM customerleads WHERE reporting = '$user_id' AND DATE(todaydate) = '$date' AND (matelize = '0' OR matelize IS NULL)";
    $day_result = mysqli_query($conn, $day_sql);
    $weekly_data[] = [
        'day' => $day_name,
        'count' => mysqli_fetch_assoc($day_result)['total']
    ];
}

include_once "includes/link.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Team Lead Dashboard | GO2EXPORT MART</title>
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
        
        .welcome-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 16px;
            padding: 25px;
            color: white;
            margin-bottom: 25px;
        }
        
        .executive-card {
            background: white;
            border-radius: 16px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        
        .executive-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .followup-item {
            padding: 12px;
            border-bottom: 1px solid #eef2f6;
            transition: background 0.3s;
        }
        
        .followup-item:hover {
            background: #f8f9fa;
        }
        
        .followup-item:last-child {
            border-bottom: none;
        }
        
        .progress-sm {
            height: 6px;
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
                        <h4 class="mb-1">Team Lead Dashboard</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Team Lead Dashboard</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
                        <div id="reportrange" class="reportrange-picker d-flex align-items-center shadow">
                            <i class="ti ti-calendar-due text-dark fs-14 me-1"></i>
                            <span class="reportrange-picker-field"><?php echo date('d M Y'); ?></span>
                        </div>
                        <button onclick="window.location.reload()" class="btn btn-icon btn-outline-light shadow">
                            <i class="ti ti-refresh"></i>
                        </button>
                    </div>
                </div>

                <!-- Welcome Card -->
                <div class="welcome-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2">Welcome, <?php echo htmlspecialchars($user_data['user_name']); ?>!</h2>
                            <p class="mb-0 opacity-75">Lead your team to success. Track performance and manage follow-ups</p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <div class="d-flex gap-2 justify-content-md-end">
                                <span class="badge bg-light text-dark px-3 py-2">
                                    <i class="ti ti-users"></i> <?php echo $total_executives; ?> Team Members
                                </span>
                                <span class="badge bg-light text-dark px-3 py-2">
                                    <i class="ti ti-chart-line"></i> <?php echo $this_month_leads; ?> This Month
                                </span>
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
                                    <div class="stat-value"><?php echo $total_leads; ?></div>
                                    <p class="stat-label">Total Team Leads</p>
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
                                    <div class="stat-value"><?php echo $fresh_leads; ?></div>
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
                                    <div class="stat-value"><?php echo $followup_leads; ?></div>
                                    <p class="stat-label">Pending Follow-ups</p>
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
                                    <div class="stat-value"><?php echo $matelized_leads; ?></div>
                                    <p class="stat-label">Matelized Leads</p>
                                </div>
                                <div class="stat-icon bg-success bg-opacity-10">
                                    <i class="ti ti-star text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Second Row Stats -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $positive_leads; ?></div>
                                    <p class="stat-label">Positive Responses</p>
                                </div>
                                <div class="stat-icon bg-success bg-opacity-10">
                                    <i class="ti ti-thumb-up text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $committed_leads; ?></div>
                                    <p class="stat-label">Committed Leads</p>
                                </div>
                                <div class="stat-icon bg-primary bg-opacity-10">
                                    <i class="ti ti-check text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value">₹<?php echo number_format($total_revenue, 2); ?></div>
                                    <p class="stat-label">Team Revenue</p>
                                </div>
                                <div class="stat-icon bg-warning bg-opacity-10">
                                    <i class="ti ti-currency-rupee text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Team Members Performance -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-bottom">
                        <h5 class="mb-0"><i class="ti ti-user-star me-2"></i>Team Members Performance</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Executive Name</th>
                                        <th>Total Leads</th>
                                        <th>Matelized</th>
                                        <th>Revenue (₹)</th>
                                        <th>Conversion Rate</th>
                                        <th>Actions</th>
                                    </thead>
                                <tbody>
                                    <?php foreach ($executive_performance as $exec): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-primary bg-opacity-10 me-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                    <span class="text-primary"><?php echo substr($exec['name'], 0, 2); ?></span>
                                                </div>
                                                <?php echo htmlspecialchars($exec['name']); ?>
                                            </div>
                                         </td>
                                        <td class="fw-bold"><?php echo $exec['total_leads']; ?></td>
                                        <td class="text-success"><?php echo $exec['matelized']; ?></td>
                                        <td class="fw-bold">₹<?php echo number_format($exec['revenue'], 2); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress progress-sm flex-grow-1">
                                                    <div class="progress-bar bg-<?php echo $exec['conversion_rate'] >= 50 ? 'success' : ($exec['conversion_rate'] >= 25 ? 'warning' : 'danger'); ?>" style="width: <?php echo $exec['conversion_rate']; ?>%"></div>
                                                </div>
                                                <span class="small"><?php echo $exec['conversion_rate']; ?>%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="executive-details.php?exec_id=<?php echo $exec['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="ti ti-eye"></i> View
                                            </a>
                                        </td>
                                     </tr>
                                    <?php endforeach; ?>
                                </tbody>
                             </table>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Weekly Performance Chart -->
                    <div class="col-xl-6">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-transparent border-bottom">
                                <h5 class="mb-0"><i class="ti ti-chart-line me-2"></i>Weekly Performance</h5>
                            </div>
                            <div class="card-body">
                                <div id="weeklyChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Pending Follow-ups -->
                    <div class="col-xl-6">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-transparent border-bottom">
                                <h5 class="mb-0"><i class="ti ti-alarm me-2"></i>Today's Pending Follow-ups</h5>
                            </div>
                            <div class="card-body">
                                <?php if (mysqli_num_rows($pending_followups_result) > 0): ?>
                                    <?php while($followup = mysqli_fetch_assoc($pending_followups_result)): ?>
                                    <div class="followup-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($followup['customer_name']); ?></h6>
                                            <p class="mb-0 text-muted small">
                                                <i class="ti ti-user"></i> <?php echo htmlspecialchars($followup['executive_name']); ?>
                                                <span class="mx-2">•</span>
                                                <i class="ti ti-phone"></i> <?php echo htmlspecialchars($followup['customer_num']); ?>
                                            </p>
                                        </div>
                                        <a href="comment.php?customerId=<?php echo $followup['sno']; ?>" class="btn btn-sm btn-warning">
                                            <i class="ti ti-message-circle"></i> Follow Up
                                        </a>
                                    </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="ti ti-check-circle fs-48 text-success"></i>
                                        <p class="mt-2 text-muted">No pending follow-ups for today!</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Team Activities -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-bottom">
                        <h5 class="mb-0"><i class="ti ti-history me-2"></i>Recent Team Activities</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Executive</th>
                                        <th>Customer</th>
                                        <th>Status</th>
                                        <th>Amount (₹)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $activities_sql = "SELECT c.*, e.user_name as executive_name
                                                      FROM customerleads c
                                                      LEFT JOIN employees e ON c.assigned_to = e.user_id
                                                      WHERE c.reporting = '$user_id'
                                                      ORDER BY c.todaydate DESC LIMIT 15";
                                    $activities_result = mysqli_query($conn, $activities_sql);
                                    
                                    if (mysqli_num_rows($activities_result) > 0):
                                        while($activity = mysqli_fetch_assoc($activities_result)):
                                    ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($activity['todaydate'])); ?></td>
                                        <td><?php echo htmlspecialchars($activity['executive_name']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['customer_name']); ?> (<?php echo $activity['customer_num']; ?>)</td>
                                        <td>
                                            <span class="badge bg-<?php echo $activity['matelize'] == 1 ? 'success' : ($activity['status'] == 'Follow Up' ? 'warning' : 'info'); ?>">
                                                <?php echo $activity['matelize'] == 1 ? 'Matelized' : $activity['status']; ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold">₹<?php echo number_format($activity['amount'] ?? 0, 2); ?></td>
                                    </tr>
                                    <?php endwhile; else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">No recent activities found</td>
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

    <?php include_once "includes/footer-link.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        $(document).ready(function() {
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
            
            // Weekly Chart
            var weeklyData = <?php echo json_encode($weekly_data); ?>;
            var days = weeklyData.map(item => item.day);
            var counts = weeklyData.map(item => item.count);
            
            var options = {
                series: [{
                    name: 'Leads',
                    data: counts
                }],
                chart: {
                    type: 'line',
                    height: 300,
                    toolbar: {
                        show: true
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                markers: {
                    size: 5,
                    colors: ['#f5576c'],
                    strokeColors: '#fff',
                    strokeWidth: 2
                },
                xaxis: {
                    categories: days,
                    title: {
                        text: 'Day of Week'
                    }
                },
                yaxis: {
                    title: {
                        text: 'Number of Leads'
                    }
                },
                colors: ['#f5576c'],
                grid: {
                    borderColor: '#eef2f6'
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val + " leads";
                        }
                    }
                }
            };
            
            var chart = new ApexCharts(document.querySelector("#weeklyChart"), options);
            chart.render();
        });
    </script>
    
    <style>
        .avatar-sm {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
    </style>
</body>
</html>