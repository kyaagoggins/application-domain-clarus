<?php
//KSU student project for Clarus Accounting tool
//This page is used to view the details of an account
//Initially drafted by Eric Poole. Reviewed and updated by Kyaa Goggins
//This page is where the user can view account details that cannot be found in the account ledger.

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
$canEditAccount = ($userAccessLevel >= 3);
$account_number = $_GET['account_number'];
include '../db_connect.php';

// Fetch account details from database
$pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$getAccount = $pdo->prepare("SELECT * FROM accounts WHERE account_number = :account_number");
$getAccount->execute([':account_number' => $account_number]);
$account = $getAccount->fetch(PDO::FETCH_ASSOC);

if (!$account) {
    die("Hmm.. something went wrong. There is no account found for the provided account number. Please go back and try again.");
}

// Fetch all users for email dropdown
$stmt = $pdo->query("SELECT user_id, username, first_name, last_name, email FROM users WHERE access_level > 1 ORDER BY username");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Format monetary values for display
function formatMoney($value)
{
    return '$' . number_format((float) $value, 2);
}

//account specific information for active and balance info
$isActive = isset($account['is_active']) ? (int) $account['is_active'] : 1;
$accountBalance = (float) $account['balance'];
$hasBalance = ($accountBalance != 0);

include 'header.php';
?>

<link rel="stylesheet" href="/styling/view_account.css">
<div class="container" style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none;">
    <div class="account-header <?php echo $isActive ? '' : 'inactive'; ?>">
        <div class="account-name"><?php echo $account['name']; ?></div>
        <div class="status-badge <?php echo $isActive ? 'status-active' : 'status-inactive'; ?>">
            <?php echo $isActive ? 'ACTIVE' : 'INACTIVE'; ?>
        </div>
        <div
            class="account-balance <?php echo (float) $account['balance'] >= 0 ? 'balance-positive' : 'balance-negative'; ?>">
            Balance: <?php echo formatMoney($account['balance']); ?>
        </div>
    </div>

    <h1>Account Details</h1>

    <form>
        <div class="form-container">
            <div class="form-column">
                <div class="form-group">
                    <label for="accountNumber">Account Number</label>
                    <input type="text" id="accountNumber" value="<?php echo $account['account_number']; ?>" readonly
                        class="readonly-field">
                    <div class="help-text">Unique account identifier</div>
                </div>

                <div class="form-group">
                    <label for="accountName">Account Name</label>
                    <input type="text" id="accountName" value="<?php echo $account['name']; ?>" readonly
                        class="readonly-field">
                    <div class="help-text">Display name for this account</div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" rows="3" readonly
                        class="readonly-field"><?php echo $account['description']; ?></textarea>
                    <div class="help-text">Detailed description of the account</div>
                </div>

                <div class="form-group">
                    <label for="normalSide">Normal Side</label>
                    <input type="text" id="normalSide" value="<?php echo $account['normal_side']; ?>" readonly
                        class="readonly-field">
                    <div class="help-text">Debit or Credit normal balance side</div>
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <input type="text" id="category" value="<?php echo $account['category']; ?>" readonly
                        class="readonly-field">
                    <div class="help-text">Primary account classification</div>
                </div>

                <div class="form-group">
                    <label for="subcategory">Subcategory</label>
                    <input type="text" id="subcategory" value="<?php echo $account['subcategory']; ?>" readonly
                        class="readonly-field">
                    <div class="help-text">Secondary account classification</div>
                </div>

                <div class="form-group">
                    <label for="statement">Financial Statement</label>
                    <input type="text" id="statement" value="<?php echo $account['statement']; ?>" readonly
                        class="readonly-field">
                    <div class="help-text">Which financial statement this account appears on</div>
                </div>

                <div class="form-group">
                    <label for="accountStatus">Account Status</label>
                    <input type="text" id="accountStatus" value="<?php echo $isActive ? 'Active' : 'Inactive'; ?>"
                        readonly class="readonly-field">
                    <div class="help-text">Current status of this account</div>
                </div>
            </div>

            <div class="form-column">
                <div class="form-group">
                    <label for="initialBalance">Initial Balance</label>
                    <input type="text" id="initialBalance"
                        value="<?php echo formatMoney($account['initial_balance']); ?>" readonly class="readonly-field">
                    <div class="help-text">Starting balance when account was created</div>
                </div>

                <div class="form-group">
                    <label for="debitAmount">Total Debits</label>
                    <input type="text" id="debitAmount" value="<?php echo formatMoney($account['debit']); ?>" readonly
                        class="readonly-field">
                    <div class="help-text">Total debit transactions</div>
                </div>

                <div class="form-group">
                    <label for="creditAmount">Total Credits</label>
                    <input type="text" id="creditAmount" value="<?php echo formatMoney($account['credit']); ?>" readonly
                        class="readonly-field">
                    <div class="help-text">Total credit transactions</div>
                </div>

                <div class="form-group">
                    <label for="balance">Current Balance</label>
                    <input type="text" id="balance" value="<?php echo formatMoney($account['balance']); ?>" readonly
                        class="readonly-field">
                    <div class="help-text">Current account balance</div>
                </div>

                <div class="form-group">
                    <label for="orderType">Order Type</label>
                    <input type="text" id="orderType" value="<?php echo $account['order_type']; ?>" readonly
                        class="readonly-field">
                    <div class="help-text">Classification for financial statement ordering</div>
                </div>

                <div class="form-group">
                    <label for="createdAt">Created Date</label>
                    <input type="text" id="createdAt"
                        value="<?php echo date('F j, Y g:i A', strtotime($account['created_at'])); ?>" readonly
                        class="readonly-field">
                    <div class="help-text">When this account was created</div>
                </div>

                <div class="form-group">
                    <label for="comment">Comments</label>
                    <textarea id="comment" rows="4" readonly
                        class="readonly-field"><?php echo $account['comment']; ?></textarea>
                    <div class="help-text">Additional notes about this account</div>
                </div>
            </div>

            <div class="form-footer">
                <?php if ($canEditAccount): ?>
                    <a href="edit_account.php?account_number=<?php echo urlencode($account_number); ?>"
                        class="action-btn edit-btn <?php echo $isActive ? '' : 'disabled-btn'; ?>" <?php echo $isActive ? '' : 'onclick="return false;" title="Cannot edit inactive account"'; ?>>
                        Edit Account <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                <?php endif; ?>

                <a href="view_transactions.php?account_number=<?php echo $account_number; ?>"
                    class="action-btn edit-btn">View Transactions <i class="fa-solid fa-receipt"></i></a>

                <a type="button" onclick="openEmailModal()" class="action-btn edit-btn">
                    Send Email <i class="fa-solid fa-envelope-open-text"></i></a>
                <br>
                <br>
                <?php if ($canEditAccount): ?>

                    <?php if ($isActive && !$hasBalance): ?>
                        <button type="button" onclick="confirmDeactivate()" class="action-btn deactivate-btn">
                            Deactivate Account
                        </button>
                    <?php else: ?>
                        <button type="button" class="action-btn deactivate-btn">
                            Cannot Deactivate: Balance Greater than 0
                        </button>
                    <?php endif; ?>
                    <?php if (!$isActive): ?>
                        <button type="button" onclick="confirmReactivate()" class="action-btn reactivate-btn">
                            Reactivate Account
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
                <br />
                <br />
                <a href="accounts_dashboard.php" class="action-btn back-btn"><i class="fa-solid fa-arrow-left"></i> Back
                    to Accounts Dashboard</a>
            </div>
        </div>
    </form>
</div>

<!-- Email Modal -->
<div id="emailModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="closeEmailModal()">&times;</span>
            <h2> Send Email to User</h2>
        </div>
        <div class="modal-body">
            <form id="emailForm" onsubmit="return sendEmail(event)">
                <div class="email-form-group">
                    <label for="recipientUser">Send To <span class="required-star">*</span></label>
                    <select id="recipientUser" name="recipient_user" required>
                        <option value="">Select a user...</option>
                        <?php foreach ($users as $user): ?>
                            <?php if ($user['user_id'] != $userId): // Disable abiliy for the logged in user to email his/herself ?>
                                <option value="<?php echo $user['user_id']; ?>" data-email="<?php echo $user['email']; ?>">
                                    <?php echo $user['username']; ?>
                                    <?php if (!empty($user['first_name']) || !empty($user['last_name'])): ?>
                                        (<?php echo $user['first_name'] . ' ' . $user['last_name']; ?>)
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
                        value="Account Information: <?php echo $account['account_number'] . ' - ' . $account['name']; ?>">
                </div>

                <div class="email-form-group">
                    <label for="emailContent">Message <span class="required-star">*</span></label>
                    <textarea id="emailContent" name="content" required placeholder="Enter your message here...">Hello,

I wanted to share information about the following account:

Account Number: <?php echo $account['account_number']; ?>
Account Name: <?php echo $account['name']; ?>
Category: <?php echo $account['category']; ?>
Current Balance: <?php echo formatMoney($account['balance']); ?>
Status: <?php echo $isActive ? 'Active' : 'Inactive'; ?>

Please review and let me know if you have any questions.

</textarea>
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

    //function to verify deactivation of financial account 
    function confirmDeactivate() {
        const accountName = "<?php echo addslashes($account['name']); ?>";
        const accountNumber = "<?php echo addslashes($account['account_number']); ?>";
        const balance = "<?php echo formatMoney($account['balance']); ?>";

        //confirmation message formatting 
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

    //function for reactivation of financial account 
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

    //email modal handling functionality 
    function openEmailModal() {
        document.getElementById('emailModal').style.display = 'block';
    }

    function closeEmailModal() {
        document.getElementById('emailModal').style.display = 'none';
    }

    //function to send email to a selected user about the viewed account
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

        //form data inputted from user 
        const formData = new FormData();
        formData.append('recipient_user_id', recipientUserId);
        formData.append('recipient_email', recipientEmail);
        formData.append('subject', subject);
        formData.append('content', content);
        formData.append('account_number', '<?php echo addslashes($account_number); ?>');

        fetch('send_email_from_account.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Email sent successfully!');
                    closeEmailModal();
                    document.getElementById('emailForm').reset();
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
</script>
</body>

</html>