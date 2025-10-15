// check_duplicate.php example
<?php
session_start();
if (!isset($_SESSION['user_id'])) exit;

$type = $_GET['type'] ?? '';
$value = $_GET['value'] ?? '';

// Database connection and check logic here
// Return JSON response: {"exists": true/false}
?>