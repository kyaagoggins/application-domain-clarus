<?php
//KSU student project for Clarus Accounting tool
//This page is used to update the user table when an admin deactivates a user's account
//Initially drafted by Eric Poole. Reviewed and updated by Kyaa Goggins

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: home.html?error=session_expired');
    exit;
}

// Connect to the external db file
include '../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") 
{
    //user specific information 
    $user_id = $_POST['user_id'];
    $suspension_end_date = $_POST['suspension_end_date'];


    // Validate that the suspension end date is in the future
    //route user back to user management table if not proper data selected
    if (strtotime($suspension_end_date) <= time()) {
        echo "<script>alert('Oops! The suspension end date must be in the future. Please go back and try again.'); window.location.href='user_management.php';</script>";
        exit;
    }


    // Create database connection
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get user information for confirmation
    $get_user_info = $pdo->prepare("SELECT first_name, last_name FROM users WHERE user_id = :user_id");
    $get_user_info->execute([':user_id' => $user_id]);
    $user_info = $get_user_info->fetch(PDO::FETCH_ASSOC);

    //route user back to user management table 
    if (!$user_info) {
        echo "<script>alert('Hmm... that user was not found. Please go back and try again.'); window.location.href='user_management.php';</script>";
        exit;
    }

    // Update user to suspended status
    //pushes db changes to post 
    $update_user = $pdo->prepare("
            UPDATE users 
            SET active = 0, 
                suspension_remove_date = :suspension_end_date
            WHERE user_id = :user_id
        ");

    $update_user->execute([
        ':suspension_end_date' => $suspension_end_date,
        ':user_id' => $user_id
    ]);

    if ($update_user->rowCount() > 0) {
        $user_name = $user_info['first_name'] . ' ' . $user_info['last_name'];
        $formatted_date = date('F j, Y', strtotime($suspension_end_date));

        echo "<script>
                alert('Okay! User " . $user_name. " has been suspended until " . $formatted_date . ".');
                window.location.href='dashboard.php';
            </script>";
    } else {
        echo "<script>alert('Hmm... Something went wrong and the user was not suspended. Please go back and try again.'); window.location.href='dashboard.php';</script>";
    }
} else {
    //sends user back to user dashboard home 
    header('Location: dashboard.php');
    exit;
}
?>