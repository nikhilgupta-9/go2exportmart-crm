<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header('location: index.php');
}

include 'partials/_header.php';
$user_id = $_SESSION['user_id'];
$info_row = $conn->query("SELECT * FROM `employees` WHERE `user_id` = '$user_id'")->fetch_assoc();
$name = $info_row['user_name'];

?>

<div class="dashboard-container">
    <div id="tool-div" class="d-none tooldiv">
        <?php include 'partials/_tool.php' ?>
    </div>
    <div class="container-fluid">
        <div class="dashboard">
            <h4><i id="tool_hide" class="fa-solid fa-bars me-3"></i>Welcome <?php echo $name; ?></h4>
            <?php echo date("d-m-Y, l"); ?>
            <div class="container-fluid mt-4">
                <table id="myTable" class="display">
                    <thead>
                        <th>Customer No.</th>
                        <th>Alternate No.</th>
                        <th>Customer Name</th>
                        <th>Company</th>
                        <th>Website</th>
                        <th>Address</th>
                        <th>State</th>
                        <th>History</th>
                    </thead>
                    <tbody>
                        <?php
                            $result_mat = $conn->query("SELECT * FROM `customerleads` WHERE `Aadhar` != '0' AND `Aadhar` != ''");
                            while($row_mat = mysqli_fetch_assoc($result_mat)){
                                echo '<tr>
                                        <td>'. $row_mat['customer_num'] .'</td>
                                        <td>'. $row_mat['alt_number'] .'</td>
                                        <td>'. $row_mat['customer_name'] .'</td>
                                        <td>'. $row_mat['cust_company'] .'</td>
                                        <td>'. $row_mat['website'] .'</td>
                                        <td>'. $row_mat['cust_address'] .'</td>
                                        <td>'. $row_mat['cust_state'] .'</td>
                                        <td><a href="'. $row_mat['sno'] .'"><i class="fa-solid fa-clock-rotate-left"></i></a></td>
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