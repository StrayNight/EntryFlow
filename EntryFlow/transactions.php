<?php
session_start();
require_once 'config.php';
requireLogin();

$db = getDB();
$userId = (int)$_SESSION['user_id'];

$search = $_GET['search'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$query = "
    SELECT t.*, c.name AS client_name, cat.name AS category_name
    FROM transactions t
    LEFT JOIN clients c ON c.id = t.client_id
    LEFT JOIN categories cat ON cat.id = t.category_id
    WHERE t.user_id = $userId
";

// Append filters to SQL Query dynamically
if ($search !== '') {
    $searchEscaped = $db->real_escape_string($search);
    $query .= " AND t.description LIKE '%$searchEscaped%'";
}
if ($typeFilter === 'income' || $typeFilter === 'expense') {
    $query .= " AND t.type = '$typeFilter'";
}
if ($statusFilter === 'pending' || $statusFilter === 'overdue' || $statusFilter === 'paid') {
    $query .= " AND t.status = '$statusFilter'";
}

$query .= " ORDER BY t.transaction_date DESC, t.id DESC";
$transactions = $db->query($query)->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php'; 
?>

<div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 15px; margin-bottom: 20px;">
    
    <form method="GET" action="transactions.php" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <input type="text" name="search" class="sb" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
        <select name="type" class="sb">
            <option value="">All Types</option>
            <option value="income" <?= $typeFilter === 'income' ? 'selected' : '' ?>>Income</option>
            <option value="expense" <?= $typeFilter === 'expense' ? 'selected' : '' ?>>Expense</option>
        </select>
        
        <?php if ($statusFilter): ?>
            <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
        <?php endif; ?>
        
        <button type="submit" class="btn btn-primary">Search</button>
        
        <div style="display: flex; gap: 8px; margin-left: 10px; border-left: 1px solid var(--border-color); padding-left: 15px;">
            <a href="transactions.php?status=pending<?= $search ? '&search='.urlencode($search) : '' ?><?= $typeFilter ? '&type='.urlencode($typeFilter) : '' ?>" 
               class="badge <?= $statusFilter === 'pending' ? 'bpd' : '' ?>" 
               style="text-decoration:none; padding: 8px 12px; font-size: 13px; display:flex; align-items:center; <?= $statusFilter !== 'pending' ? 'background: var(--surface-2); color: var(--text-muted);' : '' ?>">
               ⏳ Pending
            </a>
            
            <a href="transactions.php?status=overdue<?= $search ? '&search='.urlencode($search) : '' ?><?= $typeFilter ? '&type='.urlencode($typeFilter) : '' ?>" 
               class="badge <?= $statusFilter === 'overdue' ? 'bod' : '' ?>" 
               style="text-decoration:none; padding: 8px 12px; font-size: 13px; display:flex; align-items:center; <?= $statusFilter !== 'overdue' ? 'background: var(--surface-2); color: var(--text-muted);' : '' ?>">
               ⚠️ Overdue
            </a>
            
            <?php if ($statusFilter || $search || $typeFilter): ?>
                <a href="transactions.php" style="color: var(--text-muted); font-size: 13px; text-decoration: none; margin-left: 5px; display: flex; align-items: center;">Clear All</a>
            <?php endif; ?>
        </div>
    </form>
    
    <div style="display: flex; gap: 10px;">
        <button class="btn" onclick="openDynamicModal('income')" style="background: var(--primary) !important; color: #ffffff !important; border: none;">📈 Log Sale</button>
        <button class="btn" onclick="openDynamicModal('expense')" style="background: var(--danger) !important; color: #ffffff !important; border: none;">📉 Log Expense</button>
    </div>
</div>

<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Category</th>
                <th>Client / Payee</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($transactions)): ?>
                <tr><td colspan="7" style="text-align: center; padding: 20px; color: var(--text-muted);">No transactions found.</td></tr>
            <?php else: ?>
                <?php foreach ($transactions as $tx): ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($tx['transaction_date'])) ?></td>
                        <td class="tdn">
                            <?= htmlspecialchars($tx['description']) ?>
                            <?php if (!empty($tx['invoice_no'])): ?>
                                <div class="tds">Inv: <?= htmlspecialchars($tx['invoice_no']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($tx['category_name'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($tx['client_name'] ?? '—') ?></td>
                        <td class="<?= $tx['type'] === 'income' ? 'ap' : 'an' ?>">
                            <?= $tx['type'] === 'income' ? '+' : '-' ?><?= $sym ?><?= number_format($tx['amount'], 2) ?>
                        </td>
                        <td>
                            <?php 
                                $statusClass = '';
                                if ($tx['status'] === 'paid') $statusClass = 'bp2';
                                elseif ($tx['status'] === 'pending') $statusClass = 'bpd';
                                else $statusClass = 'bod';
                            ?>
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