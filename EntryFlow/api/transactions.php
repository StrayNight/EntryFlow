<?php
require_once '../config.php';
requireLogin();

$db  = getDB();
$uid = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// ─── GET — list / search ─────────────────────────────────────
if ($method === 'GET') {
    $where = ["t.user_id = $uid"];
    $params = [];
    $types  = '';

    if (!empty($_GET['type'])) {
        $where[] = "t.type = ?"; $params[] = $_GET['type']; $types .= 's';
    }
    if (!empty($_GET['status'])) {
        $where[] = "t.status = ?"; $params[] = $_GET['status']; $types .= 's';
    }
    if (!empty($_GET['category_id'])) {
        $where[] = "t.category_id = ?"; $params[] = (int)$_GET['category_id']; $types .= 'i';
    }
    if (!empty($_GET['month'])) {     // format YYYY-MM
        $where[] = "DATE_FORMAT(t.transaction_date, '%Y-%m') = ?";
        $params[] = $_GET['month']; $types .= 's';
    }
    if (!empty($_GET['search'])) {
        $where[] = "t.description LIKE ?";
        $params[] = '%' . $_GET['search'] . '%'; $types .= 's';
    }

    $sql = "SELECT t.id, t.invoice_no, t.description, t.amount, t.type, t.status,
                   t.transaction_date, t.notes, t.created_at,
                   c.name AS client_name, cat.name AS category_name
            FROM transactions t
            LEFT JOIN clients c ON c.id = t.client_id
            LEFT JOIN categories cat ON cat.id = t.category_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY t.transaction_date DESC, t.id DESC
            LIMIT 200";

    $stmt = $db->prepare($sql);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    jsonOk($rows);
}

// ─── POST — create ───────────────────────────────────────────
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $desc   = trim($data['description'] ?? '');
    $amount = (float)($data['amount'] ?? 0);
    $type   = $data['type'] ?? '';
    $status = $data['status'] ?? 'pending';
    $date   = $data['transaction_date'] ?? date('Y-m-d');
    $invNo  = trim($data['invoice_no'] ?? '') ?: null;
    $catId  = !empty($data['category_id']) ? (int)$data['category_id'] : null;
    $cliId  = !empty($data['client_id'])   ? (int)$data['client_id']   : null;
    $notes  = trim($data['notes'] ?? '') ?: null;

    if (!$desc || $amount <= 0 || !in_array($type, ['income','expense']) || !$date) {
        jsonErr('Missing required fields');
    }

    $stmt = $db->prepare(
        "INSERT INTO transactions (user_id, client_id, category_id, invoice_no, description, amount, type, status, transaction_date, notes)
         VALUES (?,?,?,?,?,?,?,?,?,?)"
    );
    $stmt->bind_param('iiissdssss', $uid, $cliId, $catId, $invNo, $desc, $amount, $type, $status, $date, $notes);
    if ($stmt->execute()) {
        jsonOk(['id' => $db->insert_id, 'message' => 'Transaction added']);
    }
    jsonErr('Failed to save: ' . $db->error);
}

// ─── PUT — update ────────────────────────────────────────────
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id   = (int)($data['id'] ?? 0);
    if (!$id) jsonErr('Missing id');

    $desc   = trim($data['description'] ?? '');
    $amount = (float)($data['amount'] ?? 0);
    $type   = $data['type'] ?? '';
    $status = $data['status'] ?? 'pending';
    $date   = $data['transaction_date'] ?? date('Y-m-d');
    $invNo  = trim($data['invoice_no'] ?? '') ?: null;
    $catId  = !empty($data['category_id']) ? (int)$data['category_id'] : null;
    $cliId  = !empty($data['client_id'])   ? (int)$data['client_id']   : null;
    $notes  = trim($data['notes'] ?? '') ?: null;

    $stmt = $db->prepare(
        "UPDATE transactions SET client_id=?, category_id=?, invoice_no=?, description=?,
         amount=?, type=?, status=?, transaction_date=?, notes=?
         WHERE id=? AND user_id=?"
    );
    $stmt->bind_param('iissdssssii', $cliId, $catId, $invNo, $desc, $amount, $type, $status, $date, $notes, $id, $uid);
    $stmt->execute();
    jsonOk(['message' => 'Updated']);
}

// ─── DELETE ──────────────────────────────────────────────────
if ($method === 'DELETE') {
    // Read the ID straight from the URL
    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Missing ID in request.']);
        exit;
    }

    // Direct database query bypassing any prepared statement quirks
    $sql = "DELETE FROM transactions WHERE id = $id AND user_id = $uid";
    
    if ($db->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Deleted successfully']);
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'Database Error: ' . $db->error]);
        exit;
    }
}