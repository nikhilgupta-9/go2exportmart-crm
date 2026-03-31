<?php
session_start();
require_once 'partials/_dbconnect.php';
if(!isset($_SESSION['user_id'])){
    header('location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$info_ftch_sql = "SELECT * FROM `employees` WHERE `user_id` = '$user_id'";
$result_ftch_sql = mysqli_query($conn, $info_ftch_sql);
$info_row = mysqli_fetch_assoc($result_ftch_sql);

$success_message = '';
$error_message = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name       = mysqli_real_escape_string($conn, $_POST['name']);
    $dob        = mysqli_real_escape_string($conn, $_POST['dob']);
    $doj        = mysqli_real_escape_string($conn, $_POST['doj']);
    $phone      = mysqli_real_escape_string($conn, $_POST['phone']);
    $email      = mysqli_real_escape_string($conn, $_POST['email']);
    $address    = mysqli_real_escape_string($conn, $_POST['address']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $role       = mysqli_real_escape_string($conn, $_POST['role']);
    $reporting  = mysqli_real_escape_string($conn, $_POST['reporting']);
    $hr         = mysqli_real_escape_string($conn, $_POST['hr']);

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Invalid email address format.';
    } elseif(!preg_match('/^[0-9]{10}$/', $phone)) {
        $error_message = 'Phone number must be 10 digits.';
    } else {
        $update_sql = "UPDATE employees SET 
                        user_name    = '$name',
                        user_dob     = '$dob',
                        user_doj     = '$doj',
                        user_num     = '$phone',
                        user_mail    = '$email',
                        user_address = '$address',
                        department   = '$department',
                        user_role    = '$role',
                        Reporting    = '$reporting',
                        line_hr      = '$hr'
                        WHERE user_id = '$user_id'";

        if(mysqli_query($conn, $update_sql)) {
            $success_message = 'Profile updated successfully!';
            $result_ftch_sql = mysqli_query($conn, $info_ftch_sql);
            $info_row = mysqli_fetch_assoc($result_ftch_sql);
        } else {
            $error_message = 'Error updating profile: ' . mysqli_error($conn);
        }
    }
}

$dob_obj  = new DateTime($info_row['user_dob']);
$today    = new DateTime();
$age      = $dob_obj->diff($today);

$departments     = ['Sales', 'Marketing', 'Operations', 'HR', 'IT', 'Finance', 'Customer Support'];
$role_options    = [
    1 => ['Administrator', 'CEO', 'Director'],
    2 => ['Sales Manager', 'Marketing Manager', 'Operations Manager'],
    3 => ['Team Lead', 'Senior Executive'],
    4 => ['Sales Executive', 'Marketing Executive'],
    5 => ['Associate', 'Trainee']
];
$grade_names     = [1=>'Executive', 2=>'Senior Executive', 3=>'Team Lead', 4=>'Manager', 5=>'Senior Manager'];
$current_grade   = $info_row['grade_level'];
$available_roles = $role_options[$current_grade] ?? ['Sales Executive', 'Associate'];

$initials = '';
foreach(explode(' ', $info_row['user_name']) as $part) { $initials .= strtoupper(substr($part,0,1)); }
$initials   = substr($initials, 0, 2);
$avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($initials) . "&background=4f46e5&color=fff&size=120&bold=true&rounded=true&length=2";

$is_birthday = date('m-d') === date('m-d', strtotime($info_row['user_dob']));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Edit Profile | <?php echo htmlspecialchars($info_row['user_name']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once "includes/link.php"; ?>
</head>

<body>

<!-- Begin Wrapper -->
<div class="main-wrapper">

    <?php include_once "includes/header.php"; ?>

    <!-- Search Modal -->
    <div class="modal fade" id="searchModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-transparent">
                <div class="card shadow-none mb-0">
                    <div class="px-3 py-2 d-flex flex-row align-items-center" id="search-top">
                        <i class="ti ti-search fs-22"></i>
                        <input type="search" class="form-control border-0" placeholder="Search">
                        <button type="button" class="btn p-0" data-bs-dismiss="modal" aria-label="Close">
                            <i class="ti ti-x fs-22"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include_once "includes/sidebar.php"; ?>

    <div class="page-wrapper">
        <div class="content pb-0">

            <!-- ── Birthday Banner ── -->
            <?php if($is_birthday): ?>
            <div class="alert alert-dismissible fade show d-flex align-items-center gap-3 mb-4"
                 style="background:linear-gradient(135deg,#ffecd2,#fcb69f);border:none;border-radius:8px;"
                 role="alert">
                <i class="ti ti-cake fs-24 text-danger flex-shrink-0"></i>
                <div>
                    <strong>🎉 HAPPY BIRTHDAY!</strong>
                    Dear <strong><?php echo htmlspecialchars($info_row['user_name']); ?></strong>,
                    wishing you a fantastic birthday filled with joy and success!
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- ── Page Header (same style as dashboard.php) ── -->
            <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                <div>
                    <h4 class="mb-1">Edit Profile</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="dashboard.php"><i class="ti ti-smart-home"></i></a>
                            </li>
                            <li class="breadcrumb-item"><a href="info.php">My Profile</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Edit Profile</li>
                        </ol>
                    </nav>
                </div>
                <div class="gap-2 d-flex align-items-center flex-wrap">
                    <a href="info.php" class="btn btn-outline-light shadow d-flex align-items-center gap-1">
                        <i class="ti ti-arrow-left fs-14"></i>Back to Profile
                    </a>
                    <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow"
                       data-bs-toggle="tooltip" data-bs-placement="top"
                       aria-label="Refresh" title="Refresh"
                       onclick="location.reload();">
                        <i class="ti ti-refresh"></i>
                    </a>
                    <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow"
                       data-bs-toggle="tooltip" data-bs-placement="top"
                       aria-label="Collapse" title="Collapse"
                       id="collapse-header">
                        <i class="ti ti-transition-top"></i>
                    </a>
                </div>
            </div>
            <!-- End Page Header -->

            <!-- ── Alerts ── -->
            <?php if($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                <i class="ti ti-circle-check fs-18 flex-shrink-0"></i>
                <div><?php echo $success_message; ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                <i class="ti ti-alert-circle fs-18 flex-shrink-0"></i>
                <div><?php echo $error_message; ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- ── Main Row ── -->
            <div class="row">

                <!-- ════ LEFT: Form ════ -->
                <div class="col-xl-8 col-lg-7">
                    <form method="POST" action="" id="profileForm">

                        <!-- Personal Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title d-flex align-items-center gap-2 mb-0">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded p-1 bg-soft-primary" style="width:30px;height:30px;">
                                        <i class="ti ti-user text-primary"></i>
                                    </span>
                                    Personal Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="name"
                                               value="<?php echo htmlspecialchars($info_row['user_name']); ?>"
                                               placeholder="Enter full name" required>
                                        <div class="form-text">As per official records</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ti ti-calendar"></i></span>
                                            <input type="date" class="form-control" name="dob"
                                                   value="<?php echo $info_row['user_dob']; ?>" required>
                                        </div>
                                        <div class="form-text">Age: <strong><?php echo $age->y; ?> years</strong></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Date of Joining <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ti ti-calendar-check"></i></span>
                                            <input type="date" class="form-control" name="doj"
                                                   value="<?php echo $info_row['user_doj']; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Calculated Age</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ti ti-clock"></i></span>
                                            <input type="text" class="form-control"
                                                   value="<?php echo $age->y; ?> years old" disabled>
                                        </div>
                                        <div class="form-text">Auto-calculated from DOB</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title d-flex align-items-center gap-2 mb-0">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded p-1 bg-soft-success" style="width:30px;height:30px;">
                                        <i class="ti ti-address-card text-success"></i>
                                    </span>
                                    Contact Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ti ti-phone"></i></span>
                                            <input type="tel" class="form-control" name="phone"
                                                   value="<?php echo htmlspecialchars($info_row['user_num']); ?>"
                                                   pattern="[0-9]{10}" maxlength="10"
                                                   placeholder="10-digit number" required>
                                        </div>
                                        <div class="form-text">10-digit mobile number only</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ti ti-mail"></i></span>
                                            <input type="email" class="form-control" name="email"
                                                   value="<?php echo htmlspecialchars($info_row['user_mail']); ?>"
                                                   placeholder="official@email.com" required>
                                        </div>
                                        <div class="form-text">Used for official communication</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text align-items-start pt-2">
                                                <i class="ti ti-map-pin"></i>
                                            </span>
                                            <textarea class="form-control" name="address" rows="3"
                                                      placeholder="Enter your full address"><?php echo htmlspecialchars($info_row['user_address']); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Professional Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title d-flex align-items-center gap-2 mb-0">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded p-1 bg-soft-warning" style="width:30px;height:30px;">
                                        <i class="ti ti-briefcase text-warning"></i>
                                    </span>
                                    Professional Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Department <span class="text-danger">*</span></label>
                                        <select class="form-select" name="department" required>
                                            <?php foreach($departments as $dept): ?>
                                            <option value="<?php echo $dept; ?>"
                                                <?php echo ($info_row['department'] == $dept) ? 'selected' : ''; ?>>
                                                <?php echo $dept; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Designation <span class="text-danger">*</span></label>
                                        <select class="form-select" name="role" required>
                                            <?php foreach($available_roles as $role_opt): ?>
                                            <option value="<?php echo $role_opt; ?>"
                                                <?php echo ($info_row['user_role'] == $role_opt) ? 'selected' : ''; ?>>
                                                <?php echo $role_opt; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Grade Level</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ti ti-award"></i></span>
                                            <input type="text" class="form-control"
                                                   value="<?php echo $grade_names[$info_row['grade_level']] ?? $info_row['grade_level']; ?>"
                                                   disabled>
                                        </div>
                                        <div class="form-text"><i class="ti ti-lock me-1"></i>Assigned by HR — not editable</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Monthly Target</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₹</span>
                                            <input type="text" class="form-control"
                                                   value="<?php echo number_format($info_row['user_target']); ?>"
                                                   disabled>
                                        </div>
                                        <div class="form-text"><i class="ti ti-lock me-1"></i>Set by management — not editable</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reporting Structure -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title d-flex align-items-center gap-2 mb-0">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded p-1 bg-soft-info" style="width:30px;height:30px;">
                                        <i class="ti ti-sitemap text-info"></i>
                                    </span>
                                    Reporting Structure
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Reports To</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ti ti-user-check"></i></span>
                                            <input type="text" class="form-control" name="reporting"
                                                   value="<?php echo htmlspecialchars($info_row['Reporting']); ?>"
                                                   placeholder="Manager's name">
                                        </div>
                                        <div class="form-text">Your direct reporting manager</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">HR Manager</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ti ti-user-star"></i></span>
                                            <input type="text" class="form-control" name="hr"
                                                   value="<?php echo htmlspecialchars($info_row['line_hr']); ?>"
                                                   placeholder="HR contact name">
                                        </div>
                                        <div class="form-text">Your assigned HR contact</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-5">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary d-flex align-items-center gap-1">
                                    <i class="ti ti-device-floppy"></i>Save Changes
                                </button>
                                <a href="info.php" class="btn btn-outline-light shadow d-flex align-items-center gap-1">
                                    <i class="ti ti-x"></i>Cancel
                                </a>
                            </div>
                            <button type="reset" id="resetBtn"
                                    class="btn btn-outline-danger d-flex align-items-center gap-1">
                                <i class="ti ti-refresh"></i>Reset Form
                            </button>
                        </div>

                    </form>
                </div>
                <!-- /Left Column -->

                <!-- ════ RIGHT: Profile Preview + Tips ════ -->
                <div class="col-xl-4 col-lg-5">

                    <!-- Profile Preview -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title d-flex align-items-center gap-2 mb-0">
                                <i class="ti ti-id-badge text-primary fs-18"></i>Profile Preview
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="position-relative d-inline-block mb-3">
                                <img src="<?php echo $avatar_url; ?>" alt="Avatar"
                                     class="rounded-circle"
                                     style="width:90px;height:90px;border:3px solid #4f46e5;">
                                <span class="position-absolute bottom-0 end-0 bg-success rounded-circle border border-2 border-white"
                                      style="width:14px;height:14px;display:block;"></span>
                            </div>
                            <h5 class="fw-semibold mb-1"><?php echo htmlspecialchars($info_row['user_name']); ?></h5>
                            <p class="text-muted fs-14 mb-3"><?php echo htmlspecialchars($info_row['user_role']); ?></p>

                            <div class="d-flex justify-content-center gap-2 mb-4">
                                <span class="badge badge-soft-primary fs-12">
                                    <i class="ti ti-building me-1"></i>
                                    <?php echo htmlspecialchars($info_row['department']); ?>
                                </span>
                                <span class="badge badge-soft-success fs-12">
                                    <i class="ti ti-circle-filled me-1" style="font-size:8px;"></i>Active
                                </span>
                            </div>

                            <div class="text-start border-top pt-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded bg-soft-primary flex-shrink-0" style="width:28px;height:28px;">
                                        <i class="ti ti-mail text-primary fs-14"></i>
                                    </span>
                                    <div class="overflow-hidden">
                                        <small class="text-muted d-block lh-1">Email</small>
                                        <span class="fs-13 text-truncate d-block">
                                            <?php echo htmlspecialchars($info_row['user_mail']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded bg-soft-success flex-shrink-0" style="width:28px;height:28px;">
                                        <i class="ti ti-phone text-success fs-14"></i>
                                    </span>
                                    <div>
                                        <small class="text-muted d-block lh-1">Phone</small>
                                        <span class="fs-13"><?php echo htmlspecialchars($info_row['user_num']); ?></span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded bg-soft-warning flex-shrink-0" style="width:28px;height:28px;">
                                        <i class="ti ti-calendar text-warning fs-14"></i>
                                    </span>
                                    <div>
                                        <small class="text-muted d-block lh-1">Joined</small>
                                        <span class="fs-13"><?php echo date('d M Y', strtotime($info_row['user_doj'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="change-password.php"
                               class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center gap-2">
                                <i class="ti ti-key"></i>Change Password
                            </a>
                        </div>
                    </div>

                    <!-- Tips -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title d-flex align-items-center gap-2 mb-0">
                                <i class="ti ti-bulb text-warning fs-18"></i>Tips
                            </h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="d-flex align-items-start gap-2 mb-3">
                                    <i class="ti ti-circle-check text-success mt-1 flex-shrink-0"></i>
                                    <small>Keep your contact information up to date at all times</small>
                                </li>
                                <li class="d-flex align-items-start gap-2 mb-3">
                                    <i class="ti ti-circle-check text-success mt-1 flex-shrink-0"></i>
                                    <small>Always use your official email for communications</small>
                                </li>
                                <li class="d-flex align-items-start gap-2 mb-3">
                                    <i class="ti ti-circle-check text-success mt-1 flex-shrink-0"></i>
                                    <small>Update your reporting manager if there has been any change</small>
                                </li>
                                <li class="d-flex align-items-start gap-2 mb-3">
                                    <i class="ti ti-shield text-primary mt-1 flex-shrink-0"></i>
                                    <small>Some fields are read-only and managed by HR or management</small>
                                </li>
                                <li class="d-flex align-items-start gap-2">
                                    <i class="ti ti-lock text-warning mt-1 flex-shrink-0"></i>
                                    <small>Grade level and monthly target are set by your management team</small>
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>
                <!-- /Right Column -->

            </div><!-- /row -->
        </div><!-- /content -->

        <?php include_once "includes/footer.php"; ?>
    </div><!-- /page-wrapper -->

</div><!-- /main-wrapper -->

<?php include_once "includes/footer-link.php"; ?>

<script>
// Phone: digits only, max 10
document.getElementById('profileForm')?.addEventListener('input', function(e) {
    if(e.target.name === 'phone')
        e.target.value = e.target.value.replace(/[^0-9]/g, '').slice(0, 10);
    if(e.target.name === 'name')
        e.target.value = e.target.value.replace(/\b\w/g, l => l.toUpperCase());
});

// Warn on unsaved changes
let formChanged = false;
document.querySelectorAll('#profileForm input:not([disabled]), #profileForm select, #profileForm textarea')
    .forEach(f => f.addEventListener('change', () => { formChanged = true; }));

window.addEventListener('beforeunload', e => {
    if(formChanged) { e.preventDefault(); e.returnValue = ''; }
});

document.getElementById('resetBtn')?.addEventListener('click', function(e) {
    if(!confirm('Reset all changes to their original values?')) {
        e.preventDefault();
    } else {
        formChanged = false;
    }
});

document.getElementById('profileForm')?.addEventListener('submit', () => { formChanged = false; });
</script>

</body>
</html>