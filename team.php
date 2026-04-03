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

// Handle Add Team (Admin only - grade_level 1)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_team'])) {
    $team_name = mysqli_real_escape_string($conn, $_POST['team_name']);
    $team_lead = mysqli_real_escape_string($conn, $_POST['team_lead']);
    $target_revenue = mysqli_real_escape_string($conn, $_POST['target_revenue']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $insert_team = "INSERT INTO teams (team_name, team_lead, target_revenue, status, created_by, created_at) 
                    VALUES ('$team_name', '$team_lead', '$target_revenue', '$status', '$user_id', NOW())";
    
    if ($conn->query($insert_team)) {
        $team_id = $conn->insert_id;
        
        // Update team_lead's team_id in employees table
        $update_lead = "UPDATE employees SET team_id = '$team_id', user_role = 'Team Lead' WHERE user_id = '$team_lead'";
        $conn->query($update_lead);
        
        $success_message = "Team created successfully!";
    } else {
        $error_message = "Error creating team: " . $conn->error;
    }
}

// Handle Assign/Reassign Executive to Team (Admin only)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_executive'])) {
    $executive_id = mysqli_real_escape_string($conn, $_POST['executive_id']);
    $team_id = mysqli_real_escape_string($conn, $_POST['team_id']);
    
    // Check if executive is already in a team
    $check_current = $conn->query("SELECT team_id FROM employees WHERE user_id = '$executive_id'");
    $current_team = $check_current->fetch_assoc();
    
    if ($current_team['team_id']) {
        // Reassigning - will be handled by the update
        $action_type = "reassigned";
    } else {
        $action_type = "assigned";
    }
    
    $update_executive = "UPDATE employees SET team_id = '$team_id' WHERE user_id = '$executive_id'";
    if ($conn->query($update_executive)) {
        $success_message = "Executive " . ($action_type == "reassigned" ? "reassigned" : "assigned") . " successfully!";
    } else {
        $error_message = "Error assigning executive: " . $conn->error;
    }
}

// Handle Remove from Team (Admin only)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_from_team'])) {
    $employee_id = mysqli_real_escape_string($conn, $_POST['employee_id']);
    $update_employee = "UPDATE employees SET team_id = NULL WHERE user_id = '$employee_id'";
    if ($conn->query($update_employee)) {
        $success_message = "Employee removed from team successfully!";
    } else {
        $error_message = "Error removing employee: " . $conn->error;
    }
}

// Handle Bulk Assign Executives
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_assign'])) {
    $team_id = mysqli_real_escape_string($conn, $_POST['bulk_team_id']);
    $executives = $_POST['bulk_executives'];
    
    if (!empty($executives)) {
        $success_count = 0;
        foreach ($executives as $exec_id) {
            $update = $conn->query("UPDATE employees SET team_id = '$team_id' WHERE user_id = '$exec_id'");
            if ($update) $success_count++;
        }
        $success_message = "$success_count executives assigned to team successfully!";
    } else {
        $error_message = "Please select at least one executive to assign";
    }
}

// Handle Delete Team (Admin only)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_team'])) {
    $team_id = mysqli_real_escape_string($conn, $_POST['team_id']);
    
    // First remove team_id from all employees in this team
    $conn->query("UPDATE employees SET team_id = NULL WHERE team_id = '$team_id'");
    
    // Then delete the team
    $delete_team = "DELETE FROM teams WHERE id = '$team_id'";
    if ($conn->query($delete_team)) {
        $success_message = "Team deleted successfully!";
    } else {
        $error_message = "Error deleting team: " . $conn->error;
    }
}

// Handle Edit Team
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_team'])) {
    $team_id = mysqli_real_escape_string($conn, $_POST['team_id']);
    $team_name = mysqli_real_escape_string($conn, $_POST['team_name']);
    $team_lead = mysqli_real_escape_string($conn, $_POST['team_lead']);
    $target_revenue = mysqli_real_escape_string($conn, $_POST['target_revenue']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $update_team = "UPDATE teams SET 
                    team_name = '$team_name', 
                    team_lead = '$team_lead', 
                    target_revenue = '$target_revenue', 
                    status = '$status',
                    updated_at = NOW()
                    WHERE id = '$team_id'";
    
    if ($conn->query($update_team)) {
        $success_message = "Team updated successfully!";
    } else {
        $error_message = "Error updating team: " . $conn->error;
    }
}

// Fetch all teams with member counts
$teams_query = "SELECT t.*, 
                COUNT(CASE WHEN e.grade_level = 4 THEN 1 END) as member_count,
                e.user_name as lead_name
                FROM teams t 
                LEFT JOIN employees e ON t.team_lead = e.user_id
                LEFT JOIN employees m ON m.team_id = t.id
                GROUP BY t.id
                ORDER BY t.created_at DESC";
$teams_result = $conn->query($teams_query);

// Fetch team leaders (grade_level 3)
$team_leaders = $conn->query("SELECT user_id, user_name FROM employees WHERE grade_level = 3 AND status = 1");

// Fetch all executives with their team info
$all_executives = $conn->query("SELECT e.*, t.team_name, t.id as team_id 
                                FROM employees e 
                                LEFT JOIN teams t ON e.team_id = t.id 
                                WHERE e.grade_level = 4 AND e.status = 1 
                                ORDER BY e.user_name");

// Fetch unassigned executives for quick assign
$unassigned_executives = $conn->query("SELECT user_id, user_name FROM employees WHERE grade_level = 4 AND (team_id IS NULL OR team_id = '') AND status = 1");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Team Management | GO2EXPORT MART</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once "includes/link.php"; ?>
    
    <style>
        .team-card {
            transition: transform 0.2s, box-shadow 0.2s;
            margin-bottom: 20px;
        }
        
        .team-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .team-avatar {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .team-stats {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
        }
        
        .team-stats h6 {
            margin-bottom: 5px;
            font-size: 20px;
            font-weight: bold;
        }
        
        .team-stats p {
            margin-bottom: 0;
            font-size: 11px;
            color: #6c757d;
        }
        
        .executive-row {
            transition: background 0.3s;
        }
        
        .executive-row:hover {
            background: #f8f9fa;
        }
        
        .assign-badge {
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .assign-badge:hover {
            transform: scale(1.05);
        }
        
        .quick-assign-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 100;
        }
        
        @media (max-width: 768px) {
            .team-card {
                margin-bottom: 15px;
            }
            .quick-assign-btn {
                bottom: 20px;
                right: 20px;
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
        
        .custom-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
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
                        <h4 class="mb-1">Team Management</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Teams</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
                        <?php if ($grade_level == 1): ?>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeamModal">
                                <i class="ti ti-plus me-2"></i>Create New Team
                            </button>
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#bulkAssignModal">
                                <i class="ti ti-layers me-2"></i>Bulk Assign
                            </button>
                        <?php endif; ?>
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

                <!-- Teams Grid -->
                <div class="row">
                    <?php if ($teams_result->num_rows > 0): ?>
                        <?php while($team = $teams_result->fetch_assoc()): 
                            // Fetch team members
                            $members_query = $conn->query("SELECT user_id, user_name FROM employees WHERE team_id = '{$team['id']}' AND grade_level = 4");
                            $member_count = $members_query->num_rows;
                            
                            // Fetch team leads for this team
                            $team_lead = $conn->query("SELECT user_name FROM employees WHERE user_id = '{$team['team_lead']}'")->fetch_assoc();
                        ?>
                        <div class="col-xl-4 col-md-6">
                            <div class="card team-card shadow-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="team-avatar bg-primary bg-opacity-10">
                                            <i class="ti ti-users-group text-primary"></i>
                                        </div>
                                        <span class="status-badge <?php echo $team['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo ucfirst($team['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <h5 class="card-title mb-2"><?php echo htmlspecialchars($team['team_name']); ?></h5>
                                    <p class="text-muted small mb-3">
                                        <i class="ti ti-user-check"></i> Team Lead: <?php echo htmlspecialchars($team_lead['user_name'] ?? 'Not Assigned'); ?>
                                    </p>
                                    
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <div class="team-stats">
                                                <h6><?php echo $member_count; ?></h6>
                                                <p>Members</p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="team-stats">
                                                <h6>₹<?php echo number_format($team['target_revenue']); ?></h6>
                                                <p>Target Revenue</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if ($grade_level == 1): ?>
                                    <div class="d-flex gap-2 mt-3">
                                        <button class="btn btn-sm btn-outline-primary" onclick="editTeam(<?php echo $team['id']; ?>, '<?php echo htmlspecialchars($team['team_name']); ?>', '<?php echo $team['team_lead']; ?>', '<?php echo $team['target_revenue']; ?>', '<?php echo $team['status']; ?>')">
                                            <i class="ti ti-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-info" onclick="viewTeamMembers(<?php echo $team['id']; ?>, '<?php echo htmlspecialchars($team['team_name']); ?>')">
                                            <i class="ti ti-users"></i> Members
                                        </button>
                                        <button class="btn btn-sm btn-outline-success" onclick="quickAssignToTeam(<?php echo $team['id']; ?>, '<?php echo htmlspecialchars($team['team_name']); ?>')">
                                            <i class="ti ti-user-plus"></i> Add Members
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteTeam(<?php echo $team['id']; ?>)">
                                            <i class="ti ti-trash"></i> Delete
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="text-center py-5">
                                <i class="ti ti-users-group fs-64 text-muted"></i>
                                <h5 class="mt-3">No Teams Found</h5>
                                <p class="text-muted"><?php echo ($grade_level == 1) ? 'Click "Create New Team" to get started.' : 'No teams have been created yet.'; ?></p>
                                <?php if ($grade_level == 1): ?>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeamModal">
                                        <i class="ti ti-plus"></i> Create Your First Team
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Executives Section (Only for Admin) -->
                <?php if ($grade_level == 1): ?>
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h5 class="mb-0">Executive Management</h5>
                            <small class="text-muted">Assign, reassign or remove executives from teams</small>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-outline-primary" onclick="window.location.reload()">
                                <i class="ti ti-refresh"></i> Refresh
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="executivesTable">
                                <thead>
                                    <tr>
                                        <th width="30"><input type="checkbox" id="selectAllExecutives" class="custom-checkbox"></th>
                                        <th>Executive Name</th>
                                        <th>Employee ID</th>
                                        <th>Contact</th>
                                        <th>Email</th>
                                        <th>Current Team</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($executive = $all_executives->fetch_assoc()): ?>
                                    <tr class="executive-row">
                                        <td>
                                            <input type="checkbox" class="executive-checkbox custom-checkbox" value="<?php echo $executive['user_id']; ?>">
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm rounded-circle bg-primary bg-opacity-10 me-2">
                                                    <span class="text-primary"><?php echo substr($executive['user_name'], 0, 2); ?></span>
                                                </div>
                                                <?php echo htmlspecialchars($executive['user_name']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($executive['user_id']); ?></td>
                                        <td><?php echo htmlspecialchars($executive['user_num']); ?></td>
                                        <td><?php echo htmlspecialchars($executive['user_mail']); ?></td>
                                        <td>
                                            <?php if (!empty($executive['team_name'])): ?>
                                                <span class="badge bg-info bg-opacity-10 text-info">
                                                    <i class="ti ti-users"></i> <?php echo htmlspecialchars($executive['team_name']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                                    <i class="ti ti-user-off"></i> Not Assigned
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button class="btn btn-sm btn-outline-primary" onclick="assignToTeam('<?php echo $executive['user_id']; ?>', '<?php echo htmlspecialchars($executive['user_name']); ?>')">
                                                    <i class="ti ti-user-plus"></i> <?php echo !empty($executive['team_name']) ? 'Reassign' : 'Assign'; ?>
                                                </button>
                                                <?php if (!empty($executive['team_name'])): ?>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="removeFromTeam('<?php echo $executive['user_id']; ?>')">
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
                <?php endif; ?>
            </div>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- Add Team Modal -->
    <div class="modal fade" id="addTeamModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Team</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Team Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="team_name" placeholder="e.g., Sales Team Alpha" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Team Lead <span class="text-danger">*</span></label>
                                <select class="form-select" name="team_lead" required>
                                    <option value="">Select Team Lead</option>
                                    <?php 
                                    $team_leaders = $conn->query("SELECT user_id, user_name FROM employees WHERE grade_level = 3 OR 2 AND status = 1");
                                    while($tl = $team_leaders->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $tl['user_id']; ?>"><?php echo htmlspecialchars($tl['user_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                                <small class="text-muted">Only Team Leaders (Grade Level 3) can be selected</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Target Revenue (₹)</label>
                                <input type="number" class="form-control" name="target_revenue" placeholder="Enter target revenue">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
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

    <!-- Edit Team Modal -->
    <div class="modal fade" id="editTeamModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Team</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <input type="hidden" name="team_id" id="edit_team_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Team Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="team_name" id="edit_team_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Team Lead <span class="text-danger">*</span></label>
                                <select class="form-select" name="team_lead" id="edit_team_lead" required>
                                    <option value="">Select Team Lead</option>
                                    <?php 
                                    $team_leaders_edit = $conn->query("SELECT user_id, user_name FROM employees WHERE grade_level = 3 AND status = 1");
                                    while($tl = $team_leaders_edit->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $tl['user_id']; ?>"><?php echo htmlspecialchars($tl['user_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Target Revenue (₹)</label>
                                <input type="number" class="form-control" name="target_revenue" id="edit_target_revenue">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="edit_status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_team" class="btn btn-primary">Update Team</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Assign/Reassign Executive Modal -->
    <div class="modal fade" id="assignTeamModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignModalTitle">Assign Executive to Team</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="executive_id" id="assign_executive_id">
                        <div class="mb-3">
                            <label class="form-label">Executive Name</label>
                            <input type="text" class="form-control" id="assign_executive_name" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Team <span class="text-danger">*</span></label>
                            <select class="form-select" name="team_id" id="assign_team_select" required>
                                <option value="">Select a team</option>
                                <?php 
                                $teams_for_assign = $conn->query("SELECT id, team_name FROM teams WHERE status = 'active'");
                                while($team = $teams_for_assign->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['team_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="alert alert-info" id="reassignWarning" style="display: none;">
                            <i class="ti ti-info-circle"></i> This executive is already assigned to a team. Reassigning will move them to the new team.
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

    <!-- Quick Assign to Team Modal -->
    <div class="modal fade" id="quickAssignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Members to <span id="quickTeamName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <input type="hidden" name="team_id" id="quick_team_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Select Executives to Add</label>
                            <select class="form-select" name="executive_id" required>
                                <option value="">Select Executive</option>
                                <?php 
                                $unassigned = $conn->query("SELECT user_id, user_name FROM employees WHERE grade_level = 4 AND (team_id IS NULL OR team_id = '') AND status = 1");
                                while($exec = $unassigned->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $exec['user_id']; ?>"><?php echo htmlspecialchars($exec['user_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="assign_executive" class="btn btn-primary">Add to Team</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Assign Modal -->
    <div class="modal fade" id="bulkAssignModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Assign Executives to Team</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Select Team <span class="text-danger">*</span></label>
                            <select class="form-select" name="bulk_team_id" id="bulk_team_id" required>
                                <option value="">Select a team</option>
                                <?php 
                                $teams_bulk = $conn->query("SELECT id, team_name FROM teams WHERE status = 'active'");
                                while($team = $teams_bulk->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['team_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Executives to Assign</label>
                            <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                <?php 
                                $all_execs = $conn->query("SELECT user_id, user_name, team_name FROM employees e LEFT JOIN teams t ON e.team_id = t.id WHERE e.grade_level = 4 AND e.status = 1 ORDER BY e.user_name");
                                while($exec = $all_execs->fetch_assoc()):
                                ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="bulk_executives[]" value="<?php echo $exec['user_id']; ?>" id="exec_<?php echo $exec['user_id']; ?>">
                                    <label class="form-check-label" for="exec_<?php echo $exec['user_id']; ?>">
                                        <?php echo htmlspecialchars($exec['user_name']); ?>
                                        <?php if($exec['team_name']): ?>
                                            <small class="text-muted">(Currently in: <?php echo htmlspecialchars($exec['team_name']); ?>)</small>
                                        <?php else: ?>
                                            <small class="text-success">(Unassigned)</small>
                                        <?php endif; ?>
                                    </label>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="bulk_assign" class="btn btn-primary">Assign Selected</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Team Members Modal -->
    <div class="modal fade" id="viewMembersModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Team Members - <span id="teamNameDisplay"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Member Name</th>
                                    <th>Employee ID</th>
                                    <th>Contact</th>
                                    <th>Email</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="teamMembersList">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include_once "includes/footer-link.php"; ?>

    <script>
        $(document).ready(function() {
            // Initialize DataTable for executives
            if ($('#executivesTable').length) {
                $('#executivesTable').DataTable({
                    pageLength: 10,
                    order: [[1, 'asc']],
                    language: {
                        search: "Search executives:",
                        lengthMenu: "Show _MENU_ executives",
                        info: "Showing _START_ to _END_ of _TOTAL_ executives"
                    }
                });
            }
            
            // Select All Executives
            $('#selectAllExecutives').change(function() {
                $('.executive-checkbox').prop('checked', $(this).prop('checked'));
            });
        });
        
        function editTeam(teamId, teamName, teamLead, targetRevenue, status) {
            $('#edit_team_id').val(teamId);
            $('#edit_team_name').val(teamName);
            $('#edit_team_lead').val(teamLead);
            $('#edit_target_revenue').val(targetRevenue);
            $('#edit_status').val(status);
            $('#editTeamModal').modal('show');
        }
        
        function viewTeamMembers(teamId, teamName) {
            $('#teamNameDisplay').text(teamName);
            $('#teamMembersList').html('<tr><td colspan="5" class="text-center"><div class="spinner-border text-primary"></div></td></tr>');
            
            $.ajax({
                url: 'ajax/get-team-members.php',
                type: 'GET',
                data: { team_id: teamId },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        let html = '';
                        response.data.forEach(member => {
                            html += `
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm rounded-circle bg-primary bg-opacity-10 me-2">
                                                <span class="text-primary">${member.user_name.substring(0, 2)}</span>
                                            </div>
                                            ${member.user_name}
                                        </div>
                                    </td>
                                    <td>${member.user_id}</td>
                                    <td>${member.user_num || '-'}</td>
                                    <td>${member.user_mail || '-'}</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger" onclick="removeFromTeam('${member.user_id}')">
                                            <i class="ti ti-user-minus"></i> Remove
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });
                        $('#teamMembersList').html(html);
                    } else {
                        $('#teamMembersList').html('<tr><td colspan="5" class="text-center text-muted">No members in this team</td></tr>');
                    }
                    $('#viewMembersModal').modal('show');
                },
                error: function() {
                    $('#teamMembersList').html('<tr><td colspan="5" class="text-center text-danger">Error loading members</td></tr>');
                }
            });
        }
        
        function assignToTeam(empId, empName) {
            // Check if executive is already in a team
            $.ajax({
                url: 'ajax/check-executive-team.php',
                type: 'GET',
                data: { user_id: empId },
                dataType: 'json',
                success: function(response) {
                    $('#assign_executive_id').val(empId);
                    $('#assign_executive_name').val(empName);
                    
                    if (response.has_team) {
                        $('#assignModalTitle').text('Reassign Executive to Team');
                        $('#reassignWarning').show();
                        $('#reassignWarning').html('<i class="ti ti-info-circle"></i> This executive is currently in <strong>' + response.team_name + '</strong>. Reassigning will move them to the new team.');
                    } else {
                        $('#assignModalTitle').text('Assign Executive to Team');
                        $('#reassignWarning').hide();
                    }
                    
                    $('#assignTeamModal').modal('show');
                }
            });
        }
        
        function quickAssignToTeam(teamId, teamName) {
            $('#quick_team_id').val(teamId);
            $('#quickTeamName').text(teamName);
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
        
        function deleteTeam(teamId) {
            if (confirm('Are you sure you want to delete this team? All members will be unassigned.')) {
                $('<form>', {
                    method: 'POST',
                    action: ''
                }).append($('<input>', {
                    type: 'hidden',
                    name: 'delete_team',
                    value: '1'
                })).append($('<input>', {
                    type: 'hidden',
                    name: 'team_id',
                    value: teamId
                })).appendTo('body').submit();
            }
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
        
        .avatar {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .fs-64 {
            font-size: 64px;
        }
        
        .custom-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        
        .gap-1 {
            gap: 0.25rem;
        }
    </style>
</body>
</html>