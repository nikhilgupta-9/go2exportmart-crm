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

// Check if user is Manager (Grade Level 2)
if ($_SESSION['grade_level'] != 2) {
    header('location: index.php');
    exit;
}

include_once "partials/_dbconnect.php";

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Fetch user details
$user_sql = "SELECT * FROM employees WHERE user_id = '$user_id'";
$user_result = mysqli_query($conn, $user_sql);
$user_data = mysqli_fetch_assoc($user_result);

// Get all Team Leads under this manager (Grade Level 3)
$team_leads_sql = "SELECT * FROM employees WHERE reporting = '$user_id' AND grade_level = 3 AND status = 1 ORDER BY user_name";
$team_leads_result = mysqli_query($conn, $team_leads_sql);
$total_team_leads = mysqli_num_rows($team_leads_result);

// Get all executives indirectly under this manager (through team leads)
$all_executives_sql = "SELECT e.* FROM employees e 
                        INNER JOIN employees tl ON e.reporting = tl.user_id 
                        WHERE tl.reporting = '$user_id' AND e.grade_level = 4 AND e.status = 1";
$all_executives_result = mysqli_query($conn, $all_executives_sql);
$total_executives = mysqli_num_rows($all_executives_result);

// Current date filters
$current_month = date('m');
$current_year = date('Y');
$today_date = date('Y-m-d');

// Overall department statistics
// Total leads in department
$total_leads_sql = "SELECT COUNT(*) as total FROM customerleads 
                    WHERE reporting IN (SELECT user_id FROM employees WHERE reporting = '$user_id' AND grade_level = 3) 
                    AND (matelize = '0' OR matelize IS NULL)";
$total_leads_result = mysqli_query($conn, $total_leads_sql);
$total_leads = mysqli_fetch_assoc($total_leads_result)['total'];

// Fresh leads
$fresh_leads_sql = "SELECT COUNT(*) as total FROM customerleads 
                    WHERE reporting IN (SELECT user_id FROM employees WHERE reporting = '$user_id' AND grade_level = 3) 
                    AND status = 'Fresh Lead' AND (matelize = '0' OR matelize IS NULL)";
$fresh_leads_result = mysqli_query($conn, $fresh_leads_sql);
$fresh_leads = mysqli_fetch_assoc($fresh_leads_result)['total'];

// Follow up leads
$followup_sql = "SELECT COUNT(*) as total FROM customerleads 
                 WHERE reporting IN (SELECT user_id FROM employees WHERE reporting = '$user_id' AND grade_level = 3) 
                 AND status = 'Follow Up' AND (matelize = '0' OR matelize IS NULL)";
$followup_result = mysqli_query($conn, $followup_sql);
$followup_leads = mysqli_fetch_assoc($followup_result)['total'];

// Positive leads
$positive_sql = "SELECT COUNT(*) as total FROM customerleads 
                 WHERE reporting IN (SELECT user_id FROM employees WHERE reporting = '$user_id' AND grade_level = 3) 
                 AND status = 'Positive' AND (matelize = '0' OR matelize IS NULL)";
$positive_result = mysqli_query($conn, $positive_sql);
$positive_leads = mysqli_fetch_assoc($positive_result)['total'];

// Committed leads
$committed_sql = "SELECT COUNT(*) as total FROM customerleads 
                  WHERE reporting IN (SELECT user_id FROM employees WHERE reporting = '$user_id' AND grade_level = 3) 
                  AND status = 'Committed' AND (matelize = '0' OR matelize IS NULL)";
$committed_result = mysqli_query($conn, $committed_sql);
$committed_leads = mysqli_fetch_assoc($committed_result)['total'];

// Matelized leads
$matelized_sql = "SELECT COUNT(*) as total FROM customerleads 
                  WHERE reporting IN (SELECT user_id FROM employees WHERE reporting = '$user_id' AND grade_level = 3) 
                  AND matelize = '1'";
$matelized_result = mysqli_query($conn, $matelized_sql);
$matelized_leads = mysqli_fetch_assoc($matelized_result)['total'];

// Total revenue
$revenue_sql = "SELECT SUM(amount) as total FROM customerleads 
                WHERE reporting IN (SELECT user_id FROM employees WHERE reporting = '$user_id' AND grade_level = 3) 
                AND matelize = '1'";
$revenue_result = mysqli_query($conn, $revenue_sql);
$total_revenue = mysqli_fetch_assoc($revenue_result)['total'] ?? 0;

// This month's performance
$this_month_sql = "SELECT COUNT(*) as total FROM customerleads 
                   WHERE reporting IN (SELECT user_id FROM employees WHERE reporting = '$user_id' AND grade_level = 3) 
                   AND MONTH(todaydate) = '$current_month' AND YEAR(todaydate) = '$current_year' 
                   AND (matelize = '0' OR matelize IS NULL)";
$this_month_result = mysqli_query($conn, $this_month_sql);
$this_month_leads = mysqli_fetch_assoc($this_month_result)['total'];

// Team Lead performance data
$team_lead_performance = [];
while ($tl = mysqli_fetch_assoc($team_leads_result)) {
    // Get team stats
    $tl_leads = $conn->query("SELECT COUNT(*) as total FROM customerleads WHERE reporting = '{$tl['user_id']}' AND (matelize = '0' OR matelize IS NULL)")->fetch_assoc()['total'];
    $tl_matelized = $conn->query("SELECT COUNT(*) as total FROM customerleads WHERE reporting = '{$tl['user_id']}' AND matelize = '1'")->fetch_assoc()['total'];
    $tl_revenue = $conn->query("SELECT SUM(amount) as total FROM customerleads WHERE reporting = '{$tl['user_id']}' AND matelize = '1'")->fetch_assoc()['total'] ?? 0;
    
    // Get team members count
    $team_members = $conn->query("SELECT COUNT(*) as total FROM employees WHERE reporting = '{$tl['user_id']}' AND grade_level = 4 AND status = 1")->fetch_assoc()['total'];
    
    // Get pending follow-ups for team
    $pending_followups = $conn->query("SELECT COUNT(*) as total FROM customerleads WHERE reporting = '{$tl['user_id']}' AND status = 'Follow Up' AND DATE(todaydate) <= '$today_date'")->fetch_assoc()['total'];
    
    $team_lead_performance[] = [
        'id' => $tl['user_id'],
        'name' => $tl['user_name'],
        'total_leads' => $tl_leads,
        'matelized' => $tl_matelized,
        'revenue' => $tl_revenue,
        'conversion_rate' => $tl_leads > 0 ? round(($tl_matelized / $tl_leads) * 100, 1) : 0,
        'team_members' => $team_members,
        'pending_followups' => $pending_followups
    ];
}

// Department performance by month (last 6 months)
$monthly_performance = [];
for ($i = 5; $i >= 0; $i--) {
    $month_date = date('Y-m', strtotime("-$i months"));
    $month_name = date('M', strtotime("-$i months"));
    $year = date('Y', strtotime("-$i months"));
    $month_num = date('m', strtotime("-$i months"));
    
    $monthly_sql = "SELECT 
                        COUNT(*) as total_leads,
                        SUM(CASE WHEN matelize = 1 THEN 1 ELSE 0 END) as matelized,
                        COALESCE(SUM(CASE WHEN matelize = 1 THEN amount ELSE 0 END), 0) as revenue
                    FROM customerleads 
                    WHERE reporting IN (SELECT user_id FROM employees WHERE reporting = '$user_id' AND grade_level = 3) 
                    AND MONTH(todaydate) = '$month_num' AND YEAR(todaydate) = '$year'";
    $monthly_result = mysqli_query($conn, $monthly_sql);
    $monthly_data = mysqli_fetch_assoc($monthly_result);
    
    $monthly_performance[] = [
        'month' => $month_name,
        'leads' => $monthly_data['total_leads'] ?? 0,
        'matelized' => $monthly_data['matelized'] ?? 0,
        'revenue' => $monthly_data['revenue'] ?? 0
    ];
}

// Top performing executives across all teams
$top_executives_sql = "SELECT e.user_name, e.user_id, 
                        COUNT(CASE WHEN c.matelize = 1 THEN 1 END) as matelized,
                        COALESCE(SUM(CASE WHEN c.matelize = 1 THEN c.amount ELSE 0 END), 0) as revenue,
                        COUNT(c.sno) as total_leads
                       FROM employees e
                       LEFT JOIN customerleads c ON e.user_id = c.assigned_to
                       WHERE e.grade_level = 4 AND e.status = 1 
                       AND e.reporting IN (SELECT user_id FROM employees WHERE reporting = '$user_id' AND grade_level = 3)
                       GROUP BY e.user_id
                       ORDER BY matelized DESC, revenue DESC
                       LIMIT 10";
$top_executives_result = mysqli_query($conn, $top_executives_sql);

// Recent high-value deals (matelized)
$recent_deals_sql = "SELECT c.*, e.user_name as executive_name, tl.user_name as team_lead_name
                     FROM customerleads c
                     LEFT JOIN employees e ON c.assigned_to = e.user_id
                     LEFT JOIN employees tl ON c.reporting = tl.user_id
                     WHERE c.reporting IN (SELECT user_id FROM employees WHERE reporting = '$user_id' AND grade_level = 3)
                     AND c.matelize = 1
                     ORDER BY c.amount DESC, c.todaydate DESC
                     LIMIT 10";
$recent_deals_result = mysqli_query($conn, $recent_deals_sql);

// Department activity summary by team lead
$team_activity_sql = "SELECT tl.user_name as team_lead, 
                      COUNT(c.sno) as total_activities,
                      COUNT(CASE WHEN c.matelize = 1 THEN 1 END) as deals_closed,
                      COALESCE(SUM(CASE WHEN c.matelize = 1 THEN c.amount ELSE 0 END), 0) as total_value
                     FROM employees tl
                     LEFT JOIN customerleads c ON tl.user_id = c.reporting
                     WHERE tl.reporting = '$user_id' AND tl.grade_level = 3 AND tl.status = 1
                     GROUP BY tl.user_id
                     ORDER BY total_value DESC";
$team_activity_result = mysqli_query($conn, $team_activity_sql);

include_once "includes/link.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Manager Dashboard | GO2EXPORT MART</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 25px;
            color: white;
            margin-bottom: 25px;
        }
        
        .team-lead-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
            border-left: 4px solid #667eea;
        }
        
        .team-lead-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .progress-sm {
            height: 6px;
        }
        
        .metric-badge {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 8px 12px;
            text-align: center;
        }
        
        .metric-value {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .metric-label {
            font-size: 11px;
            color: #6c757d;
            margin-top: 4px;
        }
        
        .deal-card {
            padding: 12px;
            border-bottom: 1px solid #eef2f6;
            transition: background 0.3s;
        }
        
        .deal-card:hover {
            background: #f8f9fa;
        }
        
        .deal-card:last-child {
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
        }
        
        .rank-1 { background: linear-gradient(135deg, #ffd700, #ffed4e); color: #856404; }
        .rank-2 { background: linear-gradient(135deg, #c0c0c0, #e8e8e8); color: #5a5a5a; }
        .rank-3 { background: linear-gradient(135deg, #cd7f32, #e6b17e); color: #7b3f00; }
        
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
                        <h4 class="mb-1">Manager Dashboard</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Manager Dashboard</li>
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
                            <p class="mb-0 opacity-75">Manage your department's performance and track team achievements</p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <div class="d-flex gap-2 justify-content-md-end">
                                <span class="badge bg-light text-dark px-3 py-2">
                                    <i class="ti ti-users"></i> <?php echo $total_team_leads; ?> Team Leads
                                </span>
                                <span class="badge bg-light text-dark px-3 py-2">
                                    <i class="ti ti-user-star"></i> <?php echo $total_executives; ?> Executives
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
                                    <p class="stat-label">Total Department Leads</p>
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
                                    <p class="stat-label">Deals Closed</p>
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
                                    <p class="stat-label">Total Revenue Generated</p>
                                </div>
                                <div class="stat-icon bg-warning bg-opacity-10">
                                    <i class="ti ti-currency-rupee text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Team Leads Performance Cards -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-bottom">
                        <h5 class="mb-0"><i class="ti ti-user-star me-2"></i>Team Leads Performance</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($team_lead_performance as $tl): ?>
                            <div class="col-md-6 col-xl-4">
                                <div class="team-lead-card">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($tl['name']); ?></h6>
                                            <small class="text-muted">
                                                <i class="ti ti-users"></i> <?php echo $tl['team_members']; ?> Team Members
                                            </small>
                                        </div>
                                        <div class="avatar-sm bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <span class="text-primary fs-16"><?php echo substr($tl['name'], 0, 2); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <div class="metric-badge">
                                                <div class="metric-value"><?php echo $tl['total_leads']; ?></div>
                                                <div class="metric-label">Total Leads</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="metric-badge">
                                                <div class="metric-value text-success"><?php echo $tl['matelized']; ?></div>
                                                <div class="metric-label">Deals Closed</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <small>Conversion Rate</small>
                                            <small class="fw-bold"><?php echo $tl['conversion_rate']; ?>%</small>
                                        </div>
                                        <div class="progress progress-sm">
                                            <div class="progress-bar bg-<?php echo $tl['conversion_rate'] >= 50 ? 'success' : ($tl['conversion_rate'] >= 25 ? 'warning' : 'danger'); ?>" style="width: <?php echo $tl['conversion_rate']; ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted">Revenue</small>
                                            <div class="fw-bold">₹<?php echo number_format($tl['revenue'], 2); ?></div>
                                        </div>
                                        <?php if ($tl['pending_followups'] > 0): ?>
                                        <span class="badge bg-warning">
                                            <i class="ti ti-alarm"></i> <?php echo $tl['pending_followups']; ?> Pending
                                        </span>
                                        <?php endif; ?>
                                        <a href="team-lead-details.php?lead_id=<?php echo $tl['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            View Details <i class="ti ti-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Monthly Performance Chart -->
                    <div class="col-xl-6">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-transparent border-bottom">
                                <h5 class="mb-0"><i class="ti ti-chart-line me-2"></i>Department Performance (Last 6 Months)</h5>
                            </div>
                            <div class="card-body">
                                <div id="monthlyChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Performing Executives -->
                    <div class="col-xl-6">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-transparent border-bottom">
                                <h5 class="mb-0"><i class="ti ti-trophy me-2"></i>Top Performing Executives</h5>
                            </div>
                            <div class="card-body">
                                <?php 
                                $rank = 1;
                                while($exec = mysqli_fetch_assoc($top_executives_result)): 
                                ?>
                                <div class="deal-card d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rank-badge rank-<?php echo $rank <= 3 ? $rank : 'other'; ?>">
                                            <?php echo $rank; ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($exec['user_name']); ?></h6>
                                            <small class="text-muted">
                                                <i class="ti ti-chart-line"></i> <?php echo $exec['total_leads']; ?> Total Leads
                                            </small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-success"><?php echo $exec['matelized']; ?> Deals</div>
                                        <small class="text-muted">₹<?php echo number_format($exec['revenue'], 2); ?></small>
                                    </div>
                                </div>
                                <?php 
                                $rank++;
                                endwhile; 
                                if ($rank == 1):
                                ?>
                                <div class="text-center py-4">
                                    <i class="ti ti-user-off fs-48 text-muted"></i>
                                    <p class="mt-2 text-muted">No executive data available</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent High-Value Deals -->
                    <div class="col-xl-6">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-transparent border-bottom">
                                <h5 class="mb-0"><i class="ti ti-crown me-2"></i>Recent High-Value Deals</h5>
                            </div>
                            <div class="card-body">
                                <?php if (mysqli_num_rows($recent_deals_result) > 0): ?>
                                    <?php while($deal = mysqli_fetch_assoc($recent_deals_result)): ?>
                                    <div class="deal-card">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($deal['customer_name']); ?></h6>
                                                <p class="mb-0 text-muted small">
                                                    <i class="ti ti-user-star"></i> <?php echo htmlspecialchars($deal['executive_name']); ?>
                                                    <span class="mx-2">•</span>
                                                    <i class="ti ti-user"></i> TL: <?php echo htmlspecialchars($deal['team_lead_name']); ?>
                                                </p>
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-bold text-success">₹<?php echo number_format($deal['amount'], 2); ?></div>
                                                <small class="text-muted"><?php echo date('d M Y', strtotime($deal['todaydate'])); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="ti ti-star-off fs-48 text-muted"></i>
                                        <p class="mt-2 text-muted">No closed deals yet</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Team Activity Summary -->
                    <div class="col-xl-6">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-transparent border-bottom">
                                <h5 class="mb-0"><i class="ti ti-chart-pie me-2"></i>Team Activity Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Team Lead</th>
                                                <th>Activities</th>
                                                <th>Deals Closed</th>
                                                <th>Total Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (mysqli_num_rows($team_activity_result) > 0): ?>
                                                <?php while($activity = mysqli_fetch_assoc($team_activity_result)): ?>
                                                <tr>
                                                    <td class="fw-medium"><?php echo htmlspecialchars($activity['team_lead']); ?></td>
                                                    <td><?php echo $activity['total_activities']; ?></td>
                                                    <td class="text-success"><?php echo $activity['deals_closed']; ?></td>
                                                    <td class="fw-bold">₹<?php echo number_format($activity['total_value'], 2); ?></td>
                                                </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center py-4">No activity data available</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
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
            
            // Monthly Performance Chart
            var monthlyData = <?php echo json_encode($monthly_performance); ?>;
            var months = monthlyData.map(item => item.month);
            var leadsData = monthlyData.map(item => item.leads);
            var matelizedData = monthlyData.map(item => item.matelized);
            
            var options = {
                series: [
                    {
                        name: 'Total Leads',
                        type: 'column',
                        data: leadsData
                    },
                    {
                        name: 'Deals Closed',
                        type: 'line',
                        data: matelizedData
                    }
                ],
                chart: {
                    height: 350,
                    type: 'line',
                    toolbar: {
                        show: true
                    }
                },
                stroke: {
                    width: [0, 3],
                    curve: 'smooth'
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
                yaxis: [
                    {
                        title: {
                            text: 'Number of Leads'
                        }
                    },
                    {
                        opposite: true,
                        title: {
                            text: 'Deals Closed'
                        }
                    }
                ],
                colors: ['#667eea', '#28a745'],
                fill: {
                    opacity: [0.85, 1]
                },
                tooltip: {
                    shared: true,
                    intersect: false
                }
            };
            
            var chart = new ApexCharts(document.querySelector("#monthlyChart"), options);
            chart.render();
        });
    </script>
    
    <style>
        .avatar-sm {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        .fs-16 {
            font-size: 16px;
        }
        .rank-other {
            background: #e9ecef;
            color: #6c757d;
        }
    </style>
</body>
</html>