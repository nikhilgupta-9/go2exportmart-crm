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
$customerID = $_GET['customerID'];
$ftch_cust_info_cmd = "SELECT * FROM `customerleads` WHERE `sno` = '$customerID'";
$cust_info_result = mysqli_query($conn, $ftch_cust_info_cmd);
$row_cust_info = mysqli_fetch_assoc($cust_info_result);

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
            <div class="container mt-3">

                <form action="matelize_part.php" method="post">
                    <div class="row">
                        <div class="col-6 mb-2">
                            <label for="customer_num">Customer Number</label>
                            <input type="text" class="form-control" name="customer_num" readonly value="<?php echo $row_cust_info['customer_num']; ?>">
                        </div>
                        <div class="col-6 mb-2">
                            <label for="alt_number">Alternate Number</label>
                            <input type="text" class="form-control" name="alt_number" value="<?php echo $row_cust_info['alt_number']; ?>" readonly>
                        </div>
                        <div class="col-6 mb-2">
                            <label for="customer_name">Customer Name</label>
                            <input type="text" class="form-control" name="customer_name" value="<?php echo $row_cust_info['customer_name']; ?>" readonly>
                        </div>
                        <div class="col-6 mb-2">
                            <label for="cust_company">Company</label>
                            <input type="text" class="form-control" name="cust_company" value="<?php echo $row_cust_info['cust_company'] ?>" readonly>
                        </div>
                        <div class="col-6 mb-2">
                            <div>
                                <label for="service">Service</label>
                            </div>
                            <select class="form-select" name="service" id="service">
                                <?php
                                $serv_sql = "SELECT * FROM `services`";
                                $result_serv = mysqli_query($conn, $serv_sql);
                                while ($row_serv = mysqli_fetch_assoc($result_serv)) {
                                    echo '
                                    <option value="' . $row_serv['Service Name'] . '">' . $row_serv['Service Name'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-6 mb-2">
                            <label for="website">Website</label>
                            <input type="text" class="form-control" name="website" value="<?php echo $row_cust_info['website'] ?>">
                        </div>
                        <div class="col-6 mb-2">
                            <label for="cust_mail">Email</label>
                            <input type="email" class="form-control" name="cust_mail" value="<?php echo $row_cust_info['cust_mail'] ?>">
                        </div>
                        <div class="col-6 mb-2">
                            <label for="cust_address">Address</label>
                            <input type="text" class="form-control" name="cust_address" value="<?php echo $row_cust_info['cust_address'] ?>">
                        </div>
                        <div class="col-6 mb-2">
                            <label for="state">State</label>
                            <input type="text" class="form-control" name="state" value="<?php echo $row_cust_info['cust_state']; ?>">
                        </div>
                        <div class="col-6 mb-2">
                            <label for="pincode">State</label>
                            <input type="text" class="form-control" name="pincode" value="<?php echo $row_cust_info['cust_pincode']; ?>">
                        </div>
                        <div class="col-6 mb-2">
                            <label for="pan"><span class="text-danger">*</span>PAN</label>
                            <input type="text" minlength="10" maxlength="10" class="form-control" name="pan" value="<?php echo $row_cust_info['pan'] ?>" required>
                        </div>
                        <div class="col-6 mb-2">
                            <label for="GST"><span class="text-danger">*</span>GST</label>
                            <input type="text" minlength="15" maxlength="15" class="form-control" name="GST" value="<?php echo $row_cust_info['GST'] ?>" required>
                        </div>
                        <div class="col-6 mb-2">
                            <label for="MRP"><span class="text-danger">*</span>MRP</label>
                            <input id="mrp" type="number" class="form-control" name="MRP" value="<?php echo $row_cust_info['MRP'] ?>" readonly>
                        </div>
                        <div class="col-6 mb-2">
                            <label for="amount"><span class="text-danger">*</span>Already Paid</label>
                            <input id="pamt" type="number" class="form-control" name="amount" value="<?php echo $row_cust_info['amount'] ?>" required>
                        </div>
                        <div class="col-6 mb-2">
                            <label for="nxtpay"><span class="text-danger">*</span>Paid Amount</label>
                            <input id="nxtpay" type="number" class="form-control" name="nxtpay" value="0" required>
                        </div>
                        <div class="col-6 mb-2">
                            <label for="discount"><span class="text-danger">*</span>Discount</label>
                            <input id="discount" type="number" class="form-control" name="discount" value="<?php echo $row_cust_info['discount'] ?>" required>
                        </div>
                        <div class="col-6 mb-2">
                            <label for="bal_amt">Balance</label>
                            <input id="balAmt" type="number" class="form-control" name="bal_amt" value="<?php echo $row_cust_info['bal_amt'] ?>" readonly>
                        </div>
                        <div class="col-6 mb-2">
                            <div>
                                <label for="pay_mode">Payment Method</label>
                            </div>
                            <select class="form-select" name="pay_mode" id="pay_mode">
                                <option value="upi">UPI</option>
                                <option value="neft">NEFT</option>
                                <option value="cc">Card</option>
                                <option value="dc">IMPS</option>
                                <option value="Nb">Net Banking</option>
                            </select>
                        </div>
                        <div class="col-6 mb-2">
                            <label for="transaction"><span class="text-danger">*</span>Transaction ID</label>
                            <input type="text" class="form-control" name="transaction" value="<?php echo $row_cust_info['transaction'] ?>" required>
                        </div>
                        <div class="text-center mt-3">
                            <button class="btn btn-outline-success">Matelize Lead</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="assets\js\bal_amt.js"></script>
    <?php
    include 'partials/_footer.php';
    ?>