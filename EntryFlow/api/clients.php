<?php
require_once '../config.php';
requireLogin();

$db  = getDB();
$uid = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $search = !empty($_GET['search']) ? '%' . $_GET['search'] . '%' : null;
    if ($search) {
        $stmt = $db->prepare("SELECT * FROM clients WHERE user_id=? AND name LIKE ? ORDER BY name");
        $stmt->bind_param('is', $uid, $search);
    } else {
        $stmt = $db->prepare("SELECT c.*, COUNT(t.id) AS tx_count,
            SUM(CASE WHEN t.type='income' THEN t.amount ELSE 0 END) AS total_revenue
            FROM clients c
            LEFT JOIN transactions t ON t.client_id = c.id AND t.user_id = ?
            WHERE c.user_id=? GROUP BY c.id ORDER BY c.name");
        $stmt->bind_param('ii', $uid, $uid);
    }
    $stmt->execute();
    jsonOk($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

if ($method === 'POST') {
    $data  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $name  = trim($data['name']  ?? '');
    $email = trim($data['email'] ?? '') ?: null;
    $phone = trim($data['phone'] ?? '') ?: null;
    $addr  = trim($data['address'] ?? '') ?: null;
    $notes = trim($data['notes'] ?? '') ?: null;
    if (!$name) jsonErr('Name required');
    $stmt = $db->prepare("INSERT INTO clients (user_id, name, email, phone, address, notes) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param('isssss', $uid, $name, $email, $phone, $addr, $notes);
    $stmt->execute();
    jsonOk(['id' => $db->insert_id]);
}

if ($method === 'PUT') {
    $data  = json_decode(file_get_contents('php://input'), true);
    $id    = (int)($data['id'] ?? 0);
    $name  = trim($data['name']  ?? '');
    $email = trim($data['email'] ?? '') ?: null;
    $phone = trim($data['phone'] ?? '') ?: null;
    $addr  = trim($data['address'] ?? '') ?: null;
    $notes = trim($data['notes'] ?? '') ?: null;
    if (!$id || !$name) jsonErr('ID and name required');
    $stmt = $db->prepare("UPDATE clients SET name=?,email=?,phone=?,address=?,notes=? WHERE id=? AND user_id=?");
    $stmt->bind_param('sssssii', $name, $email, $phone, $addr, $notes, $id, $uid);
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

    $sql = "DELETE FROM clients WHERE id = $id AND user_id = $uid";
    
    if ($db->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Deleted successfully']);
        exit;
    } else {
        // Protection: Warn the user if they try to delete a client that still has transactions
        if (strpos($db->error, 'foreign key constraint') !== false) {
            echo json_encode(['success' => false, 'error' => 'Cannot delete this client because they still have attached transactions.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database Error: ' . $db->error]);
        }
        exit;
    }
}