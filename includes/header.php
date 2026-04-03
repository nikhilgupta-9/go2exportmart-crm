<?php

// Get user info for header display
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT * FROM employees WHERE user_id = '$user_id'";
$user_result = mysqli_query($conn, $user_sql);
$user_data = mysqli_fetch_assoc($user_result);

// Get profile image
$profile_image = !empty($user_data['user_img']) && file_exists("assets/uploads/profiles/" . $user_data['user_img']) 
    ? "assets/uploads/profiles/" . $user_data['user_img'] 
    : "https://ui-avatars.com/api/?name=" . urlencode($user_data['user_name']) . "&background=667eea&color=fff&size=100&bold=true";
?>

<header class="navbar-header">
    <div class="page-container topbar-menu">
        <div class="d-flex align-items-center gap-2">

            <!-- Logo -->
            <a href="dashboard.php" class="logo">
                <!-- Logo Normal -->
                <span class="logo-light">
                    <span class="logo-lg"><img src="assets/img/logo.svg" alt="logo"></span>
                    <span class="logo-sm"><img src="assets/img/logo-small.svg" alt="small logo"></span>
                </span>
                <!-- Logo Dark -->
                <span class="logo-dark">
                    <span class="logo-lg"><img src="assets/img/logo-white.svg" alt="dark logo"></span>
                </span>
            </a>

            <!-- Sidebar Mobile Button -->
            <a id="mobile_btn" class="mobile-btn" href="#sidebar">
                <i class="ti ti-menu-deep fs-24"></i>
            </a>

            <button class="sidenav-toggle-btn btn border-0 p-0" id="toggle_btn2"> 
                <i class="ti ti-arrow-bar-to-right"></i>
            </button> 
            
            <!-- Search -->
            <div class="me-auto d-flex align-items-center header-search d-lg-flex d-none">
                <div class="input-icon position-relative me-2">
                    <input type="text" class="form-control" placeholder="Search...">
                    <span class="input-icon-addon d-inline-flex p-0 header-search-icon"><i class="ti ti-search"></i></span>
                </div>
            </div>
            
        </div>

        <div class="d-flex align-items-center">
        
            <!-- Search for Mobile -->
            <div class="header-item d-flex d-lg-none me-2">
                <button class="topbar-link btn" data-bs-toggle="modal" data-bs-target="#searchModal" type="button">
                    <i class="ti ti-search fs-16"></i>
                </button>
            </div>

            <!-- Minimize -->
            <div class="header-item">
                <div class="dropdown me-2">
                    <a href="javascript:void(0);" class="btn topbar-link btnFullscreen"><i class="ti ti-maximize"></i></a>
                </div> 
            </div>

            <!-- Light/Dark Mode Button -->
            <div class="header-item d-none d-sm-flex me-2">
                <button class="topbar-link btn topbar-link" id="light-dark-mode" type="button">
                    <i class="ti ti-moon fs-16"></i>
                </button>
            </div>

            <div class="header-line"></div>

            <!-- User Dropdown -->
            <div class="dropdown profile-dropdown d-flex align-items-center justify-content-center">
                <a href="javascript:void(0);" class="topbar-link dropdown-toggle drop-arrow-none position-relative" data-bs-toggle="dropdown" data-bs-offset="0,22" aria-haspopup="false" aria-expanded="false">
                    <img src="<?php echo $profile_image; ?>" width="38" class="rounded-1 d-flex" alt="user-image" style="object-fit: cover; height: 38px;">
                    <span class="online text-success"><i class="ti ti-circle-filled d-flex bg-white rounded-circle border border-1 border-white"></i></span>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-md p-2">
                
                    <div class="d-flex align-items-center bg-light rounded-3 p-2 mb-2">
                        <img src="<?php echo $profile_image; ?>" class="rounded-circle" width="42" height="42" alt="Img" style="object-fit: cover;">
                        <div class="ms-2">
                            <p class="fw-medium text-dark mb-0"><?php echo htmlspecialchars($user_data['user_name']); ?></p>
                            <span class="d-block fs-13"><?php echo htmlspecialchars($user_data['user_role']); ?></span>
                        </div>
                    </div>

                    <a href="info.php" class="dropdown-item">
                        <i class="ti ti-user-circle me-1 align-middle"></i>
                        <span class="align-middle">My Profile</span>
                    </a>

                    <a href="change-password.php" class="dropdown-item">
                        <i class="ti ti-lock me-1 align-middle"></i>
                        <span class="align-middle">Change Password</span>
                    </a>

                    <a href="javascript:void(0);" class="dropdown-item" id="light-dark-mode-mobile">
                        <i class="ti ti-moon me-1 align-middle"></i>
                        <span class="align-middle">Dark/Light Mode</span>
                    </a>
                    
                    <div class="pt-2 mt-2 border-top">
                        <a href="logout.php" class="dropdown-item text-danger">
                            <i class="ti ti-logout me-1 fs-17 align-middle"></i>
                            <span class="align-middle">Sign Out</span>
                        </a>
                    </div>
                </div>
            </div>
                
        </div>
    </div>
</header>

<!-- Search Modal -->
<div class="modal fade" id="searchModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-transparent">
            <div class="card shadow-none mb-0">
                <div class="px-3 py-2 d-flex flex-row align-items-center" id="search-top">
                    <i class="ti ti-search fs-22"></i>
                    <input type="search" class="form-control border-0" placeholder="Search...">
                    <button type="button" class="btn p-0" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x fs-22"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>