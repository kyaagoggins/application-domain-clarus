<?php
/**
 * Update Journal Entry Status
 * Handles approval and rejection of journal entries
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}

$userId = $_SESSION['user_id'];
//$userAccessLevel = isset($_SESSION['access_level']) ? (int)$_SESSION['access_level'] : 0;
$userAccessLevel = 5;  //uncomment out the line above and add in logic so only managers can approve
// Check if user has permission to approve/reject
if ($userAccessLevel < 5) {
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$entryId = $input['entry_id'] ?? null;
$status = $input['status'] ?? null;
$notes = $input['notes'] ?? null;

if (!$entryId || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate status
if (!in_array($status, ['approved', 'rejected', 'posted'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// If rejecting, notes are required
if ($status === 'rejected' && empty($notes)) {
    echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
    exit;
}

// Include database configuration
include '../db_connect.php';

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Update journal entry status
    $stmt = $pdo->prepare("
        UPDATE journal_entries 
        SET status = :status,
            notes = :notes,
            approved_by = :approved_by,
            approved_at = NOW(),
            updated_at = NOW()
        WHERE entry_id = :entry_id
    ");
    
    $result = $stmt->execute([
        ':status' => $status,
        ':notes' => $notes,
        ':approved_by' => $userId,
        ':entry_id' => $entryId
    ]);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>