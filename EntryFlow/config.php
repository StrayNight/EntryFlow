<?php
// Secure Session Cookie Settings
ini_set('session.cookie_httponly', 1); // Prevents Javascript from reading the session
ini_set('session.use_only_cookies', 1);
// ─── Database Configuration ───────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // Change if you set a MySQL root password
define('DB_NAME', 'Entryflow_DB');
define('DB_PORT', 3306);

// ─── Singleton DB connection ──────────────────────────────────
function getDB(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        if ($conn->connect_error) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'DB error: ' . $conn->connect_error]);
            exit;
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

// ─── JSON response helpers ────────────────────────────────────
function jsonOk(array $data = []): void {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

function jsonErr(string $msg, int $code = 400): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

// ─── Auth guard ───────────────────────────────────────────────
function requireLogin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_id'])) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || !empty($_GET['ajax'])) {
            jsonErr('Not authenticated', 401);
        }
        header('Location: login.php');
        exit;
    }
}

// ─── Currency format helper ───────────────────────────────────
function peso(float $n): string {
    return '₱' . number_format($n, 2);
}
