<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
    exit;
}

include_once "partials/_dbconnect.php";

$user_id = $_SESSION['user_id'];
$grade_level = $_SESSION['grade_level'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reallocate'])) {
    $lead_id = intval($_POST['lead_id']);
    $assign_to = $_POST['assign_to'];
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    
    // Get reporting for the assignee
    $reporting_sql = "SELECT Reporting FROM employees WHERE user_id = '$assign_to'";
    $reporting_result = $conn->query($reporting_sql);
    $reporting_data = $reporting_result->fetch_assoc();
    $reporting = $reporting_data['Reporting'] ?? 'admin';
    
    $update_sql = "UPDATE fresh_leads SET 
                   assigned_to = '$assign_to',
                   reporting = '$reporting',
                   allocated_by = '$user_id',
                   allocated_at = NOW()
                   WHERE id = '$lead_id'";
    
    if ($conn->query($update_sql)) {
        // Log the reallocation
        $log_sql = "INSERT INTO allocation_logs (lead_id, from_user, to_user, reason, allocated_by, created_at) 
                    VALUES ('$lead_id', (SELECT assigned_to FROM fresh_leads WHERE id = '$lead_id'), '$assign_to', '$reason', '$user_id', NOW())";
        $conn->query($log_sql);
        
        $_SESSION['success_message'] = "Lead reallocated successfully!";
    } else {
        $_SESSION['error_message'] = "Error reallocating lead: " . $conn->error;
    }
    
    header('location: lead-allocation.php');
    exit;
}
?>