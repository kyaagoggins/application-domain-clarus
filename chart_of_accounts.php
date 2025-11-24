<?php
//KSU student project for Clarus Accounting tool
//This page is used to view the chart of accounts dashboard
//The chart of accounts page lists all financial accounts and transactions for the application.
//Initially drafted by Eric Poole. Reviewed and updated by Kyaa Goggins

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

<link rel="stylesheet" href="/styling/chart_of_accounts.css">
<div class="container"
    style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none; -ms-overflow-style: none;">

    <!-- Calendar Widget - present on left side of page -->
    <div class="calendar-widget">
        <h3>📅 Calendar</h3>
        <input style="width: 150px" type="text" id="calendar" placeholder="Select date..." readonly>
        <div style="margin-top: 10px; font-size: 12px; color: #666;">
            Today: <span id="currentDate"></span>
        </div>
    </div>


    <div class="main-content">
        <h1>Chart of Accounts</h1>

        <div id="manageButtonDiv"><a id="manageButton" role="button" class="btn btn-link"
                href="accounts_dashboard.php">Manage Accounts</a></div>

        <!-- Search and Filter Requirements -->
        <div class="search-filter-container">
            <div class="search-row">
                <div class="search-group form-group">
                    <label class="form-label">Quick Search</label>
                    <input class="form-control" type="text" id="quickSearch" placeholder="Account number or name...">
                </div>

                <div class="search-group form-group">
                    <label class="form-label">Account Number</label>
                    <input class="form-control" type="text" id="accountNumberFilter" placeholder="Account number...">
                </div>

                <div class="search-group form-group">
                    <label class="form-label">Account Name</label>
                    <input class="form-control" type="text" id="accountNameFilter" placeholder="Account name...">
                </div>

                <div class="search-group form-group" style="margin-bottom: 15px">
                    <label class="form-label" style="margin-bottom: 15px">Category</label>
                    <select class="form-control" id="categoryFilter">
                        <option value="">All Categories</option>
                        <option value="Assets">Assets</option>
                        <option value="Liabilities">Liabilities</option>
                        <option value="Equity">Equity</option>
                        <option value="Revenue">Revenue</option>
                        <option value="Expenses">Expenses</option>
                    </select>
                </div>

                <div class="search-group form-group">
                    <label class="form-label">Subcategory</label>
                    <input class="form-control" type="text" id="subcategoryFilter" placeholder="Subcategory...">
                </div>
            </div>

            <div class="search-row">
                <div class="search-group form-group">
                    <label class="form-label">Balance Range</label>
                    <select class="form-control" id="balanceRangeFilter">
                        <option value="">All Balances</option>
                        <option value="positive">Positive Balance</option>
                        <option value="negative">Negative Balance</option>
                        <option value="zero">Zero Balance</option>
                        <option value="over1000">Over $1,000</option>
                        <option value="over10000">Over $10,000</option>
                    </select>
                </div>

                <div class="search-group form-group">
                    <label class="form-label">Status</label>
                    <select class="form-control" id="statusFilter">
                        <option value="">All Accounts</option>
                        <option value="active">Active Only</option>
                        <option value="inactive">Inactive Only</option>
                    </select>
                </div>

                <div class="search-group form-group">
                    <label class="form-label">Normal Side</label>
                    <select class="form-control" id="normalSideFilter">
                        <option value="">All Sides</option>
                        <option value="Debit">Debit</option>
                        <option value="Credit">Credit</option>
                    </select>
                </div>
            </div>

            <div class="filter-buttons">
                <button class="filter-btn filter-btn-apply" title="Apply the Changes to Filters Made Above"
                    onclick="applyFilters()">Apply Filters</button>
                <button class="filter-btn filter-btn-clear"
                    title="Clear any Filters Selected and Revert the Page to Normal" onclick="clearAllFilters()">Clear
                    All</button>
                <button class="filter-btn filter-btn-email" title="Send Email to Another User"
                    onclick="openEmailModal()">Send Email</button>
            </div>
        </div>

        <?php
        //Connect to the external database config file
        include '../db_connect.php';

        //initialization of connection to database
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Get all accounts in a single query
        $getAccounts = $pdo->query("
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

        $accounts = $getAccounts->fetchAll(PDO::FETCH_ASSOC);

        $accountCount = count($accounts);
        $activeCount = 0;

        foreach ($accounts as $account) {
            if ($account['is_active']) {
                $activeCount++;
            }
        }

        // Fetch all users for email dropdown
        $getUsers = $pdo->query("SELECT user_id, username, first_name, last_name, email FROM users WHERE access_level < 3 ORDER BY username");
        $users = $getUsers->fetchAll(PDO::FETCH_ASSOC);

        // Format money function
        function formatMoney($value)
        {
            return '$' . number_format((float) $value, 2);
        }
        ?>

        <!-- Statistics Summary -->
        <!-- This gives metrics regarding the chart of accounts page and transactions. -->
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
                <div class="stat-number"><?php echo count(array_unique(array_column($accounts, 'category'))); ?>
                </div>
            </div>
            <div class="stat-card">
                <h4>Last Updated</h4>
                <div class="stat-number" style="font-size: 14px;"><?php echo date('M j, Y'); ?></div>
            </div>
        </div>

        <!-- Chart of Accounts Table -->
        <!-- Displays all entries in the database of these accounts -->
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
                        <tr class="account-row" data-account-number="<?php echo $account['account_number']; ?>"
                            data-account-name="<?php echo strtolower($account['name']); ?>"
                            data-category="<?php echo strtolower($account['category']); ?>"
                            data-subcategory="<?php echo strtolower($account['subcategory']); ?>"
                            data-normal-side="<?php echo $account['normal_side']; ?>"
                            data-balance="<?php echo (float) $account['balance']; ?>"
                            data-status="<?php echo $account['is_active'] ? 'active' : 'inactive'; ?>"
                            onclick="openAccountLedger('<?php echo $account['account_number']; ?>')">

                            <td class="account-number">
                                <?php echo $account['account_number']; ?>
                            </td>

                            <td class="account-name">
                                <?php echo $account['name']; ?>
                            </td>

                            <td class="category">
                                <?php echo $account['category']; ?>
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
<!-- This is also the formatting of the verbiage for the email content. -->
<div id="emailModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="closeEmailModal()">&times;</span>
            <h2>Send Email to User</h2>
        </div>
        <div class="modal-body">
            <form id="emailForm" onsubmit="return sendEmail(event)">
                <div class="email-form-group">
                    <label for="recipientUser">Send To <span class="required-star">*</span></label>
                    <select id="recipientUser" name="recipient_user" required>
                        <option value="">Select a user...</option>
                        <?php foreach ($users as $user): ?>
                            <?php if ($user['user_id'] != $userId): // Don't show current user ?>
                                <option value="<?php echo $user['user_id']; ?>" data-email="<?php echo $user['email']; ?>">
                                    <?php echo $user['username']; ?>
                                    <?php if (!empty($user['first_name']) || !empty($user['last_name'])): ?>
                                        (<?php echo trim($user['first_name'] . ' ' . $user['last_name']); ?>)
                                    <?php endif; ?>
                                    - <?php echo $user['email']; ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="email-form-group">
                    <label for="emailSubject">Subject <span class="required-star">*</span></label>
                    <input type="text" id="emailSubject" name="subject" required placeholder="Enter email subject..."
                        value="Chart of Accounts Information">
                </div>

                <div class="email-form-group">
                    <label for="emailContent">Message <span class="required-star">*</span></label>
                    <textarea id="emailContent" name="content" required placeholder="Enter your message here...">Hello,

I wanted to share information about our Chart of Accounts:

Total Accounts: <?php echo $accountCount; ?>
Active Accounts: <?php echo $activeCount; ?>
Categories: <?php echo count(array_unique(array_column($accounts, 'category'))); ?>

Please review and let me know if you have any questions.

Best regards,
<?php echo $username; ?></textarea>
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
        onChange: function (selectedDates, dateStr, instance) {
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

    document.addEventListener('DOMContentLoaded', function () {
        allAccountRows = Array.from(document.querySelectorAll('.account-row'));

        // Add event listeners for real-time search
        document.getElementById('quickSearch').addEventListener('input', handleQuickSearch);
        document.getElementById('accountNumberFilter').addEventListener('input', applyFilters);
        document.getElementById('accountNameFilter').addEventListener('input', applyFilters);
        document.getElementById('subcategoryFilter').addEventListener('input', applyFilters);
    });

    //quick search function
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

    //applies search filters with user input 
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

    //verify no results has the correct information returning
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

    //clear filters function
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

    //sends user to the specific account ledger page
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

    // Email Modal Functions
    function openEmailModal() {
        document.getElementById('emailModal').style.display = 'block';
    }

    function closeEmailModal() {
        document.getElementById('emailModal').style.display = 'none';
    }

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