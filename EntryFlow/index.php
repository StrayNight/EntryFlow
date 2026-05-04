<?php
session_start();
require_once 'config.php';
requireLogin();

$db   = getDB();
$userId  = (int)$_SESSION['user_id'];

// 1. Fetch Dashboard Statistics
$curMonth = date('Y-m');
$statsQuery = $db->query("SELECT type, SUM(amount) AS t FROM transactions WHERE user_id=$userId AND DATE_FORMAT(transaction_date,'%Y-%m')='$curMonth' GROUP BY type");

$totals = ['income' => 0, 'expense' => 0];
foreach ($statsQuery->fetch_all(MYSQLI_ASSOC) as $row) {
    $totals[$row['type']] = (float)$row['t'];
}

$grossProfit = $totals['income'] - $totals['expense'];
$pendingCnt  = (int)$db->query("SELECT COUNT(*) FROM transactions WHERE user_id=$userId AND status='pending'")->fetch_row()[0];
$clientCnt   = (int)$db->query("SELECT COUNT(*) FROM clients WHERE user_id=$userId")->fetch_row()[0];

// 2. Fetch Recent 5 Transactions
$recentTx = $db->query("
    SELECT t.id, t.description, t.amount, t.type, t.status, t.transaction_date, t.invoice_no, 
           c.name AS client_name, cat.name AS category_name
    FROM transactions t
    LEFT JOIN clients c ON c.id=t.client_id
    LEFT JOIN categories cat ON cat.id=t.category_id
    WHERE t.user_id=$userId 
    ORDER BY t.transaction_date DESC, t.id DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php'; 
?>

<div class="grid">
    <div class="card-income">
        <h3>Gross Income</h3>
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
    
    <div class="card-pending">
        <h3>Pending Transactions</h3>
        <h2><?= $pendingCnt ?></h2>
    </div>
</div>

<div style="display: flex; justify-content: space-between; align-items: center; margin: 30px 0 15px;">
    <h3>Recent Transactions</h3>
</div>

<div class="tc">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Category</th>
                <th>Client</th>
                <th>Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recentTx)): ?>
                <tr><td colspan="6" style="text-align: center; padding: 20px; color: var(--text-muted);">No recent transactions</td></tr>
            <?php else: ?>
                <?php foreach ($recentTx as $tx): ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($tx['transaction_date'])) ?></td>
                        <td class="tdn">
                            <?= htmlspecialchars($tx['description']) ?>
                            <?php if ($tx['invoice_no']): ?><div class="tds">Inv: <?= htmlspecialchars($tx['invoice_no']) ?></div><?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($tx['category_name'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($tx['client_name'] ?? '—') ?></td>
                        <td class="<?= $tx['type'] === 'income' ? 'ap' : 'an' ?>">
                            <?= $tx['type'] === 'income' ? '+' : '-' ?>₱<?= number_format($tx['amount'], 2) ?>
                        </td>
                        <td>
                            <?php $statusClass = $tx['status'] === 'paid' ? 'bp2' : ($tx['status'] === 'pending' ? 'bpd' : 'bod'); ?>
                            <span class="badge <?= $statusClass ?>"><?= ucfirst($tx['status']) ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>