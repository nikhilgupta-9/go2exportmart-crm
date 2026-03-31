<?php
$id = $_SESSION['user_id'];
$ftch_info_sql = "SELECT * FROM `employees` WHERE `user_id` = '$id'";
$result_info = mysqli_query($conn, $ftch_info_sql);
$row_info = mysqli_fetch_assoc($result_info);
$grade = $row_info['grade_level'];
?>

<div class="dashboard-tools" id="dashtool">
        <div class="tool-header">
            <img src="assets\images\business.png" alt="">
            <i id="closeBtn" class="text-end fa-solid fa-xmark"></i>
        </div>
        <ul class="tools-ul">

            <a href="dashboard.php">
                <h5>Dashboard</h5>
            </a>
            <hr>
            <div class="header">
                <h5>Info</h5>
                <li><a href="info.php">My Profile</a></li>
                <?php
                if($grade < 2){
                    echo '
                    <li><a href="general.php">Company Info</a></li>
                    <li><a href="employee.php">Employees</a></li>
                    <li><a href="new_emp.php">Create New Employee</a></li>
                    <li><a href="service.php">Create New Service</a></li>
                    ';
                }
                else if($grade == 3){
                    echo '<li><a href="employee.php">Employees</a></li>';
                }

                ?>
            </div>
            <hr>
            <div class="header">
                <h5>Team Management</h5>
            <?php
                if($grade < 4){
                    echo '
                <li><a href="team.php">My Team</a></li>
                <li><a href="#">Team Alignment</a></li>
                </div>';
            }
            ?>
                <hr>
                <div class="header">
                <h5>Leads</h5>
                <li><a href="lead.php">All Leads</a></li>
                <li><a href="create_lead.php">Create New Lead</a></li>
                <li><a href="lead.php?leadType=Fresh+Lead">Fresh Leads</a></li>
                <li><a href="lead.php?leadType=Follow+Up">Follow up Leads</a></li>
                <li><a href="lead.php?leadType=Committed">Committed</a></li>
                <li><a href="matelize.php">Matelize</a></li>
                <li><a href="lead.php?leadType=Not+Interested">Not Interested</a></li>
            </div>
            <hr>
            <li><a href="target.php">Target</a></li>
            <hr>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>

    <script src="assets\js\close.js"></script>