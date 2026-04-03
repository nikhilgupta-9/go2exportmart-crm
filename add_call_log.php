<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
    exit;
}

include 'partials/_dbconnect.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_call_log'])) {
    $lead_id = intval($_POST['lead_id']);
    $call_status = mysqli_real_escape_string($conn, $_POST['call_status']);
    $call_duration = mysqli_real_escape_string($conn, $_POST['call_duration']);
    $follow_up_date = !empty($_POST['follow_up_date']) ? mysqli_real_escape_string($conn, $_POST['follow_up_date']) : null;
    $call_notes = mysqli_real_escape_string($conn, $_POST['call_notes']);
    $source = mysqli_real_escape_string($conn, $_POST['source'] ?? 'Manual');
    
    // Fetch lead details
    $lead_sql = "SELECT * FROM customerleads WHERE sno = '$lead_id'";
    $lead_result = $conn->query($lead_sql);
    $lead = $lead_result->fetch_assoc();
    
    if (!$lead) {
        $_SESSION['error_message'] = "Lead not found!";
        header('location: lead.php');
        exit;
    }
    
    // Update lead status based on call status
    $new_status = $lead['status'];
    switch($call_status) {
        case 'committed':
            $new_status = 'Committed';
            break;
        case 'not_interested':
            $new_status = 'Not Interested';
            break;
        case 'positive':
            $new_status = 'Positive';
            break;
        case 'follow_up':
            $new_status = 'Follow Up';
            break;
        case 'payment_done':
            $new_status = 'Committed';
            // Update matelize status if payment done
            $conn->query("UPDATE customerleads SET matelize = '1' WHERE sno = '$lead_id'");
            break;
        case 'call_picked':
            // Status remains the same
            break;
        default:
            $new_status = $lead['status'];
    }
    
    // Update lead status if changed
    if ($new_status != $lead['status']) {
        $conn->query("UPDATE customerleads SET status = '$new_status' WHERE sno = '$lead_id'");
    }
    
    // Insert call log
    $insert_sql = "INSERT INTO call_logs (lead_id, call_status, call_duration, follow_up_date, call_notes, source, created_by, created_at) 
                   VALUES ('$lead_id', '$call_status', '$call_duration', " . ($follow_up_date ? "'$follow_up_date'" : "NULL") . ", '$call_notes', '$source', '$user_id', NOW())";
    
    if ($conn->query($insert_sql)) {
        $_SESSION['success_message'] = "Call log added successfully!";
    } else {
        $_SESSION['error_message'] = "Error adding call log: " . $conn->error;
    }
    
    // Redirect back to lead page
    header('location: lead.php');
    exit;
}

// If accessed directly without POST, redirect to lead page
header('location: lead.php');
exit;
?>