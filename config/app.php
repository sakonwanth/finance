<?php
/**
 * TP-Finance config. Read-layer endpoints to tp-erp/tp-crm are added in P3.
 */
if (!defined('TP_FINANCE')) { http_response_code(403); exit('Forbidden'); }

// Load .env if present (simple parser; tp-common Env used once vendor installed)
$envFile = BASE_PATH . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) { continue; }
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k); $v = trim($v);
        if (getenv($k) === false) { putenv("$k=$v"); $_ENV[$k] = $v; }
    }
}
unset($envFile);

define('APP_NAME', 'TP-Asset Finance Flow');
define('APP_URL', rtrim(getenv('APP_URL') ?: '', '/'));
// Read-layer sources (used from P3 onward)
define('CRM_BASE_URL', rtrim(getenv('CRM_BASE_URL') ?: 'https://crm.tp-asset.com', '/'));
define('ERP_BASE_URL', rtrim(getenv('ERP_BASE_URL') ?: 'https://erp.tp-asset.com', '/'));
