<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
    exit;
}

include 'partials/_dbconnect.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['customerId'])) {
    $lead_id = intval($_POST['customerId']);
    $call_status = mysqli_real_escape_string($conn, $_POST['call_status']);
    $call_duration = mysqli_real_escape_string($conn, $_POST['call_duration']);
    $follow_up_date = !empty($_POST['follow_up_date']) ? mysqli_real_escape_string($conn, $_POST['follow_up_date']) : null;
    $call_notes = mysqli_real_escape_string($conn, $_POST['call_notes']);
    
    // Update lead status if needed
    if ($call_status == 'committed') {
        $conn->query("UPDATE customerleads SET status = 'Committed' WHERE sno = '$lead_id'");
    } elseif ($call_status == 'not_interested') {
        $conn->query("UPDATE customerleads SET status = 'Not Interested' WHERE sno = '$lead_id'");
    } elseif ($call_status == 'positive') {
        $conn->query("UPDATE customerleads SET status = 'Positive' WHERE sno = '$lead_id'");
    } elseif ($call_status == 'follow_up') {
        $conn->query("UPDATE customerleads SET status = 'Follow Up' WHERE sno = '$lead_id'");
    } elseif ($call_status == 'payment_done') {
        $conn->query("UPDATE customerleads SET status = 'Committed', matelize = '1' WHERE sno = '$lead_id'");
    }
    
    // Insert call log
    $insert_sql = "INSERT INTO call_logs (lead_id, call_status, call_duration, follow_up_date, call_notes, created_by, created_at) 
                   VALUES ('$lead_id', '$call_status', '$call_duration', " . ($follow_up_date ? "'$follow_up_date'" : "NULL") . ", '$call_notes', '$user_id', NOW())";
    
    if ($conn->query($insert_sql)) {
        $_SESSION['success_message'] = "Call log added successfully!";
    } else {
        $_SESSION['error_message'] = "Error adding call log: " . $conn->error;
    }
    
    header('location: lead.php');
    exit;
}
?>