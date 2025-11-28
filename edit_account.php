<?php
//KSU student project for Clarus Accounting tool
//This page is used to edit an account's specific details
//Initially drafted by Eric Poole. Reviewed and updated by Kyaa Goggins
//This page was created to meet Sprint 2 requirements
//11-28: Eric made the edit account number field read only after discussing with team

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

//user session details 
$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];

// Get account_number from URL parameter
$account_number = $_GET['account_number'];

if (!$account_number) {
    die("Hmm.. something went wrong. There is no account number in the URL. Please go back and try again.");
}

// Include database configuration
include '../db_connect.php';

// Fetch account details from database
$pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->prepare("SELECT * FROM accounts WHERE account_number = :account_number");
$stmt->execute([':account_number' => $account_number]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$account) {
    die("Hmm.. something went wrong. There was no account found with the provided account number. Please go back and try again.");
}

// Remove commas and dollar signs from dollar value fields
function formatMoneyForEdit($value)
{
    return number_format((float) $value, 2, '.', '');
}

include 'header.php';
?>

<link rel="stylesheet" href="/styling/edit_account.css">
<div class="container"
    style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none; -ms-overflow-style: none;">

    <div class="account-header">
        <div class="account-name"><?php echo $account['name']; ?></div>
    </div>

    <h1><i class="fa-solid fa-pen-to-square"></i> Edit Account</h1>

    <form action="push_edit_account.php" method="POST" onsubmit="return validateForm()">
        <!--This is a hidden field from when we used to allow the user to change the account number, we disabled this feature during testing -->
        <input type="hidden" name="original_name" value="<?php echo $account['name']; ?>">

        <div class="form-container">
            <!-- Left Column -->
            <div class="form-column">
                <div class="form-group">
                    <label for="accountNumber">Account Number (cannot be changed)</label>
                    <input readonly disabled type="text" id="accountNumber" name="account_number"
                        value="<?php echo $account['account_number']; ?>" required maxlength="20"
                        oninput="validateAccountNumber()" onblur="checkDuplicateAccount()">
                    <div id="accountNumberError" class="error-message"></div>
                    <div class="help-text">Enter a unique account number (numbers only, no spaces or decimals)</div>
                </div>

                <div class="form-group">
                    <label for="accountName">Account Name <span class="required">*</span></label>
                    <input type="text" id="accountName" name="name" value="<?php echo $account['name']; ?>" required
                        maxlength="100" onblur="checkDuplicateName()">
                    <div id="accountNameError" class="error-message"></div>
                    <div class="help-text">Enter a unique account name (e.g., Cash, Accounts Receivable)</div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"
                        maxlength="500"><?php echo $account['description']; ?></textarea>
                    <div class="help-text">Optional detailed description of the account</div>
                </div>

                <div class="form-group">
                    <label for="normalSide">Normal Side <span class="required">*</span></label>
                    <select id="normalSide" name="normal_side" required onchange="updateBalance()">
                        <option value="">Choose Normal Side</option>
                        <option value="Debit" <?php echo $account['normal_side'] == 'Debit' ? 'selected' : ''; ?>>
                            Debit</option>
                        <option value="Credit" <?php echo $account['normal_side'] == 'Credit' ? 'selected' : ''; ?>>
                            Credit</option>
                    </select>
                    <div class="help-text">Assets/Expenses = Debit, Liabilities/Equity/Revenue = Credit</div>
                </div>

                <div class="form-group">
                    <label for="category">Category <span class="required">*</span></label>
                    <select id="category" name="category" required onchange="updateSubcategories()">
                        <option value="">Choose Category</option>
                        <!--The following option elements have php that references this account in the table, checks its existing category, and adds the "selected" attribute so that the form is prepopulated for the user -->
                        <option value="Assets" <?php echo $account['category'] == 'Assets' ? 'selected' : ''; ?>>
                            Assets</option>
                        <option value="Liabilities" <?php echo $account['category'] == 'Liabilities' ? 'selected' : ''; ?>>Liabilities</option>
                        <option value="Equity" <?php echo $account['category'] == 'Equity' ? 'selected' : ''; ?>>
                            Equity</option>
                        <option value="Revenue" <?php echo $account['category'] == 'Revenue' ? 'selected' : ''; ?>>
                            Revenue</option>
                        <option value="Expenses" <?php echo $account['category'] == 'Expenses' ? 'selected' : ''; ?>>
                            Expenses</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="subcategory">Subcategory</label>
                    <select id="subcategory" name="subcategory">
                        <option value="">Choose Subcategory</option>
                        <!-- Will be populated by the JavaScript function updateSubCategories on page load-->
                    </select>
                </div>

                <div class="form-group">
                    <label for="statement">Financial Statement <span class="required">*</span></label>
                    <select id="statement" name="statement" required>
                        <option value="">Choose Statement</option>
                        <!--The following option elements have php that references this account in the table, checks its existing statement type, and adds the "selected" attribute so that the form is prepopulated for the user -->
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
                        value="<?php echo formatMoneyForEdit($account['debit']); ?>" oninput="formatCurrency(this)"
                        onchange="updateBalance()">
                    <div class="help-text">Total debit transactions</div>
                </div>

                <div class="form-group">
                    <label for="creditAmount">Credit Amount</label>
                    <input type="text" id="creditAmount" name="credit"
                        value="<?php echo formatMoneyForEdit($account['credit']); ?>" oninput="formatCurrency(this)"
                        onchange="updateBalance()">
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
                        <!--The following option elements have php that references this account in the table, checks its existing order type, and adds the "selected" attribute so that the form is prepopulated for the user -->
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
                    <textarea id="comment" name="comment" rows="4"
                        maxlength="1000"><?php echo $account['comment']; ?></textarea>
                    <div class="help-text">Additional notes or comments about this account</div>
                </div>
            </div>

            <!-- Form Footer -->
            <div class="form-footer">
                <button type="submit" class="submit-btn">Update Account <i class="fa-solid fa-pen"></i></button>
                <br><br>
                <a href="view_account.php?account_number=<?php echo $account['account_number']; ?>"
                    class="cancel-btn">View Account</a>
                <a href="accounts_dashboard.php" class="cancel-btn">Cancel</a>
            </div>
        </div>
    </form>
</div>

<script>
    // Store original values for duplicate checking
    const originalAccountNumber = "<?php echo $account['account_number']; ?>";
    const originalAccountName = "<?php echo $account['name']; ?>";
    const currentSubcategory = "<?php echo $account['subcategory']; ?>";

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

        // Check length of account number 
        if (value.length < 3) {
            errorDiv.textContent = 'Account number must be at least 3 digits.';
            input.classList.add('validation-error');
            input.classList.remove('validation-success');
            return false;
        }

        //error validation handling ui
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

        // Placeholder values, duplicate check to tables still under development
        const duplicateAccounts = ['1000', '2000', '3000', '4000', '5000'];

        if (duplicateAccounts.includes(value)) {
            errorDiv.textContent = 'This account number is taken. Please choose a different number.';
            errorDiv.className = 'error-message';
            input.classList.add('validation-error');
            input.classList.remove('validation-success');
            return false;
        }

        errorDiv.textContent = '';
        errorDiv.className = 'success-message';
        errorDiv.textContent = 'Account number is available.';
        input.classList.remove('validation-error');
        input.classList.add('validation-success');

        return true;
    }

    // Check for duplicate account name (ignores current account's name to resolve a bug we found)
    function checkDuplicateName() {
        const input = document.getElementById('accountName');
        const errorDiv = document.getElementById('accountNameError');
        const value = input.value.trim();

        if (!value) return;

        // If it's the same as the original, no need to check it, carry on
        if (value === originalAccountName) {
            errorDiv.textContent = '';
            errorDiv.className = 'success-message';
            errorDiv.textContent = '✓ Current account name.';
            input.classList.remove('validation-error');
            input.classList.add('validation-success');
            return true;
        }

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
        errorDiv.textContent = 'Account name is available.';
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
            subcategories[category].forEach(function (subcategory) {
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

    //function to dynamically update balance information based on inputted information by the user 
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

    //validate inputted information by the user 
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
            alert('Wait! Please fix the account number error before submitting.');
            return false;
        }

        if (hasAccountNameError) {
            alert('Wait! Please fix the account name error before submitting.');
            return false;
        }

        if (!accountNumber) {
            alert('Wait! Account Number is required.');
            return false;
        }

        if (!validateAccountNumber()) {
            alert('Wait! Please enter a valid account number (only numbers and at least 3 digits).');
            return false;
        }

        if (!accountName) {
            alert('Wait! Account Name is required.');
            return false;
        }

        if (!normalSide) {
            alert('Wait! Normal Side is required.');
            return false;
        }

        if (!category) {
            alert('Wait! Category is required.');
            return false;
        }

        if (!statement) {
            alert('Wait! Financial Statement is required.');
            return false;
        }

        return true;
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function () {
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