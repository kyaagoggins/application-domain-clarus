<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get form data
$to_email = $_POST['email'] ?? '';
$firstName = $_POST['firstName'] ?? '';
$lastName = $_POST['lastName'] ?? '';
$subject = $_POST['subject'] ?? '';
$message = $_POST['message'] ?? '';
$from_user = $_SESSION['username'] ?? 'Admin';

// Validate required fields
if (empty($to_email) || empty($subject) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate email format
if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// Email configuration - UPDATE THESE WITH YOUR SMTP SETTINGS
$smtp_host = 'smtp.gmail.com';  // Your SMTP server
$smtp_port = 587;               // SMTP port (587 for TLS, 465 for SSL)
$smtp_username = 'your-email@gmail.com';  // Your email
$smtp_password = 'your-app-password';     // Your email password or app password
$from_email = 'your-email@gmail.com';     // From email address
$from_name = 'Clarus System Administration';

// Create email headers
$headers = [
    'From' => $from_name . ' <' . $from_email . '>',
    'Reply-To' => $from_email,
    'X-Mailer' => 'PHP/' . phpversion(),
    'MIME-Version' => '1.0',
    'Content-Type' => 'text/plain; charset=utf-8'
];

// Add signature to message
$full_message = $message . "\n\n---\nThis email was sent by: " . $from_user . "\nFrom: Clarus User Management System\nDate: " . date('Y-m-d H:i:s');

try {
    // Simple mail() function approach
    $header_string = '';
    foreach ($headers as $key => $value) {
        $header_string .= $key . ': ' . $value . "\r\n";
    }
    
    if (mail($to_email, $subject, $full_message, $header_string)) {
        // Log the email sending
        logEmailActivity($to_email, $firstName, $lastName, $subject, $from_user);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Email sent successfully to ' . $firstName . ' ' . $lastName
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send email']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Email sending failed: ' . $e->getMessage()]);
}

// Function to log email activity
function logEmailActivity($to_email, $firstName, $lastName, $subject, $from_user) {
    include '../db_connect.php';
    
    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create email log table if it doesn't exist
        $create_table = "CREATE TABLE IF NOT EXISTS email_log (
            log_id INT AUTO_INCREMENT PRIMARY KEY,
            sent_by VARCHAR(100),
            sent_to VARCHAR(255),
            recipient_name VARCHAR(200),
            subject VARCHAR(500),
            sent_datetime TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($create_table);
        
        // Insert log entry
        $stmt = $pdo->prepare("INSERT INTO email_log (sent_by, sent_to, recipient_name, subject) VALUES (:sent_by, :sent_to, :recipient_name, :subject)");
        $stmt->execute([
            ':sent_by' => $from_user,
            ':sent_to' => $to_email,
            ':recipient_name' => $firstName . ' ' . $lastName,
            ':subject' => $subject
        ]);
        
    } catch (PDOException $e) {
        // Log error but don't fail the email sending
        error_log('Email log failed: ' . $e->getMessage());
    }
}
?>