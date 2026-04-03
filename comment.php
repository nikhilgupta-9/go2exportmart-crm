<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
    exit;
}

include 'partials/_dbconnect.php';
include 'partials/_header.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$grade_level = $_SESSION['grade_level'];

// Get lead ID from URL
$lead_id = isset($_GET['customerId']) ? intval($_GET['customerId']) : (isset($_POST['customerId']) ? intval($_POST['customerId']) : 0);

if (!$lead_id) {
    header('location: lead.php');
    exit;
}

// Fetch lead details
$lead_sql = "SELECT * FROM customerleads WHERE sno = '$lead_id'";
$lead_result = $conn->query($lead_sql);
$lead = $lead_result->fetch_assoc();

if (!$lead) {
    header('location: lead.php');
    exit;
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_comment'])) {
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);
    $call_duration = mysqli_real_escape_string($conn, $_POST['call_duration'] ?? '');
    $follow_up_date = !empty($_POST['follow_up_date']) ? mysqli_real_escape_string($conn, $_POST['follow_up_date']) : null;
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    $previous_status = $lead['status'];
    
    // Update lead status if changed
    if ($new_status && $new_status != $previous_status) {
        $update_sql = "UPDATE customerleads SET status = '$new_status' WHERE sno = '$lead_id'";
        $conn->query($update_sql);
    }
    
    // Insert comment
    $insert_sql = "INSERT INTO lead_comments (lead_id, customer_num, comment_type, previous_status, new_status, comment, call_duration, follow_up_date, created_by, created_by_name, created_at) 
                   VALUES ('$lead_id', '{$lead['customer_num']}', 'call', '$previous_status', '$new_status', '$comment', '$call_duration', " . ($follow_up_date ? "'$follow_up_date'" : "NULL") . ", '$user_id', '$user_name', NOW())";
    
    if ($conn->query($insert_sql)) {
        $_SESSION['success_message'] = "Comment added successfully!";
        
        // If follow up date is set, update lead's follow up date (if you have such a field)
        if ($follow_up_date) {
            // You can add a follow_up_date column to customerleads if needed
        }
        
        header("location: lead.php");
        exit;
    } else {
        $error_message = "Error saving comment: " . $conn->error;
    }
}

// Fetch all comments for this lead
$comments_sql = "SELECT c.*, e.user_name as commenter_name 
                 FROM lead_comments c 
                 LEFT JOIN employees e ON c.created_by = e.user_id 
                 WHERE c.lead_id = '$lead_id' 
                 ORDER BY c.created_at DESC";
$comments_result = $conn->query($comments_sql);
$total_comments = $comments_result->num_rows;

// Get status history
$status_history_sql = "SELECT * FROM lead_comments 
                       WHERE lead_id = '$lead_id' 
                       AND (previous_status IS NOT NULL OR new_status IS NOT NULL)
                       ORDER BY created_at DESC";
$status_history_result = $conn->query($status_history_sql);

include_once "includes/link.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Lead Comments | GO2EXPORT MART</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once "includes/link.php"; ?>
    
    <style>
        .customer-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .comment-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        
        .comment-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eef2f6;
        }
        
        .commenter-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .status-change {
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 13px;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-fresh { background: #e3f2fd; color: #1976d2; }
        .status-followup { background: #fff3e0; color: #f57c00; }
        .status-positive { background: #e8f5e9; color: #388e3c; }
        .status-committed { background: #e1f5fe; color: #0288d1; }
        .status-not-interested { background: #ffebee; color: #d32f2f; }
        
        .call-duration {
            font-family: monospace;
            background: #f1f3f4;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .quick-status-btn {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid #dee2e6;
            background: white;
        }
        
        .quick-status-btn:hover {
            transform: translateY(-2px);
        }
        
        .quick-status-btn.selected {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }
        
        @media (max-width: 768px) {
            .comment-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <?php include_once "includes/header.php"; ?>
        <?php include_once "includes/sidebar.php"; ?>

        <div class="page-wrapper">
            <div class="content pb-0">
                <!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-1">Lead Comments</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="lead.php">Leads</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Comments</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
                        <a href="lead.php" class="btn btn-outline-secondary">
                            <i class="ti ti-arrow-left me-1"></i> Back to Leads
                        </a>
                    </div>
                </div>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="ti ti-check-circle me-2"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="ti ti-alert-circle me-2"></i> <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Customer Information Card -->
                <div class="customer-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4 class="mb-2"><?php echo htmlspecialchars($lead['customer_name']); ?></h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><i class="ti ti-phone"></i> <strong><?php echo htmlspecialchars($lead['customer_num']); ?></strong></p>
                                    <?php if (!empty($lead['alt_number'])): ?>
                                        <p class="mb-1"><i class="ti ti-phone-call"></i> Alt: <?php echo htmlspecialchars($lead['alt_number']); ?></p>
                                    <?php endif; ?>
                                    <p class="mb-1"><i class="ti ti-mail"></i> <?php echo htmlspecialchars($lead['cust_mail'] ?: '—'); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><i class="ti ti-building"></i> <?php echo htmlspecialchars($lead['cust_company'] ?: '—'); ?></p>
                                    <p class="mb-1"><i class="ti ti-map-pin"></i> <?php echo htmlspecialchars($lead['cust_state'] ?: '—'); ?></p>
                                    <p class="mb-1">
                                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $lead['status'])); ?>">
                                            <?php echo $lead['status']; ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <a href="edit_customer.php?customerID=<?php echo $lead['sno']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-edit"></i> Edit Lead
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Add Comment Form -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-bottom">
                        <h5 class="mb-0"><i class="ti ti-message-circle me-2"></i>Add New Comment</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" id="commentForm">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Call Status</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="quick-status-btn" data-status="Fresh Lead">Fresh Lead</button>
                                        <button type="button" class="quick-status-btn" data-status="Follow Up">Follow Up</button>
                                        <button type="button" class="quick-status-btn" data-status="Positive">Positive</button>
                                        <button type="button" class="quick-status-btn" data-status="Committed">Committed</button>
                                        <button type="button" class="quick-status-btn" data-status="Not Interested">Not Interested</button>
                                    </div>
                                    <input type="hidden" name="new_status" id="new_status" value="<?php echo $lead['status']; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Call Duration</label>
                                    <input type="text" class="form-control" name="call_duration" id="call_duration" 
                                           placeholder="e.g., 2:30 or 150 seconds">
                                    <div class="help-text mt-1">Enter call duration (optional)</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Next Follow-up Date</label>
                                    <input type="date" class="form-control" name="follow_up_date" id="follow_up_date">
                                    <div class="help-text mt-1">Set next follow-up date (optional)</div>
                                </div>
                                
                                <div class="col-12 mb-3">
                                    <label class="form-label">Comment / Notes <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="comment" rows="4" required 
                                              placeholder="What happened during the call? Any important notes? Customer requirements?"></textarea>
                                </div>
                                
                                <div class="col-12">
                                    <button type="submit" name="save_comment" class="btn btn-primary">
                                        <i class="ti ti-device-floppy me-2"></i>Save Comment
                                    </button>
                                    <button type="reset" class="btn btn-secondary ms-2">
                                        <i class="ti ti-refresh me-2"></i>Clear
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Comments History -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-bottom">
                        <h5 class="mb-0"><i class="ti ti-history me-2"></i>Call & Comment History</h5>
                        <small class="text-muted">Total <?php echo $total_comments; ?> comments</small>
                    </div>
                    <div class="card-body">
                        <?php if ($comments_result->num_rows > 0): ?>
                            <?php while($comment = $comments_result->fetch_assoc()): 
                                $commenter_name = $comment['created_by_name'] ?: $comment['created_by'];
                            ?>
                            <div class="comment-card">
                                <div class="comment-header">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="commenter-avatar">
                                            <?php echo strtoupper(substr($commenter_name, 0, 2)); ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($commenter_name); ?></h6>
                                            <small class="text-muted"><?php echo date('d M Y, h:i A', strtotime($comment['created_at'])); ?></small>
                                        </div>
                                    </div>
                                    <?php if ($comment['call_duration']): ?>
                                        <span class="call-duration">
                                            <i class="ti ti-clock"></i> Duration: <?php echo htmlspecialchars($comment['call_duration']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($comment['previous_status'] && $comment['new_status'] && $comment['previous_status'] != $comment['new_status']): ?>
                                <div class="status-change">
                                    <i class="ti ti-exchange"></i> Status changed from 
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $comment['previous_status'])); ?>">
                                        <?php echo $comment['previous_status']; ?>
                                    </span>
                                    <i class="ti ti-arrow-right"></i>
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $comment['new_status'])); ?>">
                                        <?php echo $comment['new_status']; ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="mt-2">
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                </div>
                                
                                <?php if ($comment['follow_up_date']): ?>
                                <div class="mt-2">
                                    <small class="text-warning">
                                        <i class="ti ti-alarm"></i> Next Follow-up: <?php echo date('d M Y', strtotime($comment['follow_up_date'])); ?>
                                    </small>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="ti ti-message-circle-off fs-48 text-muted"></i>
                                <h5 class="mt-3">No Comments Yet</h5>
                                <p class="text-muted">Add your first comment about this lead</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <?php include_once "includes/footer-link.php"; ?>

    <script>
        $(document).ready(function() {
            // Quick status selection
            $('.quick-status-btn').click(function() {
                $('.quick-status-btn').removeClass('selected');
                $(this).addClass('selected');
                var status = $(this).data('status');
                $('#new_status').val(status);
            });
            
            // Auto-format call duration
            $('#call_duration').on('input', function() {
                var value = $(this).val();
                // Auto-format if user enters just numbers
                if (/^\d+$/.test(value)) {
                    var seconds = parseInt(value);
                    var minutes = Math.floor(seconds / 60);
                    var remainingSeconds = seconds % 60;
                    if (minutes > 0) {
                        $(this).val(minutes + ':' + (remainingSeconds < 10 ? '0' + remainingSeconds : remainingSeconds));
                    } else {
                        $(this).val(seconds + ' seconds');
                    }
                }
            });
        });
    </script>
</body>
</html>