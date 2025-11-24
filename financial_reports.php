<?php
/**
 * KSU student project for Clarus Accounting tool
 * Financial Reports Generation
 * Generate Trial Balance, Income Statement, Balance Sheet, and Retained Earnings Statement
 * Initially drafted by Eric Poole; Reviewed and updated by Kyaa Goggins
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

//session variables
$username = $_SESSION['username'] ?? 'User';
$userId = $_SESSION['user_id'];
$userAccessLevel = isset($_SESSION['access_level']) ? (int) $_SESSION['access_level'] : 0;

//include header on all pages
include 'header.php';
?>

<link rel="stylesheet" href="/styling/financial_reports.css">
<div class="container"
    style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none; -ms-overflow-style: none;">

    <div class="reports-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fa-solid fa-chart-bar"></i> Financial Reports</h1>
            <p>Generate comprehensive financial statements and reports</p>
        </div>

        <!-- Report Selection -->
        <div class="section-title">Select Report Type</div>
        <div class="report-selection-grid">
            <div class="report-card" data-report="trial_balance" onclick="selectReport('trial_balance')">
                <i class="report-card-icon fa-solid fa-scale-balanced"></i>
                <div class="report-card-title">Trial Balance</div>
                <div class="report-card-description">
                    Lists all accounts with their debit and credit balances. Verifies that total debits equal total
                    credits.
                </div>
            </div>

            <div class="report-card" data-report="income_statement" onclick="selectReport('income_statement')">
                <i class="report-card-icon fa-solid fa-piggy-bank"></i>
                <div class="report-card-title">Income Statement</div>
                <div class="report-card-description">
                    Shows revenues, expenses, and net income for a specific period. Also known as Profit & Loss
                    Statement.
                </div>
            </div>

            <div class="report-card" data-report="balance_sheet" onclick="selectReport('balance_sheet')">
                <i class="report-card-icon fa-solid fa-file-invoice-dollar"></i>
                <div class="report-card-title">Balance Sheet</div>
                <div class="report-card-description">
                    Displays assets, liabilities, and equity at a specific point in time. Shows financial position.
                </div>
            </div>

            <div class="report-card" data-report="retained_earnings" onclick="selectReport('retained_earnings')">
                <i class="report-card-icon fa-solid fa-chart-line"></i>
                <div class="report-card-title">Retained Earnings Statement</div>
                <div class="report-card-description">
                    Shows changes in retained earnings from beginning to end of period, including net income and
                    dividends.
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
                        <span><i class="fa-solid fa-chart-column"></i></span> Generate Report
                    </button>
                    <button type="button" class="btn btn-reset" onclick="resetForm()">
                        <span><i class="fa-solid fa-rotate-right"></i></span> Reset
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
                    <span><i class="fa-solid fa-print"></i></span> Print
                </button>
                <button class="btn-action btn-pdf" onclick="downloadPDF()">
                    <span><i class="fa-solid fa-file-pdf"></i></span> Save as PDF
                </button>
                <button class="btn-action btn-email" onclick="emailReport()">
                    <span><i class="fa-solid fa-envelope"></i></span> Email
                </button>
                <button class="btn-action btn-excel" onclick="exportToExcel()">
                    <span><i class="fa-solid fa-chart-column"></i></span> Export to Excel
                </button>
            </div>

            <div id="reportContent" class="report-content">
                <!-- Report content will be inserted here in js -->
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

        switch (reportType) {
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

    //update date fields for the financial reports
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

    //allows resetting of form information
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
    document.getElementById('reportOptionsForm').addEventListener('submit', function (e) {
        e.preventDefault();

        if (!currentReportType) {
            alert('Wait! Please select a report type first!');
            return;
        }

        // Validate dates
        const dateType = document.getElementById('dateType').value;
        if (dateType === 'date_range') {
            const startDate = new Date(document.getElementById('startDate').value);
            const endDate = new Date(document.getElementById('endDate').value);
            if (startDate > endDate) {
                alert('Wait! Start date MUST be before or equal to end date.');
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
                    alert('Error getting report: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                document.getElementById('loadingSpinner').classList.remove('active');
                console.error('Error:', error);
                alert('Oh no! An error occurred while generating the report. Please try again!');
            });
    });

    //create specific html report content
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

    //allows the page to be exported to print 
    function printReport() {
        window.print();
    }

    //allows for the exporting of the form as a pdf
    function downloadPDF() {
        if (!currentReportData) {
            alert('Oh no! Please generate a report first.');
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

    //email functionality 
    function emailReport() {
        if (!currentReportData) {
            alert('Oh no! Please generate a report first.');
            return;
        }

        const email = prompt('Enter email address to send report to:');
        if (!email) return;

        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('Oh no! Please enter a valid email address.');
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
                    alert('Yay! Report sent successfully to ' + email);
                } else {
                    alert('Error sending report: ' + (data.message || 'Unknown error'));
                }
            })
    }

    function exportToExcel() {
        if (!currentReportData) {
            alert('Oh no! Please generate a report first.');
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