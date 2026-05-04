<?php
session_start();
require_once 'config.php';
requireLogin();

$db = getDB();
$userId = (int)$_SESSION['user_id'];

// Search logic
$search = $_GET['search'] ?? '';
$searchQuery = "";
if ($search !== '') {
    $searchEscaped = $db->real_escape_string($search);
    $searchQuery = "AND c.name LIKE '%$searchEscaped%'";
}

// Fetch clients
$query = "
    SELECT c.*, COUNT(t.id) AS tx_count, 
           SUM(CASE WHEN t.type='income' THEN t.amount ELSE 0 END) AS total_revenue
    FROM clients c
    LEFT JOIN transactions t ON t.client_id = c.id AND t.user_id = $userId
    WHERE c.user_id = $userId $searchQuery
    GROUP BY c.id 
    ORDER BY c.name ASC
";
$clients = $db->query($query)->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php'; 
?>

<div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
    <form method="GET" action="clients.php" style="display: flex; gap: 10px;">
        <input type="text" name="search" class="sb" placeholder="Search clients..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if($search): ?>
            <a href="clients.php" class="btn" style="background: var(--surface-2); color: var(--text-main); text-decoration: none;">Clear</a>
        <?php endif; ?>
    </form>
    
    <button class="btn btn-primary" onclick="openModal('clientModal')">+ Add Client</button>
</div>

<div class="tc">
    <table>
        <thead>
            <tr>
                <th>Client Name</th>
                <th>Contact Info</th>
                <th>Address</th>
                <th>Transactions</th>
                <th>Total Revenue</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($clients)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">No clients found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($clients as $client): ?>
                    <tr>
                        <td class="client-name-wrap">
                            <?php 
                                $initial = strtoupper(substr($client['name'], 0, 1)); 
                                $colorClass = 'av-' . ((ord($initial) % 5) + 1); 
                            ?>
                            <div class="avatar <?= $colorClass ?>"><?= $initial ?></div>
                            <span style="font-weight: 500; color: var(--primary-light);"><?= htmlspecialchars($client['name']) ?></span>
                        </td>
                        <td class="tdn">
                            <div><?= htmlspecialchars($client['email'] ?? 'No email') ?></div>
                            <div class="tds"><?= htmlspecialchars($client['phone'] ?? 'No phone') ?></div>
                        </td>
                        <td><?= htmlspecialchars($client['address'] ?? '—') ?></td>
                        <td><?= (int)$client['tx_count'] ?></td>
                        <td class="ap">₱<?= number_format($client['total_revenue'] ?? 0, 2) ?></td>
                        <td style="display: flex; gap: 5px;">
                            <button class="btn" style="padding: 4px 8px; font-size: 11px;" 
                                onclick="editClient(this)"
                                data-id="<?= $client['id'] ?>"
                                data-name="<?= htmlspecialchars($client['name']) ?>"
                                data-email="<?= htmlspecialchars($client['email'] ?? '') ?>"
                                data-phone="<?= htmlspecialchars($client['phone'] ?? '') ?>"
                                data-address="<?= htmlspecialchars($client['address'] ?? '') ?>"
                                data-notes="<?= htmlspecialchars($client['notes'] ?? '') ?>"
                            >Edit</button>
                            <button class="btn btn-danger" style="padding: 4px 8px; font-size: 11px;" 
                                onclick="confirmDelete('client', <?= $client['id'] ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>