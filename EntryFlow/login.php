<?php
require_once 'config.php'; 
session_start();           
if (!empty($_SESSION['user_id'])) { header('Location: index.php'); exit; }

$error = '';
$mode  = $_POST['mode'] ?? 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    
    // --- NEW: reCAPTCHA VERIFICATION ---
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    $secretKey = "6Ld2F9osAAAAAOVrbpi_BwEhzYftXi4MNolnOoND";

    // Verify the response with Google's servers
    $verifyUrl = "https://www.google.com/recaptcha/api/siteverify?secret={$secretKey}&response={$recaptchaResponse}";
    $verifyResponse = file_get_contents($verifyUrl);
    $responseData = json_decode($verifyResponse);

    if (!$responseData->success) {
        $error = 'Please complete the reCAPTCHA verification.';
    } else {
        // --- PROCEED WITH NORMAL LOGIN ---
        if ($mode === 'login') {
            $stmt = $db->prepare("SELECT id, name, password, business_name FROM admins WHERE email = ? AND is_active = 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            
            if ($user && password_verify($pass, $user['password'])) {
                session_regenerate_id(true); 
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['business']  = $user['business_name'];
                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid credentials.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EntryFlow — Authentication</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: var(--bg-main);
            margin: 0;
            padding: 20px;
        }
        .auth-box {
            background: var(--surface-1);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.4);
        }
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-family: 'DM Serif Display', serif;
            font-size: 32px;
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        .logo-dot {
            width: 14px;
            height: 14px;
            background: var(--primary);
            border-radius: 50%;
        }
        .tabs {
            display: flex;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border-color);
        }
        .tab {
            flex: 1;
            text-align: center;
            padding: 12px;
            cursor: pointer;
            color: var(--text-muted);
            font-weight: 500;
            transition: all 0.2s ease;
            font-size: 15px;
        }
        .tab.active {
            color: var(--primary-light);
            border-bottom: 2px solid var(--primary);
        }
        .pane {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        .pane.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            color: var(--text-muted);
        }
        .form-group input {
            width: 100%;
            background: var(--surface-2);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            padding: 12px 14px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
        }
        .btn-block {
            width: 100%;
            padding: 14px;
            font-size: 15px;
            margin-top: 10px;
            justify-content: center;
            border-radius: 8px;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: center;
        }
        .hint {
            text-align: center;
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }
        .hint b {
            color: var(--text-main);
            font-weight: 500;
        }
    </style>
</head>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<body>

<div class="auth-box">
    <div class="auth-header">
        <div class="logo"><div class="logo-dot"></div> EntryFlow</div>
    </div>

    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="tabs">
        <div class="tab <?= $mode === 'login' ? 'active' : '' ?>" onclick="show('pane-login', this)">Sign In</div>
        <div class="tab <?= $mode === 'register' ? 'active' : '' ?>" onclick="show('pane-register', this)">Create Account</div>
    </div>

    <div class="pane <?= $mode === 'login' ? 'active' : '' ?>" id="pane-login">
        <form method="POST">
            <input type="hidden" name="mode" value="login" />
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="you@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required />
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <div class="g-recaptcha" data-sitekey="6Ld2F9osAAAAANX98htMa4342Ipg_SqoGaGasFcT"></div>
            </div>
            <button class="btn btn-primary btn-block" type="submit">Sign In to Dashboard</button>
        </form>
    </div>

    <div class="pane <?= $mode === 'register' ? 'active' : '' ?>" id="pane-register">
        <form method="POST">
            <input type="hidden" name="mode" value="register" />
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" placeholder="Your name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" />
            </div>
            <div class="form-group">
                <label>Business Name</label>
                <input type="text" name="business_name" placeholder="Your business name" required value="<?= htmlspecialchars($_POST['business_name'] ?? '') ?>" />
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="you@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
            </div>
            <div class="form-group">
                <label>Password <span style="font-size: 11px; color: var(--text-muted);">(min 6 chars)</span></label>
                <input type="password" name="password" placeholder="••••••••" required />
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <div class="g-recaptcha" data-sitekey="6Ld2F9osAAAAANX98htMa4342Ipg_SqoGaGasFcT"></div>
            </div>
            <button class="btn btn-primary btn-block" type="submit">Create Account</button>
        </form>
    </div>
</div>

<script>
function show(paneId, el) {
    document.querySelectorAll('.pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.getElementById(paneId).classList.add('active');
    el.classList.add('active');
}
</script>

</body>
</html>