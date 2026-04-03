<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
    exit;
}

// Check if user is Admin (Grade Level 1)
if ($_SESSION['grade_level'] != 1) {
    header('location: dashboard.php');
    exit;
}

include 'partials/_dbconnect.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Fetch user info
$user_info = $conn->query("SELECT * FROM employees WHERE user_id = '$user_id'")->fetch_assoc();

// ==================== STATISTICS ====================

// Employee Statistics
$total_employees = $conn->query("SELECT COUNT(*) as total FROM employees WHERE status = 1")->fetch_assoc()['total'];
$total_executives = $conn->query("SELECT COUNT(*) as total FROM employees WHERE grade_level = 4 AND status = 1")->fetch_assoc()['total'];
$total_team_leads = $conn->query("SELECT COUNT(*) as total FROM employees WHERE grade_level = 3 AND status = 1")->fetch_assoc()['total'];
$total_managers = $conn->query("SELECT COUNT(*) as total FROM employees WHERE grade_level = 2 AND status = 1")->fetch_assoc()['total'];

// Lead Statistics
$total_leads = $conn->query("SELECT COUNT(*) as total FROM customerleads WHERE (matelize = '0' OR matelize IS NULL)")->fetch_assoc()['total'];
$total_fresh = $conn->query("SELECT COUNT(*) as total FROM customerleads WHERE status = 'Fresh Lead' AND (matelize = '0' OR matelize IS NULL)")->fetch_assoc()['total'];
$total_followup = $conn->query("SELECT COUNT(*) as total FROM customerleads WHERE status = 'Follow Up' AND (matelize = '0' OR matelize IS NULL)")->fetch_assoc()['total'];
$total_positive = $conn->query("SELECT COUNT(*) as total FROM customerleads WHERE status = 'Positive' AND (matelize = '0' OR matelize IS NULL)")->fetch_assoc()['total'];
$total_committed = $conn->query("SELECT COUNT(*) as total FROM customerleads WHERE status = 'Committed' AND (matelize = '0' OR matelize IS NULL)")->fetch_assoc()['total'];
$total_not_interested = $conn->query("SELECT COUNT(*) as total FROM customerleads WHERE status = 'Not Interested' AND (matelize = '0' OR matelize IS NULL)")->fetch_assoc()['total'];
$total_matelized = $conn->query("SELECT COUNT(*) as total FROM customerleads WHERE matelize = '1'")->fetch_assoc()['total'];

// Revenue Statistics
$total_revenue = $conn->query("SELECT SUM(amount) as total FROM customerleads WHERE matelize = '1'")->fetch_assoc()['total'] ?? 0;

// Current Month Revenue
$current_month = date('m');
$current_year = date('Y');
$current_month_revenue = $conn->query("SELECT SUM(amount) as total FROM customerleads WHERE matelize = '1' AND MONTH(todaydate) = '$current_month' AND YEAR(todaydate) = '$current_year'")->fetch_assoc()['total'] ?? 0;

// Last Month Revenue
$last_month = date('m', strtotime('-1 month'));
$last_month_revenue = $conn->query("SELECT SUM(amount) as total FROM customerleads WHERE matelize = '1' AND MONTH(todaydate) = '$last_month' AND YEAR(todaydate) = '$current_year'")->fetch_assoc()['total'] ?? 0;

// Revenue Growth Percentage
$revenue_growth = $last_month_revenue > 0 ? round((($current_month_revenue - $last_month_revenue) / $last_month_revenue) * 100, 1) : 0;

// Today's Statistics
$today_date = date('Y-m-d');
$today_leads = $conn->query("SELECT COUNT(*) as total FROM customerleads WHERE DATE(todaydate) = '$today_date' AND (matelize = '0' OR matelize IS NULL)")->fetch_assoc()['total'];
$today_matelized = $conn->query("SELECT COUNT(*) as total FROM customerleads WHERE DATE(todaydate) = '$today_date' AND matelize = '1'")->fetch_assoc()['total'];

// Get monthly lead data for chart
$monthly_leads = [];
$monthly_revenue = [];
for ($i = 1; $i <= 12; $i++) {
    $leads = $conn->query("SELECT COUNT(*) as total FROM customerleads WHERE MONTH(todaydate) = '$i' AND YEAR(todaydate) = '$current_year' AND (matelize = '0' OR matelize IS NULL)")->fetch_assoc()['total'];
    $revenue = $conn->query("SELECT SUM(amount) as total FROM customerleads WHERE MONTH(todaydate) = '$i' AND YEAR(todaydate) = '$current_year' AND matelize = '1'")->fetch_assoc()['total'] ?? 0;
    $monthly_leads[] = $leads;
    $monthly_revenue[] = $revenue;
}

// Get top performing employees
$top_performers = $conn->query("SELECT e.user_name, e.user_id, COUNT(c.sno) as total_leads, SUM(c.amount) as total_revenue 
                                 FROM employees e 
                                 LEFT JOIN customerleads c ON e.user_id = c.assigned_to AND c.matelize = '1'
                                 WHERE e.grade_level = 4 AND e.status = 1
                                 GROUP BY e.user_id 
                                 ORDER BY total_revenue DESC 
                                 LIMIT 5");

// Get department wise performance
$dept_performance = $conn->query("SELECT e.department, COUNT(c.sno) as total_leads, SUM(c.amount) as total_revenue 
                                   FROM employees e 
                                   LEFT JOIN customerleads c ON e.user_id = c.assigned_to AND c.matelize = '1'
                                   WHERE e.status = 1
                                   GROUP BY e.department 
                                   ORDER BY total_revenue DESC");

include_once "includes/link.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Admin Dashboard | GO2EXPORT MART</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once "includes/link.php"; ?>
    
    <style>
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 55px;
            height: 55px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 13px;
            margin-bottom: 0;
        }
        
        .stat-trend {
            font-size: 12px;
            margin-top: 8px;
        }
        
        .trend-up { color: #28a745; }
        .trend-down { color: #dc3545; }
        
        .welcome-card {
            background: linear-gradient(135deg, #e02932ff 0%, #c52115ff 100%);
            border-radius: 20px;
            padding: 30px;
            color: white;
            margin-bottom: 25px;
        }
        
        .chart-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .chart-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eef2f6;
        }
        
        .chart-title i {
            color: #667eea;
            margin-right: 8px;
        }
        
        .top-performer-item {
            padding: 12px;
            border-bottom: 1px solid #eef2f6;
            transition: all 0.3s;
        }
        
        .top-performer-item:hover {
            background: #f8f9fa;
            transform: translateX(5px);
        }
        
        .rank-badge {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
        
        .rank-1 { background: #ffd700; color: #856404; }
        .rank-2 { background: #c0c0c0; color: #495057; }
        .rank-3 { background: #cd7f32; color: white; }
        
        .progress-sm {
            height: 6px;
        }
        
        @media (max-width: 768px) {
            .stat-value { font-size: 24px; }
            .stat-card { margin-bottom: 15px; }
        }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <?php include_once "includes/header.php"; ?>
        <?php include_once "includes/sidebar.php"; ?>

        <div class="page-wrapper">
            <div class="content pb-0">
                
                <!-- Welcome Section -->
                <div class="welcome-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2">Welcome, <?php echo htmlspecialchars($user_info['user_name']); ?>!</h2>
                            <p class="mb-0 opacity-75">Here's what's happening with your business today.</p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <div class="d-flex gap-2 justify-content-md-end">
                                <span class="badge bg-light text-dark px-3 py-2">
                                    <i class="ti ti-calendar"></i> <?php echo date('d M Y'); ?>
                                </span>
                                <span class="badge bg-light text-dark px-3 py-2">
                                    <i class="ti ti-clock"></i> <?php echo date('h:i A'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards Row 1 -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $total_employees; ?></div>
                                    <p class="stat-label">Total Employees</p>
                                </div>
                                <div class="stat-icon bg-primary bg-opacity-10">
                                    <i class="ti ti-users text-primary"></i>
                                </div>
                            </div>
                            <div class="stat-trend">
                                <span class="text-muted">
                                    <i class="ti ti-user-check"></i> <?php echo $total_executives; ?> Executives, 
                                    <?php echo $total_team_leads; ?> Team Leads, 
                                    <?php echo $total_managers; ?> Managers
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $total_leads; ?></div>
                                    <p class="stat-label">Total Leads</p>
                                </div>
                                <div class="stat-icon bg-info bg-opacity-10">
                                    <i class="ti ti-chart-arcs text-info"></i>
                                </div>
                            </div>
                            <div class="stat-trend">
                                <span class="text-muted">
                                    <i class="ti ti-droplet"></i> <?php echo $total_fresh; ?> Fresh
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $total_matelized; ?></div>
                                    <p class="stat-label">Matelized Leads</p>
                                </div>
                                <div class="stat-icon bg-success bg-opacity-10">
                                    <i class="ti ti-star text-success"></i>
                                </div>
                            </div>
                            <div class="stat-trend">
                                <span class="text-muted"><?php echo round(($total_matelized / max($total_leads, 1)) * 100, 1); ?>% Conversion Rate</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value">₹<?php echo number_format($total_revenue, 2); ?></div>
                                    <p class="stat-label">Total Revenue</p>
                                </div>
                                <div class="stat-icon bg-warning bg-opacity-10">
                                    <i class="ti ti-currency-rupee text-warning"></i>
                                </div>
                            </div>
                            <div class="stat-trend">
                                <span class="<?php echo $revenue_growth >= 0 ? 'trend-up' : 'trend-down'; ?>">
                                    <i class="ti ti-arrow-<?php echo $revenue_growth >= 0 ? 'up' : 'down'; ?>"></i>
                                    <?php echo abs($revenue_growth); ?>% from last month
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards Row 2 - Lead Status -->
                <div class="row mb-4">
                    <div class="col-md-2 col-6">
                        <div class="stat-card text-center">
                            <div class="stat-value text-info"><?php echo $total_fresh; ?></div>
                            <p class="stat-label">Fresh Leads</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="stat-card text-center">
                            <div class="stat-value text-warning"><?php echo $total_followup; ?></div>
                            <p class="stat-label">Follow Ups</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="stat-card text-center">
                            <div class="stat-value text-success"><?php echo $total_positive; ?></div>
                            <p class="stat-label">Positive</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="stat-card text-center">
                            <div class="stat-value text-primary"><?php echo $total_committed; ?></div>
                            <p class="stat-label">Committed</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="stat-card text-center">
                            <div class="stat-value text-danger"><?php echo $total_not_interested; ?></div>
                            <p class="stat-label">Not Interested</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="stat-card text-center">
                            <div class="stat-value text-success"><?php echo $total_matelized; ?></div>
                            <p class="stat-label">Matelized</p>
                        </div>
                    </div>
                </div>

                <!-- Today's Performance -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-value"><?php echo $today_leads; ?></div>
                                    <p class="stat-label">Leads Added Today</p>
                                </div>
                                <div class="stat-icon bg-primary bg-opacity-10">
                                    <i class="ti ti-plus text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-value"><?php echo $today_matelized; ?></div>
                                    <p class="stat-label">Matelized Today</p>
                                </div>
                                <div class="stat-icon bg-success bg-opacity-10">
                                    <i class="ti ti-star text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row">
                    <div class="col-lg-7">
                        <div class="chart-card">
                            <div class="chart-title">
                                <i class="ti ti-chart-bar"></i> Monthly Lead Generation
                            </div>
                            <div id="monthlyLeadsChart" style="height: 350px;"></div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="chart-card">
                            <div class="chart-title">
                                <i class="ti ti-currency-rupee"></i> Monthly Revenue Trend
                            </div>
                            <div id="monthlyRevenueChart" style="height: 350px;"></div>
                        </div>
                    </div>
                </div>

                <!-- Top Performers & Department Performance -->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="chart-card">
                            <div class="chart-title">
                                <i class="ti ti-trophy"></i> Top Performing Executives
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Rank</th>
                                            <th>Executive</th>
                                            <th>Leads</th>
                                            <th>Revenue</th>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $rank = 1;
                                            while($performer = $top_performers->fetch_assoc()): 
                                            ?>
                                             <tr>
                                                <td>
                                                    <div class="rank-badge rank-<?php echo $rank <= 3 ? $rank : ''; ?>">
                                                        <?php echo $rank; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm bg-primary bg-opacity-10 me-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                            <span class="text-primary"><?php echo substr($performer['user_name'], 0, 2); ?></span>
                                                        </div>
                                                        <?php echo htmlspecialchars($performer['user_name']); ?>
                                                    </div>
                                                </td>
                                                <td><?php echo $performer['total_leads']; ?></td>
                                                <td class="fw-bold text-success">₹<?php echo number_format($performer['total_revenue'], 2); ?></td>
                                             </tr>
                                            <?php 
                                            $rank++;
                                            endwhile; 
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="chart-card">
                                <div class="chart-title">
                                    <i class="ti ti-building"></i> Department Performance
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Department</th>
                                                <th>Total Leads</th>
                                                <th>Revenue</th>
                                             </thead>
                                            <tbody>
                                                <?php while($dept = $dept_performance->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-primary bg-opacity-10 text-primary">
                                                            <?php echo htmlspecialchars($dept['department']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $dept['total_leads'] ?? 0; ?></td>
                                                    <td class="fw-bold text-success">₹<?php echo number_format($dept['total_revenue'] ?? 0, 2); ?></td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row">
                        <div class="col-12">
                            <div class="chart-card">
                                <div class="chart-title">
                                    <i class="ti ti-settings"></i> Quick Actions
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-3 col-6">
                                        <a href="create_lead.php" class="btn btn-outline-primary w-100 py-3">
                                            <i class="ti ti-plus fs-24 d-block mb-2"></i>
                                            <span>Add New Lead</span>
                                        </a>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <a href="employee.php" class="btn btn-outline-success w-100 py-3">
                                            <i class="ti ti-users fs-24 d-block mb-2"></i>
                                            <span>Manage Employees</span>
                                        </a>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <a href="lead-allocation.php" class="btn btn-outline-info w-100 py-3">
                                            <i class="ti ti-user-plus fs-24 d-block mb-2"></i>
                                            <span>Allocate Leads</span>
                                        </a>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <a href="daily-activity.php" class="btn btn-outline-warning w-100 py-3">
                                            <i class="ti ti-chart-line fs-24 d-block mb-2"></i>
                                            <span>Daily Report</span>
                                        </a>
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
            // Monthly Leads Chart
            var monthlyLeads = <?php echo json_encode($monthly_leads); ?>;
            var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            
            var leadsOptions = {
                series: [{
                    name: 'Leads',
                    data: monthlyLeads
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
                        borderRadius: 6,
                        dataLabels: {
                            position: 'top'
                        },
                        columnWidth: '55%'
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
                    position: 'bottom'
                },
                yaxis: {
                    title: {
                        text: 'Number of Leads'
                    }
                },
                colors: ['#667eea'],
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
            
            var leadsChart = new ApexCharts(document.querySelector("#monthlyLeadsChart"), leadsOptions);
            leadsChart.render();
            
            // Monthly Revenue Chart
            var monthlyRevenue = <?php echo json_encode($monthly_revenue); ?>;
            
            var revenueOptions = {
                series: [{
                    name: 'Revenue',
                    data: monthlyRevenue
                }],
                chart: {
                    type: 'line',
                    height: 350,
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
                    colors: ['#28a745'],
                    strokeColors: '#fff',
                    strokeWidth: 2
                },
                xaxis: {
                    categories: months,
                    title: {
                        text: 'Month'
                    }
                },
                yaxis: {
                    title: {
                        text: 'Revenue (₹)'
                    },
                    labels: {
                        formatter: function(val) {
                            return '₹' + val.toLocaleString();
                        }
                    }
                },
                colors: ['#28a745'],
                grid: {
                    borderColor: '#eef2f6'
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return '₹' + val.toLocaleString();
                        }
                    }
                }
            };
            
            var revenueChart = new ApexCharts(document.querySelector("#monthlyRevenueChart"), revenueOptions);
            revenueChart.render();
        </script>
    </body>
    </html>