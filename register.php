<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Sign Up | Go 2 Export Mart</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Create a new account for Go 2 Export Mart CRMS platform" />

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

        .password-strength {
            height: 3px;
            margin-top: 5px;
            transition: all 0.3s;
            border-radius: 3px;
        }

        .strength-weak {
            background-color: #dc3545;
            width: 33%;
        }

        .strength-medium {
            background-color: #ffc107;
            width: 66%;
        }

        .strength-strong {
            background-color: #28a745;
            width: 100%;
        }

        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .input-group-flat .input-group-text {
            background-color: #f8f9fa;
        }

        .toggle-password {
            cursor: pointer;
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
                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="vh-100 d-flex justify-content-between flex-column p-4 pb-0" id="registerForm">
                                <div class="text-center mb-4 auth-logo">
                                    <img src="assets/img/logo-g2em.png" class="img-fluid" alt="Logo">
                                </div>

                                <div>
                                    <div class="mb-3">
                                        <h3 class="mb-2">Create an Account</h3>
                                        <p class="mb-0 text-muted">Join Go 2 Export Mart CRMS platform</p>
                                    </div>

                                    <?php
                                    // Start session for registration messages
                                    session_start();
                                    
                                    // Database connection
                                    include 'partials/_dbconnect.php';
                                    
                                    $error = "";
                                    $success = "";
                                    
                                    if ($_SERVER['REQUEST_METHOD'] == "POST") {
                                        // Get and sanitize inputs
                                        $fullName = trim($_POST['fullName']);
                                        $email = trim($_POST['email']);
                                        $userId = trim($_POST['userId']);
                                        $password = trim($_POST['password']);
                                        $confirmPassword = trim($_POST['confirmPassword']);
                                        
                                        // Validation flags
                                        $isValid = true;
                                        
                                        // Validate full name
                                        if (empty($fullName)) {
                                            $error = "Please enter your full name";
                                            $isValid = false;
                                        } elseif (!preg_match('/^[A-Za-z\s]+$/', $fullName)) {
                                            $error = "Name should only contain letters and spaces";
                                            $isValid = false;
                                        }
                                        
                                        // Validate email
                                        if ($isValid && empty($email)) {
                                            $error = "Please enter your email address";
                                            $isValid = false;
                                        } elseif ($isValid && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                            $error = "Please enter a valid email address";
                                            $isValid = false;
                                        }
                                        
                                        // Validate user ID (alphanumeric only)
                                        if ($isValid && empty($userId)) {
                                            $error = "Please enter a User ID";
                                            $isValid = false;
                                        } elseif ($isValid) {
                                            $sanitizedUserId = preg_replace('/[^A-Za-z0-9]/', '', $userId);
                                            if (empty($sanitizedUserId)) {
                                                $error = "User ID must contain only letters and numbers";
                                                $isValid = false;
                                            } elseif (strlen($sanitizedUserId) < 3) {
                                                $error = "User ID must be at least 3 characters long";
                                                $isValid = false;
                                            } else {
                                                $userId = $sanitizedUserId;
                                            }
                                        }
                                        
                                        // Validate password
                                        if ($isValid && empty($password)) {
                                            $error = "Please enter a password";
                                            $isValid = false;
                                        } elseif ($isValid && strlen($password) < 6) {
                                            $error = "Password must be at least 6 characters long";
                                            $isValid = false;
                                        }
                                        
                                        // Validate password confirmation
                                        if ($isValid && empty($confirmPassword)) {
                                            $error = "Please confirm your password";
                                            $isValid = false;
                                        } elseif ($isValid && $password !== $confirmPassword) {
                                            $error = "Passwords do not match";
                                            $isValid = false;
                                        }
                                        
                                        // Check if user already exists
                                        if ($isValid) {
                                            $check_sql = $conn->prepare("SELECT user_id, user_mail FROM employees WHERE user_id = ? OR user_mail = ?");
                                            $check_sql->bind_param("ss", $userId, $email);
                                            $check_sql->execute();
                                            $check_result = $check_sql->get_result();
                                            
                                            if ($check_result->num_rows > 0) {
                                                $existing = $check_result->fetch_assoc();
                                                if ($existing['user_id'] === $userId) {
                                                    $error = "User ID already exists. Please choose a different one.";
                                                } elseif ($existing['email'] === $email) {
                                                    $error = "Email address is already registered. Please login instead.";
                                                }
                                                $isValid = false;
                                            }
                                            $check_sql->close();
                                        }
                                        
                                        // Insert new user if all validations pass
                                        if ($isValid) {
                                            // Generate a unique employee ID (you can modify this logic)
                                            $emp_id = 'EMP' . strtoupper(substr(uniqid(), -6));
                                            
                                            // Default values for new users
                                            $department = "General";
                                            $grade_level = 0;
                                            $user_role = "user";
                                            $status = 0; 
                                            
                                            // Insert using prepared statement (plain text password as per your login page requirement)
                                            $insert_sql = $conn->prepare("INSERT INTO employees (emp_id, user_id, user_password, user_name, user_mail,  status, created_at) VALUES ( ?, ?, ?, ?, ?, ?, NOW())");
                                            
                                            if ($insert_sql) {
                                                $insert_sql->bind_param("ssssss", $emp_id, $userId, $password, $fullName, $email, $status);
                                                
                                                if ($insert_sql->execute()) {
                                                    $success = "Account created successfully! You can now login with your credentials.";
                                                    
                                                    // Log registration activity
                                                    $log_sql = $conn->prepare("INSERT INTO registration_logs (user_id, email, registered_at, ip_address) VALUES (?, ?, NOW(), ?)");
                                                    $ip_address = $_SERVER['REMOTE_ADDR'];
                                                    $log_sql->bind_param("sss", $userId, $email, $ip_address);
                                                    $log_sql->execute();
                                                    $log_sql->close();
                                                    
                                                    // Clear form data after success (optional)
                                                    $_POST = array();
                                                } else {
                                                    $error = "Registration failed. Please try again later.";
                                                    error_log("Registration insert error: " . $conn->error);
                                                }
                                                $insert_sql->close();
                                            } else {
                                                $error = "Database error. Please try again later.";
                                                error_log("Registration prepare error: " . $conn->error);
                                            }
                                        }
                                    }
                                    ?>

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
                                        <script>
                                            // Auto redirect to login page after 3 seconds on success
                                            setTimeout(function() {
                                                window.location.href = 'login.php';
                                            }, 3000);
                                        </script>
                                    <?php } ?>

                                    <div class="row">
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label fw-semibold">Full Name</label>
                                        <div class="input-group input-group-flat">
                                            <span class="input-group-text bg-light">
                                                <i class="ti ti-user"></i>
                                            </span>
                                            <input type="text" name="fullName" class="form-control" placeholder="Enter your full name" required 
                                                   value="<?php echo isset($_POST['fullName']) ? htmlspecialchars($_POST['fullName']) : ''; ?>">
                                        </div>
                                        <small class="text-muted">Enter your legal name as per records</small>
                                    </div>

                                    <div class="mb-3 col-md-6">
                                        <label class="form-label fw-semibold">Email Address</label>
                                        <div class="input-group input-group-flat">
                                            <span class="input-group-text bg-light">
                                                <i class="ti ti-mail"></i>
                                            </span>
                                            <input type="email" name="email" class="form-control" placeholder="Enter your email address" required 
                                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                        </div>
                                        <small class="text-muted">We'll send verification to this email</small>
                                    </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">User ID</label>
                                        <div class="input-group input-group-flat">
                                            <span class="input-group-text bg-light">
                                                <i class="ti ti-id"></i>
                                            </span>
                                            <input type="text" name="userId" class="form-control" placeholder="Choose a User ID (letters & numbers only)" required 
                                                   value="<?php echo isset($_POST['userId']) ? htmlspecialchars($_POST['userId']) : ''; ?>">
                                        </div>
                                        <small class="text-muted">Use only letters and numbers, minimum 3 characters</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Password</label>
                                        <div class="input-group input-group-flat pass-group">
                                            <span class="input-group-text bg-light">
                                                <i class="ti ti-lock"></i>
                                            </span>
                                            <input type="password" name="password" id="password-field" class="form-control pass-input" placeholder="Create a password" required>
                                            <span class="input-group-text toggle-password bg-light" style="cursor: pointer;">
                                                <i class="ti ti-eye-off" id="toggleIcon1"></i>
                                            </span>
                                        </div>
                                        <div class="password-strength" id="passwordStrength"></div>
                                        <small class="text-muted">Minimum 6 characters. Use letters and numbers for strong password</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Confirm Password</label>
                                        <div class="input-group input-group-flat pass-group">
                                            <span class="input-group-text bg-light">
                                                <i class="ti ti-lock-check"></i>
                                            </span>
                                            <input type="password" name="confirmPassword" id="confirm-password-field" class="form-control pass-input" placeholder="Confirm your password" required>
                                            <span class="input-group-text toggle-password bg-light" style="cursor: pointer;">
                                                <i class="ti ti-eye-off" id="toggleIcon2"></i>
                                            </span>
                                        </div>
                                        <small class="text-muted" id="passwordMatchMsg"></small>
                                    </div>

                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="form-check form-check-md d-flex align-items-center">
                                            <input class="form-check-input mt-0" type="checkbox" value="" id="termsCheckbox" required>
                                            <label class="form-check-label ms-2" for="termsCheckbox">
                                                I agree to the <a href="policy/terms-conditions.php" class="text-primary text-decoration-none">Terms of Service</a> and 
                                                <a href="policy/privacy-policy.php" class="text-primary text-decoration-none">Privacy Policy</a>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <button type="submit" class="btn btn-primary w-100" id="registerBtn">
                                            <i class="ti ti-user-plus me-2"></i>Create Account
                                        </button>
                                    </div>

                                    <div class="mb-3">
                                        <p class="mb-0 text-center">Already have an account? 
                                            <a href="index.php" class="text-primary fw-bold text-decoration-none">Sign In Instead</a>
                                        </p>
                                    </div>

                                    <div class="or-login text-center position-relative mb-3">
                                        <h6 class="fs-14 mb-0 position-relative text-body">OR</h6>
                                    </div>

                                    <div class="d-flex align-items-center justify-content-center flex-wrap gap-2 mb-3">
                                        <div class="text-center flex-fill">
                                            <a href="#" class="p-2 btn btn-outline-light d-flex align-items-center justify-content-center border">
                                                <img class="img-fluid m-1" src="assets/img/icons/google-logo.svg" alt="Google" width="20">
                                                <span class="ms-2">Sign up with Google</span>
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

                <div class="col-lg-6 account-bg-02">
                    <!-- Background image area - CSS will handle this -->
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery-3.7.1.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Toggle password visibility for both password fields
            $('.toggle-password').each(function(index) {
                $(this).click(function() {
                    const targetId = index === 0 ? '#password-field' : '#confirm-password-field';
                    const targetIcon = index === 0 ? '#toggleIcon1' : '#toggleIcon2';
                    const passwordField = $(targetId);
                    const icon = $(targetIcon);
                    
                    if (passwordField.attr('type') === 'password') {
                        passwordField.attr('type', 'text');
                        icon.removeClass('ti-eye-off').addClass('ti-eye');
                    } else {
                        passwordField.attr('type', 'password');
                        icon.removeClass('ti-eye').addClass('ti-eye-off');
                    }
                });
            });
            
            // Password strength checker
            $('#password-field').on('keyup', function() {
                const password = $(this).val();
                const strengthBar = $('#passwordStrength');
                let strength = 0;
                
                if (password.length >= 6) strength++;
                if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
                if (password.match(/\d/)) strength++;
                if (password.match(/[^a-zA-Z\d]/)) strength++;
                
                strengthBar.removeClass('strength-weak strength-medium strength-strong');
                
                if (password.length === 0) {
                    strengthBar.css('width', '0');
                    return;
                }
                
                if (strength <= 1) {
                    strengthBar.addClass('strength-weak');
                } else if (strength === 2) {
                    strengthBar.addClass('strength-medium');
                } else {
                    strengthBar.addClass('strength-strong');
                }
            });
            
            // Password match validation
            function validatePasswordMatch() {
                const password = $('#password-field').val();
                const confirmPassword = $('#confirm-password-field').val();
                const matchMsg = $('#passwordMatchMsg');
                
                if (confirmPassword.length > 0) {
                    if (password === confirmPassword) {
                        matchMsg.html('<i class="ti ti-check text-success"></i> Passwords match');
                        matchMsg.css('color', '#28a745');
                        return true;
                    } else {
                        matchMsg.html('<i class="ti ti-x text-danger"></i> Passwords do not match');
                        matchMsg.css('color', '#dc3545');
                        return false;
                    }
                } else {
                    matchMsg.html('');
                    return true;
                }
            }
            
            $('#password-field, #confirm-password-field').on('keyup', function() {
                validatePasswordMatch();
            });
            
            // User ID validation (alphanumeric only)
            $('input[name="userId"]').on('keyup', function() {
                const value = $(this).val();
                const sanitized = value.replace(/[^A-Za-z0-9]/g, '');
                if (value !== sanitized) {
                    $(this).val(sanitized);
                }
            });
            
            // Form submission with validation
            $('#registerForm').on('submit', function(e) {
                const btn = $('#registerBtn');
                const password = $('#password-field').val();
                const confirmPassword = $('#confirm-password-field').val();
                const termsChecked = $('#termsCheckbox').is(':checked');
                const userId = $('input[name="userId"]').val();
                
                // Additional client-side validation
                if (password !== confirmPassword) {
                    e.preventDefault();
                    $('#passwordMatchMsg').html('<i class="ti ti-x text-danger"></i> Passwords do not match');
                    $('#passwordMatchMsg').css('color', '#dc3545');
                    $('html, body').animate({
                        scrollTop: $('#confirm-password-field').offset().top - 100
                    }, 500);
                    return false;
                }
                
                if (password.length < 6) {
                    e.preventDefault();
                    const errorDiv = $('<div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">' +
                        '<i class="ti ti-alert-circle me-2"></i>' +
                        '<strong>Error!</strong> Password must be at least 6 characters long' +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                        '</div>');
                    $('#registerForm > div:first-child').after(errorDiv);
                    $('html, body').animate({
                        scrollTop: $('#password-field').offset().top - 100
                    }, 500);
                    return false;
                }
                
                if (!termsChecked) {
                    e.preventDefault();
                    const errorDiv = $('<div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">' +
                        '<i class="ti ti-alert-circle me-2"></i>' +
                        '<strong>Error!</strong> You must agree to the Terms and Privacy Policy' +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                        '</div>');
                    $('#registerForm > div:first-child').after(errorDiv);
                    $('html, body').animate({
                        scrollTop: $('#termsCheckbox').offset().top - 100
                    }, 500);
                    return false;
                }
                
                if (userId.length < 3) {
                    e.preventDefault();
                    const errorDiv = $('<div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">' +
                        '<i class="ti ti-alert-circle me-2"></i>' +
                        '<strong>Error!</strong> User ID must be at least 3 characters long' +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                        '</div>');
                    $('#registerForm > div:first-child').after(errorDiv);
                    $('html, body').animate({
                        scrollTop: $('input[name="userId"]').offset().top - 100
                    }, 500);
                    return false;
                }
                
                // Show loading state
                btn.addClass('btn-loading');
                btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Creating account...');
                btn.prop('disabled', true);
                
                // Allow form submission to proceed
                return true;
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
            
            // Live user ID availability check (optional, can be implemented with AJAX)
            let typingTimer;
            const doneTypingInterval = 500;
            
            $('input[name="userId"]').on('keyup', function() {
                clearTimeout(typingTimer);
                const userId = $(this).val();
                if (userId.length >= 3) {
                    typingTimer = setTimeout(function() {
                        // Optional: AJAX call to check user ID availability
                        // This would require a separate endpoint
                    }, doneTypingInterval);
                }
            });
        });
    </script>
</body>

</html>