<?php
//KSU student project for Clarus Accounting tool
//This page is used to reset a user's password
//This minimal interface is for a user resetting a password if they forgot
//Initially drafted by Eric Poole. Reviewed and updated by Kyaa Goggins

// Connect to the external database file
include '../db_connect.php';

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get the user input from the form submission
    $email = $_POST['email'];
    $securityAnswer1 = $_POST['securityAnswer1'];
    $securityAnswer2 = $_POST['securityAnswer2'];
    $securityAnswer3 = $_POST['securityAnswer3'];

    // Validate input is completely filled out
    if (empty($email) || empty($securityAnswer1) || empty($securityAnswer2) || empty($securityAnswer3)) {
        die("Oops.. one or more fields were missing and all are required. Please go back and try again.");
    }


    // Create thedatabase connection
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare SQL statement to get user data
    $getUserData = $pdo->prepare("SELECT username, security_question_answer_1, security_question_answer_2, security_question_answer_3 FROM users WHERE email = :email");
    $getUserData->bindParam(':email', $email);
    $getUserData->execute();

    // Get user data
    $user = $getUserData->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Verify all three security answers
        $answer1Match = password_verify($securityAnswer1, $user['security_question_answer_1']);
        $answer2Match = password_verify($securityAnswer2, $user['security_question_answer_2']);
        $answer3Match = password_verify($securityAnswer3, $user['security_question_answer_3']);

        if ($answer1Match && $answer2Match && $answer3Match) {
            // All security answers match
            //no action needed

        } else {
            // One or more answers don't match
            echo "Oops, something isn't right...<br>";
            echo "One or more of your security question answers didn't match. Please try again.<br>";

            // Login attempt failed, increment count up by 1
            $log_stmt = $pdo->prepare("UPDATE users SET unsuccessful_login_attempts = unsuccessful_login_attempts + 1 WHERE email = :email");
            $log_stmt->bindParam(':email', $email);
            $log_stmt->execute();
        }

    } else {
        // Email not found
        echo "Oops, something isn't right...<br>";
        echo "We didn't recognize this email address.";
    }


} else {
    // Display the form
    ?>
    <!DOCTYPE html>
    <html>
    <!-- header information needs to remain here because this is part of the application that does not include the navigation bar header with styling references -->

    <head>
        <title>Account Verification</title>
        <link rel="stylesheet" href="style.css">
    </head>

    <body>
        <div class="container"
            style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none; -ms-overflow-style: none;">
            <h2 class="logo"><img src="assets/logo.png" style="border: 1px solid black; border-radius: 5px; height:30px">
                <span>Clarus</span>
            </h2>
            <h1>Account Verification</h1>
            <p>Please provide your email and answer the security questions to verify your identity.</p>

            <form action="" method="POST">
                <div>
                    <label for="email">Email Address:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <br>
                <div>
                    <label for="securityAnswer1">Security Answer 1 (What was your first vacation spot?):</label>
                    <input type="text" id="securityAnswer1" name="securityAnswer1" required>
                </div>
                <br>
                <div>
                    <label for="securityAnswer2">Security Answer 2 (How old were you when you lost your first
                        tooth?):</label>
                    <input type="text" id="securityAnswer2" name="securityAnswer2" required>
                </div>
                <br>
                <div>
                    <label for="securityAnswer3">Security Answer 3 (What is your favorite animal?):</label>
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