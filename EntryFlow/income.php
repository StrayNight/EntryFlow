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

$totalIncome = fetchTotal($db, $userId, 'income', $monthStart, $monthEnd);
$totalExpense = fetchTotal($db, $userId, 'expense', $monthStart, $monthEnd);
$prevIncome = fetchTotal($db, $userId, 'income', date('Y-m-01', strtotime($previousMonth . '-01')), date('Y-m-t', strtotime($previousMonth . '-01')));
$incomeRatio = $totalIncome > 0 ? round(($totalExpense / $totalIncome) * 100, 1) : 0;
$dailyCount = (int)date('t', strtotime($monthStart));
$avgDaily = $dailyCount > 0 ? round($totalIncome / $dailyCount, 2) : 0;
$customerCnt = (int)$db->query("SELECT COUNT(DISTINCT IFNULL(client_id, description)) FROM transactions WHERE user_id=$userId AND type='income' AND transaction_date BETWEEN '$monthStart' AND '$monthEnd'")->fetch_row()[0];
$customerCnt = max($customerCnt, 0);
$avgCustomerValue = $customerCnt > 0 ? round($totalIncome / $customerCnt, 2) : 0;
$incomeChange = $prevIncome > 0 ? round((($totalIncome - $prevIncome) / $prevIncome) * 100, 1) : ($totalIncome > 0 ? 100 : 0);
$incomeChangeLabel = $incomeChange >= 0 ? 'increase' : 'decrease';

// Last 6 months income trend
$trendStart = date('Y-m-01', strtotime($monthStart . ' -5 months'));
$trendStmt = $db->prepare("SELECT DATE_FORMAT(transaction_date, '%Y-%m') AS month_label, IFNULL(SUM(amount),0) AS total FROM transactions WHERE user_id = ? AND type = 'income' AND transaction_date BETWEEN ? AND ? GROUP BY month_label ORDER BY month_label ASC");
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

// Income by category for selected month
$catStmt = $db->prepare("SELECT COALESCE(c.name, 'Uncategorized') AS category_name, IFNULL(SUM(t.amount),0) AS total FROM transactions t LEFT JOIN categories c ON c.id = t.category_id WHERE t.user_id = ? AND t.type = 'income' AND t.transaction_date BETWEEN ? AND ? GROUP BY category_name ORDER BY total DESC LIMIT 5");
$catStmt->bind_param('iss', $userId, $monthStart, $monthEnd);
$catStmt->execute();
$categoryRows = $catStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Daily income trend for selected month
$dailyStmt = $db->prepare("SELECT DAY(transaction_date) AS day, IFNULL(SUM(amount),0) AS total FROM transactions WHERE user_id = ? AND type = 'income' AND transaction_date BETWEEN ? AND ? GROUP BY day ORDER BY day ASC");
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
        <h2>Income Dashboard</h2>
        <p class="page-copy">Track revenue streams, customer contributions, and monthly income flow with detailed accounting metrics.</p>
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
        <span>Income Total</span>
        <strong><?= $sym ?><?= number_format($totalIncome, 2) ?></strong>
        <div class="metric-help">Current month revenue.</div>
    </div>
    <div class="small-card">
        <span>Expense Ratio</span>
        <strong><?= number_format($incomeRatio, 1) ?>%</strong>
        <div class="metric-help">Expenses as % of revenue.</div>
    </div>
    <div class="small-card">
        <span>Avg Daily Income</span>
        <strong><?= $sym ?><?= number_format($avgDaily, 2) ?></strong>
        <div class="metric-help">Average revenue per day.</div>
    </div>
    <div class="small-card">
        <span>Customer Count</span>
        <strong><?= $customerCnt ?></strong>
        <div class="metric-help">Unique payers this month.</div>
    </div>
</div>

<div class="expense-pane">
    <div class="chart-panel">
        <div class="panel-header">
            <div>
                <h3>Monthly Income Trend</h3>
                <p class="panel-copy">Last 6 months of revenue, showing the income momentum and growth patterns.</p>
            </div>
            <span class="badge small">Income</span>
        </div>
        <div class="chart-bars">
            <?php
            $maxTrend = max(1, max($trendData));
            foreach ($trendData as $monthLabel => $value):
                $display = date('M', strtotime($monthLabel . '-01'));
                $height = round(($value / $maxTrend) * 100);
            ?>
                <div class="chart-bar">
                    <div class="chart-bar-fill" style="height: <?= $height ?>%; background: linear-gradient(180deg, rgba(16,185,129,0.95), rgba(5,150,105,0.75));"></div>
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
            <h4>Income vs Prev Month</h4>
            <p class="summary-number <?= $incomeChange >= 0 ? 'text-success' : 'text-danger' ?>"><?= $incomeChange >= 0 ? '+' : '' ?><?= $incomeChange ?>%</p>
            <p class="summary-copy">Compared to <?= date('M Y', strtotime($previousMonth . '-01')) ?>.</p>
        </div>
        <div class="summary-card bg-light">
            <h4>Avg Customer Value</h4>
            <p class="summary-number"><?= $sym ?><?= number_format($avgCustomerValue, 2) ?></p>
            <p class="summary-copy">Average revenue per customer.</p>
        </div>
        <div class="summary-card bg-light">
            <h4>Income Coverage</h4>
            <p class="summary-number"><?= 100 - $incomeRatio ?>%</p>
            <p class="summary-copy">Net profit margin after expenses.</p>
        </div>
        <div class="summary-card bg-light">
            <h4>Net Profit</h4>
            <p class="summary-number"><?= $sym ?><?= number_format(max(0, $totalIncome - $totalExpense), 2) ?></p>
            <p class="summary-copy">Revenue minus expenses.</p>
        </div>
    </div>
</div>

<div class="chart-panel chart-area-panel">
    <div class="panel-header">
        <div>
            <h3>Daily Income Flow</h3>
            <p class="panel-copy">Actual daily revenue pattern for the selected month.</p>
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
                    <div class="graph-fill" style="height: <?= $height ?>%; background: linear-gradient(180deg, rgba(16,185,129,0.95), rgba(5,182,212,0.35));"></div>
                    <span><?= $day ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="chart-panel">
    <div class="panel-header">
        <div>
            <h3>Top Income Categories</h3>
            <p class="panel-copy">Highest-earning categories for the selected month.</p>
        </div>
    </div>
    <div class="category-grid">
        <?php if (empty($categoryRows)): ?>
            <p class="empty-note">No income categories found for this period.</p>
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
                    <div class="category-bar"><span style="width: <?= $pct ?>%; background: linear-gradient(90deg, #10B981, #14B8A6);"></span></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>