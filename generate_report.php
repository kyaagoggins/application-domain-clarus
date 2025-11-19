<?php
/**
 * Generate Financial Reports
 * Backend handler for generating Trial Balance, Income Statement, Balance Sheet, and Retained Earnings
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
    $reportType = $_POST['report_type'] ?? '';
    $dateType = $_POST['date_type'] ?? 'as_of';
    $asOfDate = $_POST['as_of_date'] ?? date('Y-m-d');
    $startDate = $_POST['start_date'] ?? date('Y-01-01');
    $endDate = $_POST['end_date'] ?? date('Y-m-d');
    $includeAdjusting = isset($_POST['include_adjusting']) ? 1 : 0;
    $showZeroBalances = isset($_POST['show_zero_balances']) ? 1 : 0;
    
    // Validate report type
    $validReports = ['trial_balance', 'income_statement', 'balance_sheet', 'retained_earnings'];
    if (!in_array($reportType, $validReports)) {
        echo json_encode(['success' => false, 'message' => 'Invalid report type']);
        exit;
    }
    
    // Generate appropriate report
    switch ($reportType) {
        case 'trial_balance':
            $result = generateTrialBalance($pdo, $dateType, $asOfDate, $startDate, $endDate, $includeAdjusting, $showZeroBalances);
            break;
        case 'income_statement':
            $result = generateIncomeStatement($pdo, $startDate, $endDate, $includeAdjusting);
            break;
        case 'balance_sheet':
            $result = generateBalanceSheet($pdo, $asOfDate, $includeAdjusting);
            break;
        case 'retained_earnings':
            $result = generateRetainedEarningsStatement($pdo, $startDate, $endDate, $includeAdjusting);
            break;
    }
    
    echo json_encode($result);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

/**
 * Generate Trial Balance Report
 */
function generateTrialBalance($pdo, $dateType, $asOfDate, $startDate, $endDate, $includeAdjusting, $showZeroBalances) {
    // Determine the date for calculation
    $reportDate = ($dateType === 'as_of') ? $asOfDate : $endDate;
    
    // Build adjusting entry filter
    $adjustingFilter = $includeAdjusting ? '' : ' AND je.is_adjusting_entry = 0';
    
    // Get all active accounts with their balances calculated from journal entries
    $sql = "
        SELECT 
            a.account_number,
            a.name as account_name,
            a.category,
            a.subcategory,
            a.normal_side,
            COALESCE(SUM(jel.debit_amount), 0) as total_debits,
            COALESCE(SUM(jel.credit_amount), 0) as total_credits
        FROM accounts a
        LEFT JOIN journal_entry_lines jel ON a.account_number = jel.account_number
        LEFT JOIN journal_entries je ON jel.journal_entry_id = je.entry_id
        WHERE a.is_active = 1
            AND (je.status = 'approved' OR je.status IS NULL)
            AND (je.entry_date <= :report_date OR je.entry_date IS NULL)
            $adjustingFilter
        GROUP BY a.account_number, a.name, a.category, a.subcategory, a.normal_side
        ORDER BY a.account_number
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':report_date' => $reportDate]);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate balances for each account
    $trialBalanceData = [];
    $totalDebits = 0;
    $totalCredits = 0;
    
    foreach ($accounts as $account) {
        $debits = floatval($account['total_debits']);
        $credits = floatval($account['total_credits']);
        $balance = $debits - $credits;
        
        // Determine debit or credit balance based on normal side
        $debitBalance = 0;
        $creditBalance = 0;
        
        if ($balance > 0) {
            $debitBalance = $balance;
        } else if ($balance < 0) {
            $creditBalance = abs($balance);
        }
        
        // Skip zero balances if option is not checked
        if (!$showZeroBalances && $balance == 0) {
            continue;
        }
        
        $trialBalanceData[] = [
            'account_number' => $account['account_number'],
            'account_name' => $account['account_name'],
            'category' => $account['category'],
            'debit_balance' => $debitBalance,
            'credit_balance' => $creditBalance
        ];
        
        $totalDebits += $debitBalance;
        $totalCredits += $creditBalance;
    }
    
    // Generate HTML
    $html = '<table class="report-table">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th>Account Number</th>';
    $html .= '<th>Account Name</th>';
    $html .= '<th>Category</th>';
    $html .= '<th class="text-right">Debit</th>';
    $html .= '<th class="text-right">Credit</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    
    foreach ($trialBalanceData as $row) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($row['account_number']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['account_name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['category']) . '</td>';
        $html .= '<td class="text-right amount">' . ($row['debit_balance'] > 0 ? '$' . number_format($row['debit_balance'], 2) : '-') . '</td>';
        $html .= '<td class="text-right amount">' . ($row['credit_balance'] > 0 ? '$' . number_format($row['credit_balance'], 2) : '-') . '</td>';
        $html .= '</tr>';
    }
    
    // Total row
    $html .= '<tr class="total-row">';
    $html .= '<td colspan="3"><strong>TOTAL</strong></td>';
    $html .= '<td class="text-right amount">$' . number_format($totalDebits, 2) . '</td>';
    $html .= '<td class="text-right amount">$' . number_format($totalCredits, 2) . '</td>';
    $html .= '</tr>';
    
    $html .= '</tbody>';
    $html .= '</table>';
    
    // Check if balanced
    $difference = abs($totalDebits - $totalCredits);
    if ($difference > 0.01) {
        $html .= '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-top: 20px;">';
        $html .= '<strong>⚠️ Warning:</strong> Trial balance is out of balance by $' . number_format($difference, 2);
        $html .= '</div>';
    } else {
        $html .= '<div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-top: 20px;">';
        $html .= '<strong>✅ Success:</strong> Trial balance is in balance. Total debits equal total credits.';
        $html .= '</div>';
    }
    
    $reportDateText = ($dateType === 'as_of') ? 
        'As of ' . date('F j, Y', strtotime($asOfDate)) :
        'For the period ' . date('F j, Y', strtotime($startDate)) . ' to ' . date('F j, Y', strtotime($endDate));
    
    return [
        'success' => true,
        'report_type' => 'trial_balance',
        'report_title' => 'Trial Balance',
        'report_date_text' => $reportDateText,
        'report_html' => $html,
        'data' => $trialBalanceData,
        'totals' => [
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'difference' => $difference
        ]
    ];
}

/**
 * Generate Income Statement
 */
function generateIncomeStatement($pdo, $startDate, $endDate, $includeAdjusting) {
    $adjustingFilter = $includeAdjusting ? '' : ' AND je.is_adjusting_entry = 0';
    
    // Get revenue accounts
    $sql = "
        SELECT 
            a.account_number,
            a.name as account_name,
            a.subcategory,
            COALESCE(SUM(jel.credit_amount), 0) - COALESCE(SUM(jel.debit_amount), 0) as balance
        FROM accounts a
        LEFT JOIN journal_entry_lines jel ON a.account_number = jel.account_number
        LEFT JOIN journal_entries je ON jel.journal_entry_id = je.entry_id
        WHERE a.is_active = 1
            AND a.category = 'Revenue'
            AND je.status = 'approved'
            AND je.entry_date BETWEEN :start_date AND :end_date
            $adjustingFilter
        GROUP BY a.account_number, a.name, a.subcategory
        ORDER BY a.account_number
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
    $revenues = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get expense accounts
    $sql = "
        SELECT 
            a.account_number,
            a.name as account_name,
            a.subcategory,
            COALESCE(SUM(jel.debit_amount), 0) - COALESCE(SUM(jel.credit_amount), 0) as balance
        FROM accounts a
        LEFT JOIN journal_entry_lines jel ON a.account_number = jel.account_number
        LEFT JOIN journal_entries je ON jel.journal_entry_id = je.entry_id
        WHERE a.is_active = 1
            AND a.category = 'Expenses'
            AND je.status = 'approved'
            AND je.entry_date BETWEEN :start_date AND :end_date
            $adjustingFilter
        GROUP BY a.account_number, a.name, a.subcategory
        ORDER BY a.account_number
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $totalRevenue = 0;
    foreach ($revenues as $revenue) {
        $totalRevenue += floatval($revenue['balance']);
    }
    
    $totalExpenses = 0;
    foreach ($expenses as $expense) {
        $totalExpenses += floatval($expense['balance']);
    }
    
    $netIncome = $totalRevenue - $totalExpenses;
    
    // Generate HTML
    $html = '<table class="report-table">';
    
    // Revenue Section
    $html .= '<thead><tr class="category-header"><th colspan="2">REVENUE</th></tr></thead>';
    $html .= '<tbody>';
    
    if (empty($revenues)) {
        $html .= '<tr><td colspan="2" style="text-align: center; color: #666;">No revenue accounts found for this period</td></tr>';
    } else {
        foreach ($revenues as $revenue) {
            if (floatval($revenue['balance']) == 0) continue;
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($revenue['account_number']) . ' - ' . htmlspecialchars($revenue['account_name']) . '</td>';
            $html .= '<td class="text-right amount">$' . number_format(floatval($revenue['balance']), 2) . '</td>';
            $html .= '</tr>';
        }
    }
    
    $html .= '<tr class="subtotal-row">';
    $html .= '<td><strong>Total Revenue</strong></td>';
    $html .= '<td class="text-right amount"><strong>$' . number_format($totalRevenue, 2) . '</strong></td>';
    $html .= '</tr>';
    $html .= '</tbody>';
    
    // Expenses Section
    $html .= '<thead><tr class="category-header"><th colspan="2">EXPENSES</th></tr></thead>';
    $html .= '<tbody>';
    
    if (empty($expenses)) {
        $html .= '<tr><td colspan="2" style="text-align: center; color: #666;">No expense accounts found for this period</td></tr>';
    } else {
        foreach ($expenses as $expense) {
            if (floatval($expense['balance']) == 0) continue;
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($expense['account_number']) . ' - ' . htmlspecialchars($expense['account_name']) . '</td>';
            $html .= '<td class="text-right amount">$' . number_format(floatval($expense['balance']), 2) . '</td>';
            $html .= '</tr>';
        }
    }
    
    $html .= '<tr class="subtotal-row">';
    $html .= '<td><strong>Total Expenses</strong></td>';
    $html .= '<td class="text-right amount"><strong>$' . number_format($totalExpenses, 2) . '</strong></td>';
    $html .= '</tr>';
    $html .= '</tbody>';
    
    // Net Income
    $html .= '<tfoot>';
    $html .= '<tr class="total-row">';
    $html .= '<td><strong>NET INCOME ' . ($netIncome >= 0 ? '(PROFIT)' : '(LOSS)') . '</strong></td>';
    $html .= '<td class="text-right amount" style="color: ' . ($netIncome >= 0 ? '#28a745' : '#dc3545') . ';">';
    $html .= '<strong>$' . number_format($netIncome, 2) . '</strong></td>';
    $html .= '</tr>';
    $html .= '</tfoot>';
    
    $html .= '</table>';
    
    $reportDateText = 'For the period from ' . date('F j, Y', strtotime($startDate)) . ' to ' . date('F j, Y', strtotime($endDate));
    
    return [
        'success' => true,
        'report_type' => 'income_statement',
        'report_title' => 'Income Statement',
        'report_date_text' => $reportDateText,
        'report_html' => $html,
        'data' => [
            'revenues' => $revenues,
            'expenses' => $expenses
        ],
        'totals' => [
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'net_income' => $netIncome
        ]
    ];
}

/**
 * Generate Balance Sheet
 */
function generateBalanceSheet($pdo, $asOfDate, $includeAdjusting) {
    $adjustingFilter = $includeAdjusting ? '' : ' AND je.is_adjusting_entry = 0';
    
    // Get Assets
    $sql = "
        SELECT 
            a.account_number,
            a.name as account_name,
            a.subcategory,
            COALESCE(SUM(jel.debit_amount), 0) - COALESCE(SUM(jel.credit_amount), 0) as balance
        FROM accounts a
        LEFT JOIN journal_entry_lines jel ON a.account_number = jel.account_number
        LEFT JOIN journal_entries je ON jel.journal_entry_id = je.entry_id
        WHERE a.is_active = 1
            AND a.category = 'Assets'
            AND (je.status = 'approved' OR je.status IS NULL)
            AND (je.entry_date <= :as_of_date OR je.entry_date IS NULL)
            $adjustingFilter
        GROUP BY a.account_number, a.name, a.subcategory
        ORDER BY a.account_number
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':as_of_date' => $asOfDate]);
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get Liabilities
    $sql = "
        SELECT 
            a.account_number,
            a.name as account_name,
            a.subcategory,
            COALESCE(SUM(jel.credit_amount), 0) - COALESCE(SUM(jel.debit_amount), 0) as balance
        FROM accounts a
        LEFT JOIN journal_entry_lines jel ON a.account_number = jel.account_number
        LEFT JOIN journal_entries je ON jel.journal_entry_id = je.entry_id
        WHERE a.is_active = 1
            AND a.category = 'Liabilities'
            AND (je.status = 'approved' OR je.status IS NULL)
            AND (je.entry_date <= :as_of_date OR je.entry_date IS NULL)
            $adjustingFilter
        GROUP BY a.account_number, a.name, a.subcategory
        ORDER BY a.account_number
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':as_of_date' => $asOfDate]);
    $liabilities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get Equity (excluding Retained Earnings temporarily)
    $sql = "
        SELECT 
            a.account_number,
            a.name as account_name,
            a.subcategory,
            COALESCE(SUM(jel.credit_amount), 0) - COALESCE(SUM(jel.debit_amount), 0) as balance
        FROM accounts a
        LEFT JOIN journal_entry_lines jel ON a.account_number = jel.account_number
        LEFT JOIN journal_entries je ON jel.journal_entry_id = je.entry_id
        WHERE a.is_active = 1
            AND a.category = 'Equity'
            AND a.name != 'Retained Earnings'
            AND (je.status = 'approved' OR je.status IS NULL)
            AND (je.entry_date <= :as_of_date OR je.entry_date IS NULL)
            $adjustingFilter
        GROUP BY a.account_number, a.name, a.subcategory
        ORDER BY a.account_number
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':as_of_date' => $asOfDate]);
    $equity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate Retained Earnings (Net Income from beginning to as_of_date)
    // Revenue - Expenses up to as_of_date
    $sql = "
        SELECT 
            COALESCE(SUM(CASE WHEN a.category = 'Revenue' THEN jel.credit_amount - jel.debit_amount ELSE 0 END), 0) as total_revenue,
            COALESCE(SUM(CASE WHEN a.category = 'Expenses' THEN jel.debit_amount - jel.credit_amount ELSE 0 END), 0) as total_expenses
        FROM journal_entry_lines jel
        JOIN journal_entries je ON jel.journal_entry_id = je.entry_id
        JOIN accounts a ON jel.account_number = a.account_number
        WHERE je.status = 'approved'
            AND je.entry_date <= :as_of_date
            $adjustingFilter
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':as_of_date' => $asOfDate]);
    $incomeData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $totalRevenue = floatval($incomeData['total_revenue']);
    $totalExpenses = floatval($incomeData['total_expenses']);
    $retainedEarnings = $totalRevenue - $totalExpenses;
    
    // Calculate totals
    $totalAssets = 0;
    foreach ($assets as $asset) {
        $totalAssets += floatval($asset['balance']);
    }
    
    $totalLiabilities = 0;
    foreach ($liabilities as $liability) {
        $totalLiabilities += floatval($liability['balance']);
    }
    
    $totalEquity = $retainedEarnings;
    foreach ($equity as $eq) {
        $totalEquity += floatval($eq['balance']);
    }
    
    $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;
    
    // Generate HTML
    $html = '<table class="report-table">';
    
    // Assets Section
    $html .= '<thead><tr class="category-header"><th colspan="2">ASSETS</th></tr></thead>';
    $html .= '<tbody>';
    
    foreach ($assets as $asset) {
        if (floatval($asset['balance']) == 0) continue;
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($asset['account_number']) . ' - ' . htmlspecialchars($asset['account_name']) . '</td>';
        $html .= '<td class="text-right amount">$' . number_format(floatval($asset['balance']), 2) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '<tr class="subtotal-row">';
    $html .= '<td><strong>Total Assets</strong></td>';
    $html .= '<td class="text-right amount"><strong>$' . number_format($totalAssets, 2) . '</strong></td>';
    $html .= '</tr>';
    $html .= '</tbody>';
    
    // Liabilities Section
    $html .= '<thead><tr class="category-header"><th colspan="2">LIABILITIES</th></tr></thead>';
    $html .= '<tbody>';
    
    foreach ($liabilities as $liability) {
        if (floatval($liability['balance']) == 0) continue;
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($liability['account_number']) . ' - ' . htmlspecialchars($liability['account_name']) . '</td>';
        $html .= '<td class="text-right amount">$' . number_format(floatval($liability['balance']), 2) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '<tr class="subtotal-row">';
    $html .= '<td><strong>Total Liabilities</strong></td>';
    $html .= '<td class="text-right amount"><strong>$' . number_format($totalLiabilities, 2) . '</strong></td>';
    $html .= '</tr>';
    $html .= '</tbody>';
    
    // Equity Section
    $html .= '<thead><tr class="category-header"><th colspan="2">EQUITY</th></tr></thead>';
    $html .= '<tbody>';
    
    foreach ($equity as $eq) {
        if (floatval($eq['balance']) == 0) continue;
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($eq['account_number']) . ' - ' . htmlspecialchars($eq['account_name']) . '</td>';
        $html .= '<td class="text-right amount">$' . number_format(floatval($eq['balance']), 2) . '</td>';
        $html .= '</tr>';
    }
    
    // Add Retained Earnings
    $html .= '<tr>';
    $html .= '<td>Retained Earnings</td>';
    $html .= '<td class="text-right amount">$' . number_format($retainedEarnings, 2) . '</td>';
    $html .= '</tr>';
    
    $html .= '<tr class="subtotal-row">';
    $html .= '<td><strong>Total Equity</strong></td>';
    $html .= '<td class="text-right amount"><strong>$' . number_format($totalEquity, 2) . '</strong></td>';
    $html .= '</tr>';
    $html .= '</tbody>';
    
    // Total Liabilities and Equity
    $html .= '<tfoot>';
    $html .= '<tr class="total-row">';
    $html .= '<td><strong>TOTAL LIABILITIES AND EQUITY</strong></td>';
    $html .= '<td class="text-right amount"><strong>$' . number_format($totalLiabilitiesAndEquity, 2) . '</strong></td>';
    $html .= '</tr>';
    $html .= '</tfoot>';
    
    $html .= '</table>';
    
    // Check if balanced
    $difference = abs($totalAssets - $totalLiabilitiesAndEquity);
    if ($difference > 0.01) {
        $html .= '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-top: 20px;">';
        $html .= '<strong>⚠️ Warning:</strong> Balance sheet is out of balance by $' . number_format($difference, 2);
        $html .= '</div>';
    } else {
        $html .= '<div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-top: 20px;">';
        $html .= '<strong>✅ Success:</strong> Balance sheet is balanced. Assets = Liabilities + Equity.';
        $html .= '</div>';
    }
    
    $reportDateText = 'As of ' . date('F j, Y', strtotime($asOfDate));
    
    return [
        'success' => true,
        'report_type' => 'balance_sheet',
        'report_title' => 'Balance Sheet',
        'report_date_text' => $reportDateText,
        'report_html' => $html,
        'data' => [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'retained_earnings' => $retainedEarnings
        ],
        'totals' => [
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'difference' => $difference
        ]
    ];
}

/**
 * Generate Retained Earnings Statement
 */
function generateRetainedEarningsStatement($pdo, $startDate, $endDate, $includeAdjusting) {
    $adjustingFilter = $includeAdjusting ? '' : ' AND je.is_adjusting_entry = 0';
    
    // Get beginning retained earnings (from beginning of time to day before start date)
    $dayBeforeStart = date('Y-m-d', strtotime($startDate . ' -1 day'));
    
    $sql = "
        SELECT 
            COALESCE(SUM(CASE WHEN a.category = 'Revenue' THEN jel.credit_amount - jel.debit_amount ELSE 0 END), 0) as total_revenue,
            COALESCE(SUM(CASE WHEN a.category = 'Expenses' THEN jel.debit_amount - jel.credit_amount ELSE 0 END), 0) as total_expenses
        FROM journal_entry_lines jel
        JOIN journal_entries je ON jel.journal_entry_id = je.entry_id
        JOIN accounts a ON jel.account_number = a.account_number
        WHERE je.status = 'approved'
            AND je.entry_date <= :day_before_start
            $adjustingFilter
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':day_before_start' => $dayBeforeStart]);
    $beginningData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $beginningRetainedEarnings = floatval($beginningData['total_revenue']) - floatval($beginningData['total_expenses']);
    
    // Get net income for the period
    $sql = "
        SELECT 
            COALESCE(SUM(CASE WHEN a.category = 'Revenue' THEN jel.credit_amount - jel.debit_amount ELSE 0 END), 0) as total_revenue,
            COALESCE(SUM(CASE WHEN a.category = 'Expenses' THEN jel.debit_amount - jel.credit_amount ELSE 0 END), 0) as total_expenses
        FROM journal_entry_lines jel
        JOIN journal_entries je ON jel.journal_entry_id = je.entry_id
        JOIN accounts a ON jel.account_number = a.account_number
        WHERE je.status = 'approved'
            AND je.entry_date BETWEEN :start_date AND :end_date
            $adjustingFilter
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
    $periodData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $periodRevenue = floatval($periodData['total_revenue']);
    $periodExpenses = floatval($periodData['total_expenses']);
    $netIncome = $periodRevenue - $periodExpenses;
    
    // Get dividends paid (if any - look for dividend accounts in equity)
    $sql = "
        SELECT 
            COALESCE(SUM(jel.debit_amount - jel.credit_amount), 0) as dividends
        FROM journal_entry_lines jel
        JOIN journal_entries je ON jel.journal_entry_id = je.entry_id
        JOIN accounts a ON jel.account_number = a.account_number
        WHERE je.status = 'approved'
            AND je.entry_date BETWEEN :start_date AND :end_date
            AND a.category = 'Equity'
            AND (a.name LIKE '%Dividend%' OR a.subcategory LIKE '%Dividend%')
            $adjustingFilter
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
    $dividends = floatval($stmt->fetchColumn());
    
    // Calculate ending retained earnings
    $endingRetainedEarnings = $beginningRetainedEarnings + $netIncome - $dividends;
    
    // Generate HTML
    $html = '<table class="report-table">';
    $html .= '<tbody>';
    
    $html .= '<tr>';
    $html .= '<td><strong>Retained Earnings, Beginning of Period</strong></td>';
    $html .= '<td class="text-right amount">$' . number_format($beginningRetainedEarnings, 2) . '</td>';
    $html .= '</tr>';
    
    $html .= '<tr>';
    $html .= '<td style="padding-left: 30px;">Add: Net Income for the Period</td>';
    $html .= '<td class="text-right amount" style="color: ' . ($netIncome >= 0 ? '#28a745' : '#dc3545') . ';">$' . number_format($netIncome, 2) . '</td>';
    $html .= '</tr>';
    
    if ($dividends > 0) {
        $html .= '<tr>';
        $html .= '<td style="padding-left: 30px;">Less: Dividends Paid</td>';
        $html .= '<td class="text-right amount" style="color: #dc3545;">($' . number_format($dividends, 2) . ')</td>';
        $html .= '</tr>';
    }
    
    $html .= '<tr class="total-row">';
    $html .= '<td><strong>Retained Earnings, End of Period</strong></td>';
    $html .= '<td class="text-right amount"><strong>$' . number_format($endingRetainedEarnings, 2) . '</strong></td>';
    $html .= '</tr>';
    
    $html .= '</tbody>';
    $html .= '</table>';
    
    // Add summary
    $html .= '<div style="background: #e3f2fd; padding: 15px; border-radius: 4px; margin-top: 20px;">';
    $html .= '<h4 style="margin: 0 0 10px 0; color: #2980b9;">Summary</h4>';
    $html .= '<p style="margin: 5px 0;"><strong>Beginning Balance:</strong> $' . number_format($beginningRetainedEarnings, 2) . '</p>';
    $html .= '<p style="margin: 5px 0;"><strong>Net Income:</strong> $' . number_format($netIncome, 2) . '</p>';
    if ($dividends > 0) {
        $html .= '<p style="margin: 5px 0;"><strong>Dividends:</strong> $' . number_format($dividends, 2) . '</p>';
    }
    $html .= '<p style="margin: 5px 0;"><strong>Ending Balance:</strong> $' . number_format($endingRetainedEarnings, 2) . '</p>';
    $html .= '</div>';
    
    $reportDateText = 'For the period from ' . date('F j, Y', strtotime($startDate)) . ' to ' . date('F j, Y', strtotime($endDate));
    
    return [
        'success' => true,
        'report_type' => 'retained_earnings',
        'report_title' => 'Statement of Retained Earnings',
        'report_date_text' => $reportDateText,
        'report_html' => $html,
        'data' => [
            'beginning_retained_earnings' => $beginningRetainedEarnings,
            'net_income' => $netIncome,
            'dividends' => $dividends,
            'ending_retained_earnings' => $endingRetainedEarnings
        ]
    ];
}
?>