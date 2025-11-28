<?php
//KSU student project for Clarus Accounting tool
//This page is used to push updates to a user's profile
//Initially drafted by Eric Poole, reviewed and edited by Kiana Knight

session_start();

// Check if user is already logged in
if (!isset($_SESSION['username'])) {
    die("It looks like your login timed out. Please log in first.");
}

// Connect to the external database file
include '../db_connect.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get session username
    $username = $_SESSION['username'];
    $user_id = $_SESSION['user_id'];

    // Get form data and trim whitespace
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

    // Update profile image if one was uploaded
    $profile_image_uploaded = false;
    $profile_image_url = "";

    if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] == 0) {

        $target_dir = "uploads/profile_images/";

        $imageFileType = strtolower(pathinfo($_FILES['profileImage']['name'], PATHINFO_EXTENSION));
        $target_file = $target_dir . $user_id . "." . $imageFileType;

        // Check if image file is valid
        $check = getimagesize($_FILES['profileImage']['tmp_name']);
        if ($check === false) {
            die("Hmm... it looks like this file was not an image.");
        }

        // Check file size (limit to 5MB)
        if ($_FILES['profileImage']['size'] > 5000000) {
            die("Oops, this file is too large. The maximum size is 5MB.");
        }

        // Only allow these certain file formats
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
            die("Oops.. Only these file formats are allowed: JPG, JPEG, PNG & GIF files.");
        }

        // Upload file
        if (move_uploaded_file($_FILES['profileImage']['tmp_name'], $target_file)) {
            $profile_image_url = $target_file;
            $profile_image_uploaded = true;
        } else {
            die("Oops, we hit a snap uploading the profile image. Please try again.");
        }
    }

    // Create database connection
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Build dynamic UPDATE query based on non-empty fields
    $updateFields = [];
    $params = [];

    // Check each field and add to update if not empty
    //verification handling here so no incorrect or empty fields are sent to post
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

    // Always update the updated_at timestamp
    //EP: Commenting this out on 10/21 due to a DB issue I am debugging
    //$updateFields[] = "updated_at = NOW()";

    // Build final SQL query
    $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE username = :username";
    $params[':username'] = $username;

    $insertProfileChanges = $pdo->prepare($sql);

    // Execute the update
    if ($insertProfileChanges->execute($params)) {
        echo "<div style='color: green; padding: 15px; border: 1px solid #28a745; background-color: #d4edda; border-radius: 4px; margin: 10px;'><br><a href='landing.php' style='background-color: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Return home.</a></div>";
    }
}
?>