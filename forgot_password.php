<?php
// Database configuration
include '../db_connect.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get form data
    $email = $_POST['email'];
    $securityAnswer1 = $_POST['securityAnswer1'];
    $securityAnswer2 = $_POST['securityAnswer2'];
    $securityAnswer3 = $_POST['securityAnswer3'];
    
    // Validate input
    if (empty($email) || empty($securityAnswer1) || empty($securityAnswer2) || empty($securityAnswer3)) {
        die("All fields are required.");
    }
    
    try {
        // Create database connection
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Prepare SQL statement to get user data
        $stmt = $pdo->prepare("SELECT username, security_question_answer_1, security_question_answer_2, security_question_answer_3 FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        // Get user data
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Verify all three security answers
            $answer1Match = password_verify($securityAnswer1, $user['security_question_answer_1']);
            $answer2Match = password_verify($securityAnswer2, $user['security_question_answer_2']);
            $answer3Match = password_verify($securityAnswer3, $user['security_question_answer_3']);
            
            if ($answer1Match && $answer2Match && $answer3Match) {
                // All security answers match
                echo "✅ VALIDATION SUCCESSFUL<br>";
                echo "Email: " . htmlspecialchars($email) . "<br>";
                echo "Username: " . htmlspecialchars($user['username']) . "<br>";
                echo "All security question answers match!<br>";
                echo "<br><strong>User identity verified.</strong>";
                
                // Optional: Set session or redirect to password reset
                session_start();
                $_SESSION['verified_user'] = $user['username'];
                $_SESSION['verified_email'] = $email;
                header("Location: reset_password.php");
                
            } else {
                // One or more answers don't match
                echo "❌ VALIDATION FAILED<br>";
                echo "One or more security question answers are incorrect.<br>";
                
                // Optional: Log failed attempt
                $log_stmt = $pdo->prepare("UPDATE users SET unsuccessful_login_attempts = unsuccessful_login_attempts + 1 WHERE email = :email");
                $log_stmt->bindParam(':email', $email);
                $log_stmt->execute();
            }
            
        } else {
            // Email not found
            echo "❌ VALIDATION FAILED<br>";
            echo "Email address not found in our records.";
        }
        
    } catch(PDOException $e) {
        echo "Database Error: " . $e->getMessage();
    }
    
} else {
    // Display the form
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Account Verification</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <div class="container" style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none; -ms-overflow-style: none;">
        <h2 class="logo"><img src="https://thumbs.dreamstime.com/b/calculator-icon-vector-isolated-white-background-your-web-mobile-app-design-calculator-logo-concept-calculator-icon-134617239.jpg" height="24px">
 <span>Clarus</span></h2>
        <h1>Account Verification</h1>
        <p>Please provide your email and answer the security questions to verify your identity.</p>
        
        <form action="" method="POST">
            <div>
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <br>
            <div>
                <label for="securityAnswer1">Security Answer 1 (What was your first pet's name?):</label>
                <input type="text" id="securityAnswer1" name="securityAnswer1" required>
            </div>
            <br>
            <div>
                <label for="securityAnswer2">Security Answer 2 (What city were you born in?):</label>
                <input type="text" id="securityAnswer2" name="securityAnswer2" required>
            </div>
            <br>
            <div>
                <label for="securityAnswer3">Security Answer 3 (What was your mother's maiden name?):</label>
                <input type="text" id="securityAnswer3" name="securityAnswer3" required>
            </div>
            <br>
            <div>
                <input type="submit" value="Verify Identity">
            </div>
        </form>
        </div>
    </body>
    </html>
    <?php
}
?>