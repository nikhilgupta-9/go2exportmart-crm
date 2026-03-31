<?php
session_start();
include 'partials/_header.php';
if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
}

$user_id = $_SESSION['user_id'];
$info_ftch_sql = "SELECT * FROM `employees` WHERE `user_id` = '$user_id'";
$result_ftch_sql = mysqli_query($conn, $info_ftch_sql);
$info_row = mysqli_fetch_assoc($result_ftch_sql);
$name = $info_row['user_name'];

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $customerid = $_GET['customerID'];
    $supportp = $_POST['supportp'];
    $assign_support = $conn->query("UPDATE `customerleads` SET `Aadhar` = '$supportp' WHERE `sno` = '$customerid'");
    header('location: matelize.php');
    
}

?>


<div class="dashboard-container">
    <div id="tool-div" class="d-none tooldiv">
        <?php include 'partials/_tool.php'; ?>
    </div>
    <div class="container-fluid">
        <div class="dashboard">
            <div class="top-functions d-flex justify-content-between">
                <div class="name">
                    <h4><i id="tool_hide" class="fa-solid fa-bars me-3"></i>Welcome <?php echo $name; ?></h4>
                    <?php echo date("d-m-Y, l"); ?>
                </div>
            </div>
            <div class="container mt-4">
                <div class="row">
                    <div class="col-6">
                        <form action="#" method="post">
                            <label class="mb-2" for="supportagent"><span class="text-danger">*</span>Assign to Support Team</label>
                            <select class="form-select" name="supportp" id="supportp">
                                <?php 
                                $result_support = $conn->query("SELECT * FROM `employees` WHERE `department` = 'Support' AND `grade_level` = '4'");
                                while ($row_support = mysqli_fetch_assoc($result_support)){
                                    echo '
                                    <option value="'. $row_support['user_id'] .'">'. $row_support['user_name'] .'</option>';
                                }
                                ?>
                            </select>
                            <button type="submit" class="btn btn-primary mt-2">Assign</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'partials/_footer.php';
?>