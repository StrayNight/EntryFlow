<?php
session_start();
require_once 'config.php';
requireLogin();

$db = getDB();
$userId = (int)$_SESSION['user_id'];

$selectedMonth = $_GET['month'] ?? date('Y-m');
$selectedMonth = date('Y-m', strtotime($selectedMonth . '-01'));
$monthStart = date('Y-m-01', strtotime($selectedMonth . '-01'));
$monthEnd = date('Y-m-t', strtotime($selectedMonth . '-01'));
$previousMonth = date('Y-m', strtotime($monthStart . ' -1 month'));

function fetchTotal($db, $userId, $type, $start, $end) {
    $sql = "SELECT IFNULL(SUM(amount), 0) AS total FROM transactions WHERE user_id = ? AND type = ? AND transaction_date BETWEEN ? AND ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('isss', $userId, $type, $start, $end);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return (float)$result['total'];
}

$totalExpense = fetchTotal($db, $userId, 'expense', $monthStart, $monthEnd);
$totalIncome = fetchTotal($db, $userId, 'income', $monthStart, $monthEnd);
$prevExpense = fetchTotal($db, $userId, 'expense', date('Y-m-01', strtotime($previousMonth . '-01')), date('Y-m-t', strtotime($previousMonth . '-01')));
$expenseRatio = $totalIncome > 0 ? round(($totalExpense / $totalIncome) * 100, 1) : 0;
$dailyCount = (int)date('t', strtotime($monthStart));
$avgDaily = $dailyCount > 0 ? round($totalExpense / $dailyCount, 2) : 0;
$vendorCnt = (int)$db->query("SELECT COUNT(DISTINCT IFNULL(client_id, description)) FROM transactions WHERE user_id=$userId AND type='expense' AND transaction_date BETWEEN '$monthStart' AND '$monthEnd'")->fetch_row()[0];
$vendorCnt = max($vendorCnt, 0);
$avgVendorSpend = $vendorCnt > 0 ? round($totalExpense / $vendorCnt, 2) : 0;
$expenseChange = $prevExpense > 0 ? round((($totalExpense - $prevExpense) / $prevExpense) * 100, 1) : ($totalExpense > 0 ? 100 : 0);
$expenseChangeLabel = $expenseChange >= 0 ? 'increase' : 'decrease';

// Last 6 months expense trend
$trendStart = date('Y-m-01', strtotime($monthStart . ' -5 months'));
$trendStmt = $db->prepare("SELECT DATE_FORMAT(transaction_date, '%Y-%m') AS month_label, IFNULL(SUM(amount),0) AS total FROM transactions WHERE user_id = ? AND type = 'expense' AND transaction_date BETWEEN ? AND ? GROUP BY month_label ORDER BY month_label ASC");
$trendStmt->bind_param('iss', $userId, $trendStart, $monthEnd);
$trendStmt->execute();
$trendRows = $trendStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$trendData = [];
for ($i = 5; $i >= 0; $i--) {
    $label = date('Y-m', strtotime($monthStart . " -$i months"));
    $trendData[$label] = 0;
}
foreach ($trendRows as $row) {
    if (isset($trendData[$row['month_label']])) {
        $trendData[$row['month_label']] = (float)$row['total'];
    }
}

// Expense by category for selected month
$catStmt = $db->prepare("SELECT COALESCE(c.name, 'Uncategorized') AS category_name, IFNULL(SUM(t.amount),0) AS total FROM transactions t LEFT JOIN categories c ON c.id = t.category_id WHERE t.user_id = ? AND t.type = 'expense' AND t.transaction_date BETWEEN ? AND ? GROUP BY category_name ORDER BY total DESC LIMIT 5");
$catStmt->bind_param('iss', $userId, $monthStart, $monthEnd);
$catStmt->execute();
$categoryRows = $catStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Daily expense trend for selected month
$dailyStmt = $db->prepare("SELECT DAY(transaction_date) AS day, IFNULL(SUM(amount),0) AS total FROM transactions WHERE user_id = ? AND type = 'expense' AND transaction_date BETWEEN ? AND ? GROUP BY day ORDER BY day ASC");
$dailyStmt->bind_param('iss', $userId, $monthStart, $monthEnd);
$dailyStmt->execute();
$dailyRows = $dailyStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$dailyData = array_fill(1, $dailyCount, 0.0);
foreach ($dailyRows as $row) {
    $dailyData[(int)$row['day']] = (float)$row['total'];
}

include 'includes/header.php';
?>

<div class="dashboard-intro" style="margin-bottom: 24px;">
    <div>
        <span class="label-soft">Auto date range</span>
        <h2>Expense Dashboard</h2>
        <p class="page-copy">Review spending performance, category breakdowns, and monthly expense flow with a clean accounting-style layout.</p>
    </div>
    <form method="GET" class="top-filters">
        <div class="filter-item">
            <label>Range</label>
            <input type="month" name="month" class="sb" value="<?= htmlspecialchars($selectedMonth) ?>">
        </div>
        <div class="filter-item">
            <label>Services</label>
            <select class="sb" disabled>
                <option>All</option>
            </select>
        </div>
        <div class="filter-item">
            <label>Posts</label>
            <select class="sb" disabled>
                <option>All</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Apply</button>
    </form>
</div>

<div class="expense-kpi-grid">
    <div class="small-card">
        <span>Expense Total</span>
        <strong><?= $sym ?><?= number_format($totalExpense, 2) ?></strong>
        <div class="metric-help">Current month spending.</div>
    </div>
    <div class="small-card">
        <span>Expense Ratio</span>
        <strong><?= number_format($expenseRatio, 1) ?>%</strong>
        <div class="metric-help">Share of revenue spent.</div>
    </div>
    <div class="small-card">
        <span>Avg Daily Spend</span>
        <strong><?= $sym ?><?= number_format($avgDaily, 2) ?></strong>
        <div class="metric-help">Average cost per day.</div>
    </div>
    <div class="small-card">
        <span>Vendor Count</span>
        <strong><?= $vendorCnt ?></strong>
        <div class="metric-help">Unique payees this month.</div>
    </div>
</div>

<div class="expense-pane">
    <div class="chart-panel">
        <div class="panel-header">
            <div>
                <h3>Monthly Expense Trend</h3>
                <p class="panel-copy">Last 6 months of spending, showing the expense momentum across the business.</p>
            </div>
            <span class="badge small">Expense</span>
        </div>
        <div class="chart-bars">
            <?php
            $maxTrend = max(1, max($trendData));
            foreach ($trendData as $monthLabel => $value):
                $display = date('M', strtotime($monthLabel . '-01'));
                $height = round(($value / $maxTrend) * 100);
            ?>
                <div class="chart-bar">
                    <div class="chart-bar-fill" style="height: <?= $height ?>%;"></div>
                    <div class="chart-bar-footer">
                        <strong><?= $sym ?><?= number_format($value, 0) ?></strong>
                        <span><?= $display ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="mini-summary-grid">
        <div class="summary-card bg-light">
            <h4>Expense vs Prev Month</h4>
            <p class="summary-number <?= $expenseChange >= 0 ? 'text-success' : 'text-danger' ?>"><?= $expenseChange >= 0 ? '+' : '' ?><?= $expenseChange ?>%</p>
            <p class="summary-copy">Compared to <?= date('M Y', strtotime($previousMonth . '-01')) ?>.</p>
        </div>
        <div class="summary-card bg-light">
            <h4>Avg Vendor Spend</h4>
            <p class="summary-number"><?= $sym ?><?= number_format($avgVendorSpend, 2) ?></p>
            <p class="summary-copy">Average amount per vendor.</p>
        </div>
        <div class="summary-card bg-light">
            <h4>Expense Coverage</h4>
            <p class="summary-number"><?= $expenseRatio ?>%</p>
            <p class="summary-copy">Portion of income allocated to expenses.</p>
        </div>
        <div class="summary-card bg-light">
            <h4>Projected Savings</h4>
            <p class="summary-number"><?= $sym ?><?= number_format(max(0, $totalIncome - $totalExpense), 2) ?></p>
            <p class="summary-copy">Potential savings this month.</p>
        </div>
    </div>
</div>

<div class="chart-panel chart-area-panel">
    <div class="panel-header">
        <div>
            <h3>Daily Expense Flow</h3>
            <p class="panel-copy">Actual daily spending pattern for the selected month.</p>
        </div>
    </div>
    <div class="area-graph">
        <div class="graph-grid">
            <?php
            $maxDaily = max(1, max($dailyData));
            foreach ($dailyData as $day => $value):
                $height = round(($value / $maxDaily) * 100);
            ?>
                <div class="graph-point">
                    <div class="graph-fill" style="height: <?= $height ?>%;"></div>
                    <span><?= $day ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="chart-panel">
    <div class="panel-header">
        <div>
            <h3>Top Expense Categories</h3>
            <p class="panel-copy">Most expensive categories for the selected month.</p>
        </div>
    </div>
    <div class="category-grid">
        <?php if (empty($categoryRows)): ?>
            <p class="empty-note">No expense categories found for this period.</p>
        <?php else: ?>
            <?php
            $maxCat = max(array_column($categoryRows, 'total')) ?: 1;
            foreach ($categoryRows as $cat):
                $pct = round(($cat['total'] / $maxCat) * 100);
            ?>
                <div class="category-card">
                    <div>
                        <h4><?= htmlspecialchars($cat['category_name']) ?></h4>
                        <span><?= $sym ?><?= number_format($cat['total'], 2) ?></span>
                    </div>
                    <div class="category-bar"><span style="width: <?= $pct ?>%;"></span></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>