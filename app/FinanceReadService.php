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
            'bank_accounts' => [],
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
            'bank_accounts' => self::bankAccounts($d['cash_position']['accounts'] ?? []),
        ];
    }

    /**
     * Per-project P&L from tp-erp /reports/project-pl (read-only). Same fail-soft contract.
     */
    public static function projectPnl(?int $year = null): array
    {
        $base = ['available' => false, 'reason' => null, 'year' => null, 'projects' => [], 'totals' => null];

        if (!defined('TP_COMMON_AVAILABLE') || !TP_COMMON_AVAILABLE || !class_exists('TpCommon\\Http\\ErpClient')) {
            return ['reason' => 'tp-common ยังไม่พร้อม (composer install)'] + $base;
        }
        if (!(getenv('TP_ERP_API_KEY') ?: getenv('ERP_API_KEY'))) {
            return ['reason' => 'ยังไม่ตั้ง TP_ERP_API_KEY บนเซิร์ฟเวอร์'] + $base;
        }
        try {
            $erp = \TpCommon\Http\ErpClient::fromEnv();
            $resp = $erp->get('/reports/project-pl', $year ? ['year' => $year] : []);
        } catch (\Throwable $e) {
            return ['reason' => 'เชื่อมต่อ tp-erp ไม่ได้'] + $base;
        }
        if (!is_array($resp) || ($resp['success'] ?? false) !== true) {
            return ['reason' => 'tp-erp ปฏิเสธคำขอ (ตรวจ API key/scope)'] + $base;
        }
        $d = $resp['data'] ?? [];
        $projects = [];
        foreach (($d['projects'] ?? []) as $p) {
            if (!is_array($p)) { continue; }
            $projects[] = [
                'name'    => (string)($p['project_name'] ?? $p['project_code'] ?? '—'),
                'code'    => (string)($p['project_code'] ?? ''),
                'status'  => (string)($p['status'] ?? ''),
                'revenue' => (float)($p['erp_revenue'] ?? 0),
                'cost'    => (float)($p['erp_total_cost'] ?? 0),
                'profit'  => (float)($p['erp_profit'] ?? 0),
            ];
        }
        $t = $d['totals'] ?? [];
        return [
            'available' => true,
            'reason'    => null,
            'year'      => $d['year'] ?? $year,
            'projects'  => $projects,
            'totals'    => [
                'revenue' => (float)($t['erp_revenue'] ?? 0),
                'cost'    => (float)($t['erp_total_cost'] ?? 0),
                'profit'  => (float)($t['erp_profit'] ?? 0),
                'count'   => (int)($t['projects'] ?? count($projects)),
            ],
        ];
    }

    /**
     * Company cashflow ledger from tp-erp /cashflow/ledger (read-only).
     *
     * @return array{available:bool,reason:?string,from:string,to:string,rows:list<array<string,mixed>>,meta:array<string,mixed>}
     */
    public static function cashflowLedger(?string $from = null, ?string $to = null): array
    {
        $from = self::validDate($from) ?: date('Y-m-01');
        $to = self::validDate($to) ?: date('Y-m-t');
        $base = ['available' => false, 'reason' => null, 'from' => $from, 'to' => $to, 'rows' => [], 'meta' => []];

        if (!defined('TP_COMMON_AVAILABLE') || !TP_COMMON_AVAILABLE || !class_exists('TpCommon\\Http\\ErpClient')) {
            return ['reason' => 'tp-common ยังไม่พร้อม (composer install)'] + $base;
        }
        if (!(getenv('TP_ERP_API_KEY') ?: getenv('ERP_API_KEY'))) {
            return ['reason' => 'ยังไม่ตั้ง TP_ERP_API_KEY บนเซิร์ฟเวอร์'] + $base;
        }

        try {
            $erp = \TpCommon\Http\ErpClient::fromEnv();
            $resp = $erp->get('/cashflow/ledger', ['from' => $from, 'to' => $to]);
        } catch (\Throwable $e) {
            return ['reason' => 'เชื่อมต่อ tp-erp ไม่ได้'] + $base;
        }

        if (!is_array($resp) || ($resp['success'] ?? false) !== true) {
            return ['reason' => 'tp-erp ปฏิเสธคำขอ (ตรวจ API key/scope)'] + $base;
        }

        $data = $resp['data'] ?? [];
        $items = is_array($data) && array_key_exists('items', $data) ? ($data['items'] ?? []) : $data;
        $meta = is_array($data) && isset($data['meta']) && is_array($data['meta']) ? $data['meta'] : [];

        return [
            'available' => true,
            'reason' => null,
            'from' => (string)($meta['from'] ?? $from),
            'to' => (string)($meta['to'] ?? $to),
            'rows' => self::ledgerRows($items),
            'meta' => $meta,
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

    /** Normalize ERP bank account rows to [{id, name, balance, total_in, total_out}]. */
    private static function bankAccounts(mixed $rows): array
    {
        if (!is_array($rows)) { return []; }
        $out = [];
        foreach ($rows as $r) {
            if (!is_array($r)) { continue; }
            $out[] = [
                'id' => (int)($r['id'] ?? 0),
                'name' => (string)($r['account_name'] ?? '—'),
                'balance' => (float)($r['current_balance'] ?? 0),
                'total_in' => (float)($r['total_in'] ?? 0),
                'total_out' => (float)($r['total_out'] ?? 0),
            ];
        }
        return $out;
    }

    /** Normalize ERP company transaction rows to stable keys while keeping raw data for audit. */
    private static function ledgerRows(mixed $rows): array
    {
        if (!is_array($rows)) { return []; }
        $out = [];
        foreach ($rows as $r) {
            if (!is_array($r)) { continue; }
            $out[] = [
                'id' => (int)($r['id'] ?? 0),
                'transaction_code' => (string)($r['transaction_code'] ?? ''),
                'date' => (string)($r['transaction_date'] ?? $r['tx_date'] ?? ''),
                'type' => (string)($r['transaction_type'] ?? ''),
                'amount' => (float)($r['amount'] ?? 0),
                'description' => (string)($r['description'] ?? ''),
                'reference_type' => (string)($r['reference_type'] ?? ''),
                'reference_id' => (string)($r['reference_id'] ?? ''),
                'payment_status' => (string)($r['payment_status'] ?? ''),
                'category_name' => (string)($r['category_name'] ?? ''),
                'bank_account_id' => isset($r['bank_account_id']) && $r['bank_account_id'] !== ''
                    ? (int)$r['bank_account_id']
                    : null,
                'bank_account_name' => (string)($r['bank_account_name'] ?? ''),
                'bank_account_source' => (string)($r['bank_account_source'] ?? ''),
                'raw' => $r,
            ];
        }
        return $out;
    }

    private static function validDate(?string $value): ?string
    {
        $value = trim((string)$value);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        return $value;
    }

    public static function money(?float $n): string
    {
        if ($n === null) { return '—'; }
        return number_format($n, 2) . ' ฿';
    }
}
