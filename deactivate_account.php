<?php
//KSU student project for Clarus Accounting tool
//This page is for the handling of scripting with deactivating a user 
//Meets Sprint 2 requirements
//Initially drafted by Eric Poole. Reviewed and updated by Kyaa Goggins

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: home.html?error=session_expired');
    exit;
}

// Include database configuration
include '../db_connect.php';

// Get parameters from URL
$account_number = $_GET['account_number'];
$action = $_GET['action'];

if ($action == "reactivate") {
    $new_status = 1;
} else {
    $new_status = 0;
}
// Test database connection first
$pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


//$sql = "UPDATE accounts SET is_active = :status WHERE account_number = :account_number";

$updateAccount = $pdo->prepare("UPDATE accounts SET is_active = :status WHERE account_number = :account_number");
$result = $updateAccount->execute([':status' => $new_status, ':account_number' => $account_number]);

echo "This account was updated successfully.";



echo "<br><a href='accounts_dashboard.php'>Return to Account Management.</a>";
?>