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

// Include database configuration and fetch user data
include '../db_connect.php';

$userData = [];
$profileExists = false;

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch user data from database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userData) {
        $profileExists = !empty($userData['first_name']) && !empty($userData['last_name']) && 
                        !empty($userData['email']) && !empty($userData['address']);
    }
    
} catch(PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    // Continue with empty userData array
}

// Helper function to safely get user data
function getUserData($field, $default = '') {
    global $userData;
    return isset($userData[$field]) && $userData[$field] !== null ? htmlspecialchars($userData[$field]) : $default;
}

// Helper function to format date for input field
function formatDateForInput($date) {
    if (empty($date) || $date === '0000-00-00') {
        return '';
    }
    return date('Y-m-d', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title><?php echo $profileExists ? 'Update Your Profile' : 'Complete Your Profile'; ?></title>
</head>
<body>
    <div class="container" style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none; -ms-overflow-style: none;">
    <?php include 'header.php'; ?>
    
    <h1><?php echo $profileExists ? 'Update Your Profile' : 'Complete Your Profile'; ?></h1>
    
    <?php if ($profileExists): ?>
    <div style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
        <strong>Profile Found:</strong> Your existing profile information has been loaded. Update any fields as needed.
    </div>
    <?php else: ?>
    <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
        <strong>Complete Your Profile:</strong> Please fill in all required information to complete your profile.
    </div>
    <?php endif; ?>
    
    <form action="/update_profile.php" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
        <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
        <input type="hidden" name="profile_exists" value="<?php echo $profileExists ? '1' : '0'; ?>">
        
        <div>
            <label for="firstName">First Name: <span style="color: red;">*</span></label>
            <input type="text" id="firstName" name="firstName" 
                   value="<?php echo getUserData('first_name'); ?>" required>
        </div>
        <br>
        
        <div>
            <label for="lastName">Last Name: <span style="color: red;">*</span></label>
            <input type="text" id="lastName" name="lastName" 
                   value="<?php echo getUserData('last_name'); ?>" required>
        </div>
        <br>
        
        <div>
            <label for="email">Email: <span style="color: red;">*</span></label>
            <input type="email" id="email" name="email" 
                   value="<?php echo getUserData('email'); ?>" required>
        </div>
        <br>
        
        <div>
            <label for="password">New Password: <?php echo $profileExists ? '(Leave blank to keep current)' : '<span style="color: red;">*</span>'; ?></label>
            <input type="password" id="password" name="password" 
                   <?php echo $profileExists ? '' : 'required'; ?> onblur="validatePassword()">
            <div id="passwordError" style="color: red; font-size: 12px; margin-top: 5px;"></div>
            <div id="passwordRequirements" style="color: #666; font-size: 11px; margin-top: 3px;">
                Password must be at least 8 characters, start with a letter, and contain a letter, number, and special character.
            </div>
        </div>
        <br>
        
        <div>
            <label for="repeatPassword">Repeat Password:</label>
            <input type="password" id="repeatPassword" name="repeatPassword" 
                   <?php echo $profileExists ? '' : 'required'; ?> onblur="validatePasswordMatch()">
            <div id="passwordMatchError" style="color: red; font-size: 12px; margin-top: 5px;"></div>
        </div>
        <br>
        
        <div>
            <label for="profileImage">Profile Image:</label>
            <input type="file" id="profileImage" name="profileImage" accept="image/*">
            <?php if (file_exists("/uploads/profile_images/{$userId}.jpg")): ?>
            <div style="margin-top: 10px;">
                <small>Current profile image:</small><br>
                <img src="/uploads/profile_images/<?php echo $userId; ?>.jpg" style="width:100px; border-radius: 8px; border: 2px solid #ddd;">
            </div>
            <?php endif; ?>
        </div>
        <br>
        
        <div>
            <label for="address">Address: <span style="color: red;">*</span></label>
            <textarea id="address" name="address" rows="3" cols="50" required><?php echo getUserData('address'); ?></textarea>
        </div>
        <br>
        
        <div>
            <label for="dateOfBirth">Date of Birth: <span style="color: red;">*</span></label>
            <input type="date" id="dateOfBirth" name="dateOfBirth" 
                   value="<?php echo formatDateForInput(getUserData('date_of_birth')); ?>" required>
            <?php if (!empty($userData['date_of_birth']) && $userData['date_of_birth'] !== '0000-00-00'): ?>
            <small style="color: #666; margin-left: 10px;">
                Current: <?php echo date('F j, Y', strtotime($userData['date_of_birth'])); ?>
            </small>
            <?php endif; ?>
        </div>
        <br>
        
        <div>
            <label for="securityAnswer1">Security Question Answer 1: <span style="color: red;"></span></label>
            <input type="text" id="securityAnswer1" name="securityAnswer1" 
                   placeholder="What was your first pet's name?" 
                   value="">
        </div>
        <br>
        
        <div>
            <label for="securityAnswer2">Security Question Answer 2: <span style="color: red;"></span></label>
            <input type="text" id="securityAnswer2" name="securityAnswer2" 
                   placeholder="What city were you born in?" 
                   value="">
        </div>
        <br>
        
        <div>
            <label for="securityAnswer3">Security Question Answer 3: <span style="color: red;"></span></label>
            <input type="text" id="securityAnswer3" name="securityAnswer3" 
                   placeholder="What was your mother's maiden name?" 
                   value="">
        </div>
        <br>
        
        <?php if ($profileExists): ?>
        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 4px; margin: 20px 0;">
            <h4>Profile Information:</h4>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($userData['username'] ?? 'N/A'); ?></p>
            <p><strong>Access Level:</strong> <?php echo htmlspecialchars($userData['access_level'] ?? 'N/A'); ?></p>
            <p><strong>Account Status:</strong> <?php echo ($userData['active'] ?? 1) ? 'Active' : 'Inactive'; ?></p>
            <p><strong>Profile Created:</strong> <?php echo !empty($userData['created_at']) ? date('F j, Y g:i A', strtotime($userData['created_at'])) : 'N/A'; ?></p>
            <?php if (!empty($userData['last_login_datetime'])): ?>
            <p><strong>Last Login:</strong> <?php echo date('F j, Y g:i A', strtotime($userData['last_login_datetime'])); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div>
            <input type="submit" value="<?php echo $profileExists ? 'Update Profile' : 'Complete Profile'; ?>" 
                   style="background-color: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 4px; font-size: 16px; cursor: pointer;">
            <?php if ($profileExists): ?>
            <br/>
            <br/>
            <a href="dashboard.php" style="margin-left: 10px; padding: 12px 24px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 4px;">Cancel</a>
            <?php endif; ?>
        </div>
    </form>

<script>
function validatePassword() {
    const password = document.getElementById('password').value;
    const errorDiv = document.getElementById('passwordError');
    const profileExists = <?php echo $profileExists ? 'true' : 'false'; ?>;
    
    // Clear previous error
    errorDiv.innerHTML = '';
    
    // If profile exists and password is empty, skip validation
    if (profileExists && password === '') {
        return true;
    }
    
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
    const profileExists = <?php echo $profileExists ? 'true' : 'false'; ?>;
    
    // If profile exists and both passwords are empty, that's okay
    if (profileExists && password === '' && repeatPassword === '') {
        errorDiv.innerHTML = '';
        return true;
    }
    
    // If one is empty but not the other
    if ((password === '') !== (repeatPassword === '')) {
        errorDiv.innerHTML = 'Both password fields must be filled or both must be empty.';
        return false;
    }
    
    if (password !== repeatPassword) {
        errorDiv.innerHTML = 'Passwords do not match.';
        return false;
    } else if (repeatPassword !== '') {
        errorDiv.innerHTML = '<span style="color: green;">✓ Passwords match</span>';
    }
    return true;
}

function validateForm() {
    const profileExists = <?php echo $profileExists ? 'true' : 'false'; ?>;
    const password = document.getElementById('password').value;
    
    // For existing profiles, password is optional
    if (!profileExists || password !== '') {
        const passwordValid = validatePassword();
        if (!passwordValid) {
            alert('Please fix the password requirements before submitting.');
            return false;
        }
    }
    
    const passwordMatchValid = validatePasswordMatch();
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
    } else {
        document.getElementById('passwordError').innerHTML = '';
    }
});

document.getElementById('repeatPassword').addEventListener('input', function() {
    validatePasswordMatch();
});

// Show confirmation for profile updates
<?php if ($profileExists): ?>
document.querySelector('form').addEventListener('submit', function(e) {
    if (!confirm('Are you sure you want to update your profile with these changes?')) {
        e.preventDefault();
        return false;
    }
});
<?php endif; ?>
</script>
</body>
</html>