<?php
include '../db_connect.php';

function generateUsername($firstName, $lastName) {
    // Get first initial (uppercase)
    $firstInitial = strtoupper(substr(trim($firstName), 0, 1));
    
    // Get last name (capitalize first letter, lowercase rest)
    $lastName = ucfirst(strtolower(trim($lastName)));
    
    // Get current month and year (MMYY format)
    $month = date('m'); // Two digit month (01-12)
    $year = date('y');  // Two digit year (24 for 2024)
    
    // Combine: FirstInitial + LastName + MMYY
    return $firstInitial . $lastName . $month . $year;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if ($_POST['password'] !== $_POST['repeatPassword']) {
        die("Passwords do not match.");
    }
    
    // Generate username using the new rules
    $base_username = generateUsername($_POST['firstName'], $_POST['lastName']);
    
    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $pdo->beginTransaction();
        
        // Ensure username is unique
        $username = $base_username;
        $counter = 1;
        
        while (true) {
            $check_stmt = $pdo->prepare("SELECT username FROM users WHERE username = :username");
            $check_stmt->execute([':username' => $username]);
            
            if ($check_stmt->rowCount() == 0) {
                break; // Username is unique
            }
            
            // If username exists, add number suffix
            $username = $base_username . $counter;
            $counter++;
        }
        
        // Insert user record
        $stmt = $pdo->prepare("
            INSERT INTO users (
                username, first_name, last_name, email, password_hash, 
                address, date_of_birth, access_level, active, 
                unsuccessful_login_attempts, created_at
            ) VALUES (
                :username, :first_name, :last_name, :email, :password_hash, 
                :address, :date_of_birth, :access_level, TRUE, 
                0, NOW()
            )
        ");
        
        $stmt->execute([
            ':username' => $username,
            ':first_name' => $_POST['firstName'],
            ':last_name' => $_POST['lastName'],
            ':email' => $_POST['email'],
            ':password_hash' => password_hash($_POST['password'], PASSWORD_DEFAULT),
            ':address' => $_POST['address'],
            ':date_of_birth' => $_POST['dateOfBirth'],
            ':access_level' => $_POST['accessLevel']
        ]);
        
        $user_id = $pdo->lastInsertId();
        
        // Handle image upload with user_id filename
        $profile_image_info = "";
        if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] == 0) {
            $target_dir = "uploads/profile_images/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            
            $imageFileType = strtolower(pathinfo($_FILES['profileImage']['name'], PATHINFO_EXTENSION));
            $target_file = $target_dir . $user_id . "." . $imageFileType;
            
            if (move_uploaded_file($_FILES['profileImage']['tmp_name'], $target_file)) {
                $update_stmt = $pdo->prepare("UPDATE users SET profile_image_url = :profile_image_url WHERE user_id = :user_id");
              /*  $update_stmt->execute([
                    ':profile_image_url' => $target_file,
                    ':user_id' => $user_id
                ]);
            */    
                $profile_image_info = "Profile Image: " . $user_id . "." . $imageFileType . "<br>";
            }
        }
        
        $pdo->commit();
        
        echo "✅ USER CREATED SUCCESSFULLY!<br><br>";
        echo "<strong>Account Details:</strong><br>";
        echo "User ID: " . $user_id . "<br>";
        echo "Username: <strong>" . htmlspecialchars($username) . "</strong><br>";
        echo "Name: " . htmlspecialchars($_POST['firstName']) . " " . htmlspecialchars($_POST['lastName']) . "<br>";
        echo "Email: " . htmlspecialchars($_POST['email']) . "<br>";
        echo "Access Level: " . htmlspecialchars($_POST['accessLevel']) . "<br>";
        echo $profile_image_info;
        echo "<br><em>Username Format: " . strtoupper(substr($_POST['firstName'], 0, 1)) . " + " . ucfirst($_POST['lastName']) . " + " . date('m/y') . "</em><br>";
        echo "<a href='dashboard.php'>Back to Admin Panel</a>";
        
    } catch(PDOException $e) {
        $pdo->rollBack();
        echo "❌ Error: " . ($e->getCode() == 23000 ? "Email already exists" : $e->getMessage());
    }
}
?>