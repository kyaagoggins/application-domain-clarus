<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    die("Access denied. Please log in first.");
}

// Database configuration
include '../db_connect.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get session username
    $username = $_SESSION['username'];
    $user_id = $_SESSION['user_id'];
    
    // Get form data
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $repeatPassword = $_POST['repeatPassword'];
    $address = $_POST['address'];
    $dateOfBirth = $_POST['dateOfBirth'];
    $securityAnswer1 = $_POST['securityAnswer1'];
    $securityAnswer2 = $_POST['securityAnswer2'];
    $securityAnswer3 = $_POST['securityAnswer3'];
    
    // Validate passwords match
    if ($password !== $repeatPassword) {
        die("Passwords do not match.");
    }
    
    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Hash security question answers for security
    $security_hash_1 = password_hash($securityAnswer1, PASSWORD_DEFAULT);
    $security_hash_2 = password_hash($securityAnswer2, PASSWORD_DEFAULT);
    $security_hash_3 = password_hash($securityAnswer3, PASSWORD_DEFAULT);
    
    // Handle profile image upload
    $profile_image_url = "";
    if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] == 0) {
        
        $target_dir = "uploads/profile_images/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $imageFileType = strtolower(pathinfo($_FILES['profileImage']['name'], PATHINFO_EXTENSION));
        //$target_file = $target_dir . $username . "_" . time() . "." . $imageFileType;
        $target_file = $target_dir . $user_id ."." . $imageFileType;
        
        // Check if image file is valid
        $check = getimagesize($_FILES['profileImage']['tmp_name']);
        if($check === false) {
            die("File is not an image.");
        }
        
        // Check file size (limit to 5MB)
        if ($_FILES['profileImage']['size'] > 5000000) {
            die("File is too large. Maximum size is 5MB.");
        }
        
        // Allow certain file formats
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
            die("Only JPG, JPEG, PNG & GIF files are allowed.");
        }
        
        // Upload file
        if (move_uploaded_file($_FILES['profileImage']['tmp_name'], $target_file)) {
            $profile_image_url = $target_file;
        } else {
            die("Error uploading profile image.");
        }
    }
    
    try {
        // Create database connection
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Prepare UPDATE statement
        $sql = "UPDATE users SET 
                first_name = :first_name,
                last_name = :last_name,
                email = :email,
                password_hash = :password_hash,
                address = :address,
                date_of_birth = :date_of_birth,
                security_question_answer_1 = :security_answer_1,
                security_question_answer_2 = :security_answer_2,
                security_question_answer_3 = :security_answer_3";
        
        // Add profile image to query if uploaded
        //if (!empty($profile_image_url)) {
            //$sql .= ", profile_image_url = :profile_image_url";
        //}
        
        $sql .= " WHERE username = :username";
        
        $stmt = $pdo->prepare($sql);
        
        // Bind parameters
        $stmt->bindParam(':first_name', $firstName);
        $stmt->bindParam(':last_name', $lastName);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':date_of_birth', $dateOfBirth);
        $stmt->bindParam(':security_answer_1', $security_hash_1);
        $stmt->bindParam(':security_answer_2', $security_hash_2);
        $stmt->bindParam(':security_answer_3', $security_hash_3);
        $stmt->bindParam(':username', $username);
        
        //if (!empty($profile_image_url)) {
            //$stmt->bindParam(':profile_image_url', $profile_image_url);
        //}
        
        // Execute the update
        if ($stmt->execute()) {
            $rowsAffected = $stmt->rowCount();
            
            if ($rowsAffected > 0) {
                echo "Profile updated successfully!<br>";
                echo "Username: " . htmlspecialchars($username) . "<br>";
                echo "Name: " . htmlspecialchars($firstName) . " " . htmlspecialchars($lastName) . "<br>";
                echo "Email: " . htmlspecialchars($email) . "<br>";
                if (!empty($profile_image_url)) {
                    echo "Profile Image: " . htmlspecialchars($profile_image_url) . "<br>";
                }
                echo "<br><a href='dashboard.php'>Go to Dashboard</a>";
            } else {
                echo "No changes were made to the profile.";
            }
        } else {
            echo "Error updating profile.";
        }
        
    } catch(PDOException $e) {
        echo "Database Error: " . $e->getMessage();
    }
    
} else {
    echo "Invalid request method.";
}
?>