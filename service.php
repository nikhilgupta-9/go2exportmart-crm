<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Check if user has admin privileges (grade level = 1)
if (!isset($_SESSION['grade_level']) || $_SESSION['grade_level'] != 1) {
    header("Location: dashboard.php");
    exit;
}

include_once "partials/_dbconnect.php";
include_once "partials/_header.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Service Management | GO2EXPORT MART Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once "includes/link.php"; ?>
    
    <style>
        .service-card {
            transition: transform 0.2s, box-shadow 0.2s;
            margin-bottom: 20px;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .service-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 15px;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons .btn {
            margin: 0 3px;
        }
        
        .price-tag {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .custom-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideInRight 0.3s ease-out;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .service-description {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <?php include_once "includes/header.php"; ?>
        <?php include_once "includes/sidebar.php"; ?>

        <div class="page-wrapper">
            <div class="content pb-0">
                <!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-1">Service Management</h4>
                        <p class="text-muted mb-0">Create and manage all your services here</p>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                            <i class="ti ti-plus me-2"></i>Create New Service
                        </button>
                        <button onclick="refreshServices()" class="btn btn-icon btn-outline-light shadow">
                            <i class="ti ti-refresh"></i>
                        </button>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Services</h6>
                                        <h3 class="mb-0" id="totalServices">0</h3>
                                    </div>
                                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                                        <i class="ti ti-package fs-24 text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Active Services</h6>
                                        <h3 class="mb-0" id="activeServices">0</h3>
                                    </div>
                                    <div class="bg-success bg-opacity-10 p-3 rounded">
                                        <i class="ti ti-circle-check fs-24 text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Categories</h6>
                                        <h3 class="mb-0" id="totalCategories">0</h3>
                                    </div>
                                    <div class="bg-info bg-opacity-10 p-3 rounded">
                                        <i class="ti ti-category fs-24 text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Revenue</h6>
                                        <h3 class="mb-0" id="totalRevenue">0</h3>
                                    </div>
                                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                                        <i class="ti ti-currency-rupee fs-24 text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Services Grid -->
                <div class="row" id="servicesGrid">
                    <div class="col-12 text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- Add Service Modal -->
    <div class="modal fade" id="addServiceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addServiceForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Service Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="service_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price (₹) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="price" step="0.01" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category">
                                    <option value="">Select Category</option>
                                    <option value="Import Services">Import Services</option>
                                    <option value="Export Services">Export Services</option>
                                    <option value="Consulting">Consulting</option>
                                    <option value="Logistics">Logistics</option>
                                    <option value="Documentation">Documentation</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Duration</label>
                                <input type="text" class="form-control" name="duration" placeholder="e.g., 30 days, 1 year">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3" placeholder="Service description..."></textarea>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Features (One per line)</label>
                                <textarea class="form-control" name="features" rows="3" placeholder="24/7 Support&#10;Dedicated Manager&#10;Priority Processing"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Service Modal -->
    <div class="modal fade" id="editServiceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editServiceForm">
                    <input type="hidden" name="service_id" id="edit_service_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Service Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="service_name" id="edit_service_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price (₹) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="price" id="edit_price" step="0.01" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category" id="edit_category">
                                    <option value="">Select Category</option>
                                    <option value="Import Services">Import Services</option>
                                    <option value="Export Services">Export Services</option>
                                    <option value="Consulting">Consulting</option>
                                    <option value="Logistics">Logistics</option>
                                    <option value="Documentation">Documentation</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Duration</label>
                                <input type="text" class="form-control" name="duration" id="edit_duration">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="edit_status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Features</label>
                                <textarea class="form-control" name="features" id="edit_features" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteServiceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this service? This action cannot be undone.</p>
                    <input type="hidden" id="delete_service_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <?php include_once "includes/footer-link.php"; ?>

    <script>
        const AJAX_URL = 'service-actions.php';
        
        $(document).ready(function() {
            loadServices();
            loadStatistics();
        });

        function loadServices() {
            $.ajax({
                url: AJAX_URL,
                type: 'GET',
                data: { action: 'get_services' },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displayServices(response.data);
                    } else {
                        $('#servicesGrid').html('<div class="col-12 text-center py-5 my-2"><div class="alert alert-danger">' + response.message + '</div></div>');
                    }
                },
                error: function(xhr, status, error) {
                    $('#servicesGrid').html('<div class="col-12 text-center py-5"><div class="alert alert-danger">Error loading services: ' + error + '</div></div>');
                }
            });
        }

        function displayServices(services) {
            if (!services || services.length === 0) {
                $('#servicesGrid').html('<div class="col-12 text-center py-5"><div class="alert alert-info">No services found. Click "Create New Service" to get started.</div></div>');
                return;
            }

            let html = '';
            services.forEach(service => {
                const features = service.features ? service.features.split('\n').filter(f => f.trim()) : [];
                const statusClass = service.status === 'active' ? 'status-active' : 'status-inactive';
                const statusText = service.status === 'active' ? 'Active' : 'Inactive';
                
                html += `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card service-card shadow-sm h-100 ">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="service-icon bg-primary bg-opacity-10">
                                        <i class="ti ti-package fs-28 text-primary"></i>
                                    </div>
                                    <span class="status-badge ${statusClass}">${statusText}</span>
                                </div>
                                <h5 class="card-title mb-2">${escapeHtml(service.service_name)}</h5>
                                ${service.category ? `<p class="text-muted small mb-2">${escapeHtml(service.category)}</p>` : ''}
                                ${service.description ? `<p class="service-description">${escapeHtml(service.description.substring(0, 100))}${service.description.length > 100 ? '...' : ''}</p>` : ''}
                                <div class="price-tag mb-2">₹${parseFloat(service.price).toFixed(2)}</div>
                                ${service.duration ? `<div class="service-duration mb-3"><i class="ti ti-clock"></i> ${escapeHtml(service.duration)}</div>` : ''}
                                ${features.length > 0 ? `
                                    <div class="mt-3">
                                        <strong>Key Features:</strong>
                                        <ul class="feature-list mt-2">
                                            ${features.slice(0, 3).map(f => `<li><i class="ti ti-check"></i> ${escapeHtml(f)}</li>`).join('')}
                                            ${features.length > 3 ? `<li><small class="text-muted">+${features.length - 3} more features</small></li>` : ''}
                                        </ul>
                                    </div>
                                ` : ''}
                                <div class="action-buttons mt-3">
                                    <button class="btn btn-sm btn-outline-primary" onclick="editService(${service.sno})">
                                        <i class="ti ti-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteServicePrompt(${service.sno})">
                                        <i class="ti ti-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            $('#servicesGrid').html(html);
        }

        function loadStatistics() {
            $.ajax({
                url: AJAX_URL,
                type: 'GET',
                data: { action: 'get_statistics' },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#totalServices').text(response.data.total_services);
                        $('#activeServices').text(response.data.active_services);
                        $('#totalCategories').text(response.data.total_categories);
                        $('#totalRevenue').text('₹' + response.data.total_revenue);
                    }
                }
            });
        }

        // Add Service
        $('#addServiceForm').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: AJAX_URL,
                type: 'POST',
                data: $(this).serialize() + '&action=add_service',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#addServiceModal').modal('hide');
                        $('#addServiceForm')[0].reset();
                        showNotification('success', response.message);
                        loadServices();
                        loadStatistics();
                    } else {
                        showNotification('error', response.message);
                    }
                },
                error: function() {
                    showNotification('error', 'Error adding service');
                }
            });
        });

        // Edit Service
        function editService(serviceId) {
            $.ajax({
                url: AJAX_URL,
                type: 'GET',
                data: { action: 'get_service', id: serviceId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const service = response.data;
                        $('#edit_service_id').val(service.sno);
                        $('#edit_service_name').val(service.service_name);
                        $('#edit_price').val(service.price);
                        $('#edit_category').val(service.category || '');
                        $('#edit_duration').val(service.duration || '');
                        $('#edit_description').val(service.description || '');
                        $('#edit_features').val(service.features || '');
                        $('#edit_status').val(service.status || 'active');
                        $('#editServiceModal').modal('show');
                    } else {
                        showNotification('error', response.message);
                    }
                }
            });
        }

        $('#editServiceForm').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: AJAX_URL,
                type: 'POST',
                data: $(this).serialize() + '&action=update_service',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#editServiceModal').modal('hide');
                        showNotification('success', response.message);
                        loadServices();
                        loadStatistics();
                    } else {
                        showNotification('error', response.message);
                    }
                }
            });
        });

        // Delete Service
        function deleteServicePrompt(serviceId) {
            $('#delete_service_id').val(serviceId);
            $('#deleteServiceModal').modal('show');
        }

        function confirmDelete() {
            const serviceId = $('#delete_service_id').val();
            $.ajax({
                url: AJAX_URL,
                type: 'POST',
                data: { action: 'delete_service', id: serviceId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#deleteServiceModal').modal('hide');
                        showNotification('success', response.message);
                        loadServices();
                        loadStatistics();
                    } else {
                        showNotification('error', response.message);
                    }
                }
            });
        }

        function refreshServices() {
            loadServices();
            loadStatistics();
            showNotification('info', 'Refreshing services...');
        }

        function showNotification(type, message) {
            const notification = $(`
                <div class="custom-notification alert alert-${type === 'success' ? 'success' : (type === 'error' ? 'danger' : 'info')} alert-dismissible fade show" role="alert">
                    <i class="ti ti-${type === 'success' ? 'check-circle' : (type === 'error' ? 'alert-circle' : 'info-circle')} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);
            $('body').append(notification);
            setTimeout(function() {
                notification.fadeOut('slow', function() {
                    $(this).remove();
                });
            }, 3000);
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }
    </script>
    
    <style>
        .feature-list {
            list-style: none;
            padding-left: 0;
        }
        
        .feature-list li {
            padding: 5px 0;
            border-bottom: 1px solid #eef2f6;
        }
        
        .feature-list li:last-child {
            border-bottom: none;
        }
        
        .feature-list li i {
            color: #28a745;
            margin-right: 8px;
        }
        
        .service-duration {
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</body>
</html>