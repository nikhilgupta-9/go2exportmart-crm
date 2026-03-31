<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['grade_level']) || $_SESSION['grade_level'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

include_once "partials/_dbconnect.php";

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

switch($action) {
    case 'add_service':
        addService($conn);
        break;
    case 'get_services':
        getServices($conn);
        break;
    case 'get_service':
        getService($conn);
        break;
    case 'update_service':
        updateService($conn);
        break;
    case 'delete_service':
        deleteService($conn);
        break;
    case 'get_statistics':
        getStatistics($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function addService($conn) {
    $service_name = mysqli_real_escape_string($conn, $_POST['service_name']);
    $price = floatval($_POST['price']);
    $category = isset($_POST['category']) ? mysqli_real_escape_string($conn, $_POST['category']) : '';
    $duration = isset($_POST['duration']) ? mysqli_real_escape_string($conn, $_POST['duration']) : '';
    $description = isset($_POST['description']) ? mysqli_real_escape_string($conn, $_POST['description']) : '';
    $features = isset($_POST['features']) ? mysqli_real_escape_string($conn, $_POST['features']) : '';
    $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 'active';
    
    $sql = "INSERT INTO services (`Service Name`, price, category, duration, description, features, status, created_at) 
            VALUES ('$service_name', '$price', '$category', '$duration', '$description', '$features', '$status', NOW())";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true, 'message' => 'Service added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding service: ' . mysqli_error($conn)]);
    }
}

function getServices($conn) {
    $sql = "SELECT sno, `Service Name` as service_name, price, category, duration, description, features, status, created_at 
            FROM services ORDER BY sno DESC";
    $result = mysqli_query($conn, $sql);
    
    $services = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $services[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $services]);
}

function getService($conn) {
    $id = intval($_GET['id']);
    $sql = "SELECT sno, `Service Name` as service_name, price, category, duration, description, features, status 
            FROM services WHERE sno = $id";
    $result = mysqli_query($conn, $sql);
    
    if ($row = mysqli_fetch_assoc($result)) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Service not found']);
    }
}

function updateService($conn) {
    $id = intval($_POST['service_id']);
    $service_name = mysqli_real_escape_string($conn, $_POST['service_name']);
    $price = floatval($_POST['price']);
    $category = isset($_POST['category']) ? mysqli_real_escape_string($conn, $_POST['category']) : '';
    $duration = isset($_POST['duration']) ? mysqli_real_escape_string($conn, $_POST['duration']) : '';
    $description = isset($_POST['description']) ? mysqli_real_escape_string($conn, $_POST['description']) : '';
    $features = isset($_POST['features']) ? mysqli_real_escape_string($conn, $_POST['features']) : '';
    $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 'active';
    
    $sql = "UPDATE services SET 
            `Service Name` = '$service_name',
            price = '$price',
            category = '$category',
            duration = '$duration',
            description = '$description',
            features = '$features',
            status = '$status'
            WHERE sno = $id";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true, 'message' => 'Service updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating service: ' . mysqli_error($conn)]);
    }
}

function deleteService($conn) {
    $id = intval($_POST['id']);
    $sql = "DELETE FROM services WHERE sno = $id";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true, 'message' => 'Service deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting service: ' . mysqli_error($conn)]);
    }
}

function getStatistics($conn) {
    // Total services
    $total_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM services");
    $total_services = mysqli_fetch_assoc($total_result)['total'];
    
    // Active services
    $active_result = mysqli_query($conn, "SELECT COUNT(*) as active FROM services WHERE status = 'active'");
    $active_services = mysqli_fetch_assoc($active_result)['active'];
    
    // Total categories
    $cat_result = mysqli_query($conn, "SELECT COUNT(DISTINCT category) as total FROM services WHERE category IS NOT NULL AND category != ''");
    $total_categories = $cat_result ? mysqli_fetch_assoc($cat_result)['total'] : 0;
    
    // Total revenue (sum of all service prices)
    $revenue_result = mysqli_query($conn, "SELECT SUM(price) as total FROM services");
    $total_revenue = $revenue_result ? mysqli_fetch_assoc($revenue_result)['total'] : 0;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_services' => $total_services,
            'active_services' => $active_services,
            'total_categories' => $total_categories,
            'total_revenue' => number_format($total_revenue, 2)
        ]
    ]);
}
?>