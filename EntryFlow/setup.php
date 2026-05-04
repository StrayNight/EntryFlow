<?php
/**
 * EntryFlow — First-Time Setup Script
 * ────────────────────────────────────
 * Run this ONCE at http://localhost/entryflow/setup.php
 * Then DELETE this file from your server for security.
 */

$host = 'localhost';
$user = 'root';
$pass = '';   // ← change if your MySQL has a password
$port = 3306;

$conn = new mysqli($host, $user, $pass, '', $port);
if ($conn->connect_error) {
    die('<b>Connection failed:</b> ' . $conn->connect_error);
}

$log = [];

function run(mysqli $db, string $sql, string $label): void {
    global $log;
    if ($db->multi_query($sql)) {
        do { if ($r = $db->store_result()) $r->free(); } while ($db->next_result());
        $log[] = "✅ $label";
    } else {
        $log[] = "❌ $label — " . $db->error;
    }
}

// ── Create + use DB ──────────────────────────────────────────
$conn->query("CREATE DATABASE IF NOT EXISTS Entryflow_DB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db('Entryflow_DB');
$log[] = '✅ Database Entryflow_DB ready';

// ── Load and run schema SQL ──────────────────────────────────
$schema = file_get_contents(__DIR__ . '/entryflow_db.sql');
// Strip "USE Entryflow_DB;" lines already handled above
$schema = preg_replace('/^USE\s+Entryflow_DB\s*;/mi', '', $schema);
// Split and run each statement
$stmts = array_filter(array_map('trim', explode(';', $schema)));
foreach ($stmts as $s) {
    if ($s === '') continue;
    if (!$conn->query($s)) {
        $log[] = "⚠️ Schema stmt skipped: " . $conn->error . "<br><small>" . htmlspecialchars(substr($s, 0, 80)) . "…</small>";
    }
}
$log[] = '✅ Schema tables created';

// ── Demo user ────────────────────────────────────────────────
$demoPass = password_hash('password123', PASSWORD_BCRYPT);
$stmt = $conn->prepare("INSERT IGNORE INTO users (name, email, password, role, business_name, business_type, phone, currency)
    VALUES (?, ?, ?, 'owner', ?, 'Retail & Food', '+63-912-345-6789', 'PHP')");
$name = "Zette Reyes";
$email = "zette@entryflow.ph";
$biz   = "Zette's Kitchen Supplies";
$stmt->bind_param('ssss', $name, $email, $demoPass, $biz);
$stmt->execute();
$userId = $conn->insert_id ?: 1;
$log[] = "✅ Demo user created (email: zette@entryflow.ph / password: password123)";

// ── Demo settings ────────────────────────────────────────────
$conn->query("INSERT IGNORE INTO settings (user_id, business_name, business_type, currency, tax_rate)
    VALUES ($userId, 'Zette\\'s Kitchen Supplies', 'Retail & Food', 'PHP', 12.00)");
$log[] = '✅ Settings created';

// ── Demo clients ─────────────────────────────────────────────
$clients = [
    ["Dela Cruz Store",  "delacruz@gmail.com",       "09171234567", "Brgy. Poblacion, Malaybalay City"],
    ["Santos Market",    "santos.market@yahoo.com",   "09281234567", "Brgy. Casisang, Malaybalay City"],
    ["Reyes Grocery",   "reyesgrocery@gmail.com",    "09351234567", "Brgy. Aglayan, Malaybalay City"],
    ["Flores Bakery",   "floresbakery@gmail.com",    "09461234567", "Brgy. Sumpong, Malaybalay City"],
];
$stmt = $conn->prepare("INSERT IGNORE INTO clients (user_id, name, email, phone, address) VALUES (?,?,?,?,?)");
foreach ($clients as $c) {
    $stmt->bind_param('issss', $userId, $c[0], $c[1], $c[2], $c[3]);
    $stmt->execute();
}
$log[] = '✅ Demo clients created';

// ── Demo transactions ─────────────────────────────────────────
$txRows = [
    [1, 1, 'INV-1042', 'Product Sales — Batch 12',      12500, 'income',  'paid',    '2025-04-22'],
    [1, null, null,    'Freelance Developer',            20000, 'expense', 'paid',    '2025-04-20'],
    [1, null, null,    'Inventory Restock - AgroMart',   8300, 'expense', 'pending', '2025-04-19'],
    [1, 2,    'INV-1041', 'Client: Santos Market Order', 5750, 'income',  'overdue', '2025-04-18'],
    [1, null, null,    'Cloud Hosting — AWS',             3000, 'expense', 'paid',    '2025-04-15'],
    [1, null, null,    'SMS Notification Service',        1000, 'expense', 'paid',    '2025-04-10'],
    [1, 3,    'INV-1040', 'Reyes Grocery - Service Fee', 8000, 'income',  'paid',    '2025-04-08'],
    [1, null, null,    'Facebook Ads Marketing',          5000, 'expense', 'paid',    '2025-04-05'],
    [1, 4,    'INV-1039', 'Flores Bakery Order',         15000, 'income',  'paid',    '2025-04-03'],
    [1, null, null,    'Staff Salary - April',           15000, 'expense', 'paid',    '2025-04-01'],
    [1, 1,    'INV-1038', 'Product Sales — Batch 11',    9800, 'income',  'paid',    '2025-03-25'],
    [1, null, null,    'Cloud Hosting — AWS',             3000, 'expense', 'paid',    '2025-03-15'],
    [1, 2,    'INV-1037', 'Santos Market March Order',   6500, 'income',  'paid',    '2025-03-10'],
    [1, null, null,    'Staff Salary - March',           15000, 'expense', 'paid',    '2025-03-01'],
];

// Get actual client IDs from DB
$cRes = $conn->query("SELECT id FROM clients WHERE user_id=$userId ORDER BY id LIMIT 4");
$cIds = [];
while ($r = $cRes->fetch_row()) $cIds[] = $r[0];

$stmt = $conn->prepare("INSERT INTO transactions (user_id, client_id, invoice_no, description, amount, type, status, transaction_date) VALUES (?,?,?,?,?,?,?,?)");
foreach ($txRows as $tx) {
    $cId = $tx[1] ? ($cIds[$tx[1]-1] ?? null) : null;
    $stmt->bind_param('iissdss s', $userId, $cId, $tx[2], $tx[3], $tx[4], $tx[5], $tx[6], $tx[7]);
    $stmt->execute();
}
$log[] = '✅ Demo transactions created';

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>EntryFlow Setup</title>
<style>
  body { font-family: monospace; background: #0F0D0D; color: #F2EDE9; padding: 40px; }
  h1   { color: #C53030; }
  li   { margin: 6px 0; font-size: 14px; }
  .done { margin-top: 24px; background: #1A1717; border: 1px solid #2E2626; padding: 20px; border-radius: 8px; }
  a    { color: #E07030; }
  .warn { color: #E07030; font-size: 13px; margin-top: 16px; }
</style>
</head>
<body>
<h1>⚙ EntryFlow Setup</h1>
<ul>
<?php foreach ($log as $l) echo "<li>$l</li>"; ?>
</ul>
<div class="done">
  <strong>Setup complete!</strong><br><br>
  Demo login: <b>zette@entryflow.ph</b> / <b>password123</b><br><br>
  <a href="login.php">→ Go to Login</a>
</div>
<p class="warn">⚠️ Delete or rename setup.php after setup for security.</p>
</body>
</html>
