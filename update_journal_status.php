<?php
//KSU student project for Clarus Accounting tool
//This page processes journal status updates when a manager or admin approves or rejects a journal entry
//Initially drafted by Eric Poole. Reviewed and updated by Kyaa Goggins
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oh no! Session expired.']);
    exit;
}

//user specific information 
$userId = $_SESSION['user_id'];
$userAccessLevel = $_SESSION['access_level'];

// Check if user has permission to approve/reject
if ($userAccessLevel < 2) {
    echo "You don't have permission to approve or reject journal entries. Contact your manager or administrator for assistance.";
    exit;
}

// Get JSON input of the specific journal entry content 
$input = json_decode(file_get_contents('php://input'), true);

//journal entry details 
$entryId = $input['entry_id'];
$status = $input['status'];
$notes = $input['notes'];

// Include database configuration
include '../db_connect.php';

//database connection initialization  
$pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Update journal entry status
$updateJournal = $pdo->prepare("
        UPDATE journal_entries 
        SET status = :status,
            notes = :notes,
            approved_by = :approved_by,
            approved_at = NOW(),
            updated_at = NOW()
        WHERE entry_id = :entry_id
");

//updated journal entry result - execution of the sql statement 
$result = $updateJournal->execute([
    ':status' => $status,
    ':notes' => $notes,
    ':approved_by' => $userId,
    ':entry_id' => $entryId
]);

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Oh no! Something went wrong, the status could not be updated.']);
}
?>