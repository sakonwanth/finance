<?php
/**
 * FinanceReadService — read-only view over tp-erp finance data (via TpCommon\Http\ErpClient).
 * tp-finance owns NO real financial figures; it only reads. Fails soft (graceful degrade)
 * so pages never break when tp-common/API key is absent or the API is unreachable.
 */
if (!defined('TP_FINANCE')) { http_response_code(403); exit('Forbidden'); }

final class FinanceReadService
{
    /**
     * Headline financial snapshot from tp-erp /dashboard/financial.
     * Only exposes values that are exact + reliable (cash total, gross margin %, and
     * posted-this-month category breakdowns). Grand-total P&L is left to tp-erp income
     * statement to avoid showing approximate numbers in a finance tool.
     */
    public static function snapshot(?int $year = null, ?int $month = null): array
    {
        $base = [
            'available' => false,
            'reason'    => null,
            'period'    => null,
            'cash_total'=> null,
            'gross_margin_pct' => null,
            'revenue_categories' => [],
            'expense_categories' => [],
        ];

        if (!defined('TP_COMMON_AVAILABLE') || !TP_COMMON_AVAILABLE || !class_exists('TpCommon\\Http\\ErpClient')) {
            return ['reason' => 'tp-common ยังไม่พร้อม (composer install)'] + $base;
        }
        $key = getenv('TP_ERP_API_KEY') ?: getenv('ERP_API_KEY');
        if (!$key) {
            return ['reason' => 'ยังไม่ตั้ง TP_ERP_API_KEY บนเซิร์ฟเวอร์'] + $base;
        }

        try {
            $erp = \TpCommon\Http\ErpClient::fromEnv();
            $params = [];
            if ($year)  { $params['year'] = $year; }
            if ($month) { $params['month'] = str_pad((string)$month, 2, '0', STR_PAD_LEFT); }
            $resp = $params
                ? $erp->get('/dashboard/financial', $params)
                : $erp->getFinancialDashboard();
        } catch (\Throwable $e) {
            return ['reason' => 'เชื่อมต่อ tp-erp ไม่ได้'] + $base;
        }

        if (!is_array($resp) || ($resp['success'] ?? false) !== true) {
            return ['reason' => 'tp-erp ปฏิเสธคำขอ (ตรวจ API key/scope)'] + $base;
        }
        $d = $resp['data'] ?? [];

        return [
            'available' => true,
            'reason'    => null,
            'period'    => $d['period'] ?? null,
            'cash_total'=> isset($d['cash_position']['total']) ? (float)$d['cash_position']['total'] : null,
            'gross_margin_pct' => isset($d['financial_ratios']['gross_margin']) ? (float)$d['financial_ratios']['gross_margin'] : null,
            'revenue_categories' => self::categories($d['revenue_by_category'] ?? []),
            'expense_categories' => self::categories($d['expense_by_category'] ?? []),
        ];
    }

    /** Normalize a *_by_category list to [{name, amount}], skipping malformed rows. */
    private static function categories(mixed $rows): array
    {
        if (!is_array($rows)) { return []; }
        $out = [];
        foreach ($rows as $r) {
            if (!is_array($r) || !isset($r['amount'])) { continue; }
            $out[] = [
                'name'   => (string)($r['account_name'] ?? $r['account_code'] ?? '—'),
                'amount' => (float)$r['amount'],
            ];
        }
        return $out;
    }

    public static function money(?float $n): string
    {
        if ($n === null) { return '—'; }
        return number_format($n, 2) . ' ฿';
    }
}
