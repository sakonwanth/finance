#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$index = (string)file_get_contents($root . '/index.php');
$runtimePhp = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);
foreach ($iterator as $file) {
    $path = $file->getPathname();
    if ($file->isFile()
        && $file->getExtension() === 'php'
        && !str_contains($path, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR)
        && !str_contains($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) {
        $runtimePhp[] = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
    }
}
sort($runtimePhp);
$checks = [
    'finance runtime is redirect-only' => str_contains($index, "header('Location: '"),
    'retirement response header exists' => str_contains($index, 'X-TP-Finance-Status: retired'),
    'dashboard moved' => str_contains($index, "'dashboard' => '/dashboard/financial'"),
    'calculator moved' => str_contains($index, "'calculator' => '/operations/deal-calculator'"),
    'P&L moved' => str_contains($index, "'profit-loss' => '/reports/project-pl'"),
    'integration moved' => str_contains($index, "'integration' => '/settings/integrations'"),
    'account mapping moved' => str_contains($index, "'account-mapping' => '/settings/account-mapping'"),
    'flow audit moved' => str_contains($index, "'flow-audit' => '/audit/finance-flow'"),
    'usage guide moved' => str_contains($index, "'usage-guide' => '/help/finance'"),
    'case studies moved' => str_contains($index, "'case-studies' => '/reports/customer-case-pl'"),
    'no-store redirect' => str_contains($index, "header('Cache-Control: no-store')"),
    'legacy app runtime removed' => array_filter($runtimePhp, static fn(string $path): bool => str_starts_with($path, 'app/')) === [],
    'legacy views runtime removed' => array_filter($runtimePhp, static fn(string $path): bool => str_starts_with($path, 'views/')) === [],
    'legacy API runtime removed' => array_filter($runtimePhp, static fn(string $path): bool => str_starts_with($path, 'api/')) === [],
    'only shell PHP files remain' => $runtimePhp === [
        'index.php',
        'scripts/audit_retirement_contract.php',
        'scripts/qa_e2e_retirement_redirects.php',
    ],
];

$failed = 0;
foreach ($checks as $name => $ok) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    $failed += $ok ? 0 : 1;
}
echo 'Finance retirement audit: ' . (count($checks) - $failed) . ' passed, ' . $failed . ' failed' . PHP_EOL;
exit($failed === 0 ? 0 : 1);
