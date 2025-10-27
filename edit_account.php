<?php
/**
 * Edit Account Page
 * This page allows editing of an existing accounting account
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

// Get account_number from URL parameter
$account_number = isset($_GET['account_number']) ? trim($_GET['account_number']) : null;

if (!$account_number) {
    die("Error: Account Number is required. Please provide a valid account_number parameter in the URL.");
}

// Include database configuration
include '../db_connect.php';

// Fetch account details from database
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE account_number = :account_number");
    $stmt->execute([':account_number' => $account_number]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$account) {
        die("Error: Account not found with Account Number: $account_number");
    }
    
} catch(PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Format monetary values for display (remove commas and dollar signs for editing)
function formatMoneyForEdit($value) {
    return number_format((float)$value, 2, '.', '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Edit Account - <?php echo htmlspecialchars($account['name']); ?></title>
    <style>
        .form-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .form-column {
            display: flex;
            flex-direction: column;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 60px;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0,123,255,0.3);
        }
        .required {
            color: red;
        }
        .form-footer {
            grid-column: 1 / -1;
            text-align: center;
            margin-top: 20px;
        }
        .submit-btn {
            background-color: #2980b9;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin-right: 10px;
        }
        .submit-btn:hover {
            background-color: #2980b9;
        }
        .cancel-btn {
            background-color: #6c757d;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .cancel-btn:hover {
            background-color: #545b62;
        }
        .readonly-field {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
        .error-message {
            color: red;
            font-size: 12px;
            margin-top: 5px;
        }
        .success-message {
            color: green;
            font-size: 12px;
            margin-top: 5px;
        }
        .help-text {
            color: #666;
            font-size: 11px;
            margin-top: 3px;
        }
        .validation-error {
            border-color: #dc3545 !important;
        }
        .validation-success {
            border-color: #28a745 !important;
        }
        .account-header {
            background: linear-gradient(135deg, #fd7e14, #e55a4e);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .account-number {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .account-name {
            font-size: 1.5em;
            margin-bottom: 5px;
        }
        @media (max-width: 768px) {
            .form-container {
                grid-template-columns: 1fr;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container" style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none; -ms-overflow-style: none;">
    <!--<img src="https://thumbs.dreamstime.com/b/calculator-icon-vector-isolated-white-background-your-web-mobile-app-design-calculator-logo-concept-calculator-icon-134617239.jpg" width="100px">-->
    <?php include 'header.php'; ?>
    
    <div class="account-header">
        <div class="account-number"><?php echo htmlspecialchars($account['account_number']); ?></div>
        <div class="account-name">Editing: <?php echo htmlspecialchars($account['name']); ?></div>
    </div>
    
    <h1>Edit Account</h1>
    
    <form action="push_edit_account.php" method="POST" onsubmit="return validateForm()">
        <input type="hidden" name="original_account_number" value="<?php echo htmlspecialchars($account['account_number']); ?>">
        <input type="hidden" name="original_name" value="<?php echo htmlspecialchars($account['name']); ?>">
        
        <div class="form-container">
            <!-- Left Column -->
            <div class="form-column">
                <div class="form-group">
                    <label for="accountNumber">Account Number <span class="required">*</span></label>
                    <input type="text" id="accountNumber" name="account_number" 
                           value="<?php echo htmlspecialchars($account['account_number']); ?>" 
                           required maxlength="20" oninput="validateAccountNumber()" onblur="checkDuplicateAccount()">
                    <div id="accountNumberError" class="error-message"></div>
                    <div class="help-text">Enter a unique account number (numbers only, no spaces or decimals)</div>
                </div>
                
                <div class="form-group">
                    <label for="accountName">Account Name <span class="required">*</span></label>
                    <input type="text" id="accountName" name="name" 
                           value="<?php echo htmlspecialchars($account['name']); ?>" 
                           required maxlength="100" onblur="checkDuplicateName()">
                    <div id="accountNameError" class="error-message"></div>
                    <div class="help-text">Enter a unique account name (e.g., Cash, Accounts Receivable)</div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" maxlength="500"><?php echo htmlspecialchars($account['description']); ?></textarea>
                    <div class="help-text">Optional detailed description of the account</div>
                </div>
                
                <div class="form-group">
                    <label for="normalSide">Normal Side <span class="required">*</span></label>
                    <select id="normalSide" name="normal_side" required onchange="updateBalance()">
                        <option value="">Choose Normal Side</option>
                        <option value="Debit" <?php echo $account['normal_side'] == 'Debit' ? 'selected' : ''; ?>>Debit</option>
                        <option value="Credit" <?php echo $account['normal_side'] == 'Credit' ? 'selected' : ''; ?>>Credit</option>
                    </select>
                    <div class="help-text">Assets/Expenses = Debit, Liabilities/Equity/Revenue = Credit</div>
                </div>
                
                <div class="form-group">
                    <label for="category">Category <span class="required">*</span></label>
                    <select id="category" name="category" required onchange="updateSubcategories()">
                        <option value="">Choose Category</option>
                        <option value="Assets" <?php echo $account['category'] == 'Assets' ? 'selected' : ''; ?>>Assets</option>
                        <option value="Liabilities" <?php echo $account['category'] == 'Liabilities' ? 'selected' : ''; ?>>Liabilities</option>
                        <option value="Equity" <?php echo $account['category'] == 'Equity' ? 'selected' : ''; ?>>Equity</option>
                        <option value="Revenue" <?php echo $account['category'] == 'Revenue' ? 'selected' : ''; ?>>Revenue</option>
                        <option value="Expenses" <?php echo $account['category'] == 'Expenses' ? 'selected' : ''; ?>>Expenses</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="subcategory">Subcategory</label>
                    <select id="subcategory" name="subcategory">
                        <option value="">Choose Subcategory</option>
                        <!-- Will be populated by JavaScript -->
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="statement">Financial Statement <span class="required">*</span></label>
                    <select id="statement" name="statement" required>
                        <option value="">Choose Statement</option>
                        <option value="Balance Sheet" <?php echo $account['statement'] == 'Balance Sheet' ? 'selected' : ''; ?>>Balance Sheet</option>
                        <option value="Income Statement" <?php echo $account['statement'] == 'Income Statement' ? 'selected' : ''; ?>>Income Statement</option>
                        <option value="Statement of Cash Flows" <?php echo $account['statement'] == 'Statement of Cash Flows' ? 'selected' : ''; ?>>Statement of Cash Flows</option>
                        <option value="Statement of Equity" <?php echo $account['statement'] == 'Statement of Equity' ? 'selected' : ''; ?>>Statement of Equity</option>
                    </select>
                </div>
            </div>
            
            <!-- Right Column -->
            <div class="form-column">
                <div class="form-group">
                    <label for="initialBalance">Initial Balance</label>
                    <input type="text" id="initialBalance" name="initial_balance" 
                           value="<?php echo formatMoneyForEdit($account['initial_balance']); ?>" 
                           oninput="formatCurrency(this)" onchange="updateBalance()">
                    <div class="help-text">Starting balance for this account</div>
                </div>
                
                <div class="form-group">
                    <label for="debitAmount">Debit Amount</label>
                    <input type="text" id="debitAmount" name="debit" 
                           value="<?php echo formatMoneyForEdit($account['debit']); ?>" 
                           oninput="formatCurrency(this)" onchange="updateBalance()">
                    <div class="help-text">Total debit transactions</div>
                </div>
                
                <div class="form-group">
                    <label for="creditAmount">Credit Amount</label>
                    <input type="text" id="creditAmount" name="credit" 
                           value="<?php echo formatMoneyForEdit($account['credit']); ?>" 
                           oninput="formatCurrency(this)" onchange="updateBalance()">
                    <div class="help-text">Total credit transactions</div>
                </div>
                
                <div class="form-group">
                    <label for="balance">Current Balance</label>
                    <input type="text" id="balance" name="balance" 
                           value="<?php echo formatMoneyForEdit($account['balance']); ?>" readonly class="readonly-field">
                    <div class="help-text">Calculated automatically based on normal side and amounts</div>
                </div>
                
                <div class="form-group">
                    <label for="orderType">Order Type</label>
                    <select id="orderType" name="order_type">
                        <option value="">Choose Order Type</option>
                        <option value="Current Assets" <?php echo $account['order_type'] == 'Current Assets' ? 'selected' : ''; ?>>Current Assets</option>
                        <option value="Non-Current Assets" <?php echo $account['order_type'] == 'Non-Current Assets' ? 'selected' : ''; ?>>Non-Current Assets</option>
                        <option value="Current Liabilities" <?php echo $account['order_type'] == 'Current Liabilities' ? 'selected' : ''; ?>>Current Liabilities</option>
                        <option value="Non-Current Liabilities" <?php echo $account['order_type'] == 'Non-Current Liabilities' ? 'selected' : ''; ?>>Non-Current Liabilities</option>
                        <option value="Operating Expenses" <?php echo $account['order_type'] == 'Operating Expenses' ? 'selected' : ''; ?>>Operating Expenses</option>
                        <option value="Non-Operating Expenses" <?php echo $account['order_type'] == 'Non-Operating Expenses' ? 'selected' : ''; ?>>Non-Operating Expenses</option>
                        <option value="Operating Revenue" <?php echo $account['order_type'] == 'Operating Revenue' ? 'selected' : ''; ?>>Operating Revenue</option>
                        <option value="Non-Operating Revenue" <?php echo $account['order_type'] == 'Non-Operating Revenue' ? 'selected' : ''; ?>>Non-Operating Revenue</option>
                    </select>
                    <div class="help-text">Classification for financial statement ordering</div>
                </div>
                
                <div class="form-group">
                    <label for="comment">Comments</label>
                    <textarea id="comment" name="comment" rows="4" maxlength="1000"><?php echo htmlspecialchars($account['comment']); ?></textarea>
                    <div class="help-text">Additional notes or comments about this account</div>
                </div>
            </div>
            
            <!-- Form Footer -->
            <div class="form-footer">
                <button type="submit" class="submit-btn">💾 Update Account</button>
                <br><br>
                <a href="view_account.php?account_number=<?php echo urlencode($account['account_number']); ?>" class="cancel-btn">👁️ View Account</a>
                <a href="accounts_dashboard.php" class="cancel-btn">❌ Cancel</a>
            </div>
        </div>
    </form>
</div>

<script>
// Store original values for duplicate checking
const originalAccountNumber = "<?php echo addslashes($account['account_number']); ?>";
const originalAccountName = "<?php echo addslashes($account['name']); ?>";
const currentSubcategory = "<?php echo addslashes($account['subcategory']); ?>";

// Subcategory options based on category
const subcategories = {
    'Assets': ['Cash and Cash Equivalents', 'Accounts Receivable', 'Inventory', 'Prepaid Expenses', 'Property, Plant & Equipment', 'Intangible Assets', 'Investments', 'Other Assets'],
    'Liabilities': ['Accounts Payable', 'Accrued Liabilities', 'Short-term Debt', 'Long-term Debt', 'Deferred Revenue', 'Other Liabilities'],
    'Equity': ['Common Stock', 'Preferred Stock', 'Retained Earnings', 'Additional Paid-in Capital', 'Treasury Stock', 'Other Equity'],
    'Revenue': ['Sales Revenue', 'Service Revenue', 'Interest Revenue', 'Other Revenue'],
    'Expenses': ['Cost of Goods Sold', 'Operating Expenses', 'Administrative Expenses', 'Interest Expense', 'Tax Expense', 'Other Expenses']
};

// Format currency with commas and two decimal places
function formatCurrency(input) {
    let value = input.value.replace(/[^0-9.-]/g, ''); // Remove non-numeric characters except decimal and minus
    
    // Ensure only one decimal point
    let decimalCount = (value.match(/\./g) || []).length;
    if (decimalCount > 1) {
        value = value.replace(/\.(?=.*\.)/, '');
    }
    
    // Convert to number and format
    let num = parseFloat(value);
    if (isNaN(num)) {
        input.value = '0.00';
        return;
    }
    
    // Format with commas and two decimal places
    input.value = num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    
    // Update balance after formatting
    if (input.id !== 'balance') {
        updateBalance();
    }
}

// Parse currency string to number
function parseCurrency(value) {
    return parseFloat(value.replace(/[^0-9.-]/g, '')) || 0;
}

// Validate account number (numbers only, no spaces, no decimals)
function validateAccountNumber() {
    const input = document.getElementById('accountNumber');
    const errorDiv = document.getElementById('accountNumberError');
    let value = input.value;
    
    // Remove any non-numeric characters
    value = value.replace(/[^0-9]/g, '');
    input.value = value;
    
    if (value === '') {
        errorDiv.textContent = '';
        input.classList.remove('validation-error', 'validation-success');
        return;
    }
    
    // Validate format
    if (!/^\d+$/.test(value)) {
        errorDiv.textContent = 'Account number must contain only numbers.';
        input.classList.add('validation-error');
        input.classList.remove('validation-success');
        return false;
    }
    
    // Check length
    if (value.length < 3) {
        errorDiv.textContent = 'Account number must be at least 3 digits.';
        input.classList.add('validation-error');
        input.classList.remove('validation-success');
        return false;
    }
    
    errorDiv.textContent = '';
    input.classList.remove('validation-error');
    input.classList.add('validation-success');
    return true;
}

// Check for duplicate account number (exclude current account)
function checkDuplicateAccount() {
    const input = document.getElementById('accountNumber');
    const errorDiv = document.getElementById('accountNumberError');
    const value = input.value;
    
    if (!value || !validateAccountNumber()) return;
    
    // If it's the same as the original, no need to check
    if (value === originalAccountNumber) {
        errorDiv.textContent = '';
        errorDiv.className = 'success-message';
        errorDiv.textContent = '✓ Current account number.';
        input.classList.remove('validation-error');
        input.classList.add('validation-success');
        return true;
    }
    
    // Simulate AJAX call - replace with actual AJAX call to check_duplicate.php
    const duplicateAccounts = ['1000', '2000', '3000', '4000', '5000'];
    
    if (duplicateAccounts.includes(value)) {
        errorDiv.textContent = 'This account number already exists. Please choose a different number.';
        errorDiv.className = 'error-message';
        input.classList.add('validation-error');
        input.classList.remove('validation-success');
        return false;
    }
    
    errorDiv.textContent = '';
    errorDiv.className = 'success-message';
    errorDiv.textContent = '✓ Account number is available.';
    input.classList.remove('validation-error');
    input.classList.add('validation-success');
    
    return true;
}

// Check for duplicate account name (exclude current account)
function checkDuplicateName() {
    const input = document.getElementById('accountName');
    const errorDiv = document.getElementById('accountNameError');
    const value = input.value.trim();
    
    if (!value) return;
    
    // If it's the same as the original, no need to check
    if (value === originalAccountName) {
        errorDiv.textContent = '';
        errorDiv.className = 'success-message';
        errorDiv.textContent = '✓ Current account name.';
        input.classList.remove('validation-error');
        input.classList.add('validation-success');
        return true;
    }
    
    // Simulate AJAX call - replace with actual AJAX call to check_duplicate.php
    const duplicateNames = ['cash', 'accounts receivable', 'inventory', 'accounts payable'];
    
    if (duplicateNames.includes(value.toLowerCase())) {
        errorDiv.textContent = 'This account name already exists. Please choose a different name.';
        errorDiv.className = 'error-message';
        input.classList.add('validation-error');
        input.classList.remove('validation-success');
        return false;
    }
    
    errorDiv.textContent = '';
    errorDiv.className = 'success-message';
    errorDiv.textContent = '✓ Account name is available.';
    input.classList.remove('validation-error');
    input.classList.add('validation-success');
    
    return true;
}

function updateSubcategories() {
    const category = document.getElementById('category').value;
    const subcategorySelect = document.getElementById('subcategory');
    
    // Clear existing options
    subcategorySelect.innerHTML = '<option value="">Choose Subcategory</option>';
    
    if (category && subcategories[category]) {
        subcategories[category].forEach(function(subcategory) {
            const option = document.createElement('option');
            option.value = subcategory;
            option.textContent = subcategory;
            // Pre-select current subcategory if it matches
            if (subcategory === currentSubcategory) {
                option.selected = true;
            }
            subcategorySelect.appendChild(option);
        });
    }
    
    updateBalance();
}

function updateBalance() {
    const initialBalance = parseCurrency(document.getElementById('initialBalance').value);
    const debitAmount = parseCurrency(document.getElementById('debitAmount').value);
    const creditAmount = parseCurrency(document.getElementById('creditAmount').value);
    const normalSide = document.getElementById('normalSide').value;
    
    let balance;
    
    if (normalSide === 'Debit') {
        // For debit normal side: Balance = Initial + Debits - Credits
        balance = initialBalance + debitAmount - creditAmount;
    } else if (normalSide === 'Credit') {
        // For credit normal side: Balance = Initial + Credits - Debits
        balance = initialBalance + creditAmount - debitAmount;
    } else {
        // No normal side selected, just use initial balance
        balance = initialBalance;
    }
    
    // Format balance with commas and two decimal places
    document.getElementById('balance').value = balance.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function validateForm() {
    const accountNumber = document.getElementById('accountNumber').value.trim();
    const accountName = document.getElementById('accountName').value.trim();
    const normalSide = document.getElementById('normalSide').value;
    const category = document.getElementById('category').value;
    const statement = document.getElementById('statement').value;
    
    // Check for validation errors
    const hasAccountNumberError = document.getElementById('accountNumberError').textContent.includes('already exists') || 
                                  document.getElementById('accountNumberError').textContent.includes('must');
    const hasAccountNameError = document.getElementById('accountNameError').textContent.includes('already exists');
    
    if (hasAccountNumberError) {
        alert('Please fix the account number error before submitting.');
        return false;
    }
    
    if (hasAccountNameError) {
        alert('Please fix the account name error before submitting.');
        return false;
    }
    
    if (!accountNumber) {
        alert('Account Number is required.');
        return false;
    }
    
    if (!validateAccountNumber()) {
        alert('Please enter a valid account number (numbers only, at least 3 digits).');
        return false;
    }
    
    if (!accountName) {
        alert('Account Name is required.');
        return false;
    }
    
    if (!normalSide) {
        alert('Normal Side is required.');
        return false;
    }
    
    if (!category) {
        alert('Category is required.');
        return false;
    }
    
    if (!statement) {
        alert('Financial Statement is required.');
        return false;
    }
    
    return true;
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Format initial currency values
    formatCurrency(document.getElementById('initialBalance'));
    formatCurrency(document.getElementById('debitAmount'));
    formatCurrency(document.getElementById('creditAmount'));
    
    // Populate subcategories based on current category
    updateSubcategories();
    
    // Update balance
    updateBalance();
});
</script>
</body>
</html>