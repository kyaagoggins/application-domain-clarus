<?php
/**
 * New User Requests Management
 * This page shows pending user registration requests for admin approval
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>New User Requests</title>
</head>
<body>
    <div class="container" style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none; -ms-overflow-style: none;">
    <h2 class="logo" style="float:left"><img src="https://thumbs.dreamstime.com/b/calculator-icon-vector-isolated-white-background-your-web-mobile-app-design-calculator-logo-concept-calculator-icon-134617239.jpg" height="24px">
 <span>Clarus</span></h2>
    <?php echo'<img src="/uploads/profile_images/'.$userId.'.jpg" style="width:50px; float: right; border-radius: 50%; border: 3px solid black"><div style="clear:both"></div><span style="float: right">'.$username.'</span>';?>
    <div style="clear:both"></div>
    
    <?php
// Database configuration
include '../db_connect.php';

try {
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
    $pending_count = count(array_filter($requests, function($r) { return $r['approved'] == 0; }));
    $approved_count = count(array_filter($requests, function($r) { return $r['approved'] == 1; }));
    
} catch(PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>New User Requests Management</title>
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
        .pending { 
            color: #ff8c00; 
            font-weight: bold; 
        }
        .approved { 
            color: #28a745; 
            font-weight: bold; 
        }
        .action-btn {
            padding: 5px 10px;
            margin: 2px;
            border: none;
            border-radius: 3px;
            font-size: 12px;
            display: inline-block;
            text-align: center;
            min-width: 60px;
            cursor: pointer;
            text-decoration: none;
        }
        
        /* Navigation Button Styles */
        .nav-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            margin-left: 10px;
            color: white;
            font-weight: bold;
        }
        .nav-btn:first-child {
            margin-left: 0;
        }
        .nav-btn-back {
            background-color: #6c757d;
        }
        .nav-btn-back:hover {
            background-color: #545b62;
        }
        .nav-btn-refresh {
            background-color: #17a2b8;
        }
        .nav-btn-refresh:hover {
            background-color: #138496;
        }
        
        .approve-btn {
            background-color: #28a745;
            color: white;
        }
        .approve-btn:hover {
            background-color: #218838;
        }
        .reject-btn {
            background-color: #dc3545;
            color: white;
        }
        .reject-btn:hover {
            background-color: #c82333;
        }
        .actions-column {
            width: 180px;
            text-align: center;
        }
        .approved-row {
            opacity: 0.7;
            background-color: #f8fff9;
        }
        .old-request {
            background-color: #fff3cd;
        }
        
        /* Alert Styles */
        .alert {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 4px solid;
        }
        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
        .alert-warning {
            background-color: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
        }
    </style>
</head>
<body>
    <h1>New User Requests Management</h1>
    
    <?php if ($pending_count > 0): ?>
        <div class="alert alert-info">
            <strong>📋 Pending Requests:</strong> There are <?php echo $pending_count; ?> user registration requests awaiting your review.
        </div>
    <?php endif; ?>
    
    <?php 
    $old_requests = array_filter($requests, function($r) { return $r['days_waiting'] > 7 && $r['approved'] == 0; });
    if (count($old_requests) > 0): 
    ?>
        <div class="alert alert-warning">
            <strong>⏰ Attention:</strong> <?php echo count($old_requests); ?> request(s) have been waiting more than 7 days for approval.
        </div>
    <?php endif; ?>
    
    <div style="margin-top: 20px;">
        <button class="nav-btn nav-btn-back" onclick="goBackToUserManagement()" title="Back to User Management">
            ← Back to User Management
        </button>
        <!--<button class="nav-btn nav-btn-refresh" onclick="refreshPage()" title="Refresh Page">
            🔄 Refresh
        </button>-->
    </div>
    
    <?php if (empty($requests)): ?>
        <div class="alert alert-info">
            <strong>📭 No Requests Found</strong><br>
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
            <th>Days Waiting</th>
            <th class="actions-column">Actions</th>
        </tr>
        
        <?php foreach ($requests as $request): ?>
        <tr class="<?php echo $request['approved'] == 1 ? 'approved-row' : ''; ?><?php echo $request['days_waiting'] > 7 && $request['approved'] == 0 ? ' old-request' : ''; ?>">
            <td><?php echo htmlspecialchars($request['request_id']); ?></td>
            <td><?php echo htmlspecialchars($request['first_name']); ?></td>
            <td><?php echo htmlspecialchars($request['last_name']); ?></td>
            <td><?php echo htmlspecialchars($request['email']); ?></td>
            <td class="<?php echo strtolower($request['status_text']); ?>">
                <?php 
                if ($request['approved'] == 1) {
                    echo '✅ ' . $request['status_text'];
                } else {
                    echo '⏳ ' . $request['status_text'];
                }
                ?>
            </td>
            <td><?php echo date('M j, Y', strtotime($request['created_at'])); ?><br>
                <small><?php echo date('g:i A', strtotime($request['created_at'])); ?></small>
            </td>
            <td>
                <?php 
                if ($request['days_waiting'] == 0) {
                    echo 'Today';
                } else {
                    echo $request['days_waiting'] . ' day' . ($request['days_waiting'] > 1 ? 's' : '');
                }
                ?>
            </td>
            <td class="actions-column">
                <?php if ($request['approved'] == 0): ?>
                    <button class="action-btn approve-btn" 
                            onclick="approveRequest(<?php echo $request['request_id']; ?>, '<?php echo htmlspecialchars($request['first_name']); ?>','<?php echo $request['last_name']; ?>', '<?php echo htmlspecialchars($request['email']); ?>')"
                            title="Approve Request">
                        ✅ Approve
                    </button><br>
                    
                    <button class="action-btn reject-btn" 
                            onclick="rejectRequest(<?php echo $request['request_id']; ?>, '<?php echo htmlspecialchars($request['first_name']); ?>','<?php echo $request['last_name']; ?>', '<?php echo htmlspecialchars($request['email']); ?>')"
                            title="Reject Request">
                        ❌ Reject
                    </button>
                <?php else: ?>
                    <span style="color: #28a745; font-size: 12px;">
                        ✅ Already Approved
                    </span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <?php endif; ?>
    
    <p><strong>Total Requests:</strong> <?php echo count($requests); ?></p>
    <p><strong>Pending Approval:</strong> <?php echo $pending_count; ?></p>
    <p><strong>Approved:</strong> <?php echo $approved_count; ?></p>

    <script>
        // Navigation functions
        function goBackToUserManagement() {
            window.location.href = 'dashboard.php';
        }
        
        function refreshPage() {
            window.location.reload();
        }
        
        // Approve request function
        function approveRequest(requestId, firstName, lastName, email) {
            const confirmMessage = 'Are you sure you want to APPROVE the account request for:\n\n' + 
                                 'Name: ' + firstName + '\n' +
                                 'Email: ' + email + '\n\n' +
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
        
        // Reject request function
        function rejectRequest(requestId, firstName, lastName, email) {
            const confirmMessage = 'Are you sure you want to REJECT the account request for:\n\n' + 
                                 'Name: ' + firstName + '\n' +
                                 'Email: ' + email + '\n\n' +
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