<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
    exit();
}

if($_SERVER['REQUEST_METHOD'] == "POST"){

    include 'partials/_header.php';
    
    $assign_to = preg_replace('/[^A-Za-z0-9]/', '', $_POST['assign_to']);


    $transfer_lead = preg_replace('/[^0-9,]/', '', $_POST['transfer_lead']);

    
    $name_sql = $conn->prepare("SELECT `user_name` FROM `employees` WHERE `user_id` = ?");
    $name_sql->bind_param("s", $assign_to);
    $name_sql->execute();
    $pname = $name_sql->get_result()->fetch_assoc()['user_name'];

        
    $user_id = $_SESSION['user_id'];
    $info_row = $conn->prepare("SELECT * FROM `employees` WHERE `user_id` = ?");

    $info_row->bind_param('s', $user_id);
    $info_row->execute();
    $uname = $info_row->get_result()->fetch_assoc();
    $name = $uname['user_name'];
    $userName = $uname['user_id'];
    
    $leads = explode(",", $transfer_lead);
    print_r($leads);

    foreach ($leads as $lead) {

        $assign_sql = $conn->prepare("UPDATE `customerleads` SET `assigned_to` = ? WHERE `sno` = ?");
        $assign_sql->bind_param("ss" , $assign_to , $lead);
        $assign_sql->execute();

    }

    header('location: lead.php');


    
} else {
    header('location: lead.php');
}
