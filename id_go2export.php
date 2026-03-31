<?php
session_start();
require_once 'partials/_dbconnect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('location: index.php');
    exit();
}

// Get employee ID from URL or session
$uid = isset($_GET['uid']) ? $_GET['uid'] : $_SESSION['user_id'];

// Fetch employee details
$stmt = $conn->prepare("SELECT * FROM employees WHERE user_id = ?");
$stmt->bind_param("s", $uid);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();

if (!$employee) {
    die("Employee not found.");
}

// Calculate tenure
$doj = new DateTime($employee['user_doj']);
$today = new DateTime();
$tenure = $doj->diff($today);

// Grade level mapping
$grade_names = [
    1 => 'Executive',
    2 => 'Senior Executive',
    3 => 'Team Lead',
    4 => 'Manager',
    5 => 'Senior Manager'
];
$grade_name = $grade_names[$employee['grade_level']] ?? 'Employee';

// Get profile image
$profile_image = !empty($employee['user_img']) ? 'assets/uploads/profiles/' . $employee['user_img'] : '';

// Company website for QR code
$company_website = "https://go2exportmart.com";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>GO2EXPORT MART - Digital ID Card</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            /* background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); */
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            padding: 20px;
        }
        
        /* Modern ID Card Container */
        .digital-id-card {
            max-width: 450px;
            width: 100%;
            background: white;
            border-radius: 32px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            transition: transform 0.3s ease;
            animation: fadeInUp 0.6s ease-out;
        }
        
        .digital-id-card:hover {
            transform: translateY(-5px);
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Card Header with Gradient */
        .card-header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            padding: 30px 20px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: shimmer 15s infinite linear;
        }
        
        @keyframes shimmer {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Company Logo */
        .company-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            padding: 8px 16px;
            border-radius: 100px;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .company-badge img {
            height: 28px;
            width: auto;
        }
        
        .company-badge span {
            color: white;
            font-weight: 600;
            font-size: 14px;
            letter-spacing: 0.5px;
        }
        
        /* Card Type Label */
        .card-type {
            display: inline-block;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            padding: 6px 20px;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            color: white;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        /* Employee Photo */
        .photo-section {
            position: relative;
            z-index: 1;
            margin-top: -50px;
            margin-bottom: 20px;
        }
        
        .photo-frame {
            width: 110px;
            height: 110px;
            margin: 0 auto;
            background: white;
            border-radius: 50%;
            padding: 3px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
        }
        
        .photo-frame img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }
        
        /* Employee Name */
        .employee-name {
            text-align: center;
            font-size: 22px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 5px;
        }
        
        .employee-designation {
            text-align: center;
            color: #f5576c;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 20px;
            letter-spacing: 0.5px;
        }
        
        /* Info Grid */
        .info-grid {
            padding: 0 20px 20px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-icon {
            width: 40px;
            color: #667eea;
            font-size: 20px;
        }
        
        .info-content {
            flex: 1;
        }
        
        .info-label {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 600;
            color: #94a3b8;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        
        .info-value {
            font-size: 15px;
            font-weight: 600;
            color: #1e293b;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #d4edda;
            color: #155724;
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
        }
        
        /* QR Code Section */
        .qr-section {
            background: #f8fafc;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        
        .qr-code {
            display: inline-block;
            background: white;
            padding: 10px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .qr-code img {
            width: 100px;
            height: 100px;
            display: block;
        }
        
        .qr-text {
            font-size: 10px;
            color: #64748b;
            margin-top: 8px;
        }
        
        /* Footer */
        .card-footer {
            background: white;
            padding: 15px 20px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        
        .signature-line {
            width: 120px;
            border-top: 1.5px solid #cbd5e1;
            margin: 5px auto 0;
        }
        
        /* Buttons */
        .action-buttons {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: flex;
            gap: 12px;
            z-index: 1000;
        }
        
        .action-btn {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: white;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #1e293b;
        }
        
        .action-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }
        
        .action-btn.print {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .action-btn.close {
            background: #ef4444;
            color: white;
        }
        
        /* Watermark */
        .watermark {
            position: absolute;
            bottom: 10px;
            right: 10px;
            opacity: 0.3;
            font-size: 10px;
            color: #94a3b8;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .action-buttons {
                display: none;
            }
            .digital-id-card {
                box-shadow: none;
                margin: 0 auto;
                page-break-inside: avoid;
            }
            .card-header {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .card-type {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .action-btn {
                display: none;
            }
        }
        
        @media (max-width: 480px) {
            .digital-id-card {
                margin: 20px auto;
            }
            .action-buttons {
                bottom: 10px;
                right: 10px;
            }
            .action-btn {
                width: 42px;
                height: 42px;
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="digital-id-card">
        <!-- Card Header -->
        <div class="card-header">
            <div class="company-badge">
                <img src="assets/img/logo-g2em.png" alt="GO2EXPORT MART" onerror="this.src='https://via.placeholder.com/28?text=G2E'">
                <span>GO2EXPORT MART</span>
            </div>
            <div class="card-type">
                <i class="ti ti-id-badge" style="font-size: 12px;"></i> EMPLOYEE ID CARD
            </div>
        </div>
        
        <!-- Photo Section -->
        <div class="photo-section">
            <div class="photo-frame">
                <?php if (!empty($profile_image) && file_exists($profile_image)): ?>
                    <img src="<?php echo $profile_image; ?>" alt="Employee Photo">
                <?php else: ?>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($employee['user_name']); ?>&background=667eea&color=fff&size=110&bold=true&length=2" alt="Employee Photo">
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Employee Details -->
        <div class="employee-name"><?php echo htmlspecialchars($employee['user_name']); ?></div>
        <div class="employee-designation"><?php echo htmlspecialchars($employee['user_role']); ?> | <?php echo $grade_name; ?></div>
        
        <!-- Info Grid -->
        <div class="info-grid">
            <div class="info-item">
                <div class="info-icon"><i class="ti ti-id"></i></div>
                <div class="info-content">
                    <div class="info-label">Employee ID</div>
                    <div class="info-value"><?php echo htmlspecialchars($employee['user_id']); ?></div>
                </div>
                <div class="status-badge">
                    <i class="ti ti-circle-check" style="font-size: 12px;"></i> Active
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon"><i class="ti ti-building"></i></div>
                <div class="info-content">
                    <div class="info-label">Department</div>
                    <div class="info-value"><?php echo htmlspecialchars($employee['department']); ?></div>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon"><i class="ti ti-calendar"></i></div>
                <div class="info-content">
                    <div class="info-label">Date of Joining</div>
                    <div class="info-value"><?php echo date('d M Y', strtotime($employee['user_doj'])); ?></div>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon"><i class="ti ti-phone"></i></div>
                <div class="info-content">
                    <div class="info-label">Contact Number</div>
                    <div class="info-value"><?php echo htmlspecialchars($employee['user_num']); ?></div>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon"><i class="ti ti-mail"></i></div>
                <div class="info-content">
                    <div class="info-label">Email Address</div>
                    <div class="info-value" style="font-size: 13px;"><?php echo htmlspecialchars($employee['user_mail']); ?></div>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon"><i class="ti ti-map-pin"></i></div>
                <div class="info-content">
                    <div class="info-label">Location</div>
                    <div class="info-value">India</div>
                </div>
            </div>
        </div>
        
        <!-- QR Code Section -->
        <div class="qr-section">
            <div class="qr-code">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?php echo urlencode($company_website); ?>" alt="Scan to visit website">
            </div>
            <div class="qr-text">
                <i class="ti ti-qrcode"></i> Scan QR to visit GO2EXPORT MART
            </div>
        </div>
        
        <!-- Footer with Signatures -->
        <div class="card-footer">
            
            <div style="margin-top: 12px;">
                <small style="font-size: 9px; color: #94a3b8;">
                    <i class="ti ti-calendar-due"></i> Valid up to: <?php echo date('d M Y', strtotime('+1 year')); ?>
                </small>
            </div>
        </div>
        
        <div class="watermark">
            GO2EXPORT MART
        </div>
    </div>
    
    <!-- Floating Action Buttons -->
    <div class="action-buttons">
        <button onclick="window.print()" class="action-btn print" title="Print / Save as PDF">
            <i class="ti ti-printer"></i>
        </button>
        <button onclick="window.close()" class="action-btn close" title="Close">
            <i class="ti ti-x"></i>
        </button>
    </div>
    
    <script>
        // Optional: Auto trigger print dialog (uncomment if needed)
        /*
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1000);
        }
        */
        
        // Add keyboard shortcut for print (Ctrl+P)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
    </script>
</body>
</html>