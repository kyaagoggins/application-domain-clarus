<?php
// Database configuration
include '../db_connect.php';

// Set the user credentials to insert
$username = "admin2";          // Change this to desired username
$password = "Admin123!";  // Change this to desired password

try {
    // Create database connection
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Prepare SQL statement
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)");
    
    // Bind parameters
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password_hash', $password_hash);
    
    // Execute the statement
    if ($stmt->execute()) {
        echo "User created successfully!<br>";
        echo "Username: " . $username . "<br>";
        echo "Original Password: " . $password . "<br>";
        echo "Hashed Password: " . $password_hash . "<br>";
    } else {
        echo "Error creating user.";
    }
    
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>