<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header('location: index.php');
}
include 'partials/_header.php';

$id = $_SESSION['user_id'];
$ftch_info = "SELECT * FROM `employees` where `user_id` = '$id'";
$result_info = mysqli_query($conn, $ftch_info);
$row_info = mysqli_fetch_assoc($result_info);
$name = $row_info['user_name'];
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $customerId = $_POST['customerId'];
    $cust_info = "SELECT * FROM `customerleads` WHERE `sno` = '$customerId'";
    $result_cust = mysqli_query($conn, $cust_info);
    $row_cust = mysqli_fetch_assoc($result_cust);
    $services = "SELECT * FROM `services`";
    $result_ser = mysqli_query($conn, $services);
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
            <div class="container mt-3">

                <form action="make_proforma.php" method="post">
                    <div class="row">
                        <div class="col-6 mb-2">
                            <label for="cust_company"><span class="text-danger">*</span>Company Name</label>
                            <input type="text" class="form-control" id="custnum" name="cust_company"
                                value="<?php echo $row_cust['cust_company'] ?>" readonly>
                                <input type="hidden" name="customerid" value="<?php echo $customerId ?>">
                        </div>
                        <div class="col-6 mb-2">
                            <label for="cust_company"><span class="text-danger">*</span>GST</label>
                            <input type="text" class="form-control" id="cust_company" name="cust_company"
                                value="<?php echo $row_cust['GST'] ?>" readonly>
                        </div>
                        <div class="col-6 mb-2">
                            <label for="cust_address"><span class="text-danger">*</span>Address</label>
                            <input type="text" class="form-control" name="cust_address" id="cust_address"
                                value="<?php echo $row_cust['cust_address'] ?>" readonly>
                        </div>
                        <div class="col-6 mb-2">
                            <label for="pan"><span class="text-danger">*</span>PAN</label>
                            <input type="text" class="form-control" id="pan" name="pan" value="<?php echo $row_cust['pan'] ?>">
                        </div>
                        <div class="col-6 mb-2">
                            <label for="desc" class="form-label">Description</label>
                            <textarea class="form-control" id="desc" name="desc" rows="3"></textarea>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-5">
                                <label for="service">Service</label>
                                <select class="form-select" name="service" id="service">
                                    <?php 
                                while($row_ser = mysqli_fetch_assoc($result_ser)){
                                    echo '<option value="'. $row_ser['Service Name'] .'">'. $row_ser['Service Name'] .'</option>';
                                }
                                ?>
                                </select>
                            </div>
                            <div class="col-1">
                                <div>
                                    <label for="qty">Qty</label>
                                </div>
                                <input class="form-control" type="number" id="qty" value="1">
                            </div>
                            <div class="col-2">
                                <div>
                                    <label for="amount">Price without GST</label>
                                </div>
                                <input class="form-control" type="text" id="amount">
                            </div>
                            <div class="col-2">
                                <div>
                                    <label for="amount">GST (@18%)</label>
                                </div>
                                <input class="form-control" type="text" id="gst" readonly >
                            </div>
                            <div class="col-2">
                                <div>
                                    <label for="amount">Total Amount</label>
                                </div>
                                <input class="form-control" type="text" name="tamount" id="total" value="" required>
                            </div>
                        </div>
                        <div class="text-center">
                            <button class="btn btn-outline-success mt-4">Create Proforma</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/pricecal.js"></script>

<?php
include 'partials/_footer.php';
?>