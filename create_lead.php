<?php
session_start();
include 'partials/_dbconnect.php';
include_once 'util/function.php';

if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$grade_level = $_SESSION['grade_level'];
$user_name = $_SESSION['user_name'];

// Fetch user info
$user_info = $conn->query("SELECT * FROM employees WHERE user_id = '$user_id'")->fetch_assoc();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_lead'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = "Invalid security token. Please try again.";
        header('location: create_lead.php');
        exit();
    }

    // Sanitize and validate inputs
    $lead_source = mysqli_real_escape_string($conn, $_POST['lead_source']);
    $customer_num = mysqli_real_escape_string($conn, $_POST['customer_num']);
    $alt_number = mysqli_real_escape_string($conn, $_POST['alt_number']);
    $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
    $cust_company = mysqli_real_escape_string($conn, $_POST['cust_company']);
    $website = mysqli_real_escape_string($conn, $_POST['website']);
    $cust_mail = mysqli_real_escape_string($conn, $_POST['cust_mail']);
    $cust_address = mysqli_real_escape_string($conn, $_POST['cust_address']);
    $cust_state = mysqli_real_escape_string($conn, $_POST['cust_state']);
    $cust_pincode = mysqli_real_escape_string($conn, $_POST['cust_pincode']);
    $pan = mysqli_real_escape_string($conn, $_POST['pan']);
    $gst = mysqli_real_escape_string($conn, $_POST['gst']);
    $service = mysqli_real_escape_string($conn, $_POST['service']);
    $call_status = mysqli_real_escape_string($conn, $_POST['call_status']);
    $call_duration = mysqli_real_escape_string($conn, $_POST['call_duration']);
    $follow_up_date = !empty($_POST['follow_up_date']) ? mysqli_real_escape_string($conn, $_POST['follow_up_date']) : null;
    $call_notes = mysqli_real_escape_string($conn, $_POST['call_notes']);

    // Validate mobile number
    if (!preg_match('/^[6-9][0-9]{9}$/', $customer_num)) {
        $_SESSION['error_message'] = "Invalid mobile number. Please enter a valid 10-digit number starting with 6-9.";
        header('location: create_lead.php');
        exit();
    }

    // Check if lead already exists
    $check_sql = "SELECT sno FROM customerleads WHERE customer_num = '$customer_num'";
    $check_result = $conn->query($check_sql);
    if ($check_result->num_rows > 0) {
        $_SESSION['error_message'] = "Lead with this mobile number already exists!";
        header('location: create_lead.php');
        exit();
    }

    // Determine assigned_to and reporting based on grade level
    if ($grade_level == 4) {
        $assigned_to = $user_id;
        $reporting_sql = "SELECT Reporting FROM employees WHERE user_id = '$user_id'";
        $reporting_result = $conn->query($reporting_sql);
        $reporting_data = $reporting_result->fetch_assoc();
        $reporting = $reporting_data['Reporting'] ?? 'admin';
    } elseif ($grade_level == 3) {
        $assigned_to = !empty($_POST['assigned_to']) ? mysqli_real_escape_string($conn, $_POST['assigned_to']) : $user_id;
        $reporting = $user_id;
    } else {
        $assigned_to = mysqli_real_escape_string($conn, $_POST['assigned_to']);
        $reporting_sql = "SELECT Reporting FROM employees WHERE user_id = '$assigned_to'";
        $reporting_result = $conn->query($reporting_sql);
        $reporting_data = $reporting_result->fetch_assoc();
        $reporting = $reporting_data['Reporting'] ?? 'admin';
    }

    // Set status based on call status
    switch ($call_status) {
    case 'committed':
        $status = 'Committed';
        $matelize = 0;
        break;
    case 'not_interested':
        $status = 'Not Interested';
        $matelize = 0;
        break;
    case 'positive':
        $status = 'Positive';
        $matelize = 0;
        break;
    case 'payment_done':
        $status = 'Committed';
        $matelize = 1;
        break;
    case 'call_picked':
        $status = 'Call Picked';
        $matelize = 0;
        break;
    case 'call_not_picked':
        $status = 'Call Not Picked';
        $matelize = 0;
        break;
    case 'call_busy':
        $status = 'Call Busy';
        $matelize = 0;
        break;
    case 'call_switched_off':
        $status = 'Call Switched Off';
        $matelize = 0;
        break;
    case 'call_wrong_number':
        $status = 'Wrong Number';
        $matelize = 0;
        break;
    case 'call_cut':
        $status = 'Call Cut';
        $matelize = 0;
        break;
    case 'follow_up':
        $status = 'Follow Up';
        $matelize = 0;
        break;
    default:
        $status = 'Fresh Lead';
        $matelize = 0;
}

    $todaydate = date('Y-m-d');
    $month = date('F');
    $dtstamp = date('Y-m-d H:i:s');

    // Insert lead
    $insert_sql = "INSERT INTO customerleads (
        customer_num, alt_number, assigned_to, reporting, customer_name, 
        cust_company, service, website, cust_mail, cust_address, 
        cust_state, cust_pincode, status, matelize, pan, GST, 
        todaydate, month, dtstamp
    ) VALUES (
        '$customer_num', '$alt_number', '$assigned_to', '$reporting', '$customer_name',
        '$cust_company', '$service', '$website', '$cust_mail', '$cust_address',
        '$cust_state', '$cust_pincode', '$status', '$matelize', '$pan', '$gst',
        '$todaydate', '$month', '$dtstamp'
    )";

    if ($conn->query($insert_sql)) {
        // Insert call log
        $lead_id = $conn->insert_id;
        $call_log_sql = "INSERT INTO call_logs (lead_id, call_status, call_duration, follow_up_date, call_notes, source, created_by, created_at) 
                         VALUES ('$lead_id', '$call_status', '$call_duration', " . ($follow_up_date ? "'$follow_up_date'" : "NULL") . ", '$call_notes', '$lead_source', '$user_id', NOW())";
        $conn->query($call_log_sql);

        $_SESSION['success_message'] = "Lead created successfully! Lead assigned to: " . getEmployeeName($conn, $assigned_to);
        header('location: lead.php');
        exit();
    } else {
        $_SESSION['error_message'] = "Error creating lead: " . $conn->error;
        header('location: create_lead.php');
        exit();
    }
}

// Function to get employee name
function getEmployeeName($conn, $user_id)
{
    $sql = "SELECT user_name FROM employees WHERE user_id = '$user_id'";
    $result = $conn->query($sql);
    if ($row = $result->fetch_assoc()) {
        return $row['user_name'];
    }
    return $user_id;
}

// Fetch team members for Team Lead (Grade Level 3)
$team_members = [];
if ($grade_level == 3) {
    $members_sql = "SELECT user_id, user_name FROM employees WHERE Reporting = '$user_id' AND grade_level = 4 AND status = 1";
    $members_result = $conn->query($members_sql);
    while ($member = $members_result->fetch_assoc()) {
        $team_members[] = $member;
    }
}

// Fetch all executives for Admin (Grade Level 1 & 2)
$all_executives = [];
if ($grade_level < 3) {
    $exec_sql = "SELECT user_id, user_name FROM employees WHERE grade_level = 4 AND status = 1 ORDER BY user_name";
    $exec_result = $conn->query($exec_sql);
    while ($exec = $exec_result->fetch_assoc()) {
        $all_executives[] = $exec;
    }
}

include 'partials/_header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Create New Lead | GO2EXPORT MART</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once "includes/link.php"; ?>

    <style>
        .form-section {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eef2f6;
        }

        .section-title i {
            color: #667eea;
            margin-right: 10px;
        }

        .required-field::after {
            content: '*';
            color: #dc3545;
            margin-left: 4px;
        }

        .optional-field::after {
            content: '(Optional)';
            color: #6c757d;
            margin-left: 4px;
            font-weight: normal;
            font-size: 11px;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
        }

        .input-group-text {
            background: #f8f9fa;
        }

        .btn-submit {
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 10px;
        }

        .info-text {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }

        /* Status Card Styles */
        .status-card {
            border: 2px solid #eef2f6;
            border-radius: 12px;
            padding: 12px 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            margin-bottom: 10px;
        }

        .status-card:hover {
            border-color: #667eea;
            background: #f8f9fa;
            transform: translateY(-3px);
        }

        .status-card.selected {
            border-color: #28a745;
            background: #d4edda;
        }

        .status-card i {
            font-size: 24px;
            margin-bottom: 8px;
            display: block;
        }

        .status-card .status-title {
            font-size: 12px;
            font-weight: 500;
        }

        .status-card .status-desc {
            font-size: 10px;
            color: #6c757d;
            margin-top: 4px;
        }

        .source-badge {
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            border: 2px solid #eef2f6;
        }

        .source-badge:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        .source-badge.selected {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        /* Call Duration Input Styles */
        .duration-input-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .duration-input-group input {
            flex: 1;
        }

        .duration-preset {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }

        .duration-preset-btn {
            padding: 4px 12px;
            font-size: 12px;
            border: 1px solid #dee2e6;
            background: #f8f9fa;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .duration-preset-btn:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        @media (max-width: 768px) {
            .form-section {
                padding: 15px;
            }

            .status-card {
                padding: 8px 4px;
            }

            .status-card i {
                font-size: 20px;
            }

            .status-card .status-title {
                font-size: 10px;
            }

            .duration-preset {
                flex-wrap: wrap;
            }
        }

        .quick-input {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            margin-top: 10px;
        }

        .help-text {
            font-size: 11px;
            color: #6c757d;
            margin-top: 5px;
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
                        <h4 class="mb-1">Create New Lead</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="lead.php">Leads</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Create Lead</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
                        <a href="lead.php" class="btn btn-outline-secondary">
                            <i class="ti ti-arrow-left me-1"></i> Back to Leads
                        </a>
                    </div>
                </div>

                <!-- Display Messages -->
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="ti ti-alert-circle me-2"></i> <?php echo $_SESSION['error_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="ti ti-check-circle me-2"></i> <?php echo $_SESSION['success_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <form action="" method="POST" id="leadForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <!-- Lead Source Section -->
                    <div class="form-section">
                        <h5 class="section-title">
                            <i class="ti ti-source-code"></i> Where did you get this lead from?
                        </h5>
                        <div class="row g-3">
                            <div class="col-md-3 col-6">
                                <div class="source-badge" data-source="meta_ads">
                                    <i class="ti ti-brand-facebook fs-28"></i>
                                    <div>Meta Ads</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="source-badge" data-source="gmb">
                                    <i class="ti ti-brand-google fs-28"></i>
                                    <div>Google My Business</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="source-badge" data-source="google_data">
                                    <i class="ti ti-brand-google fs-28"></i>
                                    <div>Google Search</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="source-badge" data-source="open_source">
                                    <i class="ti ti-world-wide-web fs-28"></i>
                                    <div>Website/Referral</div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="lead_source" id="lead_source" required>
                    </div>

                    <!-- Assignment Section (Only for Grade Level 3 and above) -->
                    <?php if ($grade_level < 4): ?>
                        <div class="form-section">
                            <h5 class="section-title">
                                <i class="ti ti-user-plus"></i> Assignment Information
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label required-field">Assign Lead To</label>
                                    <select class="form-select" name="assigned_to" id="assigned_to" required>
                                        <option value="">Select Executive</option>
                                        <?php if ($grade_level == 3): ?>
                                            <option value="<?php echo $user_id; ?>">Myself (<?php echo htmlspecialchars($user_name); ?>)</option>
                                            <?php foreach ($team_members as $member): ?>
                                                <option value="<?php echo $member['user_id']; ?>"><?php echo htmlspecialchars($member['user_name']); ?></option>
                                            <?php endforeach; ?>
                                        <?php elseif ($grade_level < 3): ?>
                                            <?php foreach ($all_executives as $exec): ?>
                                                <option value="<?php echo $exec['user_id']; ?>"><?php echo htmlspecialchars($exec['user_name']); ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Service Type</label>
                                    <select class="form-select" name="service">
                                        <option value="">Select Service</option>
                                        <?php
                                        $services = getAllServices($conn);
                                        foreach ($services as $serv) {
                                        ?>
                                            <option value="<?= $serv['Service Name'] ?>"><?= $serv['Service Name'] ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="assigned_to" value="<?php echo $user_id; ?>">
                        <input type="hidden" name="service" value="">
                    <?php endif; ?>

                    <!-- Customer Information Section -->
                    <div class="form-section">
                        <h5 class="section-title">
                            <i class="ti ti-user"></i> Customer Details
                        </h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required-field">Mobile Number</label>
                                <input type="tel" class="form-control" id="customer_num" name="customer_num"
                                    pattern="[6-9][0-9]{9}" maxlength="10" placeholder="9876543210" required>
                                <div class="help-text">10-digit mobile number starting with 6,7,8, or 9</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label optional-field">Alternate Number</label>
                                <input type="tel" class="form-control" name="alt_number" pattern="[6-9][0-9]{9}"
                                    maxlength="10" placeholder="Optional">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required-field">Customer Name</label>
                                <input type="text" class="form-control" name="customer_name"
                                    placeholder="Enter full name" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label optional-field">Company Name</label>
                                <input type="text" class="form-control" name="cust_company"
                                    placeholder="Company name (if any)">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label optional-field">Email</label>
                                <input type="email" class="form-control" name="cust_mail"
                                    placeholder="customer@email.com">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label optional-field">Website</label>
                                <input type="url" class="form-control" name="website"
                                    placeholder="https://example.com">
                            </div>
                        </div>
                    </div>

                    <!-- Address Section (Collapsible) -->
                    <div class="form-section">
                        <h5 class="section-title" style="cursor: pointer;" onclick="toggleAddress()">
                            <i class="ti ti-map-pin"></i> Address Details
                            <i class="ti ti-chevron-down" id="addressToggleIcon"></i>
                        </h5>
                        <div id="addressSection" style="display: none;">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label optional-field">Address</label>
                                    <textarea class="form-control" name="cust_address" rows="2"
                                        placeholder="Street address, area, landmark"></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label optional-field">State</label>
                                    <select class="form-select" name="cust_state">
                                        <option value="">Select State</option>
                                        <?php
                                        $sql_stat = "SELECT * FROM states ORDER BY name";
                                        $res_state = mysqli_query($conn, $sql_stat);
                                        while ($row = mysqli_fetch_assoc($res_state)) {
                                        ?>
                                            <option value="<?= $row['name'] ?>"><?= $row['name'] ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label optional-field">Pincode</label>
                                    <input type="text" class="form-control" name="cust_pincode"
                                        pattern="[0-9]{6}" maxlength="6" placeholder="123456">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label optional-field">GST Number</label>
                                    <input type="text" class="form-control" name="gst"
                                        placeholder="22AAAAA0000A1Z">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Call Status Section -->
                    <div class="form-section">
                        <h5 class="section-title">
                            <i class="ti ti-phone-call"></i> Call Information
                        </h5>
                        <div class="row">
                            <div class="col-md-12">
                                <label class="form-label required-field">What happened during the call?</label>
                                <div class="row g-2">
                                    <!-- Call Not Picked Section -->
                                    <div class="col-md-3 col-6">
                                        <div class="status-card" data-status="call_not_picked">
                                            <i class="ti ti-phone-off text-danger"></i>
                                            <div class="status-title">Not Picked</div>
                                            <div class="status-desc">Customer didn't answer</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <div class="status-card" data-status="call_busy">
                                            <i class="ti ti-phone-off text-warning"></i>
                                            <div class="status-title">Busy</div>
                                            <div class="status-desc">Line was busy</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <div class="status-card" data-status="call_switched_off">
                                            <i class="ti ti-device-mobile text-secondary"></i>
                                            <div class="status-title">Switched Off</div>
                                            <div class="status-desc">Phone switched off</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <div class="status-card" data-status="call_wrong_number">
                                            <i class="ti ti-error-404 text-danger"></i>
                                            <div class="status-title">Wrong Number</div>
                                            <div class="status-desc">Incorrect number</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <div class="status-card" data-status="call_cut">
                                            <i class="ti ti-phone-x text-danger"></i>
                                            <div class="status-title">Call Cut</div>
                                            <div class="status-desc">Disconnected abruptly</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <div class="status-card" data-status="call_picked">
                                            <i class="ti ti-phone-check text-success"></i>
                                            <div class="status-title">Call Picked</div>
                                            <div class="status-desc">Customer answered</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <div class="status-card" data-status="positive">
                                            <i class="ti ti-thumb-up text-info"></i>
                                            <div class="status-title">Positive</div>
                                            <div class="status-desc">Interested in service</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <div class="status-card" data-status="committed">
                                            <i class="ti ti-check text-primary"></i>
                                            <div class="status-title">Committed</div>
                                            <div class="status-desc">Confirmed to buy</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <div class="status-card" data-status="not_interested">
                                            <i class="ti ti-thumb-down text-danger"></i>
                                            <div class="status-title">Not Interested</div>
                                            <div class="status-desc">Declined the offer</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <div class="status-card" data-status="follow_up">
                                            <i class="ti ti-clock text-warning"></i>
                                            <div class="status-title">Follow Up</div>
                                            <div class="status-desc">Call again later</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <div class="status-card" data-status="payment_done">
                                            <i class="ti ti-currency-rupee text-success"></i>
                                            <div class="status-title">Payment Done</div>
                                            <div class="status-desc">Payment received</div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="call_status" id="call_status" required>
                            </div>

                            <!-- Call Duration Field -->
                            <div class="col-md-6 mt-3">
                                <label class="form-label">Call Duration</label>
                                <div class="duration-input-group">
                                    <input type="text" class="form-control" name="call_duration" id="call_duration"
                                        placeholder="e.g., 2:30 or 150 seconds">
                                    <span class="input-group-text bg-light">minutes:seconds</span>
                                </div>
                                <div class="duration-preset">
                                    <span class="duration-preset-btn" data-duration="30">30 sec</span>
                                    <span class="duration-preset-btn" data-duration="60">1 min</span>
                                    <span class="duration-preset-btn" data-duration="120">2 min</span>
                                    <span class="duration-preset-btn" data-duration="180">3 min</span>
                                    <span class="duration-preset-btn" data-duration="300">5 min</span>
                                    <span class="duration-preset-btn" data-duration="600">10 min</span>
                                </div>
                                <div class="help-text">Enter call duration (e.g., 2:30 for 2 minutes 30 seconds)</div>
                            </div>

                            <div class="col-md-6" id="followUpDateDiv" style="display: none;">
                                <label class="form-label">Next Follow-up Date</label>
                                <input type="date" class="form-control" name="follow_up_date" id="follow_up_date">
                                <div class="help-text">When should we call again?</div>
                            </div>

                            <div class="col-12 mt-3">
                                <label class="form-label">Call Notes / Remarks</label>
                                <textarea class="form-control" name="call_notes" rows="3"
                                    placeholder="What did the customer say? Any special requests? Why not interested? etc..."></textarea>
                                <div class="help-text">Add important notes about this conversation</div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Section -->
                    <div class="form-section text-center">
                        <button type="submit" name="create_lead" class="btn btn-primary btn-submit">
                            <i class="ti ti-device-floppy me-2"></i> Save Lead
                        </button>
                        <button type="reset" class="btn btn-secondary btn-submit ms-2">
                            <i class="ti ti-refresh me-2"></i> Clear Form
                        </button>
                    </div>
                </form>
            </div>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <?php include_once "includes/footer-link.php"; ?>

    <script>
        $(document).ready(function() {
            // Lead Source Selection
            $('.source-badge').click(function() {
                $('.source-badge').removeClass('selected');
                $(this).addClass('selected');
                $('#lead_source').val($(this).data('source'));
            });

            // Call Status Selection
            $('.status-card').click(function() {
                $('.status-card').removeClass('selected');
                $(this).addClass('selected');
                var status = $(this).data('status');
                $('#call_status').val(status);

                // Show/hide follow up date based on status
                if (status === 'follow_up') {
                    $('#followUpDateDiv').slideDown();
                } else {
                    $('#followUpDateDiv').slideUp();
                    $('#follow_up_date').val('');
                }

                // Alert for payment done
                if (status === 'payment_done') {
                    alert('Payment received! This lead will be marked as committed.');
                }
            });

            // Call Duration Preset Buttons
            $('.duration-preset-btn').click(function() {
                var duration = $(this).data('duration');
                var minutes = Math.floor(duration / 60);
                var seconds = duration % 60;

                if (minutes > 0) {
                    $('#call_duration').val(minutes + ':' + (seconds < 10 ? '0' + seconds : seconds));
                } else {
                    $('#call_duration').val(seconds + ' seconds');
                }
            });

            // Format call duration as user types
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

            // Form validation
            $('#leadForm').on('submit', function(e) {
                let isValid = true;

                // Validate lead source
                if (!$('#lead_source').val()) {
                    alert('Please select where you got this lead from');
                    isValid = false;
                }

                // Validate call status
                if (!$('#call_status').val()) {
                    alert('Please select what happened during the call');
                    isValid = false;
                }

                // Validate mobile number
                const mobile = $('#customer_num').val();
                const mobileRegex = /^[6-9][0-9]{9}$/;
                if (!mobileRegex.test(mobile)) {
                    $('#customer_num').addClass('is-invalid');
                    alert('Please enter a valid 10-digit mobile number');
                    isValid = false;
                } else {
                    $('#customer_num').removeClass('is-invalid').addClass('is-valid');
                }

                // Validate customer name
                const customerName = $('input[name="customer_name"]').val();
                if (!customerName.trim()) {
                    $('input[name="customer_name"]').addClass('is-invalid');
                    alert('Please enter customer name');
                    isValid = false;
                } else {
                    $('input[name="customer_name"]').removeClass('is-invalid').addClass('is-valid');
                }

                if (!isValid) {
                    e.preventDefault();
                }
            });

            // Real-time validation
            $('#customer_num').on('input', function() {
                let value = this.value.replace(/\D/g, '');
                if (value.length > 10) value = value.slice(0, 10);
                this.value = value;

                const regex = /^[6-9][0-9]{9}$/;
                if (regex.test(value)) {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                } else {
                    $(this).removeClass('is-valid').addClass('is-invalid');
                }
            });

            $('input[name="customer_name"]').on('input', function() {
                if ($(this).val().trim()) {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                } else {
                    $(this).removeClass('is-valid').addClass('is-invalid');
                }
            });
        });

        function toggleAddress() {
            const addressSection = document.getElementById('addressSection');
            const icon = document.getElementById('addressToggleIcon');
            if (addressSection.style.display === 'none') {
                addressSection.style.display = 'block';
                icon.classList.remove('ti-chevron-down');
                icon.classList.add('ti-chevron-up');
            } else {
                addressSection.style.display = 'none';
                icon.classList.remove('ti-chevron-up');
                icon.classList.add('ti-chevron-down');
            }
        }

        // Auto-format inputs
        document.querySelector('input[name="cust_pincode"]')?.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 6) value = value.slice(0, 6);
            this.value = value;
        });

        document.querySelector('input[name="gst"]')?.addEventListener('input', function(e) {
            this.value = this.value.toUpperCase();
        });

        document.querySelector('input[name="pan"]')?.addEventListener('input', function(e) {
            this.value = this.value.toUpperCase();
        });
    </script>
</body>

</html>