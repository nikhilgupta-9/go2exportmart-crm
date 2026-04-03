<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include_once "../partials/_dbconnect.php";

$lead_id = intval($_GET['lead_id']);

$sql = "SELECT cl.*, e.user_name as created_by 
        FROM call_logs cl 
        LEFT JOIN employees e ON cl.created_by = e.user_id 
        WHERE cl.lead_id = '$lead_id' 
        ORDER BY cl.created_at DESC";

$result = mysqli_query($conn, $sql);
$calls = [];

while ($row = mysqli_fetch_assoc($result)) {
    $calls[] = $row;
}

echo json_encode(['success' => true, 'data' => $calls]);
?>