<?php
/**
 * Accounting Dashboard
 * Displays financial ratios with color-coded indicators and pending journal entries
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
$userAccessLevel = isset($_SESSION['access_level']) ? (int) $_SESSION['access_level'] : 0;

include 'header.php';
?>

<style>
    .dashboard-header {
        background: linear-gradient(135deg, #2980b9, #3498db);
        color: white;
        padding: 30px;
        border-radius: 8px;
        margin-bottom: 30px;
    }

    .dashboard-header h1 {
        margin: 0 0 10px 0;
        font-size: 32px;
    }

    .dashboard-header p {
        margin: 0;
        font-size: 16px;
        opacity: 0.9;
    }

    .quick-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        border-left: 4px solid #2980b9;
    }

    .stat-label {
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
        margin-bottom: 8px;
        font-weight: bold;
    }

    .stat-value {
        font-size: 28px;
        font-weight: bold;
        color: #333;
        font-family: monospace;
    }

    .stat-trend {
        font-size: 12px;
        margin-top: 5px;
    }

    .trend-up {
        color: #28a745;
    }

    .trend-down {
        color: #dc3545;
    }

    .section-title {
        font-size: 24px;
        font-weight: bold;
        color: #2980b9;
        margin: 30px 0 20px 0;
        padding-bottom: 10px;
        border-bottom: 3px solid #2980b9;
    }

    .ratios-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .ratio-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .ratio-header {
        padding: 15px 20px;
        color: white;
        font-weight: bold;
        font-size: 16px;
    }

    .ratio-header.good {
        background: linear-gradient(135deg, #28a745, #20c997);
    }

    .ratio-header.warning {
        background: linear-gradient(135deg, #ffc107, #ff9800);
        color: #000;
    }

    .ratio-header.danger {
        background: linear-gradient(135deg, #dc3545, #c82333);
    }

    .ratio-body {
        padding: 20px;
    }

    .ratio-value {
        font-size: 36px;
        font-weight: bold;
        margin-bottom: 10px;
        font-family: monospace;
    }

    .ratio-value.good {
        color: #28a745;
    }

    .ratio-value.warning {
        color: #ffc107;
    }

    .ratio-value.danger {
        color: #dc3545;
    }

    .ratio-description {
        font-size: 13px;
        color: #666;
        line-height: 1.6;
        margin-bottom: 15px;
    }

    .ratio-range {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 4px;
        font-size: 12px;
        margin-top: 10px;
    }

    .ratio-range strong {
        display: block;
        margin-bottom: 5px;
        color: #333;
    }

    .range-indicator {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: bold;
        margin-right: 5px;
    }

    .range-good {
        background: #d4edda;
        color: #155724;
    }

    .range-warning {
        background: #fff3cd;
        color: #856404;
    }

    .range-danger {
        background: #f8d7da;
        color: #721c24;
    }

    .pending-entries-section {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        padding: 25px;
        margin-bottom: 30px;
    }

    .entries-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    .entries-table thead {
        background: linear-gradient(135deg, #ffc107, #ff9800);
        color: #000;
    }

    .entries-table th {
        padding: 12px 10px;
        text-align: left;
        font-weight: bold;
        font-size: 13px;
    }

    .entries-table tbody tr {
        border-bottom: 1px solid #f1f1f1;
    }

    .entries-table tbody tr:hover {
        background-color: #f8f9fa;
    }

    .entries-table td {
        padding: 12px 10px;
    }

    .entry-link {
        color: #2980b9;
        text-decoration: none;
        font-weight: bold;
    }

    .entry-link:hover {
        text-decoration: underline;
    }

    .amount {
        font-family: monospace;
        font-weight: bold;
    }

    .no-pending {
        text-align: center;
        padding: 40px;
        color: #666;
    }

    .action-btn {
        padding: 6px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        font-weight: bold;
        text-decoration: none;
        display: inline-block;
    }

    .btn-view {
        background-color: #2980b9;
        color: white;
    }

    .btn-view:hover {
        background-color: #21618c;
    }

    .charts-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .chart-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .chart-title {
        font-size: 18px;
        font-weight: bold;
        color: #333;
        margin-bottom: 15px;
    }

    @media (max-width: 768px) {

        .ratios-grid,
        .quick-stats,
        .charts-container {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container"
    style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none; -ms-overflow-style: none;">

    <?php
    include '../db_connect.php';

    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Get account balances by category
        $stmt = $pdo->query("
            SELECT category, SUM(balance) as total_balance
            FROM accounts
            WHERE is_active = 1
            GROUP BY category
        ");
        $categoryBalances = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Calculate financial statement totals
        $currentAssets = $pdo->query("
            SELECT SUM(balance) FROM accounts 
            WHERE category = 'Assets' 
            AND (subcategory LIKE '%Current%' OR subcategory = 'Cash and Cash Equivalents' OR subcategory = 'Accounts Receivable' OR subcategory = 'Inventory')
            AND is_active = 1
        ")->fetchColumn() ?: 0;

        $totalAssets = $categoryBalances['Assets'] ?? 0;

        $currentLiabilities = $pdo->query("
            SELECT SUM(balance) FROM accounts 
            WHERE category = 'Liabilities' 
            AND (subcategory LIKE '%Current%' OR subcategory = 'Accounts Payable' OR subcategory = 'Accrued Liabilities')
            AND is_active = 1
        ")->fetchColumn() ?: 0;

        $totalLiabilities = $categoryBalances['Liabilities'] ?? 0;
        $totalEquity = $categoryBalances['Equity'] ?? 0;
        $totalRevenue = $categoryBalances['Revenue'] ?? 0;
        $totalExpenses = $categoryBalances['Expenses'] ?? 0;

        // Calculate additional metrics
        $inventory = $pdo->query("
            SELECT SUM(balance) FROM accounts 
            WHERE category = 'Assets' AND subcategory = 'Inventory'
            AND is_active = 1
        ")->fetchColumn() ?: 0;

        $accountsReceivable = $pdo->query("
            SELECT SUM(balance) FROM accounts 
            WHERE category = 'Assets' AND subcategory = 'Accounts Receivable'
            AND is_active = 1
        ")->fetchColumn() ?: 0;

        $cash = $pdo->query("
            SELECT SUM(balance) FROM accounts 
            WHERE category = 'Assets' AND subcategory = 'Cash and Cash Equivalents'
            AND is_active = 1
        ")->fetchColumn() ?: 0;

        $netIncome = $totalRevenue - $totalExpenses;

        // Get pending journal entries
        $stmt = $pdo->query("
            SELECT 
                je.entry_id,
                je.entry_date,
                je.description,
                je.total_debit,
                je.created_by,
                je.created_at,
                u.username as created_by_name,
                a.name as account_name
            FROM journal_entries je
            LEFT JOIN users u ON je.created_by = u.user_id
            LEFT JOIN accounts a ON je.account_id = a.account_number
            WHERE je.status = 'pending'
            ORDER BY je.created_at DESC
        ");
        $pendingEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate Financial Ratios
    
        // 1. Current Ratio = Current Assets / Current Liabilities
        $currentRatio = $currentLiabilities > 0 ? $currentAssets / $currentLiabilities : 0;

        // 2. Quick Ratio = (Current Assets - Inventory) / Current Liabilities
        $quickRatio = $currentLiabilities > 0 ? ($currentAssets - $inventory) / $currentLiabilities : 0;

        // 3. Debt-to-Equity Ratio = Total Liabilities / Total Equity
        $debtToEquityRatio = $totalEquity > 0 ? $totalLiabilities / $totalEquity : 0;

        // 4. Debt Ratio = Total Liabilities / Total Assets
        $debtRatio = $totalAssets > 0 ? $totalLiabilities / $totalAssets : 0;

        // 5. Profit Margin = Net Income / Revenue
        $profitMargin = $totalRevenue > 0 ? ($netIncome / $totalRevenue) * 100 : 0;

        // 6. Return on Assets (ROA) = Net Income / Total Assets
        $returnOnAssets = $totalAssets > 0 ? ($netIncome / $totalAssets) * 100 : 0;

        // 7. Return on Equity (ROE) = Net Income / Total Equity
        $returnOnEquity = $totalEquity > 0 ? ($netIncome / $totalEquity) * 100 : 0;

        // 8. Working Capital = Current Assets - Current Liabilities
        $workingCapital = $currentAssets - $currentLiabilities;

        // Functions to determine ratio health
        function getCurrentRatioStatus($ratio)
        {
            if ($ratio >= 1.5 && $ratio <= 3.0)
                return 'good';
            if (($ratio >= 1.0 && $ratio < 1.5) || ($ratio > 3.0 && $ratio <= 4.0))
                return 'warning';
            return 'danger';
        }

        function getQuickRatioStatus($ratio)
        {
            if ($ratio >= 1.0 && $ratio <= 2.0)
                return 'good';
            if (($ratio >= 0.7 && $ratio < 1.0) || ($ratio > 2.0 && $ratio <= 2.5))
                return 'warning';
            return 'danger';
        }

        function getDebtToEquityStatus($ratio)
        {
            if ($ratio <= 1.0)
                return 'good';
            if ($ratio > 1.0 && $ratio <= 2.0)
                return 'warning';
            return 'danger';
        }

        function getDebtRatioStatus($ratio)
        {
            if ($ratio <= 0.4)
                return 'good';
            if ($ratio > 0.4 && $ratio <= 0.6)
                return 'warning';
            return 'danger';
        }

        function getProfitMarginStatus($margin)
        {
            if ($margin >= 10)
                return 'good';
            if ($margin >= 5 && $margin < 10)
                return 'warning';
            return 'danger';
        }

        function getROAStatus($roa)
        {
            if ($roa >= 5)
                return 'good';
            if ($roa >= 0 && $roa < 5)
                return 'warning';
            return 'danger';
        }

        function getROEStatus($roe)
        {
            if ($roe >= 15)
                return 'good';
            if ($roe >= 10 && $roe < 15)
                return 'warning';
            return 'danger';
        }

        function getWorkingCapitalStatus($wc)
        {
            if ($wc > 0)
                return 'good';
            if ($wc == 0)
                return 'warning';
            return 'danger';
        }

    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }

    function formatMoney($value)
    {
        return '$' . number_format((float) $value, 2);
    }

    function formatPercent($value)
    {
        return number_format($value, 2) . '%';
    }

    function formatRatio($value)
    {
        return number_format($value, 2);
    }
    ?>

    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <h1>📊 Accounting Dashboard</h1>
        <p>Financial Overview and Key Performance Indicators</p>
        <p style="font-size: 14px; margin-top: 10px;">
            📅 Report Date: <?php echo date('F j, Y'); ?> |
            👤 User: <?php echo htmlspecialchars($username); ?>
        </p>
    </div>

    <!-- Quick Stats -->
    <div class="quick-stats">
        <div class="stat-card" style="border-left-color: #28a745;">
            <div class="stat-label">Total Assets</div>
            <div class="stat-value" style="color: #28a745;"><?php echo formatMoney($totalAssets); ?></div>
        </div>

        <div class="stat-card" style="border-left-color: #dc3545;">
            <div class="stat-label">Total Liabilities</div>
            <div class="stat-value" style="color: #dc3545;"><?php echo formatMoney($totalLiabilities); ?></div>
        </div>

        <div class="stat-card" style="border-left-color: #2980b9;">
            <div class="stat-label">Total Equity</div>
            <div class="stat-value" style="color: #2980b9;"><?php echo formatMoney($totalEquity); ?></div>
        </div>

        <div class="stat-card" style="border-left-color: #17a2b8;">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value" style="color: #17a2b8;"><?php echo formatMoney($totalRevenue); ?></div>
        </div>

        <div class="stat-card" style="border-left-color: #ffc107;">
            <div class="stat-label">Total Expenses</div>
            <div class="stat-value" style="color: #ffc107;"><?php echo formatMoney($totalExpenses); ?></div>
        </div>

        <div class="stat-card" style="border-left-color: <?php echo $netIncome >= 0 ? '#28a745' : '#dc3545'; ?>;">
            <div class="stat-label">Net Income</div>
            <div class="stat-value" style="color: <?php echo $netIncome >= 0 ? '#28a745' : '#dc3545'; ?>;">
                <?php echo formatMoney($netIncome); ?>
            </div>
        </div>
    </div>

    <!-- Financial Ratios -->
    <h2 class="section-title">📈 Financial Ratios Analysis</h2>

    <div class="ratios-grid">
        <!-- Current Ratio -->
        <div class="ratio-card">
            <div class="ratio-header <?php echo getCurrentRatioStatus($currentRatio); ?>">
                💧 Current Ratio (Liquidity)
            </div>
            <div class="ratio-body">
                <div class="ratio-value <?php echo getCurrentRatioStatus($currentRatio); ?>">
                    <?php echo formatRatio($currentRatio); ?>:1
                </div>
                <div class="ratio-description">
                    Measures ability to pay short-term obligations. Current Assets / Current Liabilities.
                </div>
                <div class="ratio-range">
                    <strong>Healthy Ranges:</strong>
                    <span class="range-indicator range-good">1.5 - 3.0 Excellent</span>
                    <span class="range-indicator range-warning">1.0 - 1.5 Adequate</span>
                    <span class="range-indicator range-danger">&lt; 1.0 Poor</span>
                </div>
            </div>
        </div>

        <!-- Quick Ratio -->
        <div class="ratio-card">
            <div class="ratio-header <?php echo getQuickRatioStatus($quickRatio); ?>">
                ⚡ Quick Ratio (Acid Test)
            </div>
            <div class="ratio-body">
                <div class="ratio-value <?php echo getQuickRatioStatus($quickRatio); ?>">
                    <?php echo formatRatio($quickRatio); ?>:1
                </div>
                <div class="ratio-description">
                    Measures immediate liquidity without inventory. (Current Assets - Inventory) / Current
                    Liabilities.
                </div>
                <div class="ratio-range">
                    <strong>Healthy Ranges:</strong>
                    <span class="range-indicator range-good">1.0 - 2.0 Excellent</span>
                    <span class="range-indicator range-warning">0.7 - 1.0 Adequate</span>
                    <span class="range-indicator range-danger">&lt; 0.7 Poor</span>
                </div>
            </div>
        </div>

        <!-- Debt-to-Equity Ratio -->
        <div class="ratio-card">
            <div class="ratio-header <?php echo getDebtToEquityStatus($debtToEquityRatio); ?>">
                ⚖️ Debt-to-Equity Ratio
            </div>
            <div class="ratio-body">
                <div class="ratio-value <?php echo getDebtToEquityStatus($debtToEquityRatio); ?>">
                    <?php echo formatRatio($debtToEquityRatio); ?>
                </div>
                <div class="ratio-description">
                    Measures financial leverage. Total Liabilities / Total Equity. Lower is generally better.
                </div>
                <div class="ratio-range">
                    <strong>Healthy Ranges:</strong>
                    <span class="range-indicator range-good">&lt; 1.0 Excellent</span>
                    <span class="range-indicator range-warning">1.0 - 2.0 Moderate</span>
                    <span class="range-indicator range-danger">&gt; 2.0 High Risk</span>
                </div>
            </div>
        </div>

        <!-- Debt Ratio -->
        <div class="ratio-card">
            <div class="ratio-header <?php echo getDebtRatioStatus($debtRatio); ?>">
                📊 Debt Ratio
            </div>
            <div class="ratio-body">
                <div class="ratio-value <?php echo getDebtRatioStatus($debtRatio); ?>">
                    <?php echo formatPercent($debtRatio * 100); ?>
                </div>
                <div class="ratio-description">
                    Percentage of assets financed by debt. Total Liabilities / Total Assets. Lower is better.
                </div>
                <div class="ratio-range">
                    <strong>Healthy Ranges:</strong>
                    <span class="range-indicator range-good">&lt; 40% Excellent</span>
                    <span class="range-indicator range-warning">40% - 60% Moderate</span>
                    <span class="range-indicator range-danger">&gt; 60% High</span>
                </div>
            </div>
        </div>

        <!-- Profit Margin -->
        <div class="ratio-card">
            <div class="ratio-header <?php echo getProfitMarginStatus($profitMargin); ?>">
                💰 Profit Margin
            </div>
            <div class="ratio-body">
                <div class="ratio-value <?php echo getProfitMarginStatus($profitMargin); ?>">
                    <?php echo formatPercent($profitMargin); ?>
                </div>
                <div class="ratio-description">
                    Shows profitability as percentage of revenue. Net Income / Revenue. Higher is better.
                </div>
                <div class="ratio-range">
                    <strong>Healthy Ranges:</strong>
                    <span class="range-indicator range-good">&gt; 10% Excellent</span>
                    <span class="range-indicator range-warning">5% - 10% Average</span>
                    <span class="range-indicator range-danger">&lt; 5% Poor</span>
                </div>
            </div>
        </div>

        <!-- Return on Assets -->
        <div class="ratio-card">
            <div class="ratio-header <?php echo getROAStatus($returnOnAssets); ?>">
                📈 Return on Assets (ROA)
            </div>
            <div class="ratio-body">
                <div class="ratio-value <?php echo getROAStatus($returnOnAssets); ?>">
                    <?php echo formatPercent($returnOnAssets); ?>
                </div>
                <div class="ratio-description">
                    Measures efficiency in using assets. Net Income / Total Assets. Higher indicates better asset
                    utilization.
                </div>
                <div class="ratio-range">
                    <strong>Healthy Ranges:</strong>
                    <span class="range-indicator range-good">&gt; 5% Excellent</span>
                    <span class="range-indicator range-warning">0% - 5% Average</span>
                    <span class="range-indicator range-danger">&lt; 0% Negative</span>
                </div>
            </div>
        </div>

        <!-- Return on Equity -->
        <div class="ratio-card">
            <div class="ratio-header <?php echo getROEStatus($returnOnEquity); ?>">
                🎯 Return on Equity (ROE)
            </div>
            <div class="ratio-body">
                <div class="ratio-value <?php echo getROEStatus($returnOnEquity); ?>">
                    <?php echo formatPercent($returnOnEquity); ?>
                </div>
                <div class="ratio-description">
                    Measures return generated on shareholders' equity. Net Income / Total Equity. Higher is better.
                </div>
                <div class="ratio-range">
                    <strong>Healthy Ranges:</strong>
                    <span class="range-indicator range-good">&gt; 15% Excellent</span>
                    <span class="range-indicator range-warning">10% - 15% Good</span>
                    <span class="range-indicator range-danger">&lt; 10% Below Average</span>
                </div>
            </div>
        </div>

        <!-- Working Capital -->
        <div class="ratio-card">
            <div class="ratio-header <?php echo getWorkingCapitalStatus($workingCapital); ?>">
                💼 Working Capital
            </div>
            <div class="ratio-body">
                <div class="ratio-value <?php echo getWorkingCapitalStatus($workingCapital); ?>">
                    <?php echo formatMoney($workingCapital); ?>
                </div>
                <div class="ratio-description">
                    Difference between current assets and liabilities. Current Assets - Current Liabilities.
                </div>
                <div class="ratio-range">
                    <strong>Healthy Ranges:</strong>
                    <span class="range-indicator range-good">&gt; $0 Positive</span>
                    <span class="range-indicator range-warning">$0 Break Even</span>
                    <span class="range-indicator range-danger">&lt; $0 Negative</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Journal Entries -->
    <h2 class="section-title">⏳ Pending Journal Entries (<?php echo count($pendingEntries); ?>)</h2>

    <div class="pending-entries-section">
        <?php if (empty($pendingEntries)): ?>
            <div class="no-pending">
                <h3>✅ No Pending Entries</h3>
                <p>All journal entries have been processed.</p>
            </div>
        <?php else: ?>
            <table class="entries-table">
                <thead>
                    <tr>
                        <th style="width: 100px;">Entry ID</th>
                        <th style="width: 120px;">Date</th>
                        <th>Description</th>
                        <th>Account</th>
                        <th style="width: 130px;">Amount</th>
                        <th style="width: 150px;">Created By</th>
                        <th style="width: 120px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingEntries as $entry): ?>
                        <tr>
                            <td>
                                <a href="view_journal_entry.php?entry_id=<?php echo $entry['entry_id']; ?>" class="entry-link">
                                    #JE-<?php echo str_pad($entry['entry_id'], 6, '0', STR_PAD_LEFT); ?>
                                </a>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($entry['entry_date'])); ?></td>
                            <td><?php echo htmlspecialchars($entry['description']); ?></td>
                            <td><?php echo htmlspecialchars($entry['account_name']); ?></td>
                            <td class="amount" style="color: #2980b9;"><?php echo formatMoney($entry['total_debit']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($entry['created_by_name']); ?></td>
                            <td>
                                <a href="view_journal_entry.php?entry_id=<?php echo $entry['entry_id']; ?>"
                                    class="action-btn btn-view">
                                    👁️ Review
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Quick Links -->
    <h2 class="section-title">🔗 Quick Links</h2>
    <div
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
        <a href="chart_of_accounts.php"
            style="padding: 20px; background: white; border-radius: 8px; text-decoration: none; color: #2980b9; font-weight: bold; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            📊 Chart of Accounts
        </a>
        <a href="view_journal_entries.php"
            style="padding: 20px; background: white; border-radius: 8px; text-decoration: none; color: #2980b9; font-weight: bold; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            📋 Journal Entries
        </a>
        <a href="create_journal_entry.php"
            style="padding: 20px; background: white; border-radius: 8px; text-decoration: none; color: #28a745; font-weight: bold; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            ➕ New Entry
        </a>
        <a href="accounts_dashboard.php"
            style="padding: 20px; background: white; border-radius: 8px; text-decoration: none; color: #2980b9; font-weight: bold; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            🏦 View Accounts
        </a>
        <?php
        if ($userAccessLevel > 1) {
            echo '<a href="view_change_log.php" style="padding: 20px; background: white; border-radius: 8px; text-decoration: none; color: #2980b9; font-weight: bold; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            📜 Change Log
        </a>';
        }

        if ($userAccessLevel > 2) {
            echo '<a href="dashboard.php" style="padding: 20px; background: white; border-radius: 8px; text-decoration: none; color: #2980b9; font-weight: bold; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            👥 User Management
        </a>';
        }
        ?>
    </div>

</div>
</body>

</html>