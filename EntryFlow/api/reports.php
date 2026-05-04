<?php
require_once '../config.php';
requireLogin();

$db  = getDB();
$uid = (int)$_SESSION['user_id'];

$month = $_GET['month'] ?? date('Y-m');  // YYYY-MM
$year  = $_GET['year']  ?? date('Y');

// ─── CSV export ──────────────────────────────────────────────
if (!empty($_GET['export']) && $_GET['export'] === 'csv') {
    $period = !empty($_GET['month']) ? "DATE_FORMAT(t.transaction_date,'%Y-%m') = '$month'" : "YEAR(t.transaction_date) = '$year'";
    $rows = $db->query("SELECT t.transaction_date, t.invoice_no, t.description, t.amount, t.type, t.status,
                                c.name AS client, cat.name AS category
                         FROM transactions t
                         LEFT JOIN clients c ON c.id = t.client_id
                         LEFT JOIN categories cat ON cat.id = t.category_id
                         WHERE t.user_id = $uid AND $period
                         ORDER BY t.transaction_date DESC")->fetch_all(MYSQLI_ASSOC);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="entryflow-report-' . $month . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date','Invoice No','Description','Amount','Type','Status','Client','Category']);
    foreach ($rows as $r) fputcsv($out, $r);
    fclose($out);
    exit;
}

// ─── P&L summary ─────────────────────────────────────────────
$period = "DATE_FORMAT(t.transaction_date, '%Y-%m') = ?";

// Income / Expense totals
$stmt = $db->prepare("SELECT type, SUM(amount) AS total FROM transactions t WHERE user_id=? AND $period GROUP BY type");
$stmt->bind_param('is', $uid, $month);
$stmt->execute();
$totals = ['income' => 0, 'expense' => 0];
foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) $totals[$r['type']] = (float)$r['total'];

// Expenses by category
$stmt = $db->prepare("SELECT COALESCE(cat.name, 'Uncategorized') AS cat, SUM(t.amount) AS total
    FROM transactions t LEFT JOIN categories cat ON cat.id = t.category_id
    WHERE t.user_id=? AND t.type='expense' AND $period GROUP BY cat.name ORDER BY total DESC");
$stmt->bind_param('is', $uid, $month);
$stmt->execute();
$expByCat = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Income by category
$stmt = $db->prepare("SELECT COALESCE(cat.name, 'Uncategorized') AS cat, SUM(t.amount) AS total
    FROM transactions t LEFT JOIN categories cat ON cat.id = t.category_id
    WHERE t.user_id=? AND t.type='income' AND $period GROUP BY cat.name ORDER BY total DESC");
$stmt->bind_param('is', $uid, $month);
$stmt->execute();
$incByCat = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Monthly chart data — last 8 months
$chartData = [];
for ($i = 7; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $label = date('M', strtotime("-$i months"));
    $res = $db->query("SELECT type, SUM(amount) AS t FROM transactions
        WHERE user_id=$uid AND DATE_FORMAT(transaction_date,'%Y-%m')='$m' GROUP BY type");
    $row = ['month' => $label, 'income' => 0, 'expense' => 0];
    foreach ($res->fetch_all(MYSQLI_ASSOC) as $r) $row[$r['type']] = (float)$r['t'];
    $chartData[] = $row;
}

// Pending / overdue counts
$stmt = $db->prepare("SELECT status, COUNT(*) AS n FROM transactions WHERE user_id=? AND status!='paid' GROUP BY status");
$stmt->bind_param('i', $uid);
$stmt->execute();
$statusCounts = [];
foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) $statusCounts[$r['status']] = $r['n'];

$gross = $totals['income'] - $totals['expense'];
$settings = $db->query("SELECT tax_rate FROM settings WHERE user_id=$uid")->fetch_assoc();
$taxRate = (float)($settings['tax_rate'] ?? 12);
$taxAmt = $gross > 0 ? $gross * ($taxRate / 100) : 0;
$net = $gross - $taxAmt;

jsonOk([
    'month'        => $month,
    'income'       => $totals['income'],
    'expense'      => $totals['expense'],
    'gross_profit' => $gross,
    'tax_rate'     => $taxRate,
    'tax_amount'   => $taxAmt,
    'net_profit'   => $net,
    'income_by_cat'  => $incByCat,
    'expense_by_cat' => $expByCat,
    'chart_data'   => $chartData,
    'status_counts'=> $statusCounts,
]);
