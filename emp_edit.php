<?php
session_start();
include_once "partials/_dbconnect.php";
include_once "util/function.php";

if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
    exit;
}

// Check if user has admin privileges
if ($_SESSION['grade_level'] != 1) {
    header('location: dashboard.php');
    exit;
}

// GET EMPLOYEE ID
if (!isset($_GET['id'])) {
    die("Invalid Request");
}

$emp_id = $_GET['id'];

// FETCH EMPLOYEE DATA (SECURE)
$stmt = $conn->prepare("SELECT * FROM employees WHERE emp_id = ?");
$stmt->bind_param("i", $emp_id);
$stmt->execute();
$emp = $stmt->get_result()->fetch_assoc();

if (!$emp) {
    die("Employee not found");
}

// Fetch departments (you can modify this list)
$depts_edit = $conn->query("SELECT id, dept_name FROM departments WHERE status = 'active' ORDER BY dept_name");

$departments = [];
while ($row = $depts_edit->fetch_assoc()) {
    $departments[] = $row;
}

// Fetch employees for reporting dropdown (grade_level < 4 and not the current employee)
$reporting_sql = "SELECT emp_id, user_id, user_name, grade_level, user_role 
                  FROM employees 
                  WHERE grade_level < 4 AND emp_id != ? AND status = 1
                  ORDER BY grade_level, user_name";
$reporting_stmt = $conn->prepare($reporting_sql);
$reporting_stmt->bind_param("i", $emp_id);
$reporting_stmt->execute();
$reporting_result = $reporting_stmt->get_result();

// Fetch all employees for status update
$all_reporting = [];
while ($row = $reporting_result->fetch_assoc()) {
    $all_reporting[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Edit Employee | GO2EXPORT MART</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once "includes/link.php"; ?>

    <style>
        .form-section {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eef2f6;
        }

        .section-title i {
            color: #667eea;
            margin-right: 10px;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
        }

        .required-field::after {
            content: '*';
            color: #dc3545;
            margin-left: 4px;
        }

        .info-text {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .form-section {
                padding: 15px;
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
                        <h4 class="mb-1">Edit Employee</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="employee.php">Employees</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Edit Employee</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
                        <a href="employee.php" class="btn btn-outline-secondary">
                            <i class="ti ti-arrow-left me-1"></i> Back to List
                        </a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <form action="emp_update.php" method="POST" id="editEmployeeForm">
                            <input type="hidden" name="emp_id" value="<?= $emp['emp_id'] ?>">

                            <!-- Personal Information Section -->
                            <div class="form-section">
                                <h5 class="section-title">
                                    <i class="ti ti-user"></i> Personal Information
                                </h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label required-field">Full Name</label>
                                        <input type="text" class="form-control" name="user_name" value="<?= htmlspecialchars($emp['user_name']) ?>" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label required-field">Email Address</label>
                                        <input type="email" class="form-control" name="user_mail" value="<?= htmlspecialchars($emp['user_mail']) ?>" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label required-field">Phone Number</label>
                                        <input type="tel" class="form-control" name="user_num" value="<?= htmlspecialchars($emp['user_num']) ?>" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" name="user_dob" value="<?= $emp['user_dob'] ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Employment Information Section -->
                            <div class="form-section">
                                <h5 class="section-title">
                                    <i class="ti ti-briefcase"></i> Employment Information
                                </h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label required-field">Department</label>
                                        <select class="form-select" name="department" required>
                                            <option value="">Select Department</option>

                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?= $dept['dept_name'] ?>"
                                                    <?= ($emp['department'] == $dept['dept_name']) ? 'selected' : '' ?>>

                                                    <?= htmlspecialchars($dept['dept_name']) ?>
                                                </option>
                                            <?php endforeach; ?>

                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label required-field">Designation / Role</label>
                                        <!-- <input type="text" class="form-control" name="user_role" value="<?= htmlspecialchars($emp['user_role']) ?>"  required> -->
                                        <select class="form-select" name="user_role" required>
                                            <option value="">Select Designation</option>

                                            <?php 
                                            $designation1 = getAllDesignations($conn);
                                            foreach ($designation1 as $dept): ?>
                                                <option value="<?= $dept['designation_name'] ?>"
                                                    <?= ($emp['department'] == $dept['designation_name']) ? 'selected' : '' ?>>

                                                    <?= htmlspecialchars($dept['designation_name']) ?>
                                                </option>
                                            <?php endforeach; ?>

                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Date of Joining</label>
                                        <input type="date" class="form-control" name="user_doj" value="<?= $emp['user_doj'] ?>">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Target Amount (₹)</label>
                                        <input type="number" class="form-control" name="user_target" value="<?= $emp['user_target'] ?>" step="0.01">
                                    </div>
                                </div>
                            </div>

                            <!-- Reporting & HR Section -->
                            <div class="form-section">
                                <h5 class="section-title">
                                    <i class="ti ti-user-check"></i> Reporting & HR Information
                                </h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Reporting To</label>
                                        <select class="form-select" name="Reporting">
                                            <option value="">Select Reporting Manager</option>
                                            <option value="admin" <?= $emp['Reporting'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                            <?php foreach ($all_reporting as $reporter):
                                                $grade_name = '';
                                                switch ($reporter['grade_level']) {
                                                    case 1:
                                                        $grade_name = '(Admin)';
                                                        break;
                                                    case 2:
                                                        $grade_name = '(Manager)';
                                                        break;
                                                    case 3:
                                                        $grade_name = '(Team Lead)';
                                                        break;
                                                    default:
                                                        $grade_name = '';
                                                }
                                            ?>
                                                <option value="<?= $reporter['user_id'] ?>" <?= $emp['Reporting'] == $reporter['user_id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($reporter['user_name']) ?>
                                                    <?= $grade_name ?> - <?= htmlspecialchars($reporter['user_role']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="info-text">
                                            <i class="ti ti-info-circle"></i>
                                            Only Managers and Team Leads can be selected as Reporting Authority
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">HR Contact</label>
                                        <input type="text" class="form-control" name="line_hr" value="<?= htmlspecialchars($emp['line_hr']) ?>" placeholder="HR Name or Contact">
                                    </div>
                                </div>
                            </div>

                            <!-- Address & Status Section -->
                            <div class="form-section">
                                <h5 class="section-title">
                                    <i class="ti ti-map-pin"></i> Additional Information
                                </h5>
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Address</label>
                                        <textarea class="form-control" name="user_address" rows="2"><?= htmlspecialchars($emp['user_address']) ?></textarea>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label required-field">Status</label>
                                        <select class="form-select" name="status" required>
                                            <option value="1" <?= $emp['status'] == 1 ? 'selected' : '' ?>>Active</option>
                                            <option value="0" <?= $emp['status'] == 0 ? 'selected' : '' ?>>Inactive</option>
                                            <option value="2" <?= $emp['status'] == 2 ? 'selected' : '' ?>>Left</option>
                                        </select>
                                        <div class="info-text">
                                            <i class="ti ti-info-circle"></i>
                                            <span class="text-success">Active</span> - Employee is currently working<br>
                                            <span class="text-warning">Inactive</span> - Employee is on leave/break<br>
                                            <span class="text-danger">Left</span> - Employee has left the organization
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Grade Level</label>
                                        <select class="form-select" name="grade_level">
                                            <option value="4" <?= $emp['grade_level'] == 4 ? 'selected' : '' ?>>4 - Executive</option>
                                            <option value="3" <?= $emp['grade_level'] == 3 ? 'selected' : '' ?>>3 - Team Lead</option>
                                            <option value="2" <?= $emp['grade_level'] == 2 ? 'selected' : '' ?>>2 - Manager</option>
                                            <option value="1" <?= $emp['grade_level'] == 1 ? 'selected' : '' ?>>1 - Admin</option>
                                        </select>
                                        <div class="info-text">
                                            <i class="ti ti-info-circle"></i>
                                            Grade Level determines access permissions
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section text-center">
                                <button type="submit" class="btn btn-primary btn-submit">
                                    <i class="ti ti-device-floppy me-2"></i> Update Employee
                                </button>
                                <button type="reset" class="btn btn-secondary btn-submit ms-2">
                                    <i class="ti ti-refresh me-2"></i> Reset
                                </button>
                                <a href="employee.php" class="btn btn-outline-secondary btn-submit ms-2">
                                    <i class="ti ti-x me-2"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <?php include_once "includes/footer-link.php"; ?>

    <script>
        $(document).ready(function() {
            // Form validation
            $('#editEmployeeForm').on('submit', function(e) {
                let isValid = true;

                // Validate name
                const name = $('input[name="user_name"]').val();
                if (!name.trim()) {
                    $('input[name="user_name"]').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('input[name="user_name"]').removeClass('is-invalid').addClass('is-valid');
                }

                // Validate email
                const email = $('input[name="user_mail"]').val();
                const emailRegex = /^[^\s@]+@([^\s@.,]+\.)+[^\s@.,]{2,}$/;
                if (!emailRegex.test(email)) {
                    $('input[name="user_mail"]').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('input[name="user_mail"]').removeClass('is-invalid').addClass('is-valid');
                }

                // Validate phone
                const phone = $('input[name="user_num"]').val();
                const phoneRegex = /^[6-9][0-9]{9}$/;
                if (!phoneRegex.test(phone)) {
                    $('input[name="user_num"]').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('input[name="user_num"]').removeClass('is-invalid').addClass('is-valid');
                }

                // Validate department
                const dept = $('select[name="department"]').val();
                if (!dept) {
                    $('select[name="department"]').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('select[name="department"]').removeClass('is-invalid').addClass('is-valid');
                }

                // Validate role
                const role = $('input[name="user_role"]').val();
                if (!role.trim()) {
                    $('input[name="user_role"]').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('input[name="user_role"]').removeClass('is-invalid').addClass('is-valid');
                }

                if (!isValid) {
                    e.preventDefault();
                    $('html, body').animate({
                        scrollTop: $('.is-invalid:first').offset().top - 100
                    }, 500);
                    showNotification('error', 'Please fill all required fields correctly');
                }
            });

            // Real-time validation
            $('input[name="user_name"]').on('input', function() {
                if ($(this).val().trim()) {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                } else {
                    $(this).removeClass('is-valid').addClass('is-invalid');
                }
            });

            $('input[name="user_mail"]').on('input', function() {
                const email = $(this).val();
                const regex = /^[^\s@]+@([^\s@.,]+\.)+[^\s@.,]{2,}$/;
                if (regex.test(email)) {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                } else {
                    $(this).removeClass('is-valid').addClass('is-invalid');
                }
            });

            $('input[name="user_num"]').on('input', function() {
                let value = $(this).val().replace(/\D/g, '');
                if (value.length > 10) value = value.slice(0, 10);
                $(this).val(value);

                const regex = /^[6-9][0-9]{9}$/;
                if (regex.test(value)) {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                } else {
                    $(this).removeClass('is-valid').addClass('is-invalid');
                }
            });

            $('select[name="department"]').on('change', function() {
                if ($(this).val()) {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                } else {
                    $(this).removeClass('is-valid').addClass('is-invalid');
                }
            });
        });

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
        .btn-submit {
            padding: 10px 24px;
            font-weight: 500;
        }

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

        .is-invalid {
            border-color: #dc3545;
        }

        .is-valid {
            border-color: #28a745;
        }
    </style>
</body>

</html>