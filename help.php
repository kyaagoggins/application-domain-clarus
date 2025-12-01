<?php

//KSU student project for Clarus Accounting tool
//This page provides help context for each part of the applications
//Initially drafted by Eric Poole, Expanded on by Jared Louissant

//Question: why do we need to start a session for this page? 
//Answer: yes because of the requirement to show logged in users name on each page
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

$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];
$userAccessLevel = $_SESSION['access_level'];
$canEditAccounts = ($userAccessLevel >= 5);

include 'header.php';
?>
<div class="container"
     style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none; -ms-overflow-style: none;">

    <h1>Clarus Help</h1>

    <h3 style="text-align: left">About the Chart of Accounts</h3>
    <p style="text-align: left">
        The Chart of Accounts is the main structure used to organize a company’s financial information. It lists every
        account a business uses such as assets, liabilities, equity, revenue, and expenses. Each account has its own code
        so information stays consistent. This setup makes it easier to record transactions accurately and to create
        financial reports that are clear and easy to understand.
    </p>

    <h3 style="text-align: left">About Accounts</h3>
    <p style="text-align: left">
        An account tracks all activity for a specific financial item such as cash, accounts receivable, or rent. Accounts
        fit into the Chart of Accounts so everything stays organized. Accounting uses a double entry system which means
        every transaction updates at least two accounts. This helps keep all financial records balanced and complete.
    </p>

    <h3 style="text-align: left">About Ledgers</h3>
    <p style="text-align: left">
        The ledger is the central place where all financial activity is stored. It gathers transactions by account and
        serves as the main source for financial statements. A well maintained ledger shows the true financial position of
        a business which is important for tracking performance and making decisions.
    </p>

    <h3 style="text-align: left">About User Management in Clarus</h3>
    <p style="text-align: left"><b>Admins:</b> Admins have full access to the Clarus system. They can create and edit
        users along with any account information. They handle the highest level of system control.</p>

    <p style="text-align: left"><b>Managers:</b> Managers can view account information and approve transactions submitted
        by Accountants. They cannot create, edit, or deactivate accounts and they do not manage users.</p>

    <p style="text-align: left"><b>Accountants:</b> Accountants can view accounts and submit transactions. All of their
        transactions must be approved by a Manager before they are finalized.</p>

    <h3 style="text-align: left">Financial Ratios with Color Indicators</h3>
    <p style="text-align: left">
        Financial ratios help show the overall health of an organization. Clarus uses color indicators to make it easier
        to quickly understand what each ratio is telling you:
        <br><br>
        🟢 Green means the ratio is in a healthy range<br>
        🟡 Yellow means the ratio may need attention<br>
        🔴 Red means the ratio may indicate a concern<br><br>

        <b>Current Ratio</b> which measures short term liquidity<br>
        🟢 1.5 to 3.0 | 🟡 1.0 to 1.5 | 🔴 less than 1.0<br><br>

        <b>Quick Ratio</b> which measures liquidity without inventory<br>
        🟢 1.0 to 2.0 | 🟡 0.7 to 1.0 | 🔴 less than 0.7<br><br>

        <b>Debt to Equity Ratio</b> which shows the level of financial leverage<br>
        🟢 less than 1.0 | 🟡 1.0 to 2.0 | 🔴 greater than 2.0<br><br>

        <b>Debt Ratio</b> which shows how much of the assets are financed by debt<br>
        🟢 less than 40 percent | 🟡 40 to 60 percent | 🔴 greater than 60 percent<br><br>

        <b>Profit Margin</b> which measures how much profit is made from revenue<br>
        🟢 greater than 10 percent | 🟡 5 to 10 percent | 🔴 less than 5 percent<br><br>

        <b>Return on Assets</b> which shows how efficiently assets create income<br>
        🟢 greater than 5 percent | 🟡 0 to 5 percent | 🔴 less than 0 percent<br><br>

        <b>Return on Equity</b> which shows the return earned for shareholders<br>
        🟢 greater than 15 percent | 🟡 10 to 15 percent | 🔴 less than 10 percent<br><br>

        <b>Working Capital</b> which reflects short term financial strength<br>
        🟢 greater than zero | 🟡 equal to zero | 🔴 less than zero<br>
    </p>

</div>


    </body>

    </html>