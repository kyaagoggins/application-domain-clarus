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
        echo "<script>alert('Hmm.. this user was not found. Please go back and try again.'); window.location.href='dashboard.php';</script>";
        exit;
    }
        
    // Check if we are activating or deactivating the user
    if ($action == 'activate') 
    {
        $new_status = 1;
    } 
    else {
        $new_status = 0;
    }

        
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
        
    if ($updateStatus->rowCount() > 0) 
    {
        $user_name = $user_info['first_name'] . ' ' . $user_info['last_name'];
            
        if ($action == 'activate') 
        {
            $message = "This user is now active!";
            if ($user_info['suspension_remove_date']) 
            {
                $message .= " The user's suspension has been cleared.";
            }
        } 
        else 
        {
             $message = "This user has been deactivated successfully!";
        }
            
        echo "<script>alert('" . $message."'); window.location.href='dashboard.php';</script>";
    } 
    else 
    {
        echo "<script>alert('It looks like nothing was updated? If this doesn't sound right, go back and try again.'); window.location.href='dashboard.php';</script>";
    }
        
} else {
    header("Location: dashboard.php");
    exit();
}
?>