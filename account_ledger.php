<?php
//KSU student project for Clarus Accounting tool
//This page is used to view an account's ledger
//Initially drafted by Eric Poole

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

$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];
$userAccessLevel = $_SESSION['access_level'];

// Get account number from URL parameter
$account_number = $_GET['account_number'];

if (!$account_number) {
    die("Hmm... the account number is missing from the URL. Please go back and try again.");
}

// Connect to external db file
include '../db_connect.php';


    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch account details
    $getAccountDetails = $pdo->prepare("SELECT * FROM accounts WHERE account_number = :account_number");
    $getAccountDetails->execute([':account_number' => $account_number]);
    $account = $getAccountDetails->fetch(PDO::FETCH_ASSOC);

    if (!$account) {
        die("Hmm... no details were found for an Account with the number: $account_number");
    }

    // Fetch all approved journal entry lines for this account
    $getLedgerEntries = $pdo->prepare("
        SELECT 
            jel.*,
            je.entry_id,
            je.entry_date,
            je.description as journal_description,
            je.reference_number,
            je.created_at
        FROM journal_entry_lines jel
        INNER JOIN journal_entries je ON jel.journal_entry_id = je.entry_id
        WHERE jel.account_number = :account_number
        AND je.status IN ('approved', 'posted')
        ORDER BY je.entry_date ASC, je.entry_id ASC, jel.line_id ASC
    ");

$getLedgerEntries->execute([':account_number' => $account_number]);
$ledgerEntries = $getLedgerEntries->fetchAll(PDO::FETCH_ASSOC);

// Calculate running balance
$runningBalance = (float) $account['initial_balance'];
$normalSide = $account['normal_side'];

foreach ($ledgerEntries as &$entry) {
    $debit = (float) $entry['debit_amount'];
    $credit = (float) $entry['credit_amount'];

    // Calculate balance based on normal side
    if ($normalSide === 'Debit') {
        // For debit accounts, the balance increases with debits, decreases with credits
        $runningBalance += $debit - $credit;
    } else {
        // For credit accounts, the balance increases with credits, decreases with debits
        $runningBalance += $credit - $debit;
    }

    $entry['balance'] = $runningBalance;
}

// Calculate statistics
$totalDebits = array_sum(array_column($ledgerEntries, 'debit_amount'));
$totalCredits = array_sum(array_column($ledgerEntries, 'credit_amount'));
$currentBalance = $runningBalance;

function formatMoney($value)
{
    return '$' . number_format((float) $value, 2);
}

include 'header.php';
?>

<style>
    .account-header {
        background: linear-gradient(135deg, #2980b9, #3498db);
        color: white;
        padding: 30px;
        border-radius: 8px;
        margin-bottom: 30px;
    }

    .account-header h1 {
        margin: 0 0 15px 0;
        font-size: 28px;
    }

    .account-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 15px;
    }

    .account-info-item {
        background: rgba(255, 255, 255, 0.2);
        padding: 15px;
        border-radius: 8px;
    }

    .account-info-label {
        font-size: 12px;
        opacity: 0.9;
        margin-bottom: 5px;
    }

    .account-info-value {
        font-size: 20px;
        font-weight: bold;
    }

    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin: 20px 0;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        border-left: 4px solid #2980b9;
    }

    .stat-card h3 {
        margin: 0 0 10px 0;
        font-size: 14px;
        color: #666;
    }

    .stat-card .stat-number {
        font-size: 24px;
        font-weight: bold;
        color: #333;
        font-family: monospace;
    }

    .stat-card.debit {
        border-left-color: #dc3545;
    }

    .stat-card.debit .stat-number {
        color: #dc3545;
    }

    .stat-card.credit {
        border-left-color: #28a745;
    }

    .stat-card.credit .stat-number {
        color: #28a745;
    }

    .stat-card.balance {
        border-left-color: #2980b9;
    }

    .stat-card.balance .stat-number {
        color: #2980b9;
    }

    .filter-container {
        margin: 20px 0;
        padding: 20px;
        background-color: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }

    .filter-row {
        display: flex;
        gap: 15px;
        align-items: end;
        flex-wrap: wrap;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        min-width: 150px;
    }

    .filter-group label {
        font-weight: bold;
        margin-bottom: 5px;
        color: #333;
        font-size: 12px;
    }

    .filter-group input,
    .filter-group select {
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }

    .filter-btn {
        padding: 8px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        font-weight: bold;
        background-color: #2980b9;
        color: white;
    }

    .filter-btn:hover {
        background-color: #21618c;
    }

    .ledger-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .ledger-table {
        width: 100%;
        border-collapse: collapse;
    }

    .ledger-table thead {
        background: linear-gradient(135deg, #2980b9, #3498db);
        color: white;
    }

    .ledger-table th {
        padding: 15px 10px;
        text-align: left;
        font-weight: bold;
        font-size: 13px;
    }

    .ledger-table th.text-right {
        text-align: right;
    }

    .ledger-table th.text-center {
        text-align: center;
    }

    .ledger-table tbody tr {
        border-bottom: 1px solid #f1f1f1;
    }

    .ledger-table tbody tr:hover {
        background-color: #f8f9fa;
    }

    .ledger-table td {
        padding: 12px 10px;
    }

    .text-right {
        text-align: right;
    }

    .text-center {
        text-align: center;
    }

    .post-reference {
        color: #2980b9;
        text-decoration: none;
        font-weight: bold;
        font-family: monospace;
        cursor: pointer;
    }

    .post-reference:hover {
        text-decoration: underline;
    }

    .amount-debit {
        color: #dc3545;
        font-weight: bold;
        font-family: monospace;
    }

    .amount-credit {
        color: #28a745;
        font-weight: bold;
        font-family: monospace;
    }

    .amount-balance {
        font-weight: bold;
        font-family: monospace;
        color: #2980b9;
    }

    .balance-negative {
        color: #dc3545;
    }

    .balance-positive {
        color: #28a745;
    }

    .opening-balance-row {
        background-color: #fff3cd;
        font-weight: bold;
    }

    .closing-balance-row {
        background-color: #d4edda;
        font-weight: bold;
        border-top: 3px solid #28a745;
    }

    .no-entries {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
    }

    .no-entries h3 {
        margin: 0 0 10px 0;
    }

    .action-buttons {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        height: 30px
    }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        font-weight: bold;
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary {
        background-color: #2980b9;
        color: white;
    }

    .btn-primary:hover {
        background-color: #21618c;
    }

    .btn-secondary {
        background-color: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background-color: #545b62;
    }

    .btn-success {
        background-color: #28a745;
        color: white;
    }

    .btn-success:hover {
        background-color: #218838;
    }

    .entry-description {
        font-size: 12px;
        color: #666;
        font-style: italic;
    }

    .action {
        display: flex;
        flex-direction: row;
        justify-content: flex-start;
        width: 60%;
        align-items: flex-end;
    }

    .action button {
        width: 50%;
        padding: 0px 20px;
    }

    .export {
        display: flex;
        flex-direction: row;
        justify-content: flex-end;
        width: 100%;
        align-items: flex-end;
    }

    .export button {
        width: 30%;
        margin-left: 5px;
    }

    .form-group {
        margin-bottom: 5px;
    }

    .form-group label {
        align-items: center;
        justify-content: center;
    }

    .filter-row {
        flex-direction: row;
        align-items: stretch;
        justify-content: center;
    }

    @media print {

        .filter-container,
        .action-buttons,
        .logo {
            display: none;
        }

        .ledger-table {
            font-size: 11px;
        }
    }

    @media (max-width: 768px) {
        .filter-row {
            flex-direction: column;
            align-items: stretch;
        }

        .filter-group {
            width: 100%;
        }

        .ledger-table {
            font-size: 12px;
        }

        .ledger-table th,
        .ledger-table td {
            padding: 8px 5px;
        }
    }
</style>

<div class="container"
    style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none;">

    <div class="action-buttons">
        <div class="action">
            <a href="view_account.php?account_number=<?php echo urlencode($account_number); ?>" role="button"
                class="btn btn-secondary" data-bs-toggle="tooltip"
                title="View details, transactions, and status of this account.">
                ⬅️ Account Details
            </a>
            <a href="create_journal_entry.php?account_id=<?php echo urlencode($account_number); ?>" role="button"
                style="margin-left:5px" class="btn btn-success" data-bs-toggle="tooltip"
                title="Create a new journal entry for this account.">
                ➕ New Journal Entry
            </a>
        </div>
        <div class="export">
            <button onclick="window.print()" style="width: 30%" class="btn btn-primary" data-bs-toggle="tooltip"
                title="Print account ledger details.">
                🖨️ Print Ledger
            </button>

        </div>
    </div>

    <!-- Account Header -->
    <div class="account-header">
        <h1>📒 Account Ledger</h1>
        <div style="font-size: 20px; margin-bottom: 10px;">
            <strong>Account <?php echo $account['account_number']; ?>:</strong>
            <?php echo $account['name']; ?>
        </div>

        <div class="account-info-grid">
            <div class="account-info-item">
                <div class="account-info-label">Category</div>
                <div class="account-info-value"><?php echo htmlspecialchars($account['category']); ?></div>
            </div>
            <div class="account-info-item">
                <div class="account-info-label">Subcategory</div>
                <div class="account-info-value"><?php echo htmlspecialchars($account['subcategory'] ?: 'N/A'); ?></div>
            </div>
            <div class="account-info-item">
                <div class="account-info-label">Normal Side</div>
                <div class="account-info-value"><?php echo htmlspecialchars($account['normal_side']); ?></div>
            </div>
            <div class="account-info-item">
                <div class="account-info-label">Initial Balance</div>
                <div class="account-info-value"><?php echo formatMoney($account['initial_balance']); ?></div>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="stats-container">
        <div class="stat-card">
            <h3>Total Entries</h3>
            <div class="stat-number"><?php echo count($ledgerEntries); ?></div>
        </div>
        <div class="stat-card debit">
            <h3>Total Debits</h3>
            <div class="stat-number"><?php echo formatMoney($totalDebits); ?></div>
        </div>
        <div class="stat-card credit">
            <h3>Total Credits</h3>
            <div class="stat-number"><?php echo formatMoney($totalCredits); ?></div>
        </div>
        <div class="stat-card balance">
            <h3>Current Balance</h3>
            <div class="stat-number"><?php echo formatMoney($currentBalance); ?></div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-container">
        <div class="filter-row">
            <div class="filter-group form-group">
                <label class="form-label">🔍 Search</label>
                <input class="form-control" type="text" id="searchFilter" placeholder="Description, amount, or PR..."
                    onkeyup="filterLedger()">
            </div>

            <div class="filter-group form-group">
                <label class="form-label">Date From</label>
                <input class="form-control" type="date" id="dateFromFilter" onchange="filterLedger()">
            </div>

            <div class="filter-group form-group">
                <label class="form-label">Date To</label>
                <input class="form-control" type="date" id="dateToFilter" onchange="filterLedger()">
            </div>

            <div class="filter-group form-group">
                <label class="form-label" style="margin-bottom: 15px">Transaction Type</label>
                <select class="form-control" id="transactionTypeFilter" onchange="filterLedger()">
                    <option value="">All Transactions</option>
                    <option value="debit">Debits Only</option>
                    <option value="credit">Credits Only</option>
                </select>
            </div>

            <div class="filter-group form-group">
                <label class="form-label">&nbsp;</label>
                <button class="filter-btn" onclick="clearFilters()">Clear Filters</button>
            </div>
        </div>
    </div>

    <!-- Ledger Table -->
    <div class="ledger-container">
        <table class="ledger-table">
            <thead>
                <tr>
                    <th style="width: 100px;">Date</th>
                    <th style="width: 80px;" class="text-center">PR</th>
                    <th>Description</th>
                    <th style="width: 120px;" class="text-right">Debit</th>
                    <th style="width: 120px;" class="text-right">Credit</th>
                    <th style="width: 140px;" class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                <!-- Opening Balance -->
                <tr class="opening-balance-row">
                    <td><?php echo date('M j, Y', strtotime($account['created_at'])); ?></td>
                    <td class="text-center">—</td>
                    <td><strong>Opening Balance</strong></td>
                    <td class="text-right">—</td>
                    <td class="text-right">—</td>
                    <td class="text-right">
                        <span class="amount-balance"><?php echo formatMoney($account['initial_balance']); ?></span>
                    </td>
                </tr>

                <?php if (empty($ledgerEntries)): ?>
                    <tr>
                        <td colspan="6" class="no-entries">
                            <h3>No transactions recorded</h3>
                            <p>Journal entries will appear here once approved.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ledgerEntries as $entry): ?>
                        <tr class="ledger-row" data-date="<?php echo $entry['entry_date']; ?>"
                            data-debit="<?php echo $entry['debit_amount']; ?>"
                            data-credit="<?php echo $entry['credit_amount']; ?>">
                            <td><?php echo date('M j, Y', strtotime($entry['entry_date'])); ?></td>
                            <td class="text-center">
                                <a href="view_journal_entry.php?entry_id=<?php echo $entry['entry_id']; ?>"
                                    class="post-reference" title="View Journal Entry #<?php echo $entry['entry_id']; ?>">
                                    JE-<?php echo str_pad($entry['entry_id'], 4, '0', STR_PAD_LEFT); ?>
                                </a>
                            </td>
                            <td>
                                <div>
                                    <?php echo htmlspecialchars($entry['line_description'] ?: $entry['journal_description']); ?>
                                </div>
                                <?php if ($entry['reference_number']): ?>
                                    <div class="entry-description">
                                        Ref: <?php echo htmlspecialchars($entry['reference_number']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <?php if ($entry['debit_amount'] > 0): ?>
                                    <span class="amount-debit"><?php echo formatMoney($entry['debit_amount']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <?php if ($entry['credit_amount'] > 0): ?>
                                    <span class="amount-credit"><?php echo formatMoney($entry['credit_amount']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <span
                                    class="amount-balance <?php echo $entry['balance'] < 0 ? 'balance-negative' : 'balance-positive'; ?>">
                                    <?php echo formatMoney($entry['balance']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <!-- Closing Balance -->
                    <tr class="closing-balance-row">
                        <td colspan="3" class="text-right"><strong>Closing Balance</strong></td>
                        <td class="text-right">
                            <span class="amount-debit"><strong><?php echo formatMoney($totalDebits); ?></strong></span>
                        </td>
                        <td class="text-right">
                            <span class="amount-credit"><strong><?php echo formatMoney($totalCredits); ?></strong></span>
                        </td>
                        <td class="text-right">
                            <span
                                class="amount-balance <?php echo $currentBalance < 0 ? 'balance-negative' : 'balance-positive'; ?>">
                                <strong><?php echo formatMoney($currentBalance); ?></strong>
                            </span>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
    function filterLedger() {
        const searchTerm = document.getElementById('searchFilter').value.toLowerCase();
        const dateFrom = document.getElementById('dateFromFilter').value;
        const dateTo = document.getElementById('dateToFilter').value;
        const transactionType = document.getElementById('transactionTypeFilter').value;

        const rows = document.querySelectorAll('.ledger-row');
        let visibleCount = 0;

        rows.forEach(row => {
            let show = true;

            // Date filter
            const rowDate = row.dataset.date;
            if (dateFrom && rowDate < dateFrom) {
                show = false;
            }
            if (dateTo && rowDate > dateTo) {
                show = false;
            }

            // Transaction type filter
            const debit = parseFloat(row.dataset.debit) || 0;
            const credit = parseFloat(row.dataset.credit) || 0;

            if (transactionType === 'debit' && debit === 0) {
                show = false;
            }
            if (transactionType === 'credit' && credit === 0) {
                show = false;
            }

            // Search filter
            if (searchTerm) {
                const rowText = row.textContent.toLowerCase();
                if (!rowText.includes(searchTerm)) {
                    show = false;
                }
            }

            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        // Update totals based on visible rows
        updateVisibleTotals();
    }

    function updateVisibleTotals() {
        const visibleRows = document.querySelectorAll('.ledger-row:not([style*="display: none"])');
        let totalDebit = 0;
        let totalCredit = 0;

        visibleRows.forEach(row => {
            totalDebit += parseFloat(row.dataset.debit) || 0;
            totalCredit += parseFloat(row.dataset.credit) || 0;
        });

        // You could update a summary section here if needed
        console.log('Visible totals - Debits:', totalDebit, 'Credits:', totalCredit);
    }

    function clearFilters() {
        document.getElementById('searchFilter').value = '';
        document.getElementById('dateFromFilter').value = '';
        document.getElementById('dateToFilter').value = '';
        document.getElementById('transactionTypeFilter').value = '';

        document.querySelectorAll('.ledger-row').forEach(row => {
            row.style.display = '';
        });

        updateVisibleTotals();
    }


</script>
</body>

</html>