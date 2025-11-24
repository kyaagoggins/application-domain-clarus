<?php
//KSU student project for Clarus Accounting tool
//This page is used to view a dashboard of all accounts
//Initially drafted by Eric Poole. Reviewed and updated by Kyaa Goggins
//This page is where the user can view all accounts in the application. Added for ease of use of application.

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
$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];
$userAccessLevel = $_SESSION['access_level'];
$canEditAccounts = ($userAccessLevel >= 2);

include 'header.php';
?>

<link rel="stylesheet" href="/styling/accounts_dashboard.css">
<div class="container"
    style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none; -ms-overflow-style: none;">
    <?php
    include '../db_connect.php';


    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get all accounts with relevant information
    $getAccounts = $pdo->query("
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

    $accounts = $getAccounts->fetchAll(PDO::FETCH_ASSOC);

    // Calculate statistics
    $totalAccounts = count($accounts);
    $activeAccounts = count(array_filter($accounts, function ($a) {
        return $a['is_active'];
    }));
    $inactiveAccounts = $totalAccounts - $activeAccounts;
    $zeroBalanceAccounts = count(array_filter($accounts, function ($a) {
        return (float) $a['balance'] == 0;
    }));

    // Calculate total balances by category
    $categoryTotals = [];
    foreach ($accounts as $account) {
        if (!isset($categoryTotals[$account['category']])) {
            $categoryTotals[$account['category']] = 0;
        }
        $categoryTotals[$account['category']] += (float) $account['balance'];
    }



    // Format money function
    function formatMoney($value)
    {
        return '$' . number_format((float) $value, 2);
    }
    ?>

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
                <i class="fa-solid fa-plus"></i> Add New Account
            </button>
        <?php endif; ?>
        <button style="width:250px" onclick="addNewAccount()" title="Add a New Account Record"
            class="nav-btn nav-btn-add">
            <i class="fa-solid fa-plus"></i> Add New Account
        </button>
        <button style="width:250px" onclick="toggleInactiveAccounts()" title="Show/Hide Inactive Accounts"
            class="nav-btn nav-btn-filter">
            <i class="fa-solid fa-eye"></i> Toggle Inactive Accounts
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
                data-balance="<?php echo (float) $account['balance'] > 0 ? 'positive' : ((float) $account['balance'] < 0 ? 'negative' : 'zero'); ?>"
                data-active="<?php echo $account['is_active'] ? '1' : '0'; ?>">

                <td class="account-number"><?php echo htmlspecialchars($account['account_number']); ?></td>
                <td><?php echo htmlspecialchars($account['name']); ?></td>
                <td><?php echo htmlspecialchars($account['category']); ?></td>
                <td><?php echo htmlspecialchars($account['subcategory']); ?></td>
                <td><?php echo htmlspecialchars($account['normal_side']); ?></td>
                <td
                    class="balance-cell <?php echo (float) $account['balance'] > 0 ? 'positive-balance' : ((float) $account['balance'] < 0 ? 'negative-balance' : 'zero-balance'); ?>">
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
                        title="View Details about this Account.">
                        <i class="fa-solid fa-magnifying-glass"></i> View Account
                    </button>
                    <button class="action-btn view-btn"
                        onclick="viewAccountLedger('<?php echo htmlspecialchars($account['account_number']); ?>')"
                        title="View the ledger for this account.">
                        <i class="fa-solid fa-magnifying-glass"></i> View Ledger
                    </button>

                    <?php if ($canEditAccounts): ?>
                        <?php if ($account['is_active']): ?>
                            <button class="action-btn edit-btn"
                                onclick="editAccount('<?php echo htmlspecialchars($account['account_number']); ?>')"
                                title="Edit Account">
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                            </button>

                            <?php if ((float) $account['balance'] == 0): ?>
                                <button class="action-btn deactivate-btn"
                                    onclick="confirmDeactivateAccount('<?php echo htmlspecialchars($account['account_number']); ?>', '<?php echo htmlspecialchars($account['name']); ?>')"
                                    title="Deactivate Account">
                                    <i class="fa-solid fa-ban"></i> Deactivate
                                </button>
                            <?php else: ?>
                                <button class="action-btn disabled-btn"
                                    onclick="showBalanceAlert('<?php echo formatMoney($account['balance']); ?>')"
                                    title="Cannot deactivate - Non-zero balance">
                                    <i class="fa-solid fa-ban"></i> Deactivate
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="action-btn activate-btn"
                                onclick="confirmReactivateAccount('<?php echo htmlspecialchars($account['account_number']); ?>', '<?php echo htmlspecialchars($account['name']); ?>')"
                                title="Reactivate Account">
                                <i class="fa-solid fa-square-check"></i> Reactivate
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
                    <td style="text-align: right; font-family: monospace;"
                        class="<?php echo $total > 0 ? 'positive-balance' : ($total < 0 ? 'negative-balance' : 'zero-balance'); ?>">
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

        function viewAccountLedger(accountNumber) {
            window.location.href = 'account_ledger.php?account_number=' + encodeURIComponent(accountNumber);
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
            alert("You can't deactivate an account with a balance. Please adjust the balance to $0.00 before deactivating.");
        }

        function toggleInactiveAccounts() {
            showInactive = !showInactive;
            const inactiveRows = document.querySelectorAll('.inactive-row');

            inactiveRows.forEach(row => {
                row.style.display = showInactive ? '' : 'none';
            });

            const button = event.target;
            button.textContent = showInactive ? '<i class="fa-solid fa-eye"></i> Hide Inactive Accounts' : '<i class="fa-solid fa-eye"></i> Show Inactive Accounts';
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

    </script>
</div>
</body>

</html>