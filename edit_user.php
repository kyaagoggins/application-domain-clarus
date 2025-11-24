<?php
//KSU student project for Clarus Accounting tool
//This page is used by admins to edit an existing system user
//Initially drafted by Eric Poole
//Meets sprint 1 requirements

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: home.html?error=session_expired');
    exit;
}

// Check session timeout
if (isset($_SESSION['expires']) && time() > $_SESSION['expires']) {
    session_destroy();
    header('Location: home.html?error=session_expired');
    exit;
}
$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];
$current_username = $_SESSION['username'];
$current_user_id = $_SESSION['user_id'];

// Get user_id from URL parameter
$edit_user_id = $_GET['user_id'];

if (!$edit_user_id) {
    die("Hmm.. something went wrong. No user id was found in the URL. Please go back and try again.");
}

// Database configuration
include '../db_connect.php';


// Create database connection
$pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
// Get user information
$getUserData = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id");
$getUserData->bindParam(':user_id', $edit_user_id);
$getUserData->execute();
    
$user_data = $getUserData->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_data) {
        die("Hmm.. the specified user was not found. Please go back and try again.");
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Edit User Profile</title>
</head>
<body>
    <div class="container" style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none; -ms-overflow-style: none;">
    <!--Disabling the original icon, Kyaa is creating a new one and will include it in the header file -->
    <!--<img src="https://thumbs.dreamstime.com/b/calculator-icon-vector-isolated-white-background-your-web-mobile-app-design-calculator-logo-concept-calculator-icon-134617239.jpg" width="100px">-->
    <?php include 'header.php'; ?>
    <h1>Edit Profile: <?php echo $user_data['first_name'] . ' ' . $user_data['last_name']; ?></h1>
    
    <form action="push_user_edits.php" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
        <!-- Hidden field to pass the user_id being edited -->
        <input type="hidden" name="edit_user_id" value="<?php echo $edit_user_id; ?>">
        
        <div>
            <label for="firstName">First Name:</label>
            <input type="text" id="firstName" name="firstName" value="<?php echo $user_data['first_name']; ?>" required>
        </div>
    <br>
    <div>
        <label for="lastName">Last Name:</label>
        <input type="text" id="lastName" name="lastName" value="<?php echo $user_data['last_name']; ?>" required>
    </div>
    <br>
    <div>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?php echo $user_data['email']; ?>" required>
    </div>
    <br>
    <div>
        <label for="accessLevel">Access Level:</label>
        <select name="accessLevel" id="accessLevel" required>
            <option value="">Choose Role</option>
            <!--The option elements use php to reference the table to see which element to select by default on page load, the selected attribute is added to the item that matches the db table -->
            <option value="3" <?php echo $user_data['access_level'] == '3' ? 'selected' : ''; ?>>Administrator</option>
            <option value="2" <?php echo $user_data['access_level'] == '2' ? 'selected' : ''; ?>>Manager</option>
            <option value="1" <?php echo $user_data['access_level'] == '1' ? 'selected' : ''; ?>>Accountant</option>
        </select>
    </div>
    <br>
    <div>
        <label for="password">New Password (leave blank to keep current):</label>
        <input type="password" id="password" name="password" onblur="validatePassword()">
        <div id="passwordError" style="color: red; font-size: 12px; margin-top: 5px;"></div>
        <div id="passwordRequirements" style="color: #666; font-size: 11px; margin-top: 3px;">
            Password must be at least 8 characters, start with a letter, and contain a letter, number, and special character.
        </div>
    </div>
    <br>
    <div>
        <label for="repeatPassword">Repeat New Password:</label>
        <input type="password" id="repeatPassword" name="repeatPassword" onblur="validatePasswordMatch()">
        <div id="passwordMatchError" style="color: red; font-size: 12px; margin-top: 5px;"></div>
    </div>
    <br>
    <div>
        <label for="profileImage">Profile Image:</label>
        <input type="file" id="profileImage" name="profileImage" accept="image/*">
        <?php if (!empty($user_data['profile_image_url'])): ?>
            <br><small>Current image: <?php echo $user_data['profile_image_url']; ?></small>
        <?php endif; ?>
    </div>
    <br>
    <div>
        <label for="address">Address:</label>
        <textarea id="address" name="address" rows="3" cols="50" required><?php echo $user_data['address']; ?></textarea>
    </div>
    <br>
    <div>
        <label for="dateOfBirth">Date of Birth:</label>
        <input type="date" id="dateOfBirth" name="dateOfBirth" value="<?php echo $user_data['date_of_birth']; ?>" required>
    </div>
    <br>
    <div>
        <label for="active">Account Status:</label>
        <select name="active" id="active" required>
            <!--The option elements use php to reference the table to see which element to select by default on page load, the selected attribute is added to the item that matches the db table -->
            <option value="1" <?php echo $user_data['active'] == 1 ? 'selected' : ''; ?>>Active</option>
            <option value="0" <?php echo $user_data['active'] == 0 ? 'selected' : ''; ?>>Inactive</option>
        </select>
    </div>
    <br>
    <div style="display:none">
    <div>
        <label for="securityAnswer1">Security Question Answer 1:</label>
        <input type="text" id="securityAnswer1" name="securityAnswer1" placeholder="What was your first pet's name?">
        <small style="color: #666;">Leave blank to keep current answer</small>
    </div>
    <br>
    <div>
        <label for="securityAnswer2">Security Question Answer 2:</label>
        <input type="text" id="securityAnswer2" name="securityAnswer2" placeholder="What city were you born in?">
        <small style="color: #666;">Leave blank to keep current answer</small>
    </div>
    <br>
    <div>
        <label for="securityAnswer3">Security Question Answer 3:</label>
        <input type="text" id="securityAnswer3" name="securityAnswer3" placeholder="What was your mother's maiden name?">
        <small style="color: #666;">Leave blank to keep current answer</small>
    </div>
    </div>
    <br>
    <div>
        <input type="submit" value="Update Profile">
        <a href="dashboard.php" style="margin-left: 20px; color: #666;">Cancel</a>
    </div>
</form>

</div>
<script>
//this function checks that all of the assigned password security requirements are met before the user can submit 
function validatePassword() {
    const password = document.getElementById('password').value;
    const errorDiv = document.getElementById('passwordError');
    
    // Clear previous error
    errorDiv.innerHTML = '';
    
    // If password is empty, it's optional for updates
    if (password === '') {
        return true;
    }
    
    // Check minimum length (8 characters)
    if (password.length < 8) {
        errorDiv.innerHTML = 'Password needs to be at least 8 characters long.';
        return false;
    }
    
    // Check if starts with a letter
    if (!/^[a-zA-Z]/.test(password)) {
        errorDiv.innerHTML = 'Passwords need to start with a letter.';
        return false;
    }
    
    // Check if contains at least one letter
    if (!/[a-zA-Z]/.test(password)) {
        errorDiv.innerHTML = 'Passwords need to contain at least one letter.';
        return false;
    }
    
    // Check if contains at least one number
    if (!/[0-9]/.test(password)) {
        errorDiv.innerHTML = 'Passwords need to contain at least one number.';
        return false;
    }
    
    // Check if contains at least one special character
    if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
        errorDiv.innerHTML = 'Passwords need to contain at least one special character.';
        return false;
    }
    
    // If all checks pass
    errorDiv.innerHTML = '<span style="color: green;">Nice! This password meets all security requirements</span>';
    return true;
}

function validatePasswordMatch() {
    const password = document.getElementById('password').value;
    const repeatPassword = document.getElementById('repeatPassword').value;
    const errorDiv = document.getElementById('passwordMatchError');
    
    // If both are empty, that's fine (keeping current password)
    if (password === '' && repeatPassword === '') {
        return true;
    }
    
    if (password !== repeatPassword) {
        errorDiv.innerHTML = 'Opps.. these passwords do not match.';
        return false;
    } else if (repeatPassword !== '') {
        errorDiv.innerHTML = '<span style="color: green;">Passwords match</span>';
    }
    return true;
}

function validateForm() {
    const passwordValid = validatePassword();
    const passwordMatchValid = validatePasswordMatch();
    
    if (!passwordValid) {
        alert('Please fix the password requirements before submitting.');
        return false;
    }
    
    if (!passwordMatchValid) {
        alert('Please ensure passwords match before submitting.');
        return false;
    }
    
    return true; // Allow form submission
}

// Real-time validation as user types
document.getElementById('password').addEventListener('input', function() {
    if (this.value.length > 0) {
        validatePassword();
    }
});

document.getElementById('repeatPassword').addEventListener('input', function() {
    validatePasswordMatch();
});
</script>
</body>
</html>