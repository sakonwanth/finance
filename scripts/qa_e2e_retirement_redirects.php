#!/usr/bin/env php
<?php

declare(strict_types=1);

$index = (string)file_get_contents(dirname(__DIR__) . '/index.php');
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
$failed = 0;
foreach ($targets as $page => $target) {
    $ok = str_contains($index, "'{$page}' => '{$target}'");
    echo ($ok ? '[OK]   ' : '[FAIL] ') . "{$page} -> {$target}" . PHP_EOL;
    $failed += $ok ? 0 : 1;
}
$fallback = str_contains($index, "\$targets[\$page] ?? '/dashboard/financial'");
echo ($fallback ? '[OK]   ' : '[FAIL] ') . 'unknown page fallback' . PHP_EOL;
$failed += $fallback ? 0 : 1;
echo 'Finance redirect E2E: ' . ((count($targets) + 1) - $failed)
    . ' passed, ' . $failed . ' failed' . PHP_EOL;
exit($failed === 0 ? 0 : 1);
