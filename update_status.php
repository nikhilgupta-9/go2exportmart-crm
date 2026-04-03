<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
    exit;
}

include 'partials/_dbconnect.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['customerId'])) {
    $lead_id = intval($_POST['customerId']);
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);
    
    // Fetch current lead
    $lead_sql = "SELECT * FROM customerleads WHERE sno = '$lead_id'";
    $lead_result = $conn->query($lead_sql);
    $lead = $lead_result->fetch_assoc();
    
    if ($lead) {
        $previous_status = $lead['status'];
        
        // Update lead status
        $update_sql = "UPDATE customerleads SET status = '$new_status' WHERE sno = '$lead_id'";
        $conn->query($update_sql);
        
        // Insert status change comment
        $insert_sql = "INSERT INTO lead_comments (lead_id, customer_num, comment_type, previous_status, new_status, comment, created_by, created_by_name, created_at) 
                       VALUES ('$lead_id', '{$lead['customer_num']}', 'status', '$previous_status', '$new_status', '$comment', '$user_id', '$user_name', NOW())";
        $conn->query($insert_sql);
        
        $_SESSION['success_message'] = "Status updated successfully!";
    }
    
    header('location: lead.php');
    exit;
}
?>