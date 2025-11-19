<?php
/**
 * Financial Reports Generator
 * Generate Trial Balance, Income Statement, Balance Sheet, and Retained Earnings Statement
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: home.html?error=session_expired');
    exit;
}

// Check session timeout
if (isset($_SESSION['expires']) && time() > $_SESSION['expires']) {
    session_destroy();
    header('Location: home.html?error=session_expired');
    exit;
}

$username = $_SESSION['username'] ?? 'User';
$userId = $_SESSION['user_id'];
$userAccessLevel = isset($_SESSION['access_level']) ? (int)$_SESSION['access_level'] : 0;

include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Reports - Clarus</title>
    <style>
        .reports-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            background: linear-gradient(135deg, #2980b9, #3498db);
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .page-header h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
        }

        .page-header p {
            margin: 0;
            font-size: 16px;
            opacity: 0.9;
        }

        .report-selection-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .report-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 3px solid transparent;
        }

        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .report-card.selected {
            border-color: #2980b9;
            background: #e3f2fd;
        }

        .report-card-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .report-card-title {
            font-size: 20px;
            font-weight: bold;
            color: #2980b9;
            margin-bottom: 10px;
        }

        .report-card-description {
            font-size: 13px;
            color: #666;
            line-height: 1.5;
        }

        .report-options-section {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: none;
        }

        .report-options-section.active {
            display: block;
        }

        .section-title {
            font-size: 22px;
            font-weight: bold;
            color: #2980b9;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2980b9;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2980b9;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 2px solid #ddd;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            font-weight: bold;
            color: #333;
        }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2980b9;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .info-box p {
            margin: 0;
            color: #1565c0;
            font-size: 13px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
            flex-wrap: wrap;
        }

        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-generate {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .btn-generate:hover {
            background: linear-gradient(135deg, #218838, #1a9970);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .btn-reset {
            background: #6c757d;
            color: white;
        }

        .btn-reset:hover {
            background: #545b62;
        }

        .report-display-area {
            background: white;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
            display: none;
        }

        .report-display-area.active {
            display: block;
        }

        .report-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #2980b9;
        }

        .report-header h2 {
            font-size: 28px;
            color: #2980b9;
            margin: 0 0 10px 0;
        }

        .report-header .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .report-header .report-date {
            font-size: 14px;
            color: #666;
        }

        .report-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-print {
            background: #17a2b8;
            color: white;
        }

        .btn-print:hover {
            background: #138496;
        }

        .btn-pdf {
            background: #dc3545;
            color: white;
        }

        .btn-pdf:hover {
            background: #c82333;
        }

        .btn-email {
            background: #ffc107;
            color: #000;
        }

        .btn-email:hover {
            background: #e0a800;
        }

        .btn-excel {
            background: #28a745;
            color: white;
        }

        .btn-excel:hover {
            background: #218838;
        }

        .report-content {
            font-size: 13px;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .report-table thead {
            background: linear-gradient(135deg, #2980b9, #3498db);
            color: white;
        }

        .report-table th {
            padding: 12px 10px;
            text-align: left;
            font-weight: bold;
            font-size: 13px;
        }

        .report-table th.text-right,
        .report-table td.text-right {
            text-align: right;
        }

        .report-table tbody tr {
            border-bottom: 1px solid #e0e0e0;
        }

        .report-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .report-table td {
            padding: 10px;
        }

        .report-table .total-row {
            background: #f8f9fa;
            font-weight: bold;
            border-top: 2px solid #2980b9;
            border-bottom: 2px solid #2980b9;
        }

        .report-table .subtotal-row {
            background: #e9ecef;
            font-weight: bold;
        }

        .report-table .category-header {
            background: #2980b9;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }

        .amount {
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }

        .loading-spinner {
            text-align: center;
            padding: 40px;
            display: none;
        }

        .loading-spinner.active {
            display: block;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #2980b9;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media print {
            .report-options-section,
            .report-actions,
            .action-buttons,
            .page-header {
                display: none !important;
            }

            .report-display-area {
                box-shadow: none;
                padding: 0;
            }
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container" style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none; -ms-overflow-style: none;">
        
        <div class="reports-container">
            <!-- Page Header -->
            <div class="page-header">
                <h1>📊 Financial Reports</h1>
                <p>Generate comprehensive financial statements and reports</p>
            </div>

            <!-- Report Selection -->
            <div class="section-title">Select Report Type</div>
            <div class="report-selection-grid">
                <div class="report-card" data-report="trial_balance" onclick="selectReport('trial_balance')">
                    <div class="report-card-icon">⚖️</div>
                    <div class="report-card-title">Trial Balance</div>
                    <div class="report-card-description">
                        Lists all accounts with their debit and credit balances. Verifies that total debits equal total credits.
                    </div>
                </div>

                <div class="report-card" data-report="income_statement" onclick="selectReport('income_statement')">
                    <div class="report-card-icon">💰</div>
                    <div class="report-card-title">Income Statement</div>
                    <div class="report-card-description">
                        Shows revenues, expenses, and net income for a specific period. Also known as Profit & Loss Statement.
                    </div>
                </div>

                <div class="report-card" data-report="balance_sheet" onclick="selectReport('balance_sheet')">
                    <div class="report-card-icon">📋</div>
                    <div class="report-card-title">Balance Sheet</div>
                    <div class="report-card-description">
                        Displays assets, liabilities, and equity at a specific point in time. Shows financial position.
                    </div>
                </div>

                <div class="report-card" data-report="retained_earnings" onclick="selectReport('retained_earnings')">
                    <div class="report-card-icon">📈</div>
                    <div class="report-card-title">Retained Earnings Statement</div>
                    <div class="report-card-description">
                        Shows changes in retained earnings from beginning to end of period, including net income and dividends.
                    </div>
                </div>
            </div>

            <!-- Report Options Section -->
            <div id="reportOptionsSection" class="report-options-section">
                <div class="section-title">Report Options</div>

                <div class="info-box" id="reportInfoBox">
                    <p id="reportInfoText">Select a report type above to configure options.</p>
                </div>

                <form id="reportOptionsForm">
                    <input type="hidden" id="reportType" name="report_type" value="">
                    
                    <div class="form-grid">
                        <div class="form-group" id="dateTypeGroup">
                            <label for="dateType">Report Date Type</label>
                            <select id="dateType" name="date_type" onchange="updateDateFields()">
                                <option value="as_of">As of Date (Point in Time)</option>
                                <option value="date_range">Date Range (Period)</option>
                            </select>
                        </div>

                        <div class="form-group" id="asOfDateGroup">
                            <label for="asOfDate">As of Date</label>
                            <input type="date" id="asOfDate" name="as_of_date" value="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="form-group" id="startDateGroup" style="display: none;">
                            <label for="startDate">Start Date</label>
                            <input type="date" id="startDate" name="start_date" value="<?php echo date('Y-01-01'); ?>">
                        </div>

                        <div class="form-group" id="endDateGroup" style="display: none;">
                            <label for="endDate">End Date</label>
                            <input type="date" id="endDate" name="end_date" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="includeAdjusting" name="include_adjusting" value="1" checked>
                            <label for="includeAdjusting">Include Adjusting Journal Entries</label>
                        </div>
                    </div>

                    <div class="form-group" id="zeroBalanceGroup">
                        <div class="checkbox-group">
                            <input type="checkbox" id="showZeroBalances" name="show_zero_balances" value="1">
                            <label for="showZeroBalances">Show Accounts with Zero Balances</label>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button type="submit" class="btn btn-generate">
                            <span>📊</span> Generate Report
                        </button>
                        <button type="button" class="btn btn-reset" onclick="resetForm()">
                            <span>🔄</span> Reset
                        </button>
                    </div>
                </form>
            </div>

            <!-- Loading Spinner -->
            <div id="loadingSpinner" class="loading-spinner">
                <div class="spinner"></div>
                <p>Generating report, please wait...</p>
            </div>

            <!-- Report Display Area -->
            <div id="reportDisplayArea" class="report-display-area">
                <div class="report-actions">
                    <button class="btn-action btn-print" onclick="printReport()">
                        <span>🖨️</span> Print
                    </button>
                    <button class="btn-action btn-pdf" onclick="downloadPDF()">
                        <span>📄</span> Save as PDF
                    </button>
                    <button class="btn-action btn-email" onclick="emailReport()">
                        <span>📧</span> Email
                    </button>
                    <button class="btn-action btn-excel" onclick="exportToExcel()">
                        <span>📊</span> Export to Excel
                    </button>
                </div>

                <div id="reportContent" class="report-content">
                    <!-- Report content will be inserted here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentReportType = '';
        let currentReportData = null;

        function selectReport(reportType) {
            // Remove selected class from all cards
            document.querySelectorAll('.report-card').forEach(card => {
                card.classList.remove('selected');
            });

            // Add selected class to clicked card
            document.querySelector(`.report-card[data-report="${reportType}"]`).classList.add('selected');

            // Update hidden input
            document.getElementById('reportType').value = reportType;
            currentReportType = reportType;

            // Show options section
            document.getElementById('reportOptionsSection').classList.add('active');

            // Update info box and date fields based on report type
            updateReportInfo(reportType);
            updateDateFields();
        }

        function updateReportInfo(reportType) {
            const infoText = document.getElementById('reportInfoText');
            const dateTypeGroup = document.getElementById('dateTypeGroup');
            
            switch(reportType) {
                case 'trial_balance':
                    infoText.innerHTML = '<strong>Trial Balance:</strong> Shows all accounts with debit and credit balances as of a specific date. Total debits must equal total credits.';
                    dateTypeGroup.style.display = 'block';
                    document.getElementById('dateType').value = 'as_of';
                    break;
                case 'income_statement':
                    infoText.innerHTML = '<strong>Income Statement:</strong> Shows revenues and expenses for a period (date range). Calculates net income or loss.';
                    dateTypeGroup.style.display = 'block';
                    document.getElementById('dateType').value = 'date_range';
                    break;
                case 'balance_sheet':
                    infoText.innerHTML = '<strong>Balance Sheet:</strong> Shows assets, liabilities, and equity as of a specific date. Assets must equal Liabilities + Equity.';
                    dateTypeGroup.style.display = 'block';
                    document.getElementById('dateType').value = 'as_of';
                    break;
                case 'retained_earnings':
                    infoText.innerHTML = '<strong>Retained Earnings Statement:</strong> Shows changes in retained earnings over a period, including net income and dividends paid.';
                    dateTypeGroup.style.display = 'block';
                    document.getElementById('dateType').value = 'date_range';
                    break;
            }
            
            updateDateFields();
        }

        function updateDateFields() {
            const dateType = document.getElementById('dateType').value;
            const asOfDateGroup = document.getElementById('asOfDateGroup');
            const startDateGroup = document.getElementById('startDateGroup');
            const endDateGroup = document.getElementById('endDateGroup');

            if (dateType === 'as_of') {
                asOfDateGroup.style.display = 'block';
                startDateGroup.style.display = 'none';
                endDateGroup.style.display = 'none';
            } else {
                asOfDateGroup.style.display = 'none';
                startDateGroup.style.display = 'block';
                endDateGroup.style.display = 'block';
            }
        }

        function resetForm() {
            document.getElementById('reportOptionsForm').reset();
            document.querySelectorAll('.report-card').forEach(card => {
                card.classList.remove('selected');
            });
            document.getElementById('reportOptionsSection').classList.remove('active');
            document.getElementById('reportDisplayArea').classList.remove('active');
            currentReportType = '';
            currentReportData = null;
        }

        // Form submission
        document.getElementById('reportOptionsForm').addEventListener('submit', function(e) {
            e.preventDefault();

            if (!currentReportType) {
                alert('Please select a report type first.');
                return;
            }

            // Validate dates
            const dateType = document.getElementById('dateType').value;
            if (dateType === 'date_range') {
                const startDate = new Date(document.getElementById('startDate').value);
                const endDate = new Date(document.getElementById('endDate').value);
                if (startDate > endDate) {
                    alert('Start date must be before or equal to end date.');
                    return;
                }
            }

            // Show loading spinner
            document.getElementById('loadingSpinner').classList.add('active');
            document.getElementById('reportDisplayArea').classList.remove('active');

            // Prepare form data
            const formData = new FormData(this);

            // Submit via AJAX
            fetch('generate_report.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loadingSpinner').classList.remove('active');
                
                if (data.success) {
                    currentReportData = data;
                    displayReport(data);
                    document.getElementById('reportDisplayArea').classList.add('active');
                    
                    // Scroll to report
                    document.getElementById('reportDisplayArea').scrollIntoView({ behavior: 'smooth' });
                } else {
                    alert('Error generating report: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                document.getElementById('loadingSpinner').classList.remove('active');
                console.error('Error:', error);
                alert('An error occurred while generating the report. Please try again.');
            });
        });

        function displayReport(data) {
            const reportContent = document.getElementById('reportContent');
            
            // Create report header
            let html = `
                <div class="report-header">
                    <div class="company-name">Clarus Accounting System</div>
                    <h2>${data.report_title}</h2>
                    <div class="report-date">${data.report_date_text}</div>
                </div>
            `;

            // Add report-specific content
            html += data.report_html;

            reportContent.innerHTML = html;
        }

        function printReport() {
            window.print();
        }

        function downloadPDF() {
            if (!currentReportData) {
                alert('Please generate a report first.');
                return;
            }

            // Create form and submit to PDF generator
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'export_report_pdf.php';
            form.target = '_blank';

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'report_data';
            input.value = JSON.stringify(currentReportData);

            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }

        function emailReport() {
            if (!currentReportData) {
                alert('Please generate a report first.');
                return;
            }

            const email = prompt('Enter email address to send report to:');
            if (!email) return;

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email address.');
                return;
            }

            const formData = new FormData();
            formData.append('report_data', JSON.stringify(currentReportData));
            formData.append('email_address', email);

            fetch('email_report.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Report sent successfully to ' + email);
                } else {
                    alert('Error sending report: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while sending the report.');
            });
        }

        function exportToExcel() {
            if (!currentReportData) {
                alert('Please generate a report first.');
                return;
            }

            // Create form and submit to Excel generator
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'export_report_excel.php';

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'report_data';
            input.value = JSON.stringify(currentReportData);

            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
    </script>
</body>
</html>