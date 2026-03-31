<?php

include 'partials/_dbconnect.php';

if(isset($_POST['add-service'])){
    $service = $_POST['add_service'];
    $result_sql = $conn->query("INSERT INTO `services` (`Service Name`) VALUES ('$service')");
    header('location: service.php');
}


