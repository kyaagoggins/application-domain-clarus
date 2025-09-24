<?php
/**
 * Complete Profile Page
 * This page is shown to users who need to complete their profile information
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
        include '../db_connect.php';

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get only users with expired passwords
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
            CASE 
                WHEN last_password_reset_datetime IS NULL THEN 'Never Reset'
                WHEN DATEDIFF(NOW(), last_password_reset_datetime) > 30 THEN 'Expired'
                ELSE 'Valid'
            END AS password_status
        FROM users 
        HAVING password_status = 'Expired'
        ORDER BY last_name, first_name
    ");
    
    $expired_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total user counts for reference
    $total_stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $total_count = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
} catch(PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Users with Expired Passwords</title>
    <style>
        table { 
            border-collapse: collapse; 
            width: 100%; 
            margin: 20px 0; 
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: left; 
        }
        th { 
            background-color: #f44336; 
            color: white; 
        }
        tr:nth-child(even) { 
            background-color: #f2f2f2; 
        }
        .expired { 
            color: red; 
            font-weight: bold; 
        }
        .inactive-row {
            opacity: 0.6;
            background-color: #f5f5f5;
        }
        .alert-header {
            background-color: #ffebee;
            border-left: 4px solid #f44336;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .alert-header h2 {
            color: #c62828;
            margin: 0 0 10px 0;
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
    </style>
</head>
<body>
    <button class="nav-btn nav-btn-back" onclick="goBackToUserManagement()" title="Back to User Management">
            ← Back to User Management
        </button>
    <div class="alert-header">
        <h2>⚠️ Users with Expired Passwords</h2>
        <p>The following users have passwords that are more than 30 days old and need to be reset for security compliance.</p>
    </div>

    <?php if (empty($expired_users)): ?>
        <div style="text-align: center; padding: 40px; color: #4CAF50; font-size: 18px;">
            <h3>✅ No users with expired passwords!</h3>
            <p>All users have valid passwords or have reset their passwords within the last 30 days.</p>
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
            <td><?php echo htmlspecialchars($user['first_name']); ?></td>
            <td><?php echo htmlspecialchars($user['last_name']); ?></td>
            <td><?php echo htmlspecialchars($user['email']); ?></td>
            <td><?php echo ucfirst(htmlspecialchars($user['access_level'])); ?></td>
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
    
    <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <p><strong>Users with Expired Passwords:</strong> <?php echo count($expired_users); ?></p>
        <p><strong>Total Users in System:</strong> <?php echo $total_count; ?></p>
        <p><strong>Compliance Rate:</strong> <?php echo $total_count > 0 ? round((($total_count - count($expired_users)) / $total_count) * 100, 1) : 0; ?>%</p>
    </div>
    
    <div style="margin-top: 20px;">
        <button style="width:300px" onclick="addNewUser()" title="Add New User">
            ➕ Add New User
        </button>
    </div>
    </div>
    <script>
        // Navigation button functions
        function addNewUser() {
            window.location.href = 'new_user.php';
        }
 
        function goBackToUserManagement() {
            window.location.href = 'dashboard.php';
        }
    </script>
</body>
</html>