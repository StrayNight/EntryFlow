<?php
require_once 'config.php'; 
session_start();  
requireLogin();

$db = getDB();
$userId = (int)$_SESSION['user_id'];

// Get filter values from URL
$period = $_GET['period'] ?? 'this_month';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');
$type = $_GET['type'] ?? 'all';

$whereClause = "WHERE t.user_id = $userId";

if ($startDate) {
    $startEscaped = $db->real_escape_string($startDate);
    $whereClause .= " AND t.transaction_date >= '$startEscaped'";
}
if ($endDate) {
    $endEscaped = $db->real_escape_string($endDate);
    $whereClause .= " AND t.transaction_date <= '$endEscaped'";
}
if ($type === 'income' || $type === 'expense') {
    $whereClause .= " AND t.type = '$type'";
}

$query = "
    SELECT t.*, c.name as client_name, cat.name as category_name 
    FROM transactions t 
    LEFT JOIN clients c ON t.client_id = c.id 
    LEFT JOIN categories cat ON t.category_id = cat.id 
    $whereClause 
    ORDER BY t.transaction_date DESC
";
$transactions = $db->query($query)->fetch_all(MYSQLI_ASSOC);

// --- NEW: EXPORT TO CSV LOGIC ---
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="EntryFlow_Report_' . date('Ymd') . '.csv"');
    $output = fopen('php://output', 'w');
    
    // Write Headers
    fputcsv($output, ['Date', 'Description', 'Category', 'Client/Payee', 'Type', 'Amount', 'Status']);
    
    // Write Data Rows
    foreach ($transactions as $tx) {
        fputcsv($output, [
            date('M d, Y', strtotime($tx['transaction_date'])),
            $tx['description'],
            $tx['category_name'] ?? 'Uncategorized',
            $tx['client_name'] ?? '—',
            ucfirst($tx['type']),
            $tx['amount'],
            ucfirst($tx['status'])
        ]);
    }
    fclose($output);
    exit; // Stop HTML from rendering into the CSV file
}
// ---------------------------------

$totals = ['income' => 0, 'expense' => 0];
$categoryBreakdown = [];

foreach ($transactions as $t) {
    $totals[$t['type']] += $t['amount'];
    
    // Calculate Category Breakdown
    $catName = $t['category_name'] ?? 'Uncategorized';
    if (!isset($categoryBreakdown[$catName])) {
        $categoryBreakdown[$catName] = ['income' => 0, 'expense' => 0];
    }
    $categoryBreakdown[$catName][$t['type']] += $t['amount'];
}

$grossProfit = $totals['income'] - $totals['expense'];

include 'includes/header.php'; 
?>

<form method="GET" action="reports.php" class="report-filters" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 20px; background: var(--surface); padding: 15px; border-radius: 8px; border: 1px solid var(--border-color);">
    <div>
        <label style="display: block; font-size: 12px; margin-bottom: 5px;">Period</label>
        <select name="period" id="periodSelect" class="sb" style="width: 150px;">
            <option value="this_month" <?= $period == 'this_month' ? 'selected' : '' ?>>This Month</option>
            <option value="last_month" <?= $period == 'last_month' ? 'selected' : '' ?>>Last Month</option>
            <option value="this_year" <?= $period == 'this_year' ? 'selected' : '' ?>>This Year</option>
            <option value="custom" <?= $period == 'custom' ? 'selected' : '' ?>>Custom Range</option>
        </select>
    </div>
    <div>
        <label style="display: block; font-size: 12px; margin-bottom: 5px;">Start Date</label>
        <input type="date" name="start_date" id="startDate" class="sb" value="<?= htmlspecialchars($startDate) ?>">
    </div>
    <div>
        <label style="display: block; font-size: 12px; margin-bottom: 5px;">End Date</label>
        <input type="date" name="end_date" id="endDate" class="sb" value="<?= htmlspecialchars($endDate) ?>">
    </div>
    <div>
        <label style="display: block; font-size: 12px; margin-bottom: 5px;">Type</label>
        <select name="type" class="sb" style="width: 120px;">
            <option value="all" <?= $type == 'all' ? 'selected' : '' ?>>All</option>
            <option value="income" <?= $type == 'income' ? 'selected' : '' ?>>Income Only</option>
            <option value="expense" <?= $type == 'expense' ? 'selected' : '' ?>>Expense Only</option>
        </select>
    </div>
    <div style="display: flex; gap: 10px;">
        <button type="submit" class="btn btn-primary" style="height: 42px;">📊 Generate Report</button>
        <a href="reports.php?period=<?= $period ?>&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>&type=<?= $type ?>&export=csv" class="btn" style="height: 42px; display: flex; align-items: center; background: var(--surface-2); color: var(--text);">📥 Export CSV</a>
    </div>
</form>

<div class="grid-3">
    <div class="card-income">
        <h3>Total Income</h3>
        <h2><?= $sym ?><?= number_format($totals['income'], 2) ?></h2>
    </div>
    <div class="card-expense">
        <h3>Total Expenses</h3>
        <h2><?= $sym ?><?= number_format($totals['expense'], 2) ?></h2>
    </div>
    <div class="card-profit" <?= $grossProfit < 0 ? 'style="border-left-color: var(--danger) !important;"' : '' ?>>
        <h3>Net Profit</h3>
        <h2 <?= $grossProfit < 0 ? 'style="color: var(--danger) !important;"' : '' ?>>
            <?= $grossProfit < 0 ? '-' : '' ?><?= $sym ?><?= number_format(abs($grossProfit), 2) ?>
        </h2>
    </div>
</div>

<div class="grid" style="grid-template-columns: 1fr 2fr; margin-top: 20px;">
    <div class="table-responsive">
        <h3 style="margin: 0 0 15px 0; font-size: 16px;">Category Breakdown</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th style="text-align: right;">Income</th>
                    <th style="text-align: right;">Expense</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categoryBreakdown)): ?>
                    <tr><td colspan="3" style="text-align: center; padding: 20px; color: var(--text-muted);">No data for this period.</td></tr>
                <?php else: ?>
                    <?php foreach ($categoryBreakdown as $catName => $amts): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($catName) ?></strong></td>
                            <td style="text-align: right; color: var(--primary);"><?= $amts['income'] > 0 ? $sym . number_format($amts['income'], 2) : '-' ?></td>
                            <td style="text-align: right; color: var(--danger);"><?= $amts['expense'] > 0 ? $sym . number_format($amts['expense'], 2) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="table-responsive">
        <h3 style="margin: 0 0 15px 0; font-size: 16px;">Transaction Record</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr><td colspan="4" style="text-align: center; padding: 20px; color: var(--text-muted);">No transactions found.</td></tr>
                <?php else: ?>
                    <?php foreach ($transactions as $tx): ?>
                        <tr>
                            <td><?= date('M d, Y', strtotime($tx['transaction_date'])) ?></td>
                            <td><?= htmlspecialchars($tx['description']) ?></td>
                            <td><?= htmlspecialchars($tx['category_name'] ?? '—') ?></td>
                            <td class="<?= $tx['type'] === 'income' ? 'ap' : 'an' ?>">
                                <?= $tx['type'] === 'income' ? '+' : '-' ?><?= $sym ?><?= number_format($tx['amount'], 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Auto-adjust Start and End dates when Period is changed
document.getElementById('periodSelect').addEventListener('change', function() {
    const period = this.value;
    const startInput = document.getElementById('startDate');
    const endInput = document.getElementById('endDate');
    
    const today = new Date();
    let start, end;
    
    if (period === 'this_month') {
        start = new Date(today.getFullYear(), today.getMonth(), 1);
        end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    } else if (period === 'last_month') {
        start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        end = new Date(today.getFullYear(), today.getMonth(), 0);
    } else if (period === 'this_year') {
        start = new Date(today.getFullYear(), 0, 1);
        end = new Date(today.getFullYear(), 11, 31);
    }
    
    if (start && end) {
        // Format to YYYY-MM-DD for the input fields
        startInput.value = start.getFullYear() + '-' + String(start.getMonth() + 1).padStart(2, '0') + '-' + String(start.getDate()).padStart(2, '0');
        endInput.value = end.getFullYear() + '-' + String(end.getMonth() + 1).padStart(2, '0') + '-' + String(end.getDate()).padStart(2, '0');
    }
});
</script>

<?php include 'includes/footer.php'; ?>