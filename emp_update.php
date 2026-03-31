<?php
include_once "partials/_dbconnect.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $stmt = $conn->prepare("UPDATE employees SET 
        user_name=?, user_mail=?, user_num=?, user_dob=?, user_doj=?, 
        department=?, user_role=?, user_target=?, Reporting=?, 
        line_hr=?, status=?, user_address=? 
        WHERE emp_id=?");

    $stmt->bind_param(
        "ssssssssssssi",
        $_POST['user_name'],
        $_POST['user_mail'],
        $_POST['user_num'],
        $_POST['user_dob'],
        $_POST['user_doj'],
        $_POST['department'],
        $_POST['user_role'],
        $_POST['user_target'],
        $_POST['Reporting'],
        $_POST['line_hr'],
        $_POST['status'],
        $_POST['user_address'],
        $_POST['emp_id']
    );

    $stmt->execute();

    header("Location: employees.php");
}