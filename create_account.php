<?php
/**
 * Create Account Handler
 * Processes the new account form submission
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: home.html?error=session_expired');
    exit;
}

// Include database configuration with error handling
$db_config_path = '../db_connect.php';
if (!file_exists($db_config_path)) {
    die("Database configuration file not found at: " . $db_config_path);
}

include $db_config_path;

// Verify database variables are defined
if (!isset($servername) || !isset($dbname) || !isset($username_db) || !isset($password_db)) {
    die("Database configuration variables are not properly defined in db_connect.php");
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get form data
    $account_number = trim($_POST['account_number'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $normal_side = $_POST['normal_side'] ?? '';
    $category = $_POST['category'] ?? '';
    $subcategory = trim($_POST['subcategory'] ?? '');
    $initial_balance = $_POST['initial_balance'] ?? '0';
    $debit = $_POST['debit'] ?? '0';
    $credit = $_POST['credit'] ?? '0';
    $order_type = $_POST['order_type'] ?? '';
    $statement = $_POST['statement'] ?? '';
    $comment = trim($_POST['comment'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    // Validation
    $errors = [];
    
    // Required field validation
    if (empty($account_number)) {
        $errors[] = "Account number is required.";
    }
    if (empty($name)) {
        $errors[] = "Account name is required.";
    }
    if (empty($normal_side)) {
        $errors[] = "Normal side is required.";
    }
    if (empty($category)) {
        $errors[] = "Category is required.";
    }
    
    // Account number validation (only integers, no decimals, spaces, or alphanumeric)
    if (!empty($account_number) && !preg_match('/^[0-9]+$/', $account_number)) {
        $errors[] = "Account number must contain only numbers (no decimals, spaces, or letters).";
    }
    
    // Monetary value validation and formatting
    function validateAndFormatMoney($value, $fieldName) {
        global $errors;
        
        if (empty($value)) {
            return '0.00';
        }
        
        // Remove commas and whitespace
        $cleanValue = str_replace([',', ' '], '', $value);
        
        // Validate numeric
        if (!is_numeric($cleanValue)) {
            $errors[] = "$fieldName must be a valid monetary amount.";
            return '0.00';
        }
        
        // Format to 2 decimal places
        return number_format((float)$cleanValue, 2, '.', '');
    }
    
    // Format monetary values
    $initial_balance = validateAndFormatMoney($initial_balance, "Initial balance");
    $debit = validateAndFormatMoney($debit, "Debit");
    $credit = validateAndFormatMoney($credit, "Credit");
    
    // Calculate balance
    $balance = number_format((float)$initial_balance + (float)$debit - (float)$credit, 2, '.', '');
    
    // Database validation and insertion
    if (empty($errors)) {
        try {
            $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check for duplicate account number
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM accounts WHERE account_number = :account_number");
            $stmt->execute([':account_number' => $account_number]);
            
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Account number '$account_number' already exists. Please use a different account number.";
            }
            
            // Check for duplicate account name
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM accounts WHERE name = :name");
            $stmt->execute([':name' => $name]);
            
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Account name '$name' already exists. Please use a different account name.";
            }
            
            // If no errors, insert the account
            if (empty($errors)) {
                $stmt = $pdo->prepare("
                    INSERT INTO accounts (
                        account_number, name, description, normal_side, category, 
                        subcategory, initial_balance, debit, credit, balance, 
                        created_at, user_id, order_type, statement, comment
                    ) VALUES (
                        :account_number, :name, :description, :normal_side, :category,
                        :subcategory, :initial_balance, :debit, :credit, :balance,
                        NOW(), :user_id, :order_type, :statement, :comment
                    )
                ");
                
                $result = $stmt->execute([
                    ':account_number' => $account_number,
                    ':name' => $name,
                    ':description' => $description,
                    ':normal_side' => $normal_side,
                    ':category' => $category,
                    ':subcategory' => $subcategory,
                    ':initial_balance' => $initial_balance,
                    ':debit' => $debit,
                    ':credit' => $credit,
                    ':balance' => $balance,
                    ':user_id' => $user_id,
                    ':order_type' => $order_type,
                    ':statement' => $statement,
                    ':comment' => $comment
                ]);
                
                if ($result) {
                    // Success message with formatted values
                    $formatted_initial = '$' . number_format((float)$initial_balance, 2);
                    $formatted_debit = '$' . number_format((float)$debit, 2);
                    $formatted_credit = '$' . number_format((float)$credit, 2);
                    $formatted_balance = '$' . number_format((float)$balance, 2);
                    
                    echo "<script>
                        alert('Account created successfully!\\n\\n" .
                        "Account Number: $account_number\\n" .
                        "Account Name: $name\\n" .
                        "Initial Balance: $formatted_initial\\n" .
                        "Current Balance: $formatted_balance');
                        window.location.href='new_account.php';
                    </script>";
                } else {
                    $errors[] = "Error creating account. Please try again.";
                }
            }
            
        } catch(PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
    
    // Display errors if any
    if (!empty($errors)) {
        $errorMessage = "Please correct the following errors:\\n\\n";
        foreach ($errors as $error) {
            $errorMessage .= "• " . $error . "\\n";
        }
        
        echo "<script>
            alert(".$errorMessage.");
            history.back();
        </script>";
    }
    
} else {
    // If not POST request, redirect to form
    header("Location: create_account.php");
    exit();
}
?>