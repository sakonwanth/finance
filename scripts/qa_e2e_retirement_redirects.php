#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$index = (string)file_get_contents($root . '/index.php');
$targets = [
    'account-mapping' => '/settings/account-mapping',
    'flow-audit' => '/audit/finance-flow',
];
$failed = 0;

foreach ($targets as $page => $target) {
    $hasTarget = str_contains($index, "'{$page}' => '{$target}'");
    $hasLegacyTarget = $page === 'account-mapping'
        ? str_contains($index, "'{$page}' => '/gl/accounts'")
        : str_contains($index, "'{$page}' => '/settings/integrations'");
    echo ($hasTarget ? 'PASS ' : 'FAIL ') . "{$page} canonical redirect" . PHP_EOL;
    echo (!$hasLegacyTarget ? 'PASS ' : 'FAIL ') . "{$page} legacy redirect removed" . PHP_EOL;
    $failed += $hasTarget ? 0 : 1;
    $failed += $hasLegacyTarget ? 1 : 0;
}

echo 'Finance redirect E2E: ' . ((count($targets) * 2) - $failed)
    . ' passed, ' . $failed . ' failed' . PHP_EOL;
exit($failed === 0 ? 0 : 1);
