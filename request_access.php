<?php
// Database configuration
include '../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get and validate form data
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    
    if (empty($firstName) || empty($lastName) || empty($email)) {
        die("All fields are required.");
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email address.");
    }
    
    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check for duplicates
        $check = $pdo->prepare("SELECT email FROM `new-user-requests` WHERE email = :email");
        $check->execute([':email' => $email]);
        
        if ($check->rowCount() > 0) {
            echo "A request already exists for this email address. We will get back to you soon.";
            exit;
        }
        
        // Insert new request
        $stmt = $pdo->prepare("
            INSERT INTO `new-user-requests` (first_name, last_name, email, approved, created_at, updated_at) 
            VALUES (:first_name, :last_name, :email, FALSE, NOW(), NOW())
        ");
        
        $success = $stmt->execute([
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':email' => $email
        ]);
        
        if ($success) {
            echo "Your request was submitted successfully! We will get back to you shortly. <a href='home.html'> Return home.</a>";
        } else {
            echo "Error submitting request.";
        }
        
    } catch(PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }
    
} else {
    echo "Invalid request method.";
}
?>