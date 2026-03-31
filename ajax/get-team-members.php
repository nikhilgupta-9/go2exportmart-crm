<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include_once "../partials/_dbconnect.php";

$team_id = intval($_GET['team_id']);
$sql = "SELECT emp_id, user_id, user_name, user_num, user_mail FROM employees WHERE team_id = '$team_id' AND grade_level = 4";
$result = mysqli_query($conn, $sql);

$members = [];
while ($row = mysqli_fetch_assoc($result)) {
    $members[] = $row;
}

echo json_encode(['success' => true, 'data' => $members]);
?>