<?php
include '_dbconnect.php';

echo '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="datatables.css">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="./assets/js/jquery.js"></script>
    <script src="./assets/js/bootstrap.bundle.min.js"></script>
    <script src="datatables.js"></script>
    <script>
    $(document).ready(function(){
        $("#myTable").dataTable();
    });
    </script>
    <title>Go2Export Mart CRM</title>
</head>
<body>';


?>