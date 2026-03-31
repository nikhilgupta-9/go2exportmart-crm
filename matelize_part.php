<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header('location: index.php');
}
include 'partials/_header.php';
include 'partials/_footer.php';

$user_id = $_SESSION['user_id'];
$userInfo = "SELECT * FROM `employees` WHERE `user_id` = '$user_id'";
$userResult = mysqli_query($conn, $userInfo);
$rowUser = mysqli_fetch_assoc($userResult);
$reporting = $rowUser['Reporting'];
if($_SERVER['REQUEST_METHOD'] == "POST"){
    $cust_num = $_POST['customer_num'];
    $alt = $_POST['alt_number'];
    $cust_name = $_POST['customer_name'];
    $company = $_POST['cust_company'];
    $service = $_POST['service'];
    $website = $_POST['website'];
    $mail = $_POST['cust_mail'];
    $address = $_POST['cust_address'];
    $state = $_POST['state'];
    $pincode = $_POST['pincode'];
    $pan = $_POST['pan'];
    $gst = $_POST['GST'];
    $mrp  = $_POST['MRP'];
    $paid = $_POST['amount'];
    $npaid = $_POST['nxtpay'];
    $discount = $_POST['discount'];
    $balance = $_POST['bal_amt'];
    $bal = explode(".", $balance);
    $finbal = $bal[0];
    $method = $_POST['pay_mode'];
    $transaction  = $_POST['transaction'];
    $month = date("F");
    $matelize_sql = "INSERT INTO `customerleads` (`customer_num`, `alt_number`, `assigned_to`, `reporting`, `customer_name`, `cust_company`, `service`, `website`, `cust_mail`, `cust_address`, `cust_state`, `cust_pincode`, `status`, `matelize`, `pan`, `GST`, `MRP`, `amount`, `discount`, `bal_amt`, `pay_mode`, `transaction`, `todaydate`, `month`, `dtstamp`) VALUES ('$cust_num', '$alt', '$user_id', '$reporting', '$cust_name', '$company', '$service', '$website', '$mail', '$address', '$state', '$pincode', 'Committed', '1', '$pan', '$gst', '$mrp', '$npaid', '$discount', '$finbal', '$method', '$transaction', current_timestamp(), '$month', current_timestamp())";

    // echo $matelize_sql;


    if($result_matelize = mysqli_query($conn, $matelize_sql)){
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Success!</strong> Part Matelize Added.
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
        <strong>Error!</strong> Unable to add lead.
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