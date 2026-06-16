<?php
/**
 * Stateless what-if calculation endpoint.
 */
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';

tpfin_require_login();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

try {
    $raw = file_get_contents('php://input') ?: '';
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    echo json_encode([
        'success' => true,
        'data' => DealCalculator::compute($input),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'calculate failed',
    ], JSON_UNESCAPED_UNICODE);
}
