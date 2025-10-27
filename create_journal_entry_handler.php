<?php
/**
 * Create Journal Entry Handler
 * Processes the journal entry form submission
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}

$userId = $_SESSION['user_id'];

// Include database configuration
include '../db_connect.php';

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get form data
    $account_id = $_POST['account_id'] ?? '';
    $entry_date = $_POST['entry_date'] ?? '';
    $description = $_POST['description'] ?? '';
    $reference = $_POST['reference'] ?? '';
    $entry_lines = json_decode($_POST['entry_lines'] ?? '[]', true);
    
    // Validate data
    if (empty($account_id) || empty($entry_date) || empty($description) || empty($entry_lines)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    // Calculate totals
    $total_debit = 0;
    $total_credit = 0;
    foreach ($entry_lines as $line) {
        $total_debit += $line['debit'];
        $total_credit += $line['credit'];
    }
    
    // Validate balanced entry
    if (abs($total_debit - $total_credit) > 0.01) {
        echo json_encode(['success' => false, 'message' => 'Debits must equal credits']);
        exit;
    }
    
    // Handle file uploads
    $uploaded_files = [];
    if (isset($_FILES['source_documents']) && !empty($_FILES['source_documents']['name'][0])) {
        $upload_dir = '../uploads/journal_documents/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        for ($i = 0; $i < count($_FILES['source_documents']['name']); $i++) {
            if ($_FILES['source_documents']['error'][$i] === 0) {
                $file_name = time() . '_' . basename($_FILES['source_documents']['name'][$i]);
                $target_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['source_documents']['tmp_name'][$i], $target_path)) {
                    $uploaded_files[] = $file_name;
                }
            }
        }
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Insert journal entry
    $stmt = $pdo->prepare("
        INSERT INTO journal_entries (
            account_id, entry_date, description, reference_number, 
            total_debit, total_credit, created_by, created_at, status, source_documents
        ) VALUES (
            :account_id, :entry_date, :description, :reference,
            :total_debit, :total_credit, :user_id, NOW(), 'pending', :documents
        )
    ");
    
    $stmt->execute([
        ':account_id' => $account_id,
        ':entry_date' => $entry_date,
        ':description' => $description,
        ':reference' => $reference,
        ':total_debit' => $total_debit,
        ':total_credit' => $total_credit,
        ':user_id' => $userId,
        ':documents' => json_encode($uploaded_files)
    ]);
    
    $journal_entry_id = $pdo->lastInsertId();
    
    // Insert journal entry lines
    $stmt = $pdo->prepare("
        INSERT INTO journal_entry_lines (
            journal_entry_id, account_number, line_description, debit_amount, credit_amount
        ) VALUES (
            :journal_entry_id, :account_number, :line_description, :debit_amount, :credit_amount
        )
    ");
    
    foreach ($entry_lines as $line) {
        $stmt->execute([
            ':journal_entry_id' => $journal_entry_id,
            ':account_number' => $line['account'],
            ':line_description' => $line['description'],
            ':debit_amount' => $line['debit'],
            ':credit_amount' => $line['credit']
        ]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode(['success' => true, 'journal_entry_id' => $journal_entry_id]);
    
} catch(PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>