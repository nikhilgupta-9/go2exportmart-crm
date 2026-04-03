<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Meta Tags -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Privacy Policy | GO2EXPORT MART</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Privacy Policy for GO2EXPORT MART employees">
    <meta name="keywords" content="privacy policy, data protection, employee privacy, GO2EXPORT MART">
    <meta name="author" content="GO2EXPORT MART">
    <?php
    include_once "../includes/link.php";
    ?>
    
    <style>
        :root {
            --primary-red: #dc3545;
            --primary-red-dark: #c82333;
            --primary-red-light: #f8d7da;
            --primary-red-bg: #fff5f5;
        }
        
        .policy-content {
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        
        .policy-section {
            margin-bottom: 30px;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 20px;
        }
        
        .policy-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .policy-section h3 {
            font-size: 20px;
            font-weight: 600;
            color: #dc3545;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .policy-section h3 i {
            color: #dc3545;
            font-size: 24px;
        }
        
        .policy-section h4 {
            font-size: 16px;
            font-weight: 600;
            color: #495057;
            margin: 15px 0 10px 0;
        }
        
        .policy-section p {
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 12px;
        }
        
        .policy-section ul, .policy-section ol {
            margin-bottom: 12px;
            padding-left: 20px;
        }
        
        .policy-section li {
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 8px;
        }
        
        .effective-date {
            background: var(--primary-red-bg);
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #dc3545;
        }
        
        .effective-date p {
            margin-bottom: 0;
            color: #495057;
        }
        
        .btn-outline-red {
            color: #dc3545;
            border-color: #dc3545;
            background: transparent;
        }
        
        .btn-outline-red:hover {
            background: #dc3545;
            color: white;
            border-color: #dc3545;
        }
        
        .print-btn {
            position: sticky;
            top: 20px;
            float: right;
            margin-left: 20px;
        }
        
        @media print {
            .sidebar, .header, .footer, .print-btn, .reportrange-picker, .btn-icon {
                display: none !important;
            }
            .page-wrapper {
                margin: 0 !important;
                padding: 0 !important;
            }
            .policy-content {
                box-shadow: none;
                padding: 0;
            }
            .policy-section {
                page-break-inside: avoid;
            }
        }
        
        .last-updated {
            color: #6c757d;
            font-size: 13px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            text-align: center;
        }
        
        .breadcrumb-item.active {
            color: #dc3545;
        }
        
        .breadcrumb-item a {
            color: #6c757d;
            text-decoration: none;
        }
        
        .breadcrumb-item a:hover {
            color: #dc3545;
        }
        
        .text-red {
            color: #dc3545 !important;
        }
        
        .bg-red-light {
            background-color: var(--primary-red-bg);
        }
        
        .card-red-border {
            border-top: 3px solid #dc3545;
        }
    </style>
</head>

<body>

    <!-- Begin Wrapper -->
    <div class="main-wrapper">


        <div class="page-wrapper">
            <div class="content pb-0">
                <!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-1">Privacy Policy</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Privacy Policy</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
                        <button onclick="window.print();" class="btn btn-outline-red print-btn">
                            <i class="ti ti-printer me-2"></i>Print Policy
                        </button>
                    </div>
                </div>
                <!-- End Page Header -->

                <!-- Privacy Policy Content -->
                <div class="policy-content">
                    <div class="effective-date">
                        <p><strong><i class="ti ti-calendar-clock me-2 text-red"></i>Effective Date:</strong> January 1, 2024</p>
                        <p><strong><i class="ti ti-file-text me-2 text-red"></i>Last Updated:</strong> <?php echo date("F d, Y"); ?></p>
                        <p class="mb-0"><strong><i class="ti ti-building me-2 text-red"></i>Applicable to:</strong> All employees, contractors, and authorized personnel of GO2EXPORT MART</p>
                    </div>

                    <div class="policy-section">
                        <h3><i class="ti ti-shield-lock"></i>1. Introduction</h3>
                        <p>GO2EXPORT MART ("Company," "we," "our," or "us") is committed to protecting the privacy and security of your personal information. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you access our Customer Relationship Management System (CRMS) and other company systems as an employee.</p>
                        <p>We value your trust and are dedicated to maintaining the confidentiality and security of your personal information. Please read this policy carefully to understand our practices regarding your personal data.</p>
                    </div>

                    <div class="policy-section">
                        <h3><i class="ti ti-database"></i>2. Information We Collect</h3>
                        <h4>2.1 Personal Information Provided by You</h4>
                        <ul>
                            <li><strong>Identification Data:</strong> Full name, employee ID, user ID, department, grade level, job title</li>
                            <li><strong>Contact Information:</strong> Email address, phone number, office extension</li>
                            <li><strong>Account Credentials:</strong> Username, password, security questions</li>
                            <li><strong>Professional Information:</strong> Employment history, skills, certifications, performance reviews</li>
                        </ul>
                        
                        <h4>2.2 Information Automatically Collected</h4>
                        <ul>
                            <li><strong>Usage Data:</strong> Login times, session duration, pages accessed, features used</li>
                            <li><strong>Device Information:</strong> IP address, browser type, operating system, device identifiers</li>
                            <li><strong>Location Data:</strong> Approximate geographic location based on IP address</li>
                            <li><strong>Activity Logs:</strong> Actions performed within the CRMS system, including data modifications and access records</li>
                        </ul>
                    </div>

                    <div class="policy-section">
                        <h3><i class="ti ti-chart-line"></i>3. How We Use Your Information</h3>
                        <p>We use the collected information for legitimate business purposes, including:</p>
                        <ul>
                            <li><strong>System Access & Authentication:</strong> To verify your identity and grant access to company systems</li>
                            <li><strong>Work Management:</strong> To assign tasks, track performance, and manage workflows</li>
                            <li><strong>Communication:</strong> To send important notices, updates, and system alerts</li>
                            <li><strong>Security & Compliance:</strong> To monitor system activity, detect unauthorized access, and ensure compliance with company policies</li>
                            <li><strong>Performance Evaluation:</strong> To assess work quality, productivity, and professional development</li>
                            <li><strong>Audit & Reporting:</strong> To generate reports for management, auditing, and regulatory compliance</li>
                            <li><strong>System Improvement:</strong> To analyze usage patterns and improve system functionality</li>
                        </ul>
                    </div>

                    <div class="policy-section">
                        <h3><i class="ti ti-share"></i>4. Data Sharing and Disclosure</h3>
                        <p>We may share your information in the following circumstances:</p>
                        <ul>
                            <li><strong>Internal Sharing:</strong> With managers, HR, IT, and other authorized personnel for legitimate business purposes</li>
                            <li><strong>Service Providers:</strong> With third-party vendors who assist in system operations, maintenance, and security (under confidentiality agreements)</li>
                            <li><strong>Legal Requirements:</strong> When required by law, court order, or government regulation</li>
                            <li><strong>Business Transfers:</strong> In connection with mergers, acquisitions, or restructuring</li>
                            <li><strong>Security Investigations:</strong> To investigate potential violations of company policies or security threats</li>
                        </ul>
                        <p>We do not sell your personal information to third parties for marketing purposes.</p>
                    </div>

                    <div class="policy-section">
                        <h3><i class="ti ti-lock"></i>5. Data Security</h3>
                        <p>We implement comprehensive security measures to protect your information:</p>
                        <ul>
                            <li><strong>Encryption:</strong> Data is encrypted during transmission (SSL/TLS) and at rest</li>
                            <li><strong>Access Controls:</strong> Role-based access control ensures only authorized personnel can view sensitive information</li>
                            <li><strong>Authentication:</strong> Multi-factor authentication for system access where applicable</li>
                            <li><strong>Monitoring:</strong> Continuous monitoring and logging of system activities</li>
                            <li><strong>Regular Audits:</strong> Periodic security assessments and vulnerability testing</li>
                            <li><strong>Employee Training:</strong> Regular security awareness training for all employees</li>
                        </ul>
                        <p>While we strive to protect your information, no security system is impenetrable. You are responsible for maintaining the confidentiality of your login credentials.</p>
                    </div>

                    <div class="policy-section">
                        <h3><i class="ti ti-clock"></i>6. Data Retention</h3>
                        <p>We retain your personal information for as long as necessary to fulfill the purposes outlined in this policy, including:</p>
                        <ul>
                            <li><strong>Active Employment:</strong> Throughout your employment period</li>
                            <li><strong>Post-Employment:</strong> As required by applicable laws and regulations (typically 3-7 years)</li>
                            <li><strong>Legal Requirements:</strong> For compliance with tax, labor, and other legal obligations</li>
                            <li><strong>Security Logs:</strong> Activity logs may be retained for security investigations</li>
                        </ul>
                        <p>Upon termination of employment, access to systems is immediately revoked, and data is archived according to retention policies.</p>
                    </div>

                    <div class="policy-section">
                        <h3><i class="ti ti-user-check"></i>7. Your Rights and Responsibilities</h3>
                        <h4>Employee Rights:</h4>
                        <ul>
                            <li>Access your personal information held by the company</li>
                            <li>Request correction of inaccurate or incomplete data</li>
                            <li>Request deletion of data subject to legal retention requirements</li>
                            <li>Receive information about how your data is used</li>
                            <li>Report privacy concerns without fear of retaliation</li>
                        </ul>
                        <h4>Employee Responsibilities:</h4>
                        <ul>
                            <li>Maintain confidentiality of your login credentials</li>
                            <li>Report any security incidents or suspicious activities immediately</li>
                            <li>Use company systems only for authorized business purposes</li>
                            <li>Comply with all company security policies and procedures</li>
                        </ul>
                    </div>

                    <div class="policy-section">
                        <h3><i class="ti ti-cookie"></i>8. Cookies and Tracking Technologies</h3>
                        <p>Our systems use cookies and similar technologies to:</p>
                        <ul>
                            <li>Maintain your session and authentication status</li>
                            <li>Remember your preferences and settings</li>
                            <li>Analyze system usage and performance</li>
                            <li>Enhance security and detect suspicious activities</li>
                        </ul>
                        <p>You may configure your browser to reject cookies, but this may affect system functionality.</p>
                    </div>

                    <div class="policy-section">
                        <h3><i class="ti ti-globe"></i>9. International Data Transfers</h3>
                        <p>GO2EXPORT MART operates globally, and your information may be transferred to and processed in countries where we have operations. We ensure appropriate safeguards are in place to protect your information.</p>
                    </div>

                    <div class="policy-section">
                        <h3><i class="ti ti-bell-ringing"></i>10. Changes to This Policy</h3>
                        <p>We may update this Privacy Policy from time to time to reflect changes in our practices or legal requirements. We will notify you of material changes through:</p>
                        <ul>
                            <li>Email notifications to your company email address</li>
                            <li>System announcements upon login</li>
                            <li>Updated "Last Updated" date at the top of this policy</li>
                        </ul>
                        <p>Continued use of company systems after changes constitutes acceptance of the updated policy.</p>
                    </div>

                    <div class="policy-section">
                        <h3><i class="ti ti-headset"></i>11. Contact Information</h3>
                        <p>If you have questions, concerns, or requests regarding this Privacy Policy or your personal information, please contact:</p>
                        <p><strong>Data Protection Officer</strong><br>
                        GO2EXPORT MART<br>
                        Email: privacy@go2exportmart.com<br>
                        Phone: +1 (555) 123-4567</p>
                        <p>For security incidents or urgent matters, please contact our IT Security Team at security@go2exportmart.com</p>
                    </div>

                    <div class="last-updated">
                        <p><i class="ti ti-heart text-danger me-1"></i> GO2EXPORT MART is committed to protecting your privacy and ensuring a secure working environment.</p>
                        <p class="mb-0">© <?php echo date("Y"); ?> GO2EXPORT MART. All rights reserved.</p>
                    </div>
                </div>
            </div>
            <?php include_once "../includes/footer.php" ?>
        </div>
    </div>
    <?php include_once "../includes/footer-link.php" ?>
    
    <script>
        $(document).ready(function() {
            // Add active class to sidebar menu
            $('.sidebar-menu a').each(function() {
                if ($(this).attr('href') === 'privacy-policy.php') {
                    $(this).addClass('active');
                    $(this).css('background', '#dc3545');
                    $(this).css('color', 'white');
                }
            });
        });
    </script>
</body>

</html>