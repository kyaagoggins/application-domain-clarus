<?php
/**
 * Complete Profile Page
 * This page is shown to users who need to complete their profile information
 */

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

$username = $_SESSION['username'] ?? 'User';
$userId = $_SESSION['user_id'];

// If accessed directly and profile is already complete, redirect to home
// In real implementation, you'd check the database here
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Complete Your Profile</title>
</head>
<body>
    <div class="container" style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none; -ms-overflow-style: none;">
    <!--<img src="https://thumbs.dreamstime.com/b/calculator-icon-vector-isolated-white-background-your-web-mobile-app-design-calculator-logo-concept-calculator-icon-134617239.jpg" width="100px">-->
    <h2 class="logo" style="float:left"><img src="https://thumbs.dreamstime.com/b/calculator-icon-vector-isolated-white-background-your-web-mobile-app-design-calculator-logo-concept-calculator-icon-134617239.jpg" height="24px">
 <span>Clarus</span></h2>
    <?php echo'<img src="/uploads/profile_images/'.$userId.'.jpg" style="width:50px; float: right; border-radius: 50%; border: 3px solid black"><div style="clear:both"></div><span style="float: right">'.$username.'</span>';?>
    <div style="clear:both"></div>
    <h1>Complete Your Profile</h1>
    <form action="/update_profile.php" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
        <div>
            <label for="firstName">First Name:</label>
            <input type="text" id="firstName" name="firstName" required>
        </div>
    <br>
    <div>
        <label for="lastName">Last Name:</label>
        <input type="text" id="lastName" name="lastName" required>
    </div>
    <br>
    <div>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
    </div>
    <br>
    <div>
        <label for="password">New Password:</label>
        <input type="password" id="password" name="password" required onblur="validatePassword()">
        <div id="passwordError" style="color: red; font-size: 12px; margin-top: 5px;"></div>
        <div id="passwordRequirements" style="color: #666; font-size: 11px; margin-top: 3px;">
            Password must be at least 8 characters, start with a letter, and contain a letter, number, and special character.
        </div>
    </div>
    <br>
    <div>
        <label for="repeatPassword">Repeat Password:</label>
        <input type="password" id="repeatPassword" name="repeatPassword" required onblur="validatePasswordMatch()">
        <div id="passwordMatchError" style="color: red; font-size: 12px; margin-top: 5px;"></div>
    </div>
    <br>
    <div>
        <label for="profileImage">Profile Image:</label>
        <input type="file" id="profileImage" name="profileImage" accept="image/*">
    </div>
    <br>
    <div>
        <label for="address">Address:</label>
        <textarea id="address" name="address" rows="3" cols="50" required></textarea>
    </div>
    <br>
    <div>
        <label for="dateOfBirth">Date of Birth:</label>
        <input type="date" id="dateOfBirth" name="dateOfBirth" required>
    </div>
    <br>
    <div>
        <label for="securityAnswer1">Security Question Answer 1:</label>
        <input type="text" id="securityAnswer1" name="securityAnswer1" placeholder="What was your first pet's name?" required>
    </div>
    <br>
    <div>
        <label for="securityAnswer2">Security Question Answer 2:</label>
        <input type="text" id="securityAnswer2" name="securityAnswer2" placeholder="What city were you born in?" required>
    </div>
    <br>
    <div>
        <label for="securityAnswer3">Security Question Answer 3:</label>
        <input type="text" id="securityAnswer3" name="securityAnswer3" placeholder="What was your mother's maiden name?" required>
    </div>
    <br>
    <div>
        <input type="submit" value="Complete Profile">
    </div>
</form>

<script>
function validatePassword() {
    const password = document.getElementById('password').value;
    const errorDiv = document.getElementById('passwordError');
    
    // Clear previous error
    errorDiv.innerHTML = '';
    
    // Check minimum length (8 characters)
    if (password.length < 8) {
        errorDiv.innerHTML = 'Password must be at least 8 characters long.';
        return false;
    }
    
    // Check if starts with a letter
    if (!/^[a-zA-Z]/.test(password)) {
        errorDiv.innerHTML = 'Password must start with a letter.';
        return false;
    }
    
    // Check if contains at least one letter
    if (!/[a-zA-Z]/.test(password)) {
        errorDiv.innerHTML = 'Password must contain at least one letter.';
        return false;
    }
    
    // Check if contains at least one number
    if (!/[0-9]/.test(password)) {
        errorDiv.innerHTML = 'Password must contain at least one number.';
        return false;
    }
    
    // Check if contains at least one special character
    if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
        errorDiv.innerHTML = 'Password must contain at least one special character (!@#$%^&*()_+-=[]{}|;:,.<>?).';
        return false;
    }
    
    // If all checks pass
    errorDiv.innerHTML = '<span style="color: green;">✓ Password meets all requirements</span>';
    return true;
}

function validatePasswordMatch() {
    const password = document.getElementById('password').value;
    const repeatPassword = document.getElementById('repeatPassword').value;
    const errorDiv = document.getElementById('passwordMatchError');
    
    if (password !== repeatPassword) {
        errorDiv.innerHTML = 'Passwords do not match.';
        return false;
    } else if (repeatPassword !== '') {
        errorDiv.innerHTML = '<span style="color: green;">✓ Passwords match</span>';
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
    if (this.value.length > 0) {
        validatePasswordMatch();
    }
});
</script>
</body>
</html>
