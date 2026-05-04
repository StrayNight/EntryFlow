<?php
require_once '../config.php';
requireLogin();

$db  = getDB();
$uid = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonErr('POST only');
if (empty($_FILES['csv'])) jsonErr('No file uploaded');

$file = $_FILES['csv'];
if ($file['error'] !== UPLOAD_ERR_OK) jsonErr('Upload error: ' . $file['error']);
if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'csv') jsonErr('Must be a .csv file');

$handle = fopen($file['tmp_name'], 'r');
if (!$handle) jsonErr('Cannot read file');

// Expected columns (case-insensitive): date, description, amount, type, status, category, invoice_no, notes
$headers = array_map(fn($h) => strtolower(trim($h)), fgetcsv($handle));

$required = ['date','description','amount','type'];
foreach ($required as $r) {
    if (!in_array($r, $headers)) jsonErr("Missing column: $r (required: date, description, amount, type)");
}

$col = array_flip($headers);
$total = $imported = $failed = 0;
$errors = [];

$stmt = $db->prepare(
    "INSERT INTO transactions (user_id, invoice_no, description, amount, type, status, transaction_date, notes)
     VALUES (?,?,?,?,?,?,?,?)"
);

while (($row = fgetcsv($handle)) !== false) {
    $total++;
    if (count($row) < count($required)) { $errors[] = "Row $total: too few columns"; $failed++; continue; }

    $date   = trim($row[$col['date']]        ?? '');
    $desc   = trim($row[$col['description']] ?? '');
    $amount = (float)trim($row[$col['amount']] ?? 0);
    $type   = strtolower(trim($row[$col['type']] ?? ''));
    $status = strtolower(trim($row[$col['status'] ?? -1] ?? 'paid'));
    $invNo  = trim($row[$col['invoice_no'] ?? -1] ?? '') ?: null;
    $notes  = trim($row[$col['notes'] ?? -1] ?? '') ?: null;

    // Validate
    if (!$desc || $amount <= 0 || !in_array($type, ['income','expense'])) {
        $errors[] = "Row $total: invalid data"; $failed++; continue;
    }
    if (!in_array($status, ['paid','pending','overdue'])) $status = 'paid';
    $parsedDate = date('Y-m-d', strtotime($date));
    if ($parsedDate === '1970-01-01') { $errors[] = "Row $total: bad date '$date'"; $failed++; continue; }

    $stmt->bind_param('issdssss', $uid, $invNo, $desc, $amount, $type, $status, $parsedDate, $notes);
    if ($stmt->execute()) { $imported++; } else { $errors[] = "Row $total: DB error"; $failed++; }
}
fclose($handle);

// Log
$filename = $file['name'];
$logStatus = $failed === 0 ? 'success' : ($imported > 0 ? 'partial' : 'failed');
$errLog = implode("\n", $errors) ?: null;
$logStmt = $db->prepare("INSERT INTO import_logs (user_id, filename, total_rows, imported_rows, failed_rows, status, error_log) VALUES (?,?,?,?,?,?,?)");
$logStmt->bind_param('isiiiss', $uid, $filename, $total, $imported, $failed, $logStatus, $errLog);
$logStmt->execute();

jsonOk([
    'total'    => $total,
    'imported' => $imported,
    'failed'   => $failed,
    'errors'   => $errors,
    'status'   => $logStatus,
]);
