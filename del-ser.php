<?php
include 'partials/_dbconnect.php';

if(isset($_POST['del_service'])){
    $service = $_POST['service_id'];
    if($del_query = $conn->query("DELETE FROM `services` WHERE `sno` = '$service'")){
        header('location: service.php');
    }
}