<?php
//KSU student project for Clarus Accounting tool
//This page is used to send an email from the Clarus system
//Initially drafted by Eric Poole

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Your have been logged out, please log back in and try again.";
    exit;
}

$senderUserId = $_SESSION['user_id'];
$senderUsername = $_SESSION['username'];

// Get POST data
$recipientUserId = $_POST['recipient_user_id'];
$recipientEmail = $_POST['recipient_email'];
$subject = $_POST['subject'];
$content = $_POST['content'];
$accountNumber = $_POST['account_number'];

// Validate inputs
if (empty($recipientUserId) || empty($recipientEmail) || empty($subject) || empty($content)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Validate email format
if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

//Connect to the external database config file
include '../db_connect.php';


$pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
// Get sender's email
$getSenderEmail = $pdo->prepare("SELECT email, first_name, last_name FROM users WHERE user_id = :user_id");
$getSenderEmail->execute([':user_id' => $senderUserId]);
$sender = $getSenderEmail->fetch(PDO::FETCH_ASSOC);
    
if (!$sender) {
    echo 'Something went wrong... the sender was not found.';
    exit;
}
    
$senderEmail = $sender['email'];
$senderFullName = $sender['first_name'] . ' ' . $sender['last_name'];
if (empty($senderFullName)) {
    $senderFullName = $senderUsername;
}
    
// Prepare email headers
$headers = "From: " . $senderFullName . " <" . $senderEmail . ">\r\n";
$headers .= "Reply-To: " . $senderEmail . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    
// Prepare email body
$emailBody = "Message from: " . $senderFullName . " (" . $senderUsername . ")\n";
$emailBody .= "Sender Email: " . $senderEmail . "\n";
if (!empty($accountNumber)) {
    $emailBody .= "Regarding Account: " . $accountNumber . "\n";
}
$emailBody .= "\n" . str_repeat("-", 50) . "\n\n";
$emailBody .= $content;
$emailBody .= "\n\n" . str_repeat("-", 50) . "\n";
$emailBody .= "This email was sent through the Clarus Accounting System.\n";
    
// Send email
$mailSent = mail($recipientEmail, $subject, $emailBody, $headers);

echo 'Your email was sent successfully!';

    
?>