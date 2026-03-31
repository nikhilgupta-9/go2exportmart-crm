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
$current_month = date("F");
$current_month_num = date("m");
$current_year = date("Y");

// Fetch user info
$user_info = $conn->query("SELECT * FROM employees WHERE user_id = '$user_id'")->fetch_assoc();
$target = $user_info['user_target'];

// Calculate achieved for logged-in user
$achieved_sql = "SELECT SUM(amount) as total FROM customerleads WHERE matelize = '1' AND assigned_to = '$user_id' AND MONTH(todaydate) = '$current_month_num' AND YEAR(todaydate) = '$current_year'";
$achieved_result = $conn->query($achieved_sql);
$achieved_data = $achieved_result->fetch_assoc();
$achieved = $achieved_data['total'] ?? 0;

// Calculate percentages
$achieved_percent = $target > 0 ? round(($achieved / $target) * 100, 1) : 0;
$remaining = $target - $achieved;
$today = date("d");
$monthdays = date("t");
$daysleft = $monthdays - $today;
$sunday = floor($daysleft / 7);
$businessDays = $daysleft - $sunday;
$per_day_target = $businessDays > 0 ? round($remaining / $businessDays) : 0;
$current_per_day = $today > 0 ? round($achieved / $today) : 0;

// For Grade Level 3 (Team Lead) - Get team members
$team_members = [];
$team_total_target = 0;
$team_total_achieved = 0;

if ($grade_level == 3) {
    // Get all team members under this team lead
    $team_query = "SELECT user_id, user_name, user_target FROM employees WHERE Reporting = '$user_id' AND grade_level = 4 AND status = 1";
    $team_result = $conn->query($team_query);
    
    while ($member = $team_result->fetch_assoc()) {
        $member_achieved_sql = "SELECT SUM(amount) as total FROM customerleads WHERE matelize = '1' AND assigned_to = '{$member['user_id']}' AND MONTH(todaydate) = '$current_month_num' AND YEAR(todaydate) = '$current_year'";
        $member_achieved_result = $conn->query($member_achieved_sql);
        $member_achieved = $member_achieved_result->fetch_assoc()['total'] ?? 0;
        
        $member['achieved'] = $member_achieved;
        $member['percentage'] = $member['user_target'] > 0 ? round(($member_achieved / $member['user_target']) * 100, 1) : 0;
        $team_members[] = $member;
        
        $team_total_target += $member['user_target'];
        $team_total_achieved += $member_achieved;
    }
    
    // Add team lead's own target
    $team_total_target += $target;
    $team_total_achieved += $achieved;
    $team_percentage = $team_total_target > 0 ? round(($team_total_achieved / $team_total_target) * 100, 1) : 0;
}

// For Grade Level 1 (Admin) - Get all employees data
$all_employees_data = [];
$company_total_target = 0;
$company_total_achieved = 0;
$team_wise_data = [];

if ($grade_level == 1) {
    // Get all teams and their performance
    $teams_query = "SELECT t.*, e.user_name as lead_name 
                    FROM teams t 
                    LEFT JOIN employees e ON t.team_lead = e.user_id 
                    WHERE t.status = 'active'";
    $teams_result = $conn->query($teams_query);
    
    while ($team = $teams_result->fetch_assoc()) {
        // Get team members (grade level 4 under this team)
        $team_members_query = "SELECT e.* FROM employees e 
                               WHERE e.team_id = '{$team['id']}' AND e.grade_level = 4 AND e.status = 1";
        $team_members_res = $conn->query($team_members_query);
        
        $team_target = 0;
        $team_achieved = 0;
        $members_data = [];
        
        while ($member = $team_members_res->fetch_assoc()) {
            $member_achieved_sql = "SELECT SUM(amount) as total FROM customerleads WHERE matelize = '1' AND assigned_to = '{$member['user_id']}' AND MONTH(todaydate) = '$current_month_num' AND YEAR(todaydate) = '$current_year'";
            $member_achieved_result = $conn->query($member_achieved_sql);
            $member_achieved = $member_achieved_result->fetch_assoc()['total'] ?? 0;
            
            $member_target = $member['user_target'];
            $team_target += $member_target;
            $team_achieved += $member_achieved;
            
            $members_data[] = [
                'name' => $member['user_name'],
                'id' => $member['user_id'],
                'target' => $member_target,
                'achieved' => $member_achieved,
                'percentage' => $member_target > 0 ? round(($member_achieved / $member_target) * 100, 1) : 0
            ];
        }
        
        // Add team lead's target
        $lead_target = $team['target_revenue'] ?? 0;
        $team_target += $lead_target;
        
        // Get lead's achieved (based on team's total achieved from leads assigned to team)
        $lead_achieved_sql = "SELECT SUM(amount) as total FROM customerleads WHERE matelize = '1' AND Reporting = '{$team['team_lead']}' AND MONTH(todaydate) = '$current_month_num' AND YEAR(todaydate) = '$current_year'";
        $lead_achieved_result = $conn->query($lead_achieved_sql);
        $lead_achieved = $lead_achieved_result->fetch_assoc()['total'] ?? 0;
        $team_achieved += $lead_achieved;
        
        $team_wise_data[] = [
            'id' => $team['id'],
            'name' => $team['team_name'],
            'lead_name' => $team['lead_name'],
            'target' => $team_target,
            'achieved' => $team_achieved,
            'percentage' => $team_target > 0 ? round(($team_achieved / $team_target) * 100, 1) : 0,
            'members' => $members_data
        ];
        
        $company_total_target += $team_target;
        $company_total_achieved += $team_achieved;
    }
    
    // Get all employees for table view
    $all_emps_query = "SELECT e.*, t.team_name 
                       FROM employees e 
                       LEFT JOIN teams t ON e.team_id = t.id 
                       WHERE e.grade_level >= 3 AND e.status = 1 
                       ORDER BY e.grade_level, e.user_name";
    $all_emps_result = $conn->query($all_emps_query);
    
    while ($emp = $all_emps_result->fetch_assoc()) {
        $emp_achieved_sql = "SELECT SUM(amount) as total FROM customerleads WHERE matelize = '1' AND assigned_to = '{$emp['user_id']}' AND MONTH(todaydate) = '$current_month_num' AND YEAR(todaydate) = '$current_year'";
        $emp_achieved_result = $conn->query($emp_achieved_sql);
        $emp_achieved = $emp_achieved_result->fetch_assoc()['total'] ?? 0;
        
        $all_employees_data[] = [
            'id' => $emp['user_id'],
            'name' => $emp['user_name'],
            'role' => $emp['user_role'],
            'grade' => $emp['grade_level'],
            'team' => $emp['team_name'] ?? 'Not Assigned',
            'target' => $emp['user_target'],
            'achieved' => $emp_achieved,
            'percentage' => $emp['user_target'] > 0 ? round(($emp_achieved / $emp['user_target']) * 100, 1) : 0
        ];
    }
    
    $company_percentage = $company_total_target > 0 ? round(($company_total_achieved / $company_total_target) * 100, 1) : 0;
}

// Get monthly performance data for charts
$monthly_performance = [];
for ($i = 1; $i <= 12; $i++) {
    $month_achieved = 0;
    
    if ($grade_level == 4) {
        $month_sql = "SELECT SUM(amount) as total FROM customerleads WHERE matelize = '1' AND assigned_to = '$user_id' AND MONTH(todaydate) = '$i' AND YEAR(todaydate) = '$current_year'";
    } elseif ($grade_level == 3) {
        $team_ids = [$user_id];
        $team_members_res = $conn->query("SELECT user_id FROM employees WHERE Reporting = '$user_id' AND grade_level = 4");
        while ($m = $team_members_res->fetch_assoc()) {
            $team_ids[] = $m['user_id'];
        }
        $ids_string = implode("','", $team_ids);
        $month_sql = "SELECT SUM(amount) as total FROM customerleads WHERE matelize = '1' AND assigned_to IN ('$ids_string') AND MONTH(todaydate) = '$i' AND YEAR(todaydate) = '$current_year'";
    } else {
        $month_sql = "SELECT SUM(amount) as total FROM customerleads WHERE matelize = '1' AND MONTH(todaydate) = '$i' AND YEAR(todaydate) = '$current_year'";
    }
    
    $month_result = $conn->query($month_sql);
    $month_achieved = $month_result->fetch_assoc()['total'] ?? 0;
    $monthly_performance[] = $month_achieved;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Target Management | GO2EXPORT MART</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once "includes/link.php"; ?>
    
    <style>
        .target-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .target-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .target-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .progress-circle {
            width: 120px;
            height: 120px;
            position: relative;
        }
        
        .progress-circle canvas {
            width: 100%;
            height: 100%;
        }
        
        .target-value {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .target-label {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .achievement-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .achievement-excellent {
            background: #d4edda;
            color: #155724;
        }
        
        .achievement-good {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .achievement-average {
            background: #fff3cd;
            color: #856404;
        }
        
        .achievement-poor {
            background: #f8d7da;
            color: #721c24;
        }
        
        .team-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        
        .team-card:hover {
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .team-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
        }
        
        .member-item {
            padding: 12px 15px;
            border-bottom: 1px solid #eef2f6;
            transition: background 0.3s;
        }
        
        .member-item:hover {
            background: #f8f9fa;
        }
        
        .member-item:last-child {
            border-bottom: none;
        }
        
        .progress-sm {
            height: 6px;
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
        
        .rank-1 { background: #ffd700; color: #856404; }
        .rank-2 { background: #c0c0c0; color: #495057; }
        .rank-3 { background: #cd7f32; color: white; }
        
        @media (max-width: 768px) {
            .target-card {
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
                        <h4 class="mb-1">Target Management</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Target</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
                        <div class="dropdown">
                            <button class="btn btn-outline-light shadow dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="ti ti-calendar me-1"></i> <?php echo $current_month . ' ' . $current_year; ?>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a href="?month=1" class="dropdown-item">January</a>
                                <a href="?month=2" class="dropdown-item">February</a>
                                <a href="?month=3" class="dropdown-item">March</a>
                                <a href="?month=4" class="dropdown-item">April</a>
                                <a href="?month=5" class="dropdown-item">May</a>
                                <a href="?month=6" class="dropdown-item">June</a>
                                <a href="?month=7" class="dropdown-item">July</a>
                                <a href="?month=8" class="dropdown-item">August</a>
                                <a href="?month=9" class="dropdown-item">September</a>
                                <a href="?month=10" class="dropdown-item">October</a>
                                <a href="?month=11" class="dropdown-item">November</a>
                                <a href="?month=12" class="dropdown-item">December</a>
                            </div>
                        </div>
                        <button onclick="window.location.reload()" class="btn btn-icon btn-outline-light shadow">
                            <i class="ti ti-refresh"></i>
                        </button>
                    </div>
                </div>

                <!-- Personal Target Section -->
                <div class="row">
                    <div class="col-xl-6">
                        <div class="target-card">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <span class="target-label">My Target - <?php echo $current_month; ?> <?php echo $current_year; ?></span>
                                    <h2 class="mb-0">₹<?php echo number_format($target); ?></h2>
                                </div>
                                <div class="target-icon bg-primary bg-opacity-10">
                                    <i class="ti ti-target text-primary"></i>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Progress</span>
                                    <span class="fw-bold"><?php echo $achieved_percent; ?>%</span>
                                </div>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-success" style="width: <?php echo $achieved_percent; ?>%"></div>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-6">
                                    <div class="text-center p-2 bg-light rounded">
                                        <small class="text-muted d-block">Achieved</small>
                                        <strong>₹<?php echo number_format($achieved); ?></strong>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-2 bg-light rounded">
                                        <small class="text-muted d-block">Remaining</small>
                                        <strong>₹<?php echo number_format($remaining); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-6">
                        <div class="target-card">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <span class="target-label">Daily Performance</span>
                                    <h2 class="mb-0">₹<?php echo number_format($current_per_day); ?></h2>
                                </div>
                                <div class="target-icon bg-info bg-opacity-10">
                                    <i class="ti ti-calendar text-info"></i>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-6">
                                    <div class="text-center p-2 bg-light rounded mb-2">
                                        <small class="text-muted d-block">Days Left</small>
                                        <strong><?php echo $businessDays; ?> days</strong>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-2 bg-light rounded mb-2">
                                        <small class="text-muted d-block">Per Day Target</small>
                                        <strong>₹<?php echo number_format($per_day_target); ?></strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mt-2 mb-0">
                                <i class="ti ti-info-circle me-1"></i>
                                You need to achieve ₹<?php echo number_format($per_day_target); ?> per day to meet your monthly target.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Team Target Section (For Team Lead - Grade Level 3) -->
                <?php if ($grade_level == 3 && !empty($team_members)): ?>
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-transparent border-bottom">
                        <h5 class="mb-0"><i class="ti ti-users-group me-2"></i>Team Performance</h5>
                        <small>Overall team target and achievements</small>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="text-center p-3 bg-light rounded">
                                    <small class="text-muted d-block">Team Target</small>
                                    <h3 class="mb-0">₹<?php echo number_format($team_total_target); ?></h3>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center p-3 bg-light rounded">
                                    <small class="text-muted d-block">Team Achieved</small>
                                    <h3 class="mb-0 text-success">₹<?php echo number_format($team_total_achieved); ?></h3>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center p-3 bg-light rounded">
                                    <small class="text-muted d-block">Team Progress</small>
                                    <h3 class="mb-0"><?php echo $team_percentage; ?>%</h3>
                                    <div class="progress progress-sm mt-2">
                                        <div class="progress-bar bg-success" style="width: <?php echo $team_percentage; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <h6 class="mb-3">Team Members Performance</h6>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Member Name</th>
                                        <th>Target</th>
                                        <th>Achieved</th>
                                        <th>Progress</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($team_members as $member): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($member['user_name']); ?></td>
                                        <td>₹<?php echo number_format($member['user_target']); ?></td>
                                        <td>₹<?php echo number_format($member['achieved']); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress progress-sm flex-grow-1">
                                                    <div class="progress-bar bg-<?php echo $member['percentage'] >= 70 ? 'success' : ($member['percentage'] >= 40 ? 'warning' : 'danger'); ?>" style="width: <?php echo $member['percentage']; ?>%"></div>
                                                </div>
                                                <span class="small"><?php echo $member['percentage']; ?>%</span>
                                            </div>
                                         </td>
                                     </tr>
                                    <?php endforeach; ?>
                                </tbody>
                             </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Company Wide Target (For Admin - Grade Level 1) -->
                <?php if ($grade_level == 1): ?>
                <!-- Company Overview -->
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="target-card text-center">
                            <div class="target-icon mx-auto mb-3 bg-primary bg-opacity-10">
                                <i class="ti ti-building text-primary"></i>
                            </div>
                            <span class="target-label">Company Target</span>
                            <h2 class="mb-0">₹<?php echo number_format($company_total_target); ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="target-card text-center">
                            <div class="target-icon mx-auto mb-3 bg-success bg-opacity-10">
                                <i class="ti ti-check-circle text-success"></i>
                            </div>
                            <span class="target-label">Company Achieved</span>
                            <h2 class="mb-0">₹<?php echo number_format($company_total_achieved); ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="target-card text-center">
                            <div class="target-icon mx-auto mb-3 bg-info bg-opacity-10">
                                <i class="ti ti-chart-line text-info"></i>
                            </div>
                            <span class="target-label">Overall Progress</span>
                            <h2 class="mb-0"><?php echo $company_percentage; ?>%</h2>
                            <div class="progress progress-sm mt-2">
                                <div class="progress-bar bg-success" style="width: <?php echo $company_percentage; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Team Wise Performance -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-transparent border-bottom">
                        <h5 class="mb-0"><i class="ti ti-chart-pie me-2"></i>Team Wise Performance</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($team_wise_data as $team): ?>
                            <div class="col-lg-6">
                                <div class="team-card">
                                    <div class="team-header">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($team['name']); ?></h6>
                                        <small>Lead: <?php echo htmlspecialchars($team['lead_name']); ?></small>
                                    </div>
                                    <div class="p-3">
                                        <div class="row mb-3">
                                            <div class="col-6">
                                                <div class="text-center">
                                                    <small class="text-muted d-block">Target</small>
                                                    <strong>₹<?php echo number_format($team['target']); ?></strong>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-center">
                                                    <small class="text-muted d-block">Achieved</small>
                                                    <strong class="text-success">₹<?php echo number_format($team['achieved']); ?></strong>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between small mb-1">
                                                <span>Progress</span>
                                                <span><?php echo $team['percentage']; ?>%</span>
                                            </div>
                                            <div class="progress progress-sm">
                                                <div class="progress-bar bg-<?php echo $team['percentage'] >= 70 ? 'success' : ($team['percentage'] >= 40 ? 'warning' : 'danger'); ?>" style="width: <?php echo $team['percentage']; ?>%"></div>
                                            </div>
                                        </div>
                                        <?php if (!empty($team['members'])): ?>
                                        <button class="btn btn-sm btn-outline-primary mt-2" data-bs-toggle="collapse" data-bs-target="#team-<?php echo $team['id']; ?>">
                                            <i class="ti ti-users"></i> View Members (<?php echo count($team['members']); ?>)
                                        </button>
                                        <div class="collapse mt-3" id="team-<?php echo $team['id']; ?>">
                                            <?php foreach ($team['members'] as $member): ?>
                                            <div class="member-item">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <div class="fw-semibold"><?php echo htmlspecialchars($member['name']); ?></div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($member['id']); ?></small>
                                                    </div>
                                                    <div class="text-end">
                                                        <div>₹<?php echo number_format($member['achieved']); ?> / ₹<?php echo number_format($member['target']); ?></div>
                                                        <div class="small text-<?php echo $member['percentage'] >= 70 ? 'success' : ($member['percentage'] >= 40 ? 'warning' : 'danger'); ?>">
                                                            <?php echo $member['percentage']; ?>%
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="progress progress-sm mt-1">
                                                    <div class="progress-bar" style="width: <?php echo $member['percentage']; ?>%"></div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- All Employees Performance Table -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-transparent border-bottom">
                        <h5 class="mb-0"><i class="ti ti-users me-2"></i>All Employees Performance</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="employeesTable">
                                <thead>
                                    <tr>
                                        <th>Employee Name</th>
                                        <th>Role</th>
                                        <th>Team</th>
                                        <th>Target</th>
                                        <th>Achieved</th>
                                        <th>Progress</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Sort by percentage
                                    usort($all_employees_data, function($a, $b) {
                                        return $b['percentage'] <=> $a['percentage'];
                                    });
                                    $rank = 1;
                                    foreach ($all_employees_data as $emp): 
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rank-badge rank-<?php echo $rank <= 3 ? $rank : ''; ?>">
                                                    <?php echo $rank <= 3 ? $rank : ''; ?>
                                                </div>
                                                <?php echo htmlspecialchars($emp['name']); ?>
                                            </div>
                                         </td>
                                        <td><?php echo htmlspecialchars($emp['role']); ?> </td>
                                        <td><?php echo htmlspecialchars($emp['team']); ?> </td>
                                        <td>₹<?php echo number_format($emp['target']); ?> </td>
                                        <td>₹<?php echo number_format($emp['achieved']); ?> </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress progress-sm flex-grow-1">
                                                    <div class="progress-bar bg-<?php echo $emp['percentage'] >= 70 ? 'success' : ($emp['percentage'] >= 40 ? 'warning' : 'danger'); ?>" style="width: <?php echo $emp['percentage']; ?>%"></div>
                                                </div>
                                                <span class="small"><?php echo $emp['percentage']; ?>%</span>
                                            </div>
                                         </td>
                                     </tr>
                                    <?php 
                                    $rank++;
                                    endforeach; 
                                    ?>
                                </tbody>
                             </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Monthly Performance Chart -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-transparent border-bottom">
                        <h5 class="mb-0"><i class="ti ti-chart-bar me-2"></i>Monthly Performance <?php echo $current_year; ?></h5>
                    </div>
                    <div class="card-body">
                        <div id="monthlyChart" style="height: 350px;"></div>
                    </div>
                </div>
            </div>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <?php include_once "includes/footer-link.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        // Monthly Performance Chart
        var monthlyData = <?php echo json_encode($monthly_performance); ?>;
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        var options = {
            series: [{
                name: 'Achieved Amount',
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
                formatter: function(val) {
                    return '₹' + val.toLocaleString();
                },
                style: {
                    fontSize: '11px',
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
                }
            },
            yaxis: {
                title: {
                    text: 'Amount (₹)'
                },
                labels: {
                    formatter: function(val) {
                        return '₹' + val.toLocaleString();
                    }
                }
            },
            colors: ['#667eea'],
            grid: {
                borderColor: '#eef2f6',
                row: {
                    colors: ['#f8f9fa', 'transparent'],
                    opacity: 0.5
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return '₹' + val.toLocaleString();
                    }
                }
            }
        };
        
        var chart = new ApexCharts(document.querySelector("#monthlyChart"), options);
        chart.render();
        
        // Initialize DataTable
        if ($('#employeesTable').length) {
            $('#employeesTable').DataTable({
                pageLength: 10,
                order: [[5, 'desc']],
                language: {
                    search: "Search employees:",
                    lengthMenu: "Show _MENU_ employees",
                    info: "Showing _START_ to _END_ of _TOTAL_ employees"
                }
            });
        }
    </script>
</body>
</html>