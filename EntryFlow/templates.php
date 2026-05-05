<?php
session_start();
require_once 'config.php';
requireLogin();

$db = getDB();
$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $icon = trim($_POST['icon'] ?? '🗂️');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $sortOrder = max(0, (int)($_POST['sort_order'] ?? 0));

    if ($name !== '') {
        $stmt = $db->prepare("INSERT INTO templates (name, description, icon, is_featured, sort_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('sssii', $name, $description, $icon, $isFeatured, $sortOrder);
        $stmt->execute();
        $stmt->close();
        header('Location: templates.php');
        exit;
    }
}

$templates = $db->query("SELECT * FROM templates ORDER BY is_featured DESC, sort_order ASC, name ASC")->fetch_all(MYSQLI_ASSOC);
include 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: flex-end; gap: 22px; margin-bottom: 24px;">
    <div>
        <span class="label-soft">Template manager</span>
        <h2>Business Templates</h2>
        <p class="page-copy">Browse, create, and manage reusable business templates for orders, records, and tracking workflows.</p>
    </div>
    <a href="business.php" class="btn btn-primary">Open Business View</a>
</div>

<div class="templates-grid">
    <?php foreach ($templates as $template): ?>
        <article class="template-card">
            <div>
                <div class="template-meta">
                    <div class="template-icon"><?= htmlspecialchars($template['icon'] ?: '🗂️') ?></div>
                    <div>
                        <div class="template-name"><?= htmlspecialchars($template['name']) ?></div>
                        <div class="template-copy"><?= htmlspecialchars($template['description']) ?></div>
                    </div>
                </div>
            </div>
            <div class="template-actions">
                <a href="business.php?template_id=<?= (int)$template['id'] ?>" class="btn button-muted">View in Business</a>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<div class="template-panel create-template-panel">
    <div>
        <h3>Create New Template</h3>
        <p class="panel-copy">Add a reusable business template that can later be reviewed in the Business page.</p>
    </div>
    <form method="POST" style="display: grid; gap: 16px;">
        <div>
            <label>Name</label>
            <input type="text" name="name" class="sb" required placeholder="Sales Order Record">
        </div>
        <div>
            <label>Description</label>
            <textarea name="description" class="sb" placeholder="e.g. Track sales orders, customer info, and delivery dates."></textarea>
        </div>
        <div style="display:flex; gap: 16px; flex-wrap: wrap; align-items: center;">
            <div style="flex:1; min-width:150px;">
                <label>Icon</label>
                <input type="text" name="icon" class="sb" placeholder="🧾">
            </div>
            <div style="flex:1; min-width:150px;">
                <label>Sort order</label>
                <input type="number" name="sort_order" class="sb" value="0" min="0">
            </div>
            <div style="flex:1; min-width:150px; display:flex; flex-direction:column; gap: 6px;">
                <label style="opacity: 0;">Featured</label>
                <label style="display:inline-flex; align-items:center; gap:10px; font-size:13px; color:var(--text-muted);">
                    <input type="checkbox" name="is_featured" value="1"> Mark featured
                </label>
            </div>
        </div>
        <div>
            <button type="submit" class="btn btn-primary">Save Template</button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>