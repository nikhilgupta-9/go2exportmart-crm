<?php
session_start();
include_once "partials/_dbconnect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['grade_level'] != 1) {
    header('location: index.php');
    exit;
}

$id = intval($_GET['id']);
$status = $_GET['status'];

if ($status == 'left') {
    $conn->query("UPDATE employees SET status = 'left' WHERE emp_id = $id");
} elseif ($status == 'active') {
    $conn->query("UPDATE employees SET status = 1 WHERE emp_id = $id");
}

header('location: employee.php');
?>