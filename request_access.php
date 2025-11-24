<?php
//KSU student project for Clarus Accounting tool
//This page is used to reset a user's password
//Initially drafted by Eric Poole

// Connect to the external database file
include '../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get the user's input
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = strtolower($_POST['email']);
    
    if (empty($firstName) || empty($lastName) || empty($email)) {
        die("Please complete all fields then try again.");
    }
    

    
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        

        // Add the new request to the request table
        $insertRequest = $pdo->prepare("
            INSERT INTO `new-user-requests` (first_name, last_name, email, approved, created_at, updated_at) 
            VALUES (:first_name, :last_name, :email, FALSE, NOW(), NOW())
        ");
        
        $performInsert = $insertRequest->execute([
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':email' => $email
        ]);
        
        if ($performInsert) {
            echo "Your request was submitted successfully! We will get back to you shortly. <a href='home.html'> Return home.</a>";
        } else {
            echo "Oops, something went wrong here, let's try that again!";
        }
        
    
} else {
    //display an error if there was an issue connecting
    echo "Hmm, something seems to have gone wrong, it's us, not you. Please try again later.";
}
?>