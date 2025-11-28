<?php
//KSU student project for Clarus Accounting tool
//This page is used to view changes that users make to accounts
//Initially drafted by Eric Poole. Reviewed and updated by Kyaa Goggins
//This page displays all account changes and events in the application.

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

include 'header.php';
?>

<link rel="stylesheet" href="/styling/view_change_log.css">
<div class="container"
    style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none; -ms-overflow-style: none;">

    <?php
    // Connect to the external database file
    include '../db_connect.php';

    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get all change log entries
    $stmt = $pdo->query("
        SELECT * FROM change_log 
        ORDER BY change_time DESC
    ");

    $changes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate statistics
    $totalChanges = count($changes);
    $uniqueAccounts = count(array_unique(array_column($changes, 'account_number')));

    // Get changes in last 7 days
    $recentChanges = count(array_filter($changes, function ($c) {
        return strtotime($c['change_time']) > strtotime('-7 days');
    }));

    // Get changes in last 30 days
    $monthlyChanges = count(array_filter($changes, function ($c) {
        return strtotime($c['change_time']) > strtotime('-30 days');
    }));

    // Format money function
    function formatMoney($value)
    {
        return '$' . number_format((float) $value, 2);
    }
    ?>

    <h1><i class="fa-solid fa-pen-ruler"></i> Account Change Log</h1>

    <!-- Filters -->
    <div class="filter-container">
        <strong>Filters:</strong>
        <input type="text" id="accountNumberFilter" placeholder="Account Number..." onkeyup="filterTable()">
        <input type="text" id="searchFilter" placeholder="Search changes..." onkeyup="filterTable()">
        <input type="date" id="dateFromFilter" onchange="filterTable()" placeholder="From Date">
        <input type="date" id="dateToFilter" onchange="filterTable()" placeholder="To Date">
        <button onclick="clearFilters()" style="padding: 5px 10px; margin-left: 10px;">Clear Filters</button>
    </div>

    <table id="changeLogTable">
        <thead>
            <tr>
                <th>Change ID</th>
                <th>Change Time</th>
                <th>Account Number</th>
                <th>Account Name (Before)</th>
                <th>Account Name (After)</th>
                <th>Category</th>
                <th>Modified By User ID</th>
                <th>Click to View Details</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($changes as $change): ?>
                <tr class="change-row clickable-row" data-change-id="<?php echo $change['change_id']; ?>"
                    data-account-number="<?php echo ($change['account_number']); ?>"
                    data-change-time="<?php echo $change['change_time']; ?>"
                    onclick="showChangeDetails(<?php echo (json_encode($change)); ?>)">

                    <td><?php echo ($change['change_id']); ?></td>
                    <td><?php echo date('M j, Y g:i A', strtotime($change['change_time'])); ?></td>
                    <td style="font-family: monospace; font-weight: bold;">
                        <?php echo ($change['account_number']); ?>
                    </td>
                    <td><?php echo ($change['name_before']); ?></td>
                    <td><?php echo ($change['name_after']); ?></td>
                    <td><?php echo ($change['category_after']); ?></td>
                    <td><?php echo ($change['user_id_after']); ?></td>
                    <td style="text-align: center;">View</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (empty($changes)): ?>
        <div style="text-align: center; padding: 40px; color: #6c757d;">
            <h3>No changes recorded yet</h3>
            <p>Account modifications will appear here once changes are made.</p>
        </div>
    <?php endif; ?>

</div>

<!-- Change Details Modal -->
<div id="changeDetailsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Account Change Details</h2>

        </div>
        <div class="modal-body">
            <div class="comparison-container">
                <!-- Before Column -->
                <div class="comparison-column before-column">
                    <h3><i class="fa-solid fa-triangle-exclamation"></i> BEFORE Changes</h3>

                    <div class="field-group">
                        <div class="field-label">Account Name</div>
                        <div class="field-value" id="name_before"></div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">Description</div>
                        <div class="field-value" id="description_before"></div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">Normal Side</div>
                        <div class="field-value" id="normal_side_before"></div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">Category</div>
                        <div class="field-value" id="category_before"></div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">Subcategory</div>
                        <div class="field-value" id="subcategory_before"></div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">Debit</div>
                        <div class="field-value" id="debit_before"></div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">Credit</div>
                        <div class="field-value" id="credit_before"></div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">Balance</div>
                        <div class="field-value" id="balance_before"></div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">Order Type</div>
                        <div class="field-value" id="order_type_before"></div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">Statement</div>
                        <div class="field-value" id="statement_before"></div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">Comment</div>
                        <div class="field-value" id="comment_before"></div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">Active Status</div>
                        <div class="field-value" id="is_active_before"></div>
                    </div>
                </div>

                <!-- After Column -->
                <div class="comparison-column after-column">
                    <h3><i class="fa-solid fa-square-check"></i> AFTER Changes</h3>

                    <div class="field-group">
                        <div class="field-label">Account Name</div>
                        <div class="field-value" id="name_after"></div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">Description</div>
                        <div class="field-value" id="description_after"></div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">Normal Side</div>
                        <div class="field-value" id="normal_side_after"></div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">Category</div>
                        <div class="field-value" id="category_after"></div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">Subcategory</div>
                        <div class="field-value" id="subcategory_after"></div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">Debit</div>
                        <div class="field-value" id="debit_after"></div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">Credit</div>
                        <div class="field-value" id="credit_after"></div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">Balance</div>
                        <div class="field-value" id="balance_after"></div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">Order Type</div>
                        <div class="field-value" id="order_type_after"></div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">Statement</div>
                        <div class="field-value" id="statement_after"></div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">Comment</div>
                        <div class="field-value" id="comment_after"></div>
                    </div>

                    <div class="field-group">
                        <div class="field-label">Active Status</div>
                        <div class="field-value" id="is_active_after"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="close-modal-btn" onclick="closeModal()">Close</button>
        </div>
    </div>
</div>

<script>
    function showChangeDetails(change) {

        // List of fields to compare
        const fields = [
            'name', 'description', 'normal_side', 'category', 'subcategory',
            'debit', 'credit', 'balance', 'order_type', 'statement', 'comment', 'is_active'
        ];

        // Populate before and after values
        fields.forEach(field => {
            const beforeValue = change[field + '_before'] || 'N/A';
            const afterValue = change[field + '_after'] || 'N/A';

            // Format values
            let displayBeforeValue = beforeValue;
            let displayAfterValue = afterValue;

            if (field === 'debit' || field === 'credit' || field === 'balance') {
                displayBeforeValue = formatMoney(beforeValue);
                displayAfterValue = formatMoney(afterValue);
            }

            if (field === 'is_active') {
                displayBeforeValue = beforeValue == '1' ? 'Active' : 'Inactive';
                displayAfterValue = afterValue == '1' ? 'Active' : 'Inactive';
            }

            // Set values
            document.getElementById(field + '_before').textContent = displayBeforeValue;
            document.getElementById(field + '_after').textContent = displayAfterValue;

            // Highlight changed fields
            if (beforeValue !== afterValue) {
                document.getElementById(field + '_before').classList.add('changed-field');
                document.getElementById(field + '_after').classList.add('changed-field');
            } else {
                document.getElementById(field + '_before').classList.remove('changed-field');
                document.getElementById(field + '_after').classList.remove('changed-field');
            }
        });

        // Show modal
        document.getElementById('changeDetailsModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('changeDetailsModal').style.display = 'none';
    }

    //money format functionality 
    function formatMoney(value) {
        return '$' + parseFloat(value || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    //table filtering 
    function filterTable() {
        const accountNumberFilter = document.getElementById('accountNumberFilter').value.toLowerCase();
        const searchFilter = document.getElementById('searchFilter').value.toLowerCase();
        const dateFromFilter = document.getElementById('dateFromFilter').value;
        const dateToFilter = document.getElementById('dateToFilter').value;
        const rows = document.querySelectorAll('.change-row');

        rows.forEach(row => {
            const accountNumber = row.dataset.accountNumber.toLowerCase();
            const changeTime = row.dataset.changeTime;
            const text = row.textContent.toLowerCase();

            let showRow = true;

            // Account number filter
            if (accountNumberFilter && !accountNumber.includes(accountNumberFilter)) {
                showRow = false;
            }

            // Search filter
            if (searchFilter && !text.includes(searchFilter)) {
                showRow = false;
            }

            // Date from filter
            if (dateFromFilter && changeTime < dateFromFilter) {
                showRow = false;
            }

            // Date to filter
            if (dateToFilter && changeTime > dateToFilter + ' 23:59:59') {
                showRow = false;
            }

            row.style.display = showRow ? '' : 'none';
        });
    }

    function clearFilters() {
        document.getElementById('accountNumberFilter').value = '';
        document.getElementById('searchFilter').value = '';
        document.getElementById('dateFromFilter').value = '';
        document.getElementById('dateToFilter').value = '';
        filterTable();
    }
</script>
</body>

</html>