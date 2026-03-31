<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header('location: index.php');
}
include 'partials/_header.php';
include 'partials/_footer.php';

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $userID = $_POST['userId'];
    $userName = $_POST['userName'];
    $password = $_POST['userPassword'];
    $dob = $_POST['userDOB'];
    $doj = $_POST['userDOJ'];
    $num = $_POST['userNum'];
    $userMail = $_POST['userMail'];
    $address = $_POST['userAdd'];
    $target = $_POST['userTarget'];
    $department = $_POST['department'];
    $hr = $_POST['lineHr'];
    $role = $_POST['userRole'];
    echo $role;
    if($role == 'Manager'){
        $grade = 'G2';
    }
    else if ($role == 'Team Lead') {
        $grade = 'G3';
    }
    else{
        $grade = 'G4';
    }
        $level1 = explode("G", $grade);
    $level = $level1[1];
    $reporting = $_POST['reporting'];

    $insert_emp_cmd = "INSERT INTO `employees` (`user_id`, `user_name`, `user_password`, `user_dob`, `user_doj`, `user_num`, `user_mail`, `user_address`, `user_target`, `department`, `line_hr`, `user_role`, `user_grade`, `grade_level`, `Reporting`) VALUES ('$userID', '$userName', '$password', '$dob', '$doj', '$num', '$userMail', '$address', '$target', '$department', '$hr', '$role', '$grade', '$level', '$reporting')";
    $insert_emp = mysqli_query($conn, $insert_emp_cmd);
}
header('location: employee.php');
?>