<?php
session_start();

// Database connection
include 'partials/_dbconnect.php';

$error = "";
$success = "";
$email_sent = false;

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = "Please enter your email address";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        // Check if email exists in database
        $check_sql = $conn->prepare("SELECT emp_id, user_id, user_name, user_mail FROM employees WHERE user_mail = ? AND status = 1");
        $check_sql->bind_param("s", $email);
        $check_sql->execute();
        $result = $check_sql->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Generate unique reset token
            $reset_token = bin2hex(random_bytes(32));
            $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $update_sql = $conn->prepare("UPDATE employees SET reset_token = ?, reset_token_expiry = ? WHERE user_mail = ?");
            $update_sql->bind_param("sss", $reset_token, $token_expiry, $email);
            
            if ($update_sql->execute()) {
                // In a real application, you would send an email here
                // For demonstration, we'll store the reset link in session
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_token'] = $reset_token;
                
                $success = "Password reset instructions have been sent to your email address.";
                $email_sent = true;
            } else {
                $error = "Unable to process request. Please try again later.";
                error_log("Reset token error: " . $conn->error);
            }
            $update_sql->close();
        } else {
            // Don't reveal if email exists or not for security
            $success = "If an account exists with this email, password reset instructions have been sent.";
            $email_sent = true;
        }
        $check_sql->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Meta Tags -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Forgot Password | GO2EXPORT MART</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Reset your password for GO2EXPORT MART CRMS">
    <meta name="keywords" content="forgot password, reset password, GO2EXPORT MART">
    <meta name="author" content="GO2EXPORT MART">
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/img/favicon.png">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    
    <!-- Tabler Icon CSS -->
    <link rel="stylesheet" href="assets/plugins/tabler-icons/tabler-icons.min.css">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/style.css" id="app-style">
    
    <style>
        :root {
            --primary-red: #dc3545;
            --primary-red-dark: #c82333;
            --primary-red-light: #f8d7da;
            --primary-red-bg: #fff5f5;
        }
        
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
        
        .btn-primary {
            background-color: var(--primary-red);
            border-color: var(--primary-red);
        }
        
        .btn-primary:hover,
        .btn-primary:focus {
            background-color: var(--primary-red-dark);
            border-color: var(--primary-red-dark);
        }
        
        .btn-outline-primary {
            color: var(--primary-red);
            border-color: var(--primary-red);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-red);
            border-color: var(--primary-red);
        }
        
        .link-primary {
            color: var(--primary-red);
            text-decoration: none;
        }
        
        .link-primary:hover {
            color: var(--primary-red-dark);
            text-decoration: underline;
        }
        
        .input-group-flat .input-group-text {
            background-color: #f8f9fa;
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
            to {
                transform: rotate(360deg);
            }
        }
        
        .account-bg-03 {
            background: linear-gradient(135deg, #dc3545 0%, #8b0000 100%);
            position: relative;
            overflow: hidden;
        }
        
        .account-bg-03::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1%, transparent 1%);
            background-size: 50px 50px;
            animation: moveBackground 20s linear infinite;
        }
        
        @keyframes moveBackground {
            0% {
                transform: translate(0, 0);
            }
            100% {
                transform: translate(50px, 50px);
            }
        }
        
        .reset-info-card {
            background: var(--primary-red-bg);
            border-left: 4px solid var(--primary-red);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .auth-logo img {
            max-height: 60px;
        }
        
        .text-red {
            color: var(--primary-red) !important;
        }
        
        .or-login:before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e9ecef;
            z-index: 0;
        }
        
        .or-login h6 {
            background: white;
            display: inline-block;
            padding: 0 15px;
            z-index: 1;
        }
    </style>
</head>

<body class="account-page bg-white">
    <!-- Main Wrapper -->
    <div class="main-wrapper">
        <div class="overflow-hidden p-3 acc-vh">
            <!-- start row -->
            <div class="row vh-100 w-100 g-0">
                <div class="col-lg-6 vh-100 overflow-y-auto overflow-x-hidden">
                    <!-- start row -->
                    <div class="row">
                        <div class="col-md-10 mx-auto">
                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="vh-100 d-flex justify-content-between flex-column p-4 pb-0" id="forgotPasswordForm">
                                <div class="text-center mb-4 auth-logo">
                                    <img src="assets/img/logo-g2em.png" class="img-fluid" alt="GO2EXPORT MART">
                                </div>
                                
                                <div>
                                    <div class="mb-3 text-center">
                                        <div class="mb-3">
                                            <i class="ti ti-lock-question fs-1 text-red"></i>
                                        </div>
                                        <h3 class="mb-2">Forgot Password?</h3>
                                        <p class="mb-0 text-muted">Enter your email address and we'll send you instructions to reset your password.</p>
                                    </div>
                                    
                                    <?php if (!empty($error)) { ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <i class="ti ti-alert-circle me-2"></i>
                                            <strong>Error!</strong> <?php echo htmlspecialchars($error); ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                    <?php } ?>
                                    
                                    <?php if (!empty($success)) { ?>
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <i class="ti ti-circle-check me-2"></i>
                                            <strong>Success!</strong> <?php echo htmlspecialchars($success); ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                    <?php } ?>
                                    
                                    <?php if (!$email_sent) { ?>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Email Address</label>
                                            <div class="input-group input-group-flat">
                                                <span class="input-group-text bg-light">
                                                    <i class="ti ti-mail"></i>
                                                </span>
                                                <input type="email" name="email" class="form-control" placeholder="Enter your registered email address" required 
                                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                            </div>
                                            <small class="text-muted">We'll send password reset instructions to this email</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                                                <i class="ti ti-send me-2"></i>Send Reset Instructions
                                            </button>
                                        </div>
                                    <?php } else { ?>
                                        <div class="reset-info-card">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="ti ti-mail-check text-red fs-4 me-2"></i>
                                                <strong class="text-dark">What happens next?</strong>
                                            </div>
                                            <ul class="mb-0 ps-3">
                                                <li class="mb-2">Check your email inbox for password reset instructions</li>
                                                <li class="mb-2">Click the link in the email to create a new password</li>
                                                <li>The reset link expires in 1 hour for security</li>
                                                <li>If you don't see the email, check your spam folder</li>
                                            </ul>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <a href="index.php" class="btn btn-outline-primary w-100">
                                                <i class="ti ti-login me-2"></i>Return to Login
                                            </a>
                                        </div>
                                    <?php } ?>
                                    
                                    <div class="mb-3 text-center">
                                        <p class="mb-0">
                                            <a href="index.php" class="link-primary fw-bold text-decoration-none">
                                                <i class="ti ti-arrow-left me-1"></i>Back to Login
                                            </a>
                                        </p>
                                    </div>
                                    
                                    <?php if (!$email_sent) { ?>
                                        <div class="or-login text-center position-relative mb-3">
                                            <h6 class="fs-14 mb-0 position-relative text-body bg-white d-inline-block px-3">Need Help?</h6>
                                        </div>
                                        
                                        <div class="text-center">
                                            <p class="mb-2 small text-muted">Contact your system administrator or HR department</p>
                                            <p class="mb-0 small">
                                                <i class="ti ti-headset text-red me-1"></i>
                                                <span class="text-muted">Support: support@go2exportmart.com</span>
                                            </p>
                                        </div>
                                    <?php } ?>
                                </div>
                                
                                <div class="text-center pb-4">
                                    <p class="text-muted mb-0 small">Copyright &copy; <?php echo date("Y"); ?> - GO2EXPORT MART</p>
                                </div>
                            </form>
                        </div>
                        <!-- end col -->
                    </div>
                    <!-- end row -->
                </div>
                <!-- end col -->
                
                <div class="col-lg-6 d-none d-lg-block account-bg-03">
                    <div class="h-100 d-flex flex-column justify-content-center align-items-center text-white p-5 position-relative" style="z-index: 1;">
                        <div class="text-center">
                            <i class="ti ti-key fs-1 mb-3"></i>
                            <h3 class="text-white mb-3">Reset Your Password</h3>
                            <p class="text-white-50 mb-4">Don't worry, we've got you covered. Enter your email and we'll help you get back into your account.</p>
                            <div class="mt-4">
                                <div class="d-flex justify-content-center gap-3">
                                    <div class="text-center">
                                        <div class="bg-white bg-opacity-20 rounded-circle p-3 mb-2 d-inline-block">
                                            <i class="ti ti-mail fs-4"></i>
                                        </div>
                                        <small>Enter Email</small>
                                    </div>
                                    <i class="ti ti-arrow-right fs-4 mt-3"></i>
                                    <div class="text-center">
                                        <div class="bg-white bg-opacity-20 rounded-circle p-3 mb-2 d-inline-block">
                                            <i class="ti ti-link fs-4"></i>
                                        </div>
                                        <small>Get Link</small>
                                    </div>
                                    <i class="ti ti-arrow-right fs-4 mt-3"></i>
                                    <div class="text-center">
                                        <div class="bg-white bg-opacity-20 rounded-circle p-3 mb-2 d-inline-block">
                                            <i class="ti ti-lock-open fs-4"></i>
                                        </div>
                                        <small>Reset Password</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end col -->
            </div>
            <!-- end row -->
        </div>
    </div>
    <!-- End Wrapper -->
    
    <!-- jQuery -->
    <script src="assets/js/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap Core JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Form submission with loading state
            $('#forgotPasswordForm').on('submit', function() {
                const btn = $('#submitBtn');
                if (btn.length) {
                    btn.addClass('btn-loading');
                    btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Sending...');
                    btn.prop('disabled', true);
                    
                    // Remove loading state if form doesn't submit (like validation error)
                    setTimeout(function() {
                        if (btn.hasClass('btn-loading')) {
                            btn.removeClass('btn-loading');
                            btn.html('<i class="ti ti-send me-2"></i>Send Reset Instructions');
                            btn.prop('disabled', false);
                        }
                    }, 3000);
                }
                return true;
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
            
            // Email validation on input
            $('input[name="email"]').on('input', function() {
                const email = $(this).val();
                const emailPattern = /^[^\s@]+@([^\s@]+\.)+[^\s@]+$/;
                
                if (email.length > 0 && !emailPattern.test(email)) {
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
            
            // Add floating label effect
            $('.form-control').on('focus blur', function(e) {
                $(this).toggleClass('focused', e.type === 'focus');
            });
        });
        
        // Optional: Add demo reset link functionality (for testing)
        function showDemoResetLink() {
            <?php if (isset($_SESSION['reset_token']) && isset($_SESSION['reset_email'])) { ?>
                const resetLink = 'reset-password.php?token=<?php echo $_SESSION['reset_token']; ?>&email=<?php echo urlencode($_SESSION['reset_email']); ?>';
                console.log('Demo Reset Link:', resetLink);
                // In a real application, you would send this via email
                // For demo purposes, you can show it in console
            <?php } ?>
        }
    </script>
</body>

</html>