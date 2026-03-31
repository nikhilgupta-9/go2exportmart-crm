<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
    exit();
}

include 'partials/_dbconnect.php';
include 'partials/_header.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$grade_level = $_SESSION['grade_level'];

// Fetch user info
$user_info = $conn->query("SELECT * FROM employees WHERE user_id = '$user_id'")->fetch_assoc();

// Handle Executive to Team Assignment via Drag & Drop or Form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_executive'])) {
    $executive_id = mysqli_real_escape_string($conn, $_POST['executive_id']);
    $team_id = mysqli_real_escape_string($conn, $_POST['team_id']);
    
    $update_executive = "UPDATE employees SET team_id = '$team_id' WHERE user_id = '$executive_id'";
    if ($conn->query($update_executive)) {
        $success_message = "Executive assigned successfully!";
    } else {
        $error_message = "Error assigning executive: " . $conn->error;
    }
}

// Handle Remove from Team
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_from_team'])) {
    $employee_id = mysqli_real_escape_string($conn, $_POST['employee_id']);
    $update_employee = "UPDATE employees SET team_id = NULL WHERE user_id = '$employee_id'";
    if ($conn->query($update_employee)) {
        $success_message = "Employee removed from team successfully!";
    } else {
        $error_message = "Error removing employee: " . $conn->error;
    }
}

// Fetch all team leaders (Grade Level 3)
$team_leaders = $conn->query("SELECT * FROM employees WHERE grade_level = 3 AND status = 1 ORDER BY user_name");

// Fetch all teams with their leaders and members
$teams_query = "SELECT t.*, 
                e.user_name as lead_name, 
                e.user_id as lead_id,
                COUNT(DISTINCT m.emp_id) as member_count
                FROM teams t 
                LEFT JOIN employees e ON t.team_lead = e.user_id
                LEFT JOIN employees m ON m.team_id = t.id AND m.grade_level = 4
                GROUP BY t.id
                ORDER BY t.team_name";
$teams_result = $conn->query($teams_query);

// Fetch all executives (Grade Level 4) with their team info
$executives_query = "SELECT e.*, t.team_name, t.id as team_id 
                     FROM employees e 
                     LEFT JOIN teams t ON e.team_id = t.id 
                     WHERE e.grade_level = 4 AND e.status = 1 
                     ORDER BY e.user_name";
$executives_result = $conn->query($executives_query);

// Fetch unassigned executives
$unassigned_executives = $conn->query("SELECT * FROM employees WHERE grade_level = 4 AND (team_id IS NULL OR team_id = '') AND status = 1 ORDER BY user_name");

// Get statistics
$total_teams = $conn->query("SELECT COUNT(*) as total FROM teams WHERE status = 'active'")->fetch_assoc()['total'];
$total_executives = $conn->query("SELECT COUNT(*) as total FROM employees WHERE grade_level = 4 AND status = 1")->fetch_assoc()['total'];
$assigned_executives = $conn->query("SELECT COUNT(*) as total FROM employees WHERE grade_level = 4 AND team_id IS NOT NULL AND team_id != '' AND status = 1")->fetch_assoc()['total'];
$unassigned_count = $total_executives - $assigned_executives;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Team Alignment | GO2EXPORT MART</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once "includes/link.php"; ?>
    
    <style>
        /* Organization Chart Styles */
        .org-chart {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 500px;
            padding: 20px;
        }
        
        .ceo-node {
            background-color: #FCE8E6;
            color: #E4202C;
            padding: 15px 30px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 40px;
            position: relative;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .ceo-node h4 {
            margin: 0;
            font-size: 20px;
        }
        
        .ceo-node p {
            margin: 5px 0 0;
            opacity: 0.9;
            font-size: 12px;
        }
        
        .team-leaders-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
            position: relative;
            width: 100%;
        }
        
        .team-leader-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            width: 320px;
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .team-leader-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .leader-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 15px;
            text-align: center;
            position: relative;
        }
        
        .leader-avatar {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: -35px auto 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .leader-avatar i {
            font-size: 30px;
            color: #f5576c;
        }
        
        .leader-name {
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0 5px;
        }
        
        .leader-role {
            font-size: 12px;
            opacity: 0.9;
            margin-bottom: 0;
        }
        
        .team-stats {
            display: flex;
            justify-content: space-around;
            padding: 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #eef2f6;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-label {
            font-size: 11px;
            color: #6c757d;
        }
        
        .members-list {
            padding: 15px;
            max-height: 250px;
            overflow-y: auto;
        }
        
        .member-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .member-item:last-child {
            border-bottom: none;
        }
        
        .member-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .member-avatar {
            width: 32px;
            height: 32px;
            background: #e3f2fd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1976d2;
            font-weight: bold;
            font-size: 12px;
        }
        
        .member-name {
            font-size: 14px;
            font-weight: 500;
        }
        
        .member-id {
            font-size: 11px;
            color: #6c757d;
        }
        
        .empty-members {
            text-align: center;
            padding: 20px;
            color: #6c757d;
        }
        
        .connection-line {
            position: relative;
        }
        
        .connection-line::before {
            content: '';
            position: absolute;
            top: -20px;
            left: 50%;
            width: 2px;
            height: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        /* Unassigned Executives Section */
        .unassigned-section {
            margin-top: 40px;
        }
        
        .unassigned-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .unassigned-header {
            background: #ffc107;
            color: #856404;
            padding: 15px;
        }
        
        .executive-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
            padding: 20px;
        }
        
        .executive-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .executive-card:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        
        .executive-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .executive-avatar {
            width: 45px;
            height: 45px;
            background: #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }
        
        /* Drag and Drop Styles */
        .draggable-executive {
            cursor: grab;
            user-select: none;
        }
        
        .draggable-executive:active {
            cursor: grabbing;
        }
        
        .drop-zone {
            transition: all 0.3s;
            border: 2px dashed transparent;
        }
        
        .drop-zone.drag-over {
            border-color: #28a745;
            background: #f0fff4;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .team-leaders-container {
                flex-direction: column;
                align-items: center;
            }
            
            .team-leader-card {
                width: 100%;
                max-width: 350px;
            }
        }
        
        /* Animation */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .team-leader-card {
            animation: slideIn 0.5s ease-out forwards;
        }
        
        .connection-badge {
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
            background: #28a745;
            color: white;
            border-radius: 20px;
            padding: 2px 10px;
            font-size: 10px;
            white-space: nowrap;
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
                        <h4 class="mb-1">Team Alignment</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Team Alignment</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
                        <div class="dropdown">
                            <a href="javascript:void(0);" class="dropdown-toggle btn btn-outline-light px-2 shadow" data-bs-toggle="dropdown">
                                <i class="ti ti-package-export me-2"></i>Export
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a href="javascript:void(0);" class="dropdown-item" onclick="exportAsPDF()">
                                    <i class="ti ti-file-type-pdf me-1"></i>Export as PDF
                                </a>
                                <a href="javascript:void(0);" class="dropdown-item" onclick="exportAsImage()">
                                    <i class="ti ti-camera me-1"></i>Export as Image
                                </a>
                            </div>
                        </div>
                        <button onclick="window.location.reload()" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" title="Refresh">
                            <i class="ti ti-refresh"></i>
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
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Teams</h6>
                                        <h3 class="mb-0"><?php echo $total_teams; ?></h3>
                                    </div>
                                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                                        <i class="ti ti-users-group fs-24 text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Executives</h6>
                                        <h3 class="mb-0"><?php echo $total_executives; ?></h3>
                                    </div>
                                    <div class="bg-success bg-opacity-10 p-3 rounded">
                                        <i class="ti ti-user-star fs-24 text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Assigned Executives</h6>
                                        <h3 class="mb-0"><?php echo $assigned_executives; ?></h3>
                                    </div>
                                    <div class="bg-info bg-opacity-10 p-3 rounded">
                                        <i class="ti ti-user-check fs-24 text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Unassigned Executives</h6>
                                        <h3 class="mb-0"><?php echo $unassigned_count; ?></h3>
                                    </div>
                                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                                        <i class="ti ti-user-off fs-24 text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Organization Chart -->
                <div class="org-chart" id="orgChart">
                    <!-- CEO / Admin Node -->
                    <div class="ceo-node">
                        <i class="ti ti-crown fs-32 mb-2 d-block"></i>
                        <h4><?php echo $user_info['user_name']; ?></h4>
                        <p>Administrator | Grade Level 1</p>
                    </div>

                    <!-- Team Leaders Container -->
                    <div class="team-leaders-container" id="teamLeadersContainer">
                        <?php if ($teams_result->num_rows > 0): ?>
                            <?php while($team = $teams_result->fetch_assoc()): 
                                // Fetch team members for this team
                                $members_query = $conn->query("SELECT emp_id, user_id, user_name, user_num, user_mail FROM employees WHERE team_id = '{$team['id']}' AND grade_level = 4 AND status = 1");
                                $member_count = $members_query->num_rows;
                            ?>
                            <div class="team-leader-card drop-zone" data-team-id="<?php echo $team['id']; ?>" data-team-name="<?php echo htmlspecialchars($team['team_name']); ?>">
                                <div class="leader-header">
                                    <div class="leader-avatar">
                                        <i class="ti ti-user"></i>
                                    </div>
                                    <h5 class="leader-name"><?php echo htmlspecialchars($team['lead_name'] ?? 'Not Assigned'); ?></h5>
                                    <p class="leader-role">Team Lead | Grade Level 3</p>
                                </div>
                                
                                <div class="team-stats">
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo $member_count; ?></div>
                                        <div class="stat-label">Members</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value">₹<?php echo number_format($team['target_revenue']); ?></div>
                                        <div class="stat-label">Target</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo $team['status'] == 'active' ? 'Active' : 'Inactive'; ?></div>
                                        <div class="stat-label">Status</div>
                                    </div>
                                </div>
                                
                                <div class="members-list" id="members-list-<?php echo $team['id']; ?>">
                                    <small class="text-muted d-block mb-2">Team Members:</small>
                                    <?php if ($member_count > 0): ?>
                                        <?php while($member = $members_query->fetch_assoc()): ?>
                                            <div class="member-item" data-member-id="<?php echo $member['user_id']; ?>">
                                                <div class="member-info">
                                                    <div class="member-avatar">
                                                        <?php echo strtoupper(substr($member['user_name'], 0, 2)); ?>
                                                    </div>
                                                    <div>
                                                        <div class="member-name"><?php echo htmlspecialchars($member['user_name']); ?></div>
                                                        <div class="member-id"><?php echo htmlspecialchars($member['user_id']); ?></div>
                                                    </div>
                                                </div>
                                                <?php if ($grade_level == 1): ?>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="removeFromTeam('<?php echo $member['user_id']; ?>')" title="Remove from team">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <div class="empty-members">
                                            <i class="ti ti-users-off fs-32 text-muted"></i>
                                            <p class="mb-0 small">No members assigned</p>
                                            <small class="text-muted">Drag executives here to assign</small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($grade_level == 1): ?>
                                <div class="p-3 border-top">
                                    <button class="btn btn-sm btn-outline-primary w-100" onclick="showAssignModal(<?php echo $team['id']; ?>, '<?php echo htmlspecialchars($team['team_name']); ?>')">
                                        <i class="ti ti-user-plus"></i> Add Member
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-5 w-100">
                                <i class="ti ti-users-group fs-64 text-muted"></i>
                                <h5 class="mt-3">No Teams Created Yet</h5>
                                <p class="text-muted">Create a team to start building your organization structure</p>
                                <?php if ($grade_level == 1): ?>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeamModal">
                                        <i class="ti ti-plus"></i> Create Your First Team
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Unassigned Executives Section (Only for Admin) -->
                <?php if ($grade_level == 1 && $unassigned_executives->num_rows > 0): ?>
                <div class="unassigned-section">
                    <div class="unassigned-card">
                        <div class="unassigned-header">
                            <h5 class="mb-0"><i class="ti ti-user-off me-2"></i> Unassigned Executives</h5>
                            <small>Drag these executives to any team card above to assign them</small>
                        </div>
                        <div class="executive-grid" id="unassignedExecutives">
                            <?php while($executive = $unassigned_executives->fetch_assoc()): ?>
                                <div class="executive-card draggable-executive" draggable="true" data-executive-id="<?php echo $executive['user_id']; ?>" data-executive-name="<?php echo htmlspecialchars($executive['user_name']); ?>" data-executive-email="<?php echo htmlspecialchars($executive['user_mail']); ?>">
                                    <div class="executive-info">
                                        <div class="executive-avatar">
                                            <?php echo strtoupper(substr($executive['user_name'], 0, 2)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($executive['user_name']); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($executive['user_id']); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($executive['user_num']); ?></div>
                                        </div>
                                    </div>
                                    <button class="btn btn-sm btn-primary" onclick="quickAssign('<?php echo $executive['user_id']; ?>', '<?php echo htmlspecialchars($executive['user_name']); ?>')">
                                        <i class="ti ti-user-plus"></i> Assign
                                    </button>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- All Executives Table View -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-transparent border-bottom">
                        <h5 class="mb-0">All Executives</h5>
                        <small class="text-muted">Complete list of all executives with their team assignments</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="executivesTable">
                                <thead>
                                    <tr>
                                        <th>Executive Name</th>
                                        <th>Employee ID</th>
                                        <th>Contact</th>
                                        <th>Email</th>
                                        <th>Assigned Team</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $all_execs = $conn->query("SELECT e.*, t.team_name FROM employees e LEFT JOIN teams t ON e.team_id = t.id WHERE e.grade_level = 4 AND e.status = 1 ORDER BY e.user_name");
                                    while($exec = $all_execs->fetch_assoc()): 
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm rounded-circle bg-primary bg-opacity-10 me-2">
                                                    <span class="text-primary"><?php echo substr($exec['user_name'], 0, 2); ?></span>
                                                </div>
                                                <?php echo htmlspecialchars($exec['user_name']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($exec['user_id']); ?></td>
                                        <td><?php echo htmlspecialchars($exec['user_num']); ?></td>
                                        <td><?php echo htmlspecialchars($exec['user_mail']); ?></td>
                                        <td>
                                            <?php if (!empty($exec['team_name'])): ?>
                                                <span class="badge bg-info bg-opacity-10 text-info">
                                                    <i class="ti ti-users"></i> <?php echo htmlspecialchars($exec['team_name']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning bg-opacity-10 text-warning">Not Assigned</span>
                                            <?php endif; ?>
                                         </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button class="btn btn-sm btn-outline-primary" onclick="assignToTeam('<?php echo $exec['user_id']; ?>', '<?php echo htmlspecialchars($exec['user_name']); ?>')">
                                                    <i class="ti ti-user-plus"></i> Assign
                                                </button>
                                                <?php if (!empty($exec['team_name'])): ?>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="removeFromTeam('<?php echo $exec['user_id']; ?>')">
                                                        <i class="ti ti-user-minus"></i> Remove
                                                    </button>
                                                <?php endif; ?>
                                            </div>
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

    <!-- Assign Executive Modal -->
    <div class="modal fade" id="assignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Executive to Team</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="executive_id" id="modal_executive_id">
                        <div class="mb-3">
                            <label class="form-label">Executive Name</label>
                            <input type="text" class="form-control" id="modal_executive_name" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Team <span class="text-danger">*</span></label>
                            <select class="form-select" name="team_id" id="modal_team_id" required>
                                <option value="">Select a team</option>
                                <?php 
                                $teams_select = $conn->query("SELECT id, team_name FROM teams WHERE status = 'active'");
                                while($team = $teams_select->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['team_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="assign_executive" class="btn btn-primary">Assign to Team</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Quick Assign Modal -->
    <div class="modal fade" id="quickAssignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Quick Assign Executive</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="executive_id" id="quick_executive_id">
                        <div class="mb-3">
                            <label class="form-label">Executive Name</label>
                            <input type="text" class="form-control" id="quick_executive_name" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Team</label>
                            <select class="form-select" name="team_id" required>
                                <option value="">Select a team</option>
                                <?php 
                                $teams_quick = $conn->query("SELECT id, team_name FROM teams WHERE status = 'active'");
                                while($team = $teams_quick->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['team_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="assign_executive" class="btn btn-primary">Assign</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Team Modal (Quick) -->
    <div class="modal fade" id="addTeamModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Team</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="team.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Team Name</label>
                            <input type="text" class="form-control" name="team_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Team Lead</label>
                            <select class="form-select" name="team_lead" required>
                                <option value="">Select Team Lead</option>
                                <?php 
                                $team_leads = $conn->query("SELECT user_id, user_name FROM employees WHERE grade_level = 3 AND status = 1");
                                while($tl = $team_leads->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $tl['user_id']; ?>"><?php echo htmlspecialchars($tl['user_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Target Revenue</label>
                            <input type="number" class="form-control" name="target_revenue">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_team" class="btn btn-primary">Create Team</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include_once "includes/footer-link.php"; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            if ($('#executivesTable').length) {
                $('#executivesTable').DataTable({
                    pageLength: 10,
                    order: [[0, 'asc']],
                    language: {
                        search: "Search executives:",
                        lengthMenu: "Show _MENU_ executives",
                        info: "Showing _START_ to _END_ of _TOTAL_ executives"
                    }
                });
            }
            
            // Drag and Drop functionality
            initializeDragAndDrop();
        });
        
        function initializeDragAndDrop() {
            const draggables = document.querySelectorAll('.draggable-executive');
            const dropZones = document.querySelectorAll('.drop-zone');
            
            draggables.forEach(draggable => {
                draggable.addEventListener('dragstart', dragStart);
                draggable.addEventListener('dragend', dragEnd);
            });
            
            dropZones.forEach(zone => {
                zone.addEventListener('dragover', dragOver);
                zone.addEventListener('dragleave', dragLeave);
                zone.addEventListener('drop', drop);
            });
        }
        
        function dragStart(e) {
            e.dataTransfer.setData('text/plain', JSON.stringify({
                id: this.dataset.executiveId,
                name: this.dataset.executiveName,
                email: this.dataset.executiveEmail
            }));
            this.style.opacity = '0.5';
        }
        
        function dragEnd(e) {
            this.style.opacity = '1';
            document.querySelectorAll('.drop-zone').forEach(zone => {
                zone.classList.remove('drag-over');
            });
        }
        
        function dragOver(e) {
            e.preventDefault();
            this.classList.add('drag-over');
        }
        
        function dragLeave(e) {
            this.classList.remove('drag-over');
        }
        
        function drop(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
            
            const executiveData = JSON.parse(e.dataTransfer.getData('text/plain'));
            const teamId = this.dataset.teamId;
            const teamName = this.dataset.teamName;
            
            if (confirm(`Assign ${executiveData.name} to ${teamName}?`)) {
                // Submit assignment via AJAX
                $.ajax({
                    url: 'ajax/assign-executive-ajax.php',
                    type: 'POST',
                    data: {
                        executive_id: executiveData.id,
                        team_id: teamId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showNotification('success', response.message);
                            location.reload();
                        } else {
                            showNotification('error', response.message);
                        }
                    },
                    error: function() {
                        showNotification('error', 'Error assigning executive');
                    }
                });
            }
        }
        
        function assignToTeam(empId, empName) {
            $('#modal_executive_id').val(empId);
            $('#modal_executive_name').val(empName);
            $('#assignModal').modal('show');
        }
        
        function quickAssign(empId, empName) {
            $('#quick_executive_id').val(empId);
            $('#quick_executive_name').val(empName);
            $('#quickAssignModal').modal('show');
        }
        
        function removeFromTeam(empId) {
            if (confirm('Are you sure you want to remove this executive from their team?')) {
                $('<form>', {
                    method: 'POST',
                    action: ''
                }).append($('<input>', {
                    type: 'hidden',
                    name: 'remove_from_team',
                    value: '1'
                })).append($('<input>', {
                    type: 'hidden',
                    name: 'employee_id',
                    value: empId
                })).appendTo('body').submit();
            }
        }
        
        function showAssignModal(teamId, teamName) {
            // You can implement this to show a modal for assigning executives to this team
            showNotification('info', `Click "Assign" button on any executive to add them to ${teamName}`);
        }
        
        function exportAsPDF() {
            const { jsPDF } = window.jspdf;
            const element = document.getElementById('orgChart');
            
            html2canvas(element, {
                scale: 2,
                backgroundColor: '#ffffff'
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const pdf = new jsPDF({
                    orientation: 'landscape',
                    unit: 'mm',
                    format: 'a4'
                });
                const imgWidth = 280;
                const imgHeight = (canvas.height * imgWidth) / canvas.width;
                pdf.addImage(imgData, 'PNG', 10, 10, imgWidth, imgHeight);
                pdf.save('team-alignment.pdf');
            });
        }
        
        function exportAsImage() {
            const element = document.getElementById('orgChart');
            html2canvas(element, {
                scale: 2,
                backgroundColor: '#ffffff'
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'team-alignment.png';
                link.href = canvas.toDataURL();
                link.click();
            });
        }
        
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
    </script>
    
    <style>
        .custom-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideInRight 0.3s ease-out;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .avatar-sm {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .fs-64 {
            font-size: 64px;
        }
        
        .fs-32 {
            font-size: 32px;
        }
        
        .fs-24 {
            font-size: 24px;
        }
        
        .drop-zone {
            transition: all 0.3s;
        }
        
        .drop-zone.drag-over {
            border: 2px dashed #28a745;
            background: #f0fff4;
            transform: scale(1.02);
        }
        
        .draggable-executive {
            cursor: grab;
            transition: all 0.3s;
        }
        
        .draggable-executive:active {
            cursor: grabbing;
        }
        
        .draggable-executive:hover {
            transform: translateX(5px);
            background: #e9ecef;
        }
    </style>
</body>
</html>