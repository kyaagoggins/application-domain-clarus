<?php
/**
 * View Single Journal Entry
 * This page displays detailed information about a specific journal entry
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
$canApprove = ($userAccessLevel >= 5);

// Get entry_id from URL parameter
$entry_id = isset($_GET['entry_id']) ? intval($_GET['entry_id']) : null;

if (!$entry_id) {
    die("Error: Entry ID is required. Please provide a valid entry_id parameter in the URL.");
}

// Include database configuration
include '../db_connect.php';

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get journal entry details
    $stmt = $pdo->prepare("
        SELECT 
            je.*,
            u.username as created_by_name,
            u.first_name as creator_first_name,
            u.last_name as creator_last_name,
            a.name as account_name,
            a.category as account_category,
            approver.username as approved_by_name
        FROM journal_entries je
        LEFT JOIN users u ON je.created_by = u.user_id
        LEFT JOIN accounts a ON je.account_id = a.account_number
        LEFT JOIN users approver ON je.approved_by = approver.user_id
        WHERE je.entry_id = :entry_id
    ");
    $stmt->execute([':entry_id' => $entry_id]);
    $entry = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$entry) {
        die("Error: Journal entry not found with ID: $entry_id");
    }
    
    // Get entry lines
    $stmt = $pdo->prepare("
        SELECT 
            jel.*,
            a.name as account_name,
            a.category,
            a.normal_side
        FROM journal_entry_lines jel
        LEFT JOIN accounts a ON jel.account_number = a.account_number
        WHERE jel.journal_entry_id = :entry_id
        ORDER BY 
            CASE WHEN jel.debit_amount > 0 THEN 0 ELSE 1 END,
            jel.line_id
    ");
    $stmt->execute([':entry_id' => $entry_id]);
    $lines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

function formatMoney($value) {
    return '$' . number_format((float)$value, 2);
}

// Parse source documents
$sourceDocuments = json_decode($entry['source_documents'], true) ?: [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Journal Entry #<?php echo $entry_id; ?> - Clarus</title>
    <style>
        .entry-header {
            background: linear-gradient(135deg, #2980b9, #3498db);
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .entry-header h1 {
            margin: 0 0 15px 0;
            font-size: 32px;
        }
        
        .entry-id-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .status-pending {
            background: #ffc107;
            color: #000;
        }
        
        .status-approved {
            background: #28a745;
            color: white;
        }
        
        .status-rejected {
            background: #dc3545;
            color: white;
        }
        
        .status-posted {
            background: #17a2b8;
            color: white;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #2980b9;
        }
        
        .info-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .info-value {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }
        
        .section-container {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #2980b9;
            margin: 0 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #2980b9;
        }
        
        .entry-lines-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        .entry-lines-table thead {
            background: linear-gradient(135deg, #2980b9, #3498db);
            color: white;
        }
        
        .entry-lines-table th {
            padding: 15px 10px;
            text-align: left;
            font-weight: bold;
        }
        
        .entry-lines-table th.text-right {
            text-align: right;
        }
        
        .entry-lines-table tbody tr {
            border-bottom: 1px solid #f1f1f1;
        }
        
        .entry-lines-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .entry-lines-table td {
            padding: 12px 10px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .account-link {
            color: #2980b9;
            text-decoration: none;
            font-weight: 500;
        }
        
        .account-link:hover {
            text-decoration: underline;
        }
        
        .account-category {
            font-size: 11px;
            color: #666;
            font-style: italic;
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
        
        .totals-row {
            border-top: 3px solid #2980b9;
            font-weight: bold;
            background-color: #f8f9fa;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
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
        
        .btn-back {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-back:hover {
            background-color: #545b62;
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
        
        .btn-print {
            background-color: #2980b9;
            color: white;
        }
        
        .btn-print:hover {
            background-color: #21618c;
        }
        
        .documents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .document-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            text-align: center;
        }
        
        .document-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .document-name {
            font-size: 12px;
            color: #666;
            word-break: break-all;
        }
        
        .document-link {
            display: inline-block;
            margin-top: 10px;
            padding: 5px 15px;
            background-color: #2980b9;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .document-link:hover {
            background-color: #21618c;
        }
        
        .rejection-box {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .rejection-box h3 {
            color: #721c24;
            margin-top: 0;
        }
        
        .rejection-reason {
            color: #721c24;
            line-height: 1.6;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -30px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #2980b9;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #2980b9;
        }
        
        .timeline-item::after {
            content: '';
            position: absolute;
            left: -25px;
            top: 17px;
            width: 2px;
            height: calc(100% - 17px);
            background-color: #dee2e6;
        }
        
        .timeline-item:last-child::after {
            display: none;
        }
        
        .timeline-date {
            font-size: 12px;
            color: #666;
            font-weight: bold;
        }
        
        .timeline-content {
            margin-top: 5px;
            color: #333;
        }
        
        @media print {
            .action-buttons,
            .logo,
            .btn {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .documents-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                text-align: center;
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
        <a style="float:right; margin-right: 30px; padding: 10px; text-decoration:none; background-color: #efefef; color: black; font-size: 14px" href="view_journal_entries.php">View Journal Entries</a>
   </h2>
    <div style="clear:both; margin-bottom: 30px"></div>
    
    <!-- Entry Header -->
    <div class="entry-header">
        <div class="entry-id-badge">
            #JE-<?php echo str_pad($entry_id, 6, '0', STR_PAD_LEFT); ?>
        </div>
        <span class="status-badge status-<?php echo $entry['status']; ?>">
            <?php 
            $statusLabels = [
                'pending' => '⏳ Pending',
                'approved' => '✅ Approved',
                'rejected' => '❌ Rejected',
                'posted' => '📝 Posted'
            ];
            echo $statusLabels[$entry['status']];
            ?>
        </span>
        <h1><?php echo htmlspecialchars($entry['description']); ?></h1>
    </div>
    
    <!-- Action Buttons -->
    <div class="action-buttons">
        <button onclick="window.history.back()" class="btn btn-back">
            ⬅️ Back
        </button>
        <a href="view_journal_entries.php" class="btn btn-back">
            📋 All Entries
        </a>
        <a href="account_ledger.php?account_number=<?php echo urlencode($entry['account_id']); ?>" class="btn btn-back">
            📒 View Ledger
        </a>
        <button onclick="window.print()" class="btn btn-print">
            🖨️ Print
        </button>
        
        <?php if ($canApprove && $entry['status'] == 'pending'): ?>
        <button class="btn btn-approve" onclick="approveEntry(<?php echo $entry_id; ?>)">
            ✅ Approve Entry
        </button>
        <button class="btn btn-reject" onclick="openRejectModal(<?php echo $entry_id; ?>)">
            ❌ Reject Entry
        </button>
        <?php endif; ?>
    </div>
    
    <?php if ($entry['status'] == 'rejected' && $entry['notes']): ?>
    <!-- Rejection Notice -->
    <div class="rejection-box">
        <h3>❌ This entry has been rejected</h3>
        <div class="rejection-reason">
            <strong>Reason:</strong><br>
            <?php echo nl2br(htmlspecialchars($entry['notes'])); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Entry Information -->
    <div class="info-grid">
        <div class="info-card">
            <div class="info-label">Entry Date</div>
            <div class="info-value">📅 <?php echo date('F j, Y', strtotime($entry['entry_date'])); ?></div>
        </div>
        
        <div class="info-card">
            <div class="info-label">Related Account</div>
            <div class="info-value">
                <a href="view_account.php?account_number=<?php echo urlencode($entry['account_id']); ?>" class="account-link">
                    <?php echo htmlspecialchars($entry['account_id']); ?> - <?php echo htmlspecialchars($entry['account_name']); ?>
                </a>
            </div>
        </div>
        
        <?php if ($entry['reference_number']): ?>
        <div class="info-card">
            <div class="info-label">Reference Number</div>
            <div class="info-value">🔖 <?php echo htmlspecialchars($entry['reference_number']); ?></div>
        </div>
        <?php endif; ?>
        
        <div class="info-card">
            <div class="info-label">Total Amount</div>
            <div class="info-value" style="color: #2980b9; font-weight: bold;">
                💰 <?php echo formatMoney($entry['total_debit']); ?>
            </div>
        </div>
        
        <div class="info-card">
            <div class="info-label">Created By</div>
            <div class="info-value">
                👤 <?php echo htmlspecialchars($entry['created_by_name']); ?>
                <?php if ($entry['creator_first_name'] || $entry['creator_last_name']): ?>
                <br><small>(<?php echo htmlspecialchars(trim($entry['creator_first_name'] . ' ' . $entry['creator_last_name'])); ?>)</small>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="info-card">
            <div class="info-label">Created On</div>
            <div class="info-value">🕐 <?php echo date('M j, Y g:i A', strtotime($entry['created_at'])); ?></div>
        </div>
        
        <?php if ($entry['approved_by']): ?>
        <div class="info-card">
            <div class="info-label"><?php echo $entry['status'] == 'rejected' ? 'Rejected By' : 'Approved By'; ?></div>
            <div class="info-value">
                👤 <?php echo htmlspecialchars($entry['approved_by_name']); ?>
            </div>
        </div>
        
        <div class="info-card">
            <div class="info-label"><?php echo $entry['status'] == 'rejected' ? 'Rejected On' : 'Approved On'; ?></div>
            <div class="info-value">🕐 <?php echo date('M j, Y g:i A', strtotime($entry['approved_at'])); ?></div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Journal Entry Lines -->
    <div class="section-container">
        <h2 class="section-title">💰 Journal Entry Lines</h2>
        
        <table class="entry-lines-table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Account</th>
                    <th>Line Description</th>
                    <th class="text-right" style="width: 140px;">Debit</th>
                    <th class="text-right" style="width: 140px;">Credit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lines as $index => $line): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td>
                        <a href="account_ledger.php?account_number=<?php echo urlencode($line['account_number']); ?>" 
                           class="account-link">
                            <?php echo htmlspecialchars($line['account_number']); ?> - 
                            <?php echo htmlspecialchars($line['account_name']); ?>
                        </a>
                        <div class="account-category">
                            <?php echo htmlspecialchars($line['category']); ?> • 
                            <?php echo htmlspecialchars($line['normal_side']); ?> Side
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($line['line_description']); ?></td>
                    <td class="text-right">
                        <?php if ($line['debit_amount'] > 0): ?>
                            <span class="amount-debit"><?php echo formatMoney($line['debit_amount']); ?></span>
                        <?php else: ?>
                            <span style="color: #ccc;">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right">
                        <?php if ($line['credit_amount'] > 0): ?>
                            <span class="amount-credit"><?php echo formatMoney($line['credit_amount']); ?></span>
                        <?php else: ?>
                            <span style="color: #ccc;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <!-- Totals Row -->
                <tr class="totals-row">
                    <td colspan="3" class="text-right">Totals:</td>
                    <td class="text-right">
                        <span class="amount-debit"><?php echo formatMoney($entry['total_debit']); ?></span>
                    </td>
                    <td class="text-right">
                        <span class="amount-credit"><?php echo formatMoney($entry['total_credit']); ?></span>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <div style="margin-top: 15px; padding: 15px; background-color: #d4edda; border-radius: 4px; border: 1px solid #c3e6cb;">
            <strong style="color: #155724;">✅ Entry is Balanced:</strong>
            <span style="color: #155724;">
                Debits (<?php echo formatMoney($entry['total_debit']); ?>) = 
                Credits (<?php echo formatMoney($entry['total_credit']); ?>)
            </span>
        </div>
    </div>
    
    <?php if (!empty($sourceDocuments)): ?>
    <!-- Source Documents -->
    <div class="section-container">
        <h2 class="section-title">📎 Source Documents (<?php echo count($sourceDocuments); ?>)</h2>
        
        <div class="documents-grid">
            <?php foreach ($sourceDocuments as $doc): 
                $extension = strtolower(pathinfo($doc, PATHINFO_EXTENSION));
                $icon = '📄';
                
                if (in_array($extension, ['pdf'])) $icon = '📕';
                elseif (in_array($extension, ['doc', 'docx'])) $icon = '📘';
                elseif (in_array($extension, ['xls', 'xlsx'])) $icon = '📗';
                elseif (in_array($extension, ['jpg', 'jpeg', 'png'])) $icon = '🖼️';
                elseif (in_array($extension, ['csv'])) $icon = '📊';
            ?>
            <div class="document-card">
                <div class="document-icon"><?php echo $icon; ?></div>
                <div class="document-name"><?php echo htmlspecialchars(basename($doc)); ?></div>
                <a href="../uploads/journal_documents/<?php echo urlencode($doc); ?>" 
                   target="_blank" 
                   class="document-link">
                    📥 Download
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Activity Timeline -->
    <div class="section-container">
        <h2 class="section-title">📅 Activity Timeline</h2>
        
        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-date">
                    <?php echo date('M j, Y g:i A', strtotime($entry['created_at'])); ?>
                </div>
                <div class="timeline-content">
                    <strong>Entry Created</strong><br>
                    Created by <?php echo htmlspecialchars($entry['created_by_name']); ?>
                </div>
            </div>
            
            <?php if ($entry['approved_at']): ?>
            <div class="timeline-item">
                <div class="timeline-date">
                    <?php echo date('M j, Y g:i A', strtotime($entry['approved_at'])); ?>
                </div>
                <div class="timeline-content">
                    <strong><?php echo $entry['status'] == 'rejected' ? 'Entry Rejected' : 'Entry Approved'; ?></strong><br>
                    <?php echo $entry['status'] == 'rejected' ? 'Rejected' : 'Approved'; ?> by 
                    <?php echo htmlspecialchars($entry['approved_by_name']); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($entry['status'] == 'posted'): ?>
            <div class="timeline-item">
                <div class="timeline-date">
                    <?php echo date('M j, Y g:i A', strtotime($entry['updated_at'])); ?>
                </div>
                <div class="timeline-content">
                    <strong>Posted to Ledger</strong><br>
                    Entry has been posted to the general ledger
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    </div>
    
    <!-- Rejection Modal (same as in view_journal_entries.php) -->
    <div id="rejectModal" class="modal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
        <div style="background-color: #fefefe; margin: 5% auto; padding: 0; border: 1px solid #888; border-radius: 8px; width: 90%; max-width: 500px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
            <div style="background: linear-gradient(135deg, #dc3545, #c82333); color: white; padding: 20px; border-radius: 8px 8px 0 0;">
                <span onclick="closeRejectModal()" style="color: white; float: right; font-size: 28px; font-weight: bold; cursor: pointer; line-height: 20px;">&times;</span>
                <h2 style="margin: 0; font-size: 1.5em;">❌ Reject Journal Entry</h2>
            </div>
            <div style="padding: 20px;">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Rejection Reason <span style="color: red;">*</span></label>
                    <textarea id="rejectionReason" placeholder="Please provide a detailed reason for rejecting this journal entry..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box; min-height: 100px; resize: vertical;"></textarea>
                </div>
            </div>
            <div style="background-color: #f8f9fa; padding: 15px 20px; border-radius: 0 0 8px 8px; text-align: right;">
                <button type="button" onclick="closeRejectModal()" style="background-color: #6c757d; color: white; padding: 10px 25px; border: none; border-radius: 4px; font-size: 14px; cursor: pointer;">Cancel</button>
                <button type="button" onclick="submitRejection()" style="background-color: #dc3545; color: white; padding: 10px 25px; border: none; border-radius: 4px; font-size: 14px; cursor: pointer; margin-left: 10px;">Submit Rejection</button>
            </div>
        </div>
    </div>
    
    <script>
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
            window.currentRejectEntryId = entryId;
            document.getElementById('rejectionReason').value = '';
            document.getElementById('rejectModal').style.display = 'block';
        }
        
        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
            window.currentRejectEntryId = null;
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
                    entry_id: window.currentRejectEntryId,
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