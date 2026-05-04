<?php
// includes/header.php
// This handles the head, opening tags, and sidebar for all pages.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>EntryFlow — <?= htmlspecialchars($user['business_name'] ?? 'Dashboard') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time(); ?>">
</head>
<body>
    
    <aside class="sidebar">
        <div class="logo-wrap">
            <div class="logo"><div class="logo-dot"></div> EntryFlow</div>
            <div class="biz-name"><?= htmlspecialchars($user['business_name'] ?? 'My Business') ?></div>
        </div>

        <nav>
            <div class="nav-label">Menu</div>
            <a href="index.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                <span class="ni-ic">📊</span> Dashboard
            </a>
            <a href="transactions.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'active' : '' ?>">
                <span class="ni-ic">💸</span> Transactions
            </a>
            <a href="clients.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'clients.php' ? 'active' : '' ?>">
                <span class="ni-ic">👥</span> Clients
            </a>
            <a href="reports.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">
                <span class="ni-ic">📈</span> Reports
            </a>
            
            <div class="nav-label" style="margin-top: 15px;">System</div>
            <a href="settings.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
                <span class="ni-ic">⚙️</span> Settings
            </a>
            <a href="logout.php" class="nav-item">
                <span class="ni-ic">🚪</span> Logout
            </a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <h1 class="page-title">
                <?php 
                    // Dynamically set title based on page
                    $pageName = basename($_SERVER['PHP_SELF'], ".php");
                    echo ucfirst($pageName); 
                ?>
            </h1>
        </header>
        
        <div class="content">