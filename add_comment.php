<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header('location: index.php');
}
include 'partials/_header.php';
include 'partials/_footer.php';

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $user_id = $_SESSION['user_id'];
    $customer_num = $_POST['cust_number'];
    $meet_type = $_POST['meet_type'];
    $status = $_POST['status'];
    $comment = $_POST['comment'];

    $insert_cmt_cmd = "INSERT INTO `comments` (`cust_num`, `meet_type`, `status`, `comment`, `commenter`, `dtstamp`) VALUES ('$customer_num', '$meet_type', '$status', '$comment', '$user_id', current_timestamp());";
    $update_status_sql = "UPDATE `customerleads` SET `status` = '$status' WHERE `customer_num` = '$customer_num'";
    $insert_cmt = mysqli_query($conn, $insert_cmt_cmd);
    $update_status = mysqli_query($conn, $update_status_sql);
}
header('location: lead.php');
?>