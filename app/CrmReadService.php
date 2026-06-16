<?php
/**
 * CrmReadService — read-only view over tp-crm case finance data.
 */
if (!defined('TP_FINANCE')) { http_response_code(403); exit('Forbidden'); }

final class CrmReadService
{
    /**
     * Approved customer case P&L summaries from tp-crm CustomerCaseProfitService.
     *
     * @return array{available:bool,reason:?string,items:list<array<string,mixed>>,count:int,source:?string}
     */
    public static function casePnlSummaries(int $limit = 50): array
    {
        $base = ['available' => false, 'reason' => null, 'items' => [], 'count' => 0, 'source' => null];

        if (!defined('TP_COMMON_AVAILABLE') || !TP_COMMON_AVAILABLE || !class_exists('TpCommon\\Http\\Client')) {
            return ['reason' => 'tp-common ยังไม่พร้อม (composer install)'] + $base;
        }
        if (!(getenv('TP_CRM_API_KEY') ?: getenv('CRM_API_KEY'))) {
            return ['reason' => 'ยังไม่ตั้ง TP_CRM_API_KEY สำหรับอ่าน Case P&L จาก tp-crm'] + $base;
        }

        try {
            $apiUrl = getenv('TP_CRM_API_URL') ?: (getenv('CRM_API_URL') ?: (CRM_BASE_URL . '/api'));
            $client = new \TpCommon\Http\Client($apiUrl, (string)(getenv('TP_CRM_API_KEY') ?: getenv('CRM_API_KEY')));
            $resp = $client->get('/finance_case_pl.php', ['limit' => max(1, min($limit, 100))]);
        } catch (Throwable $e) {
            return ['reason' => 'เชื่อมต่อ tp-crm ไม่ได้'] + $base;
        }

        if (!is_array($resp) || ($resp['success'] ?? false) !== true) {
            return ['reason' => 'tp-crm ปฏิเสธคำขอ (ตรวจ API key/scope finance.read)'] + $base;
        }

        $data = $resp['data'] ?? [];
        $items = [];
        foreach (($data['items'] ?? []) as $row) {
            if (!is_array($row)) { continue; }
            $items[] = [
                'customer_id' => (int)($row['customer_id'] ?? 0),
                'customer_code' => (string)($row['customer_code'] ?? ''),
                'name' => (string)($row['name'] ?? ''),
                'revenue' => (float)($row['revenue'] ?? 0),
                'costs' => (float)($row['costs'] ?? 0),
                'profit' => (float)($row['profit'] ?? 0),
                'margin_pct' => isset($row['margin_pct']) ? (float)$row['margin_pct'] : null,
                'gap_count' => (int)($row['gap_count'] ?? 0),
            ];
        }

        return [
            'available' => true,
            'reason' => null,
            'items' => $items,
            'count' => (int)($data['count'] ?? count($items)),
            'source' => (string)($data['source'] ?? 'CustomerCaseProfitService'),
        ];
    }

    public static function money(?float $n): string
    {
        if ($n === null) { return '—'; }
        return number_format($n, 2) . ' ฿';
    }
}
