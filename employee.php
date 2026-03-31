<?php
session_start();
include_once "partials/_dbconnect.php";
include_once "partials/_header.php";

if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$grade_level = $_SESSION['grade_level'];

// Get logged user
$stmt = $conn->prepare("SELECT * FROM employees WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get all employees with join information
$employees = $conn->query("SELECT e.*, 
                           (SELECT COUNT(*) FROM customerleads WHERE assigned_to = e.user_id AND matelize = '1') as total_deals,
                           (SELECT SUM(amount) FROM customerleads WHERE assigned_to = e.user_id AND matelize = '1') as total_revenue
                           FROM employees e 
                           ORDER BY e.created_at DESC");

// Get statistics
$total_employees = $conn->query("SELECT COUNT(*) as total FROM employees")->fetch_assoc()['total'];
$active_employees = $conn->query("SELECT COUNT(*) as total FROM employees WHERE status = 1")->fetch_assoc()['total'];
$total_leads = $conn->query("SELECT COUNT(*) as total FROM customerleads WHERE matelize = '1'")->fetch_assoc()['total'];
$total_revenue = $conn->query("SELECT SUM(amount) as total FROM customerleads WHERE matelize = '1'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Employees | GO2EXPORT MART</title>
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
        
        .employee-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .status-badge {
            padding: 5px 12px;
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
        
        .status-left {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons .btn {
            margin: 0 2px;
            padding: 4px 8px;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.375rem 0.75rem;
            margin: 0 2px;
            border-radius: 8px;
        }
        
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 6px 12px;
        }
        
        .table > :not(caption) > * > * {
            padding: 15px;
            vertical-align: middle;
        }
        
        .filter-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
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
                        <h4 class="mb-1">Employee Management</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Employees</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
                        <button onclick="window.location.reload()" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" title="Refresh">
                            <i class="ti ti-refresh"></i>
                        </button>
                        <a href="new_emp.php" class="btn btn-primary">
                            <i class="ti ti-plus me-2"></i>Add New Employee
                        </a>
                    </div>
                </div>

                <!-- Statistics Cards -->
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
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $active_employees; ?></div>
                                    <p class="stat-label">Active Employees</p>
                                </div>
                                <div class="stat-icon bg-success bg-opacity-10">
                                    <i class="ti ti-user-check text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value"><?php echo $total_leads; ?></div>
                                    <p class="stat-label">Total Deals</p>
                                </div>
                                <div class="stat-icon bg-info bg-opacity-10">
                                    <i class="ti ti-chart-arcs text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value">₹<?php echo number_format($total_revenue ?? 0, 2); ?></div>
                                    <p class="stat-label">Total Revenue</p>
                                </div>
                                <div class="stat-icon bg-warning bg-opacity-10">
                                    <i class="ti ti-currency-rupee text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employees Table -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h5 class="mb-0">Employee List</h5>
                            <small class="text-muted">Manage all employees and their details</small>
                        </div>
                        <div class="d-flex gap-2 mt-2 mt-sm-0">
                            <div class="input-group input-group-sm" style="width: 250px;">
                                <span class="input-group-text"><i class="ti ti-search"></i></span>
                                <input type="text" id="tableSearch" class="form-control" placeholder="Search employees...">
                            </div>
                            <select id="statusFilter" class="form-select form-select-sm w-auto">
                                <option value="">All Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                                <option value="left">Left</option>
                            </select>
                            <select id="gradeFilter" class="form-select form-select-sm w-auto">
                                <option value="">All Grades</option>
                                <option value="1">Grade 1 - Admin</option>
                                <option value="2">Grade 2 - Manager</option>
                                <option value="3">Grade 3 - Team Lead</option>
                                <option value="4">Grade 4 - Executive</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="employeesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Employee</th>
                                        <th>Department</th>
                                        <th>Designation</th>
                                        <th>Grade</th>
                                        <th>Phone</th>
                                        <th>Deals</th>
                                        <th>Revenue</th>
                                        <th>Status</th>
                                        <th class="no-sort">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($emp = $employees->fetch_assoc()) { 
                                        $status_class = '';
                                        $status_text = '';
                                        if ($emp['status'] == 1) {
                                            $status_class = 'status-active';
                                            $status_text = 'Active';
                                        } elseif ($emp['status'] == 0) {
                                            $status_class = 'status-inactive';
                                            $status_text = 'Inactive';
                                        } else {
                                            $status_class = 'status-left';
                                            $status_text = 'Left';
                                        }
                                        
                                        $grade_name = '';
                                        switch($emp['grade_level']) {
                                            case 1: $grade_name = 'Admin'; break;
                                            case 2: $grade_name = 'Manager'; break;
                                            case 3: $grade_name = 'Team Lead'; break;
                                            case 4: $grade_name = 'Executive'; break;
                                            default: $grade_name = 'Employee';
                                        }
                                        
                                        $imgPath = !empty($emp['user_img']) && file_exists("assets/uploads/profiles/" . $emp['user_img'])
                                            ? "assets/uploads/profiles/" . $emp['user_img']
                                            : "https://ui-avatars.com/api/?name=" . urlencode($emp['user_name']) . "&background=667eea&color=fff&size=100&bold=true";
                                    ?>
                                        <tr data-status="<?= $emp['status'] ?>" data-grade="<?= $emp['grade_level'] ?>">
                                            <td><?= str_pad($emp['emp_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?= $imgPath ?>" alt="User Image" class="employee-avatar me-2">
                                                    <div>
                                                        <h6 class="mb-0"><?= htmlspecialchars($emp['user_name']) ?></h6>
                                                        <small class="text-muted"><?= htmlspecialchars($emp['user_id']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($emp['department']) ?></td>
                                            <td><?= htmlspecialchars($emp['user_role']) ?></td>
                                            <td>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                                    <?= $grade_name ?> (L<?= $emp['grade_level'] ?>)
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($emp['user_num']) ?></td>
                                            <td><?= $emp['total_deals'] ?? 0 ?></td>
                                            <td>₹<?= number_format($emp['total_revenue'] ?? 0, 2) ?></td>
                                            <td>
                                                <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="emp_edit.php?id=<?= $emp['emp_id'] ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Edit">
                                                        <i class="ti ti-edit"></i>
                                                    </a>
                                                    <button onclick="deleteEmployee(<?= $emp['emp_id'] ?>)" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Delete">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                    <?php if ($emp['status'] == 1): ?>
                                                        <button onclick="changeStatus(<?= $emp['emp_id'] ?>, 'left')" class="btn btn-sm btn-outline-warning" data-bs-toggle="tooltip" title="Mark as Left">
                                                            <i class="ti ti-user-off"></i>
                                                        </button>
                                                    <?php elseif ($emp['status'] == 'left'): ?>
                                                        <button onclick="changeStatus(<?= $emp['emp_id'] ?>, 'active')" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Reactivate">
                                                            <i class="ti ti-user-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <a href="emp_view.php?id=<?= $emp['emp_id'] ?>" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="View Details">
                                                        <i class="ti ti-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this employee? This action cannot be undone.</p>
                    <input type="hidden" id="delete_emp_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Change Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Employee Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="statusMessage">Are you sure you want to change this employee's status?</p>
                    <input type="hidden" id="status_emp_id">
                    <input type="hidden" id="status_action">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="confirmStatusChange()">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <?php include_once "includes/footer-link.php"; ?>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#employeesTable').DataTable({
                pageLength: 10,
                order: [[0, 'desc']],
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ employees",
                    infoEmpty: "No employees found",
                    emptyTable: "No employee data available"
                },
                columnDefs: [
                    { orderable: false, targets: 'no-sort' }
                ]
            });
            
            // Custom search
            $('#tableSearch').on('keyup', function() {
                table.search(this.value).draw();
            });
            
            // Status filter
            $('#statusFilter').on('change', function() {
                var value = $(this).val();
                if (value === '') {
                    table.column(8).search('').draw();
                } else if (value === 'left') {
                    table.column(8).search('Left').draw();
                } else {
                    table.column(8).search(value === '1' ? 'Active' : 'Inactive').draw();
                }
            });
            
            // Grade filter
            $('#gradeFilter').on('change', function() {
                var value = $(this).val();
                if (value === '') {
                    table.column(4).search('').draw();
                } else {
                    table.column(4).search(value).draw();
                }
            });
        });
        
        function deleteEmployee(empId) {
            $('#delete_emp_id').val(empId);
            $('#deleteModal').modal('show');
        }
        
        function confirmDelete() {
            var empId = $('#delete_emp_id').val();
            window.location.href = 'emp_delete.php?id=' + empId;
        }
        
        function changeStatus(empId, action) {
            $('#status_emp_id').val(empId);
            $('#status_action').val(action);
            
            if (action === 'left') {
                $('#statusMessage').text('Are you sure you want to mark this employee as Left? They will be deactivated.');
            } else if (action === 'active') {
                $('#statusMessage').text('Are you sure you want to reactivate this employee?');
            }
            
            $('#statusModal').modal('show');
        }
        
        function confirmStatusChange() {
            var empId = $('#status_emp_id').val();
            var action = $('#status_action').val();
            window.location.href = 'emp_status.php?id=' + empId + '&status=' + action;
        }
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>