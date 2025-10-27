<?php
    session_start();
    $username = $_SESSION['username'] ?? 'User';
    $userId = $_SESSION['user_id'];
    $accessLevel = $_SESSION['access_level'];
    
    echo '<h2 class="logo" style="">
        <img src="assets/logo.png" style="float:left; border: 1px solid black; border-radius: 5px; height:30px">
        <span style="float:left; margin-left: 10px">Clarus</span>';
        
        
            echo'<div style="float:right"><a href="profile.php" style="text-decoration: none; color: black;"><img src="/uploads/profile_images/'.$userId.'.jpg" style="width:50px; border-radius: 50%; border: 3px solid black">
            
            <center><div style="font-size: 14px;">'.$username.'</div></center></a></div>';
        
        echo '<a style="float:right; margin-right: 10px; padding: 10px; text-decoration:none; background-color: #efefef; color: black; font-size: 14px" href="sign_out.php">Sign Out</a>
        <a style="float:right; margin-right: 10px; padding: 10px; text-decoration:none; background-color: #efefef; color: black; font-size: 14px" href="help.php">Help</a>';
        if($accessLevel>1)
        {
        echo '<a style="float:right; margin-right: 10px; padding: 10px; text-decoration:none; background-color: #efefef; color: black; font-size: 14px" href="view_change_log.php">Event Log</a>';
        }
        if($accessLevel>2)
        {
        echo '<a style="float:right; margin-right: 10px; padding: 10px; text-decoration:none; background-color: #efefef; color: black; font-size: 14px" href="dashboard.php">User Management</a>';
        }
        echo '<a style="float:right; margin-right: 10px; padding: 10px; text-decoration:none; background-color: #efefef; color: black; font-size: 14px" href="chart_of_accounts.php">Chart of Accounts</a>
        <a style="float:right; margin-right: 10px; padding: 10px; text-decoration:none; background-color: #efefef; color: black; font-size: 14px" href="accounts_dashboard.php">Accounts</a>
        <a style="float:right; margin-right: 10px; padding: 10px; text-decoration:none; background-color: #efefef; color: black; font-size: 14px" href="view_journal_entries.php">Journal Entries</a>
        <a style="float:right; margin-right: 10px; padding: 10px; text-decoration:none; background-color: #efefef; color: black; font-size: 14px" href="landing.php">Home</a>
   </h2>
    <div style="clear:both; margin-bottom: 30px"></div>';

?>