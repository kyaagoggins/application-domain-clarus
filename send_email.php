<?php
//KSU student project for Clarus Accounting tool
//This page is used to send an email from the Clarus system
//Initially drafted by Eric Poole.
session_start();

// Get form data
$to_email = $_POST['email'];
$firstName = $_POST['firstName'];
$lastName = $_POST['lastName'];
$subject = $_POST['subject'];
$message = $_POST['message'];
$from_user = $_SESSION['username'];

// Email configuration variables
$smtp_host = ''; //Removed from code submission for security purposes
$smtp_port = 587;               //Removed from code submission for security purposes
$smtp_username = '';  //Removed from code submission for security purposes
$smtp_password = '';     //Removed from code submission for security purposes
$from_email = '';     //Removed from code submission for security purposes
$from_name = 'Clarus System Administration';

// Create email headers
$headers = [
    'From' => $from_name . ' <' . $from_email . '>',
    'Reply-To' => $from_email,
    'X-Mailer' => 'PHP/' . phpversion(),
    'MIME-Version' => '1.0',
    'Content-Type' => 'text/plain; charset=utf-8'
];

//Begin send mail process
$header_string = '';
foreach ($headers as $key => $value) {
    $header_string .= $key . ': ' . $value . "\r\n";
}

if (mail($to_email, $subject, $message, $header_string)) {
    echo 'Yay! Your email was sent successfully!';
} else {
    echo 'Oops.. it looks like something went wrong. Please try again later.';
}
?>