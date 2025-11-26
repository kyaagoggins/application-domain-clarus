<?php
/** 
 * KSU student project for Clarus Accounting tool
 * This script is used by admins for account edit form submissions
 * Initially drafted by Eric Poole; Reviewed and updated by Kyaa Goggins
 * Kyaa Goggins: Added more error handling like missing try/catches
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

include '../db_connect.php';


if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: dashboard.php");
    exit();
}

$original_account_number = trim($_POST['original_account_number']);
$original_name = trim($_POST['original_name']);
$account_number = trim($_POST['account_number']);
$name = trim($_POST['name']);
$description = trim($_POST['description']);
$normal_side = $_POST['normal_side'];
$category = $_POST['category'];
$subcategory = trim($_POST['subcategory']);
$initial_balance = $_POST['initial_balance'];
$debit = $_POST['debit'];
$credit = $_POST['credit'];
$order_type = $_POST['order_type'];
$statement = $_POST['statement'];
$comment = trim($_POST['comment']);
$user_id = $_SESSION['user_id'];



// Monetary value validation and formatting
function validateAndFormatMoney($value, $fieldName)
{
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
    return number_format((float) $cleanValue, 2, '.', '');
}

// Format monetary values
$initial_balance = validateAndFormatMoney($initial_balance, "Initial balance");
$debit = validateAndFormatMoney($debit, "Debit amount");
$credit = validateAndFormatMoney($credit, "Credit amount");

// Calculate balance
$balance = number_format((float) $initial_balance + (float) $debit - (float) $credit, 2, '.', '');

// Database validation and update
if (empty($errors)) {

        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Begin transaction
        $pdo->beginTransaction();

        // Fetch the complete original record for audit logging
        $acctRecordOrig = $pdo->prepare("
            SELECT account_number, name, description, normal_side, category, subcategory, 
                   initial_balance, debit, credit, balance, order_type, statement, comment, 
                   user_id, is_active 
            FROM accounts 
            WHERE account_number = :original_account_number
        ");
        $acctRecordOrig->execute([':original_account_number' => $original_account_number]);
        $originalAccount = $acctRecordOrig->fetch(PDO::FETCH_ASSOC);

        if (!$originalAccount) {
            throw new Exception("The original account was not found. It may have been deleted by another user.");
        }

        // Check for duplicate account number (if changed)
        if ($account_number !== $original_account_number) {
            $acctNumCheck = $pdo->prepare("SELECT COUNT(*) FROM accounts WHERE account_number = :account_number");
            $acctNumCheck->execute([':account_number' => $account_number]);

            if ($acctNumCheck->fetchColumn() > 0) {
                $errors[] = "Account number '$account_number' already exists. Please use a different account number.";
            }
        }

        // If no errors, update the account
        if (empty($errors)) {
            $updateAccts = $pdo->prepare("
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

            $result = $updateAccts->execute([
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

            if ($result && $updateAccts->rowCount() > 0) {
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
                     
                }

                // Commit the transaction
                $pdo->commit();

                // Log the update (optional - keeping existing audit log)
                
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
                

                echo "<script>
                    alert('This account was updated successfully!');
                    window.location.href='view_account.php?account_number=" . $account_number . "';
                </script>";
            } else {
                throw new Exception("No changes were made to the account or account not found.");
            }
        } else {
            throw new Exception("An error occurred when making these changes. Please try again.");
        }


}

?>