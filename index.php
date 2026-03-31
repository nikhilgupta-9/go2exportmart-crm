<?php
session_start();
session_regenerate_id(true);

// Database connection
include 'partials/_dbconnect.php';

$error = ""; // Initialize error variable

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $userId = trim($_POST['userId']);
    $enteredPassword = trim($_POST['userPassword']);
    
    // Sanitizing User ID to match your preg_replace logic
    $newcheck = preg_replace('/[^A-Za-z0-9]/', '', $userId);
    
    if (empty($newcheck) || empty($enteredPassword)) {
        $error = "Please enter both User ID and Password";
    } else {
        // Prepared statement to prevent SQL Injection
        $login_check_sql = $conn->prepare("SELECT emp_id, user_id, user_password, department, grade_level, user_name, user_role, status FROM employees WHERE user_id = ? AND status = 1");
        
        if ($login_check_sql) {
            $login_check_sql->bind_param("s", $newcheck);
            $login_check_sql->execute();
            $result = $login_check_sql->get_result();
            
            if ($result->num_rows > 0) {
                $login_row = $result->fetch_assoc();
                
                $db_user_id = $login_row['user_id'];
                $db_emp_id = $login_row['emp_id'];
                $db_pass = $login_row['user_password'];
                $db_department = $login_row['department'];
                $db_grade_level = isset($login_row['grade_level']) ? $login_row['grade_level'] : 0;
                $db_user_name = $login_row['user_name'];
                $db_user_role = $login_row['user_role'];
                
                // Plain Text Comparison (as per your requirement)
                if ($enteredPassword === $db_pass) {
                    // Set session variables
                    $_SESSION['user_id'] = $db_user_id;
                    $_SESSION['emp_id'] = $db_emp_id;
                    $_SESSION['user_name'] = $db_user_name;
                    $_SESSION['department'] = $db_department;
                    $_SESSION['grade_level'] = $db_grade_level;
                    $_SESSION['user_role'] = $db_user_role;
                    $_SESSION['logged_in'] = true;
                    $_SESSION['login_time'] = time();
                    
                    // Log the login activity (optional)
                    $log_sql = $conn->prepare("INSERT INTO login_logs (user_id, login_time, ip_address) VALUES (?, NOW(), ?)");
                    $ip_address = $_SERVER['REMOTE_ADDR'];
                    $log_sql->bind_param("ss", $db_emp_id, $ip_address);
                    $log_sql->execute();
                    $log_sql->close();
                    
                    // Redirect based on department and grade level
                    if ($db_department == "Support") {
                        header("Location: dashboard-support.php");
                    } elseif ($db_grade_level == 1) {
                        // Admin users can access admin dashboard
                        header("Location: admin-dashboard.php");
                    } else {
                        header("Location: dashboard.php");
                    }
                    exit;
                } else {
                    $error = "Invalid Password! Please try again.";
                }
            } else {
                $error = "User not found or account is inactive. Please check your User ID.";
            }
            $login_check_sql->close();
        } else {
            $error = "Database error: Unable to process login. Please try again later.";
            error_log("Login prepare error: " . $conn->error);
        }
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Login | Go 2 Export Mart</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <link rel="shortcut icon" href="assets/img/favicon.png" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/plugins/tabler-icons/tabler-icons.min.css" />
    <link rel="stylesheet" href="assets/css/style.css" id="app-style" />
    
    <style>
        .alert {
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .password-strength {
            height: 3px;
            margin-top: 5px;
            transition: all 0.3s;
        }
        
        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }
        
        .btn-loading:after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid #fff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.6s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>

<body class="account-page bg-white">
    <div class="main-wrapper">
        <div class="overflow-hidden p-3 acc-vh">
            <div class="row vh-100 w-100 g-0">
                <div class="col-lg-6 vh-100 overflow-y-auto overflow-x-hidden">
                    <div class="row">
                        <div class="col-md-10 mx-auto">
                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="vh-100 d-flex justify-content-between flex-column p-4 pb-0" id="loginForm">
                                
                                <div class="text-center mb-4 auth-logo">
                                    <img src="assets/img/logo.svg" class="img-fluid" alt="Logo">
                                </div>
                                
                                <div>
                                    <div class="mb-3">
                                        <h3 class="mb-2">Welcome Back</h3>
                                        <p class="mb-0 text-muted">Access the CRMS panel using your credentials.</p>
                                    </div>
                                    
                                    <?php if (!empty($error)) { ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <i class="ti ti-alert-circle me-2"></i>
                                            <strong>Error!</strong> <?php echo htmlspecialchars($error); ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                    <?php } ?>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">User ID</label>
                                        <div class="input-group input-group-flat">
                                            <span class="input-group-text bg-light">
                                                <i class="ti ti-user"></i>
                                            </span>
                                            <input type="text" name="userId" class="form-control" placeholder="Enter User ID" required 
                                                   value="<?php echo isset($_POST['userId']) ? htmlspecialchars($_POST['userId']) : ''; ?>">
                                        </div>
                                        <small class="text-muted">Enter your registered User ID</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Password</label>
                                        <div class="input-group input-group-flat pass-group">
                                            <span class="input-group-text bg-light">
                                                <i class="ti ti-lock"></i>
                                            </span>
                                            <input type="password" name="userPassword" id="password-field" class="form-control pass-input" placeholder="Enter Password" required>
                                            <span class="input-group-text toggle-password bg-light" style="cursor: pointer;">
                                                <i class="ti ti-eye-off" id="toggleIcon"></i>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3 d-flex justify-content-between align-items-center">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="rememberMe">
                                            <label class="form-check-label" for="rememberMe">Remember me</label>
                                        </div>
                                        <a href="forgot-password.php" class="text-primary text-decoration-none">Forgot Password?</a>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <button type="submit" class="btn btn-primary w-100" id="loginBtn">
                                            <i class="ti ti-login me-2"></i>Sign In
                                        </button>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <p class="mb-0 text-center">New on our platform? <a href="register.html" class="text-primary fw-bold text-decoration-none">Create an account</a></p>
                                    </div>
                                    
                                    <div class="or-login text-center position-relative mb-3">
                                        <h6 class="fs-14 mb-0 position-relative text-body">OR</h6>
                                    </div>
                                    
                                    <div class="d-flex align-items-center justify-content-center flex-wrap gap-2 mb-3">
                                        <div class="text-center flex-fill">
                                            <a href="#" class="p-2 btn btn-outline-light d-flex align-items-center justify-content-center border">
                                                <img class="img-fluid m-1" src="assets/img/icons/google-logo.svg" alt="Google" width="20">
                                                <span class="ms-2">Sign in with Google</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-center pb-4">
                                    <p class="text-muted mb-0 small">Copyright &copy; <?php echo date("Y"); ?> - GO2EXPORT MART</p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6 account-bg-01">
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/jquery-3.7.1.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Toggle password visibility
            $('.toggle-password').click(function() {
                const passwordField = $('#password-field');
                const icon = $('#toggleIcon');
                
                if (passwordField.attr('type') === 'password') {
                    passwordField.attr('type', 'text');
                    icon.removeClass('ti-eye-off').addClass('ti-eye');
                } else {
                    passwordField.attr('type', 'password');
                    icon.removeClass('ti-eye').addClass('ti-eye-off');
                }
            });
            
            // Remember me functionality
            if (localStorage.getItem('rememberedUserId')) {
                $('#rememberMe').prop('checked', true);
                $('input[name="userId"]').val(localStorage.getItem('rememberedUserId'));
            }
            
            $('#rememberMe').change(function() {
                if ($(this).is(':checked')) {
                    localStorage.setItem('rememberedUserId', $('input[name="userId"]').val());
                } else {
                    localStorage.removeItem('rememberedUserId');
                }
            });
            
            $('input[name="userId"]').on('input', function() {
                if ($('#rememberMe').is(':checked')) {
                    localStorage.setItem('rememberedUserId', $(this).val());
                }
            });
            
            // Form submission with loading state
            $('#loginForm').on('submit', function() {
                const btn = $('#loginBtn');
                btn.addClass('btn-loading');
                btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Signing in...');
                btn.prop('disabled', true);
                
                // Remove loading state if form doesn't submit (like validation error)
                setTimeout(function() {
                    if (btn.hasClass('btn-loading')) {
                        btn.removeClass('btn-loading');
                        btn.html('<i class="ti ti-login me-2"></i>Sign In');
                        btn.prop('disabled', false);
                    }
                }, 3000);
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        });
    </script>
</body>

</html>