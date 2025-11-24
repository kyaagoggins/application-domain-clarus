<?php
//KSU student project for Clarus Accounting tool
//This page is used by admins to toggle other users as active or inactive
//Initially drafted by Eric Poole

//Connect to the external database config file
include '../db_connect.php';

if (isset($_GET['user_id']) && isset($_GET['action'])) {
    $user_id = $_GET['user_id'];
    $action = $_GET['action'];
    
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
    // Get current user info for better messaging
    $info_stmt = $pdo->prepare("SELECT first_name, last_name, suspension_remove_date FROM users WHERE user_id = :user_id");
    $info_stmt->execute([':user_id' => $user_id]);
    $user_info = $info_stmt->fetch(PDO::FETCH_ASSOC);
        
    if (!$user_info) {
        echo "<script>alert('Error: User not found.'); window.location.href='dashboard.php';</script>";
        exit;
    }
        
    // Determine the new status
    $new_status = ($action == 'activate') ? 1 : 0;
        
    // Update user status and clear suspension_remove_date for both actions
    $updateStatus = $pdo->prepare("
        UPDATE users 
        SET active = :status, 
            suspension_remove_date = NULL,
            unsuccessful_login_attempts = 0
        WHERE user_id = :user_id
    ");
        
    $updateStatus->execute([
        ':status' => $new_status,
        ':user_id' => $user_id
    ]);
        
    if ($updateStatus->rowCount() > 0) {
        $user_name = $user_info['first_name'] . ' ' . $user_info['last_name'];
            
        if ($action == 'activate') {
            $message = "User " . $user_name . " is now active!";
            if ($user_info['suspension_remove_date']) {
                $message .= " The user's suspension has been cleared.";
            }
        } else {
             $message = "User " . $user_name . " has been deactivated successfully!";
        }
            
        echo "<script>alert('" . addslashes($message) . "'); window.location.href='dashboard.php';</script>";
    } else {
        echo "<script>alert('Error: No changes were made.'); window.location.href='dashboard.php';</script>";
    }
        
} else {
    header("Location: dashboard.php");
    exit();
}
?>