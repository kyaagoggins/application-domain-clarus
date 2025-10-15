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
    
    // Get form data and trim whitespace
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $repeatPassword = trim($_POST['repeatPassword'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $dateOfBirth = trim($_POST['dateOfBirth'] ?? '');
    $securityAnswer1 = trim($_POST['securityAnswer1'] ?? '');
    $securityAnswer2 = trim($_POST['securityAnswer2'] ?? '');
    $securityAnswer3 = trim($_POST['securityAnswer3'] ?? '');
    
    // Validate passwords if provided
    if (!empty($password) || !empty($repeatPassword)) {
        if ($password !== $repeatPassword) {
            die("Passwords do not match.");
        }
        
        if (empty($password)) {
            die("Password cannot be empty if repeat password is provided.");
        }
    }
    
    // Handle profile image upload
    $profile_image_uploaded = false;
    $profile_image_url = "";
    
    if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] == 0) {
        
        $target_dir = "uploads/profile_images/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $imageFileType = strtolower(pathinfo($_FILES['profileImage']['name'], PATHINFO_EXTENSION));
        $target_file = $target_dir . $user_id . "." . $imageFileType;
        
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
            $profile_image_uploaded = true;
        } else {
            die("Error uploading profile image.");
        }
    }
    
    try {
        // Create database connection
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Build dynamic UPDATE query based on non-empty fields
        $updateFields = [];
        $params = [];
        
        // Check each field and add to update if not empty
        if (!empty($firstName)) {
            $updateFields[] = "first_name = :first_name";
            $params[':first_name'] = $firstName;
        }
        
        if (!empty($lastName)) {
            $updateFields[] = "last_name = :last_name";
            $params[':last_name'] = $lastName;
        }
        
        if (!empty($email)) {
            $updateFields[] = "email = :email";
            $params[':email'] = $email;
        }
        
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $updateFields[] = "password_hash = :password_hash";
            $params[':password_hash'] = $password_hash;
            
            // Update last password reset time
            $updateFields[] = "last_password_reset_datetime = NOW()";
        }
        
        if (!empty($address)) {
            $updateFields[] = "address = :address";
            $params[':address'] = $address;
        }
        
        if (!empty($dateOfBirth)) {
            $updateFields[] = "date_of_birth = :date_of_birth";
            $params[':date_of_birth'] = $dateOfBirth;
        }
        
        if (!empty($securityAnswer1)) {
            $security_hash_1 = password_hash($securityAnswer1, PASSWORD_DEFAULT);
            $updateFields[] = "security_question_answer_1 = :security_answer_1";
            $params[':security_answer_1'] = $security_hash_1;
        }
        
        if (!empty($securityAnswer2)) {
            $security_hash_2 = password_hash($securityAnswer2, PASSWORD_DEFAULT);
            $updateFields[] = "security_question_answer_2 = :security_answer_2";
            $params[':security_answer_2'] = $security_hash_2;
        }
        
        if (!empty($securityAnswer3)) {
            $security_hash_3 = password_hash($securityAnswer3, PASSWORD_DEFAULT);
            $updateFields[] = "security_question_answer_3 = :security_answer_3";
            $params[':security_answer_3'] = $security_hash_3;
        }
        
        if ($profile_image_uploaded) {
            $updateFields[] = "profile_image_url = :profile_image_url";
            $params[':profile_image_url'] = $profile_image_url;
        }
        
        // Check if there are any fields to update
        if (empty($updateFields)) {
            echo "<div style='color: orange; padding: 10px; border: 1px solid #ffc107; background-color: #fff3cd; border-radius: 4px; margin: 10px;'>";
            echo "<strong>No Updates:</strong> No fields were provided for update. Please fill in at least one field to update your profile.";
            echo "<br><br><a href='complete_profile.php'>Back to Profile</a>";
            echo "</div>";
            exit;
        }
        
        // Always update the updated_at timestamp
        //$updateFields[] = "updated_at = NOW()";
        
        // Build final SQL query
        $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE username = :username";
        $params[':username'] = $username;
        
        $stmt = $pdo->prepare($sql);
        
        // Execute the update
        if ($stmt->execute($params)) {
            $rowsAffected = $stmt->rowCount();
            
            if ($rowsAffected > 0) {
                echo "<div style='color: green; padding: 15px; border: 1px solid #28a745; background-color: #d4edda; border-radius: 4px; margin: 10px;'>";
                echo "<h3>✅ Profile Updated Successfully!</h3>";
                echo "<strong>Username:</strong> " . htmlspecialchars($username) . "<br>";
                
                // Show which fields were updated
                echo "<strong>Updated Fields:</strong><br>";
                $updatedFieldsList = [];
                
                if (!empty($firstName)) $updatedFieldsList[] = "First Name: " . htmlspecialchars($firstName);
                if (!empty($lastName)) $updatedFieldsList[] = "Last Name: " . htmlspecialchars($lastName);
                if (!empty($email)) $updatedFieldsList[] = "Email: " . htmlspecialchars($email);
                if (!empty($password)) $updatedFieldsList[] = "Password: Updated";
                if (!empty($address)) $updatedFieldsList[] = "Address: Updated";
                if (!empty($dateOfBirth)) $updatedFieldsList[] = "Date of Birth: " . htmlspecialchars($dateOfBirth);
                if (!empty($securityAnswer1)) $updatedFieldsList[] = "Security Answer 1: Updated";
                if (!empty($securityAnswer2)) $updatedFieldsList[] = "Security Answer 2: Updated";
                if (!empty($securityAnswer3)) $updatedFieldsList[] = "Security Answer 3: Updated";
                if ($profile_image_uploaded) $updatedFieldsList[] = "Profile Image: " . htmlspecialchars(basename($profile_image_url));
                
                foreach ($updatedFieldsList as $field) {
                    echo "• " . $field . "<br>";
                }
                
                echo "<br><a href='dashboard.php' style='background-color: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Go to Dashboard</a> ";
                echo "<a href='complete_profile.php' style='background-color: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; margin-left: 10px;'>Edit Profile Again</a>";
                echo "</div>";
                
                // Log the profile update
                error_log("Profile updated for user: $username (ID: $user_id). Fields updated: " . count($updatedFieldsList));
                
            } else {
                echo "<div style='color: #856404; padding: 10px; border: 1px solid #ffc107; background-color: #fff3cd; border-radius: 4px; margin: 10px;'>";
                echo "<strong>No Changes:</strong> No changes were made to the profile. The provided data may be the same as existing data.";
                echo "<br><br><a href='complete_profile.php'>Back to Profile</a>";
                echo "</div>";
            }
        } else {
            echo "<div style='color: #721c24; padding: 10px; border: 1px solid #dc3545; background-color: #f8d7da; border-radius: 4px; margin: 10px;'>";
            echo "<strong>Error:</strong> Error updating profile. Please try again.";
            echo "<br><br><a href='complete_profile.php'>Back to Profile</a>";
            echo "</div>";
        }
        
    } catch(PDOException $e) {
        echo "<div style='color: #721c24; padding: 10px; border: 1px solid #dc3545; background-color: #f8d7da; border-radius: 4px; margin: 10px;'>";
        echo "<strong>Database Error:</strong> " . htmlspecialchars($e->getMessage());
        echo "<br><br><a href='complete_profile.php'>Back to Profile</a>";
        echo "</div>";
        
        // Log the error
        error_log("Profile update database error for user $username: " . $e->getMessage());
    }
    
} else {
    echo "<div style='color: #721c24; padding: 10px; border: 1px solid #dc3545; background-color: #f8d7da; border-radius: 4px; margin: 10px;'>";
    echo "<strong>Invalid Request:</strong> Invalid request method. Please use the profile form.";
    echo "<br><br><a href='complete_profile.php'>Back to Profile</a>";
    echo "</div>";
}
?>