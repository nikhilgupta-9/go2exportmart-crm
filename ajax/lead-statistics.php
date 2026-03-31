<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include_once "../partials/_dbconnect.php";

$user_id = $_SESSION['user_id'];
$grade_level = $_SESSION['grade_level'];

// Build query based on user level (same logic as lead.php)
$query = "SELECT 
    SUM(CASE WHEN status = 'Fresh Lead' THEN 1 ELSE 0 END) as fresh_leads,
    SUM(CASE WHEN status = 'Follow Up' THEN 1 ELSE 0 END) as follow_up_leads,
    SUM(CASE WHEN status = 'Committed' THEN 1 ELSE 0 END) as committed_leads
FROM customerleads WHERE matelize != '1'";

if ($grade_level == 4) {
    $query .= " AND assigned_to = '$user_id' AND status != 'Block'";
} elseif ($grade_level == 3) {
    $query .= " AND Reporting = '$user_id'";
}

$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

echo json_encode(['success' => true, 'data' => $data]);
?>