<?php
/**
 * IntegrationStatusService — read-only readiness view across TP ecosystem.
 */
if (!defined('TP_FINANCE')) { http_response_code(403); exit('Forbidden'); }

final class IntegrationStatusService
{
    /**
     * @return list<array<string,mixed>>
     */
    public static function all(): array
    {
        return [
            self::common(),
            self::erp(),
            self::crm(),
            self::crmFinanceCasePnl(),
            self::hr(),
            self::checkin(),
            self::calculator(),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function common(): array
    {
        $classes = [
            'SharedSession' => class_exists('TpCommon\\Session\\SharedSession'),
            'SsoGuard' => class_exists('TpCommon\\Auth\\SsoGuard'),
            'ErpClient' => class_exists('TpCommon\\Http\\ErpClient'),
            'DealPnl' => class_exists('TpCommon\\Ledger\\DealPnl'),
        ];
        $ok = defined('TP_COMMON_AVAILABLE') && TP_COMMON_AVAILABLE && !in_array(false, $classes, true);

        return self::row(
            'common',
            'tp-common',
            'SSO / API clients / สูตรกลาง',
            $ok ? 'ok' : 'error',
            $ok ? 'พร้อมใช้งาน' : 'ขาด class สำคัญ: ' . implode(', ', array_keys(array_filter($classes, static fn ($v) => !$v))),
            'Composer package tpasset/tp-common',
            null
        );
    }

    /**
     * @return array<string,mixed>
     */
    private static function erp(): array
    {
        $key = getenv('TP_ERP_API_KEY') ?: getenv('ERP_API_KEY');
        if (!class_exists('TpCommon\\Http\\ErpClient')) {
            return self::row('erp', 'tp-erp', 'GL / ledger / reports', 'error', 'ไม่มี ErpClient', ERP_BASE_URL, '/dashboard/financial');
        }
        if (!$key) {
            return self::row('erp', 'tp-erp', 'GL / ledger / reports', 'warning', 'ยังไม่ตั้ง TP_ERP_API_KEY — หน้า read-layer จะแสดง fallback/warnbox', ERP_BASE_URL, '/dashboard/financial');
        }

        try {
            $resp = \TpCommon\Http\ErpClient::fromEnv()->get('/ping');
            $ok = is_array($resp) && ($resp['success'] ?? false) === true;
            return self::row(
                'erp',
                'tp-erp',
                'GL / ledger / reports',
                $ok ? 'ok' : 'error',
                $ok ? 'API ping พร้อมใช้งาน' : ('API ping ไม่สำเร็จ: ' . (string)($resp['error'] ?? 'unknown')),
                ERP_BASE_URL,
                '/api/v1/ping'
            );
        } catch (Throwable $e) {
            return self::row('erp', 'tp-erp', 'GL / ledger / reports', 'error', 'เชื่อมต่อไม่ได้: ' . $e->getMessage(), ERP_BASE_URL, '/api/v1/ping');
        }
    }

    /**
     * @return array<string,mixed>
     */
    private static function crm(): array
    {
        $health = self::httpJson(CRM_BASE_URL . '/api/health');
        $hasKey = (bool)(getenv('TP_CRM_API_KEY') ?: getenv('CRM_API_KEY'));
        $status = $health['ok'] ? 'ok' : 'warning';
        $detail = $health['ok'] ? 'health พร้อม' : 'health probe ไม่สำเร็จ';
        $detail .= $hasKey ? ' · API key พร้อม' : ' · ยังไม่ตั้ง TP_CRM_API_KEY สำหรับ read API เฉพาะทาง';

        return self::row('crm', 'tp-crm', 'customer / case / deal context', $status, $detail, CRM_BASE_URL, '/api/health');
    }

    /**
     * @return array<string,mixed>
     */
    private static function crmFinanceCasePnl(): array
    {
        $key = getenv('TP_CRM_API_KEY') ?: getenv('CRM_API_KEY');
        if (!$key) {
            return self::row(
                'crm-case-pnl',
                'CRM Case P&L',
                'case profit source',
                'warning',
                'ยังไม่ตั้ง TP_CRM_API_KEY — ออก key scope finance.read จาก tp-crm ก่อน',
                CRM_BASE_URL,
                '/api/finance_case_pl.php'
            );
        }
        $probe = CrmReadService::casePnlSummaries(1);
        return self::row(
            'crm-case-pnl',
            'CRM Case P&L',
            'case profit source',
            $probe['available'] ? 'ok' : 'error',
            $probe['available'] ? 'อ่าน Case P&L จาก CustomerCaseProfitService ได้' : (string)($probe['reason'] ?? 'อ่านไม่ได้'),
            CRM_BASE_URL,
            '/api/finance_case_pl.php'
        );
    }

    /**
     * @return array<string,mixed>
     */
    private static function hr(): array
    {
        $base = HR_BASE_URL;
        $health = self::httpJson($base . '/api/health.php');
        $hasKey = (bool)(getenv('TP_HR_API_KEY') ?: getenv('HR_API_KEY'));
        $status = $health['ok'] ? 'ok' : 'warning';
        $detail = $health['ok'] ? 'health พร้อม' : 'health probe ไม่สำเร็จ';
        $detail .= $hasKey ? ' · HR API key พร้อม' : ' · ยังไม่ตั้ง TP_HR_API_KEY สำหรับ payroll/attendance cost read';

        return self::row('hr', 'tp-hr', 'payroll / staff cost', $status, $detail, $base, '/api/health.php');
    }

    /**
     * @return array<string,mixed>
     */
    private static function checkin(): array
    {
        $base = CHECKIN_BASE_URL;
        $health = self::httpJson($base . '/api/health.php');
        return self::row(
            'checkin',
            'tp-checkin',
            'attendance source for HR cost',
            $health['ok'] ? 'ok' : 'warning',
            $health['ok'] ? 'health พร้อม' : 'health probe ไม่สำเร็จ — ยังอ่าน attendance ผ่าน HR/ERP เป็นหลักได้',
            $base,
            '/api/health.php'
        );
    }

    /**
     * @return array<string,mixed>
     */
    private static function calculator(): array
    {
        $ok = class_exists('TpCommon\\Ledger\\DealPnl');
        return self::row(
            'calculator',
            'Deal Calculator',
            'what-if formula',
            $ok ? 'ok' : 'warning',
            $ok ? 'ใช้ TpCommon\\Ledger\\DealPnl เป็นสูตรกลาง' : 'ใช้ local fallback เพราะ DealPnl ไม่พร้อม',
            'tp-finance',
            '/api/calculate.php'
        );
    }

    /**
     * @return array<string,mixed>
     */
    private static function row(string $key, string $name, string $role, string $status, string $detail, string $owner, ?string $endpoint): array
    {
        return [
            'key' => $key,
            'name' => $name,
            'role' => $role,
            'status' => $status,
            'detail' => $detail,
            'owner' => $owner,
            'endpoint' => $endpoint,
        ];
    }

    /**
     * @return array{ok:bool,http_code:int,error:string|null}
     */
    private static function httpJson(string $url): array
    {
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'http_code' => 0, 'error' => 'curl unavailable'];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 2,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        $json = is_string($raw) ? json_decode($raw, true) : null;
        $status = is_array($json) ? (string)($json['status'] ?? '') : '';
        $ok = $code >= 200 && $code < 300 && in_array($status, ['ok', 'healthy'], true);

        return ['ok' => $ok, 'http_code' => $code, 'error' => $err ?: null];
    }
}
