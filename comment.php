<?php
session_start();
include 'partials/_header.php';
if(!isset($_SESSION['user_id'])){
    header('location: index.php');
}


$user_id = $_SESSION['user_id'];
$info_ftch_sql = "SELECT * FROM `employees` WHERE `user_id` = '$user_id'";
$result_ftch_sql = mysqli_query($conn, $info_ftch_sql);
$info_row = mysqli_fetch_assoc($result_ftch_sql);
$name = $info_row['user_name'];
$customerID = $_POST['customerId'];
$ftch_cust_info_cmd = "SELECT * FROM `customerleads` WHERE `sno` = '$customerID'";
$cust_info_result = mysqli_query($conn, $ftch_cust_info_cmd);
$row_cust_info = mysqli_fetch_assoc($cust_info_result);
if($_SERVER['REQUEST_METHOD'] == "POST"){
    $status = $_POST['status'];
    $cust_num = $row_cust_info['customer_num'];
    $ftch_cmt_sql = "SELECT * FROM `comments` WHERE `cust_num` = '$cust_num'";
    $result_cmt = mysqli_query($conn, $ftch_cmt_sql);
}
?>
<div class="update">
    <h5>Congratulations! Naina Singh for achieving 150% of Target in May'2024</h5>
</div>
<div class="dashboard-container">
    <div id="tool-div" class="d-none tooldiv">
        <?php include 'partials/_tool.php'; ?>
    </div>
    <div class="container-fluid">
        <div class="dashboard">
            <div class="top-functions d-flex justify-content-between">
                <div class="name">
                    <h4><i id="tool_hide" class="fa-solid fa-bars me-3"></i>Welcome <?php echo $name; ?></h4>
                    <?php echo date("d-m-Y, l"); ?>
                </div>
            </div>
            <div class="container mt-3">
                <form action="add_comment.php" method="post">
                    <div class="row">
                        <div class="col-6 mb-2">
                            <label for="cust_company">Company</label>
                            <input type="text" class="form-control" name="cust_company"
                                value="<?php echo $row_cust_info['cust_company'] ?>" readonly>
                            <input type="hidden" class="form-control" name="cust_number"
                                value="<?php echo $cust_num; ?>" readonly>
                        </div>
                        <div class="col-6 mb-2">
                            <div>
                                <label for="meet_type">Meeting Type</label>
                            </div>
                            <select class="form-select" name="meet_type" id="meet_type">
                                <option value="text">Mail/Chat</option>
                                <option value="Call">Call</option>
                                <option value="both">Both</option>
                            </select>
                        </div>
                        <div class="col-6 mb-2">
                            <div>
                                <label for="status">Status</label>
                            </div>
                            <select class="form-select" name="status" id="status">
                                <option value="Fresh Lead">Fresh Lead</option>
                                <option value="Follow Up">Follow Up</option>
                                <option value="Positive">Positive</option>
                                <option value="Committed">Committed</option>
                                <option value="Not Interested">Not Interested</option>
                                <option value="Block">Block</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="comment" class="form-label">Comment</label>
                            <textarea class="form-control" id="comment" rows="3" name="comment" required></textarea>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-outline-success">Add Comment</button>
                        </div>
                    </div>
                </form>
                <div class="container-fluid mt-3">
                    <p>Previous Comments</p>
                    <table id="myTable" class="display">
                        <thead>
                            <tr>
                                <th class="text-start">Time Stamp</th>
                                <th>Comment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                        while($row_cmt = mysqli_fetch_assoc($result_cmt)){
                            $dtstamp = $row_cmt['dtstamp'];
                            $datesep = explode(' ', $dtstamp);
                            $fdate = $datesep[0];
                            $time = $datesep[1];
                            $ndateformat = date("d-M-Y", strtotime($fdate));
                            $newfulldate = $ndateformat.' '. $time;
                            echo '
                            <tr>
                        <td class="text-start">' . $newfulldate . '</td>
                        <td>' . $row_cmt['meet_type'] . ' / '.$row_cmt['comment']. '</td>
                        </tr>';
                    }
                    ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php
include 'partials/_footer.php';
?>