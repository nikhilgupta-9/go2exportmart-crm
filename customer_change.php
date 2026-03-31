<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header('location: index.php');
}
include 'partials/_dbconnect.php';

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $sno = $_POST['sno'];
    $cust_num = $_POST['cust_num'];
    $alt_num = $_POST['alt_number'];
    $cust_name = $_POST['customer_name'];
    $company = $_POST['cust_company'];
    $mail = $_POST['cust_mail'];
    $website = $_POST['website'];
    $address = $_POST['cust_address'];
    $state = $_POST['cust_state'];
    $pan = $_POST['pan'];
    $GST = $_POST['GST'];
    $pincode = $_POST['cust_pincode'];

    $edit_sql = "UPDATE `customerleads` SET
     `customer_num` = '$cust_num',
     `alt_number` = '$alt_num',
     `customer_name` = '$cust_name',
     `cust_mail` = '$mail',
     `cust_company` = '$company',
     `website` = '$website', 
     `cust_address` = '$address', 
     `cust_state` = '$state', 
     `pan` = '$pan', 
     `GST` = '$GST',
     `cust_pincode` = '$pincode'
      WHERE `sno` = '$sno'";
    $result = mysqli_query($conn, $edit_sql);
    header('location: lead.php');
}
?>