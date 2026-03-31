<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header('location: index.php');
}
include 'partials/_header.php';
include 'partials/_footer.php';

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $cust_num = $_POST['customer_num'];
    $customerId = $_POST['sno'];
    $service = $_POST['service'];
    $website = $_POST['website'];
    $address = $_POST['cust_address'];
    $state = $_POST['state'];
    $pan = $_POST['pan'];
    $gst = $_POST['GST'];
    $mrp  = $_POST['MRP'];
    $paid = $_POST['amount'];
    $discount = $_POST['discount'];
    $balance = $_POST['bal_amt'];
    $bal = explode(".", $balance);
    $finbal = $bal[0];
    $comment = $_POST['comment'];
    $method = $_POST['pay_mode'];
    $transaction  = $_POST['transaction'];
    $month = date("F");
    $matelize_sql = "UPDATE `customerleads` SET `service` = '$service', `website` = '$website', `cust_address` = '$address', `cust_state` = '$state', `pan` = '$pan', `GST` = '$gst', `MRP` = '$mrp', `amount` = '$paid', `discount` = '$discount', `bal_amt` = '$finbal', `pay_mode` = '$method', `transaction` = '$transaction', `matelize` = '1', `invoice` = '$comment', `todaydate` = current_timestamp(), `month` = '$month', `dtstamp` = current_timestamp() WHERE `sno` = '$customerId'";


    if($result_matelize = mysqli_query($conn, $matelize_sql)){
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Success!</strong> Lead Matelized successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <script>
        setTimeout(()=>{
            document.location.href = "matelize.php";
        }, 1500);
      </script>';
    }
    else{
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Error!</strong> Unable to matelize lead.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <script>
        setTimeout(()=>{
          document.location.href = "matelize.php";
        }, 1500);
      </script>';
    }
    
}


?>