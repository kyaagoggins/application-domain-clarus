<?php
//KSU student project for Clarus Accounting tool
//This page is used to push the edits an admin makes to a user's profile
//Initially drafted by Eric Poole. Reviewed and updated by Kyaa Goggins

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: home.html?error=session_expired');
    exit;
}

// Database configuration
include '../db_connect.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get form data
    $edit_user_id = $_POST['edit_user_id'];
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['email'];
    $accessLevel = $_POST['accessLevel'];
    $password = $_POST['password'];
    $repeatPassword = $_POST['repeatPassword'];
    $address = $_POST['address'];
    $dateOfBirth = $_POST['dateOfBirth'];
    $active = $_POST['active'];
    $securityAnswer1 = $_POST['securityAnswer1'];
    $securityAnswer2 = $_POST['securityAnswer2'];
    $securityAnswer3 = $_POST['securityAnswer3'];



    // Validate passwords match if provided
    if (!empty($password)) {
        if ($password !== $repeatPassword) {
            die("The passwords provided did not match. Please go back and try again.");
        }
    }

    // Create database connection
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Begin transaction
    $pdo->beginTransaction();

    // Check if user exists
    $checkUser = $pdo->prepare("SELECT user_id FROM users WHERE user_id = :user_id");
    $checkUser->execute([':user_id' => $edit_user_id]);
    $existing_user = $checkUser->fetch(PDO::FETCH_ASSOC);

    if (!$existing_user) {
        echo "Oops... An error occured. This user was not found. Please try again.";
        exit;
    }

    // Handle profile image upload
    //$profile_image_url = $existing_user['profile_image_url']; // Keep current image by default
    if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] == 0) {

        $target_dir = "uploads/profile_images/";

        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $imageFileType = strtolower(pathinfo($_FILES['profileImage']['name'], PATHINFO_EXTENSION));
        $target_file = $target_dir . $edit_user_id . "." . $imageFileType;

        // Validate image
        $check = getimagesize($_FILES['profileImage']['tmp_name']);
        if ($check === false) {
            throw new Exception("File is not an image.");
        }

        // Check file size (5MB limit)
        if ($_FILES['profileImage']['size'] > 5000000) {
            throw new Exception("File is too large. Maximum size is 5MB.");
        }

        // Allow certain file formats
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
            throw new Exception("Only JPG, JPEG, PNG & GIF files are allowed.");
        }

        // Remove existing files for this user_id
        $existingFiles = glob($target_dir . $edit_user_id . ".*");
        foreach ($existingFiles as $existingFile) {
            unlink($existingFile);
        }

        // Upload file
        if (move_uploaded_file($_FILES['profileImage']['tmp_name'], $target_file)) {
            $profile_image_url = $target_file;
        } else {
            throw new Exception("Error uploading profile image.");
        }
    }

    // Build UPDATE query - start with basic fields
    $userUpdateSql = "UPDATE users SET 
                first_name = :first_name,
                last_name = :last_name,
                email = :email,
                access_level = :access_level,
                address = :address,
                date_of_birth = :date_of_birth,
                active = :active";

    $params = [
        ':first_name' => $firstName,
        ':last_name' => $lastName,
        ':email' => $email,
        ':access_level' => $accessLevel,
        ':address' => $address,
        ':date_of_birth' => $dateOfBirth,
        ':active' => $active,
        ':user_id' => $edit_user_id
    ];

    // Add password update if provided
    if (!empty($password)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $userUpdateSql .= ", password_hash = :password_hash, last_password_reset_datetime = NOW()";
        $params[':password_hash'] = $password_hash;
    }

    // Add security question updates if provided
    if (!empty($securityAnswer1)) {
        $userUpdateSql .= ", security_question_answer_1 = :security_answer_1";
        $params[':security_answer_1'] = password_hash($securityAnswer1, PASSWORD_DEFAULT);
    }
    if (!empty($securityAnswer2)) {
        $userUpdateSql .= ", security_question_answer_2 = :security_answer_2";
        $params[':security_answer_2'] = password_hash($securityAnswer2, PASSWORD_DEFAULT);
    }
    if (!empty($securityAnswer3)) {
        $userUpdateSql .= ", security_question_answer_3 = :security_answer_3";
        $params[':security_answer_3'] = password_hash($securityAnswer3, PASSWORD_DEFAULT);
    }

    $userUpdateSql .= " WHERE user_id = :user_id";

    // Prepare and execute the update
    $userUpdate = $pdo->prepare($userUpdateSql);
    $userUpdate->execute($params);

    if ($userUpdate->rowCount() > 0) {
        // Commit transaction
        $pdo->commit();

        // Success message and redirect
        echo "The user's profile was updated successfully!<br><br>";
        echo "<a href='edit_user.php?user_id=" . $edit_user_id . "'>Edit this user again</a> | ";
        echo "<a href='dashboard.php'>Back to User Management</a>";

    } else {
        $pdo->rollBack();
        echo "No changes were made to the profile. <a href='dashboard.php'>Return to the user dashboard.</a>";
    }

} else {
    echo "Invalid request method.";
}
?>