<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include_once "../partials/_dbconnect.php";

$user_id = mysqli_real_escape_string($conn, $_GET['user_id']);

$sql = "SELECT e.team_id, t.team_name 
        FROM employees e 
        LEFT JOIN teams t ON e.team_id = t.id 
        WHERE e.user_id = '$user_id'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

if ($row && $row['team_id']) {
    echo json_encode([
        'success' => true, 
        'has_team' => true, 
        'team_name' => $row['team_name']
    ]);
} else {
    echo json_encode(['success' => true, 'has_team' => false]);
}
?>