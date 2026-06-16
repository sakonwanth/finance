<?php
/**
 * TP-Finance bootstrap — pure PHP + tp-common (SSO/standards).
 * Read-layer over tp-erp/tp-crm; owns only its own finance_* config.
 */
declare(strict_types=1);

if (defined('BASE_PATH')) {
    return;
}
define('BASE_PATH', __DIR__);
define('TP_FINANCE', true); // access guard for views/config

// Composer autoload + tp-common detection
$autoload = BASE_PATH . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}
unset($autoload);
define('TP_COMMON_AVAILABLE', class_exists('TpCommon\\Session\\SharedSession'));

// Config
require_once BASE_PATH . '/config/app.php';

// App services
require_once BASE_PATH . '/app/FinanceReadService.php';

if (TP_COMMON_AVAILABLE && class_exists('TpCommon\\ErrorHandler')) {
    \TpCommon\ErrorHandler::register('tp-finance', BASE_PATH . '/logs');
}

// Shared SSO session (cookie tp_session) when tp-common is present
if (TP_COMMON_AVAILABLE) {
    \TpCommon\Session\SharedSession::start(['project' => 'tp-finance']);
} elseif (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require an authenticated internal staff session. Bypassable only off-production
 * via FINANCE_REQUIRE_AUTH=0 (local dev). Mirrors boq_require_login().
 */
function tpfin_require_login(): void
{
    if (!tpfin_auth_required()) {
        return;
    }
    if (TP_COMMON_AVAILABLE && class_exists('TpCommon\\Auth\\SsoGuard')) {
        \TpCommon\Auth\SsoGuard::configure(getenv('CRM_BASE_URL') ?: 'https://crm.tp-asset.com');
        \TpCommon\Auth\SsoGuard::requireLogin();
        return;
    }
    // tp-common unavailable but auth required → fail closed
    http_response_code(503);
    exit('SSO unavailable. Configure tp-common or set FINANCE_REQUIRE_AUTH=0 for local dev.');
}

function tpfin_auth_required(): bool
{
    $env = getenv('FINANCE_REQUIRE_AUTH');
    if ($env !== false && $env !== '') {
        return $env === '1' || strtolower($env) === 'true';
    }
    return tpfin_is_production_host();
}

function tpfin_is_production_host(): bool
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return str_contains($host, 'tp-asset.com');
}
