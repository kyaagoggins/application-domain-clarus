<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: home.html?error=session_expired');
    exit;
}

// Database configuration
include '../db_connect.php';

function generateUsername($firstName, $lastName) {
    // Get first initial (uppercase)
    $firstInitial = strtoupper(substr(trim($firstName), 0, 1));
    
    // Get last name (capitalize first letter, lowercase rest)
    $lastName = ucfirst(strtolower(trim($lastName)));
    
    // Get current month and year (MMYY format)
    $month = date('m'); // Two digit month (01-12)
    $year = date('y');  // Two digit year (24 for 2024)
    
    // Combine: FirstInitial + LastName + MMYY
    return $firstInitial . $lastName . $month . $year;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $request_id = $_POST['request_id'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $action = $_POST['action'] ?? '';
    
    if (empty($request_id) || empty($action)) {
        echo "<script>alert('Invalid request data.'); window.location.href='user_requests.php';</script>";
        exit;
    }
    
    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get request details
        $stmt = $pdo->prepare("SELECT * FROM `new-user-requests` WHERE request_id = :request_id");
        $stmt->execute([':request_id' => $request_id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            echo "<script>alert('Request not found.'); window.location.href='user_requests.php';</script>";
            exit;
        }
        
        if ($action == 'approve') {
            // Begin transaction
            $pdo->beginTransaction();
            
            try {
                // Generate username using the new rules
                $base_username = generateUsername($_POST['first_name'], $_POST['last_name']);
                
                // Generate username from email
                $username =  $base_username;
                
                // Generate temporary password
                $temp_password = 'Temp' . rand(1000, 9999) . '!';
                $password_hash = password_hash($temp_password, PASSWORD_DEFAULT);
                
                // Create user account
                $create_user = $pdo->prepare("
                    INSERT INTO users (
                        username, first_name, last_name, email, password_hash, 
                        access_level, active, unsuccessful_login_attempts, 
                        created_at
                    ) VALUES (
                        :username, :first_name, :last_name, :email, :password_hash,
                        'accountant', 1, 0, NOW()
                    )
                ");
                
                $create_user->execute([
                    ':username' => $username,
                    ':first_name' => $request['first_name'],
                    ':last_name' => $request['last_name'],
                    ':email' => $request['email'],
                    ':password_hash' => $password_hash
                ]);
                
                // Mark request as approved
                $update_request = $pdo->prepare("UPDATE `new-user-requests` SET approved = 1, updated_at = NOW() WHERE request_id = :request_id");
                $update_request->execute([':request_id' => $request_id]);
                
                $pdo->commit();
                
                echo "<script>
                    alert('Request APPROVED! User account created successfully.\\n\\nUsername: " . addslashes($username) . "\\nTemporary Password: " . addslashes($temp_password) . "\\n\\nPlease send these credentials to the user.');
                    window.location.href='view_user_requests.php';
                </script>";
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            
        } elseif ($action == 'reject') {
            // Simply mark as rejected (set approved to -1 or delete record)
            $reject_stmt = $pdo->prepare("DELETE FROM `new-user-requests` WHERE request_id = :request_id");
            $reject_stmt->execute([':request_id' => $request_id]);
            
            echo "<script>
                alert('Request REJECTED and removed from the system.');
                window.location.href='view_user_requests.php';
            </script>";
        }
        
    } catch(PDOException $e) {
        echo "<script>alert('Database Error: " . addslashes($e->getMessage()) . "'); window.location.href='view_user_requests.php';</script>";
    }
    
} else {
    header('Location: view_user_requests.php');
    exit;
}
?>