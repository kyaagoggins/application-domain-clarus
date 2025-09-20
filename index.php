<?php include 'header.php'; ?>

<style>
  .signup-container {
    width: 100%;
    max-width: 600px;
  }

  .signup-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    animation: fadeIn 0.8s ease-out;
  }

  .signup-header {
    background: linear-gradient(135deg, #2980b9, #6dd5fa);
    color: white;
    padding: 40px 30px;
    text-align: center;
  }

  .signup-header h1 {
    font-size: 2.2rem;
    margin-bottom: 10px;
    font-weight: 600;
  }

  .signup-header p {
    font-size: 1.1rem;
    opacity: 0.9;
  }

  /* Password Container */
  .password-container {
    position: relative;
  }

  .password-toggle {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    padding: 5px;
    border-radius: 5px;
    transition: color 0.3s ease;
  }

  .password-toggle:hover {
    color: #2980b9;
  }

  /* Password Requirements */
  .password-requirements {
    margin-top: 15px;
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #e9ecef;
  }

  .password-requirements p {
    font-weight: 500;
    color: #495057;
    margin-bottom: 10px;
  }

  .password-requirements ul {
    list-style: none;
    padding: 0;
  }

  .password-requirements li {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 5px 0;
    font-size: 0.9rem;
    transition: all 0.3s ease;
  }

  .password-requirements li.valid {
    color: #28a745;
  }

  .password-requirements li.valid i {
    color: #28a745;
  }

  .password-requirements li.invalid {
    color: #dc3545;
  }

  .password-requirements li.invalid i {
    color: #dc3545;
  }

  /* Submit Button */
  .submit-btn {
    width: 100%;
    background: linear-gradient(135deg, #2980b9, #6dd5fa);
    color: white;
    border: none;
    padding: 18px;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-top: 10px;
  }

  .submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
  }

  .submit-btn:active {
    transform: translateY(0);
  }

  .submit-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
  }

  /* Login Link */
  .login-link {
    text-align: center;
    padding: 25px 30px;
    background: #f8f9fa;
  }

  .login-link p {
    color: #6c757d;
  }

  .login-link a {
    color: #2980b9;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
  }

  .login-link a:hover {
    color: #2980b9;
    text-decoration: underline;
  }

  /* Login-specific styles */
  .login-container {
    width: 100%;
    max-width: 450px;
  }

  .login-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    animation: fadeIn 0.8s ease-out;
  }

  .login-header {
    background: linear-gradient(135deg, #2980b9, #6dd5fa);
    color: black;
    padding: 40px 30px;
    text-align: center;
  }

  .login-header h1 {
    font-size: 2.2rem;
    margin-bottom: 10px;
    font-weight: 600;
  }

  .login-header p {
    font-size: 1.1rem;
    opacity: 0.9;
  }

  .login-form {
    padding: 20px 30px;
  }

  .login-footer {
    text-align: center;
    padding: 25px 30px;
    background: #f8f9fa;
    border-top: 1px solid #f0f0f0;
  }

  button:hover {
    background: #1f6391;
  }

  .link-btn {
    background: none;
    border: none;
    color: #2980b9;
    text-decoration: underline;
    cursor: pointer;
    margin-top: 10px;
    font-size: 14px;
  }

  .link-btn:hover {
    color: #1f6391;
  }

  .forgot-password {
    text-align: right;
    margin-top: 10px;
  }

  .forgot-password a {
    color: #2980b9;
    text-decoration: none;
    font-size: 0.9rem;
    transition: color 0.3s ease;
  }

  .forgot-password a:hover {
    color: #6dd5fa;
    text-decoration: underline;
  }

  .remember-me {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 20px 0;
  }

  .remember-me input[type="checkbox"] {
    width: auto;
    margin: 0;
  }

  .remember-me label {
    margin: 0;
    font-size: 0.95rem;
    color: #666;
    cursor: pointer;
  }

  /* Loading state for login button */
  .login-btn.loading {
    position: relative;
    overflow: hidden;
  }

  .login-btn.loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    animation: loading 1.5s infinite;
  }

  /* Responsive adjustments for login */
  @media (max-width: 768px) {
    .login-header {
      padding: 30px 20px;
    }

    .login-header h1 {
      font-size: 1.8rem;
    }

    .login-form {
      padding: 30px 20px;
    }
  }
</style>

<div class="signup-container login-container">
  <div class="signup-card login-card">
    <div class="signup-header login-header">
      <div class="logo"></div>
      <h1>Clarus</h1>
    </div>

    <form id="loginForm" class="login-form" action="process_login.php" method="POST">
      <!-- Alert Messages -->
      <div class="alert" role="alert"></div>

      <!-- Username Field -->
      <div class="form-group">
        <label for="username"><i class="fas fa-user"></i> Username</label>
        <input type="text" id="username" name="username" placeholder="Enter your username" required
          autocomplete="username">
      </div>

      <!-- Password Field -->
      <div class="form-group">
        <label for="password"><i class="fas fa-lock"></i> Password</label>
        <div class="password-container">
          <input type="password" id="password" name="password" placeholder="Enter your password" required
            autocomplete="current-password">
          <button type="button" class="password-toggle" onclick="togglePassword('password')">
            <i class="fas fa-eye" id="passwordIcon"></i>
          </button>
        </div>
        <div class="forgot-password">
          <a href="forgot_password.html">Forgot your password?</a>
        </div>
      </div>

      <!-- Remember Me -->
      <div class="remember-me">
        <input type="checkbox" id="rememberMe" name="rememberMe" value="1">
        <label for="rememberMe">Remember me for 30 days</label>
      </div>

      <!-- Login Button -->
      <button type="submit" class="submit-btn login-btn" id="loginBtn">
        <i class="fas fa-sign-in-alt"></i> Log In
      </button>

      <!-- CSRF Token (for security) -->
      <input type="hidden" name="csrf_token" id="csrfToken">
    </form>

    <!-- Footer with signup link -->
    <div class="login-footer">
      <a href="index.html"><i class="link-btn fas fa-user-plus"></i> Create Account</a>
    </div>
  </div>
</div>

<script>
  // DOM Elements
  const loginForm = document.getElementById('loginForm');
  const loginBtn = document.getElementById('loginBtn');
  const usernameInput = document.getElementById('username');
  const passwordInput = document.getElementById('password');
  const alertContainer = document.getElementById('alertContainer');

  // Initialize
  document.addEventListener('DOMContentLoaded', function () {
    initializeLogin();
    generateCSRFToken();
  });

  function initializeLogin() {
    // Form submission handling
    loginForm.addEventListener('submit', handleLogin);

    // Real-time validation
    usernameInput.addEventListener('input', validateForm);
    passwordInput.addEventListener('input', validateForm);

    // Enter key handling
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !loginBtn.disabled) {
        loginForm.requestSubmit();
      }
    });

    // Check for URL parameters (error messages, etc.)
    checkURLParameters();
  }

  function validateForm() {
    const username = usernameInput.value.trim();
    const password = passwordInput.value;

    const isValid = username.length > 0 && password.length > 0;
    loginBtn.disabled = !isValid;

    if (isValid) {
      loginBtn.classList.remove('disabled');
    } else {
      loginBtn.classList.add('disabled');
    }
  }

  function handleLogin(event) {
    event.preventDefault();

    // Show loading state
    loginBtn.classList.add('loading');
    loginBtn.disabled = true;
    loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging In...';

    // Get form data
    const formData = new FormData(loginForm);

    // Submit via fetch for better UX
    fetch('process_login.php', {
      method: 'POST',
      body: formData
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          $('.alert').removeClass('alert-warning').removeClass('alert-danger');
          $('.alert').attr('hidden', false).addClass('alert-success').html(`<strong>Success!</strong> ${data.message}`);

          // Redirect after short delay
          setTimeout(() => {
            if (data.redirect) {
              window.location.href = data.redirect;
            } else {
              window.location.href = 'home.php';
            }
          }, 1500);
        } else {
          $('.alert').removeClass('alert-success').removeClass('alert-danger');
          $('.alert').attr('hidden', false).addClass('alert-warning').html(`<strong>Warning!</strong> ${data.message}`);
          resetLoginButton();
        }
      })
      .catch(error => {
        console.error('Login error:', error);
        $('.alert').removeClass('alert-warning').removeClass('alert-success');
        $('.alert').attr('hidden', false).addClass('alert-danger').html(`<strong>Error!</strong> An Error Occurred`);
        resetLoginButton();
      });
  }

  function resetLoginButton() {
    loginBtn.classList.remove('loading');
    loginBtn.disabled = false;
    loginBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Log In';
    validateForm(); // Re-check if form should be enabled
  }

  function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(inputId + 'Icon');

    if (input.type === 'password') {
      input.type = 'text';
      icon.className = 'fas fa-eye-slash';
    } else {
      input.type = 'password';
      icon.className = 'fas fa-eye';
    }
  }

  function generateCSRFToken() {
    // Generate a simple CSRF token (in production, use server-generated tokens)
    const token = Math.random().toString(36).substr(2) + Date.now().toString(36);
    document.getElementById('csrfToken').value = token;
  }

  function checkURLParameters() {
    const urlParams = new URLSearchParams(window.location.search);

    if (urlParams.has('error')) {
      const errorType = urlParams.get('error');
      let message = '';

      switch (errorType) {
        case 'invalid_credentials':
          message = 'Invalid username or password.';
          break;
        case 'account_locked':
          message = 'Your account has been locked due to multiple failed login attempts. Please contact an administrator.';
          break;
        case 'inactive_account':
          message = 'Your account is inactive. Please contact an administrator for assistance.';
          break;
        case 'session_expired':
          message = 'Your session has expired. Please sign in again.';
          break;
        default:
          message = 'Login failed. Please try again.';
      }

      $('.alert').removeClass('alert-success').removeClass('alert-danger');
      $('.alert').attr('hidden', false).addClass('alert-warning').html(`<strong>Warning!</strong> ${message}`);
    }

    if (urlParams.has('message')) {
      const message = urlParams.get('message');
      $('.alert').removeClass('alert-warning').removeClass('alert-danger');
      $('.alert').attr('hidden', false).addClass('alert-success').html(`<strong>Success!</strong> ${decodeURIComponent(message)}`);
      setTimeout(function () {
        $('.alert').attr('hidden', true).removeClass('alert-success');
      }, 2000)
    }
  }

  // Initialize form validation
  validateForm();

  function showNewUser() {
    console.log("This would open the Create New User form (UI only).");
  }

  function showForgotPassword() {
    console.log("This would open the Forgot Password form (UI only).");
  }
</script>



<?php include 'footer.php'; ?>