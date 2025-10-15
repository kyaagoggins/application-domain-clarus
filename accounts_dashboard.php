<?php
/**
 * Accounts Management Dashboard
 * This page shows all accounts in the system for management purposes
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
$canEditAccounts = ($userAccessLevel >= 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Accounts Management</title>
</head>
<body>
    <div class="container" style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none; -ms-overflow-style: none;">
    <!--<img src="https://thumbs.dreamstime.com/b/calculator-icon-vector-isolated-white-background-your-web-mobile-app-design-calculator-logo-concept-calculator-icon-134617239.jpg" width="100px">-->
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
        <a style="float:right; margin-right: 30px; padding: 10px; text-decoration:none; background-color: #efefef; color: black; font-size: 14px" href="view_journal_entries.php">View Journal Entries</a>
   </h2>
    <div style="clear:both; margin-bottom: 30px"></div>
    
    <?php
    include '../db_connect.php';

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all accounts with relevant information
    $stmt = $pdo->query("
        SELECT 
            account_number,
            name,
            category,
            subcategory,
            normal_side,
            balance,
            statement,
            order_type,
            is_active,
            created_at,
            CASE 
                WHEN is_active = 1 THEN 'Active'
                ELSE 'Inactive'
            END AS status_display,
            CASE 
                WHEN balance = 0 THEN 'Zero Balance'
                WHEN balance > 0 THEN 'Positive Balance'
                ELSE 'Negative Balance'
            END AS balance_status
        FROM accounts 
        ORDER BY category, account_number
    ");
    
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate statistics
    $totalAccounts = count($accounts);
    $activeAccounts = count(array_filter($accounts, function($a) { return $a['is_active']; }));
    $inactiveAccounts = $totalAccounts - $activeAccounts;
    $zeroBalanceAccounts = count(array_filter($accounts, function($a) { return (float)$a['balance'] == 0; }));
    
    // Calculate total balances by category
    $categoryTotals = [];
    foreach ($accounts as $account) {
        if (!isset($categoryTotals[$account['category']])) {
            $categoryTotals[$account['category']] = 0;
        }
        $categoryTotals[$account['category']] += (float)$account['balance'];
    }
    
} catch(PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Format money function
function formatMoney($value) {
    return '$' . number_format((float)$value, 2);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Accounts Management</title>
    <style>
        table { 
            border-collapse: collapse; 
            width: 100%; 
            margin: 20px 0; 
            border-radius: 16px;
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: left; 
        }
        th { 
            background-color: rgb(41, 128, 185);
            color: white; 
        }
        tr:nth-child(even) { 
            background-color: #f2f2f2; 
        }
        .positive-balance { 
            color: green; 
            font-weight: bold; 
        }
        .negative-balance { 
            color: red; 
            font-weight: bold; 
        }
        .zero-balance { 
            color: #666; 
        }
        .active-status { 
            color: green; 
            font-weight: bold; 
        }
        .inactive-status { 
            color: red; 
            font-weight: bold; 
        }
        .action-btn {
            padding: 5px 10px;
            margin: 2px;
            border: none;
            border-radius: 3px;
            font-size: 12px;
            display: inline-block;
            text-align: center;
            min-width: 60px;
            cursor: pointer;
            text-decoration: none;
        }
        
        /* Navigation Button Styles */
        .nav-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            margin-left: 10px;
            color: white;
            font-weight: bold;
        }
        .nav-btn:first-child {
            margin-left: 0;
        }
        .nav-btn-add {
            background-color: #2980b9;
        }
        .nav-btn-add:hover {
            background-color: #2980b9;
        }
        .nav-btn-filter {
            background-color: #2980b9;
        }
        .nav-btn-filter:hover {
            background-color: #2980b9;
        }
        .nav-btn-export {
            background-color: #2980b9;
        }
        .nav-btn-export:hover {
            background-color: #2980b9;
        }
        
        .view-btn {
            background-color: #2980b9;
            color: white;
        }
        .view-btn:hover {
            background-color: #2980b9;
        }
        .edit-btn {
            background-color: #2980b9;
            color: white;
        }
        .edit-btn:hover {
            background-color: #2980b9;
        }
        .deactivate-btn {
            background-color: #fd7e14;
            color: white;
        }
        .deactivate-btn:hover {
            background-color: #e66a02;
        }
        .activate-btn {
            background-color: #17a2b8;
            color: white;
        }
        .activate-btn:hover {
            background-color: #138496;
        }
        .disabled-btn {
            background-color: #adb5bd;
            color: white;
            cursor: not-allowed;
        }
        .actions-column {
            width: 200px;
            text-align: center;
        }
        .inactive-row {
            opacity: 0.6;
            background-color: #f5f5f5;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: linear-gradient(135deg, #2980b9, #6dd5fa, #ffffff);
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        .stat-card .stat-number {
            font-size: 24px;
            font-weight: bold;
        }
        
        .filter-container {
            margin: 15px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        .filter-container select, .filter-container input {
            padding: 5px 10px;
            margin: 0 10px 0 5px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        
        .account-number {
            font-family: monospace;
            font-weight: bold;
        }
        .balance-cell {
            text-align: right;
            font-family: monospace;
        }
    </style>
</head>
<body>
    
    <h1>Accounts Management Dashboard</h1>
    
    <!-- Statistics Cards -->
    <div class="stats-container">
        <div class="stat-card">
            <h3>Total Accounts</h3>
            <div class="stat-number"><?php echo $totalAccounts; ?></div>
        </div>
        <div class="stat-card">
            <h3>Active Accounts</h3>
            <div class="stat-number"><?php echo $activeAccounts; ?></div>
        </div>
        <div class="stat-card">
            <h3>Inactive Accounts</h3>
            <div class="stat-number"><?php echo $inactiveAccounts; ?></div>
        </div>
        <div class="stat-card">
            <h3>Zero Balance</h3>
            <div class="stat-number"><?php echo $zeroBalanceAccounts; ?></div>
        </div>
    </div>
    
    <!-- Navigation Buttons -->
    <div style="margin-top: 20px;">
        <?php if ($canEditAccounts): ?>
        <button style="width:250px" onclick="addNewAccount()" title="Add New Account" class="nav-btn nav-btn-add">
            ➕ Add New Account
        </button>
        <?php endif; ?>
        <button style="width:250px" onclick="addNewAccount()" title="Add a New Account Record" class="nav-btn nav-btn-add">
            ➕ Add New Account
        </button>
        <button style="width:250px" onclick="toggleInactiveAccounts()" title="Show/Hide Inactive Accounts" class="nav-btn nav-btn-filter">
            👁️ Toggle Inactive Accounts
        </button>
        <button style="width:250px" onclick="exportAccountsReport()" title="Export All Accounts Data to a CSV" class="nav-btn nav-btn-export">
            📊 Export Report
        </button>
    </div>
    
    <!-- Filters -->
    <div class="filter-container">
        <strong>Filters:</strong>
        <select id="categoryFilter" onchange="filterTable()">
            <option value="">All Categories</option>
            <option value="Assets">Assets</option>
            <option value="Liabilities">Liabilities</option>
            <option value="Equity">Equity</option>
            <option value="Revenue">Revenue</option>
            <option value="Expenses">Expenses</option>
        </select>
        
        <select id="balanceFilter" onchange="filterTable()">
            <option value="">All Balances</option>
            <option value="positive">Positive Balance</option>
            <option value="negative">Negative Balance</option>
            <option value="zero">Zero Balance</option>
        </select>
        
        <input type="text" id="searchFilter" placeholder="Search accounts..." onkeyup="filterTable()">
        
        <button onclick="clearFilters()" style="padding: 5px 10px; margin-left: 10px;">Clear Filters</button>
    </div>
    
    <table id="accountsTable">
        <tr>
            <th>Account #</th>
            <th>Account Name</th>
            <th>Category</th>
            <th>Subcategory</th>
            <th>Normal Side</th>
            <th>Balance</th>
            <th>Statement</th>
            <th>Status</th>
            <th>Created Date</th>
            <th class="actions-column">Actions</th>
        </tr>
        
        <?php foreach ($accounts as $account): ?>
        <tr class="account-row <?php echo !$account['is_active'] ? 'inactive-row' : ''; ?>" 
            data-category="<?php echo strtolower($account['category']); ?>"
            data-balance="<?php echo (float)$account['balance'] > 0 ? 'positive' : ((float)$account['balance'] < 0 ? 'negative' : 'zero'); ?>"
            data-active="<?php echo $account['is_active'] ? '1' : '0'; ?>">
            
            <td class="account-number"><?php echo htmlspecialchars($account['account_number']); ?></td>
            <td><?php echo htmlspecialchars($account['name']); ?></td>
            <td><?php echo htmlspecialchars($account['category']); ?></td>
            <td><?php echo htmlspecialchars($account['subcategory']); ?></td>
            <td><?php echo htmlspecialchars($account['normal_side']); ?></td>
            <td class="balance-cell <?php echo (float)$account['balance'] > 0 ? 'positive-balance' : ((float)$account['balance'] < 0 ? 'negative-balance' : 'zero-balance'); ?>">
                <?php echo formatMoney($account['balance']); ?>
            </td>
            <td><?php echo htmlspecialchars($account['statement']); ?></td>
            <td class="<?php echo $account['is_active'] ? 'active-status' : 'inactive-status'; ?>">
                <?php echo $account['status_display']; ?>
            </td>
            <td><?php echo date('M j, Y', strtotime($account['created_at'])); ?></td>
            <td class="actions-column">
                <button class="action-btn view-btn" 
                        onclick="viewAccount('<?php echo htmlspecialchars($account['account_number']); ?>')"
                        title="View Details about this Account">
                    👁️ View
                </button>
                
                <?php if ($canEditAccounts): ?>
                    <?php if ($account['is_active']): ?>
                        <button class="action-btn edit-btn" 
                                onclick="editAccount('<?php echo htmlspecialchars($account['account_number']); ?>')"
                                title="Edit Account">
                            ✏️ Edit
                        </button>
                        
                        <?php if ((float)$account['balance'] == 0): ?>
                            <button class="action-btn deactivate-btn" 
                                    onclick="confirmDeactivateAccount('<?php echo htmlspecialchars($account['account_number']); ?>', '<?php echo htmlspecialchars($account['name']); ?>')"
                                    title="Deactivate Account">
                                🚫 Deactivate
                            </button>
                        <?php else: ?>
                            <button class="action-btn disabled-btn" 
                                    onclick="showBalanceAlert('<?php echo formatMoney($account['balance']); ?>')"
                                    title="Cannot deactivate - Non-zero balance">
                                🚫 Deactivate
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <button class="action-btn activate-btn" 
                                onclick="confirmReactivateAccount('<?php echo htmlspecialchars($account['account_number']); ?>', '<?php echo htmlspecialchars($account['name']); ?>')"
                                title="Reactivate Account">
                            ✅ Reactivate
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <!-- Category Totals -->
    <div style="margin-top: 30px;">
        <h3>Balance Totals by Category</h3>
        <table style="width: 400px;">
            <tr>
                <th>Category</th>
                <th style="text-align: right;">Total Balance</th>
            </tr>
            <?php foreach ($categoryTotals as $category => $total): ?>
            <tr>
                <td><?php echo htmlspecialchars($category); ?></td>
                <td style="text-align: right; font-family: monospace;" class="<?php echo $total > 0 ? 'positive-balance' : ($total < 0 ? 'negative-balance' : 'zero-balance'); ?>">
                    <?php echo formatMoney($total); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <script>
        let showInactive = true;
        
        // Navigation button functions
        function addNewAccount() {
            window.location.href = 'new_account.php';
        }
        
        // Account management functions
        function viewAccount(accountNumber) {
            window.location.href = 'view_account.php?account_number=' + encodeURIComponent(accountNumber);
        }
        
        function editAccount(accountNumber) {
            window.location.href = 'edit_account.php?account_number=' + encodeURIComponent(accountNumber);
        }
        
        function confirmDeactivateAccount(accountNumber, accountName) {
            if (confirm('Are you sure you want to DEACTIVATE account ' + accountNumber + ' - ' + accountName + '?\n\nThis will hide the account from normal operations.')) {
                window.location.href = 'deactivate_account.php?account_number=' + encodeURIComponent(accountNumber) + '&action=deactivate';
            }
        }
        
        function confirmReactivateAccount(accountNumber, accountName) {
            if (confirm('Are you sure you want to REACTIVATE account ' + accountNumber + ' - ' + accountName + '?\n\nThis will restore the account to normal operations.')) {
                window.location.href = 'deactivate_account.php?account_number=' + encodeURIComponent(accountNumber) + '&action=reactivate';
            }
        }
        
        function showBalanceAlert(balance) {
            alert('Cannot deactivate account with non-zero balance.\n\nCurrent Balance: ' + balance + '\n\nPlease adjust the balance to $0.00 before deactivating.');
        }
        
        function toggleInactiveAccounts() {
            showInactive = !showInactive;
            const inactiveRows = document.querySelectorAll('.inactive-row');
            
            inactiveRows.forEach(row => {
                row.style.display = showInactive ? '' : 'none';
            });
            
            const button = event.target;
            button.textContent = showInactive ? '👁️ Hide Inactive Accounts' : '👁️ Show Inactive Accounts';
        }
        
        function filterTable() {
            const categoryFilter = document.getElementById('categoryFilter').value.toLowerCase();
            const balanceFilter = document.getElementById('balanceFilter').value;
            const searchFilter = document.getElementById('searchFilter').value.toLowerCase();
            const rows = document.querySelectorAll('.account-row');
            
            rows.forEach(row => {
                const category = row.dataset.category;
                const balance = row.dataset.balance;
                const active = row.dataset.active;
                const text = row.textContent.toLowerCase();
                
                let showRow = true;
                
                // Category filter
                if (categoryFilter && category !== categoryFilter) {
                    showRow = false;
                }
                
                // Balance filter
                if (balanceFilter && balance !== balanceFilter) {
                    showRow = false;
                }
                
                // Search filter
                if (searchFilter && !text.includes(searchFilter)) {
                    showRow = false;
                }
                
                // Inactive filter
                if (!showInactive && active === '0') {
                    showRow = false;
                }
                
                row.style.display = showRow ? '' : 'none';
            });
        }
        
        function clearFilters() {
            document.getElementById('categoryFilter').value = '';
            document.getElementById('balanceFilter').value = '';
            document.getElementById('searchFilter').value = '';
            filterTable();
        }
        
        function exportAccountsReport() {
            // Simple CSV export
            let csv = 'Account Number,Account Name,Category,Subcategory,Normal Side,Balance,Statement,Status,Created Date\n';
            
            const rows = document.querySelectorAll('.account-row');
            rows.forEach(row => {
                if (row.style.display !== 'none') {
                    const cells = row.querySelectorAll('td');
                    const rowData = [];
                    for (let i = 0; i < cells.length - 1; i++) { // Exclude actions column
                        rowData.push('"' + cells[i].textContent.trim().replace(/"/g, '""') + '"');
                    }
                    csv += rowData.join(',') + '\n';
                }
            });
            
            // Download CSV
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'accounts_report_' + new Date().toISOString().split('T')[0] + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
    </script>
</div>
</body>
</html>