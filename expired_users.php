<?php
//KSU student project for Clarus Accounting tool
//This page is used by admins to view users with expired passwords
//Initially drafted by Eric Poole. Reviewed and updated by Kyaa Goggins
//Meets sprint 1 requirements

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

// If accessed directly and profile is already complete, redirect to home

include 'header.php';
?>

<link rel="stylesheet" href="/styling/expired_users.css">
<div class="container" style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none;">
    <?php
    include '../db_connect.php';


    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get only users with expired passwords
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
            CASE 
                WHEN last_password_reset_datetime IS NULL THEN 'Never Reset'
                WHEN DATEDIFF(NOW(), last_password_reset_datetime) > 30 THEN 'Expired'
                ELSE 'Valid'
            END AS password_status
        FROM users 
        HAVING password_status = 'Expired'
        ORDER BY last_name, first_name
    ");

    $expired_users = $getExpiredUsers->fetchAll(PDO::FETCH_ASSOC);

    // Get total user counts for displaying in the UI
    $all_users = $pdo->query("SELECT COUNT(*) as total FROM users");
    $total_user_count = $all_users->fetch(PDO::FETCH_ASSOC)['total'];

    ?>

    <body>
        <button class="nav-btn nav-btn-back" onclick="window.location.href = 'dashboard.php';"
            title="Back to User Management">
            ← Back to User Management
        </button>
        <div class="alert-header">
            <h2> Users with Expired Passwords</h2>
            <p>The following users have passwords that are more than 30 days old and need to be reset for security
                compliance.</p>
        </div>

        <?php if (empty($expired_users)): ?>
            <div style="text-align: center; padding: 40px; color: #4CAF50; font-size: 18px;">
                <h3>Great! You have no users with expired passwords!</h3>
                <p>This means all users have valid passwords or have reset their passwords within the last 30 days.</p>
            </div>
        <?php else: ?>

            <table>
                <tr>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Access Level</th>
                    <th>Password Status</th>
                    <th>Account Status</th>
                    <th>Days Since Reset</th>
                </tr>

                <?php foreach ($expired_users as $user): ?>
                    <tr <?php echo !$user['active'] ? 'class="inactive-row"' : ''; ?>>
                        <td><?php echo $user['first_name']; ?></td>
                        <td><?php echo $user['last_name']; ?></td>
                        <td><?php echo $user['email']; ?></td>
                        <td>
                            <!--Logic add 11/23 to display the user role as text rather than the integer stored in the db-->
                            <?php
                            if ($user['access_level'] == 1) {
                                echo "Accountant";
                            } else if ($user['access_level'] == 2) {
                                echo "Manager";
                            } else if ($user['access_level'] == 3) {
                                echo "Admin";
                            }
                            ?>
                        </td>
                        <td class="expired">
                            <?php echo $user['password_status']; ?>
                        </td>
                        <td><?php echo $user['active'] ? 'Active' : 'Inactive'; ?></td>
                        <td>
                            <?php
                            if ($user['last_password_reset_datetime']) {
                                $days = floor((time() - strtotime($user['last_password_reset_datetime'])) / (60 * 60 * 24));
                                echo $days . " days";
                            } else {
                                echo "Never reset";
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>

        <?php endif; ?>

</div>
</body>

</html>