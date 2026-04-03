<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Meta Tags -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Terms & Conditions | GO2EXPORT MART</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Terms and Conditions for GO2EXPORT MART employees">
    <meta name="keywords" content="terms and conditions, employment policies, company rules, GO2EXPORT MART">
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
        
        .terms-content {
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        
        .terms-section {
            margin-bottom: 30px;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 20px;
        }
        
        .terms-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .terms-section h3 {
            font-size: 20px;
            font-weight: 600;
            color: #dc3545;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .terms-section h3 i {
            color: #dc3545;
            font-size: 24px;
        }
        
        .terms-section h4 {
            font-size: 16px;
            font-weight: 600;
            color: #495057;
            margin: 15px 0 10px 0;
        }
        
        .terms-section p {
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 12px;
        }
        
        .terms-section ul, .terms-section ol {
            margin-bottom: 12px;
            padding-left: 20px;
        }
        
        .terms-section li {
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 8px;
        }
        
        .acknowledgment-section {
            background: var(--primary-red-bg);
            padding: 20px;
            border-radius: 12px;
            margin-top: 30px;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .signature-area {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px dashed #dc3545;
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
            .sidebar, .header, .footer, .print-btn {
                display: none !important;
            }
            .page-wrapper {
                margin: 0 !important;
                padding: 0 !important;
            }
            .terms-content {
                box-shadow: none;
                padding: 0;
            }
            .terms-section {
                page-break-inside: avoid;
            }
            .acknowledgment-section {
                page-break-before: avoid;
            }
        }
        
        .effective-date {
            background: var(--primary-red-bg);
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #dc3545;
        }
        
        .version-badge {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            margin-left: 10px;
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
        
        .btn-red {
            background: #dc3545;
            border-color: #dc3545;
            color: white;
        }
        
        .btn-red:hover {
            background: #c82333;
            border-color: #bd2130;
            color: white;
        }
        
        hr.red-hr {
            border-top: 2px solid #dc3545;
            opacity: 0.3;
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
                        <h4 class="mb-1">Terms & Conditions <span class="version-badge">Version 2.0</span></h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Terms & Conditions</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
                        <button onclick="window.print();" class="btn btn-outline-red print-btn">
                            <i class="ti ti-printer me-2"></i>Print Terms
                        </button>
                    </div>
                </div>
                <!-- End Page Header -->

                <!-- Terms & Conditions Content -->
                <div class="terms-content">
                    <div class="effective-date">
                        <p><strong><i class="ti ti-calendar-clock me-2 text-red"></i>Effective Date:</strong> January 1, 2024</p>
                        <p><strong><i class="ti ti-file-text me-2 text-red"></i>Last Revised:</strong> <?php echo date("F d, Y"); ?></p>
                        <p><strong><i class="ti ti-building me-2 text-red"></i>Applicable to:</strong> All employees, contractors, and temporary staff of GO2EXPORT MART</p>
                        <p class="mb-0"><strong><i class="ti ti-alert-circle me-2 text-red"></i>Important:</strong> By accessing and using GO2EXPORT MART systems, you agree to be bound by these Terms & Conditions.</p>
                    </div>

                    <div class="terms-section">
                        <h3><i class="ti ti-file-description"></i>1. Acceptance of Terms</h3>
                        <p>These Terms & Conditions ("Terms") constitute a legally binding agreement between you ("Employee," "you," or "your") and GO2EXPORT MART ("Company," "we," "our," or "us"). By accessing, using, or interacting with any company system, network, or resource, you acknowledge that you have read, understood, and agree to be bound by these Terms.</p>
                        <p>If you do not agree with any part of these Terms, you must immediately cease using all company systems and notify your supervisor and IT department.</p>
                    </div>

                    <div class="terms-section">
                        <h3><i class="ti ti-id-badge"></i>2. Employment Relationship</h3>
                        <h4>2.1 Employment Status</h4>
                        <p>These Terms supplement your employment agreement or contract. In case of conflict, your employment agreement takes precedence. These Terms do not create an employment contract and do not guarantee employment for any specific duration.</p>
                        
                        <h4>2.2 Eligibility</h4>
                        <p>To access company systems, you must:</p>
                        <ul>
                            <li>Be an active employee, contractor, or authorized personnel</li>
                            <li>Have valid login credentials issued by the company</li>
                            <li>Complete all required training and security acknowledgments</li>
                            <li>Maintain active status with HR and IT departments</li>
                        </ul>
                    </div>

                    <div class="terms-section">
                        <h3><i class="ti ti-login"></i>3. System Access and Security</h3>
                        <h4>3.1 Account Credentials</h4>
                        <ul>
                            <li>Your user ID and password are personal and non-transferable</li>
                            <li>You are responsible for maintaining the confidentiality of your credentials</li>
                            <li>Immediately report any unauthorized access or security incidents to IT Security</li>
                            <li>Do not share, record, or store passwords in insecure locations</li>
                            <li>Use strong passwords and change them as required by company policy</li>
                        </ul>
                        
                        <h4>3.2 Authorized Use</h4>
                        <p>Company systems are provided for legitimate business purposes only. You agree to:</p>
                        <ul>
                            <li>Use systems only within the scope of your job responsibilities</li>
                            <li>Not attempt to bypass security measures or access restricted areas</li>
                            <li>Not introduce malicious software, viruses, or harmful code</li>
                            <li>Not engage in activities that could compromise system integrity or availability</li>
                            <li>Not use company resources for personal gain or unauthorized purposes</li>
                        </ul>
                        
                        <h4>3.3 Monitoring and Privacy</h4>
                        <p>GO2EXPORT MART reserves the right to monitor, record, and review all system activities, including but not limited to:</p>
                        <ul>
                            <li>Login times, duration, and frequency</li>
                            <li>Applications accessed and actions performed</li>
                            <li>Data created, modified, or deleted</li>
                            <li>Network traffic and communications</li>
                            <li>Email and messaging content (where permitted by law)</li>
                        </ul>
                        <p>There is no expectation of privacy when using company systems and networks.</p>
                    </div>

                    <div class="terms-section">
                        <h3><i class="ti ti-copyright"></i>4. Intellectual Property and Confidentiality</h3>
                        <h4>4.1 Company Property</h4>
                        <p>All systems, software, data, documentation, and materials provided by GO2EXPORT MART are the exclusive property of the company. You have no ownership rights to any company intellectual property.</p>
                        
                        <h4>4.2 Work Product</h4>
                        <p>Any work product, inventions, improvements, or creations developed during your employment using company resources belong exclusively to GO2EXPORT MART.</p>
                        
                        <h4>4.3 Confidential Information</h4>
                        <p>You agree to protect and maintain the confidentiality of all company information, including:</p>
                        <ul>
                            <li>Customer data and lists</li>
                            <li>Business strategies and financial information</li>
                            <li>Trade secrets and proprietary processes</li>
                            <li>Employee information and personnel records</li>
                            <li>System architecture and security measures</li>
                        </ul>
                        <p>Confidentiality obligations continue after employment termination.</p>
                    </div>

                    <div class="terms-section">
                        <h3><i class="ti ti-device-laptop"></i>5. Acceptable Use Policy</h3>
                        <p>You agree to use company systems responsibly and ethically. Prohibited activities include:</p>
                        <ul>
                            <li><strong>Illegal Activities:</strong> Any activity violating local, state, federal, or international laws</li>
                            <li><strong>Harassment:</strong> Sending offensive, threatening, or discriminatory communications</li>
                            <li><strong>Copyright Infringement:</strong> Unauthorized use or distribution of copyrighted materials</li>
                            <li><strong>Excessive Personal Use:</strong> Using company resources for non-business purposes that impact productivity</li>
                            <li><strong>Data Breaches:</strong> Attempting to access, copy, or transfer unauthorized data</li>
                            <li><strong>Circumvention:</strong> Bypassing security controls or monitoring systems</li>
                        </ul>
                    </div>

                    <div class="terms-section">
                        <h3><i class="ti ti-device-floppy"></i>6. Data Management and Compliance</h3>
                        <h4>6.1 Data Accuracy</h4>
                        <p>You are responsible for ensuring all data you enter into company systems is accurate, complete, and up-to-date. Knowingly entering false or misleading information may result in disciplinary action.</p>
                        
                        <h4>6.2 Data Retention</h4>
                        <p>Follow company data retention policies. Do not retain data beyond required periods unless authorized for legitimate business purposes.</p>
                        
                        <h4>6.3 Regulatory Compliance</h4>
                        <p>You must comply with all applicable laws and regulations, including data protection laws (GDPR, CCPA, etc.), industry regulations, and company compliance requirements.</p>
                    </div>

                    <div class="terms-section">
                        <h3><i class="ti ti-report"></i>7. Incident Reporting</h3>
                        <p>You are required to report immediately:</p>
                        <ul>
                            <li>Suspected security breaches or vulnerabilities</li>
                            <li>Unauthorized access attempts</li>
                            <li>Data loss or corruption incidents</li>
                            <li>Violations of company policies</li>
                            <li>System errors or malfunctions</li>
                        </ul>
                        <p>Report to: IT Support Desk, your supervisor, or through the incident reporting system.</p>
                    </div>

                    <div class="terms-section">
                        <h3><i class="ti ti-gavel"></i>8. Disciplinary Actions</h3>
                        <p>Violation of these Terms may result in disciplinary action, up to and including:</p>
                        <ul>
                            <li>Verbal or written warnings</li>
                            <li>System access restrictions or suspension</li>
                            <li>Required retraining or remedial actions</li>
                            <li>Termination of employment or contract</li>
                            <li>Civil or criminal legal action where applicable</li>
                        </ul>
                        <p>The company reserves the right to suspend or terminate access immediately for serious violations without prior notice.</p>
                    </div>

                    <div class="terms-section">
                        <h3><i class="ti ti-shield"></i>9. Liability and Indemnification</h3>
                        <h4>9.1 Limitation of Liability</h4>
                        <p>GO2EXPORT MART is not liable for indirect, incidental, or consequential damages arising from system use, including data loss, business interruption, or security incidents beyond our reasonable control.</p>
                        
                        <h4>9.2 Indemnification</h4>
                        <p>You agree to indemnify and hold the company harmless from claims, damages, or expenses arising from your violation of these Terms, misuse of systems, or violation of applicable laws.</p>
                    </div>

                    <div class="terms-section">
                        <h3><i class="ti ti-rotate-clockwise"></i>10. Amendments and Updates</h3>
                        <p>GO2EXPORT MART reserves the right to modify these Terms at any time. Changes become effective upon posting or notification. Continued use of company systems constitutes acceptance of updated Terms. Material changes will be communicated through:</p>
                        <ul>
                            <li>Email notifications</li>
                            <li>System announcements upon login</li>
                            <li>Team meetings or training sessions</li>
                        </ul>
                    </div>

                    <div class="terms-section">
                        <h3><i class="ti ti-scale"></i>11. Governing Law</h3>
                        <p>These Terms are governed by the laws of the jurisdiction where GO2EXPORT MART is incorporated, without regard to conflict of law principles. Any disputes arising from these Terms shall be resolved in the appropriate courts of that jurisdiction.</p>
                    </div>

                    <div class="terms-section">
                        <h3><i class="ti ti-message-circle"></i>12. Contact and Support</h3>
                        <p>For questions, concerns, or clarification regarding these Terms, contact:</p>
                        <p><strong>Human Resources Department</strong><br>
                        Email: hr@go2exportmart.com<br>
                        Phone: +1 (555) 123-4567</p>
                        <p><strong>IT Support</strong><br>
                        Email: it-support@go2exportmart.com<br>
                        Phone: +1 (555) 123-4567</p>
                    </div>

                    <!-- Acknowledgment Section -->
                    <div class="acknowledgment-section">
                        <h4 class="mb-3 text-red"><i class="ti ti-check-circle text-danger me-2"></i>Employee Acknowledgment</h4>
                        <p>By accessing and using GO2EXPORT MART systems, I acknowledge that:</p>
                        <ul>
                            <li>I have read, understood, and agree to abide by these Terms & Conditions</li>
                            <li>I understand that system access is a privilege, not a right, and may be revoked for violations</li>
                            <li>I am responsible for protecting company information and resources</li>
                            <li>I will report any security incidents or policy violations immediately</li>
                            <li>I understand that system activities may be monitored as described in this policy</li>
                        </ul>
                        
                        <div class="signature-area">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Employee Name</label>
                                    <input type="text" class="form-control" placeholder="Enter your full name" id="employeeName">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Employee ID</label>
                                    <input type="text" class="form-control" placeholder="Enter your employee ID" id="employeeId">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Signature</label>
                                    <input type="text" class="form-control" placeholder="Type your full name as signature" id="signature">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Date</label>
                                    <input type="text" class="form-control" value="<?php echo date("F d, Y"); ?>" readonly>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button class="btn btn-red" onclick="submitAcknowledgment()">
                                    <i class="ti ti-check me-2"></i>Submit Acknowledgment
                                </button>
                                <button class="btn btn-outline-red ms-2" onclick="printAcknowledgment()">
                                    <i class="ti ti-printer me-2"></i>Print Acknowledgment
                                </button>
                            </div>
                        </div>
                        <p class="mt-3 mb-0 text-muted small"><i class="ti ti-info-circle text-red"></i> Electronic acknowledgment is recorded in system logs. Please print and retain a copy for your records.</p>
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
                if ($(this).attr('href') === 'terms-conditions.php') {
                    $(this).addClass('active');
                    $(this).css('background', '#dc3545');
                    $(this).css('color', 'white');
                }
            });
        });
        
        function submitAcknowledgment() {
            const name = $('#employeeName').val();
            const empId = $('#employeeId').val();
            const signature = $('#signature').val();
            
            if (!name || !empId || !signature) {
                alert('Please fill in all fields before submitting acknowledgment.');
                return;
            }
            
            if (signature !== name) {
                alert('Signature must match your full name.');
                return;
            }
            
            // Here you can add AJAX call to save acknowledgment to database
            alert('Thank you for acknowledging the Terms & Conditions. Your acknowledgment has been recorded.');
            
            // Optional: Disable fields after submission
            $('#employeeName, #employeeId, #signature').prop('disabled', true);
        }
        
        function printAcknowledgment() {
            const name = $('#employeeName').val() || '_________________________';
            const empId = $('#employeeId').val() || '_________________________';
            const signature = $('#signature').val() || '_________________________';
            const date = '<?php echo date("F d, Y"); ?>';
            
            const printContent = `
                <html>
                <head>
                    <title>Acknowledgment - Terms & Conditions</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 40px; }
                        .header { text-align: center; margin-bottom: 30px; }
                        .content { margin: 20px 0; }
                        .signature-line { margin-top: 40px; }
                        table { width: 100%; margin-top: 30px; }
                        td { padding: 10px; }
                        .border-bottom { border-bottom: 1px solid #000; }
                        @media print {
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h2>GO2EXPORT MART</h2>
                        <h3>Terms & Conditions Acknowledgment</h3>
                    </div>
                    <div class="content">
                        <p>I, <strong>${name}</strong> (Employee ID: <strong>${empId}</strong>), acknowledge that I have read, understood, and agree to abide by the Terms & Conditions of GO2EXPORT MART.</p>
                        <p>I understand that compliance with these terms is mandatory for continued system access and employment.</p>
                    </div>
                    <table>
                        <tr>
                            <td width="50%"><strong>Signature:</strong> ${signature}</td>
                            <td width="50%"><strong>Date:</strong> ${date}</td>
                        </tr>
                    </table>
                    <p class="mt-5"><small>This acknowledgment serves as confirmation of agreement to the Terms & Conditions.</small></p>
                    <button class="no-print" onclick="window.print()" style="margin-top: 20px; padding: 10px 20px;">Print</button>
                </body>
                </html>
            `;
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(printContent);
            printWindow.document.close();
            printWindow.print();
        }
    </script>
</body>

</html>