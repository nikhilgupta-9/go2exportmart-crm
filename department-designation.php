<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['grade_level'] != 1) {
    header('location: index.php');
    exit;
}

include_once "partials/_dbconnect.php";

$success_message = '';
$error_message = '';

// Handle Department Creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_department'])) {
    $dept_name = mysqli_real_escape_string($conn, $_POST['dept_name']);
    $dept_code = mysqli_real_escape_string($conn, $_POST['dept_code']);
    $dept_description = mysqli_real_escape_string($conn, $_POST['dept_description']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Check if department already exists
    $check_sql = "SELECT id FROM departments WHERE dept_name = '$dept_name' OR dept_code = '$dept_code'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        $error_message = "Department name or code already exists!";
    } else {
        $insert_sql = "INSERT INTO departments (dept_name, dept_code, description, status, created_by, created_at) 
                       VALUES ('$dept_name', '$dept_code', '$dept_description', '$status', '{$_SESSION['user_id']}', NOW())";
        
        if ($conn->query($insert_sql)) {
            $success_message = "Department created successfully!";
        } else {
            $error_message = "Error creating department: " . $conn->error;
        }
    }
}

// Handle Designation Creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_designation'])) {
    $dept_id = intval($_POST['dept_id']);
    $designation_name = mysqli_real_escape_string($conn, $_POST['designation_name']);
    $designation_level = mysqli_real_escape_string($conn, $_POST['designation_level']);
    $grade_level = intval($_POST['grade_level']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Check if designation already exists in this department
    $check_sql = "SELECT id FROM designations WHERE dept_id = '$dept_id' AND designation_name = '$designation_name'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        $error_message = "Designation already exists in this department!";
    } else {
        $insert_sql = "INSERT INTO designations (dept_id, designation_name, designation_level, grade_level, description, status, created_by, created_at) 
                       VALUES ('$dept_id', '$designation_name', '$designation_level', '$grade_level', '$description', '$status', '{$_SESSION['user_id']}', NOW())";
        
        if ($conn->query($insert_sql)) {
            $success_message = "Designation created successfully!";
        } else {
            $error_message = "Error creating designation: " . $conn->error;
        }
    }
}

// Handle Department Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_department'])) {
    $dept_id = intval($_POST['dept_id']);
    $dept_name = mysqli_real_escape_string($conn, $_POST['dept_name']);
    $dept_code = mysqli_real_escape_string($conn, $_POST['dept_code']);
    $dept_description = mysqli_real_escape_string($conn, $_POST['dept_description']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $update_sql = "UPDATE departments SET 
                   dept_name = '$dept_name',
                   dept_code = '$dept_code',
                   description = '$dept_description',
                   status = '$status',
                   updated_at = NOW()
                   WHERE id = '$dept_id'";
    
    if ($conn->query($update_sql)) {
        $success_message = "Department updated successfully!";
    } else {
        $error_message = "Error updating department: " . $conn->error;
    }
}

// Handle Designation Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_designation'])) {
    $desig_id = intval($_POST['desig_id']);
    $dept_id = intval($_POST['dept_id']);
    $designation_name = mysqli_real_escape_string($conn, $_POST['designation_name']);
    $designation_level = mysqli_real_escape_string($conn, $_POST['designation_level']);
    $grade_level = intval($_POST['grade_level']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $update_sql = "UPDATE designations SET 
                   dept_id = '$dept_id',
                   designation_name = '$designation_name',
                   designation_level = '$designation_level',
                   grade_level = '$grade_level',
                   description = '$description',
                   status = '$status',
                   updated_at = NOW()
                   WHERE id = '$desig_id'";
    
    if ($conn->query($update_sql)) {
        $success_message = "Designation updated successfully!";
    } else {
        $error_message = "Error updating designation: " . $conn->error;
    }
}

// Handle Delete Department
if (isset($_GET['delete_dept'])) {
    $dept_id = intval($_GET['delete_dept']);
    
    // Check if department has designations
    $check_sql = "SELECT COUNT(*) as count FROM designations WHERE dept_id = '$dept_id'";
    $check_result = $conn->query($check_sql);
    $count = $check_result->fetch_assoc()['count'];
    
    if ($count > 0) {
        $error_message = "Cannot delete department. Please delete all designations under this department first.";
    } else {
        $delete_sql = "DELETE FROM departments WHERE id = '$dept_id'";
        if ($conn->query($delete_sql)) {
            $success_message = "Department deleted successfully!";
        } else {
            $error_message = "Error deleting department: " . $conn->error;
        }
    }
}

// Handle Delete Designation
if (isset($_GET['delete_desig'])) {
    $desig_id = intval($_GET['delete_desig']);
    
    $delete_sql = "DELETE FROM designations WHERE id = '$desig_id'";
    if ($conn->query($delete_sql)) {
        $success_message = "Designation deleted successfully!";
    } else {
        $error_message = "Error deleting designation: " . $conn->error;
    }
}

// Fetch all departments
$departments_sql = "SELECT * FROM departments ORDER BY created_at DESC";
$departments_result = $conn->query($departments_sql);

// Fetch all designations with department info
$designations_sql = "SELECT d.*, dp.dept_name, dp.dept_code 
                     FROM designations d 
                     LEFT JOIN departments dp ON d.dept_id = dp.id 
                     ORDER BY dp.dept_name, d.designation_level";
$designations_result = $conn->query($designations_sql);

// Get statistics
$total_depts = $conn->query("SELECT COUNT(*) as total FROM departments")->fetch_assoc()['total'];
$total_designations = $conn->query("SELECT COUNT(*) as total FROM designations")->fetch_assoc()['total'];
$active_depts = $conn->query("SELECT COUNT(*) as total FROM departments WHERE status = 'active'")->fetch_assoc()['total'];
$active_designations = $conn->query("SELECT COUNT(*) as total FROM designations WHERE status = 'active'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Department & Designation Management | GO2EXPORT MART</title>
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
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #fff3cd;
            color: #856404;
        }
        
        .dept-card {
            background: white;
            border-radius: 16px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .dept-card:hover {
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .dept-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
        }
        
        .designation-item {
            padding: 12px 15px;
            border-bottom: 1px solid #eef2f6;
            transition: background 0.3s;
        }
        
        .designation-item:hover {
            background: #f8f9fa;
        }
        
        .designation-item:last-child {
            border-bottom: none;
        }
        
        .grade-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            background: #e9ecef;
            color: #495057;
        }
        
        .action-buttons .btn {
            margin: 0 2px;
            padding: 4px 8px;
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
                        <h4 class="mb-1">Department & Designation Management</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Departments & Designations</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                            <i class="ti ti-plus me-2"></i>Add Department
                        </button>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addDesignationModal">
                            <i class="ti ti-plus me-2"></i>Add Designation
                        </button>
                        <button onclick="window.location.reload()" class="btn btn-icon btn-outline-light shadow">
                            <i class="ti ti-refresh"></i>
                        </button>
                    </div>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="ti ti-check-circle me-2"></i> <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="ti ti-alert-circle me-2"></i> <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $total_depts; ?></div>
                                    <p class="stat-label">Total Departments</p>
                                </div>
                                <div class="stat-icon bg-primary bg-opacity-10">
                                    <i class="ti ti-building-community text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $active_depts; ?></div>
                                    <p class="stat-label">Active Departments</p>
                                </div>
                                <div class="stat-icon bg-success bg-opacity-10">
                                    <i class="ti ti-circle-check text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $total_designations; ?></div>
                                    <p class="stat-label">Total Designations</p>
                                </div>
                                <div class="stat-icon bg-info bg-opacity-10">
                                    <i class="ti ti-briefcase text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $active_designations; ?></div>
                                    <p class="stat-label">Active Designations</p>
                                </div>
                                <div class="stat-icon bg-warning bg-opacity-10">
                                    <i class="ti ti-star text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Departments Section -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-bottom">
                        <h5 class="mb-0"><i class="ti ti-building-community me-2"></i>Departments</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="departmentsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Department Name</th>
                                        <th>Code</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th>Created Date</th>
                                        <th>Actions</th>
                                    </thead>
                                <tbody>
                                    <?php while($dept = $departments_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $dept['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($dept['dept_name']); ?></strong></td>
                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($dept['dept_code']); ?></span></td>
                                        <td><?php echo htmlspecialchars($dept['description']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $dept['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo ucfirst($dept['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($dept['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-outline-primary" onclick="editDepartment(<?php echo $dept['id']; ?>, '<?php echo htmlspecialchars($dept['dept_name']); ?>', '<?php echo htmlspecialchars($dept['dept_code']); ?>', '<?php echo htmlspecialchars($dept['description']); ?>', '<?php echo $dept['status']; ?>')">
                                                    <i class="ti ti-edit"></i>
                                                </button>
                                                <a href="?delete_dept=<?php echo $dept['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this department? All designations under it will be affected.');">
                                                    <i class="ti ti-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Designations Section -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-bottom">
                        <h5 class="mb-0"><i class="ti ti-briefcase me-2"></i>Designations</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="designationsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Designation</th>
                                        <th>Department</th>
                                        <th>Level</th>
                                        <th>Grade</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </thead>
                                <tbody>
                                    <?php while($desig = $designations_result->fetch_assoc()): 
                                        $grade_names = [
                                            1 => 'Admin',
                                            2 => 'Manager',
                                            3 => 'Team Lead',
                                            4 => 'Executive'
                                        ];
                                        $grade_name = $grade_names[$desig['grade_level']] ?? 'Employee';
                                    ?>
                                    <tr>
                                        <td><?php echo $desig['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($desig['designation_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($desig['dept_name']); ?> <small class="text-muted">(<?php echo $desig['dept_code']; ?>)</small></td>
                                        <td><?php echo htmlspecialchars($desig['designation_level']); ?></td>
                                        <td><span class="grade-badge"><?php echo $grade_name; ?> (L<?php echo $desig['grade_level']; ?>)</span></td>
                                        <td><?php echo htmlspecialchars($desig['description']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $desig['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo ucfirst($desig['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-outline-primary" onclick="editDesignation(<?php echo $desig['id']; ?>, <?php echo $desig['dept_id']; ?>, '<?php echo htmlspecialchars($desig['designation_name']); ?>', '<?php echo htmlspecialchars($desig['designation_level']); ?>', <?php echo $desig['grade_level']; ?>, '<?php echo htmlspecialchars($desig['description']); ?>', '<?php echo $desig['status']; ?>')">
                                                    <i class="ti ti-edit"></i>
                                                </button>
                                                <a href="?delete_desig=<?php echo $desig['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this designation?');">
                                                    <i class="ti ti-trash"></i>
                                                </a>
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

    <!-- Add Department Modal -->
    <div class="modal fade" id="addDepartmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required-field">Department Name</label>
                            <input type="text" class="form-control" name="dept_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required-field">Department Code</label>
                            <input type="text" class="form-control" name="dept_code" placeholder="e.g., SALES, HR, IT" required>
                            <small class="text-muted">Unique code for the department</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="dept_description" rows="3"></textarea>
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
                        <button type="submit" name="add_department" class="btn btn-primary">Create Department</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Department Modal -->
    <div class="modal fade" id="editDepartmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <input type="hidden" name="dept_id" id="edit_dept_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required-field">Department Name</label>
                            <input type="text" class="form-control" name="dept_name" id="edit_dept_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required-field">Department Code</label>
                            <input type="text" class="form-control" name="dept_code" id="edit_dept_code" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="dept_description" id="edit_dept_description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_dept_status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_department" class="btn btn-primary">Update Department</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Designation Modal -->
    <div class="modal fade" id="addDesignationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Designation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required-field">Department</label>
                            <select class="form-select" name="dept_id" required>
                                <option value="">Select Department</option>
                                <?php 
                                $depts = $conn->query("SELECT id, dept_name FROM departments WHERE status = 'active' ORDER BY dept_name");
                                while($dept = $depts->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['dept_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required-field">Designation Name</label>
                            <input type="text" class="form-control" name="designation_name" placeholder="e.g., Senior Manager, Team Lead" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Designation Level</label>
                            <input type="text" class="form-control" name="designation_level" placeholder="e.g., Level 1, Senior Level">
                        </div>
                        <div class="mb-3">
                            <label class="form-label required-field">Grade Level</label>
                            <select class="form-select" name="grade_level" required>
                                <option value="">Select Grade Level</option>
                                <option value="1">1 - Admin</option>
                                <option value="2">2 - Manager</option>
                                <option value="3">3 - Team Lead</option>
                                <option value="4">4 - Executive</option>
                            </select>
                            <small class="text-muted">Grade level determines access permissions</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Job description, responsibilities, etc."></textarea>
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
                        <button type="submit" name="add_designation" class="btn btn-primary">Create Designation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Designation Modal -->
    <div class="modal fade" id="editDesignationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Designation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <input type="hidden" name="desig_id" id="edit_desig_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required-field">Department</label>
                            <select class="form-select" name="dept_id" id="edit_desig_dept_id" required>
                                <option value="">Select Department</option>
                                <?php 
                                $depts_edit = $conn->query("SELECT id, dept_name FROM departments WHERE status = 'active' ORDER BY dept_name");
                                while($dept = $depts_edit->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['dept_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required-field">Designation Name</label>
                            <input type="text" class="form-control" name="designation_name" id="edit_desig_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Designation Level</label>
                            <input type="text" class="form-control" name="designation_level" id="edit_desig_level">
                        </div>
                        <div class="mb-3">
                            <label class="form-label required-field">Grade Level</label>
                            <select class="form-select" name="grade_level" id="edit_desig_grade" required>
                                <option value="">Select Grade Level</option>
                                <option value="1">1 - Admin</option>
                                <option value="2">2 - Manager</option>
                                <option value="3">3 - Team Lead</option>
                                <option value="4">4 - Executive</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_desig_description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_desig_status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_designation" class="btn btn-primary">Update Designation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include_once "includes/footer-link.php"; ?>

    <script>
        $(document).ready(function() {
            // Initialize DataTables
            $('#departmentsTable').DataTable({
                pageLength: 10,
                order: [[0, 'desc']],
                language: {
                    search: "Search departments:",
                    lengthMenu: "Show _MENU_ departments",
                    info: "Showing _START_ to _END_ of _TOTAL_ departments"
                }
            });
            
            $('#designationsTable').DataTable({
                pageLength: 10,
                order: [[0, 'desc']],
                language: {
                    search: "Search designations:",
                    lengthMenu: "Show _MENU_ designations",
                    info: "Showing _START_ to _END_ of _TOTAL_ designations"
                }
            });
        });
        
        function editDepartment(id, name, code, description, status) {
            $('#edit_dept_id').val(id);
            $('#edit_dept_name').val(name);
            $('#edit_dept_code').val(code);
            $('#edit_dept_description').val(description);
            $('#edit_dept_status').val(status);
            $('#editDepartmentModal').modal('show');
        }
        
        function editDesignation(id, deptId, name, level, grade, description, status) {
            $('#edit_desig_id').val(id);
            $('#edit_desig_dept_id').val(deptId);
            $('#edit_desig_name').val(name);
            $('#edit_desig_level').val(level);
            $('#edit_desig_grade').val(grade);
            $('#edit_desig_description').val(description);
            $('#edit_desig_status').val(status);
            $('#editDesignationModal').modal('show');
        }
    </script>
</body>
</html>