<?php
$id = $_SESSION['user_id'];
$ftch_info_sql = "SELECT * FROM `employees` WHERE `user_id` = '$id'";
$result_info = mysqli_query($conn, $ftch_info_sql);
$row_info = mysqli_fetch_assoc($result_info);
$grade = $row_info['grade_level'];

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar" id="sidebar">

    <!-- Start Logo -->
    <div class="sidebar-logo">
        <div>
            <!-- Logo Normal -->
            <a href="dashboard.php" class="logo logo-normal p-1">
                <img src="assets/img/logo-g2em.png" alt="Logo" style="max-width: 80%;">
            </a>

            <!-- Logo Small -->
            <a href="dashboard.php" class="logo-small">
                <img src="assets/img/logo-small.svg" alt="Logo">
            </a>

            <!-- Logo Dark -->
            <a href="dashboard.php" class="dark-logo">
                <img src="assets/img/logo-white.svg" alt="Logo">
            </a>
        </div>
        <button class="sidenav-toggle-btn btn border-0 p-0 active" id="toggle_btn">
            <i class="ti ti-arrow-bar-to-left"></i>
        </button>
        <!-- Sidebar Menu Close -->
        <button class="sidebar-close">
            <i class="ti ti-x align-middle"></i>
        </button>
    </div>
    <!-- End Logo -->

    <!-- Sidenav Menu -->
    <div class="sidebar-inner" data-simplebar>
        <div id="sidebar-menu" class="sidebar-menu">
            <ul>

                <!-- ── MAIN MENU ── -->
                <li class="menu-title"><span>Main Menu</span></li>
                <li>
                    <?php
                    switch ($grade) {
                        case 1:
                            $dashboard_url = 'admin-dashboard.php';
                            break;
                        case 2:
                            $dashboard_url = 'manager-dashboard.php';
                            break;
                        case 3:
                            $dashboard_url = 'teamlead-dashboard.php';
                            break;
                        case 4:
                            $dashboard_url = 'dashboard-sales.php';
                            break;
                        default:
                            $dashboard_url = 'dashboard.php';
                            break;
                    }
                    ?>
                    <a href="<?php echo $dashboard_url; ?>" class="<?= $current_page === $dashboard_url ? 'active' : '' ?>">
                        <i class="ti ti-dashboard"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <!-- ── INFO ── (grade-based) -->
                <?php if ($grade < 2 || $grade == 3): ?>
                    <li class="menu-title"><span>Info</span></li>
                    <li>
                        <ul>
                            <li>
                                <a href="info.php" class="<?= $current_page === 'info.php' ? 'active' : '' ?>">
                                    <i class="ti ti-user-circle"></i>
                                    <span>My Profile</span>
                                </a>
                            </li>

                            <?php if ($grade < 2): ?>
                                <li>
                                    <a href="daily-activity.php" class="<?= $current_page === 'daily-activity.php' ? 'active' : '' ?>">
                                        <i class="ti ti-building-community"></i>
                                        <span>Daily Performance</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="general.php" class="<?= $current_page === 'general.php' ? 'active' : '' ?>">
                                        <i class="ti ti-building-community"></i>
                                        <span>Company Info</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="employee.php" class="<?= $current_page === 'employee.php' ? 'active' : '' ?>">
                                        <i class="ti ti-users"></i>
                                        <span>Employees</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="new_emp.php" class="<?= $current_page === 'new_emp.php' ? 'active' : '' ?>">
                                        <i class="ti ti-user-plus"></i>
                                        <span>Create New Employee</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="service.php" class="<?= $current_page === 'service.php' ? 'active' : '' ?>">
                                        <i class="ti ti-briefcase"></i>
                                        <span>Service Management</span>
                                    </a>
                                </li>
                            <?php elseif ($grade == 3): ?>
                                <li>
                                    <a href="employee.php" class="<?= $current_page === 'employee.php' ? 'active' : '' ?>">
                                        <i class="ti ti-users"></i>
                                        <span>Employees</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Still show My Profile for all grades -->
                    <li class="menu-title"><span>Info</span></li>
                    <li>
                        <ul>
                            <li>
                                <a href="info.php" class="<?= $current_page === 'info.php' ? 'active' : '' ?>">
                                    <i class="ti ti-user-circle"></i>
                                    <span>My Profile</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>

                <!-- ── TEAM MANAGEMENT ── (grade < 4 only) -->
                <?php if ($grade < 4): ?>
                    <li class="menu-title"><span>Team Management</span></li>
                    <li>
                        <ul>
                            <li>
                                <a href="team.php" class="<?= $current_page === 'team.php' ? 'active' : '' ?>">
                                    <i class="ti ti-users-group"></i>
                                    <span>My Team</span>
                                </a>
                            </li>
                            <?php if ($grade < 3): ?>
                                <li>
                                    <a href="team-alignment.php" class="<?= $current_page === 'team-alignment.php' ? 'active' : '' ?>">
                                        <i class="ti ti-layout-distribute-horizontal"></i>
                                        <span>Team Alignment</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?>

                <!-- ── LEADS ── -->
                <li class="menu-title"><span>Leads</span></li>
                <li>
                    <ul>
                        <li>
                            <a href="lead.php" class="<?= $current_page === 'lead.php' && !isset($_GET['leadType']) ? 'active' : '' ?>">
                                <i class="ti ti-chart-arcs"></i>
                                <span>All Leads</span>
                            </a>
                        </li>
                        <li>
                            <a href="allocated-leads.php"
                                class="<?= ($current_page === 'allocated-leads.php') ? 'active' : '' ?>">
                                <i class="ti ti-chart-bar"></i>
                                <span>Meta Leads</span>
                            </a>
                        </li>
                        <li>
                            <a href="create_lead.php" class="<?= $current_page === 'create_lead.php' ? 'active' : '' ?>">
                                <i class="ti ti-plus"></i>
                                <span>Create New Lead</span>
                            </a>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0);"
                                class="<?= isset($_GET['leadType']) ? 'subdrop' : '' ?>">
                                <i class="ti ti-filter"></i>
                                <span>Lead Types</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li>
                                    <a href="lead.php?leadType=Fresh+Lead"
                                        class="<?= ($_GET['leadType'] ?? '') === 'Fresh Lead' ? 'active' : '' ?>">
                                        <i class="ti ti-droplet"></i> Fresh Leads
                                    </a>
                                </li>
                                <li>
                                    <a href="lead.php?leadType=Follow+Up"
                                        class="<?= ($_GET['leadType'] ?? '') === 'Follow Up' ? 'active' : '' ?>">
                                        <i class="ti ti-clock"></i> Follow Up Leads
                                    </a>
                                </li>
                                <li>
                                    <a href="lead.php?leadType=Positive"
                                        class="<?= ($_GET['leadType'] ?? '') === 'Positive' ? 'active' : '' ?>">
                                        <i class="ti ti-thumb-up"></i> Positive
                                    </a>
                                </li>
                                <li>
                                    <a href="lead.php?leadType=Committed"
                                        class="<?= ($_GET['leadType'] ?? '') === 'Committed' ? 'active' : '' ?>">
                                        <i class="ti ti-check"></i> Committed
                                    </a>
                                </li>
                                <li>
                                    <a href="lead.php?leadType=Not+Interested"
                                        class="<?= ($_GET['leadType'] ?? '') === 'Not Interested' ? 'active' : '' ?>">
                                        <i class="ti ti-thumb-down"></i> Not Interested
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li>
                            <a href="matelize.php" class="<?= $current_page === 'matelize.php' ? 'active' : '' ?>">
                                <i class="ti ti-star"></i>
                                <span>Matelize</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- ── TARGETS ── -->
                <li class="menu-title"><span>Performance</span></li>
                <li>
                    <ul>
                        <li>
                            <a href="target.php" class="<?= $current_page === 'target.php' ? 'active' : '' ?>">
                                <i class="ti ti-target"></i>
                                <span>Target</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Sales Dashboard for Grade Level 4 -->
                <?php if ($grade == 4): ?>
                    <li class="menu-title"><span>Sales</span></li>
                    <li>
                        <ul>
                            <li>
                                <a href="dashboard-sales.php" class="<?= $current_page === 'dashboard-sales.php' ? 'active' : '' ?>">
                                    <i class="ti ti-chart-pie"></i>
                                    <span>My Dashboard</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>

                <!-- Settings Section (Only for Admin Grade 1) -->
                <?php if ($grade == 1): ?>
                    <li class="menu-title"><span>Settings</span></li>
                    <li>
                        <ul>
                            <li class="submenu">
                                <a href="javascript:void(0);">
                                    <i class="ti ti-settings-2"></i><span>System Settings</span><span class="menu-arrow"></span>
                                </a>
                                <ul>
                                    <li><a href="department-designation.php" class="<?= $current_page === 'department-designation.php' ? 'active' : '' ?>">Department & Designation</a></li>
                                    <li><a href="lead-allocation.php" class="<?= $current_page === 'lead-allocation.php' ? 'active' : '' ?>">Lead Allocation</a></li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>

                <!-- ── ACCOUNT ── -->
                <li class="menu-title"><span>Account</span></li>
                <li>
                    <ul>
                        <li>
                            <a href="logout.php">
                                <i class="ti ti-logout"></i>
                                <span>Logout</span>
                            </a>
                        </li>
                    </ul>
                </li>

            </ul>
        </div>
    </div>
    <!-- End Sidenav Menu -->

</div>
<style>
    .simplebar-scrollbar:before {
        background: #d83316ff;
    }
</style>
<script>
    document.addEventListener("DOMContentLoaded", function() {

        const toggleBtn = document.getElementById("toggle_btn");
        const sidebar = document.getElementById("sidebar");

        toggleBtn.addEventListener("click", function() {
            document.body.classList.toggle("mini-sidebar");

            // Mobile specific
            if (window.innerWidth < 991) {
                sidebar.classList.toggle("opened");
            }
        });

    });
</script>
<script>
    document.querySelectorAll(".submenu > a").forEach(link => {
        link.addEventListener("click", function(e) {
            e.preventDefault();
            let submenu = this.nextElementSibling;

            if (submenu.style.display === "block") {
                submenu.style.display = "none";
            } else {
                submenu.style.display = "block";
            }
        });
    });
</script>