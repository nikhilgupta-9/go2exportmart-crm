<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

include_once "../partials/_dbconnect.php";

$id = intval($_GET['id']);
$sql = "SELECT * FROM customerleads WHERE sno = $id";
$result = mysqli_query($conn, $sql);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode(['success' => true, 'data' => $row]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lead not found']);
}
?>