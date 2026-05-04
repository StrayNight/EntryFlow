<?php
require_once '../config.php';
requireLogin();

$db  = getDB();
$uid = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'settings';

// ─── GET settings + profile ──────────────────────────────────
if ($method === 'GET') {
    $user = $db->query("SELECT id, name, email, business_name, business_type, phone, address, currency
                         FROM admins WHERE id=$uid")->fetch_assoc();
    jsonOk(['user' => $user]);
}

// ─── POST settings (Business Info) ───────────────────────────
if ($method === 'POST' && $action === 'settings') {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $bizName  = trim($data['business_name'] ?? '');
    $bizType  = trim($data['business_type'] ?? '');
    $currency = trim($data['currency'] ?? 'PHP');

    // Update the admins table (Business Core Info)
    $stmt = $db->prepare("UPDATE admins SET business_name=?, business_type=?, currency=? WHERE id=?");
    $stmt->bind_param('sssi', $bizName, $bizType, $currency, $uid);
    $stmt->execute();

    // Update the session variable for business name so the sidebar updates instantly
    $_SESSION['business'] = $bizName;

    jsonOk(['message' => 'Settings updated']);
}

// ─── POST profile (User Info & Password) ──────────────────────
if ($method === 'POST' && $action === 'profile') {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $name    = trim($data['name']    ?? '');
    $email   = trim($data['email']   ?? '');
    $phone   = trim($data['phone']   ?? '') ?: null;
    $address = trim($data['address'] ?? '') ?: null;
    $newPass = trim($data['new_password'] ?? '');
    $curPass = trim($data['current_password'] ?? '');

    if (!$name || !$email) jsonErr('Name and email required');

    // Check email uniqueness
    $check = $db->prepare("SELECT id FROM admins WHERE email=? AND id!=?");
    $check->bind_param('si', $email, $uid);
    $check->execute();
    if ($check->get_result()->num_rows > 0) jsonErr('Email already in use');

    if ($newPass) {
        // Verify current password first before allowing a change
        $cur = $db->query("SELECT password FROM admins WHERE id=$uid")->fetch_assoc();
        if (!password_verify($curPass, $cur['password'])) jsonErr('Current password is incorrect');
        if (strlen($newPass) < 6) jsonErr('New password must be at least 6 characters');
        
        $hash = password_hash($newPass, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE admins SET name=?, email=?, phone=?, address=?, password=? WHERE id=?");
        $stmt->bind_param('sssssi', $name, $email, $phone, $address, $hash, $uid);
    } else {
        // Update profile without changing password
        $stmt = $db->prepare("UPDATE admins SET name=?, email=?, phone=?, address=? WHERE id=?");
        $stmt->bind_param('ssssi', $name, $email, $phone, $address, $uid);
    }
    
    $stmt->execute();
    
    // Update the session name
    $_SESSION['user_name'] = $name;

    jsonOk(['message' => 'Profile updated']);
}