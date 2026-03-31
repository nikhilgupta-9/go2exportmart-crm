<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('location: index.php');
}
include 'partials/_header.php';
include 'partials/_footer.php';



$user_id = $_SESSION['user_id'];
$info_ftch_sql = "SELECT * FROM `employees` WHERE `user_id` = '$user_id'";
$result_ftch_sql = mysqli_query($conn, $info_ftch_sql);
$info_row = mysqli_fetch_assoc($result_ftch_sql);
$reporting = $info_row['Reporting'];
if ($_SERVER['REQUEST_METHOD'] == "POST") {

  // CSRF CHECK
  if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die("Invalid CSRF token");
  }

  // SANITIZE INPUTS
  $number = trim($_POST['customer_num']);
  $alt = trim($_POST['alt_number']);
  $name = trim($_POST['customer_name']);
  $company = trim($_POST['cust_company']);
  $website = trim($_POST['website']);
  $mail = trim($_POST['cust_mail']);
  $address = trim($_POST['cust_address']);
  $state = trim($_POST['cust_state']);
  $pan = strtoupper(trim($_POST['pan']));
  $GST = strtoupper(trim($_POST['GST']));
  $pincode = trim($_POST['cust_pincode']);

  $user = (!empty($_POST['assigned_to'])) ? $_POST['assigned_to'] : $user_id;

  // ✅ VALIDATIONS
if (!preg_match('/^[6-9][0-9]{9}$/', $number)) {
  $_SESSION['session_message'] = "Invalid Mobile Number";
  header("Location: create_lead.php");
  exit();
}

if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
  $_SESSION['session_message'] = "Invalid Email";
  header("Location: create_lead.php");
  exit();
}

if (!preg_match('/^[0-9]{6}$/', $pincode)) {
  $_SESSION['session_message'] = "Invalid Pincode";
  header("Location: create_lead.php");
  exit();
}

// OPTIONAL PAN VALIDATION
if (!empty($pan) && !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan)) {
  $_SESSION['session_message'] = "Invalid PAN";
  header("Location: create_lead.php");
  exit();
}

// GST VALIDATION
if (!preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][A-Z0-9]{3}$/', $GST)) {
  $_SESSION['session_message'] = "Invalid GST";
  header("Location: create_lead.php");
  exit();
}

  // ✅ DUPLICATE CHECK (VERY IMPORTANT)
  $check = mysqli_prepare($conn, "SELECT * FROM customerleads WHERE customer_num = ?");
  mysqli_stmt_bind_param($check, "s", $number);
  mysqli_stmt_execute($check);
  $res = mysqli_stmt_get_result($check);

  if (mysqli_num_rows($res) > 0) {
    die("Lead already exists with this number");
  }

  // ✅ INSERT (PREPARED STATEMENT)
  $stmt = mysqli_prepare($conn, "INSERT INTO customerleads 
    (customer_num, alt_number, assigned_to, reporting, customer_name, cust_company, website, cust_mail, cust_address, cust_state, cust_pincode, status, matelize, pan, GST) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Fresh Lead', '0', ?, ?)");

  mysqli_stmt_bind_param(
    $stmt,
    "sssssssssssss",
    $number,
    $alt,
    $user,
    $reporting,
    $name,
    $company,
    $website,
    $mail,
    $address,
    $state,
    $pincode,
    $pan,
    $GST
  );

  if (mysqli_stmt_execute($stmt)) {
    echo '<div class="alert alert-success">Lead created successfully</div>';
  } else {
    echo '<div class="alert alert-danger">Error creating lead</div>';
  }
}

?>