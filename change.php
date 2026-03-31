<?php
if(!isset($_SESSION['user_id'])){
    header('location: index.php');
}
include 'partials/_header.php';
include 'partials/_footer.php';

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $empID = $_POST['empId'];
    $userName = $_POST['userName'];
    $password = $_POST['userPassword'];
    $dob = $_POST['userDOB'];
    $doj = $_POST['userDOJ'];
    $num = $_POST['userNum'];
    $userMail = $_POST['userMail'];
    $address = $_POST['userAdd'];
    $target = $_POST['userTarget'];
    $department = $_POST['department'];
    $role = $_POST['userRole'];
    $grade = $_POST['userGrade'];
    $reporting = $_POST['reporting'];
    $hr = $_POST['lineHR'];
    $edit_info_sql = "UPDATE `employees` SET `user_name` = '$userName', `user_password` = '$password', `user_dob` = '$dob', `user_doj` = '$doj', `user_num` = '$num', `user_mail` = '$userMail', `user_address` = '$address', `user_target` = '$target', `department` = '$department', `line_hr` = '$hr', `user_role` = '$role', `user_grade` = '$grade', `Reporting` = '$reporting'  WHERE `emp Id` =  '$empID'";
    $edit_info = mysqli_query($conn, $edit_info_sql);
    header('location: employee.php');
}
?>