<?php
/**
 * View Account Page
 * This page displays details of a specific accounting account
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

// Check user access level for edit/deactivate permissions
$userAccessLevel = isset($_SESSION['access_level']) ? (int)$_SESSION['access_level'] : 0;
$canEditAccount = ($userAccessLevel >= 3);

// Get account_number from URL parameter
$account_number = isset($_GET['account_number']) ? trim($_GET['account_number']) : null;

if (!$account_number) {
    die("Error: Account Number is required. Please provide a valid account_number parameter in the URL.");
}

// Include database configuration
include '../db_connect.php';

// Fetch account details from database
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE account_number = :account_number");
    $stmt->execute([':account_number' => $account_number]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$account) {
        die("Error: Account not found with Account Number: $account_number");
    }
    
    // Fetch all users for email dropdown
    $stmt = $pdo->query("SELECT user_id, username, first_name, last_name, email FROM users WHERE access_level > 1 ORDER BY username");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Format monetary values for display
function formatMoney($value) {
    return '$' . number_format((float)$value, 2);
}

// Check if account is active and has a balance
$isActive = isset($account['is_active']) ? (int)$account['is_active'] : 1;
$accountBalance = (float)$account['balance'];
$hasBalance = ($accountBalance != 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>View Account - <?php echo htmlspecialchars($account['name']); ?></title>
    <style>
        .form-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .form-column {
            display: flex;
            flex-direction: column;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
            background-color: #f8f9fa;
            color: #495057;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 60px;
        }
        .form-group .readonly-field {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
        .help-text {
            color: #666;
            font-size: 11px;
            margin-top: 3px;
        }
        .form-footer {
            grid-column: 1 / -1;
            text-align: center;
            margin-top: 20px;
        }
        .action-btn {
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin: 0 5px;
            text-decoration: none;
            display: inline-block;
        }
        .edit-btn {
            background-color: #2980b9;
        }
        .edit-btn:hover {
            background-color: #2980b9;
        }
        .back-btn {
            background-color: #6c757d;
        }
        .back-btn:hover {
            background-color: #545b62;
        }
        .deactivate-btn {
            background-color: #fd7e14;
        }
        .deactivate-btn:hover {
            background-color: #e66a02;
        }
        .reactivate-btn {
            background-color: #28a745;
        }
        .reactivate-btn:hover {
            background-color: #218838;
        }
        .disabled-btn {
            background-color: #adb5bd;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .access-restricted {
            background-color: #dc3545;
            color: white;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            font-size: 12px;
            text-align: center;
        }
        .account-header {
            background: linear-gradient(135deg, #2980b9, #6dd5fa, #ffffff);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .account-header.inactive {
            background: linear-gradient(135deg, #6c757d, #495057);
        }
        .account-number {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .account-name {
            font-size: 1.5em;
            margin-bottom: 5px;
        }
        .account-balance {
            font-size: 1.8em;
            font-weight: bold;
            padding: 10px;
            background: rgba(255,255,255,0.8);
            border-radius: 5px;
            margin-top: 10px;
        }
        .balance-positive {
            color: #28a745;
        }
        .balance-negative {
            color: #dc3545;
        }
        .info-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            margin: 5px;
            font-size: 0.9em;
        }
        .status-badge {
            display: inline-block;
            background: rgba(255,255,255,0.9);
            color: #333;
            padding: 8px 20px;
            border-radius: 20px;
            margin: 10px 5px;
            font-size: 1em;
            font-weight: bold;
        }
        .status-inactive {
            background: #dc3545;
            color: white;
        }
        .status-active {
            background: #28a745;
            color: white;
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
            .form-container {
                grid-template-columns: 1fr;
                gap: 10px;
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
    <?php include 'header.php'; ?>
    
    <div class="account-header <?php echo $isActive ? '' : 'inactive'; ?>">
        <div class="account-number"><?php echo htmlspecialchars($account['account_number']); ?></div>
        <div class="account-name"><?php echo htmlspecialchars($account['name']); ?></div>
        <div class="info-badge"><?php echo htmlspecialchars($account['category']); ?></div>
        <div class="info-badge"><?php echo htmlspecialchars($account['normal_side']); ?> Side</div>
        <div class="info-badge"><?php echo htmlspecialchars($account['statement']); ?></div>
        <div class="status-badge <?php echo $isActive ? 'status-active' : 'status-inactive'; ?>">
            <?php echo $isActive ? '✓ ACTIVE' : '✗ INACTIVE'; ?>
        </div>
        <div class="account-balance <?php echo (float)$account['balance'] >= 0 ? 'balance-positive' : 'balance-negative'; ?>">
            Balance: <?php echo formatMoney($account['balance']); ?>
        </div>
    </div>
    
    <?php if (!$canEditAccount): ?>
    <div class="access-restricted">
        🔒 Your access level (<?php echo $userAccessLevel; ?>) does not permit account editing or deactivation. Contact an administrator for assistance.
    </div>
    <?php endif; ?>
    
    <h1>Account Details</h1>
    
    <form>
        <div class="form-container">
            <!-- Left Column -->
            <div class="form-column">
                <div class="form-group">
                    <label for="accountNumber">Account Number</label>
                    <input type="text" id="accountNumber" value="<?php echo htmlspecialchars($account['account_number']); ?>" readonly class="readonly-field">
                    <div class="help-text">Unique account identifier</div>
                </div>
                
                <div class="form-group">
                    <label for="accountName">Account Name</label>
                    <input type="text" id="accountName" value="<?php echo htmlspecialchars($account['name']); ?>" readonly class="readonly-field">
                    <div class="help-text">Display name for this account</div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" rows="3" readonly class="readonly-field"><?php echo htmlspecialchars($account['description']); ?></textarea>
                    <div class="help-text">Detailed description of the account</div>
                </div>
                
                <div class="form-group">
                    <label for="normalSide">Normal Side</label>
                    <input type="text" id="normalSide" value="<?php echo htmlspecialchars($account['normal_side']); ?>" readonly class="readonly-field">
                    <div class="help-text">Debit or Credit normal balance side</div>
                </div>
                
                <div class="form-group">
                    <label for="category">Category</label>
                    <input type="text" id="category" value="<?php echo htmlspecialchars($account['category']); ?>" readonly class="readonly-field">
                    <div class="help-text">Primary account classification</div>
                </div>
                
                <div class="form-group">
                    <label for="subcategory">Subcategory</label>
                    <input type="text" id="subcategory" value="<?php echo htmlspecialchars($account['subcategory']); ?>" readonly class="readonly-field">
                    <div class="help-text">Secondary account classification</div>
                </div>
                
                <div class="form-group">
                    <label for="statement">Financial Statement</label>
                    <input type="text" id="statement" value="<?php echo htmlspecialchars($account['statement']); ?>" readonly class="readonly-field">
                    <div class="help-text">Which financial statement this account appears on</div>
                </div>
                
                <div class="form-group">
                    <label for="accountStatus">Account Status</label>
                    <input type="text" id="accountStatus" value="<?php echo $isActive ? 'Active' : 'Inactive'; ?>" readonly class="readonly-field">
                    <div class="help-text">Current status of this account</div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div class="form-column">
                <div class="form-group">
                    <label for="initialBalance">Initial Balance</label>
                    <input type="text" id="initialBalance" value="<?php echo formatMoney($account['initial_balance']); ?>" readonly class="readonly-field">
                    <div class="help-text">Starting balance when account was created</div>
                </div>
                
                <div class="form-group">
                    <label for="debitAmount">Total Debits</label>
                    <input type="text" id="debitAmount" value="<?php echo formatMoney($account['debit']); ?>" readonly class="readonly-field">
                    <div class="help-text">Total debit transactions</div>
                </div>
                
                <div class="form-group">
                    <label for="creditAmount">Total Credits</label>
                    <input type="text" id="creditAmount" value="<?php echo formatMoney($account['credit']); ?>" readonly class="readonly-field">
                    <div class="help-text">Total credit transactions</div>
                </div>
                
                <div class="form-group">
                    <label for="balance">Current Balance</label>
                    <input type="text" id="balance" value="<?php echo formatMoney($account['balance']); ?>" readonly class="readonly-field">
                    <div class="help-text">Current account balance</div>
                </div>
                
                <div class="form-group">
                    <label for="orderType">Order Type</label>
                    <input type="text" id="orderType" value="<?php echo htmlspecialchars($account['order_type']); ?>" readonly class="readonly-field">
                    <div class="help-text">Classification for financial statement ordering</div>
                </div>
                
                <div class="form-group">
                    <label for="createdAt">Created Date</label>
                    <input type="text" id="createdAt" value="<?php echo date('F j, Y g:i A', strtotime($account['created_at'])); ?>" readonly class="readonly-field">
                    <div class="help-text">When this account was created</div>
                </div>
                
                <div class="form-group">
                    <label for="comment">Comments</label>
                    <textarea id="comment" rows="4" readonly class="readonly-field"><?php echo htmlspecialchars($account['comment']); ?></textarea>
                    <div class="help-text">Additional notes about this account</div>
                </div>
            </div>
            
            <!-- Form Footer -->
            <div class="form-footer">
                <?php if ($canEditAccount): ?>
                    <a href="edit_account.php?account_number=<?php echo urlencode($account_number); ?>" 
                       class="action-btn edit-btn <?php echo $isActive ? '' : 'disabled-btn'; ?>"
                       <?php echo $isActive ? '' : 'onclick="return false;" title="Cannot edit inactive account"'; ?>>
                       ✏️ Edit Account
                    </a>
                <?php endif; ?>
                
                <a href="view_transactions.php?account_number=<?php echo urlencode($account_number); ?>" class="action-btn edit-btn">📋 View Transactions</a>
                
                <a type="button" onclick="openEmailModal()" class="action-btn edit-btn">
                    ✉️ Send Email
                </a>
                <br><br>
                
                <?php if ($canEditAccount): ?>
                    
                    <?php if ($isActive && !$hasBalance): ?>
                        <button type="button" onclick="confirmDeactivate()" class="action-btn deactivate-btn">
                            🚫 Deactivate Account
                        </button>
                    <?php else: ?>
                        <button type="button" class="action-btn deactivate-btn">
                            🚫 Cannot Deactivate: Balance Greater than 0
                        </button>
                    <?php endif; ?>
                    <?php if (!$isActive): ?>
                        <button type="button" onclick="confirmReactivate()" class="action-btn reactivate-btn">
                            ✅ Reactivate Account
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
                <br/>
                <br/>
                <a href="accounts_dashboard.php" class="action-btn back-btn">⬅️ Back to Accounts Dashboard</a>
            </div>
        </div>
    </form>
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
                           value="Account Information: <?php echo htmlspecialchars($account['account_number'] . ' - ' . $account['name']); ?>">
                </div>
                
                <div class="email-form-group">
                    <label for="emailContent">Message <span class="required-star">*</span></label>
                    <textarea id="emailContent" name="content" required 
                              placeholder="Enter your message here...">Hello,

I wanted to share information about the following account:

Account Number: <?php echo htmlspecialchars($account['account_number']); ?>
Account Name: <?php echo htmlspecialchars($account['name']); ?>
Category: <?php echo htmlspecialchars($account['category']); ?>
Current Balance: <?php echo formatMoney($account['balance']); ?>
Status: <?php echo $isActive ? 'Active' : 'Inactive'; ?>

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

<script>
<?php if ($canEditAccount): ?>
function confirmDeactivate() {
    const accountName = "<?php echo addslashes($account['name']); ?>";
    const accountNumber = "<?php echo addslashes($account['account_number']); ?>";
    const balance = "<?php echo formatMoney($account['balance']); ?>";
    
    const confirmMessage = `Are you sure you want to DEACTIVATE this account?\n\n` +
                          `Account: ${accountNumber} - ${accountName}\n` +
                          `Current Balance: ${balance}\n\n` +
                          `This will:\n` +
                          `• Hide the account from normal operations\n` +
                          `• Prevent new transactions from being added\n` +
                          `• Keep all historical data intact\n\n` +
                          `The account can be reactivated later if needed.`;
    
    if (confirm(confirmMessage)) {
        window.location.href = `deactivate_account.php?account_number=<?php echo urlencode($account_number); ?>&action=deactivate`;
    }
}

function confirmReactivate() {
    const accountName = "<?php echo addslashes($account['name']); ?>";
    const accountNumber = "<?php echo addslashes($account['account_number']); ?>";
    
    const confirmMessage = `Are you sure you want to REACTIVATE this account?\n\n` +
                          `Account: ${accountNumber} - ${accountName}\n\n` +
                          `This will restore the account to normal operations\n` +
                          `and allow new transactions to be added.`;
    
    if (confirm(confirmMessage)) {
        window.location.href = `deactivate_account.php?account_number=<?php echo urlencode($account_number); ?>&action=reactivate`;
    }
}
<?php endif; ?>

function printAccount() {
    window.print();
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

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEmailModal();
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
    formData.append('account_number', '<?php echo addslashes($account_number); ?>');
    
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
            document.getElementById('emailSubject').value = 'Account Information: <?php echo addslashes($account['account_number'] . ' - ' . $account['name']); ?>';
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

// Add keyboard shortcut for editing (Ctrl+E) - only if user has sufficient access level and account is active
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'e') {
        e.preventDefault();
        <?php if ($canEditAccount): ?>
            <?php if ($isActive): ?>
                window.location.href = 'edit_account.php?account_number=<?php echo urlencode($account_number); ?>';
            <?php else: ?>
                alert('Cannot edit inactive account. Please reactivate the account first.');
            <?php endif; ?>
        <?php else: ?>
            alert('Your access level (<?php echo $userAccessLevel; ?>) does not permit account editing. Contact an administrator.');
        <?php endif; ?>
    }
});
</script>
</body>
</html>