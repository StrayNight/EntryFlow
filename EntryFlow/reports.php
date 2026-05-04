<?php
session_start();
require_once 'config.php';
requireLogin();

$db = getDB();
$userId = (int)$_SESSION['user_id'];

// SECURED: Escaping the input to prevent SQL Injection
$selectedMonth = $db->real_escape_string($_GET['month'] ?? date('Y-m'));

// Fetch totals for the selected month
$query = "
    SELECT type, SUM(amount) AS total 
    FROM transactions 
    WHERE user_id = $userId AND DATE_FORMAT(transaction_date, '%Y-%m') = '$selectedMonth'
    GROUP BY type
";
$result = $db->query($query);
$totals = ['income' => 0, 'expense' => 0];

while ($row = $result->fetch_assoc()) {
    $totals[$row['type']] = (float)$row['total'];
}

$grossProfit = $totals['income'] - $totals['expense'];

include 'includes/header.php'; 
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <form method="GET" action="reports.php" style="display: flex; gap: 10px; align-items: center;">
        <label for="month" style="font-size: 14px;">Select Month:</label>
        <input type="month" name="month" id="month" class="sb" value="<?= htmlspecialchars($selectedMonth) ?>">
        <button type="submit" class="btn btn-primary">Generate</button>
    </form>

    <a href="api/reports.php?export=csv&month=<?= $selectedMonth ?>" class="btn" style="background: var(--surface-2); color: var(--text-main); text-decoration: none;">
        📥 Export to CSV
    </a>
</div>

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
        <h3>Gross Profit</h3>
        <h2 <?= $grossProfit < 0 ? 'style="color: var(--danger) !important;"' : '' ?>>
            <?= $grossProfit < 0 ? '-' : '' ?><?= $sym ?><?= number_format(abs($grossProfit), 2) ?>
        </h2>
    </div>
</div>

<div class="tc" style="padding: 20px; background: var(--surface-1); border-radius: 12px;">
    <h3>Report Summary for <?= date('F Y', strtotime($selectedMonth . '-01')) ?></h3>
    <p style="color: var(--text-muted); margin-top: 10px;">
        Use the Export button above to download a detailed CSV file of all transactions for this period. 
        This data is fetched directly from the database and calculated securely via PHP.
    </p>
</div>

<?php include 'includes/footer.php'; ?>