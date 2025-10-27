<?php
/**
 * DEBUG VERSION - Deactivate/Reactivate Account Handler
 * This version shows detailed error messages for troubleshooting
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: home.html?error=session_expired');
    exit;
}

// Include database configuration
include '../db_connect.php';

// Get parameters from URL
$account_number = isset($_GET['account_number']) ? trim($_GET['account_number']) : null;
$action = isset($_GET['action']) ? trim($_GET['action']) : null;

echo "<h3>Debug Information:</h3>";
echo "Account Number: " . htmlspecialchars($account_number) . "<br>";
echo "Action: " . htmlspecialchars($action) . "<br>";
echo "User ID: " . $_SESSION['user_id'] . "<br>";

try {
    // Test database connection first
    echo "Testing database connection...<br>";
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Database connection successful<br>";
    
    // Test if accounts table exists
    echo "Testing accounts table...<br>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'accounts'");
    $table_exists = $stmt->fetch();
    if (!$table_exists) {
        throw new Exception("accounts table does not exist!");
    }
    echo "✓ Accounts table exists<br>";
    
    // Check table structure
    echo "Checking table structure...<br>";
    $stmt = $pdo->query("DESCRIBE accounts");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $has_account_number = false;
    $has_is_active = false;
    $has_updated_at = false;
    
    echo "Available columns:<br>";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
        if ($column['Field'] === 'account_number') $has_account_number = true;
        if ($column['Field'] === 'is_active') $has_is_active = true;
        if ($column['Field'] === 'updated_at') $has_updated_at = true;
    }
    
    if (!$has_account_number) {
        throw new Exception("Missing 'account_number' column in accounts table!");
    }
    if (!$has_is_active) {
        throw new Exception("Missing 'is_active' column in accounts table!");
    }
    if (!$has_updated_at) {
        echo "⚠️ Warning: Missing 'updated_at' column - will skip this in update<br>";
    }
    
    // Test account lookup
    echo "Testing account lookup...<br>";
    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE account_number = :account_number");
    $stmt->execute([':account_number' => $account_number]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$account) {
        throw new Exception("Account not found with number: $account_number");
    }
    echo "✓ Account found: " . htmlspecialchars($account['name']) . "<br>";
    echo "Current is_active status: " . ($account['is_active'] ?? 'NULL') . "<br>";
    
    // Test the update query
    echo "Testing update query...<br>";
    $new_status = ($action === 'reactivate') ? 1 : 0;
    
    if ($has_updated_at) {
        $sql = "UPDATE accounts SET is_active = :status, updated_at = NOW() WHERE account_number = :account_number";
    } else {
        $sql = "UPDATE accounts SET is_active = :status WHERE account_number = :account_number";
    }
    
    echo "SQL Query: " . $sql . "<br>";
    echo "New Status: " . $new_status . "<br>";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':status' => $new_status,
        ':account_number' => $account_number
    ]);
    
    echo "✓ Update successful! Rows affected: " . $stmt->rowCount() . "<br>";
    
} catch (PDOException $e) {
    echo "<strong>PDO Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Error Code:</strong> " . $e->getCode() . "<br>";
} catch (Exception $e) {
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
}

echo "<br><a href='javascript:history.back()'>Go Back</a>";
?>