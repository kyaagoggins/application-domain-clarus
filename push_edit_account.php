<?php
/**
 * Update Account Handler
 * This script processes account edit form submissions
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

// Include database configuration
include '../db_connect.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: dashboard.php");
    exit();
}

// Get form data
$original_account_number = trim($_POST['original_account_number'] ?? '');
$original_name = trim($_POST['original_name'] ?? '');
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
if (empty($original_account_number)) {
    $errors[] = "Original account number is required for update.";
}
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
if (empty($statement)) {
    $errors[] = "Financial statement is required.";
}

// Account number validation (only integers, no decimals, spaces, or alphanumeric)
if (!empty($account_number) && !preg_match('/^[0-9]+$/', $account_number)) {
    $errors[] = "Account number must contain only numbers (no decimals, spaces, or letters).";
}

// Account number length validation
if (!empty($account_number) && strlen($account_number) < 3) {
    $errors[] = "Account number must be at least 3 digits.";
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
$debit = validateAndFormatMoney($debit, "Debit amount");
$credit = validateAndFormatMoney($credit, "Credit amount");

// Calculate balance
$balance = number_format((float)$initial_balance + (float)$debit - (float)$credit, 2, '.', '');

// Database validation and update
if (empty($errors)) {
    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Fetch the complete original record for audit logging
        $stmt = $pdo->prepare("
            SELECT account_number, name, description, normal_side, category, subcategory, 
                   initial_balance, debit, credit, balance, order_type, statement, comment, 
                   user_id, is_active 
            FROM accounts 
            WHERE account_number = :original_account_number
        ");
        $stmt->execute([':original_account_number' => $original_account_number]);
        $originalAccount = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$originalAccount) {
            throw new Exception("Original account not found. It may have been deleted by another user.");
        }
        
        // Check for duplicate account number (if changed)
        if ($account_number !== $original_account_number) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM accounts WHERE account_number = :account_number");
            $stmt->execute([':account_number' => $account_number]);
            
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Account number '$account_number' already exists. Please use a different account number.";
            }
        }
        
        // Check for duplicate account name (if changed)
        if ($name !== $original_name) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM accounts WHERE name = :name");
            $stmt->execute([':name' => $name]);
            
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Account name '$name' already exists. Please use a different account name.";
            }
        }
        
        // If no errors, update the account
        if (empty($errors)) {
            $stmt = $pdo->prepare("
                UPDATE accounts SET 
                    account_number = :account_number,
                    name = :name,
                    description = :description,
                    normal_side = :normal_side,
                    category = :category,
                    subcategory = :subcategory,
                    initial_balance = :initial_balance,
                    debit = :debit,
                    credit = :credit,
                    balance = :balance,
                    order_type = :order_type,
                    statement = :statement,
                    comment = :comment,
                    user_id = :user_id
                WHERE account_number = :original_account_number
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
                ':order_type' => $order_type,
                ':statement' => $statement,
                ':comment' => $comment,
                ':user_id' => $user_id,
                ':original_account_number' => $original_account_number
            ]);
            
            if ($result && $stmt->rowCount() > 0) {
                // Check for changes and log to change_log table
                $changes_detected = false;
                $change_fields = [];
                
                // Compare each field for changes
                if ($originalAccount['name'] !== $name) {
                    $changes_detected = true;
                    $change_fields['name'] = true;
                }
                if ($originalAccount['description'] !== $description) {
                    $changes_detected = true;
                    $change_fields['description'] = true;
                }
                if ($originalAccount['normal_side'] !== $normal_side) {
                    $changes_detected = true;
                    $change_fields['normal_side'] = true;
                }
                if ($originalAccount['category'] !== $category) {
                    $changes_detected = true;
                    $change_fields['category'] = true;
                }
                if ($originalAccount['subcategory'] !== $subcategory) {
                    $changes_detected = true;
                    $change_fields['subcategory'] = true;
                }
                if ($originalAccount['debit'] !== $debit) {
                    $changes_detected = true;
                    $change_fields['debit'] = true;
                }
                if ($originalAccount['credit'] !== $credit) {
                    $changes_detected = true;
                    $change_fields['credit'] = true;
                }
                if ($originalAccount['balance'] !== $balance) {
                    $changes_detected = true;
                    $change_fields['balance'] = true;
                }
                if ($originalAccount['order_type'] !== $order_type) {
                    $changes_detected = true;
                    $change_fields['order_type'] = true;
                }
                if ($originalAccount['statement'] !== $statement) {
                    $changes_detected = true;
                    $change_fields['statement'] = true;
                }
                if ($originalAccount['comment'] !== $comment) {
                    $changes_detected = true;
                    $change_fields['comment'] = true;
                }
                if ($originalAccount['user_id'] != $user_id) {
                    $changes_detected = true;
                    $change_fields['user_id'] = true;
                }
                
                // Log changes to change_log table if any changes were detected
                if ($changes_detected) {
                    try {
                        $log_stmt = $pdo->prepare("
                            INSERT INTO change_log 
                            (change_time, account_number, 
                             name_before, description_before, normal_side_before, category_before, subcategory_before,
                             debit_before, credit_before, balance_before, user_id_before, order_type_before, 
                             statement_before, comment_before, is_active_before,
                             name_after, description_after, normal_side_after, category_after, subcategory_after,
                             debit_after, credit_after, balance_after, user_id_after, order_type_after, 
                             statement_after, comment_after, is_active_after) 
                            VALUES 
                            (NOW(), :account_number,
                             :name_before, :description_before, :normal_side_before, :category_before, :subcategory_before,
                             :debit_before, :credit_before, :balance_before, :user_id_before, :order_type_before, 
                             :statement_before, :comment_before, :is_active_before,
                             :name_after, :description_after, :normal_side_after, :category_after, :subcategory_after,
                             :debit_after, :credit_after, :balance_after, :user_id_after, :order_type_after, 
                             :statement_after, :comment_after, :is_active_after)
                        ");
                        
                        $log_stmt->execute([
                            ':account_number' => $account_number,
                            ':name_before' => $originalAccount['name'],
                            ':description_before' => $originalAccount['description'],
                            ':normal_side_before' => $originalAccount['normal_side'],
                            ':category_before' => $originalAccount['category'],
                            ':subcategory_before' => $originalAccount['subcategory'],
                            ':debit_before' => $originalAccount['debit'],
                            ':credit_before' => $originalAccount['credit'],
                            ':balance_before' => $originalAccount['balance'],
                            ':user_id_before' => $originalAccount['user_id'],
                            ':order_type_before' => $originalAccount['order_type'],
                            ':statement_before' => $originalAccount['statement'],
                            ':comment_before' => $originalAccount['comment'],
                            ':is_active_before' => $originalAccount['is_active'],
                            ':name_after' => $name,
                            ':description_after' => $description,
                            ':normal_side_after' => $normal_side,
                            ':category_after' => $category,
                            ':subcategory_after' => $subcategory,
                            ':debit_after' => $debit,
                            ':credit_after' => $credit,
                            ':balance_after' => $balance,
                            ':user_id_after' => $user_id,
                            ':order_type_after' => $order_type,
                            ':statement_after' => $statement,
                            ':comment_after' => $comment,
                            ':is_active_after' => $originalAccount['is_active'] // Assuming is_active doesn't change in this update
                        ]);
                    } catch (Exception $e) {
                        // Log error but don't fail the transaction
                        error_log("Could not log account changes to change_log: " . $e->getMessage());
                    }
                }
                
                // Commit the transaction
                $pdo->commit();
                
                // Log the update (optional - keeping existing audit log)
                try {
                    $log_stmt = $pdo->prepare("
                        INSERT INTO account_audit_log 
                        (account_number, action, performed_by, performed_at, notes) 
                        VALUES 
                        (:account_number, 'UPDATE', :user_id, NOW(), :notes)
                    ");
                    
                    $notes = "Account updated by user {$_SESSION['username']} (ID: {$user_id})";
                    if ($account_number !== $original_account_number) {
                        $notes .= " - Account number changed from $original_account_number to $account_number";
                    }
                    if ($name !== $original_name) {
                        $notes .= " - Name changed from '$original_name' to '$name'";
                    }
                    
                    $log_stmt->execute([
                        ':account_number' => $account_number,
                        ':user_id' => $user_id,
                        ':notes' => $notes
                    ]);
                } catch (Exception $e) {
                    // Audit log table might not exist, continue without logging
                    error_log("Could not log account update: " . $e->getMessage());
                }
                
                // Success message with formatted values
                $formatted_initial = '$' . number_format((float)$initial_balance, 2);
                $formatted_debit = '$' . number_format((float)$debit, 2);
                $formatted_credit = '$' . number_format((float)$credit, 2);
                $formatted_balance = '$' . number_format((float)$balance, 2);
                
                echo "<script>
                    alert('Account updated successfully!\\n\\n" .
                    "Account Number: $account_number\\n" .
                    "Account Name: $name\\n" .
                    "Category: $category\\n" .
                    "Normal Side: $normal_side\\n" .
                    "Current Balance: $formatted_balance');
                    window.location.href='view_account.php?account_number=" . urlencode($account_number) . "';
                </script>";
            } else {
                throw new Exception("No changes were made to the account or account not found.");
            }
        } else {
            // Rollback transaction due to validation errors
            $pdo->rollback();
        }
        
    } catch(PDOException $e) {
        // Rollback transaction on database error
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        $errors[] = "Database Error: " . $e->getMessage();
        error_log("Account update database error: " . $e->getMessage());
    } catch(Exception $e) {
        // Rollback transaction on general error
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        $errors[] = "Error: " . $e->getMessage();
        error_log("Account update error: " . $e->getMessage());
    }
}

// Display errors if any
if (!empty($errors)) {
    $errorMessage = "Please correct the following errors:\\n\\n";
    foreach ($errors as $error) {
        $errorMessage .= "• " . $error . "\\n";
    }
    
    echo "<script>
        alert('$errorMessage');
        history.back();
    </script>";
}
?>