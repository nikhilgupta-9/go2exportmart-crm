<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
}
include 'partials/_header.php';

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $company = $_POST['cust_company'];
    $address = $_POST['cust_address'];
    $service = $_POST['service'];
    $tprice = $_POST['tamount'];
    $customerID = $_POST['customerid'];
    $fprice = round($tprice, 0);
    $desc = $_POST['desc'];

    $cussql = "SELECT * FROM `customerleads` WHERE `sno` = '$customerID'";
    $cusresult = mysqli_query($conn, $cussql);
    $cusrow = mysqli_fetch_assoc($cusresult);
    $cuscomp = $cusrow['cust_company'];
    $cusadd = $cusrow['cust_address'];
    $cgst = $cusrow['GST'];
    $cpan = $cusrow['pan'];

    $serdet = "SELECT * FROM `services` WHERE `Service Name` = '$service'";
    $result_ser = mysqli_query($conn, $serdet);
    $row_ser = mysqli_fetch_assoc($result_ser);
    $price = $row_ser['price'];
    


    $gen_info = "SELECT * FROM `general_info`";
    $gen_result = mysqli_query($conn, $gen_info);
    $gen_row = mysqli_fetch_assoc($gen_result);

    $gname = $gen_row['name'];
    $logo = $gen_row['logo'];
    $gaddress = $gen_row['address'];
    $Ggst = $gen_row['gst'];
    $Gpan = $gen_row['pan'];
    $Ghsn = $gen_row['HSN'];



    $dprice = round($fprice / 1.18, 2);
    $Pgst = round(($fprice / 1.18) * 0.18 , 2);
    $date = date("M d, Y");



    $units = array(
        '', 'ONE', 'TWO', 'THREE', 'FOUR',
        'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'
    );

    $tens = array(
        '', 'TEN', 'TWENTY', 'THIRTY', 'FORTY',
        'FIFTY', 'SIXTY', 'SEVENTY', 'EIGHTY',
        'NINETY'
    );

    $special = array(
        'ELEVEN', 'TWELVE', 'THIRTEEN',
        'FOURTEEN', 'FIFTEEN', 'SIXTEEN',
        'SEVENTEEN', 'EIGHTEEN', 'NINTEEN'
    );

    $words = '';
    if ($fprice < 10) {
        $words = $units[$fprice];
    } elseif ($fprice < 20) {
        $words = $special[$fprice - 11];
    } elseif ($fprice < 100) {
        $words = $tens[(int)($fprice / 10)] . ' ' .  $units[$fprice % 10];
    } elseif ($fprice < 1000) {
        $hundreds = $units[(int)($fprice / 100)];
        $septen = $fprice - ((int)($fprice / 100) * 100);
        if ($septen < 10) {
            $words = $hundreds . ' HUNDRED ' . $units[$septen];
        } elseif ($septen < 20) {
            $words = $hundreds . ' HUNDRED ' . $special[$septen - 11];
        } elseif ($septen < 100) {
            $words = $hundreds . ' HUNDRED ' . $tens[(int)($septen / 10)] . ' ' .  $units[$septen % 10];
        }
    } else {
        $thousands = (int)($fprice / 1000);
        $hundreds = (int)(($fprice - $thousands * 1000) / 100);
        $septen = $fprice - $thousands * 1000 - $hundreds * 100;
        if ($thousands < 10) {
            if ($septen < 10) {
                $words = $units[$thousands] . ' THOUSAND ' . $units[$hundreds] . ' HUNDRED ' . $units[$septen];
            } elseif ($septen < 20) {
                $words = $units[$thousands] . ' THOUSAND ' . $units[$hundreds] . ' HUNDRED ' . $special[$septen - 11];
            } else {
                $words = $units[$thousands] . ' THOUSAND ' . $units[$hundreds] . ' HUNDRED ' . $tens[(int)($septen / 10)] . ' '. $units[$septen % 10];
            }
        } elseif ($thousands < 20) {
            if ($septen < 10) {
                $words = $special[$thousands - 11] . ' THOUSAND ' . $units[$hundreds] . ' HUNDRED ' . $units[$septen];
            } elseif ($septen < 20) {
                $words = $special[$thousands - 11] . ' THOUSAND ' . $units[$hundreds] . ' HUNDRED ' . $special[$septen - 11];
            } else {
                $words = $special[$thousands - 11] . ' THOUSAND ' . $units[$hundreds] . ' HUNDRED ' . $tens[(int)($septen / 10)] . ' '. $units[$septen % 10];
            }
        } else {
            if ($septen < 10) {
                $words = $tens[(int)($thousands) / 10] . ' ' . $units[$thousands % 10] . ' THOUSAND ' . $units[$hundreds] . ' HUNDRED ' . $units[$septen];
            } elseif ($septen < 20) {
                $words = $tens[(int)($thousands) / 10] . ' ' . $units[$thousands % 10] . ' THOUSAND ' . $units[$hundreds] . ' HUNDRED ' . $special[$septen - 11];
            } else {
                $words = $tens[(int)($thousands) / 10] . ' ' . $units[$thousands % 10] . ' THOUSAND ' . $units[$hundreds] . ' HUNDRED ' . $tens[(int)($septen / 10)] . ' '. $units[$septen % 10];
            }
        }
    }
}

$getino = $conn->query("SELECT * FROM `invoice_gen` ORDER BY `sno` DESC LIMIT 1;")->fetch_assoc();
$initial = $getino['initial'];
$ino = $getino['ino'];

$proforma_invoice =  '
<div class="container-prof" id="container-prof">
    <div class="row">
        <div class="col-6">
            <h2 class="head">Proforma Invoice</h2>
            <p>Invoice No# <strong>'. $initial . $ino .'</strong></p>
            <p>Invoice Date# <strong>'.$date.'</strong></p>
        </div>
        <div class="col-6">
            <div class="per-logo text-end">
                <img src="assets/images/digi_logo.png" alt="Logo">
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-6">
            <div class="pro-info">
                <h4 class="head">Billed by</h4>
                <strong>'.$gname.'</strong>
                <p> '.$gaddress.'</p>
                <p><strong>GSTIN:</strong> '.$Ggst.'<br>
                <strong>PAN:</strong> '.$Gpan.'</p>
            </div>
        </div>
        <div class="col-6">
            <div class="pro-info">
                <h4 class="head">Billed to</h4>
                <strong>' . $cuscomp . '</strong>
                <p>' . $cusadd . '</p>
                <p><strong>GSTIN:</strong> '.$cgst.'<br>
                <strong>PAN:</strong> '.$cpan.'</p>
                </div>
                </div>
                <table class="pro-tab">
                    <thead class="pro-head text-center">
                        <tr>
                            <th><span class="text-center">Sno</span></th>
                            <th><span class="pero-span">Item</span></th>
                            <th><span class="pero-span">HSN</span></th>
                            <th><span class="pero-span">GST Rate</span></th>
                            <th><span class="pero-span">MRP</span></th>
                            <th><span class="pero-span">Amount</span></th>
                            <th><span class="pero-span">IGST</span></th>
                            <th><span class="pero-span">Total</span></th>
                        </tr>
                    </thead>
                    <tbody class="pro-body text-center">
                        <tr>
                            <td>1</td>
                            <td><div>' . $service . '</div>
                            </td>
                            <td>' . $Ghsn . '</td>
                            <td>18%</td>
                            <td>&#8377;' . $price . '</td>
                            <td>&#8377;' . $dprice .'</td>
                            <td>&#8377;' . $Pgst . '</td>
                            <td>&#8377;' . $fprice . '</td>
                        </tr>
                    </tbody>
                </table>
                <div class="row mt-3">
                    <div class="col-8">
                    <p><strong>Total (in words: ) ' . $words . ' ONLY</strong></p>
                        <strong>Details/ Description: </strong>
                    <p>'. $desc .'</p>
                        <div class="bank-con">
                        <p class="mt-4">
                            <strong>Bank Details: </strong>
                        </p>
                        <p>Bank Name: Axis Bank Limited<br>
                            Account Name: Digi Global Web Solutions<br>
                            Account Number: 922020023157346<br>
                            IFSC: UTIB0002692<br>
                            Account Type: Current Account
                            Branch: Qutub Plaza, Gurugram
                        </p>
                    </div>
                </div>
                <div class="col-4">
                    <div class="breakup">
                        <div class="row">
                            <div class="text-start col-6">
                                <p>Amount</p>
                                <p>IGST</p>
                            </div>  
                            <div class="text-end col-6">
                                <p>&#8377;' . $dprice . '</p>
                                <p>&#8377;' . $Pgst .'</p>
                            </div>
                            <hr>
                            <div class="col-6">
                                <h6 class="mb-3">Total (INR)</h6>
                            </div>
                            <div class="col-6">
                                <h6 class="text-end">&#8377;' . $fprice . '</h6>
                            </div>
                            <hr>
                        </div>
                    </div>
                </div>
                <p class="head head-t">Terms & Conditions</p>
                1. All Cheque/DD are to be made in f/o Digi Global web solutions.<br>
                2. Payment Against Delivery of Services.<br>
                3. After Received payment will not be returned. For any issue Please inform within 24 hours.<br>
                4. Disputes, If any are subject to Delhi jurisdiction only.<br>
            </div>
        </div>
        <div class="footer-prof">
        <span>This is an electronically generated document, no signature is required.
        </span>
            <span>Copyright &copy; '. date("Y") .', Digi Global Web Solutions </span>
        </div>
    </div>
    ';
    $len = 5;
    $inc = ++$ino;
    $newino = str_pad($ino, $len, '0', STR_PAD_LEFT);
    $setNewino = $conn->query("INSERT INTO `invoice_gen` (`initial`, `ino`) VALUES ('$initial', '$newino')");

        ?>

<?php
    echo $proforma_invoice;
?>
<script src="assets\lib\html2pdf.js-master\html2pdf.js-master\dist\html2pdf.bundle.min.js"></script>
<script>
    var proforma = document.getElementById('container-prof');
    html2pdf(proforma);
</script>

<?php
    include 'partials/_footer.php';
?>