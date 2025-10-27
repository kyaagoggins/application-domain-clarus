<?php
/**
 * Create a New Account Page
 * This page is shown to users to create a new accounting account
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Create New Account</title>
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
        
        /* Tooltip styles */
        .tooltip {
            position: relative;
            display: inline-block;
            margin-left: 5px;
            cursor: help;
        }
        
        .tooltip .tooltiptext {
            visibility: hidden;
            width: 200px;
            background-color: #333;
            color: #fff;
            text-align: left;
            border-radius: 6px;
            padding: 8px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .tooltip .tooltiptext::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #333 transparent transparent transparent;
        }
        
        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
        
        .tooltip-icon {
            color: #007bff;
            font-weight: bold;
            font-size: 14px;
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
    <?php include 'header.php'; ?>
    
    <h1>Create New Account</h1>
    
    <form action="create_account.php" method="POST" onsubmit="return validateForm()">
        <div class="form-container">
            <!-- Left Column -->
            <div class="form-column">
                <div class="form-group">
                    <label for="accountNumber">Account Number <span class="required">*</span>
                        <span class="tooltip">
                            <span class="tooltip-icon">?</span>
                            <span class="tooltiptext">Enter a unique numeric identifier for this account. Use only numbers (no letters, spaces, or special characters). Examples: 1000, 2000, 3000. This will be used to identify the account in transactions.</span>
                        </span>
                    </label>
                    <input type="text" id="accountNumber" name="account_number" required maxlength="20" 
                           oninput="validateAccountNumber()" onblur="checkDuplicateAccount()">
                    <div id="accountNumberError" class="error-message"></div>
                    <div class="help-text">Enter a unique account number (numbers only, no spaces or decimals)</div>
                </div>
                
                <div class="form-group">
                    <label for="accountName">Account Name <span class="required">*</span>
                        <span class="tooltip">
                            <span class="tooltip-icon">?</span>
                            <span class="tooltiptext">Enter a descriptive name for this account. Use clear, professional naming conventions. Examples: "Cash", "Accounts Receivable", "Office Supplies", "Sales Revenue". This name will appear on financial statements.</span>
                        </span>
                    </label>
                    <input type="text" id="accountName" name="name" required maxlength="100" onblur="checkDuplicateName()">
                    <div id="accountNameError" class="error-message"></div>
                    <div class="help-text">Enter a unique account name (e.g., Cash, Accounts Receivable)</div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description
                        <span class="tooltip">
                            <span class="tooltip-icon">?</span>
                            <span class="tooltiptext">Optional detailed description providing additional context about this account. Include specific details about what transactions should be recorded here, account purpose, or any special instructions for users.</span>
                        </span>
                    </label>
                    <textarea id="description" name="description" rows="3" maxlength="500"></textarea>
                    <div class="help-text">Optional detailed description of the account</div>
                </div>
                
                <div class="form-group">
                    <label for="normalSide">Normal Side <span class="required">*</span>
                        <span class="tooltip">
                            <span class="tooltip-icon">?</span>
                            <span class="tooltiptext">Select whether this account normally has a debit or credit balance. Assets and Expenses normally have debit balances. Liabilities, Equity, and Revenue normally have credit balances. This determines how increases and decreases are recorded.</span>
                        </span>
                    </label>
                    <select id="normalSide" name="normal_side" required onchange="updateBalance()">
                        <option value="">Choose Normal Side</option>
                        <option value="Debit">Debit</option>
                        <option value="Credit">Credit</option>
                    </select>
                    <div class="help-text">Assets/Expenses = Debit, Liabilities/Equity/Revenue = Credit</div>
                </div>
                
                <div class="form-group">
                    <label for="category">Category <span class="required">*</span>
                        <span class="tooltip">
                            <span class="tooltip-icon">?</span>
                            <span class="tooltiptext">Select the main classification for this account. Assets: things owned (cash, equipment). Liabilities: debts owed (loans, payables). Equity: ownership interest (capital, retained earnings). Revenue: income earned. Expenses: costs incurred.</span>
                        </span>
                    </label>
                    <select id="category" name="category" required onchange="updateSubcategories()">
                        <option value="">Choose Category</option>
                        <option value="Assets">Assets</option>
                        <option value="Liabilities">Liabilities</option>
                        <option value="Equity">Equity</option>
                        <option value="Revenue">Revenue</option>
                        <option value="Expenses">Expenses</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="subcategory">Subcategory
                        <span class="tooltip">
                            <span class="tooltip-icon">?</span>
                            <span class="tooltiptext">Select a more specific classification within the chosen category. This helps organize accounts for reporting and analysis. Examples: Current Assets, Fixed Assets, Operating Expenses, etc.</span>
                        </span>
                    </label>
                    <select id="subcategory" name="subcategory">
                        <option value="">Choose Subcategory</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="statement">Financial Statement <span class="required">*</span>
                        <span class="tooltip">
                            <span class="tooltip-icon">?</span>
                            <span class="tooltiptext">Select which financial statement this account will appear on. Balance Sheet: Assets, Liabilities, Equity. Income Statement: Revenue, Expenses. Statement of Cash Flows: Cash-related accounts. Statement of Equity: Equity accounts.</span>
                        </span>
                    </label>
                    <select id="statement" name="statement" required>
                        <option value="">Choose Statement</option>
                        <option value="Balance Sheet">Balance Sheet</option>
                        <option value="Income Statement">Income Statement</option>
                        <option value="Statement of Cash Flows">Statement of Cash Flows</option>
                        <option value="Statement of Equity">Statement of Equity</option>
                    </select>
                </div>
            </div>
            
            <!-- Right Column -->
            <div class="form-column">
                <div class="form-group">
                    <label for="initialBalance">Initial Balance
                        <span class="tooltip">
                            <span class="tooltip-icon">?</span>
                            <span class="tooltiptext">Enter the starting balance for this account when it's created. This represents the account's value at the time of setup. Leave as 0.00 if this is a new account with no initial balance.</span>
                        </span>
                    </label>
                    <input type="text" id="initialBalance" name="initial_balance" value="0.00" 
                           oninput="formatCurrency(this)" onchange="updateBalance()">
                    <div class="help-text">Starting balance for this account</div>
                </div>
                
                <div class="form-group">
                    <label for="debitAmount">Debit Amount
                        <span class="tooltip">
                            <span class="tooltip-icon">?</span>
                            <span class="tooltiptext">Enter any initial debit amount for this account. Debits increase asset and expense accounts, and decrease liability, equity, and revenue accounts. Leave as 0.00 if no initial debit is needed.</span>
                        </span>
                    </label>
                    <input type="text" id="debitAmount" name="debit" value="0.00" 
                           oninput="formatCurrency(this)" onchange="updateBalance()">
                    <div class="help-text">Initial debit amount (if any)</div>
                </div>
                
                <div class="form-group">
                    <label for="creditAmount">Credit Amount
                        <span class="tooltip">
                            <span class="tooltip-icon">?</span>
                            <span class="tooltiptext">Enter any initial credit amount for this account. Credits increase liability, equity, and revenue accounts, and decrease asset and expense accounts. Leave as 0.00 if no initial credit is needed.</span>
                        </span>
                    </label>
                    <input type="text" id="creditAmount" name="credit" value="0.00" 
                           oninput="formatCurrency(this)" onchange="updateBalance()">
                    <div class="help-text">Initial credit amount (if any)</div>
                </div>
                
                <div class="form-group">
                    <label for="balance">Current Balance
                        <span class="tooltip">
                            <span class="tooltip-icon">?</span>
                            <span class="tooltiptext">This field is automatically calculated based on the initial balance, debit amount, and credit amount you entered. The calculation considers the normal side of the account to determine the final balance.</span>
                        </span>
                    </label>
                    <input type="text" id="balance" name="balance" value="0.00" readonly>
                    <div class="help-text">Calculated automatically based on normal side and amounts</div>
                </div>
                
                <div class="form-group">
                    <label for="orderType">Order Type
                        <span class="tooltip">
                            <span class="tooltip-icon">?</span>
                            <span class="tooltiptext">Select how this account should be ordered on financial statements. This helps organize accounts in a logical sequence for reporting. Examples: Current Assets appear before Non-Current Assets on the Balance Sheet.</span>
                        </span>
                    </label>
                    <select id="orderType" name="order_type">
                        <option value="">Choose Order Type</option>
                        <option value="Current Assets">Current Assets</option>
                        <option value="Non-Current Assets">Non-Current Assets</option>
                        <option value="Current Liabilities">Current Liabilities</option>
                        <option value="Non-Current Liabilities">Non-Current Liabilities</option>
                        <option value="Operating Expenses">Operating Expenses</option>
                        <option value="Non-Operating Expenses">Non-Operating Expenses</option>
                        <option value="Operating Revenue">Operating Revenue</option>
                        <option value="Non-Operating Revenue">Non-Operating Revenue</option>
                    </select>
                    <div class="help-text">Classification for financial statement ordering</div>
                </div>
                
                <div class="form-group">
                    <label for="comment">Comments
                        <span class="tooltip">
                            <span class="tooltip-icon">?</span>
                            <span class="tooltiptext">Enter any additional notes, comments, or special instructions about this account. This can include setup notes, usage guidelines, or any other relevant information that will help users understand how to use this account properly.</span>
                        </span>
                    </label>
                    <textarea id="comment" name="comment" rows="4" maxlength="1000"></textarea>
                    <div class="help-text">Additional notes or comments about this account</div>
                </div>
            </div>
            
            <!-- Form Footer -->
            <div class="form-footer">
                <button type="submit" class="submit-btn">💾 Create Account</button>
                <br>
                <br>
                <a href="accounts_dashboard.php" class="cancel-btn">❌ Cancel</a>
            </div>
        </div>
    </form>
</div>

<script>
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

// Check for duplicate account number (would typically use AJAX)
function checkDuplicateAccount() {
    const input = document.getElementById('accountNumber');
    const errorDiv = document.getElementById('accountNumberError');
    const value = input.value;
    
    if (!value || !validateAccountNumber()) return;
    
    // Simulate AJAX call - replace with actual AJAX call to check_duplicate.php
    // For demonstration, we'll simulate some duplicate account numbers
    const duplicateAccounts = ['1000', '2000', '3000', '4000', '5000'];
    
    if (duplicateAccounts.includes(value)) {
        errorDiv.textContent = 'This account number already exists. Please choose a different number.';
        input.classList.add('validation-error');
        input.classList.remove('validation-success');
        return false;
    }
    
    // Show success if no duplicate found
    if (errorDiv.textContent !== 'Account number must be at least 3 digits.' && 
        errorDiv.textContent !== 'Account number must contain only numbers.') {
        errorDiv.textContent = '';
        errorDiv.className = 'success-message';
        errorDiv.textContent = '✓ Account number is available.';
        input.classList.remove('validation-error');
        input.classList.add('validation-success');
    }
    
    return true;
}

// Check for duplicate account name (would typically use AJAX)
function checkDuplicateName() {
    const input = document.getElementById('accountName');
    const errorDiv = document.getElementById('accountNameError');
    const value = input.value.trim();
    
    if (!value) return;
    
    // Simulate AJAX call - replace with actual AJAX call to check_duplicate.php
    // For demonstration, we'll simulate some duplicate account names
    const duplicateNames = ['cash', 'accounts receivable', 'inventory', 'accounts payable'];
    
    if (duplicateNames.includes(value.toLowerCase())) {
        errorDiv.textContent = 'This account name already exists. Please choose a different name.';
        input.classList.add('validation-error');
        input.classList.remove('validation-success');
        return false;
    }
    
    // Show success if no duplicate found
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
            subcategorySelect.appendChild(option);
        });
    }
    
    // Auto-update normal side based on category
    const normalSideSelect = document.getElementById('normalSide');
    if (category === 'Assets' || category === 'Expenses') {
        normalSideSelect.value = 'Debit';
    } else if (category === 'Liabilities' || category === 'Equity' || category === 'Revenue') {
        normalSideSelect.value = 'Credit';
    }
    
    // Auto-update statement based on category
    const statementSelect = document.getElementById('statement');
    if (category === 'Assets' || category === 'Liabilities' || category === 'Equity') {
        statementSelect.value = 'Balance Sheet';
    } else if (category === 'Revenue' || category === 'Expenses') {
        statementSelect.value = 'Income Statement';
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

// Initialize formatting on page load
document.addEventListener('DOMContentLoaded', function() {
    // Format initial currency values
    formatCurrency(document.getElementById('initialBalance'));
    formatCurrency(document.getElementById('debitAmount'));
    formatCurrency(document.getElementById('creditAmount'));
    updateBalance();
});
</script>
</body>
</html>