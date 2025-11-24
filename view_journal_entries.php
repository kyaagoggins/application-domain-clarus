<?php
/**
 * KSU student project for Clarus Accounting tool
 * View Journal Entries
 * This page displays all journal entries with filtering and status management
 * This page serves as the dashboard to view all journal entries in the application
 * Initially drafted by Eric Poole; Reviewed and updated by Kyaa Goggins
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

//session variables 
$username = $_SESSION['username'] ?? 'User';
$userId = $_SESSION['user_id'];
$userAccessLevel = isset($_SESSION['access_level']) ? (int) $_SESSION['access_level'] : 0;
$canApprove = ($userAccessLevel > 1); // Only managers and above can approve
//$canApprove = true;
// Get account_id from URL parameter (optional)
$account_id = isset($_GET['account_id']) ? trim($_GET['account_id']) : null;

include 'header.php';
?>

<link rel="stylesheet" href="/styling/view_journal_entries.css">
<div class="container"
    style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none; -ms-overflow-style: none;">

    <?php
    include '../db_connect.php';

    try {
        //initialize database connections
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
        $getAllEntries = $pdo->prepare("
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

        $getAllEntries->execute($params);
        $entries = $getAllEntries->fetchAll(PDO::FETCH_ASSOC);

        // Get lines for each entry
        foreach ($entries as &$entry) {
            $entryInfo = $pdo->prepare("
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
            $entryInfo->execute([':entry_id' => $entry['entry_id']]);
            $entry['lines'] = $entryInfo->fetchAll(PDO::FETCH_ASSOC);
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

    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }

    function formatMoney($value)
    {
        return '$' . number_format((float) $value, 2);
    }
    ?>

    <h1>Journal Entries</h1>

    <?php if ($account_id): ?>
        <div style="background: #e7f3ff; padding: 10px; border-left: 4px solid #2980b9; margin-bottom: 20px;">
            <strong>Filtered by Account:</strong> <?php echo ($account_id); ?>
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
            <div class="filter-group form-group">
                <label class="form-label"><i class="fa-brands fa-searchengin"></i> Search</label>
                <input class="form-control" type="text" id="searchFilter" placeholder="Account name, amount, or date..."
                    onkeyup="filterEntries()">
            </div>

            <div class="filter-group form-group">
                <label class="form-label">Date From</label>
                <input class="form-control" type="date" id="dateFromFilter" onchange="filterEntries()">
            </div>

            <div class="filter-group form-group">
                <label class="form-label">Date To</label>
                <input class="form-control" type="date" id="dateToFilter" onchange="filterEntries()">
            </div>

            <div class="filter-group form-group">
                <label class="form-label" style="margin-bottom: 15px">Status</label>
                <select class="form-control" id="statusFilterSelect" onchange="filterEntries()">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="posted">Posted</option>
                </select>
            </div>

            <div class="filter-group form-group">
                <label>&nbsp;</label>
                <button class="filter-btn" onclick="clearFilters()">Clear Filters</button>
            </div>
        </div>
    </div>

    <!-- Journal Entries by Status -->
    <?php
    $statusLabels = [
        'pending' => '<i class="fa-solid fa-hourglass-half"></i> Pending Approval',
        'approved' => '<i class="fa-solid fa-square-check"></i> Approved',
        'rejected' => '<i class="fa-solid fa-xmark"></i> Rejected',
        'posted' => '<i class="fa-solid fa-file"></i> Posted to Ledger'
    ];

    foreach ($entriesByStatus as $status => $statusEntries):
        if (empty($statusEntries))
            //skip entries that could be empty to prevent errors
            continue;
        ?>

        <div class="status-section" id="section-<?php echo $status; ?>" data-status="<?php echo $status; ?>">
            <div class="status-header <?php echo $status; ?>" onclick="toggleSection('<?php echo $status; ?>')">
                <span><?php echo $statusLabels[$status]; ?> (<?php echo count($statusEntries); ?>)</span>
                <span class="status-toggle">▼</span>
            </div>

            <div class="status-content">
                <?php foreach ($statusEntries as $entry): ?>
                    <div class="journal-entry" data-entry-id="<?php echo $entry['entry_id']; ?>"
                        data-entry-date="<?php echo $entry['entry_date']; ?>" data-status="<?php echo $entry['status']; ?>">

                        <div class="entry-header">
                            <div class="entry-id">
                                #JE-<?php echo str_pad($entry['entry_id'], 6, '0', STR_PAD_LEFT); ?>
                            </div>

                            <div class="entry-info">
                                <div class="entry-description">
                                    <?php echo ($entry['description']); ?>
                                </div>
                                <div class="entry-meta">
                                    <i class="fa-solid fa-calendar"></i>
                                    <?php echo date('M j, Y', strtotime($entry['entry_date'])); ?>
                                    |
                                    <i class="fa-solid fa-user"></i> Created by
                                    <?php echo ($entry['created_by_name']); ?> |
                                    <i class="fa-solid fa-landmark"></i> Account:
                                    <?php echo ($entry['account_id']); ?> -
                                    <?php echo ($entry['account_name']); ?>
                                    <?php if ($entry['reference_number']): ?>
                                        | <i class="fa-solid fa-tag"></i> Ref:
                                        <?php echo ($entry['reference_number']); ?>
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
                                        data-account-name="<?php echo strtolower(($line['account_name'])); ?>"
                                        data-debit="<?php echo $line['debit_amount']; ?>"
                                        data-credit="<?php echo $line['credit_amount']; ?>">
                                        <td>
                                            <a href="account_ledger.php?account_number=<?php echo urlencode($line['account_number']); ?>"
                                                class="account-link">
                                                <?php echo ($line['account_number']); ?> -
                                                <?php echo ($line['account_name']); ?>
                                            </a>
                                            <div style="font-size: 11px; color: #666;">
                                                <?php echo ($line['category']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo ($line['line_description']); ?></td>
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
                                <?php echo nl2br(($entry['notes'])); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Actions -->
                        <div class="entry-actions">
                            <?php if ($canApprove && $entry['status'] == 'pending'): ?>
                                <button class="action-btn btn-approve" onclick="approveEntry(<?php echo $entry['entry_id']; ?>)">
                                    <i class="fa-solid fa-square-check"></i> Approve
                                </button>
                                <button class="action-btn btn-reject" onclick="openRejectModal(<?php echo $entry['entry_id']; ?>)">
                                    <i class="fa-solid fa-xmark"></i> Reject
                                </button>
                            <?php endif; ?>

                            <?php
                            $documents = json_decode($entry['source_documents'], true);
                            if ($documents && count($documents) > 0):
                                ?>
                                <button class="action-btn btn-view-docs"
                                    onclick="viewDocuments(<?php echo $entry['entry_id']; ?>, <?php echo (json_encode($documents)); ?>)">
                                    <i class="fa-solid fa-paperclip"></i> View Documents (<?php echo count($documents); ?>)
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
            <a href="create_journal_entry.php?account_id=<?php echo ($account_id ?: ''); ?>"
                style="display: inline-block; margin-top: 15px; padding: 10px 20px; background-color: #2980b9; color: white; text-decoration: none; border-radius: 4px;">
                <i class="fa-solid fa-circle-plus"></i> Create Journal Entry
            </a>
        </div>
    <?php endif; ?>

</div>

<!-- Rejection Modal -->
<div id="rejectModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="closeRejectModal()">&times;</span>
            <h2><i class="fa-solid fa-xmark"></i> Reject Journal Entry</h2>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>Rejection Reason <span style="color: red;">*</span></label>
                <textarea id="rejectionReason"
                    placeholder="Please provide a detailed reason for rejecting this journal entry..."></textarea>
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

    //function to filter all journal entries 
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

    //clear form filters 
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

    //this function corresponds to the approval workflow of a journal entry but users with the correct permissions
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
                    alert('Yay! Journal entry approved successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to approve journal entry'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('On no! An error occurred while approving the journal entry.');
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

    //function for entry rejection workflow for users with the correct permissions
    function submitRejection() {
        const reason = document.getElementById('rejectionReason').value.trim();

        if (!reason) {
            alert('Wait! Please provide a rejection reason.');
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
                    alert('Yay! Journal entry rejected successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to reject journal entry'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Oh no! An error occurred while rejecting the journal entry.');
            });
    }

    //view uploaded documents within a specific journal entry
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
    window.onclick = function (event) {
        const modal = document.getElementById('rejectModal');
        if (event.target == modal) {
            closeRejectModal();
        }
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeRejectModal();
        }
    });
</script>
</body>

</html>