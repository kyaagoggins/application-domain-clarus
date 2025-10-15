<?php
/**
 * Change Log Viewer
 * This page displays all changes made to accounts with before/after comparison
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Account Change Log - Clarus</title>
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
        tr.clickable-row {
            cursor: pointer;
        }
        tr.clickable-row:hover {
            background-color: #e3f2fd !important;
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
        
        /* Modal Styles */
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
            margin: 2% auto;
            padding: 0;
            border: 1px solid #888;
            border-radius: 8px;
            width: 95%;
            max-width: 1200px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #2980b9, #3498db);
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
        
        .comparison-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .comparison-column {
            padding: 15px;
            border-radius: 8px;
        }
        
        .before-column {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
        }
        
        .after-column {
            background-color: #d4edda;
            border: 2px solid #28a745;
        }
        
        .comparison-column h3 {
            margin-top: 0;
            text-align: center;
            padding: 10px;
            border-radius: 5px;
        }
        
        .before-column h3 {
            background-color: #ffc107;
            color: #000;
        }
        
        .after-column h3 {
            background-color: #28a745;
            color: white;
        }
        
        .field-group {
            margin-bottom: 15px;
            padding: 10px;
            background-color: white;
            border-radius: 5px;
        }
        
        .field-label {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .field-value {
            padding: 8px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: monospace;
        }
        
        .changed-field {
            background-color: #ffe8a1;
            border: 2px solid #ff9800;
        }
        
        .close-modal-btn {
            background-color: #6c757d;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
        }
        
        .close-modal-btn:hover {
            background-color: #545b62;
        }
        
        .change-time {
            color: #666;
            font-size: 12px;
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .comparison-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container" style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none; -ms-overflow-style: none;">
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
   </h2>
    <div style="clear:both; margin-bottom: 30px"></div>
    
    <?php
    include '../db_connect.php';

    try {
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
        $recentChanges = count(array_filter($changes, function($c) { 
            return strtotime($c['change_time']) > strtotime('-7 days'); 
        }));
        
        // Get changes in last 30 days
        $monthlyChanges = count(array_filter($changes, function($c) { 
            return strtotime($c['change_time']) > strtotime('-30 days'); 
        }));
        
    } catch(PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }

    // Format money function
    function formatMoney($value) {
        return '$' . number_format((float)$value, 2);
    }
    ?>
    
    <h1>Account Change Log</h1>
    
    <!-- Statistics Cards -->
    <div class="stats-container">
        <div class="stat-card">
            <h3>Total Changes</h3>
            <div class="stat-number"><?php echo $totalChanges; ?></div>
        </div>
        <div class="stat-card">
            <h3>Accounts Modified</h3>
            <div class="stat-number"><?php echo $uniqueAccounts; ?></div>
        </div>
        <div class="stat-card">
            <h3>Changes (Last 7 Days)</h3>
            <div class="stat-number"><?php echo $recentChanges; ?></div>
        </div>
        <div class="stat-card">
            <h3>Changes (Last 30 Days)</h3>
            <div class="stat-number"><?php echo $monthlyChanges; ?></div>
        </div>
    </div>
    
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
            <tr class="change-row clickable-row" 
                data-change-id="<?php echo $change['change_id']; ?>"
                data-account-number="<?php echo htmlspecialchars($change['account_number']); ?>"
                data-change-time="<?php echo $change['change_time']; ?>"
                onclick="showChangeDetails(<?php echo htmlspecialchars(json_encode($change)); ?>)">
                
                <td><?php echo htmlspecialchars($change['change_id']); ?></td>
                <td><?php echo date('M j, Y g:i A', strtotime($change['change_time'])); ?></td>
                <td style="font-family: monospace; font-weight: bold;"><?php echo htmlspecialchars($change['account_number']); ?></td>
                <td><?php echo htmlspecialchars($change['name_before']); ?></td>
                <td><?php echo htmlspecialchars($change['name_after']); ?></td>
                <td><?php echo htmlspecialchars($change['category_after']); ?></td>
                <td><?php echo htmlspecialchars($change['user_id_after']); ?></td>
                <td style="text-align: center;">👁️ View</td>
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
                <h2>📋 Account Change Details</h2>
                <p style="margin: 5px 0 0 0; font-size: 14px;" id="modalSubtitle"></p>
            </div>
            <div class="modal-body">
                <div class="comparison-container">
                    <!-- Before Column -->
                    <div class="comparison-column before-column">
                        <h3>⚠️ BEFORE Changes</h3>
                        
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
                        <h3>✅ AFTER Changes</h3>
                        
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
            // Set modal subtitle
            document.getElementById('modalSubtitle').innerHTML = 
                'Account #' + change.account_number + ' | Modified on ' + 
                new Date(change.change_time).toLocaleString();
            
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
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('changeDetailsModal');
            if (event.target == modal) {
                closeModal();
            }
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
        
        function formatMoney(value) {
            return '$' + parseFloat(value || 0).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
        
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