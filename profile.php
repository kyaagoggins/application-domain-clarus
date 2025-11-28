<?php

//KSU student project for Clarus Accounting tool
//This page is used to make updates to the user's profile
//Initially drafted by Eric Poole. Reviewed and updated by Kiana Knight

//begin the logged in session
session_start();

// Check if the user is already logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: home.html?error=session_expired');
    exit;
}

// Check if the session timed out, if so redirect home
if (isset($_SESSION['expires']) && time() > $_SESSION['expires']) {
    session_destroy();
    header('Location: home.html?error=session_expired');
    exit;
}

//set variables stored in the session
$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];

// Connect to the external database file
include '../db_connect.php';

$userData = [];


$pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fetch user data from database
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id");
$stmt->execute([':user_id' => $userId]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

// This function gets the existing users data and stores it in variables for later use
function getUserData($field, $default = '')
{
    global $userData;
    return isset($userData[$field]) && $userData[$field] !== null ? htmlspecialchars($userData[$field]) : $default;
}

include 'header.php';
?>

<div class="container" style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none; ">

    <h1>Edit your User Profile</h1>

    <form action="/update_profile.php" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
        <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
        <!--<input type="hidden" name="profile_exists" value="1">-->

        <div>
            <label for="firstName">First Name: <span style="color: red;">*</span></label>
            <input type="text" id="firstName" name="firstName" value="<?php echo getUserData('first_name'); ?>"
                required>
        </div>
        <br>

        <div>
            <label for="lastName">Last Name: <span style="color: red;">*</span></label>
            <input type="text" id="lastName" name="lastName" value="<?php echo getUserData('last_name'); ?>" required>
        </div>
        <br>

        <div>
            <label for="email">Email: <span style="color: red;">*</span></label>
            <input type="email" id="email" name="email" value="<?php echo getUserData('email'); ?>" required>
        </div>
        <br>

        <div>
            <label for="password">New Password (leave this blank if you want to keep your current password):</label>
            <input type="password" id="password" name="password" onblur="validatePassword()">
            <div id="passwordError" style="color: red; font-size: 12px; margin-top: 5px;"></div>
            <div id="passwordRequirements" style="color: #666; font-size: 11px; margin-top: 3px;">
                Password must be at least 8 characters, start with a letter, and contain a letter, number, and
                special character.
            </div>
        </div>
        <br>

        <div>
            <label for="repeatPassword">Repeat Password:</label>
            <input type="password" id="repeatPassword" name="repeatPassword" onblur="validatePasswordMatch()">
            <div id="passwordMatchError" style="color: red; font-size: 12px; margin-top: 5px;"></div>
        </div>
        <br>

        <div>
            <label for="profileImage">Profile Image:</label>
            <input type="file" id="profileImage" name="profileImage" accept="image/*">
            <?php if (file_exists("/uploads/profile_images/{$userId}.jpg")): ?>
                <div style="margin-top: 10px;">
                    <small>Current profile image:</small><br>
                    <img src="/uploads/profile_images/<?php echo $userId; ?>.jpg"
                        style="width:100px; border-radius: 8px; border: 2px solid #ddd;">
                </div>
            <?php endif; ?>
        </div>
        <br>

        <div>
            <label for="address">Address: <span style="color: red;">*</span></label>
            <textarea id="address" name="address" rows="3" cols="50"
                required><?php echo getUserData('address'); ?></textarea>
        </div>
        <br>

        <div>
            <label for="dateOfBirth">Date of Birth: <span style="color: red;">*</span></label>
            <input type="date" id="dateOfBirth" name="dateOfBirth" value="<?php echo getUserData('date_of_birth'); ?>"
                required>

        </div>
        <br>

        <div>
            <label for="securityAnswer1">Security Question Answer 1: <span style="color: red;"></span></label>
            <input type="text" id="securityAnswer1" name="securityAnswer1"
                placeholder="Where was your first vacation spot?" value="">
        </div>
        <br>

        <div>
            <label for="securityAnswer2">Security Question Answer 2: <span style="color: red;"></span></label>
            <input type="text" id="securityAnswer2" name="securityAnswer2"
                placeholder="When did you lose your first tooth?" value="">
        </div>
        <br>

        <div>
            <label for="securityAnswer3">Security Question Answer 3: <span style="color: red;"></span></label>
            <input type="text" id="securityAnswer3" name="securityAnswer3" placeholder="What is your favorite animal?"
                value="">
        </div>
        <br>



        <div>
            <input type="submit" value="Save Changes"
                style="background-color: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 4px; font-size: 16px; cursor: pointer;">
            <br />
            <br />
            <a href="dashboard.php"
                style="margin-left: 10px; padding: 12px 24px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 4px;">Cancel</a>
        </div>
    </form>

    <script>
        function validatePassword() {
            const password = document.getElementById('password').value;
            const errorDiv = document.getElementById('passwordError');


            // Clear previous error
            errorDiv.innerHTML = '';

            // If password is empty, skip validation password validation
            if (password === '') {
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


            // If profile exists and both passwords are empty, continue, no error
            if (password === '' && repeatPassword === '') {
                errorDiv.innerHTML = '';
                return true;
            }

            // If one is empty but not the other, display an error
            if ((password === '') !== (repeatPassword === '')) {
                errorDiv.innerHTML = 'Please complete both password fields.';
                return false;
            }

            if (password !== repeatPassword) {
                errorDiv.innerHTML = 'Oops, these passwords do not match. Please try again.';
                return false;
            } else if (repeatPassword !== '') {
                errorDiv.innerHTML = '<span style="color: green;">✓ Passwords match</span>';
            }
            return true;
        }

        function validateForm() {

            const password = document.getElementById('password').value;

            // For existing profiles, password is optional
            if (password !== '') {
                const passwordValid = validatePassword();
                if (!passwordValid) {
                    alert("It looks like your password doesn't quite meet all of our requirements, please try again");
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
        document.getElementById('password').addEventListener('input', function () {
            if (this.value.length > 0) {
                validatePassword();
            } else {
                document.getElementById('passwordError').innerHTML = '';
            }
        });

        document.getElementById('repeatPassword').addEventListener('input', function () {
            validatePasswordMatch();
        });

        // Show confirmation for profile updates

        document.querySelector('form').addEventListener('submit', function (e) {
            if (!confirm('Are you sure you want to update your profile with these changes?')) {
                e.preventDefault();
                return false;
            }
        });

    </script>
    </body>

    </html>