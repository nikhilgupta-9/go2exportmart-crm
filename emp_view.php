<?php
session_start();
include_once "partials/_dbconnect.php";

if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
    exit;
}

$id = intval($_GET['id']);
$employee = $conn->query("SELECT * FROM employees WHERE emp_id = $id")->fetch_assoc();

if (!$employee) {
    header('location: employee.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Employee Details | GO2EXPORT MART</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once "includes/link.php"; ?>
</head>
<body>
    <div class="main-wrapper">
        <?php include_once "includes/header.php"; ?>
        <?php include_once "includes/sidebar.php"; ?>
        
        <div class="page-wrapper">
            <div class="content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4>Employee Details</h4>
                    <a href="employee.php" class="btn btn-secondary">Back to List</a>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <?php
                                $imgPath = !empty($employee['user_img']) && file_exists("assets/uploads/profiles/" . $employee['user_img'])
                                    ? "assets/uploads/profiles/" . $employee['user_img']
                                    : "https://ui-avatars.com/api/?name=" . urlencode($employee['user_name']) . "&background=667eea&color=fff&size=200&bold=true";
                                ?>
                                <img src="<?= $imgPath ?>" alt="Profile" class="rounded-circle mb-3" width="150" height="150">
                                <h5><?= htmlspecialchars($employee['user_name']) ?></h5>
                                <p class="text-muted"><?= htmlspecialchars($employee['user_id']) ?></p>
                            </div>
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted">Department</label>
                                        <p class="fw-bold"><?= htmlspecialchars($employee['department']) ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted">Designation</label>
                                        <p class="fw-bold"><?= htmlspecialchars($employee['user_role']) ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted">Phone Number</label>
                                        <p class="fw-bold"><?= htmlspecialchars($employee['user_num']) ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted">Email Address</label>
                                        <p class="fw-bold"><?= htmlspecialchars($employee['user_mail']) ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted">Date of Joining</label>
                                        <p class="fw-bold"><?= date('d M Y', strtotime($employee['user_doj'])) ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted">Reporting To</label>
                                        <p class="fw-bold"><?= htmlspecialchars($employee['Reporting']) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include_once "includes/footer-link.php"; ?>
</body>
</html>