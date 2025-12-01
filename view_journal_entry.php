<?php
//KSU student project for Clarus Accounting tool
//This page allows users to view a specific journal entry
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

$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];
$userAccessLevel = $_SESSION['access_level'];
$canApprove = ($userAccessLevel >= 1);

// Get entry_id from URL parameter
$entry_id = $_GET['entry_id'];

// Connect to the external database file
include '../db_connect.php';


$pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get journal entry details
$getEntry = $pdo->prepare("
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
$getEntry->execute([':entry_id' => $entry_id]);
$entry = $getEntry->fetch(PDO::FETCH_ASSOC);


// Get entry lines
$getEntryLines = $pdo->prepare("
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
$getEntryLines->execute([':entry_id' => $entry_id]);
$lines = $getEntryLines->fetchAll(PDO::FETCH_ASSOC);


function formatMoney($value)
{
    return '$' . number_format((float) $value, 2);
}

// testing capturing documents
$sourceDocuments = json_decode($entry['source_documents'], true) ?: [];
include 'header.php';
?>

<div class="container" style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none;">

    <div class="entry-header">
        <div class="entry-id-badge">
            #JE-<?php echo $entry_id; ?>
        </div>
        <span class="status-badge status-<?php echo $entry['status']; ?>">
            <?php
            $statusLabels = [
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                'posted' => 'Posted'
            ];
            echo $statusLabels[$entry['status']];
            ?>
        </span>
        <h1><?php echo $entry['description']; ?></h1>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons" style="margin:auto">
        <div class="route-buttons">
            <button onclick="window.history.back()" class="btn btn-back"
                style="width:20%; margin-right:1%">Back</button>
            <a href="view_journal_entries.php" class="btn btn-back"
                style="width:20%; margin-top:1%; margin-right:1%;">All Entries</a>
            <a href="account_ledger.php?account_number=<?php echo $entry['account_id']; ?>" class="btn btn-back"
                style="width:20%; margin-top:1%;">View Ledger</a>
        </div>

        <?php if ($canApprove && $entry['status'] == 'pending'): ?>
            <button class="btn btn-approve" onclick="approveEntry(<?php echo $entry_id; ?>)">Approve Entry <i
                    class="fa-solid fa-square-check"></i></button>
            <button class="btn btn-reject" onclick="openRejectModal(<?php echo $entry_id; ?>)">Reject Entry <i
                    class="fa-solid fa-xmark"></i></button>
        <?php endif; ?>
    </div>

    <?php if ($entry['status'] == 'rejected' && $entry['notes']): ?>
        <!-- Rejection Notice -->
        <div class="rejection-box">
            <h3>This entry has been rejected</h3>
            <div class="rejection-reason">
                <strong>Reason:</strong><br>
                <?php echo $entry['notes']; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Entry Information -->
    <div class="info-grid">
        <div class="info-card">
            <div class="info-label">Entry Date</div>
            <div class="info-value"><?php echo date('F j, Y', strtotime($entry['entry_date'])); ?></div>
        </div>

        <div class="info-card">
            <div class="info-label">Related Account</div>
            <div class="info-value">
                <a href="view_account.php?account_number=<?php echo urlencode($entry['account_id']); ?>"
                    class="account-link">
                    <?php echo $entry['account_id']; ?> -
                    <?php echo $entry['account_name']; ?>
                </a>
            </div>
        </div>

        <?php if ($entry['reference_number']): ?>
            <div class="info-card">
                <div class="info-label">Reference Number</div>
                <div class="info-value"><?php echo $entry['reference_number']; ?></div>
            </div>
        <?php endif; ?>

        <div class="info-card">
            <div class="info-label">Total Amount</div>
            <div class="info-value" style="color: #2980b9; font-weight: bold;">
                <?php echo formatMoney($entry['total_debit']); ?>
            </div>
        </div>

        <div class="info-card">
            <div class="info-label">Created By</div>
            <div class="info-value">
                <?php echo $entry['created_by_name']; ?>
                <?php if ($entry['creator_first_name'] || $entry['creator_last_name']): ?>
                    <br><small>(<?php echo $entry['creator_first_name'] . ' ' . $entry['creator_last_name']; ?>)</small>
                <?php endif; ?>
            </div>
        </div>

        <div class="info-card">
            <div class="info-label">Created On</div>
            <div class="info-value"> <?php echo date('M j, Y g:i A', strtotime($entry['created_at'])); ?></div>
        </div>

        <?php if ($entry['approved_by']): ?>
            <div class="info-card">
                <div class="info-label"><?php echo $entry['status'] == 'rejected' ? 'Rejected By' : 'Approved By'; ?></div>
                <div class="info-value">
                    <?php echo $entry['approved_by_name']; ?>
                </div>
            </div>

            <div class="info-card">
                <div class="info-label"><?php echo $entry['status'] == 'rejected' ? 'Rejected On' : 'Approved On'; ?></div>
                <div class="info-value"> <?php echo date('M j, Y g:i A', strtotime($entry['approved_at'])); ?></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Journal Entry Lines -->
    <div class="section-container">
        <h2 class="section-title"> Journal Entry Lines</h2>

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
                                <?php echo $line['account_number']; ?> -
                                <?php echo $line['account_name']; ?>
                            </a>
                            <div class="account-category">
                                <?php echo $line['category']; ?> •
                                <?php echo $line['normal_side']; ?> Side
                            </div>
                        </td>
                        <td><?php echo $line['line_description']; ?></td>
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

        <div
            style="margin-top: 15px; padding: 15px; background-color: #d4edda; border-radius: 4px; border: 1px solid #c3e6cb;">
            <strong style="color: #155724;">Entry is Balanced:</strong>
            <span style="color: #155724;">
                Debits (<?php echo formatMoney($entry['total_debit']); ?>) =
                Credits (<?php echo formatMoney($entry['total_credit']); ?>)
            </span>
        </div>
    </div>

    <?php if (!empty($sourceDocuments)): ?>
        <!-- Source Documents -->
        <div class="section-container">
            <h2 class="section-title">Source Documents (<?php echo count($sourceDocuments); ?>)</h2>

            <div class="documents-grid">
                <?php foreach ($sourceDocuments as $doc):
                    $extension = strtolower(pathinfo($doc, PATHINFO_EXTENSION));
                    $icon = '<i class="fa-solid fa-file"></i>';

                    if (in_array($extension, ['pdf']))
                        $icon = '<i class="fa-solid fa-file-pdf"></i>';
                    elseif (in_array($extension, ['doc', 'docx']))
                        $icon = '<i class="fa-solid fa-file-word"></i>';
                    elseif (in_array($extension, ['xls', 'xlsx']))
                        $icon = '<i class="fa-solid fa-file-excel"></i>';
                    elseif (in_array($extension, ['jpg', 'jpeg', 'png']))
                        $icon = '<i class="fa-solid fa-file-image"></i>';
                    elseif (in_array($extension, ['csv']))
                        $icon = '<i class="fa-solid fa-file-csv"></i>';
                    ?>
                    <div class="document-card">
                        <div class="document-icon"><?php echo $icon; ?></div>
                        <div class="document-name"><?php echo basename($doc); ?></div>
                        <a href="../uploads/journal_documents/<?php echo $doc; ?>" target="_blank"
                            class="document-link">Download</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Rejection Modal (same as in view_journal_entries.php) -->
<div id="rejectModal" class="modal"
    style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
    <div
        style="background-color: #fefefe; margin: 5% auto; padding: 0; border: 1px solid #888; border-radius: 8px; width: 90%; max-width: 500px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
        <div
            style="background: linear-gradient(135deg, #dc3545, #c82333); color: white; padding: 20px; border-radius: 8px 8px 0 0;">
            <span onclick="closeRejectModal()"
                style="color: white; float: right; font-size: 28px; font-weight: bold; cursor: pointer; line-height: 20px;">&times;</span>
            <h2 style="margin: 0; font-size: 1.5em;">Reject Journal Entry</h2>
        </div>
        <div style="padding: 20px;">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Rejection Reason <span
                        style="color: red;">*</span></label>
                <textarea id="rejectionReason"
                    placeholder="Please provide a detailed reason for rejecting this journal entry..."
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box; min-height: 100px; resize: vertical;"></textarea>
            </div>
        </div>
        <div style="background-color: #f8f9fa; padding: 15px 20px; border-radius: 0 0 8px 8px; text-align: right;">
            <button type="button" onclick="closeRejectModal()"
                style="background-color: #6c757d; color: white; padding: 10px 25px; border: none; border-radius: 4px; font-size: 14px; cursor: pointer;">Cancel</button>
            <button type="button" onclick="submitRejection()"
                style="background-color: #dc3545; color: white; padding: 10px 25px; border: none; border-radius: 4px; font-size: 14px; cursor: pointer; margin-left: 10px;">Submit
                Rejection</button>
        </div>
    </div>
</div>

<script>
    //functionality for approval of journal entry
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

    //modal functionality handling 
    function openRejectModal(entryId) {
        window.currentRejectEntryId = entryId;
        document.getElementById('rejectionReason').value = '';
        document.getElementById('rejectModal').style.display = 'block';
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').style.display = 'none';
        window.currentRejectEntryId = null;
    }

    //rejection reasoning submission and content 
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
</script>
</body>

</html>