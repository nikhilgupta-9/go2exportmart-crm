<?php
session_start();
include_once "partials/_dbconnect.php";  // make sure DB connection here

if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Secure query (IMPORTANT)
$stmt = $conn->prepare("SELECT * FROM employees WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$info_row = $stmt->get_result()->fetch_assoc();

$name = $info_row['user_name'];

// Fetch general info
$gen_info = $conn->query("SELECT * FROM general_info LIMIT 1")->fetch_assoc();

// Update logic
if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $name    = $_POST['name'];
    $mail    = $_POST['email'];
    $address = $_POST['address'];
    $gst     = $_POST['gst'];
    $pan     = $_POST['pan'];
    $hsn     = $_POST['HSN'];

    $stmt = $conn->prepare("UPDATE general_info SET name=?, email=?, address=?, gst=?, pan=?, HSN=?");
    $stmt->bind_param("ssssss", $name, $mail, $address, $gst, $pan, $hsn);
    $stmt->execute();

    header('location: general.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Company Settings | GO2EXPORT MART</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once "includes/link.php"; ?>
</head>

<body>

<div class="main-wrapper">

    <?php include_once "includes/header.php"; ?>
    <?php include_once "includes/sidebar.php"; ?>

    <div class="page-wrapper">
        <div class="content">

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">Company Settings</h4>
            </div>

            <!-- Welcome Box -->
            <div class="welcome-wrap mb-4">
                <div class="bg-dark rounded p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="text-white mb-1">Welcome, <?php echo $name; ?></h5>
                        <p class="text-light mb-0"><?php echo date("d M Y, l"); ?></p>
                    </div>
                </div>
            </div>

            <!-- FORM CARD -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Edit Company Details</h5>
                </div>

                <div class="card-body">
                    <form method="POST">

                        <div class="row">

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Company Name</label>
                                <input type="text" name="name" class="form-control"
                                    value="<?= $gen_info['name'] ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control"
                                    value="<?= $gen_info['email'] ?>">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control"
                                    value="<?= $gen_info['address'] ?>">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">GST</label>
                                <input type="text" name="gst" class="form-control"
                                    value="<?= $gen_info['gst'] ?>">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">PAN</label>
                                <input type="text" name="pan" class="form-control"
                                    value="<?= $gen_info['pan'] ?>">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">HSN</label>
                                <input type="text" name="HSN" class="form-control"
                                    value="<?= $gen_info['HSN'] ?>">
                            </div>

                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                Save Changes
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>

        <?php include_once "includes/footer.php"; ?>
    </div>
</div>

<?php include_once "includes/footer-link.php"; ?>

</body>
</html>