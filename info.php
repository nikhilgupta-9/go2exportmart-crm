<?php
session_start();
require_once 'partials/_dbconnect.php';

// 1. Session & Security Check
if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Create uploads directory if not exists
$upload_dir = 'assets/uploads/profiles/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// 2. Data Fetching (Using Prepared Statements for Security)
$stmt = $conn->prepare("SELECT * FROM employees WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$info_row = $stmt->get_result()->fetch_assoc();

if (!$info_row) {
    die("User data not found.");
}

// 3. Logic: Calculations
$dob = new DateTime($info_row['user_dob']);
$doj = new DateTime($info_row['user_doj']);
$today = new DateTime();
$age = $dob->diff($today);
$tenure = $doj->diff($today);

// Check if today is birthday
$is_birthday = ($dob->format('m-d') === $today->format('m-d'));

// 4. Grade Name Mapping
$grade_names = [
    1 => 'Executive',
    2 => 'Senior Executive',
    3 => 'Team Lead',
    4 => 'Manager',
    5 => 'Senior Manager'
];
$current_grade = $grade_names[$info_row['grade_level']] ?? 'Employee';
$uid = $info_row['user_id'];

// Get profile image path
$profile_image = !empty($info_row['user_img']) ? $info_row['user_img'] : '';

// Handle profile image upload
$image_upload_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['profile_image']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $file_size = $_FILES['profile_image']['size'];
    
    // Validate file
    if (!in_array($ext, $allowed)) {
        $image_upload_message = "Only JPG, JPEG, PNG & GIF files are allowed.";
    } elseif ($file_size > 800000) { // 800KB max
        $image_upload_message = "File size must be less than 800KB.";
    } else {
        // Generate unique filename
        $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
        $destination = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $destination)) {
            // Delete old image if exists
            if (!empty($profile_image) && file_exists($upload_dir . $profile_image)) {
                unlink($upload_dir . $profile_image);
            }
            
            // Update database with new image filename
            $update_img_sql = "UPDATE employees SET user_img = '$new_filename' WHERE user_id = '$user_id'";
            if (mysqli_query($conn, $update_img_sql)) {
                $profile_image = $new_filename;
                $image_upload_message = "Profile picture updated successfully!";
                // Refresh data
                $stmt = $conn->prepare("SELECT * FROM employees WHERE user_id = ?");
                $stmt->bind_param("s", $user_id);
                $stmt->execute();
                $info_row = $stmt->get_result()->fetch_assoc();
            } else {
                $image_upload_message = "Database error: " . mysqli_error($conn);
            }
        } else {
            $image_upload_message = "Failed to upload image. Please try again.";
        }
    }
}

// Handle remove profile image
if (isset($_POST['remove_image'])) {
    if (!empty($profile_image) && file_exists($upload_dir . $profile_image)) {
        unlink($upload_dir . $profile_image);
    }
    $update_img_sql = "UPDATE employees SET user_img = NULL WHERE user_id = '$user_id'";
    if (mysqli_query($conn, $update_img_sql)) {
        $profile_image = '';
        $image_upload_message = "Profile picture removed successfully!";
        // Refresh data
        $stmt = $conn->prepare("SELECT * FROM employees WHERE user_id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $info_row = $stmt->get_result()->fetch_assoc();
    }
}

// Handle form submission for profile update
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    
    $update_sql = "UPDATE employees SET 
                   user_name = CONCAT('$first_name', ' ', '$last_name'),
                   user_num = '$phone',
                   user_mail = '$email',
                   user_address = '$address'
                   WHERE user_id = '$user_id'";
    
    if (mysqli_query($conn, $update_sql)) {
        $success_message = "Profile updated successfully!";
        // Refresh data
        $stmt = $conn->prepare("SELECT * FROM employees WHERE user_id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $info_row = $stmt->get_result()->fetch_assoc();
    } else {
        $error_message = "Error updating profile: " . mysqli_error($conn);
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    if ($current_password !== $info_row['user_password']) {
        $password_error = "Current password is incorrect!";
    } elseif (strlen($new_password) < 8) {
        $password_error = "New password must be at least 8 characters long!";
    } elseif ($new_password !== $confirm_password) {
        $password_error = "New password and confirm password do not match!";
    } else {
        $update_pass_sql = "UPDATE employees SET user_password = '$new_password' WHERE user_id = '$user_id'";
        if (mysqli_query($conn, $update_pass_sql)) {
            $password_success = "Password changed successfully!";
        } else {
            $password_error = "Error changing password: " . mysqli_error($conn);
        }
    }
}

// Get profile image URL
$profile_image_url = !empty($profile_image) ? $upload_dir . $profile_image : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Profile Settings | GO2EXPORT MART</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once "includes/link.php"; ?>
    
    <style>
        .settings-sidebar {
            position: sticky;
            top: 20px;
        }
        
        .settings-sidebar .list-group-item {
            border: none;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 4px;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .settings-sidebar .list-group-item:hover {
            background: #f8f9fa;
        }
        
        .profile-upload-img {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            overflow: hidden;
        }
        
        .profile-upload-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-remove {
            position: absolute;
            top: -8px;
            right: -8px;
            width: 24px;
            height: 24px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }
        
        .profile-remove:hover {
            background: #c82333;
            transform: scale(1.1);
        }
        
        .info-label {
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .progress-sm {
            height: 6px;
        }
        
        .profile-image-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            .settings-sidebar {
                margin-bottom: 20px;
                position: relative;
            }
        }
        
        .custom-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideInRight 0.3s ease-out;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <?php include_once "includes/header.php"; ?>
        <?php include_once "includes/sidebar.php"; ?>

        <div class="page-wrapper">
            <div class="content">
                <!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-1">Profile Settings</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Profile Settings</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
                        <button onclick="window.location.reload()" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" title="Refresh">
                            <i class="ti ti-refresh"></i>
                        </button>
                        <button onclick="window.print()" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" title="Print Profile">
                            <i class="ti ti-printer"></i>
                        </button>
                    </div>
                </div>

                <?php if ($is_birthday): ?>
                    <div class="alert alert-warning mb-4">
                        <div class="d-flex align-items-center">
                            <i class="ti ti-cake fs-24 me-3"></i>
                            <div>
                                <h5 class="mb-0">Happy Birthday, <?php echo htmlspecialchars($info_row['user_name']); ?>! 🎂</h5>
                                <p class="mb-0">The whole GO2EXPORT MART team wishes you a fantastic day ahead!</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Left Sidebar - Settings Navigation -->
                    <div class="col-xl-3 col-lg-12">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body p-0">
                                <div class="settings-sidebar p-3">
                                    <div class="text-center mb-4">
                                        <?php if (!empty($profile_image) && file_exists($upload_dir . $profile_image)): ?>
                                            <img src="<?php echo $upload_dir . $profile_image; ?>" alt="Profile" class="profile-image-preview mb-3">
                                        <?php else: ?>
                                            <?php
                                            $initials = urlencode($info_row['user_name']);
                                            $avatar_url = "https://ui-avatars.com/api/?name=$initials&background=667eea&color=fff&size=100&bold=true";
                                            ?>
                                            <img src="<?php echo $avatar_url; ?>" alt="Avatar" class="profile-image-preview mb-3">
                                        <?php endif; ?>
                                        <h5 class="mb-1 fw-bold"><?php echo htmlspecialchars($info_row['user_name']); ?></h5>
                                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($info_row['user_role']); ?></p>
                                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($info_row['department']); ?></p>
                                    </div>
                                    <div class="list-group list-group-flush">
                                        <a class="list-group-item list-group-item-action active" data-section="profile">
                                            <i class="ti ti-user-circle me-2"></i> Profile
                                        </a>
                                        <a class="list-group-item list-group-item-action" data-section="security">
                                            <i class="ti ti-lock me-2"></i> Security
                                        </a>
                                        <a class="list-group-item list-group-item-action" data-section="employment">
                                            <i class="ti ti-briefcase me-2"></i> Employment
                                        </a>
                                        <a class="list-group-item list-group-item-action" data-section="performance">
                                            <i class="ti ti-chart-bar me-2"></i> Performance
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Content - Settings Forms -->
                    <div class="col-xl-9 col-lg-12">
                        <!-- Profile Section -->
                        <div id="profile" class="settings-section">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-body">
                                    <div class="border-bottom mb-4 pb-2">
                                        <h5 class="mb-0 fs-17 fw-bold">Profile Information</h5>
                                        <p class="text-muted mb-0 small">Update your personal information</p>
                                    </div>
                                    
                                    <!-- Profile Image Upload -->
                                    <form action="" method="POST" enctype="multipart/form-data" class="mb-4">
                                        <div class="mb-4">
                                            <label class="form-label fw-semibold">Profile Picture</label>
                                            <div class="profile-upload d-flex align-items-center flex-wrap gap-3">
                                                <div class="profile-upload-img border border-dashed rounded position-relative flex-shrink-0">
                                                    <?php if (!empty($profile_image) && file_exists($upload_dir . $profile_image)): ?>
                                                        <img id="profilePreview" src="<?php echo $upload_dir . $profile_image; ?>" alt="Profile">
                                                    <?php else: ?>
                                                        <img id="profilePreview" src="https://ui-avatars.com/api/?name=<?php echo urlencode($info_row['user_name']); ?>&background=667eea&color=fff&size=120&bold=true" alt="Profile">
                                                    <?php endif; ?>
                                                    <button type="button" class="profile-remove" <?php echo empty($profile_image) ? 'style="display: none;"' : ''; ?>>
                                                        <i class="ti ti-x"></i>
                                                    </button>
                                                </div>
                                                <div class="profile-upload-content">
                                                    <label class="d-inline-flex align-items-center position-relative btn btn-primary btn-sm mb-2">
                                                        <i class="ti ti-cloud-upload me-1"></i> Upload Photo
                                                        <input type="file" name="profile_image" id="profileImage" class="position-absolute w-100 h-100 opacity-0 top-0 end-0" accept="image/jpeg,image/png,image/gif,image/jpg">
                                                    </label>
                                                    <p class="mb-0 small text-muted">JPG, GIF or PNG. Max size of 800KB</p>
                                                    <?php if ($image_upload_message): ?>
                                                        <div class="alert alert-<?php echo strpos($image_upload_message, 'success') !== false ? 'success' : 'danger'; ?> alert-sm mt-2 mb-0 py-1">
                                                            <?php echo $image_upload_message; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Remove Image Form -->
                                        <?php if (!empty($profile_image)): ?>
                                        <div class="mb-3">
                                            <button type="submit" name="remove_image" class="btn btn-sm btn-outline-danger">
                                                <i class="ti ti-trash me-1"></i> Remove Photo
                                            </button>
                                        </div>
                                        <?php endif; ?>
                                    </form>
                                    
                                    <hr>
                                    
                                    <!-- Profile Information Form -->
                                    <form action="" method="POST">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                                <?php
                                                $name_parts = explode(' ', $info_row['user_name'], 2);
                                                $first_name = $name_parts[0];
                                                $last_name = $name_parts[1] ?? '';
                                                ?>
                                                <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($first_name); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Last Name</label>
                                                <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($last_name); ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                                <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($info_row['user_num']); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($info_row['user_mail']); ?>" required>
                                            </div>
                                            <div class="col-12 mb-3">
                                                <label class="form-label">Address</label>
                                                <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($info_row['user_address']); ?></textarea>
                                            </div>
                                        </div>
                                        
                                        <?php if ($success_message): ?>
                                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                                <i class="ti ti-check-circle me-2"></i> <?php echo $success_message; ?>
                                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($error_message): ?>
                                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                <i class="ti ti-alert-circle me-2"></i> <?php echo $error_message; ?>
                                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex align-items-center justify-content-end gap-2 mt-3">
                                            <button type="reset" class="btn btn-sm btn-light">Cancel</button>
                                            <button type="submit" name="update_profile" class="btn btn-sm btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Security Section -->
                        <div id="security" class="settings-section" style="display: none;">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-body">
                                    <div class="border-bottom mb-4 pb-2">
                                        <h5 class="mb-0 fs-17 fw-bold">Security Settings</h5>
                                        <p class="text-muted mb-0 small">Update your password and security preferences</p>
                                    </div>
                                    
                                    <form action="" method="POST">
                                        <div class="mb-3">
                                            <label class="form-label">Current Password</label>
                                            <input type="password" name="current_password" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">New Password</label>
                                            <input type="password" name="new_password" class="form-control" required>
                                            <small class="text-muted">Password must be at least 8 characters long</small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Confirm New Password</label>
                                            <input type="password" name="confirm_password" class="form-control" required>
                                        </div>
                                        
                                        <?php if (isset($password_success)): ?>
                                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                                <i class="ti ti-check-circle me-2"></i> <?php echo $password_success; ?>
                                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($password_error)): ?>
                                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                <i class="ti ti-alert-circle me-2"></i> <?php echo $password_error; ?>
                                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="alert alert-info">
                                            <i class="ti ti-info-circle me-2"></i>
                                            For security reasons, we recommend changing your password every 90 days.
                                        </div>
                                        
                                        <div class="d-flex align-items-center justify-content-end gap-2">
                                            <button type="reset" class="btn btn-sm btn-light">Cancel</button>
                                            <button type="submit" name="change_password" class="btn btn-sm btn-primary">Change Password</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Employment Section -->
                        <div id="employment" class="settings-section" style="display: none;">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-body">
                                    <div class="border-bottom mb-4 pb-2">
                                        <h5 class="mb-0 fs-17 fw-bold">Employment Information</h5>
                                        <p class="text-muted mb-0 small">Your employment details and work information</p>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="info-label">Employee ID</div>
                                            <div class="info-value">#<?php echo htmlspecialchars($info_row['user_id']); ?></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="info-label">Date of Joining</div>
                                            <div class="info-value"><?php echo $doj->format('d M, Y'); ?></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="info-label">Total Tenure</div>
                                            <div class="info-value"><?php echo $tenure->y; ?> Years, <?php echo $tenure->m; ?> Months, <?php echo $tenure->d; ?> Days</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="info-label">Designation</div>
                                            <div class="info-value"><?php echo htmlspecialchars($info_row['user_role']); ?></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="info-label">Department</div>
                                            <div class="info-value"><?php echo htmlspecialchars($info_row['department']); ?></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="info-label">Grade Level</div>
                                            <div class="info-value"><?php echo $current_grade; ?> (Level <?php echo $info_row['grade_level']; ?>)</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="info-label">Reporting Authority</div>
                                            <div class="info-value"><?php echo htmlspecialchars($info_row['Reporting']); ?></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="info-label">HR Contact</div>
                                            <div class="info-value"><?php echo htmlspecialchars($info_row['line_hr']); ?></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="info-label">Date of Birth</div>
                                            <div class="info-value"><?php echo $dob->format('d M, Y'); ?> (<?php echo $age->y; ?> Years)</div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3 d-flex gap-2 flex-wrap">
                                        <a href="id_go2export.php?uid=<?php echo urlencode($uid); ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                                            <i class="ti ti-id-badge me-1"></i> GO2EXPORT ID Card
                                        </a>
                                        <a href="id_dwt.php?uid=<?php echo urlencode($uid); ?>" class="btn btn-outline-info btn-sm" target="_blank">
                                            <i class="ti ti-id-badge me-1"></i> DWT ID Card
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Performance Section -->
                        <div id="performance" class="settings-section" style="display: none;">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-body">
                                    <div class="border-bottom mb-4 pb-2">
                                        <h5 class="mb-0 fs-17 fw-bold">Performance Overview</h5>
                                        <p class="text-muted mb-0 small">Track your sales performance and targets</p>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-semibold">Monthly Sales Target</span>
                                            <span class="text-muted small">Current Month Progress</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span>Target: ₹<?php echo number_format($info_row['user_target']); ?></span>
                                            <span class="fw-bold text-success">75% Achieved</span>
                                        </div>
                                        <div class="progress progress-sm">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: 75%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-light border">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="ti ti-award fs-24 text-warning"></i>
                                            <div>
                                                <strong>Performance Insight</strong>
                                                <p class="mb-0 small text-muted">Keep up the great work! Consistent follow-ups lead to better conversions.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>
    
    <?php include_once "includes/footer-link.php"; ?>
    
    <script>
        // Section switching functionality
        document.querySelectorAll('[data-section]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Update active state in sidebar
                document.querySelectorAll('[data-section]').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                
                // Show corresponding section
                const sectionId = this.getAttribute('data-section');
                document.querySelectorAll('.settings-section').forEach(section => {
                    section.style.display = 'none';
                });
                document.getElementById(sectionId).style.display = 'block';
            });
        });
        
        // Profile image preview
        document.getElementById('profileImage')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Only JPG, JPEG, PNG & GIF files are allowed.');
                    this.value = '';
                    return;
                }
                
                // Validate file size (800KB)
                if (file.size > 800000) {
                    alert('File size must be less than 800KB.');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                    document.querySelector('.profile-remove').style.display = 'flex';
                };
                reader.readAsDataURL(file);
                
                // Auto-submit the form
                this.closest('form').submit();
            }
        });
        
        // Remove profile image
        document.querySelector('.profile-remove')?.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to remove your profile picture?')) {
                // Create a form to submit remove request
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'remove_image';
                input.value = '1';
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        });
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>