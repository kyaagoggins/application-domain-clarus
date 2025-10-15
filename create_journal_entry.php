<?php
/**
 * Create Journal Entry
 * This page allows users to create new accounting journal entries
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

// Get account_id from URL parameter
$account_id = isset($_GET['account_id']) ? trim($_GET['account_id']) : null;

if (!$account_id) {
    die("Error: Account ID is required. Please provide a valid account_id parameter in the URL.");
}

// Include database configuration
include '../db_connect.php';

// Fetch account details
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE account_number = :account_id");
    $stmt->execute([':account_id' => $account_id]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$account) {
        die("Error: Account not found with Account ID: $account_id");
    }
    
    // Fetch all accounts for dropdown
    $stmt = $pdo->query("SELECT account_number, name, category FROM accounts WHERE is_active = 1 ORDER BY account_number");
    $allAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Create Journal Entry - Clarus</title>
    <style>
        .form-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .form-section h3 {
            margin-top: 0;
            color: #2980b9;
            border-bottom: 2px solid #2980b9;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        .required {
            color: red;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            margin-bottom: 20px;
            display: none;
        }
        
        .error-message.show {
            display: block;
        }
        
        .error-message ul {
            margin: 10px 0 0 20px;
            padding: 0;
        }
        
        .error-message li {
            margin: 5px 0;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            margin-bottom: 20px;
            display: none;
        }
        
        .success-message.show {
            display: block;
        }
        
        .entry-line {
            background: white;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            position: relative;
        }
        
        .entry-line-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .entry-line-number {
            font-weight: bold;
            color: #2980b9;
        }
        
        .remove-line-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .remove-line-btn:hover {
            background-color: #c82333;
        }
        
        .entry-line-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 15px;
            align-items: end;
        }
        
        .add-line-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }
        
        .add-line-btn:hover {
            background-color: #218838;
        }
        
        .totals-section {
            background: #e9ecef;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
        }
        
        .totals-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
        }
        
        .total-box {
            text-align: center;
            padding: 10px;
            background: white;
            border-radius: 4px;
            border: 2px solid #ddd;
        }
        
        .total-label {
            font-weight: bold;
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .total-value {
            font-size: 20px;
            font-weight: bold;
            font-family: monospace;
        }
        
        .total-debit {
            color: #dc3545;
        }
        
        .total-credit {
            color: #28a745;
        }
        
        .total-difference {
            color: #666;
        }
        
        .balanced {
            border-color: #28a745;
            background-color: #d4edda;
        }
        
        .unbalanced {
            border-color: #dc3545;
            background-color: #f8d7da;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .btn-submit {
            background-color: #28a745;
            color: white;
        }
        
        .btn-submit:hover {
            background-color: #218838;
        }
        
        .btn-submit:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        
        .btn-restart {
            background-color: #ffc107;
            color: #000;
        }
        
        .btn-restart:hover {
            background-color: #e0a800;
        }
        
        .btn-cancel {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-cancel:hover {
            background-color: #545b62;
        }
        
        .file-upload-area {
            border: 2px dashed #ddd;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            background: white;
        }
        
        .file-list {
            margin-top: 15px;
        }
        
        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        
        .file-item-name {
            font-size: 14px;
            color: #333;
        }
        
        .file-item-remove {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .account-info-box {
            background: linear-gradient(135deg, #2980b9, #3498db);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .account-info-box h2 {
            margin: 0 0 10px 0;
        }
        
        .account-info-box p {
            margin: 5px 0;
        }
        
        @media (max-width: 768px) {
            .entry-line-grid {
                grid-template-columns: 1fr;
            }
            
            .totals-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container" style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none; -ms-overflow-style: none;">
    <h2 class="logo" style="">
        <img src="assets/logo.png" style="float:left; border: 1px solid black; border-radius: 5px; height:30px">
        <span style="float:left; margin-left: 10px">Clarus</span>
        
        <?php 
            echo'<div style="float:right"><a href="profile.php" style="text-decoration: none; color: black;"><img src="/uploads/profile_images/'.$userId.'.jpg" style="width:50px; border-radius: 50%; border: 3px solid black">
            
            <center><div style="font-size: 14px;">'.$username.'</div></center></a></div>';
        ?>
        <a style="float:right; margin-right: 30px; padding: 10px; text-decoration:none; background-color: #efefef; color: black; font-size: 14px" href="sign_out.php">Sign Out</a>
        <a style="float:right; margin-right: 30px; padding: 10px; text-decoration:none; background-color: #efefef; color: black; font-size: 14px" href="help.php">Help</a>
        <a style="float:right; margin-right: 30px; padding: 10px; text-decoration:none; background-color: #efefef; color: black; font-size: 14px" href="dashboard.php">User Management</a>
        <a style="float:right; margin-right: 30px; padding: 10px; text-decoration:none; background-color: #efefef; color: black; font-size: 14px" href="chart_of_accounts.php">Chart of Accounts</a>
        <a style="float:right; margin-right: 30px; padding: 10px; text-decoration:none; background-color: #efefef; color: black; font-size: 14px" href="accounts_dashboard.php">View Accounts</a>
   </h2>
    <div style="clear:both; margin-bottom: 30px"></div>
    
    <h1>Create Journal Entry</h1>
    
    <!-- Account Information -->
    <div class="account-info-box">
        <h2>Account: <?php echo htmlspecialchars($account['account_number']); ?> - <?php echo htmlspecialchars($account['name']); ?></h2>
        <p><strong>Category:</strong> <?php echo htmlspecialchars($account['category']); ?> | 
           <strong>Normal Side:</strong> <?php echo htmlspecialchars($account['normal_side']); ?> | 
           <strong>Balance:</strong> $<?php echo number_format((float)$account['balance'], 2); ?></p>
    </div>
    
    <!-- Error Messages -->
    <div id="errorMessages" class="error-message">
        <strong>⚠️ Please fix the following errors:</strong>
        <ul id="errorList"></ul>
    </div>
    
    <!-- Success Message -->
    <div id="successMessage" class="success-message">
        <strong>✅ Journal entry created successfully!</strong>
    </div>
    
    <form id="journalEntryForm" enctype="multipart/form-data">
        <div class="form-container">
            
            <!-- Journal Entry Information -->
            <div class="form-section">
                <h3>📋 Journal Entry Information</h3>
                
                <div class="form-group">
                    <label for="entryDate">Entry Date <span class="required">*</span></label>
                    <input type="date" id="entryDate" name="entry_date" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Description <span class="required">*</span></label>
                    <textarea id="description" name="description" required placeholder="Enter a description for this journal entry..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="reference">Reference Number</label>
                    <input type="text" id="reference" name="reference" placeholder="Optional reference number (e.g., INV-001, PO-123)">
                </div>
            </div>
            
            <!-- Journal Entry Lines -->
            <div class="form-section">
                <h3>💰 Journal Entry Lines</h3>
                
                <div id="entryLinesContainer">
                    <!-- Entry lines will be added here dynamically -->
                </div>
                
                <button type="button" class="add-line-btn" onclick="addEntryLine()">
                    ➕ Add Entry Line
                </button>
                
                <!-- Totals Section -->
                <div class="totals-section">
                    <div class="totals-grid">
                        <div class="total-box">
                            <div class="total-label">Total Debits</div>
                            <div class="total-value total-debit" id="totalDebits">$0.00</div>
                        </div>
                        <div class="total-box">
                            <div class="total-label">Total Credits</div>
                            <div class="total-value total-credit" id="totalCredits">$0.00</div>
                        </div>
                        <div class="total-box" id="differenceBox">
                            <div class="total-label">Difference</div>
                            <div class="total-value total-difference" id="totalDifference">$0.00</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Source Documents -->
            <div class="form-section">
                <h3>📎 Source Documents (Optional)</h3>
                
                <div class="file-upload-area">
                    <input type="file" id="sourceDocuments" name="source_documents[]" 
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.jpg,.jpeg,.png" 
                           multiple style="display: none;" onchange="handleFileSelect(event)">
                    <button type="button" onclick="document.getElementById('sourceDocuments').click()" 
                            style="padding: 10px 20px; background-color: #2980b9; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        📁 Choose Files
                    </button>
                    <p style="margin-top: 10px; color: #666; font-size: 12px;">
                        Accepted formats: PDF, Word, Excel, CSV, JPG, PNG
                    </p>
                </div>
                
                <div id="fileList" class="file-list"></div>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <button type="submit" class="btn btn-submit" id="submitBtn">
                    💾 Create Journal Entry
                </button>
                <button type="button" class="btn btn-restart" onclick="restartForm()">
                    🔄 Restart
                </button>
                <button type="button" class="btn btn-cancel" onclick="cancelForm()">
                    ❌ Cancel
                </button>
            </div>
            
        </div>
    </form>
    
    </div>
    
    <script>
        let entryLineCounter = 0;
        let selectedFiles = [];
        const accounts = <?php echo json_encode($allAccounts); ?>;
        
        // Initialize form with one entry line
        document.addEventListener('DOMContentLoaded', function() {
            addEntryLine();
            addEntryLine(); // Add two lines by default
        });
        
        function addEntryLine() {
            entryLineCounter++;
            const container = document.getElementById('entryLinesContainer');
            
            const lineDiv = document.createElement('div');
            lineDiv.className = 'entry-line';
            lineDiv.id = 'entryLine' + entryLineCounter;
            
            let accountOptions = '<option value="">Select Account...</option>';
            accounts.forEach(acc => {
                accountOptions += `<option value="${acc.account_number}">${acc.account_number} - ${acc.name} (${acc.category})</option>`;
            });
            
            lineDiv.innerHTML = `
                <div class="entry-line-header">
                    <span class="entry-line-number">Line ${entryLineCounter}</span>
                    <button type="button" class="remove-line-btn" onclick="removeEntryLine(${entryLineCounter})">
                        ❌ Remove
                    </button>
                </div>
                <div class="entry-line-grid">
                    <div class="form-group">
                        <label>Account <span class="required">*</span></label>
                        <select name="line_account[]" required onchange="calculateTotals()">
                            ${accountOptions}
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="line_description[]" placeholder="Line description...">
                    </div>
                    <div class="form-group">
                        <label>Debit</label>
                        <input type="number" name="line_debit[]" step="0.01" min="0" value="0.00" 
                               onchange="handleLineAmountChange(this)" oninput="calculateTotals()">
                    </div>
                    <div class="form-group">
                        <label>Credit</label>
                        <input type="number" name="line_credit[]" step="0.01" min="0" value="0.00" 
                               onchange="handleLineAmountChange(this)" oninput="calculateTotals()">
                    </div>
                </div>
            `;
            
            container.appendChild(lineDiv);
            calculateTotals();
        }
        
        function removeEntryLine(lineId) {
            const lineCount = document.querySelectorAll('.entry-line').length;
            if (lineCount <= 2) {
                alert('A journal entry must have at least 2 lines (one debit and one credit).');
                return;
            }
            
            const line = document.getElementById('entryLine' + lineId);
            if (line) {
                line.remove();
                calculateTotals();
                validateForm();
            }
        }
        
        function handleLineAmountChange(input) {
            // When user enters a debit, clear the credit on the same line and vice versa
            const parent = input.closest('.entry-line-grid');
            if (input.name === 'line_debit[]' && parseFloat(input.value) > 0) {
                const creditInput = parent.querySelector('input[name="line_credit[]"]');
                creditInput.value = '0.00';
            } else if (input.name === 'line_credit[]' && parseFloat(input.value) > 0) {
                const debitInput = parent.querySelector('input[name="line_debit[]"]');
                debitInput.value = '0.00';
            }
            calculateTotals();
        }
        
        function calculateTotals() {
            const debitInputs = document.querySelectorAll('input[name="line_debit[]"]');
            const creditInputs = document.querySelectorAll('input[name="line_credit[]"]');
            
            let totalDebit = 0;
            let totalCredit = 0;
            
            debitInputs.forEach(input => {
                totalDebit += parseFloat(input.value) || 0;
            });
            
            creditInputs.forEach(input => {
                totalCredit += parseFloat(input.value) || 0;
            });
            
            const difference = Math.abs(totalDebit - totalCredit);
            
            document.getElementById('totalDebits').textContent = '$' + totalDebit.toFixed(2);
            document.getElementById('totalCredits').textContent = '$' + totalCredit.toFixed(2);
            document.getElementById('totalDifference').textContent = '$' + difference.toFixed(2);
            
            const differenceBox = document.getElementById('differenceBox');
            if (difference === 0 && totalDebit > 0 && totalCredit > 0) {
                differenceBox.classList.add('balanced');
                differenceBox.classList.remove('unbalanced');
            } else {
                differenceBox.classList.remove('balanced');
                differenceBox.classList.add('unbalanced');
            }
            
            validateForm();
        }
        
        function validateForm() {
            const errors = [];
            
            // Check if entry date is provided
            const entryDate = document.getElementById('entryDate').value;
            if (!entryDate) {
                errors.push('Entry date is required.');
            }
            
            // Check if description is provided
            const description = document.getElementById('description').value.trim();
            if (!description) {
                errors.push('Description is required.');
            }
            
            // Check if there are at least 2 entry lines
            const entryLines = document.querySelectorAll('.entry-line');
            if (entryLines.length < 2) {
                errors.push('A journal entry must have at least 2 lines (one debit and one credit).');
            }
            
            // Check if all accounts are selected
            const accountSelects = document.querySelectorAll('select[name="line_account[]"]');
            let emptyAccounts = 0;
            accountSelects.forEach(select => {
                if (!select.value) {
                    emptyAccounts++;
                }
            });
            if (emptyAccounts > 0) {
                errors.push(`Please select an account for all ${emptyAccounts} entry line(s).`);
            }
            
            // Check if each line has either a debit or credit (not both, not neither)
            const debitInputs = document.querySelectorAll('input[name="line_debit[]"]');
            const creditInputs = document.querySelectorAll('input[name="line_credit[]"]');
            let linesWithoutAmount = 0;
            let linesWithBothAmounts = 0;
            
            for (let i = 0; i < debitInputs.length; i++) {
                const debit = parseFloat(debitInputs[i].value) || 0;
                const credit = parseFloat(creditInputs[i].value) || 0;
                
                if (debit === 0 && credit === 0) {
                    linesWithoutAmount++;
                } else if (debit > 0 && credit > 0) {
                    linesWithBothAmounts++;
                }
            }
            
            if (linesWithoutAmount > 0) {
                errors.push(`${linesWithoutAmount} entry line(s) have no debit or credit amount. Each line must have either a debit or credit.`);
            }
            
            if (linesWithBothAmounts > 0) {
                errors.push(`${linesWithBothAmounts} entry line(s) have both debit and credit amounts. Each line should have only one or the other.`);
            }
            
            // Calculate totals
            let totalDebit = 0;
            let totalCredit = 0;
            
            debitInputs.forEach(input => {
                totalDebit += parseFloat(input.value) || 0;
            });
            
            creditInputs.forEach(input => {
                totalCredit += parseFloat(input.value) || 0;
            });
            
            // Check if there is at least one debit
            if (totalDebit === 0) {
                errors.push('Journal entry must have at least one debit entry.');
            }
            
            // Check if there is at least one credit
            if (totalCredit === 0) {
                errors.push('Journal entry must have at least one credit entry.');
            }
            
            // Check if debits equal credits
            const difference = Math.abs(totalDebit - totalCredit);
            if (difference > 0.01) { // Allow for small rounding errors
                errors.push(`Total debits ($${totalDebit.toFixed(2)}) must equal total credits ($${totalCredit.toFixed(2)}). Current difference: $${difference.toFixed(2)}`);
            }
            
            // Display errors
            const errorDiv = document.getElementById('errorMessages');
            const errorList = document.getElementById('errorList');
            const submitBtn = document.getElementById('submitBtn');
            
            if (errors.length > 0) {
                errorList.innerHTML = errors.map(err => `<li>${err}</li>`).join('');
                errorDiv.classList.add('show');
                submitBtn.disabled = true;
            } else {
                errorDiv.classList.remove('show');
                submitBtn.disabled = false;
            }
            
            return errors.length === 0;
        }
        
        function handleFileSelect(event) {
            const files = event.target.files;
            const allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'jpg', 'jpeg', 'png'];
            
            for (let file of files) {
                const extension = file.name.split('.').pop().toLowerCase();
                
                if (!allowedExtensions.includes(extension)) {
                    alert(`File "${file.name}" has an invalid format. Only PDF, Word, Excel, CSV, JPG, and PNG files are allowed.`);
                    continue;
                }
                
                if (file.size > 10 * 1024 * 1024) { // 10MB limit
                    alert(`File "${file.name}" is too large. Maximum file size is 10MB.`);
                    continue;
                }
                
                selectedFiles.push(file);
            }
            
            displayFileList();
            event.target.value = ''; // Reset input
        }
        
        function displayFileList() {
            const fileList = document.getElementById('fileList');
            fileList.innerHTML = '';
            
            selectedFiles.forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                fileItem.innerHTML = `
                    <span class="file-item-name">📄 ${file.name} (${(file.size / 1024).toFixed(2)} KB)</span>
                    <button type="button" class="file-item-remove" onclick="removeFile(${index})">Remove</button>
                `;
                fileList.appendChild(fileItem);
            });
        }
        
        function removeFile(index) {
            selectedFiles.splice(index, 1);
            displayFileList();
        }
        
        function restartForm() {
            if (confirm('Are you sure you want to restart? All entered data will be lost.')) {
                document.getElementById('journalEntryForm').reset();
                document.getElementById('entryDate').value = '<?php echo date('Y-m-d'); ?>';
                
                // Clear all entry lines and add two new ones
                document.getElementById('entryLinesContainer').innerHTML = '';
                entryLineCounter = 0;
                addEntryLine();
                addEntryLine();
                
                // Clear files
                selectedFiles = [];
                displayFileList();
                
                // Clear messages
                document.getElementById('errorMessages').classList.remove('show');
                document.getElementById('successMessage').classList.remove('show');
                
                calculateTotals();
            }
        }
        
        function cancelForm() {
            if (confirm('Are you sure you want to cancel? All entered data will be lost.')) {
                window.history.back();
            }
        }
        
        // Form submission
        document.getElementById('journalEntryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!validateForm()) {
                alert('Please fix all errors before submitting.');
                return;
            }
            
            const formData = new FormData();
            
            // Add basic fields
            formData.append('account_id', '<?php echo $account_id; ?>');
            formData.append('entry_date', document.getElementById('entryDate').value);
            formData.append('description', document.getElementById('description').value);
            formData.append('reference', document.getElementById('reference').value);
            formData.append('user_id', '<?php echo $userId; ?>');
            
            // Add entry lines
            const accountSelects = document.querySelectorAll('select[name="line_account[]"]');
            const lineDescriptions = document.querySelectorAll('input[name="line_description[]"]');
            const debitInputs = document.querySelectorAll('input[name="line_debit[]"]');
            const creditInputs = document.querySelectorAll('input[name="line_credit[]"]');
            
            const entryLines = [];
            for (let i = 0; i < accountSelects.length; i++) {
                entryLines.push({
                    account: accountSelects[i].value,
                    description: lineDescriptions[i].value,
                    debit: parseFloat(debitInputs[i].value) || 0,
                    credit: parseFloat(creditInputs[i].value) || 0
                });
            }
            
            formData.append('entry_lines', JSON.stringify(entryLines));
            
            // Add files
            selectedFiles.forEach((file, index) => {
                formData.append('source_documents[]', file);
            });
            
            // Submit via AJAX
            fetch('create_journal_entry_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('successMessage').classList.add('show');
                    document.getElementById('errorMessages').classList.remove('show');
                    
                    setTimeout(() => {
                        window.location.href = 'view_journal_entries.php?account_id=<?php echo $account_id; ?>';
                    }, 2000);
                } else {
                    alert('Error creating journal entry: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting the form. Please try again.');
            });
        });
        
        // Real-time validation on input changes
        document.getElementById('entryDate').addEventListener('change', validateForm);
        document.getElementById('description').addEventListener('input', validateForm);
    </script>
</body>
</html>