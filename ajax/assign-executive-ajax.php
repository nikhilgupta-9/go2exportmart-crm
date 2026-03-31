<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['grade_level'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

include_once "../partials/_dbconnect.php";

$executive_id = mysqli_real_escape_string($conn, $_POST['executive_id']);
$team_id = mysqli_real_escape_string($conn, $_POST['team_id']);

$update_sql = "UPDATE employees SET team_id = '$team_id' WHERE user_id = '$executive_id' AND grade_level = 4";

if (mysqli_query($conn, $update_sql)) {
    echo json_encode(['success' => true, 'message' => 'Executive assigned successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
}
?>