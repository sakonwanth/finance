<?php

declare(strict_types=1);

$erpBaseUrl = rtrim((string)(getenv('ERP_BASE_URL') ?: 'https://erp.tp-asset.com'), '/');
if (!preg_match('#^https://[a-z0-9.-]+(?::\d+)?$#i', $erpBaseUrl)) {
    $erpBaseUrl = 'https://erp.tp-asset.com';
}

$page = trim((string)($_GET['page'] ?? 'dashboard'));
$targets = [
    'dashboard' => '/dashboard/financial',
    'account-structure' => '/gl/accounts',
    'account-mapping' => '/settings/account-mapping',
    'flow-audit' => '/audit/finance-flow',
    'integration' => '/settings/integrations',
    'calculator' => '/operations/deal-calculator',
    'case-pnl' => '/reports/project-pl',
    'profit-loss' => '/reports/project-pl',
    'project-tracker' => '/dashboard/projects',
    'usage-guide' => '/help/finance',
    'case-studies' => '/reports/customer-case-pl',
];

header('Cache-Control: no-store');
header('X-TP-Finance-Status: retired');
header('Location: ' . $erpBaseUrl . ($targets[$page] ?? '/dashboard/financial'), true, 302);
exit;
