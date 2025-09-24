<?php
/**
 * View Dashboard of All Users
 * This page is shown to admins to review the details of all users in the system
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

// If accessed directly and profile is already complete, redirect to home
// In real implementation, you'd check the database here
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Dashboard</title>
</head>
<body>
    <div class="container" style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none; -ms-overflow-style: none;">
    <!--<img src="https://thumbs.dreamstime.com/b/calculator-icon-vector-isolated-white-background-your-web-mobile-app-design-calculator-logo-concept-calculator-icon-134617239.jpg" width="100px">-->
    <h2 class="logo" style="float:left"><img src="https://thumbs.dreamstime.com/b/calculator-icon-vector-isolated-white-background-your-web-mobile-app-design-calculator-logo-concept-calculator-icon-134617239.jpg" height="24px">
 <span>Clarus</span></h2>
    <?php echo'<img src="/uploads/profile_images/'.$userId.'.jpg" style="width:50px; float: right; border-radius: 50%; border: 3px solid black"><div style="clear:both"></div><span style="float: right">'.$username.'</span>';?>
    <div style="clear:both"></div>
    
    <?php
    echo $_SESSION['password_message'];
    include '../db_connect.php';

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all users with password expiration status
    $stmt = $pdo->query("
        SELECT 
            user_id,
            username,
            first_name, 
            last_name, 
            email,
            access_level,
            last_password_reset_datetime,
            active,
            suspension_remove_date,
            CASE 
                WHEN last_password_reset_datetime IS NULL THEN 'Never Reset'
                WHEN DATEDIFF(NOW(), last_password_reset_datetime) > 30 THEN 'Expired'
                ELSE 'Valid'
            END AS password_status
        FROM users 
        ORDER BY last_name, first_name
    ");
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Management</title>
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
        .expired { 
            color: red; 
            font-weight: bold; 
        }
        .valid { 
            color: green; 
            font-weight: bold; 
        }
        .never { 
            color: orange; 
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
        .nav-btn-add {
            background-color: #4CAF50;
        }
        .nav-btn-add:hover {
            background-color: #45a049;
        }
        .nav-btn-expired {
            background-color: #2196F3;
        }
        .nav-btn-expired:hover {
            background-color: #1976D2;
        }
        
        .edit-btn {
            
            color: white;
        }
        .edit-btn:hover {
            
        }
        .email-btn {
            
            color: white;
        }
        .email-btn:hover {
            
        }
        .deactivate-btn {
            
            color: white;
        }
        .deactivate-btn:hover {
            
        }
        .activate-btn {
            
            color: white;
        }
        .activate-btn:hover {
            
        }
        .suspend-btn {
            
            color: white;
        }
        .suspend-btn:hover {
            
        }
        .actions-column {
            width: 250px;
            text-align: center;
        }
        .inactive-row {
            opacity: 0.6;
            background-color: #f5f5f5;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            border-radius: 5px;
            width: 400px;
            max-width: 90%;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover,
        .close:focus {
            color: black;
        }
        .modal h2 {
            color: #FF5722;
            margin-top: 0;
        }
        .modal-form {
            margin: 20px 0;
        }
        .modal-form label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .modal-form input[type="date"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 14px;
        }
        .modal-buttons {
            text-align: right;
            margin-top: 20px;
        }
        .modal-btn {
            padding: 10px 20px;
            margin-left: 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
        }
        .modal-btn-confirm {
            background-color: #FF5722;
            color: white;
        }
        .modal-btn-cancel {
            background-color: #666;
            color: white;
        }
    </style>
</head>
<body>
    <h1>User Management Dashboard</h1>
    <div style="margin-top: 20px;">
        <button style="width:300px" onclick="addNewUser()" title="Add New User">
            ➕ Add New User
        </button>
        <button style="width:300px" onclick="viewExpiredUsers()" title="View Users with Expired Passwords">
            ❌ View Users with Expired Passwords
        </button>
        <button style="width:300px" onclick="viewAccessRequests()" title="View New User Access Requests">
    📋 View New User Access Requests
        </button>
    </div>
    <table>
        <tr>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Access Level</th>
            <th>Password Status</th>
            <th>Account Status</th>
            <th class="actions-column">Actions</th>
        </tr>
        
        <?php foreach ($users as $user): ?>
        <tr <?php echo !$user['active'] ? 'class="inactive-row"' : ''; ?>>
            <td><?php echo htmlspecialchars($user['first_name']); ?></td>
            <td><?php echo htmlspecialchars($user['last_name']); ?></td>
            <td><?php echo ucfirst(htmlspecialchars($user['access_level'])); ?></td>
            <td class="<?php echo strtolower($user['password_status']); ?>">
                <?php echo $user['password_status']; ?>
            </td>
            <td>
                <?php 
                if ($user['active']) {
                    echo 'Active';
                } else {
                    echo 'Inactive';
                    if ($user['suspension_remove_date']) {
                        echo '<br><small>Suspended until: ' . date('M j, Y', strtotime($user['suspension_remove_date'])) . '</small>';
                    }
                }
                ?>
            </td>
            <td class="actions-column">
                <button class="action-btn edit-btn" 
                        onclick="editUser(<?php echo $user['user_id']; ?>)"
                        title="Edit User">
                    ✏️ Edit
                </button><br>
                
                <button class="action-btn email-btn" 
                        onclick="emailUser('<?php echo htmlspecialchars($user['email']); ?>', '<?php echo htmlspecialchars($user['first_name']); ?>', '<?php echo htmlspecialchars($user['last_name']); ?>')"
                        title="Send Email">
                    📧 Email
                </button><br>
                
                <?php if ($user['active']): ?>
                    <button class="action-btn suspend-btn" 
                            onclick="showSuspendModal(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>')"
                            title="Suspend User">
                        ⏸️ Suspend
                    </button><br>
                    
                    <button class="action-btn deactivate-btn" 
                            onclick="toggleUserStatus(<?php echo $user['user_id']; ?>, 'deactivate', '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>')"
                            title="Deactivate User">
                        ❌ Deactivate
                    </button><br>
                <?php else: ?>
                    <button class="action-btn activate-btn" 
                            onclick="toggleUserStatus(<?php echo $user['user_id']; ?>, 'activate', '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>')"
                            title="Activate User">
                        ✅ Activate
                    </button><br>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <p><strong>Total Users:</strong> <?php echo count($users); ?></p>
    <p><strong>Active Users:</strong> <?php echo count(array_filter($users, function($u) { return $u['active']; })); ?></p>
    <p><strong>Inactive Users:</strong> <?php echo count(array_filter($users, function($u) { return !$u['active']; })); ?></p>

    <!-- Suspend User Modal -->
    <div id="suspendModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeSuspendModal()">&times;</span>
            <h2>⏸️ Suspend User Account</h2>
            <p>You are about to suspend the account for:</p>
            <p><strong id="suspendUserName"></strong></p>
            
            <form id="suspendForm" class="modal-form">
                <input type="hidden" id="suspendUserId" name="user_id">
                
                <label for="suspensionEndDate">Select when the suspension should be automatically removed:</label>
                <input type="date" 
                       id="suspensionEndDate" 
                       name="suspension_end_date" 
                       required
                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                
                <div class="modal-buttons">
                    <button type="button" class="modal-btn modal-btn-cancel" onclick="closeSuspendModal()">
                        Cancel
                    </button>
                    <button type="submit" class="modal-btn modal-btn-confirm">
                        Suspend User
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- Email Modal -->
<div id="emailModal" class="modal" style="display: none;">
    <div class="modal-content" style="width: 500px;">
        <span class="close" onclick="closeEmailModal()">&times;</span>
        <h2>📧 Send Email</h2>
        <p>Sending email to: <strong id="recipientDisplay"></strong></p>
        
        <form id="emailForm" class="modal-form">
            <input type="hidden" id="emailRecipient" name="email">
            <input type="hidden" id="emailFirstName" name="firstName">
            <input type="hidden" id="emailLastName" name="lastName">
            
            <label for="emailSubject">Subject:</label>
            <input type="text" id="emailSubject" name="subject" required style="width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 3px;">
            
            <label for="emailMessage">Message:</label>
            <textarea id="emailMessage" name="message" required rows="8" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; resize: vertical;"></textarea>
            
            <div class="modal-buttons">
                <button type="button" class="modal-btn modal-btn-cancel" onclick="closeEmailModal()">
                    Cancel
                </button>
                <button type="button" class="modal-btn modal-btn-confirm" onclick="sendEmail()">
                    Send Email
                </button>
            </div>
        </form>
    </div>
</div>
    <script>
        // Navigation button functions
        function addNewUser() {
            window.location.href = 'new_user.php';
        }
        
        function viewExpiredUsers() {
            window.location.href = 'expired_users.php';
        }
        
        function viewAccessRequests() {
            window.location.href = 'view_access_requests.php';
        }
        
        // Edit user function
        function editUser(userId) {
            window.location.href = 'edit_user.php?user_id=' + userId;
        }
        
        // Email user function
        function emailUser(email, firstName, lastName) {
            const subject = encodeURIComponent('Account Information');
            const body = encodeURIComponent('Dear ' + firstName + ' ' + lastName + ',');
            const mailtoLink = 'mailto:' + email + '?subject=' + subject + '&body=' + body;
            window.location.href = mailtoLink;
        }
        
        // Toggle user status function
        function toggleUserStatus(userId, action, userName) {
            const actionText = action === 'activate' ? 'activate' : 'deactivate';
            const confirmMessage = 'Are you sure you want to ' + actionText + ' ' + userName + '?';
            
            if (confirm(confirmMessage)) {
                window.location.href = 'toggle_user_status.php?user_id=' + userId + '&action=' + action;
            }
        }
        
        function showSuspendModal(userId, userName) {
            document.getElementById('suspendUserId').value = userId;
            document.getElementById('suspendUserName').textContent = userName;
            
            // Set minimum date to tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const minDate = tomorrow.toISOString().split('T')[0];
            document.getElementById('suspensionEndDate').setAttribute('min', minDate);
            
            document.getElementById('suspendModal').style.display = 'block';
        }
        
        function closeSuspendModal() {
            document.getElementById('suspendModal').style.display = 'none';
            document.getElementById('suspendForm').reset();
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('suspendModal');
            if (event.target === modal) {
                closeSuspendModal();
            }
        }
        
        // Handle form submission
        document.getElementById('suspendForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const userId = document.getElementById('suspendUserId').value;
            const endDate = document.getElementById('suspensionEndDate').value;
            const userName = document.getElementById('suspendUserName').textContent;
            
            if (!endDate) {
                alert('Please select a suspension end date.');
                return;
            }
            
            if (confirm('Are you sure you want to suspend ' + userName + ' until ' + endDate + '?')) {
                // Create a form to submit the data
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'suspend_user.php';
                
                const userIdInput = document.createElement('input');
                userIdInput.type = 'hidden';
                userIdInput.name = 'user_id';
                userIdInput.value = userId;
                
                const endDateInput = document.createElement('input');
                endDateInput.type = 'hidden';
                endDateInput.name = 'suspension_end_date';
                endDateInput.value = endDate;
                
                form.appendChild(userIdInput);
                form.appendChild(endDateInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    
        function emailUser(email, firstName, lastName) {
    // Create and show email modal
    showEmailModal(email, firstName, lastName);
}

function showEmailModal(email, firstName, lastName) {
    document.getElementById('emailRecipient').value = email;
    document.getElementById('emailFirstName').value = firstName;
    document.getElementById('emailLastName').value = lastName;
    document.getElementById('recipientDisplay').textContent = firstName + ' ' + lastName + ' (' + email + ')';
    
    // Set default subject
    document.getElementById('emailSubject').value = 'Account Information from Clarus System';
    
    // Set default message
    document.getElementById('emailMessage').value = 'Dear ' + firstName + ' ' + lastName + ',\n\nWe are contacting you regarding your account.\n\nBest regards,\nClarus Administration';
    
    document.getElementById('emailModal').style.display = 'block';
}

function closeEmailModal() {
    document.getElementById('emailModal').style.display = 'none';
    document.getElementById('emailForm').reset();
}

function sendEmail() {
    const email = document.getElementById('emailRecipient').value;
    const firstName = document.getElementById('emailFirstName').value;
    const lastName = document.getElementById('emailLastName').value;
    const subject = document.getElementById('emailSubject').value;
    const message = document.getElementById('emailMessage').value;
    
    if (!subject.trim() || !message.trim()) {
        alert('Please fill in both subject and message fields.');
        return;
    }
    
    // Create form data
    const formData = new FormData();
    formData.append('email', email);
    formData.append('firstName', firstName);
    formData.append('lastName', lastName);
    formData.append('subject', subject);
    formData.append('message', message);
    
    // Send via fetch
    fetch('send_email.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Email sent successfully to ' + firstName + ' ' + lastName + '!');
            closeEmailModal();
        } else {
            alert('Error sending email: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error sending email: ' + error.message);
    });
}
    </script>
</div>
</body>
</html>