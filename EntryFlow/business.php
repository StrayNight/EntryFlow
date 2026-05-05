<?php
session_start();
require_once 'config.php';
requireLogin();

$db = getDB();
$userId = (int)$_SESSION['user_id'];

$templates = $db->query("SELECT * FROM templates ORDER BY is_featured DESC, sort_order ASC, name ASC")->fetch_all(MYSQLI_ASSOC);
$selectedId = isset($_GET['template_id']) ? (int)$_GET['template_id'] : 0;
$selectedTemplate = null;
foreach ($templates as $template) {
    if ($selectedId && $template['id'] === $selectedId) {
        $selectedTemplate = $template;
        break;
    }
}
if (!$selectedTemplate && !empty($templates)) {
    $selectedTemplate = $templates[0];
}

function getTemplateSections(string $name): array {
    $lower = strtolower($name);
    if (str_contains($lower, 'sales')) {
        return [
            'Order Details' => ['Order number', 'Customer name', 'Order date', 'Delivery date', 'Status'],
            'Line Items' => ['Item description', 'Quantity', 'Unit price', 'Amount'],
            'Summary' => ['Subtotal', 'Tax', 'Total amount', 'Payment terms']
        ];
    }
    if (str_contains($lower, 'payable')) {
        return [
            'Vendor Info' => ['Vendor name', 'Contact person', 'Due date', 'Invoice reference'],
            'Payable Items' => ['Purchase description', 'Amount due', 'Payment status'],
            'Approval' => ['Reviewed by', 'Approval date']
        ];
    }
    if (str_contains($lower, 'receivable')) {
        return [
            'Client Info' => ['Client name', 'Contact details', 'Invoice number', 'Due date'],
            'Receivable Summary' => ['Amount billed', 'Amount paid', 'Balance due'],
            'Collections' => ['Last reminder', 'Collection stage']
        ];
    }
    if (str_contains($lower, 'inventory')) {
        return [
            'Stock Item' => ['Item name', 'SKU', 'Current quantity', 'Reorder point'],
            'Movement' => ['Received', 'Used', 'Remaining'],
            'Cost' => ['Unit cost', 'Total value']
        ];
    }
    return [
        'Template Sections' => ['Title', 'Description', 'Owner', 'Date created', 'Status'],
        'Usage Notes' => ['How to apply this template', 'Recommended workflows', 'Notes or instructions']
    ];
}

include 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: flex-end; gap: 22px; margin-bottom: 24px;">
    <div>
        <span class="label-soft">Business workspace</span>
        <h2>Template Contents</h2>
        <p class="page-copy">View the selected template and explore your business record structure in one place.</p>
    </div>
    <a href="templates.php" class="btn btn-primary">Manage Templates</a>
</div>

<?php if (empty($templates)): ?>
    <div class="template-panel">
        <h3>No templates available yet</h3>
        <p class="panel-copy">Create your first business template on the Templates page and then return here to view its contents.</p>
        <a href="templates.php" class="btn btn-primary" style="margin-top:16px;">Add Template</a>
    </div>
<?php else: ?>
    <div class="business-page">
        <aside class="template-list">
            <?php foreach ($templates as $template): ?>
                <a class="template-item <?= $selectedTemplate && $selectedTemplate['id'] === $template['id'] ? 'active' : '' ?>" href="business.php?template_id=<?= (int)$template['id'] ?>">
                    <h4><?= htmlspecialchars($template['name']) ?></h4>
                    <p><?= htmlspecialchars($template['description']) ?></p>
                </a>
            <?php endforeach; ?>
        </aside>

        <section class="template-detail">
            <div class="template-badge">
                <span><?= htmlspecialchars($selectedTemplate['icon'] ?: '📄') ?></span>
                <div>
                    <div style="font-weight:700;"><?= htmlspecialchars($selectedTemplate['name']) ?></div>
                    <div style="font-size:13px; color: var(--text-muted);">Usage count: <?= (int)$selectedTemplate['usage_count'] ?> | <?= $selectedTemplate['is_featured'] ? 'Featured template' : 'Standard template' ?></div>
                </div>
            </div>
            <p class="panel-copy"><?= htmlspecialchars($selectedTemplate['description']) ?></p>

            <div class="template-actions-row">
                <button class="btn btn-primary">Use this template</button>
                <button class="btn button-muted" disabled>Share (coming soon)</button>
            </div>

            <div class="content-panel">
                <?php foreach (getTemplateSections($selectedTemplate['name']) as $section => $fields): ?>
                    <div class="content-box">
                        <h4><?= htmlspecialchars($section) ?></h4>
                        <ul>
                            <?php foreach ($fields as $field): ?>
                                <li><?= htmlspecialchars($field) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>