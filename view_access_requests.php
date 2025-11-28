<?php
//KSU student project for Clarus Accounting tool
//This page is used by admins to view and approve or reject access requests to the system by outside users
//Initially drafted by Eric Poole. Reviewed and updated by Kiana Knight

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

include 'header.php';
?>

<link rel="stylesheet" href="/styling/view_access_requests.css">
<div class="container" style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none;">
    <?php
    // Connect to the external database config file
    include '../db_connect.php';


    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get all new user requests
    $stmt = $pdo->query("
        SELECT 
            request_id,
            first_name,
            last_name,
            email,
            approved,
            created_at,
            updated_at,
            CASE 
                WHEN approved = 1 THEN 'Approved'
                WHEN approved = 0 THEN 'Pending'
                ELSE 'Unknown'
            END AS status_text,
            DATEDIFF(NOW(), created_at) AS days_waiting
        FROM `new-user-requests` 
        ORDER BY created_at DESC
    ");

    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get count statistics
    $pending_count = count(array_filter($requests, function ($r) {
        return $r['approved'] == 0;
    }));
    $approved_count = count(array_filter($requests, function ($r) {
        return $r['approved'] == 1;
    }));

    ?>
    <h1>New User Requests Management</h1>

    <?php if ($pending_count > 0): ?>
        <div class="alert alert-info">
            <strong>Pending Requests:</strong> There are <?php echo $pending_count; ?> user registration requests
            awaiting your review.
        </div>
    <?php endif; ?>
    <div style="margin-top: 20px;">
        <button class="nav-btn nav-btn-back" onclick="window.location.href = 'dashboard.php';"
            title="Back to User Management">
            ← Back to User Management
        </button>
        <!--<button class="nav-btn nav-btn-refresh" onclick="refreshPage()" title="Refresh Page">
            Refresh
        </button>-->
    </div>

    <?php if (empty($requests)): ?>
        <div class="alert alert-info">
            <strong>No Requests Found</strong><br>
            There are currently no user registration requests in the system.
        </div>
    <?php else: ?>

        <table>
            <tr>
                <th>Request ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email Address</th>
                <th>Status</th>
                <th>Request Date</th>
                <th class="actions-column">Actions</th>
            </tr>

            <?php foreach ($requests as $request): ?>
                <tr>
                    <td><?php echo $request['request_id']; ?></td>
                    <td><?php echo $request['first_name']; ?></td>
                    <td><?php echo $request['last_name']; ?></td>
                    <td><?php echo $request['email']; ?></td>
                    <td class="<?php echo strtolower($request['status_text']); ?>">
                        <?php
                        if ($request['approved'] == 1) {
                            echo $request['status_text'];
                        } else {
                            echo $request['status_text'];
                        }
                        ?>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($request['created_at'])); ?></td>

                    <td class="actions-column">
                        <?php if ($request['approved'] == 0): ?>
                            <button class="action-btn approve-btn"
                                onclick="approveRequest(<?php echo $request['request_id']; ?>, '<?php echo htmlspecialchars($request['first_name']); ?>','<?php echo $request['last_name']; ?>', '<?php echo htmlspecialchars($request['email']); ?>')"
                                title="Approve Request">
                                Approve <i class="fa-solid fa-square-check"></i>
                            </button><br>

                            <button class="action-btn reject-btn"
                                onclick="rejectRequest(<?php echo $request['request_id']; ?>, '<?php echo htmlspecialchars($request['first_name']); ?>','<?php echo $request['last_name']; ?>', '<?php echo htmlspecialchars($request['email']); ?>')"
                                title="Reject Request">
                                Reject <i class="fa-solid fa-xmark"></i>
                            </button>
                        <?php else: ?>
                            <span style="color: #28a745; font-size: 12px;">
                                Already Approved
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

    <?php endif; ?>


    <script>

        // this function approves a user request and converts the request to an actual user
        function approveRequest(requestId, firstName, lastName, email) {
            const confirmMessage = 'Are you sure you want to APPROVE the account request for:\n\n' +
                'Name: ' + firstName + '\n' +
                'This will create a new user account and send a welcome email.';

            if (confirm(confirmMessage)) {
                // Create form to submit approval
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'process_user_request.php';

                const requestIdInput = document.createElement('input');
                requestIdInput.type = 'hidden';
                requestIdInput.name = 'request_id';
                requestIdInput.value = requestId;

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'approve';

                const firstNameInput = document.createElement('input');
                firstNameInput.type = 'hidden';
                firstNameInput.name = 'first_name';
                firstNameInput.value = firstName;

                const lastNameInput = document.createElement('input');
                lastNameInput.type = 'hidden';
                lastNameInput.name = 'last_name';
                lastNameInput.value = lastName;

                form.appendChild(requestIdInput);
                form.appendChild(firstNameInput);
                form.appendChild(lastNameInput);
                form.appendChild(actionInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // this function rejects the user request
        function rejectRequest(requestId, firstName, lastName, email) {
            const confirmMessage = 'Are you sure you want to REJECT the account request for:\n\n' +
                'Name: ' + firstName + '\n' +
                'This will mark the request as rejected and send a notification email.';

            if (confirm(confirmMessage)) {
                // Create form to submit rejection
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'process_user_request.php';

                const requestIdInput = document.createElement('input');
                requestIdInput.type = 'hidden';
                requestIdInput.name = 'request_id';
                requestIdInput.value = requestId;

                const firstNameInput = document.createElement('input');
                firstNameInput.type = 'hidden';
                firstNameInput.name = 'first_name';
                firstNameInput.value = firstName;

                const lastNameInput = document.createElement('input');
                lastNameInput.type = 'hidden';
                lastNameInput.name = 'last_name';
                lastNameInput.value = lastName;

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'reject';

                form.appendChild(requestIdInput);
                form.appendChild(firstNameInput);
                form.appendChild(lastNameInput);
                form.appendChild(actionInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</div>
</body>

</html>