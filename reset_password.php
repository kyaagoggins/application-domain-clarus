<?php
session_start();

// Check verification
if (!isset($_SESSION['verified_user'])) {
    header("Location: verify_identity.php");
    exit();
}

// Database config
include '../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];
    $username = $_SESSION['verified_user'];
    
    // Basic validation
    if ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($newPassword) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        try {
            $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check current password in users table
            $current_stmt = $pdo->prepare("SELECT password_hash FROM users WHERE username = :username");
            $current_stmt->execute([':username' => $username]);
            $current_user = $current_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($current_user && password_verify($newPassword, $current_user['password_hash'])) {
                $error = "You cannot reuse your current password. Please choose a different password.";
            } else {
                // Check legacy passwords table
                $legacy_stmt = $pdo->prepare("SELECT password_hash FROM `legacy-passwords` WHERE username = :username");
                $legacy_stmt->execute([':username' => $username]);
                $legacy_passwords = $legacy_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $password_reused = false;
                foreach ($legacy_passwords as $legacy) {
                    if (password_verify($newPassword, $legacy['password_hash'])) {
                        $password_reused = true;
                        break;
                    }
                }
                
                if ($password_reused) {
                    $error = "You cannot reuse a previous password. Please choose a new password that you haven't used before.";
                } else {
                    // Hash the new password
                    $new_password_hash = password_hash($newPassword, PASSWORD_DEFAULT);
                    
                    // Update users table
                    $update_stmt = $pdo->prepare("
                        UPDATE users SET 
                            password_hash = :password_hash,
                            last_password_reset_datetime = NOW(),
                            unsuccessful_login_attempts = 0
                        WHERE username = :username
                    ");
                    
                    $update_stmt->execute([
                        ':password_hash' => $new_password_hash,
                        ':username' => $username
                    ]);
                    
                    if ($update_stmt->rowCount() > 0) {
                        // Add current password to legacy-passwords table before updating
                        if ($current_user && !empty($current_user['password_hash'])) {
                            $legacy_insert = $pdo->prepare("INSERT INTO `legacy-passwords` (username, password_hash) VALUES (:username, :password_hash)");
                            $legacy_insert->execute([
                                ':username' => $username,
                                ':password_hash' => $current_user['password_hash']
                            ]);
                        }
                        
                        echo "✅ Password reset successful!<br>";
                        echo "Your password has been updated and your previous password has been stored for security purposes.<br>";
                        echo "<a href='home.html'>Login now</a>";
                        
                        // Clear verification session
                        unset($_SESSION['verified_user']);
                        unset($_SESSION['verified_email']);
                        exit();
                    } else {
                        $error = "Error updating password.";
                    }
                }
            }
            
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>
    <h1>Reset Password</h1>
    
    <p>Resetting password for: <strong><?php echo htmlspecialchars($_SESSION['verified_user']); ?></strong></p>
    
    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>
    
    <form action="" method="POST">
        <div>
            <label for="newPassword">New Password:</label>
            <input type="password" id="newPassword" name="newPassword" required>
        </div>
        <br>
        <div>
            <label for="confirmPassword">Confirm Password:</label>
            <input type="password" id="confirmPassword" name="confirmPassword" required>
        </div>
        <br>
        <div>
            <input type="submit" value="Reset Password">
        </div>
    </form>
    
    <p style="color: #666; font-size: 12px;">
        Note: You cannot reuse your current password or any previously used passwords.
    </p>
</body>
</html>