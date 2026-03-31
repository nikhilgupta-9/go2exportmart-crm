<?php
session_start();
include_once "partials/_dbconnect.php";

if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Edit Employee | CRM</title>
    <?php include_once "includes/link.php"; ?>
</head>

<body>

    <!-- Begin Wrapper -->
    <div class="main-wrapper">

        <?php include_once "includes/header.php"; ?>
        <?php include_once "includes/sidebar.php"; ?>

        <div class="page-wrapper">
            <div class="content">

                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4>Edit Employee</h4>
                    <a href="employees.php" class="btn btn-light">← Back</a>
                </div>

                <!-- FORM -->
                <div class="row">
                    <div class="col-lg-12">

                        <div class="card">
                            <div class="card-header">
                                <h5>Employee Details</h5>
                            </div>

                            <div class="card-body">

                                <form action="emp_update.php" method="POST">

                                    <input type="hidden" name="emp_id" value="<?= $emp['emp_id'] ?>">

                                    <div class="row">

                                        <!-- NAME -->
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Name</label>
                                            <input class="form-control" name="user_name" value="<?= $emp['user_name'] ?>">
                                        </div>

                                        <!-- EMAIL -->
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" name="user_mail" value="<?= $emp['user_mail'] ?>">
                                        </div>

                                        <!-- PHONE -->
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Phone</label>
                                            <input class="form-control" name="user_num" value="<?= $emp['user_num'] ?>">
                                        </div>

                                        <!-- DOB -->
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">DOB</label>
                                            <input type="date" class="form-control" name="user_dob" value="<?= $emp['user_dob'] ?>">
                                        </div>

                                        <!-- DOJ -->
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Joining Date</label>
                                            <input type="date" class="form-control" name="user_doj" value="<?= $emp['user_doj'] ?>">
                                        </div>

                                        <!-- DEPARTMENT -->
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Department</label>
                                            <input class="form-control" name="department" value="<?= $emp['department'] ?>">
                                        </div>

                                        <!-- DESIGNATION -->
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Designation</label>
                                            <input class="form-control" name="user_role" value="<?= $emp['user_role'] ?>">
                                        </div>

                                        <!-- TARGET -->
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Target</label>
                                            <input class="form-control" name="user_target" value="<?= $emp['user_target'] ?>">
                                        </div>

                                        <!-- REPORTING -->
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Reporting To</label>
                                            <input class="form-control" name="Reporting" value="<?= $emp['Reporting'] ?>">
                                        </div>

                                        <!-- HR -->
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">HR</label>
                                            <input class="form-control" name="line_hr" value="<?= $emp['line_hr'] ?>">
                                        </div>

                                        <!-- STATUS -->
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Status</label>
                                            <select name="status" class="form-select">
                                                <option value="active" <?= $emp['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                                                <option value="inactive" <?= $emp['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                                <option value="left" <?= $emp['status'] == 'left' ? 'selected' : '' ?>>Left</option>
                                            </select>
                                        </div>

                                        <!-- ADDRESS -->
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Address</label>
                                            <input class="form-control" name="user_address" value="<?= $emp['user_address'] ?>">
                                        </div>

                                    </div>

                                    <div class="text-end">
                                        <button class="btn btn-primary">Update Employee</button>
                                    </div>

                                </form>

                            </div>
                        </div>

                    </div>
                </div>

            </div>


            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <?php include_once "includes/footer-link.php"; ?>

</body>

</html>