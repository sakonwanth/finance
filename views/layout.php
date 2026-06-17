<?php
/** Company-style operations shell for TP-ASSET internal systems. */
if (!defined('TP_FINANCE')) { http_response_code(403); exit('Forbidden'); }
$navGroups = [
    'ภาพรวม' => [
        'dashboard'         => ['ภาพรวมการเงิน', 'fa-chart-line'],
        'profit-loss'       => ['กำไร-ขาดทุน', 'fa-chart-pie'],
        'project-tracker'   => ['รายโปรเจกต์', 'fa-diagram-project'],
    ],
    'บัญชีและ Flow' => [
        'account-structure' => ['โครงสร้างบัญชี', 'fa-building-columns'],
        'account-mapping'   => ['บัญชี ERP', 'fa-code-branch'],
        'usage-guide'       => ['คู่มือใช้บัญชี', 'fa-route'],
        'flow-audit'        => ['Flow Audit', 'fa-shield-halved'],
    ],
    'เครื่องมือ' => [
        'calculator'        => ['คำนวณดีล', 'fa-calculator'],
        'case-pnl'          => ['กำไรต่อเคส', 'fa-file-invoice-dollar'],
        'case-studies'      => ['เคสตัวอย่าง', 'fa-folder-open'],
        'integration'       => ['เชื่อมระบบ', 'fa-plug-circle-check'],
    ],
];
$nav = [];
foreach ($navGroups as $groupItems) {
    $nav += $groupItems;
}
$pageDescriptions = [
    'dashboard'         => 'ศูนย์ควบคุม Finance Flow และสถานะข้อมูลจาก ERP/CRM',
    'profit-loss'       => 'สรุปผลประกอบการจากแหล่งข้อมูลจริงแบบ read-only',
    'project-tracker'   => 'ติดตามรายรับ ต้นทุน และกำไรต่อโปรเจกต์',
    'account-structure' => 'นิยามบัญชีหลัก 4 ใบของบริษัทและข้อควรระวัง',
    'account-mapping'   => 'ตรวจ mapping ระหว่าง Finance Flow, บัญชีธนาคาร และ GL',
    'usage-guide'       => 'คู่มือเลือกบัญชีให้ถูกตามสถานการณ์จริง',
    'flow-audit'        => 'ตรวจรายการ ERP ledger เทียบ policy การไหลของเงิน',
    'calculator'        => 'ประเมินกำไรดีลแบบ what-if โดยไม่บันทึกบัญชีจริง',
    'case-pnl'          => 'อ่านกำไร-ขาดทุนรายเคสจาก tp-crm',
    'case-studies'      => 'ตัวอย่าง flow เงินของดีลอสังหาริมทรัพย์รูปแบบต่าง ๆ',
    'integration'       => 'สถานะการเชื่อมต่อแหล่งข้อมูลหลักของระบบ',
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="assets/style.css?v=2">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" aria-label="Finance navigation">
        <a class="brand-block" href="?page=dashboard">
            <span class="brand-mark">TP</span>
            <span>
                <strong>TP-ASSET</strong>
                <small>Finance Flow</small>
            </span>
        </a>

        <nav class="side-nav">
            <?php foreach ($navGroups as $group => $items): ?>
                <section class="nav-section">
                    <h2><?= $e($group) ?></h2>
                    <?php foreach ($items as $key => [$label, $icon]): ?>
                        <a class="nav-link <?= $current === $key ? 'active' : '' ?>" href="?page=<?= $e($key) ?>">
                            <i class="fa-solid <?= $e($icon) ?>" aria-hidden="true"></i>
                            <span><?= $e($label) ?></span>
                        </a>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>
        </nav>

        <div class="system-card">
            <span class="status-dot"></span>
            <span>Read-only finance layer</span>
        </div>
    </aside>

    <div class="main-shell">
        <header class="topbar">
            <div>
                <div class="breadcrumb">TP-ASSET Finance</div>
                <h1 class="page-title"><?= $e($pageTitle) ?></h1>
                <p class="page-subtitle"><?= $e($pageDescriptions[$current] ?? 'ระบบการเงินภายในบริษัท') ?></p>
            </div>
            <a class="top-action" href="?page=integration">
                <i class="fa-solid fa-signal" aria-hidden="true"></i>
                <span>สถานะระบบ</span>
            </a>
        </header>

        <nav class="mobile-nav" aria-label="Finance modules">
            <?php foreach ($nav as $key => [$label, $icon]): ?>
                <a class="<?= $current === $key ? 'active' : '' ?>" href="?page=<?= $e($key) ?>">
                    <i class="fa-solid <?= $e($icon) ?>" aria-hidden="true"></i>
                    <span><?= $e($label) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <main class="container">
            <?php require $pageFile; ?>
        </main>
    </div>
</div>
</body>
</html>
