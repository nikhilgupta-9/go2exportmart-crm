<?php
function getAllDesignations($conn) {
    $data = [];

    $sql = "SELECT * FROM designations ORDER BY designation_name ASC";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }

    return $data;
}


function getAllServices($conn) {
    $services = [];

    $sql = "SELECT * FROM services ORDER BY sno DESC";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
    }

    return $services;
}
?>