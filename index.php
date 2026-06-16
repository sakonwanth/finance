<?php
/**
 * TP-Finance front controller — simple page router (pure PHP).
 */
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

tpfin_require_login(); // internal staff SSO gate

$pages = [
    'dashboard'        => ['title' => 'ภาพรวม',          'file' => 'dashboard.php'],
    'account-structure'=> ['title' => 'โครงสร้างบัญชี',   'file' => 'account-structure.php'],
    'account-mapping'  => ['title' => 'บัญชี ERP',        'file' => 'account-mapping.php'],
    'flow-audit'       => ['title' => 'Flow Audit',        'file' => 'flow-audit.php'],
    'usage-guide'      => ['title' => 'คู่มือใช้บัญชี',    'file' => 'usage-guide.php'],
    'case-studies'     => ['title' => 'เคสตัวอย่าง',       'file' => 'case-studies.php'],
    'calculator'       => ['title' => 'คำนวณดีล',         'file' => 'calculator.php'],
    'case-pnl'         => ['title' => 'กำไรต่อเคส',       'file' => 'case-pnl.php'],
    'profit-loss'      => ['title' => 'กำไร-ขาดทุน',       'file' => 'profit-loss.php'],
    'project-tracker'  => ['title' => 'รายโปรเจกต์',       'file' => 'project-tracker.php'],
    'integration'      => ['title' => 'การเชื่อมระบบ',      'file' => 'integration.php'],
];

$page = (string) ($_GET['page'] ?? 'dashboard');
if (!isset($pages[$page])) {
    $page = 'dashboard';
}
$current = $page;
$pageTitle = $pages[$page]['title'];
$pageFile  = BASE_PATH . '/views/pages/' . $pages[$page]['file'];

require BASE_PATH . '/views/layout.php';
