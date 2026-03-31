<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header('location: index.php');
}

include 'partials/_dbconnect.php';



$user_id = $_SESSION['user_id'];
$info_ftch_sql = "SELECT * FROM `employees` WHERE `user_id` = '$user_id'";
$result_ftch_sql = mysqli_query($conn, $info_ftch_sql);
$info_row = mysqli_fetch_assoc($result_ftch_sql);
$name = $info_row['user_name'];
$target = $info_row['user_target'];
$achieved_sql = "SELECT * FROM `customerleads` WHERE `matelize` = '1' AND `assigned_to` = '$user_id'";
$achieved_result = mysqli_query($conn, $achieved_sql);
$achieved = 0;
while($row = mysqli_fetch_assoc($achieved_result)){
    $revenue = $row['amount'];
    $achieved += $revenue;
}

$achieved_percent = ($target > 0) ? ($achieved/$target) * 100 : 0;
$remaining = $target - $achieved;
$today = date("d");
$monthdays =  date("t");
$daysleft = $monthdays - $today;
$sunday = floor($daysleft/7);
$businessDays = $daysleft - $sunday;
if($businessDays == 0){
    $businessDays = 1;
}
$per_day_target = round($remaining/$businessDays);
$current_per_day = ($today > 0) ? round($achieved/$today) : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Meta Tags -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Dashboard | GO2EXPORT MART</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Streamline your business with our advanced CRM template. Easily integrate and customize to manage sales, support, and customer interactions efficiently. Perfect for any business size">
    <meta name="keywords" content="Advanced CRM template, customer relationship management, business CRM, sales optimization, customer support software, CRM integration, customizable CRM, business tools, enterprise CRM solutions">
    <meta name="author" content="Dreams Technologies">
    <meta name="robots" content="index, follow">
    <?php
    include_once "includes/link.php";
    ?>

</head>

<body>
    <a href="https://crms.dreamstechnologies.com/cdn-cgi/content?id=I.QOop4fCF0MfA9GSZkaG6tWvTnIbqPO9t1EFWKtOaA-1774786649.9122906-1.0.1.1-zcccNezd9_brSgS3.MpxHIwnl2hxQUUEsR.nQW0cIdQ" aria-hidden="true" rel="nofollow noopener" style="display: none !important; visibility: hidden !important"></a>

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
                            <button type="button" class="btn p-0" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x fs-22"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include_once "includes/sidebar.php"; ?>

        <div class="page-wrapper">
            <div class="content pb-0">
        <!-- Header Section with Greeting and Date -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <div class="d-flex align-items-center">
                <i id="tool_hide" class="fas fa-bars fs-3 me-3 text-primary" style="cursor: pointer;"></i>
                <h3 class="fw-bold text-dark mb-0">Welcome back, <?php echo $name; ?>!</h3>
            </div>
            <div class="mt-2 mt-sm-0">
                <span class="badge bg-light text-dark p-3 rounded-pill shadow-sm">
                    <i class="far fa-calendar-alt me-2 text-primary"></i>
                    <?php echo date("d-m-Y, l"); ?>
                </span>
            </div>
        </div>

        <!-- Quote Section -->
        <div class="card bg-primary text-white mb-4 border-0 shadow" style="background: linear-gradient(135deg, #0a3d62 0%, #3c6382 100%);">
            <div class="card-body py-4 text-center">
                <i class="fas fa-quote-left me-2 opacity-50"></i>
                <h4 class="card-title mb-2 fst-italic">“Goal setting is the secret to a compelling future.”</h4>
                <p class="card-text mb-0 opacity-75"><i class="fas fa-star-of-life me-1 small"></i> — Tony Robbins</p>
            </div>
        </div>

        <!-- Stats Cards Row -->
        <div class="row g-4 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted text-uppercase fw-semibold mb-2">My Target</h6>
                                <h2 class="fw-bold mb-0 text-primary">₹<?php echo number_format($target); ?></h2>
                            </div>
                            <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-bullseye fa-2x text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted text-uppercase fw-semibold mb-2">Achieved</h6>
                                <h2 class="fw-bold mb-0 text-success">₹<?php echo number_format($achieved); ?></h2>
                            </div>
                            <div class="bg-success bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-trophy fa-2x text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted text-uppercase fw-semibold mb-2">Remaining</h6>
                                <h2 class="fw-bold mb-0 text-warning">₹<?php echo number_format($remaining); ?></h2>
                            </div>
                            <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-chart-line fa-2x text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted text-uppercase fw-semibold mb-2">Business Days Left</h6>
                                <h2 class="fw-bold mb-0 text-info"><?php echo $businessDays; ?> Days</h2>
                            </div>
                            <div class="bg-info bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-calendar-day fa-2x text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Row -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-4 pb-0">
                        <h5 class="fw-bold mb-0"><i class="fas fa-chart-simple me-2 text-primary"></i> Target Progress</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="fw-semibold">Achievement Rate</span>
                                <span class="fw-bold text-primary"><?php echo round($achieved_percent, 1); ?>%</span>
                            </div>
                            <div class="progress" style="height: 12px; border-radius: 10px;">
                                <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" 
                                     style="width: <?php echo $achieved_percent; ?>%; border-radius: 10px;">
                                </div>
                            </div>
                        </div>
                        <div class="row mt-4 text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <p class="text-muted mb-1 small">Per Day Target</p>
                                    <h4 class="fw-bold mb-0 text-primary">₹<?php echo number_format($per_day_target); ?></h4>
                                </div>
                            </div>
                            <div class="col-6">
                                <div>
                                    <p class="text-muted mb-1 small">Current Per Day Achievement</p>
                                    <h4 class="fw-bold mb-0 <?php echo ($current_per_day >= $per_day_target) ? 'text-success' : 'text-warning'; ?>">
                                        ₹<?php echo number_format($current_per_day); ?>
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-4 pb-0">
                        <h5 class="fw-bold mb-0"><i class="fas fa-chart-line me-2 text-primary"></i> Performance Insights</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="bg-light rounded-3 p-3 text-center h-100">
                                    <i class="fas fa-rocket fa-2x text-primary mb-2"></i>
                                    <h6 class="text-muted mb-1">Required Daily Rate</h6>
                                    <h3 class="fw-bold mb-0 text-primary">₹<?php echo number_format($per_day_target); ?></h3>
                                    <small class="text-muted">to hit monthly target</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-light rounded-3 p-3 text-center h-100">
                                    <i class="fas fa-gauge-high fa-2x <?php echo ($current_per_day >= $per_day_target) ? 'text-success' : 'text-warning'; ?> mb-2"></i>
                                    <h6 class="text-muted mb-1">Current Pace</h6>
                                    <h3 class="fw-bold mb-0 <?php echo ($current_per_day >= $per_day_target) ? 'text-success' : 'text-warning'; ?>">
                                        ₹<?php echo number_format($current_per_day); ?><span class="fs-6">/day</span>
                                    </h3>
                                    <small class="text-muted">
                                        <?php 
                                        if($current_per_day >= $per_day_target){
                                            echo "🏆 Above target!";
                                        } else {
                                            $gap = $per_day_target - $current_per_day;
                                            echo "Need ₹" . number_format($gap) . " more/day";
                                        }
                                        ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 pt-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small text-muted">
                                    <i class="fas fa-chart-simple me-1"></i> Target Completion Status
                                </span>
                                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill">
                                    <?php echo round($achieved_percent); ?>% Complete
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Stats Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-4">
                <h5 class="fw-bold mb-0"><i class="fas fa-chart-pie me-2 text-primary"></i> Detailed Performance Metrics</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><i class="fas fa-chart-line me-1"></i> Metric</th>
                                <th class="text-end">Value</th>
                                <th class="text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-semibold">Total Target</td>
                                <td class="text-end">₹<?php echo number_format($target); ?></td>
                                <td class="text-end"><span class="badge bg-secondary">Goal</span></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Total Achieved</td>
                                <td class="text-end text-success fw-bold">₹<?php echo number_format($achieved); ?></td>
                                <td class="text-end"><span class="badge bg-success">Completed</span></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Remaining Amount</td>
                                <td class="text-end text-warning fw-bold">₹<?php echo number_format($remaining); ?></td>
                                <td class="text-end"><span class="badge bg-warning">To Achieve</span></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Business Days Left</td>
                                <td class="text-end fw-bold"><?php echo $businessDays; ?> Days</td>
                                <td class="text-end"><span class="badge bg-info">Working Days</span></td>
                            </tr>
                            <tr class="table-light">
                                <td class="fw-semibold">Required Per Day Target</td>
                                <td class="text-end fw-bold text-primary">₹<?php echo number_format($per_day_target); ?>/day</td>
                                <td class="text-end"><i class="fas fa-bullhorn text-primary"></i> Daily Goal</td>
                            </tr>
                            <tr class="table-light">
                                <td class="fw-semibold">Current Per Day Achievement</td>
                                <td class="text-end fw-bold <?php echo ($current_per_day >= $per_day_target) ? 'text-success' : 'text-warning'; ?>">
                                    ₹<?php echo number_format($current_per_day); ?>/day
                                </td>
                                <td class="text-end">
                                    <?php if($current_per_day >= $per_day_target): ?>
                                        <span class="badge bg-success"><i class="fas fa-check-circle"></i> On Track</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning"><i class="fas fa-exclamation-triangle"></i> Need Improvement</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Motivational Note -->
                <div class="alert alert-primary mt-4 mb-0 bg-primary bg-opacity-10 border-0">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-lightbulb fa-2x me-3 text-primary"></i>
                        <div>
                            <strong class="text-primary">💡 Pro Tip:</strong>
                            <?php 
                            $daily_needed = $per_day_target;
                            $current_daily = $current_per_day;
                            if($current_daily >= $daily_needed){
                                echo "Excellent momentum! Maintain this pace to exceed your monthly target. 🚀";
                            } else {
                                $gap_daily = $daily_needed - $current_daily;
                                echo "To reach your target, aim for ₹" . number_format($daily_needed) . " per day. You're just ₹" . number_format($gap_daily) . " away from your daily goal. Stay focused! 💪";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
            <?php include_once "includes/footer.php" ?>
        </div>
    </div>
    <?php include_once "includes/footer-link.php" ?>

</body>

</html>