#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$index = file_get_contents($root . '/index.php');
$checks = [
    'redirect mode' => str_contains($index, "FINANCE_WEB_MODE"),
    'dashboard moved' => str_contains($index, "'dashboard' => '/dashboard/financial'"),
    'calculator moved' => str_contains($index, "'calculator' => '/operations/deal-calculator'"),
    'P&L moved' => str_contains($index, "'profit-loss' => '/reports/project-pl'"),
    'integration moved' => str_contains($index, "'integration' => '/settings/integrations'"),
    'account mapping moved' => str_contains($index, "'account-mapping' => '/settings/account-mapping'"),
    'flow audit moved' => str_contains($index, "'flow-audit' => '/audit/finance-flow'"),
    'usage guide moved' => str_contains($index, "'usage-guide' => '/help/finance'"),
    'case studies moved' => str_contains($index, "'case-studies' => '/reports/customer-case-pl'"),
    'no-store redirect' => str_contains($index, "header('Cache-Control: no-store')"),
];
$failed = 0;
foreach ($checks as $name => $ok) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    $failed += $ok ? 0 : 1;
}
echo 'Finance retirement audit: ' . (count($checks) - $failed) . ' passed, ' . $failed . ' failed' . PHP_EOL;
exit($failed === 0 ? 0 : 1);
