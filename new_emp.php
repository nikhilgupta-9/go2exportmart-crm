<?php
session_start();
include 'partials/_dbconnect.php';
if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
}


$user_id = $_SESSION['user_id'];
$repot_sql = "SELECT * FROM `employees` WHERE `grade_level` < '4'";
$result_repot = mysqli_query($conn, $repot_sql);


$info_ftch_sql = "SELECT * FROM `employees` WHERE `user_id` = '$user_id'";
$result_ftch_sql = mysqli_query($conn, $info_ftch_sql);
$info_row = mysqli_fetch_assoc($result_ftch_sql);
$name = $info_row['user_name'];
if ($_SERVER['REQUEST_METHOD'] == "GET") {
    $ftch_emp_count_cmd = "SELECT * FROM `employees`";
    if ($ftch_result = mysqli_query($conn, $ftch_emp_count_cmd)) {
        $row_num = mysqli_num_rows($ftch_result);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Meta Tags -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Create New Employee | GO2EXPORT MART</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Streamline your business with our advanced CRM template. Easily integrate and customize to manage sales, support, and customer interactions efficiently. Perfect for any business size">
    <meta name="keywords" content="Advanced CRM template, customer relationship management, business CRM, sales optimization, customer support software, CRM integration, customizable CRM, business tools, enterprise CRM solutions">
    <meta name="author" content="Dreams Technologies">
    <meta name="robots" content="index, follow">
    <?php
    include_once "includes/link.php";
    ?>

</head>

<body>
    <a href="https://crms.dreamstechnologies.com/cdn-cgi/content?id=I.QOop4fCF0MfA9GSZkaG6tWvTnIbqPO9t1EFWKtOaA-1774786649.9122906-1.0.1.1-zcccNezd9_brSgS3.MpxHIwnl2hxQUUEsR.nQW0cIdQ" aria-hidden="true" rel="nofollow noopener" style="display: none !important; visibility: hidden !important"></a>

    <!-- Begin Wrapper -->
    <div class="main-wrapper">

        <?php include_once "includes/header.php"; ?>

        <!-- Search Modal -->
        <div class="modal fade" id="searchModal">
            <div class="modal-dialog modal-lg">
                <div class="modal-content bg-transparent">
                    <div class="card shadow-none mb-0">
                        <div class="px-3 py-2 d-flex flex-row align-items-center" id="search-top">
                            <i class="ti ti-search fs-22"></i>
                            <input type="search" class="form-control border-0" placeholder="Search">
                            <button type="button" class="btn p-0" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x fs-22"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include_once "includes/sidebar.php"; ?>

        <div class="page-wrapper">
            <div class="content pb-0">

                <!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h4>Add Employee</h4>
                </div>

                <!-- FORM ROW -->
                <div class="row">
                    <div class="col-xl-12">

                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Create Employee</h5>
                            </div>

                            <div class="card-body">

                                <form action="add.php" method="POST" class="row gy-3 gx-3">

                                    <!-- USER ID -->
                                    <div class="col-md-4">
                                        <label class="form-label">User ID</label>
                                        <input type="text" name="userId" class="form-control" required>
                                    </div>

                                    <!-- NAME -->
                                    <div class="col-md-4">
                                        <label class="form-label">Name</label>
                                        <input type="text" name="userName" class="form-control" required>
                                    </div>

                                    <!-- PASSWORD -->
                                    <div class="col-md-4">
                                        <label class="form-label">Password</label>
                                        <input type="text" name="userPassword" class="form-control" required>
                                    </div>

                                    <!-- DOB -->
                                    <div class="col-md-4">
                                        <label class="form-label">DOB</label>
                                        <input type="date" name="userDOB" class="form-control" required>
                                    </div>

                                    <!-- DOJ -->
                                    <div class="col-md-4">
                                        <label class="form-label">Joining Date</label>
                                        <input type="date" name="userDOJ" class="form-control" required>
                                    </div>

                                    <!-- PHONE -->
                                    <div class="col-md-4">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="userNum" maxlength="10" class="form-control" required>
                                    </div>

                                    <!-- EMAIL -->
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="userMail" class="form-control" required>
                                    </div>

                                    <!-- ADDRESS -->
                                    <div class="col-md-6">
                                        <label class="form-label">Address</label>
                                        <input type="text" name="userAdd" class="form-control" required>
                                    </div>

                                    <!-- TARGET -->
                                    <div class="col-md-4">
                                        <label class="form-label">Target</label>
                                        <input type="number" name="userTarget" class="form-control" required>
                                    </div>

                                    <!-- DEPARTMENT -->
                                    <div class="col-md-4">
                                        <label class="form-label">Department</label>
                                        <select name="department" class="form-select">
                                            <option value="Sales">Sales</option>
                                            <option value="IT">IT</option>
                                            <option value="Support">Support</option>
                                            <option value="Renewal">Renewal</option>
                                        </select>
                                    </div>

                                    <!-- DESIGNATION -->
                                    <div class="col-md-4">
                                        <label class="form-label">Designation</label>
                                        <select name="userRole" class="form-select">
                                            <option value="Sales Executive">Executive</option>
                                            <option value="IT Executive">IT Executive</option>
                                            <option value="Team Lead">Team Lead</option>
                                            <option value="Manager">Manager</option>
                                        </select>
                                    </div>

                                    <!-- LINE HR -->
                                    <div class="col-md-6">
                                        <label class="form-label">Line HR</label>
                                        <input type="text" name="lineHr" class="form-control" required>
                                    </div>

                                    <!-- REPORTING -->
                                    <div class="col-md-6">
                                        <label class="form-label">Reporting To</label>
                                        <select name="reporting" class="form-select">
                                            <?php
                                            while ($row_repot = mysqli_fetch_assoc($result_repot)) {
                                                echo '<option value="' . $row_repot['user_id'] . '">' . $row_repot['user_name'] . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <!-- BUTTON -->
                                    <div class="col-12 text-end mt-3">
                                        <button type="submit" class="btn btn-primary">
                                            Create Employee
                                        </button>
                                    </div>

                                </form>

                            </div>
                        </div>



                    </div>
                </div>
            </div>
            <?php include_once "includes/footer.php" ?>
        </div>
    </div>
    <?php include_once "includes/footer-link.php" ?>

</body>

</html>