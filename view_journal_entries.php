<?php
/**
 * View Journal Entries
 * This page displays all journal entries with filtering and status management
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
//$canApprove = ($userAccessLevel > 1); // Only managers and above can approve
$canApprove = true;
// Get account_id from URL parameter (optional)
$account_id = isset($_GET['account_id']) ? trim($_GET['account_id']) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Journal Entries - Clarus</title>
    <style>
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            margin-bottom: 15px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
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
        
        .status-section {
            margin-bottom: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .status-header {
            padding: 15px 20px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            user-select: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .status-header.pending {
            background: linear-gradient(135deg, #ffc107, #ff9800);
            color: #000;
        }
        
        .status-header.approved {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .status-header.rejected {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        
        .status-header.posted {
            background: linear-gradient(135deg, #2980b9, #3498db);
            color: white;
        }
        
        .status-toggle {
            font-size: 16px;
            transition: transform 0.3s ease;
        }
        
        .status-content {
            padding: 0;
        }
        
        .status-section.collapsed .status-content {
            display: none;
        }
        
        .status-section.collapsed .status-toggle {
            transform: rotate(-90deg);
        }
        
        .journal-entry {
            border-bottom: 2px solid #e9ecef;
            padding: 20px;
            transition: background-color 0.2s;
        }
        
        .journal-entry:hover {
            background-color: #f8f9fa;
        }
        
        .journal-entry:last-child {
            border-bottom: none;
        }
        
        .entry-header {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 20px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .entry-id {
            font-weight: bold;
            color: #2980b9;
            font-size: 16px;
        }
        
        .entry-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .entry-description {
            font-weight: bold;
            color: #333;
        }
        
        .entry-meta {
            font-size: 12px;
            color: #666;
        }
        
        .entry-totals {
            text-align: right;
            font-family: monospace;
        }
        
        .entry-total-label {
            font-size: 11px;
            color: #666;
        }
        
        .entry-total-value {
            font-size: 16px;
            font-weight: bold;
            color: #2980b9;
        }
        
        .entry-lines-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        .entry-lines-table th {
            background-color: #f8f9fa;
            padding: 10px;
            text-align: left;
            font-size: 12px;
            color: #666;
            border-bottom: 2px solid #dee2e6;
        }
        
        .entry-lines-table td {
            padding: 10px;
            border-bottom: 1px solid #f1f1f1;
        }
        
        .entry-lines-table tr:last-child td {
            border-bottom: none;
        }
        
        .account-link {
            color: #2980b9;
            text-decoration: none;
            font-weight: 500;
        }
        
        .account-link:hover {
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
        
        .entry-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }
        
        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
        }
        
        .btn-approve {
            background-color: #28a745;
            color: white;
        }
        
        .btn-approve:hover {
            background-color: #218838;
        }
        
        .btn-reject {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-reject:hover {
            background-color: #c82333;
        }
        
        .btn-view-docs {
            background-color: #17a2b8;
            color: white;
        }
        
        .btn-view-docs:hover {
            background-color: #138496;
        }
        
        .rejection-reason {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 10px;
            margin-top: 10px;
        }
        
        .rejection-reason strong {
            color: #721c24;
        }
        
        .no-entries {
            text-align: center;
            padding: 40px;
            color: #6c757d;
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
            margin: 5% auto;
            padding: 0;
            border: 1px solid #888;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #dc3545, #c82333);
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
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
            min-height: 100px;
            resize: vertical;
        }
        
        .btn-submit-rejection {
            background-color: #dc3545;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            margin-left: 10px;
        }
        
        .btn-submit-rejection:hover {
            background-color: #c82333;
        }
        
        .btn-cancel-modal {
            background-color: #6c757d;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
        }
        
        .btn-cancel-modal:hover {
            background-color: #545b62;
        }
        
        @media (max-width: 768px) {
            .entry-header {
                grid-template-columns: 1fr;
            }
            
            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <div class="container" style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none; -ms-overflow-style: none;">
    <?php include 'header.php'; ?>
    
    <?php
    include '../db_connect.php';

    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Build query based on account_id filter
        $whereClause = "";
        $params = [];
        
        if ($account_id) {
            $whereClause = "WHERE je.account_id = :account_id";
            $params[':account_id'] = $account_id;
        }
        
        // Get all journal entries with their lines
        $stmt = $pdo->prepare("
            SELECT 
                je.*,
                u.username as created_by_name,
                a.name as account_name
            FROM journal_entries je
            LEFT JOIN users u ON je.created_by = u.user_id
            LEFT JOIN accounts a ON je.account_id = a.account_number
            $whereClause
            ORDER BY je.entry_date DESC, je.entry_id DESC
        ");
        
        $stmt->execute($params);
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get lines for each entry
        foreach ($entries as &$entry) {
            $stmt = $pdo->prepare("
                SELECT 
                    jel.*,
                    a.name as account_name,
                    a.category
                FROM journal_entry_lines jel
                LEFT JOIN accounts a ON jel.account_number = a.account_number
                WHERE jel.journal_entry_id = :entry_id
                ORDER BY 
                    CASE WHEN jel.debit_amount > 0 THEN 0 ELSE 1 END,
                    jel.line_id
            ");
            $stmt->execute([':entry_id' => $entry['entry_id']]);
            $entry['lines'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Organize by status
        $entriesByStatus = [
            'pending' => [],
            'approved' => [],
            'rejected' => [],
            'posted' => []
        ];
        
        foreach ($entries as $entry) {
            $entriesByStatus[$entry['status']][] = $entry;
        }
        
        // Calculate statistics
        $totalEntries = count($entries);
        $pendingCount = count($entriesByStatus['pending']);
        $approvedCount = count($entriesByStatus['approved']);
        $rejectedCount = count($entriesByStatus['rejected']);
        $postedCount = count($entriesByStatus['posted']);
        
    } catch(PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }

    function formatMoney($value) {
        return '$' . number_format((float)$value, 2);
    }
    ?>
    
    <h1>Journal Entries</h1>
    
    <?php if ($account_id): ?>
    <div style="background: #e7f3ff; padding: 10px; border-left: 4px solid #2980b9; margin-bottom: 20px;">
        <strong>Filtered by Account:</strong> <?php echo htmlspecialchars($account_id); ?>
        <a href="view_journal_entries.php" style="margin-left: 15px; color: #2980b9;">Clear Filter</a>
    </div>
    <?php endif; ?>
    
    <!-- Statistics -->
    <div class="stats-container">
        <div class="stat-card">
            <h3>Total Entries</h3>
            <div class="stat-number"><?php echo $totalEntries; ?></div>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, #ffc107, #ff9800);">
            <h3>Pending</h3>
            <div class="stat-number"><?php echo $pendingCount; ?></div>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, #28a745, #20c997);">
            <h3>Approved</h3>
            <div class="stat-number"><?php echo $approvedCount; ?></div>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, #dc3545, #c82333);">
            <h3>Rejected</h3>
            <div class="stat-number"><?php echo $rejectedCount; ?></div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="filter-container">
        <div class="filter-row">
            <div class="filter-group">
                <label>🔍 Search</label>
                <input type="text" id="searchFilter" placeholder="Account name, amount, or date..." onkeyup="filterEntries()">
            </div>
            
            <div class="filter-group">
                <label>Date From</label>
                <input type="date" id="dateFromFilter" onchange="filterEntries()">
            </div>
            
            <div class="filter-group">
                <label>Date To</label>
                <input type="date" id="dateToFilter" onchange="filterEntries()">
            </div>
            
            <div class="filter-group">
                <label>Status</label>
                <select id="statusFilterSelect" onchange="filterEntries()">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="posted">Posted</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>&nbsp;</label>
                <button class="filter-btn" onclick="clearFilters()">Clear Filters</button>
            </div>
        </div>
    </div>
    
    <!-- Journal Entries by Status -->
    <?php
    $statusLabels = [
        'pending' => '⏳ Pending Approval',
        'approved' => '✅ Approved',
        'rejected' => '❌ Rejected',
        'posted' => '📝 Posted to Ledger'
    ];
    
    foreach ($entriesByStatus as $status => $statusEntries):
        if (empty($statusEntries)) continue;
    ?>
    
    <div class="status-section" id="section-<?php echo $status; ?>" data-status="<?php echo $status; ?>">
        <div class="status-header <?php echo $status; ?>" onclick="toggleSection('<?php echo $status; ?>')">
            <span><?php echo $statusLabels[$status]; ?> (<?php echo count($statusEntries); ?>)</span>
            <span class="status-toggle">▼</span>
        </div>
        
        <div class="status-content">
            <?php foreach ($statusEntries as $entry): ?>
            <div class="journal-entry" 
                 data-entry-id="<?php echo $entry['entry_id']; ?>"
                 data-entry-date="<?php echo $entry['entry_date']; ?>"
                 data-status="<?php echo $entry['status']; ?>">
                
                <div class="entry-header">
                    <div class="entry-id">
                        #JE-<?php echo str_pad($entry['entry_id'], 6, '0', STR_PAD_LEFT); ?>
                    </div>
                    
                    <div class="entry-info">
                        <div class="entry-description">
                            <?php echo htmlspecialchars($entry['description']); ?>
                        </div>
                        <div class="entry-meta">
                            📅 <?php echo date('M j, Y', strtotime($entry['entry_date'])); ?> | 
                            👤 Created by <?php echo htmlspecialchars($entry['created_by_name']); ?> | 
                            🏦 Account: <?php echo htmlspecialchars($entry['account_id']); ?> - <?php echo htmlspecialchars($entry['account_name']); ?>
                            <?php if ($entry['reference_number']): ?>
                            | 🔖 Ref: <?php echo htmlspecialchars($entry['reference_number']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="entry-totals">
                        <div class="entry-total-label">Total Amount</div>
                        <div class="entry-total-value"><?php echo formatMoney($entry['total_debit']); ?></div>
                    </div>
                </div>
                
                <!-- Entry Lines -->
                <table class="entry-lines-table">
                    <thead>
                        <tr>
                            <th>Account</th>
                            <th>Description</th>
                            <th style="text-align: right;">Debit</th>
                            <th style="text-align: right;">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entry['lines'] as $line): ?>
                        <tr class="entry-line-row" 
                            data-account-name="<?php echo strtolower(htmlspecialchars($line['account_name'])); ?>"
                            data-debit="<?php echo $line['debit_amount']; ?>"
                            data-credit="<?php echo $line['credit_amount']; ?>">
                            <td>
                                <a href="account_ledger.php?account_number=<?php echo urlencode($line['account_number']); ?>" 
                                   class="account-link">
                                    <?php echo htmlspecialchars($line['account_number']); ?> - 
                                    <?php echo htmlspecialchars($line['account_name']); ?>
                                </a>
                                <div style="font-size: 11px; color: #666;">
                                    <?php echo htmlspecialchars($line['category']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($line['line_description']); ?></td>
                            <td style="text-align: right;">
                                <?php if ($line['debit_amount'] > 0): ?>
                                    <span class="amount-debit"><?php echo formatMoney($line['debit_amount']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <?php if ($line['credit_amount'] > 0): ?>
                                    <span class="amount-credit"><?php echo formatMoney($line['credit_amount']); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="border-top: 2px solid #dee2e6; font-weight: bold;">
                            <td colspan="2" style="text-align: right;">Totals:</td>
                            <td style="text-align: right;">
                                <span class="amount-debit"><?php echo formatMoney($entry['total_debit']); ?></span>
                            </td>
                            <td style="text-align: right;">
                                <span class="amount-credit"><?php echo formatMoney($entry['total_credit']); ?></span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <?php if ($entry['status'] == 'rejected' && $entry['notes']): ?>
                <div class="rejection-reason">
                    <strong>Rejection Reason:</strong><br>
                    <?php echo nl2br(htmlspecialchars($entry['notes'])); ?>
                </div>
                <?php endif; ?>
                
                <!-- Actions -->
                <div class="entry-actions">
                    <?php if ($canApprove && $entry['status'] == 'pending'): ?>
                        <button class="action-btn btn-approve" 
                                onclick="approveEntry(<?php echo $entry['entry_id']; ?>)">
                            ✅ Approve
                        </button>
                        <button class="action-btn btn-reject" 
                                onclick="openRejectModal(<?php echo $entry['entry_id']; ?>)">
                            ❌ Reject
                        </button>
                    <?php endif; ?>
                    
                    <?php 
                    $documents = json_decode($entry['source_documents'], true);
                    if ($documents && count($documents) > 0): 
                    ?>
                        <button class="action-btn btn-view-docs" 
                                onclick="viewDocuments(<?php echo $entry['entry_id']; ?>, <?php echo htmlspecialchars(json_encode($documents)); ?>)">
                            📎 View Documents (<?php echo count($documents); ?>)
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php endforeach; ?>
    
    <?php if ($totalEntries == 0): ?>
    <div class="no-entries">
        <h3>No journal entries found</h3>
        <p>Create a new journal entry to get started.</p>
        <a href="create_journal_entry.php?account_id=<?php echo htmlspecialchars($account_id ?: ''); ?>" 
           style="display: inline-block; margin-top: 15px; padding: 10px 20px; background-color: #2980b9; color: white; text-decoration: none; border-radius: 4px;">
            ➕ Create Journal Entry
        </a>
    </div>
    <?php endif; ?>
    
    </div>
    
    <!-- Rejection Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeRejectModal()">&times;</span>
                <h2>❌ Reject Journal Entry</h2>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Rejection Reason <span style="color: red;">*</span></label>
                    <textarea id="rejectionReason" placeholder="Please provide a detailed reason for rejecting this journal entry..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel-modal" onclick="closeRejectModal()">Cancel</button>
                <button type="button" class="btn-submit-rejection" onclick="submitRejection()">Submit Rejection</button>
            </div>
        </div>
    </div>
    
    <script>
        let currentRejectEntryId = null;
        
        function toggleSection(status) {
            const section = document.getElementById('section-' + status);
            section.classList.toggle('collapsed');
        }
        
        function filterEntries() {
            const searchTerm = document.getElementById('searchFilter').value.toLowerCase();
            const dateFrom = document.getElementById('dateFromFilter').value;
            const dateTo = document.getElementById('dateToFilter').value;
            const statusFilter = document.getElementById('statusFilterSelect').value;
            
            const entries = document.querySelectorAll('.journal-entry');
            const sections = document.querySelectorAll('.status-section');
            
            entries.forEach(entry => {
                let show = true;
                
                // Status filter
                const entryStatus = entry.dataset.status;
                if (statusFilter && entryStatus !== statusFilter) {
                    show = false;
                }
                
                // Date filter
                const entryDate = entry.dataset.entryDate;
                if (dateFrom && entryDate < dateFrom) {
                    show = false;
                }
                if (dateTo && entryDate > dateTo) {
                    show = false;
                }
                
                // Search filter (account name, amount)
                if (searchTerm) {
                    const entryText = entry.textContent.toLowerCase();
                    if (!entryText.includes(searchTerm)) {
                        show = false;
                    }
                }
                
                entry.style.display = show ? 'block' : 'none';
            });
            
            // Update section visibility
            sections.forEach(section => {
                const visibleEntries = section.querySelectorAll('.journal-entry[style*="block"]');
                const allEntries = section.querySelectorAll('.journal-entry:not([style*="none"])');
                
                if (visibleEntries.length === 0 && allEntries.length === 0) {
                    section.style.display = 'none';
                } else {
                    section.style.display = 'block';
                }
            });
        }
        
        function clearFilters() {
            document.getElementById('searchFilter').value = '';
            document.getElementById('dateFromFilter').value = '';
            document.getElementById('dateToFilter').value = '';
            document.getElementById('statusFilterSelect').value = '';
            
            document.querySelectorAll('.journal-entry').forEach(entry => {
                entry.style.display = 'block';
            });
            
            document.querySelectorAll('.status-section').forEach(section => {
                section.style.display = 'block';
            });
        }
        
        function approveEntry(entryId) {
            if (!confirm('Are you sure you want to approve this journal entry?')) {
                return;
            }
            
            fetch('update_journal_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    entry_id: entryId,
                    status: 'approved'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Journal entry approved successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to approve journal entry'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while approving the journal entry.');
            });
        }
        
        function openRejectModal(entryId) {
            currentRejectEntryId = entryId;
            document.getElementById('rejectionReason').value = '';
            document.getElementById('rejectModal').style.display = 'block';
        }
        
        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
            currentRejectEntryId = null;
        }
        
        function submitRejection() {
            const reason = document.getElementById('rejectionReason').value.trim();
            
            if (!reason) {
                alert('Please provide a rejection reason.');
                return;
            }
            
            fetch('update_journal_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    entry_id: currentRejectEntryId,
                    status: 'rejected',
                    notes: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Journal entry rejected successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to reject journal entry'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while rejecting the journal entry.');
            });
        }
        
        function viewDocuments(entryId, documents) {
            let docList = 'Source Documents:\n\n';
            documents.forEach((doc, index) => {
                docList += `${index + 1}. ${doc}\n`;
            });
            docList += '\nClick OK to download documents.';
            
            if (confirm(docList)) {
                // Open documents in new tabs
                documents.forEach(doc => {
                    window.open('../uploads/journal_documents/' + doc, '_blank');
                });
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('rejectModal');
            if (event.target == modal) {
                closeRejectModal();
            }
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeRejectModal();
            }
        });
    </script>
</body>
</html>