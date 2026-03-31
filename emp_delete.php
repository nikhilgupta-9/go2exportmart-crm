<?php
session_start();
include_once "partials/_dbconnect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['grade_level'] != 1) {
    header('location: index.php');
    exit;
}

$id = intval($_GET['id']);
$conn->query("DELETE FROM employees WHERE emp_id = $id");
header('location: employee.php');
?>