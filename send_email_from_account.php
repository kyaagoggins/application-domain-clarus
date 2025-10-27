<?php
/**
 * Send Email Handler
 * Processes email sending requests from users
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}

$senderUserId = $_SESSION['user_id'];
$senderUsername = $_SESSION['username'] ?? 'User';

// Get POST data
$recipientUserId = isset($_POST['recipient_user_id']) ? trim($_POST['recipient_user_id']) : '';
$recipientEmail = isset($_POST['recipient_email']) ? trim($_POST['recipient_email']) : '';
$subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$content = isset($_POST['content']) ? trim($_POST['content']) : '';
$accountNumber = isset($_POST['account_number']) ? trim($_POST['account_number']) : '';

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

// Include database configuration
include '../db_connect.php';

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get sender's email
    $stmt = $pdo->prepare("SELECT email, first_name, last_name FROM users WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $senderUserId]);
    $sender = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sender) {
        echo json_encode(['success' => false, 'message' => 'Sender not found']);
        exit;
    }
    
    $senderEmail = $sender['email'];
    $senderFullName = trim(($sender['first_name'] ?? '') . ' ' . ($sender['last_name'] ?? ''));
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
    
    if ($mailSent) {
        // Log the email in database (optional)
        $stmt = $pdo->prepare("
            INSERT INTO email_logs (sender_user_id, recipient_user_id, recipient_email, subject, content, account_number, sent_at) 
            VALUES (:sender_user_id, :recipient_user_id, :recipient_email, :subject, :content, :account_number, NOW())
        ");
        
        try {
            $stmt->execute([
                ':sender_user_id' => $senderUserId,
                ':recipient_user_id' => $recipientUserId,
                ':recipient_email' => $recipientEmail,
                ':subject' => $subject,
                ':content' => $content,
                ':account_number' => $accountNumber
            ]);
        } catch(PDOException $e) {
            // If email_logs table doesn't exist, just continue without logging
            // The email was still sent successfully
        }
        
        echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send email. Please check server mail configuration.']);
    }
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>