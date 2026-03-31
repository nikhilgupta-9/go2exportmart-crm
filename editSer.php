<?php

include 'partials/_dbconnect.php';

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $sno = $_POST['sno'];
    $newser = $_POST['servicename'];
    $query = $conn->query("UPDATE `services` SET `Service Name` = '$newser' WHERE `sno` = '$sno'");
    header('location: service.php');
}
