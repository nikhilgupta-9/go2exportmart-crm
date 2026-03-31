<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include_once "../partials/_dbconnect.php";

$lead_ids = $_POST['lead_ids'];
$new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
$comment = mysqli_real_escape_string($conn, $_POST['comment']);

if (empty($lead_ids) || empty($new_status)) {
    echo json_encode(['success' => false, 'message' => 'Please select leads and status']);
    exit;
}

$ids_array = explode(',', $lead_ids);
$success_count = 0;

foreach ($ids_array as $id) {
    $id = intval($id);
    $update_sql = "UPDATE customerleads SET status = '$new_status' WHERE sno = $id";
    if (mysqli_query($conn, $update_sql)) {
        $success_count++;
        
        // Add comment if provided
        if (!empty($comment)) {
            $comment_sql = "INSERT INTO lead_comments (lead_id, user_id, comment, created_at) 
                           VALUES ($id, '{$_SESSION['user_id']}', '$comment', NOW())";
            mysqli_query($conn, $comment_sql);
        }
    }
}

echo json_encode(['success' => true, 'message' => "Updated $success_count leads successfully"]);
?>