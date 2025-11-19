<?php
session_start();

include '../db_connect.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get form data
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Validate input
    if (empty($username) || empty($password)) {
        die("Username and password are required.");
    }
    
    try {
        // Create database connection
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check account status and handle suspension
        $stmt = $pdo->prepare("SELECT user_id, username, active, suspension_remove_date FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Check if user is suspended and if suspension should be lifted
            if ($row['active'] == 0 && !is_null($row['suspension_remove_date'])) {
                // User is suspended with a removal date
                $suspension_end = strtotime($row['suspension_remove_date']);
                $current_time = time();
                
                if ($current_time >= $suspension_end) {
                    // Suspension period has passed, reactivate the user
                    $reactivate_stmt = $pdo->prepare("UPDATE users SET active = 1, suspension_remove_date = NULL WHERE username = :username");
                    $reactivate_stmt->bindParam(':username', $username);
                    $reactivate_stmt->execute();
                    
                    //echo "Your suspension has been automatically lifted. Welcome back!   <a href='home.html'>Return home.</a><br>";
                    // Continue with login process - user is now active
                } else {
                    // Still suspended
                    $suspension_end_formatted = date('F j, Y', $suspension_end);
                    echo "Your account is suspended until " . $suspension_end_formatted . ".";
                    echo "<br>Please contact the administrator if you believe this is an error.   <a href='home.html'>Return home.</a>";
                    exit();
                }
            } elseif ($row['active'] == 0) {
                // Account is deactivated (not suspended)
                echo "The account is locked. Please contact the administrator.   <a href='home.html'>Return home.</a>";
                exit();
            }
            // If we reach here, account is active (either was already active or just reactivated)
        } else {
            echo "Username '$username' not found.";
            exit();
        }
        
        // Prepare SQL statement to get unsuccessful login attempts
        $stmt = $pdo->prepare("SELECT unsuccessful_login_attempts FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        // Get the result
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $failed_attempts = $result['unsuccessful_login_attempts'];
            
            // Check if account is locked (3 or more failed attempts)
            if ($failed_attempts >= 3) {
                
                // Prepare SQL statement to set the users active value = false
                $stmt = $pdo->prepare("UPDATE users SET active = 0, suspension_remove_date = NULL WHERE username = :username");
                $stmt->bindParam(':username', $username);
                
                // Execute the update
                if ($stmt->execute()) {
                    $rowsAffected = $stmt->rowCount();
                    
                    if ($rowsAffected > 0) {
                        //echo "User account deactivated successfully!<br>";
                        //echo "Username: " . htmlspecialchars($username) . "<br>";
                        //echo "Active status set to: FALSE";
                    } else {
                        echo "No user found with username: " . htmlspecialchars($username);
                    }
                } else {
                    echo "Error updating user account.";
                }
                
                echo "ACCOUNT LOCKED: Your account has been locked due to " . $failed_attempts . " unsuccessful login attempts.";
                echo "<br>Please contact the administrator to unlock your account.";
                exit(); // Exit the script
            }
        }
        
        // Prepare SQL statement for main login verification
        $stmt = $pdo->prepare("SELECT user_id, username, password_hash, active, access_level FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        // Get user data
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Check if account is active (should be true after suspension handling above)
            if ($user['active']) {
                // Login successful - reset failed attempts
                $reset_attempts_stmt = $pdo->prepare("UPDATE users SET unsuccessful_login_attempts = 0 WHERE username = :username");
                $reset_attempts_stmt->bindParam(':username', $username);
                $reset_attempts_stmt->execute();
                
                $_SESSION['username'] = $user['username'];
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['access_level'] = $user['access_level'];

                // Optional: Set session timeout
                $_SESSION['login_time'] = time();
                
                // Update last login time
                $update_stmt = $pdo->prepare("UPDATE users SET last_login_datetime = NOW() WHERE username = :username");
                $update_stmt->bindParam(':username', $username);
                $update_stmt->execute();
                
                // CHECK PASSWORD EXPIRATION WARNING - NEW CODE
                $password_check_stmt = $pdo->prepare("SELECT last_password_reset_datetime FROM users WHERE username = :username");
                $password_check_stmt->bindParam(':username', $username);
                $password_check_stmt->execute();
                $password_result = $password_check_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($password_result && $password_result['last_password_reset_datetime']) {
                    $last_reset = strtotime($password_result['last_password_reset_datetime']);
                    $current_time = time();
                    $days_since_reset = floor(($current_time - $last_reset) / (60 * 60 * 24));
                    
                    if ($days_since_reset > 26) {
                        // Password is expiring soon (more than 26 days old)
                        $days_until_expiry = 30 - $days_since_reset;
                        
                        if ($days_until_expiry <= 0) {
                            // Password has already expired
                            $_SESSION['password_expired'] = true;
                            $_SESSION['password_message'] = "⚠️ Your password has expired! You must reset it immediately for security purposes.";
                            $_SESSION['password_severity'] = 'critical';
                        } else {
                            // Password expiring soon
                            $_SESSION['password_expiring'] = true;
                            $_SESSION['password_message'] = "⚠️ Your password expires in " . $days_until_expiry . " day(s). Please update it soon to avoid being locked out.";
                            $_SESSION['password_severity'] = 'warning';
                        }
                        
                        // Display immediate warning message
                        echo "<div style='background-color: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
                        echo "<strong>PASSWORD EXPIRATION WARNING:</strong><br>";
                        if ($days_until_expiry <= 0) {
                            echo "Your password has expired! For security purposes, you must reset your password immediately.";
                            echo "<br><a href='reset_password.php' style='color: #721c24; text-decoration: underline;'>Click here to reset your password now</a>";
                        } else {
                            echo "Your password will expire in " . $days_until_expiry . " day(s) (last reset: " . date('F j, Y', $last_reset) . ").";
                            echo "<br><a href='change_password.php' style='color: #856404; text-decoration: underline;'>Click here to update your password</a>";
                        }
                        echo "</div>";
                    } else {
                        // Clear any existing password warnings
                        unset($_SESSION['password_expiring']);
                        unset($_SESSION['password_expired']);
                        unset($_SESSION['password_message']);
                        unset($_SESSION['password_severity']);
                    }
                } else {
                    // No password reset date found - password never reset
                    $_SESSION['password_expired'] = true;
                    $_SESSION['password_message'] = "⚠️ Your password has never been reset and may be using a default password. Please update it immediately for security.";
                    $_SESSION['password_severity'] = 'critical';
                    
                    echo "<div style='background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
                    echo "<strong>SECURITY ALERT:</strong><br>";
                    echo "Your password has never been reset. For security purposes, you should update it immediately.";
                    echo "<br><a href='change_password.php' style='color: #721c24; text-decoration: underline;'>Click here to set a new password</a>";
                    echo "</div>";
                }
                
                //create the log
                // Get user's IP address
                function getUserIP() {
                    // Check for IP from shared internet
                    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                        return $_SERVER['HTTP_CLIENT_IP'];
                    }
                    // Check for IP passed from proxy
                    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                        return $_SERVER['HTTP_X_FORWARDED_FOR'];
                    }
                    // Check for IP from remote address
                    else {
                        return $_SERVER['REMOTE_ADDR'];
                    }
                }
                
                $ip_address = getUserIP();
                $current_datetime = date('Y-m-d H:i:s'); // Current timestamp
                
                try {
                    // Prepare SQL statement (log_id will auto-increment)
                    $stmt = $pdo->prepare("INSERT INTO `system-access-log` (username, login_datetime, ip_address) VALUES (:username, :login_datetime, :ip_address)");
                    
                    // Bind parameters
                    $stmt->bindParam(':username', $username);
                    $stmt->bindParam(':login_datetime', $current_datetime);
                    $stmt->bindParam(':ip_address', $ip_address);
                    
                    // Execute the statement
                    if ($stmt->execute()) {
                        echo "Access log entry created successfully!<br>";
                        echo "Username: " . htmlspecialchars($username) . "<br>";
                        echo "IP Address: " . $ip_address . "<br>";
                        echo "Login DateTime: " . $current_datetime . "<br>";
                        echo "Log ID: " . $pdo->lastInsertId() . "<br>";
                    } else {
                        echo "Error creating log entry.";
                    }
                    
                } catch(PDOException $e) {
                    echo "Database Error: " . $e->getMessage();
                }
                
                //check if any of the user profile fields are blank and if so, redirect to the profile page
                try {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
                    $stmt->bindParam(':username', $username);
                    $stmt->execute();
                    
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$result) {
                        die("Username not found.");
                    }
                    
                    echo "COMPLETE Field Analysis for User: " . htmlspecialchars($username) . "<br>";
                    echo "(ALL FIELDS ARE REQUIRED)<br><br>";
                    
                    $has_blank_fields = false;
                    $blank_count = 0;
                    
                    foreach ($result as $field_name => $field_value) {
                        $status = "";
                        $is_blank = false;
                        
                        if (is_null($field_value)) {
                            $status = "NULL";
                            $is_blank = true;
                        } elseif (trim($field_value) === '') {
                            $status = "EMPTY";
                            $is_blank = true;
                        } else {
                            $status = "POPULATED";
                        }
                        
                        if ($is_blank) {
                            echo "<strong style='color: red;'>" . $field_name . ":</strong> " . $status . " ❌<br>";
                            $has_blank_fields = true;
                            $blank_count++;
                        } else {
                            echo $field_name . ": " . $status . " ✅<br>";
                        }
                    }
                    
                    echo "<br><strong>SUMMARY:</strong><br>";
                    if ($has_blank_fields) {
                        echo "❌ PROFILE INCOMPLETE: " . $blank_count . " field(s) missing data<br>";
                        echo "ALL fields must be populated before account can be fully activated.";
                        header("Location: profile.php");
                        exit();
                    } else {
                        echo "✅ PROFILE COMPLETE: All required fields are populated.";
                        // Redirect to dashboard or home page BACK IN OG LOGIN SCRIPT
                        header("Location: landing.php");
                        exit();
                    }
                    
                } catch(PDOException $e) {
                    echo "Error: " . $e->getMessage();
                }
                
            } else {
                echo "Account is deactivated. Please contact administrator.  <a href='home.html'>Return home.</a>";
            }
        } else {
            // Login failed - increment unsuccessful attempts
            if ($user) {
                $fail_stmt = $pdo->prepare("UPDATE users SET unsuccessful_login_attempts = unsuccessful_login_attempts + 1 WHERE username = :username");
                $fail_stmt->bindParam(':username', $username);
                $fail_stmt->execute();
            }
            echo "Invalid username or password. <a href='home.html'>Try again.</a>";
        }
        
    } catch(PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
    }
} else {
    echo "Invalid request method.  <a href='home.html'>Try again.</a>";
}
?>