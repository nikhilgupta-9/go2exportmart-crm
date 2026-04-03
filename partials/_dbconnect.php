<?php

// Detect environment (local vs live)
if ($_SERVER['SERVER_NAME'] == 'localhost') {
    // LOCAL SERVER CONFIG (XAMPP / WAMP)
    $servername = 'localhost';
    $username = 'root';
    $password = '';
    $dbname = 'my_crm_db';
} else {
    // LIVE SERVER CONFIG
    $servername = 'localhost';
    $username = 'u427250797_g2m_crm_db';
    $password = '1Xj#k3Lb9xc';
    $dbname = 'u427250797_g2m_crm_db';
}

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

?>