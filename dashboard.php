<?php
//KSU student project for Clarus Accounting tool
//This page is used by managers to view all system users
// This page is the user management dashboard for system admins to view and access
//Initially drafted by Eric Poole. Reviewed and updated by Kyaa Goggins

session_start();

// Check if user is logged in, if not redirect to the sign in page
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

//set the session values to local variables
$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];


include 'header.php';
?>
<div class="container"
    style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none; -ms-overflow-style: none;">

    <?php
    echo $_SESSION['password_message'];

    //connect to the external config db file
    include '../db_connect.php';


    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get all users with password expiration status
    $getExpiredUsers = $pdo->query("
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
            WHEN last_password_reset_datetime IS NULL THEN 'Never Set'
            WHEN DATEDIFF(NOW(), last_password_reset_datetime) > 30 THEN 'Expired'
            ELSE 'Valid'
        END AS password_status
        FROM users 
        ORDER BY last_name, first_name
    ");

    $users = $getExpiredUsers->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <link rel="stylesheet" href="/styling/dashboard.css">
    <h1><i class="fa-solid fa-users"></i>User Management Dashboard</h1>
    <div style="margin-top: 20px;">
        <button style="width:300px" onclick="addNewUser()" title="Add New User"><i class="fa-solid fa-user-plus"></i>
            Add New User</button>
        <button style="width:300px" onclick="viewExpiredUsers()" title="View Users with Expired Passwords"><i
                class="fa-solid fa-user-clock"></i> View Users
            with Expired Passwords</button>
        <button style="width:300px" onclick="viewAccessRequests()" title="View New User Access Requests"><i
                class="fa-solid fa-user-tag"></i> View New User
            Access Requests</button>
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
                <td><?php echo $user['first_name']; ?></td>
                <td><?php echo $user['last_name']; ?></td>
                <td><?php echo $user['access_level']; ?></td>
                <!--the following td element references the php twice, once to set the css class name to color the td content, then again to insert the text-->
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
                    <button class="action-btn edit-btn" onclick="editUser(<?php echo $user['user_id']; ?>)"
                        title="Edit User"><i class="fa-solid fa-user-pen"></i>Edit</button>
                    <br>
                    <button class="action-btn email-btn"
                        onclick="emailUser('<?php echo ($user['email']); ?>', '<?php echo ($user['first_name']); ?>', '<?php echo ($user['last_name']); ?>')"
                        title="Send Email"><i class="fa-solid fa-envelope-open-text"></i>Email</button>
                    <br>
                    <?php if ($user['active']): ?>
                        <button class="action-btn suspend-btn"
                            onclick="showSuspendModal(<?php echo $user['user_id']; ?>, '<?php echo ($user['first_name'] . ' ' . $user['last_name']); ?>')"
                            title="Suspend User"><i class="fa-solid fa-user-lock"></i>Suspend</button>
                        <br>
                        <button class="action-btn deactivate-btn"
                            onclick="toggleUserStatus(<?php echo $user['user_id']; ?>, 'deactivate', '<?php echo ($user['first_name'] . ' ' . $user['last_name']); ?>')"
                            title="Deactivate User"><i class="fa-solid fa-user-slash"></i>Deactivate</button>
                        <br>
                    <?php else: ?>
                        <button class="action-btn activate-btn"
                            onclick="toggleUserStatus(<?php echo $user['user_id']; ?>, 'activate', '<?php echo ($user['first_name'] . ' ' . $user['last_name']); ?>')"
                            title="Activate User"><i class="fa-solid fa-user-check"></i>Activate</button>
                        <br>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <!-- Suspend User Modal -->
    <div id="suspendModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeSuspendModal()">&times;</span>
            <h2>⏸<i class="fa-solid fa-user-lock"></i> Suspend User Account</h2>
            <p>You are about to suspend the account for:</p>
            <p><strong id="suspendUserName"></strong></p>

            <form id="suspendForm" class="modal-form">
                <input type="hidden" id="suspendUserId" name="user_id">

                <label for="suspensionEndDate">Select when the suspension should be automatically removed:</label>
                <input type="date" id="suspensionEndDate" name="suspension_end_date" required
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
            <h2><i class="fa-solid fa-envelope-open-text"></i> Send Email</h2>
            <p>Sending email to: <strong id="recipientDisplay"></strong></p>

            <form id="emailForm" class="modal-form">
                <input type="hidden" id="emailRecipient" name="email">
                <input type="hidden" id="emailFirstName" name="firstName">
                <input type="hidden" id="emailLastName" name="lastName">

                <label for="emailSubject">Subject:</label>
                <input type="text" id="emailSubject" name="subject" required
                    style="width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 3px;">

                <label for="emailMessage">Message:</label>
                <textarea id="emailMessage" name="message" required rows="8"
                    style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; resize: vertical;"></textarea>

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

        //displays suspend modal with necessary user information loaded
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
        window.onclick = function (event) {
            const modal = document.getElementById('suspendModal');
            if (event.target === modal) {
                closeSuspendModal();
            }
        }

        // Handle form submission for suspending a user
        document.getElementById('suspendForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const userId = document.getElementById('suspendUserId').value;
            const endDate = document.getElementById('suspensionEndDate').value;
            const userName = document.getElementById('suspendUserName').textContent;

            if (!endDate) {
                alert('Wait! Please select a suspension end date.');
                return;
            }

            if (confirm('Hm, Are you sure you want to suspend ' + userName + ' until ' + endDate + '?')) {
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

        //displays email modal 
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

        //functionality for sending emails 
        function sendEmail() {
            const email = document.getElementById('emailRecipient').value;
            const firstName = document.getElementById('emailFirstName').value;
            const lastName = document.getElementById('emailLastName').value;
            const subject = document.getElementById('emailSubject').value;
            const message = document.getElementById('emailMessage').value;

            if (!subject.trim() || !message.trim()) {
                alert('Wait! Please fill in both subject and message fields.');
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
                        alert('Oh no! Error sending email: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Oh no! Error sending email: ' + error.message);
                });
        }
    </script>
</div>
</body>

</html>