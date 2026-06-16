<?php
/** Mobile-first layout (Kanit, card-based) per tp-common UI standard. */
if (!defined('TP_FINANCE')) { http_response_code(403); exit('Forbidden'); }
$nav = [
    'dashboard'         => ['ภาพรวม', '📊'],
    'account-structure' => ['โครงสร้างบัญชี', '🏦'],
    'usage-guide'       => ['คู่มือใช้บัญชี', '🧭'],
    'case-studies'      => ['เคสตัวอย่าง', '📁'],
    'calculator'        => ['คำนวณดีล', '🧮'],
    'profit-loss'       => ['กำไร-ขาดทุน', '📈'],
];
$e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?><!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= $e($pageTitle) ?> · <?= $e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css?v=1">
</head>
<body>
<header class="topbar">
    <div class="brand">TP-Asset <span>Finance Flow</span></div>
</header>

<main class="container">
    <h1 class="page-title"><?= $e($pageTitle) ?></h1>
    <?php require $pageFile; ?>
</main>

<nav class="tabbar">
    <?php foreach ($nav as $key => [$label, $icon]): ?>
        <a class="tab <?= $current === $key ? 'active' : '' ?>" href="?page=<?= $e($key) ?>">
            <span class="tab-ico"><?= $icon ?></span>
            <span class="tab-label"><?= $e($label) ?></span>
        </a>
    <?php endforeach; ?>
</nav>
</body>
</html>
