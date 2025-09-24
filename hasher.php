<?php
// Set the password to test
$passwordTest = "Admin123!";

// Hash the password using PHP's built-in password_hash function
$hashedPassword = password_hash($passwordTest, PASSWORD_DEFAULT);

// Display the results
echo "Original Password: " . $passwordTest . "<br>";
echo "Hashed Password: " . $hashedPassword . "<br>";
echo "Hash Length: " . strlen($hashedPassword) . " characters<br>";

// Optional: Show that verification works
if (password_verify($passwordTest, $hashedPassword)) {
    echo "Password verification: SUCCESS<br>";
} else {
    echo "Password verification: FAILED<br>";
}
?>