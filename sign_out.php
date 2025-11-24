<?php
//KSU student project for Clarus Accounting tool
//This page is used to sign the user out of Clarus
//Initially drafted by Eric Poole

session_start();

session_destroy();

header('Location: home.html?message=logged_out_user');
exit;
?>