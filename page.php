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
                <!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-1">Dashboard</h4>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
                        <div id="reportrange" class="reportrange-picker d-flex align-items-center shadow">
                            <i class="ti ti-calendar-due text-dark fs-14 me-1"></i><span class="reportrange-picker-field">9 Jun 25 - 9 Jun 25</span>
                        </div>
                        <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Refresh" data-bs-original-title="Refresh"><i class="ti ti-refresh"></i></a>
                        <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Collapse" data-bs-original-title="Collapse" id="collapse-header"><i class="ti ti-transition-top"></i></a>
                    </div>
                </div>
                <!-- End Page Header -->

                <!-- Start Welcome Wrap -->
                <div class="welcome-wrap mb-4">
                    <div class=" d-flex align-items-center justify-content-between flex-wrap gap-3 bg-dark rounded p-4">
                        <div>
                            <h2 class="mb-1 text-white fs-24">Welcome Back, Adrian</h2>
                            <p class="text-light fs-14 mb-0">14 New Companies Subscribed Today !!!</p>
                        </div>
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <a href="company.html" class="btn btn-danger btn-sm">Companies</a>
                            <a href="packages.html" class="btn btn-light btn-sm">All Packages</a>
                        </div>
                    </div>
                </div>
                <!-- Endc Welcome Wrap -->
            </div>
            <?php include_once "includes/footer.php" ?>
        </div>
    </div>
    <?php include_once "includes/footer-link.php" ?>

</body>

</html>