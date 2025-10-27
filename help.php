<?php


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

$username = $_SESSION['username'] ?? 'User';
$userId = $_SESSION['user_id'];
$userAccessLevel = isset($_SESSION['access_level']) ? (int)$_SESSION['access_level'] : 0;
$canEditAccounts = ($userAccessLevel >= 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Clarus Help</title>
   
</head>
<body>
    <div class="container" style="width: 85%; height: 85%; overflow: scroll; scrollbar-width: none; -ms-overflow-style: none;">
    <!--<img src="https://thumbs.dreamstime.com/b/calculator-icon-vector-isolated-white-background-your-web-mobile-app-design-calculator-logo-concept-calculator-icon-134617239.jpg" width="100px">-->
    <?php include 'header.php'; ?>
    <h1>Clarus Help</h1>
    <h3 style="text-align: left">About Chart of Accounts</h3>
    <p style="text-align: left">A chart of accounts (COA) is an organized list of all financial accounts in a company's general ledger, used to classify, record, and summarize financial transactions for reporting and analysis. It acts as the framework for financial reporting by providing an index of accounts like assets, liabilities, equity, revenue, and expenses, each assigned a unique code for consistent tracking and decision-making.</p>
    <h3 style="text-align: left">About Accounts</h3>
    <p style="text-align: left">In an accounting system, an account is a record of financial transactions for a specific item, such as cash, accounts receivable, or rent expense. These accounts are organized into a chart of accounts which categorizes all of a company's financial data, typically into five main types: assets, liabilities, equity, revenue, and expenses. Each transaction affects at least two accounts, following the double-entry accounting system, to maintain a balanced financial record.</p>
    <h3 style="text-align: left">About Ledgers</h3>
    <p style="text-align: left">In an accounting system, a ledger is a central record of all financial transactions, organized by account type. It acts as a master file or "book of accounts," which is used to create financial statements and is essential for tracking a company's assets, liabilities, equity, revenue, and expenses.</p>
    <h3 style="text-align: left">About User Managment in Clarus</h3>
    <p style="text-align: left"><b>Admins:</b> Admins have "full reign" of your Clarus account. They can create and edit users as well as any account data.</p>
    <p style="text-align: left"><b>Managers:</b> Managers can view accounts but can’t add, edit, or deactivate accounts. Managers can approve transactions created by Accountants. Managers do not have access to user management.</p>
    <p style="text-align: left"><b>Accountants:</b> Accountants can view account data and create transactions. Transactions created by an accountant must be approved by a manager.</p>
    <h3 style="text-align: left">Financial Ratios with Color-Coded Indicators:</h3>
    <p style="text-align: left">
🟢 Green (Good) - Ratios within optimal healthy ranges<br/>
🟡 Yellow (Warning) - Ratios in borderline/caution ranges<br/>
🔴 Red (Danger) - Ratios that need immediate attention<br/>
Ratios Included:<br/>
Current Ratio - Liquidity measure<br/>
🟢 1.5-3.0 | 🟡 1.0-1.5 | 🔴 <1.0<br/>
Quick Ratio - Acid test without inventory<br/>
🟢 1.0-2.0 | 🟡 0.7-1.0 | 🔴 <0.7
Debt-to-Equity Ratio - Financial leverage<br/>
🟢 <1.0 | 🟡 1.0-2.0 | 🔴 >2.0<br/>
Debt Ratio - Asset financing by debt<br/>
🟢 <40% | 🟡 40-60% | 🔴 >60%<br/>
Profit Margin - Profitability measure<br/>
🟢 >10% | 🟡 5-10% | 🔴 <5%<br/>
Return on Assets (ROA) - Asset efficiency<br/>
🟢 >5% | 🟡 0-5% | 🔴 <0%<br/>
Return on Equity (ROE) - Shareholder return<br/>
🟢 >15% | 🟡 10-15% | 🔴 <10%<br/>
Working Capital - Operating liquidity<br/>
🟢 >$0 | 🟡 $0 | 🔴 <$0<br/>
</p>
</body>
</html>

