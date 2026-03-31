<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
}
include 'partials/_header.php';

$user_id = $_SESSION['user_id'];

$info_row = $conn->query("SELECT * FROM `employees` WHERE `user_id` = '$user_id'")->fetch_assoc();
$name = $info_row['user_name'];
$userName = $info_row['user_id'];
$customerId = $_GET['customerID'];
// $ftch_cust_sql = "SELECT * FROM `customerleads` WHERE `sno` = '$customerId'";
// $ftch_cust_cmd = mysqli_query($conn, $ftch_cust_sql);
// $ftch_cust_info = mysqli_fetch_assoc($ftch_cust_cmd);
$ftch_cust_info = $conn->query("SELECT * FROM `customerleads` WHERE `sno` = '$customerId'")->fetch_assoc();

?>
<div class="container-fluid">

    <div class="dashboard-container">
        <div id="tool-div" class="d-none tooldiv">
            <?php include 'partials/_tool.php'; ?>
        </div>
        <div class="dashboard">
            <div class="top-functions d-flex justify-content-between">
                <div class="name">
                    <h4><i id="tool_hide" class="fa-solid fa-bars me-3"></i>Welcome <?php echo $name; ?></h4>
                    <?php echo date("d-m-Y, l"); ?>
                </div>
            </div>
            <div class="container mt-4">
                <form action="customer_change.php" method="post">
                    <div class="row">
                        <div class="col-6 mb-2">
                            <label for="cust_num"><span class="text-danger">*</span>Customer Number</label>
                            <input class="form-control" name="cust_num" maxlength="10" minlength="10"
                                value="<?php echo $ftch_cust_info['customer_num']; ?>" required>
                        </div>
                        <input type="hidden" name="sno" value="<?php echo $customerId; ?>">
                        <div class="col-6 mb-2">
                            <label for="alt_number">Alternate Number</label>
                            <input class="form-control" name="alt_number"
                                value="<?php echo $ftch_cust_info['alt_number']; ?>" maxlength="10" minlength="10">
                        </div>
                        <div class="col-6 mb-2">
                            <label for="customer_name"><span class="text-danger">*</span>Customer Name</label>
                            <input class="form-control" name="customer_name"
                                value="<?php echo $ftch_cust_info['customer_name']; ?>" required>
                        </div>
                        <div class="col-6 mb-2">
                            <label for="cust_company"><span class="text-danger">*</span>Company</label>
                            <input type="text" class="form-control" name="cust_company"
                                value="<?php echo $ftch_cust_info['cust_company']; ?>" required>
                        </div>
                        <div class="col-6 mb-2">
                            <label for="website">Website</label>
                            <input type="text" class="form-control" name="website"
                                value="<?php echo $ftch_cust_info['website']; ?>">
                        </div>
                        <div class="col-6 mb-2">
                            <label for="cust_mail"><span class="text-danger">*</span>Email</label>
                            <input type="email" class="form-control" name="cust_mail" required value="<?php echo $ftch_cust_info['cust_mail'] ?>" required>
                        </div>
                        <div class="col-6 mb-2">
                            <label for="cust_address"><span class="text-danger">*</span>Address</label>
                            <input type="text" class="form-control" name="cust_address"
                                value="<?php echo $ftch_cust_info['cust_address']; ?>" required>
                        </div>
                        <div class="col-6 mb-2">
                            <label for="cust_state"><span class="text-danger">*</span>State</label>
                            <select class="form-select" name="cust_state" id="cust_state" required>
                                <option value="">Select State</option>
                                    <?php
                                    $sql_stat = "SELECT * FROM states ORDER BY name";
                                    $res_state = mysqli_query($conn, $sql_stat);
                                    while($row = mysqli_fetch_assoc($res_state)){
                                    ?>
                                    <option value="<?= $row['name'] ?>"><?= $row['name'] ?></option>
                                    <?php } ?>
                            </select>
                        </div>
                        <div class="col-6 mb-2">
                            <label for="pan">PAN</label>
                            <input class="form-control" name="pan" value="<?php echo $ftch_cust_info['pan']; ?>">
                        </div>
                        <div class="col-6 mb-2">
                            <label for="GST"><span class="text-danger">*</span>GST</label>
                            <input type="text" class="form-control" name="GST" maxlength="15" minlength="15"
                                value="<?php echo $ftch_cust_info['GST']; ?>">
                        </div>
                        <div class="col-6 mb-2">
                            <label for="cust_pincode"><span class="text-danger">*</span>Pincode</label>
                            <input type="text" class="form-control" name="cust_pincode" value="<?php echo $ftch_cust_info['cust_pincode'] ?>" required>
                        </div>
                        <div class="text-center mt-3">
                            <button class="btn btn-outline-success">Save Changes</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>



<?php
include 'partials/_footer.php';
?>