<?php
/**
 * Chart of Accounts
 * This page displays the complete chart of accounts with search, filter, and navigation capabilities
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
    <title>Chart of Accounts - Clarus</title>
    
    <!-- Flatpickr CSS for calendar -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <style>
        .chart-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        
        .calendar-widget {
            position: fixed;
            top: 120px;
            left: 20px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            width: 250px;
        }
        
        .calendar-widget h3 {
            margin: 0 0 10px 0;
            color: #2980b9;
            font-size: 14px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            margin-left: 280px; /* Space for calendar widget */
        }
        
        .search-filter-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        
        .search-row {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .search-group {
            display: flex;
            flex-direction: column;
            min-width: 150px;
        }
        
        .search-group label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
            font-size: 12px;
        }
        
        .search-group input, .search-group select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .filter-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
        }
        
        .filter-btn-apply {
            background-color: #2980b9;
            color: white;
        }
        
        .filter-btn-clear {
            background-color: #2980b9;
            color: white;
        }
        
        .filter-btn-export {
            background-color: #2980b9;
            color: white;
        }
        
        .filter-btn-email {
            background-color: #2980b9;
            color: white;
        }
        
        .accounts-display {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .accounts-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .accounts-table th {
            background: linear-gradient(135deg, #2980b9, #3498db);
            color: white;
            padding: 15px 10px;
            text-align: left;
            font-weight: bold;
        }
        
        .accounts-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #f1f1f1;
        }
        
        .accounts-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .account-number {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .account-name {
            font-weight: 500;
            color: #34495e;
        }
        
        .category {
            background-color: #e8f4fd;
            color: #2980b9;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
        }
        
        .subcategory {
            color: #666;
            font-size: 12px;
        }
        
        .normal-side {
            text-align: center;
            font-weight: bold;
        }
        
        .normal-side.debit {
            color: #e74c3c;
        }
        
        .normal-side.credit {
            color: #27ae60;
        }
        
        .account-balance {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            text-align: right;
        }
        
        .balance-positive {
            color: #27ae60;
        }
        
        .balance-negative {
            color: #e74c3c;
        }
        
        .balance-zero {
            color: #95a5a6;
        }
        
        .account-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .search-highlight {
            background-color: #fff3cd;
            padding: 2px 4px;
            border-radius: 2px;
        }
        
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #2980b9, #6dd5fa, #ffffff);
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-card h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .stat-card .stat-number {
            font-size: 20px;
            font-weight: bold;
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-size: 16px;
        }
        
        /* Email Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border: 1px solid #888;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 1.5em;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            background-color: #f8f9fa;
            padding: 15px 20px;
            border-radius: 0 0 8px 8px;
            text-align: right;
        }
        
        .close {
            color: white;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 20px;
        }
        
        .close:hover,
        .close:focus {
            opacity: 0.7;
        }
        
        .email-form-group {
            margin-bottom: 20px;
        }
        
        .email-form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        
        .email-form-group select,
        .email-form-group input,
        .email-form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .email-form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .email-form-group select:focus,
        .email-form-group input:focus,
        .email-form-group textarea:focus {
            outline: none;
            border-color: #17a2b8;
            box-shadow: 0 0 5px rgba(23,162,184,0.3);
        }
        
        .send-btn {
            background-color: #28a745;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            margin-left: 10px;
        }
        
        .send-btn:hover {
            background-color: #218838;
        }
        
        .cancel-modal-btn {
            background-color: #6c757d;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
        }
        
        .cancel-modal-btn:hover {
            background-color: #545b62;
        }
        
        .required-star {
            color: red;
        }
        
        @media (max-width: 768px) {
            .calendar-widget {
                position: relative;
                top: auto;
                left: auto;
                margin-bottom: 20px;
                width: 100%;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .search-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
        }
    </style>
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
        
        <!-- Calendar Widget -->
        <div class="calendar-widget">
            <h3>📅 Calendar</h3>
            <input style="width: 150px" type="text" id="calendar" placeholder="Select date..." readonly>
            <div style="margin-top: 10px; font-size: 12px; color: #666;">
                Today: <span id="currentDate"></span>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <h1>Chart of Accounts</h1>
            
            <!-- Search and Filter Controls -->
            <div class="search-filter-container">
                <div class="search-row">
                    <div class="search-group">
                        <label>🔍 Quick Search</label>
                        <input type="text" id="quickSearch" placeholder="Account number or name...">
                    </div>
                    
                    <div class="search-group">
                        <label>Account Number</label>
                        <input type="text" id="accountNumberFilter" placeholder="Account number...">
                    </div>
                    
                    <div class="search-group">
                        <label>Account Name</label>
                        <input type="text" id="accountNameFilter" placeholder="Account name...">
                    </div>
                    
                    <div class="search-group">
                        <label>Category</label>
                        <select id="categoryFilter">
                            <option value="">All Categories</option>
                            <option value="Assets">Assets</option>
                            <option value="Liabilities">Liabilities</option>
                            <option value="Equity">Equity</option>
                            <option value="Revenue">Revenue</option>
                            <option value="Expenses">Expenses</option>
                        </select>
                    </div>
                    
                    <div class="search-group">
                        <label>Subcategory</label>
                        <input type="text" id="subcategoryFilter" placeholder="Subcategory...">
                    </div>
                </div>
                
                <div class="search-row">
                    <div class="search-group">
                        <label>Balance Range</label>
                        <select id="balanceRangeFilter">
                            <option value="">All Balances</option>
                            <option value="positive">Positive Balance</option>
                            <option value="negative">Negative Balance</option>
                            <option value="zero">Zero Balance</option>
                            <option value="over1000">Over $1,000</option>
                            <option value="over10000">Over $10,000</option>
                        </select>
                    </div>
                    
                    <div class="search-group">
                        <label>Status</label>
                        <select id="statusFilter">
                            <option value="">All Accounts</option>
                            <option value="active">Active Only</option>
                            <option value="inactive">Inactive Only</option>
                        </select>
                    </div>
                    
                    <div class="search-group">
                        <label>Normal Side</label>
                        <select id="normalSideFilter">
                            <option value="">All Sides</option>
                            <option value="Debit">Debit</option>
                            <option value="Credit">Credit</option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-buttons">
                    <button class="filter-btn filter-btn-apply" title="Apply the Changes to Filters Made Above" onclick="applyFilters()">Apply Filters</button>
                    <button class="filter-btn filter-btn-clear" title="Clear any Filters Selected and Revert the Page to Normal" onclick="clearAllFilters()">Clear All</button>
                    <button class="filter-btn filter-btn-export" title="Export All Chart of Accounts Data to a CSV File" onclick="exportChartOfAccounts()">Export PDF</button>
                    <button class="filter-btn filter-btn-email" title="Send Email to Another User" onclick="openEmailModal()">✉️ Send Email</button>
                </div>
            </div>
            
            <?php
            include '../db_connect.php';

            try {
                $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Get all accounts in a single query
                $stmt = $pdo->query("
                    SELECT 
                        account_number,
                        name,
                        category,
                        subcategory,
                        normal_side,
                        balance,
                        statement,
                        is_active,
                        created_at
                    FROM accounts 
                    ORDER BY category, subcategory, account_number
                ");
                
                $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $accountCount = count($accounts);
                $activeCount = 0;
                
                foreach ($accounts as $account) {
                    if ($account['is_active']) {
                        $activeCount++;
                    }
                }
                
                // Fetch all users for email dropdown
                $stmt = $pdo->query("SELECT user_id, username, first_name, last_name, email FROM users WHERE access_level < 3 ORDER BY username");
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            } catch(PDOException $e) {
                die("Database Error: " . $e->getMessage());
            }

            // Format money function
            function formatMoney($value) {
                return '$' . number_format((float)$value, 2);
            }
            ?>
            
            <!-- Statistics Summary -->
            <div class="stats-summary">
                <div class="stat-card">
                    <h4>Total Accounts</h4>
                    <div class="stat-number"><?php echo $accountCount; ?></div>
                </div>
                <div class="stat-card">
                    <h4>Active Accounts</h4>
                    <div class="stat-number"><?php echo $activeCount; ?></div>
                </div>
                <div class="stat-card">
                    <h4>Categories</h4>
                    <div class="stat-number"><?php echo count(array_unique(array_column($accounts, 'category'))); ?></div>
                </div>
                <div class="stat-card">
                    <h4>Last Updated</h4>
                    <div class="stat-number" style="font-size: 14px;"><?php echo date('M j, Y'); ?></div>
                </div>
            </div>
            
            <!-- Chart of Accounts Table -->
            <div class="accounts-display">
                <table class="accounts-table" id="accountsTable">
                    <thead>
                        <tr>
                            <th>Account #</th>
                            <th>Account Name</th>
                            <th>Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($accounts as $account): ?>
                        <tr class="account-row" 
                            data-account-number="<?php echo htmlspecialchars($account['account_number']); ?>"
                            data-account-name="<?php echo strtolower(htmlspecialchars($account['name'])); ?>"
                            data-category="<?php echo strtolower($account['category']); ?>"
                            data-subcategory="<?php echo strtolower($account['subcategory']); ?>"
                            data-normal-side="<?php echo $account['normal_side']; ?>"
                            data-balance="<?php echo (float)$account['balance']; ?>"
                            data-status="<?php echo $account['is_active'] ? 'active' : 'inactive'; ?>"
                            onclick="openAccountLedger('<?php echo htmlspecialchars($account['account_number']); ?>')">
                            
                            <td class="account-number">
                                <?php echo htmlspecialchars($account['account_number']); ?>
                            </td>
                            
                            <td class="account-name">
                                <?php echo htmlspecialchars($account['name']); ?>
                            </td>
                            
                            <td class="category">
                                <?php echo htmlspecialchars($account['category']); ?>
                            </td>
                            
                            
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- No Results Message -->
                <div id="noResults" class="no-results" style="display: none;">
                    <h3>No accounts found</h3>
                    <p>Try adjusting your search criteria or filters.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Email Modal -->
    <div id="emailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeEmailModal()">&times;</span>
                <h2>✉️ Send Email to User</h2>
            </div>
            <div class="modal-body">
                <form id="emailForm" onsubmit="return sendEmail(event)">
                    <div class="email-form-group">
                        <label for="recipientUser">Send To <span class="required-star">*</span></label>
                        <select id="recipientUser" name="recipient_user" required>
                            <option value="">Select a user...</option>
                            <?php foreach ($users as $user): ?>
                                <?php if ($user['user_id'] != $userId): // Don't show current user ?>
                                    <option value="<?php echo htmlspecialchars($user['user_id']); ?>" 
                                            data-email="<?php echo htmlspecialchars($user['email']); ?>">
                                        <?php echo htmlspecialchars($user['username']); ?> 
                                        <?php if (!empty($user['first_name']) || !empty($user['last_name'])): ?>
                                            (<?php echo htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])); ?>)
                                        <?php endif; ?>
                                        - <?php echo htmlspecialchars($user['email']); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="email-form-group">
                        <label for="emailSubject">Subject <span class="required-star">*</span></label>
                        <input type="text" id="emailSubject" name="subject" required 
                               placeholder="Enter email subject..." 
                               value="Chart of Accounts Information">
                    </div>
                    
                    <div class="email-form-group">
                        <label for="emailContent">Message <span class="required-star">*</span></label>
                        <textarea id="emailContent" name="content" required 
                                  placeholder="Enter your message here...">Hello,

I wanted to share information about our Chart of Accounts:

Total Accounts: <?php echo $accountCount; ?>
Active Accounts: <?php echo $activeCount; ?>
Categories: <?php echo count(array_unique(array_column($accounts, 'category'))); ?>

Please review and let me know if you have any questions.

Best regards,
<?php echo htmlspecialchars($username); ?></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="cancel-modal-btn" onclick="closeEmailModal()">Cancel</button>
                <button type="submit" form="emailForm" class="send-btn">Send Email</button>
            </div>
        </div>
    </div>
    
    <!-- Flatpickr JS for calendar -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    <script>
        // Initialize calendar
        const calendar = flatpickr("#calendar", {
            inline: false,
            dateFormat: "F j, Y",
            defaultDate: "today",
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length > 0) {
                    console.log("Selected date:", dateStr);
                    // You can add functionality here to filter accounts by date
                    filterAccountsByDate(selectedDates[0]);
                }
            }
        });
        
        // Set current date
        document.getElementById('currentDate').textContent = new Date().toLocaleDateString('en-US', {
            weekday: 'short',
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
        
        // Search and filter functionality
        let allAccountRows = [];
        
        document.addEventListener('DOMContentLoaded', function() {
            allAccountRows = Array.from(document.querySelectorAll('.account-row'));
            
            // Add event listeners for real-time search
            document.getElementById('quickSearch').addEventListener('input', handleQuickSearch);
            document.getElementById('accountNumberFilter').addEventListener('input', applyFilters);
            document.getElementById('accountNameFilter').addEventListener('input', applyFilters);
            document.getElementById('subcategoryFilter').addEventListener('input', applyFilters);
        });
        
        function handleQuickSearch() {
            const searchTerm = document.getElementById('quickSearch').value.toLowerCase();
            
            if (searchTerm === '') {
                applyFilters();
                return;
            }
            
            allAccountRows.forEach(row => {
                const accountNumber = row.dataset.accountNumber.toLowerCase();
                const accountName = row.dataset.accountName.toLowerCase();
                
                if (accountNumber.includes(searchTerm) || accountName.includes(searchTerm)) {
                    row.style.display = 'table-row';
                    highlightSearchTerm(row, searchTerm);
                } else {
                    row.style.display = 'none';
                }
            });
            
            checkForNoResults();
        }
        
        function applyFilters() {
            const filters = {
                quickSearch: document.getElementById('quickSearch').value.toLowerCase(),
                accountNumber: document.getElementById('accountNumberFilter').value.toLowerCase(),
                accountName: document.getElementById('accountNameFilter').value.toLowerCase(),
                category: document.getElementById('categoryFilter').value.toLowerCase(),
                subcategory: document.getElementById('subcategoryFilter').value.toLowerCase(),
                balanceRange: document.getElementById('balanceRangeFilter').value,
                status: document.getElementById('statusFilter').value,
                normalSide: document.getElementById('normalSideFilter').value
            };
            
            allAccountRows.forEach(row => {
                let showRow = true;
                
                // Quick search filter
                if (filters.quickSearch) {
                    const accountNumber = row.dataset.accountNumber.toLowerCase();
                    const accountName = row.dataset.accountName.toLowerCase();
                    if (!accountNumber.includes(filters.quickSearch) && !accountName.includes(filters.quickSearch)) {
                        showRow = false;
                    }
                }
                
                // Account number filter
                if (filters.accountNumber && !row.dataset.accountNumber.toLowerCase().includes(filters.accountNumber)) {
                    showRow = false;
                }
                
                // Account name filter
                if (filters.accountName && !row.dataset.accountName.includes(filters.accountName)) {
                    showRow = false;
                }
                
                // Category filter
                if (filters.category && row.dataset.category !== filters.category) {
                    showRow = false;
                }
                
                // Subcategory filter
                if (filters.subcategory && !row.dataset.subcategory.includes(filters.subcategory)) {
                    showRow = false;
                }
                
                // Balance range filter
                if (filters.balanceRange) {
                    const balance = parseFloat(row.dataset.balance);
                    switch (filters.balanceRange) {
                        case 'positive':
                            if (balance <= 0) showRow = false;
                            break;
                        case 'negative':
                            if (balance >= 0) showRow = false;
                            break;
                        case 'zero':
                            if (balance !== 0) showRow = false;
                            break;
                        case 'over1000':
                            if (Math.abs(balance) <= 1000) showRow = false;
                            break;
                        case 'over10000':
                            if (Math.abs(balance) <= 10000) showRow = false;
                            break;
                    }
                }
                
                // Status filter
                if (filters.status && row.dataset.status !== filters.status) {
                    showRow = false;
                }
                
                // Normal side filter
                if (filters.normalSide && row.dataset.normalSide !== filters.normalSide) {
                    showRow = false;
                }
                
                row.style.display = showRow ? 'table-row' : 'none';
                
                // Highlight search terms
                if (showRow && (filters.quickSearch || filters.accountName)) {
                    const searchTerm = filters.quickSearch || filters.accountName;
                    highlightSearchTerm(row, searchTerm);
                }
            });
            
            checkForNoResults();
        }
        
        function highlightSearchTerm(row, searchTerm) {
            if (!searchTerm) return;
            
            const accountNameEl = row.querySelector('.account-name');
            const accountNumberEl = row.querySelector('.account-number');
            
            [accountNameEl, accountNumberEl].forEach(el => {
                if (el) {
                    const text = el.textContent;
                    const regex = new RegExp(`(${searchTerm})`, 'gi');
                    const highlightedText = text.replace(regex, '<span class="search-highlight">$1</span>');
                    el.innerHTML = highlightedText;
                }
            });
        }
        
        function checkForNoResults() {
            const visibleRows = document.querySelectorAll('.account-row[style*="table-row"], .account-row:not([style])');
            const noResultsDiv = document.getElementById('noResults');
            const tableContainer = document.querySelector('.accounts-table');
            
            if (visibleRows.length === 0) {
                noResultsDiv.style.display = 'block';
                tableContainer.style.display = 'none';
            } else {
                noResultsDiv.style.display = 'none';
                tableContainer.style.display = 'table';
            }
        }
        
        function clearAllFilters() {
            document.getElementById('quickSearch').value = '';
            document.getElementById('accountNumberFilter').value = '';
            document.getElementById('accountNameFilter').value = '';
            document.getElementById('categoryFilter').value = '';
            document.getElementById('subcategoryFilter').value = '';
            document.getElementById('balanceRangeFilter').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('normalSideFilter').value = '';
            
            // Clear highlights and show all rows
            allAccountRows.forEach(row => {
                row.style.display = 'table-row';
                const accountNameEl = row.querySelector('.account-name');
                const accountNumberEl = row.querySelector('.account-number');
                
                if (accountNameEl) accountNameEl.innerHTML = accountNameEl.textContent;
                if (accountNumberEl) accountNumberEl.innerHTML = accountNumberEl.textContent;
            });
            
            checkForNoResults();
        }
        
        function openAccountLedger(accountNumber) {
            // Navigate to account ledger page
            window.location.href = 'account_ledger.php?account_number=' + encodeURIComponent(accountNumber);
        }
        
        function filterAccountsByDate(selectedDate) {
            // This function can be used to filter accounts based on selected date
            // For example, show accounts created before/after a certain date
            console.log('Filtering by date:', selectedDate);
            // Implement date-based filtering logic here
        }
        
        function exportChartOfAccounts() {
            // Simple export functionality
            const visibleRows = document.querySelectorAll('.account-row[style*="table-row"], .account-row:not([style])');
            
            let csvContent = 'Account Number,Account Name,Category,Subcategory,Normal Side,Balance,Status\n';
            
            visibleRows.forEach(row => {
                const accountNumber = row.dataset.accountNumber;
                const accountName = row.querySelector('.account-name').textContent;
                const category = row.dataset.category;
                const subcategory = row.dataset.subcategory;
                const normalSide = row.dataset.normalSide;
                const balance = row.dataset.balance;
                const status = row.dataset.status;
                
                csvContent += `"${accountNumber}","${accountName}","${category}","${subcategory}","${normalSide}","${balance}","${status}"\n`;
            });
            
            // Download CSV
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `chart_of_accounts_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
        
        // Email Modal Functions
        function openEmailModal() {
            document.getElementById('emailModal').style.display = 'block';
        }
        
        function closeEmailModal() {
            document.getElementById('emailModal').style.display = 'none';
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('emailModal');
            if (event.target == modal) {
                closeEmailModal();
            }
        }
        
        // Close modal with Escape key - but not if it's being used for clearing filters
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('emailModal');
                if (modal.style.display === 'block') {
                    closeEmailModal();
                } else {
                    clearAllFilters();
                }
            }
            
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                document.getElementById('quickSearch').focus();
            }
        });
        
        function sendEmail(event) {
            event.preventDefault();
            
            const recipientSelect = document.getElementById('recipientUser');
            const recipientUserId = recipientSelect.value;
            const recipientEmail = recipientSelect.options[recipientSelect.selectedIndex].getAttribute('data-email');
            const subject = document.getElementById('emailSubject').value;
            const content = document.getElementById('emailContent').value;
            
            if (!recipientUserId) {
                alert('Please select a recipient.');
                return false;
            }
            
            if (!subject.trim()) {
                alert('Please enter a subject.');
                return false;
            }
            
            if (!content.trim()) {
                alert('Please enter a message.');
                return false;
            }
            
            // Prepare form data
            const formData = new FormData();
            formData.append('recipient_user_id', recipientUserId);
            formData.append('recipient_email', recipientEmail);
            formData.append('subject', subject);
            formData.append('content', content);
            formData.append('page', 'chart_of_accounts');
            
            // Send AJAX request
            fetch('send_email_from_account.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Email sent successfully!');
                    closeEmailModal();
                    // Reset form
                    document.getElementById('emailForm').reset();
                    // Restore default subject
                    document.getElementById('emailSubject').value = 'Chart of Accounts Information';
                } else {
                    alert('Error sending email: ' + (data.message || 'Unknown error occurred'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error sending email. Please try again.');
            });
            
            return false;
        }
    </script>
</body>
</html>