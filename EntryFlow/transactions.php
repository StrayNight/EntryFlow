<?php
session_start();
require_once 'config.php';
requireLogin();

$db = getDB();
$userId = (int)$_SESSION['user_id'];

$search = $_GET['search'] ?? '';
$typeFilter = $_GET['type'] ?? '';

$query = "
    SELECT t.*, c.name AS client_name, cat.name AS category_name
    FROM transactions t
    LEFT JOIN clients c ON c.id = t.client_id
    LEFT JOIN categories cat ON cat.id = t.category_id
    WHERE t.user_id = $userId
";

if ($search !== '') {
    $searchEscaped = $db->real_escape_string($search);
    $query .= " AND t.description LIKE '%$searchEscaped%'";
}
if ($typeFilter === 'income' || $typeFilter === 'expense') {
    $query .= " AND t.type = '$typeFilter'";
}
$query .= " ORDER BY t.transaction_date DESC, t.id DESC";
$transactions = $db->query($query)->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php'; 
?>

<div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
    <form method="GET" action="transactions.php" style="display: flex; gap: 10px;">
        <input type="text" name="search" class="sb" placeholder="Search description..." value="<?= htmlspecialchars($search) ?>">
        <select name="type" class="sb" style="width: auto;">
            <option value="">All Types</option>
            <option value="income" <?= $typeFilter === 'income' ? 'selected' : '' ?>>Income</option>
            <option value="expense" <?= $typeFilter === 'expense' ? 'selected' : '' ?>>Expense</option>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
        <?php if($search || $typeFilter): ?>
            <a href="transactions.php" class="btn" style="background: var(--surface-2); color: var(--text-main); text-decoration: none;">Clear</a>
        <?php endif; ?>
    </form>
    
    <button class="btn btn-primary" onclick="openModal('transactionModal')">+ Add Transaction</button>
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
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($transactions)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 30px; color: var(--text-muted);">No transactions found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($transactions as $tx): ?>
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
                        <td style="display: flex; gap: 5px;">
                            <button class="btn" style="padding: 4px 8px; font-size: 11px;" 
                                onclick="editTransaction(this)"
                                data-id="<?= $tx['id'] ?>"
                                data-desc="<?= htmlspecialchars($tx['description']) ?>"
                                data-inv="<?= htmlspecialchars($tx['invoice_no'] ?? '') ?>"
                                data-amount="<?= $tx['amount'] ?>"
                                data-type="<?= $tx['type'] ?>"
                                data-cat="<?= $tx['category_id'] ?? '' ?>"
                                data-client="<?= $tx['client_id'] ?? '' ?>"
                                data-date="<?= $tx['transaction_date'] ?>"
                                data-status="<?= $tx['status'] ?>"
                                data-notes="<?= htmlspecialchars($tx['notes'] ?? '') ?>"
                            >Edit</button>
                            <button class="btn btn-danger" style="padding: 4px 8px; font-size: 11px;" 
                                onclick="confirmDelete('transaction', <?= $tx['id'] ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>