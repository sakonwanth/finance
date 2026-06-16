<?php
/**
 * DealCalculator — stateless what-if calculator using the shared DealPnl formula.
 * This is not a financial record and never persists user input.
 */
if (!defined('TP_FINANCE')) { http_response_code(403); exit('Forbidden'); }

final class DealCalculator
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function compute(array $input): array
    {
        $sale     = self::num($input['sale'] ?? 0);
        $seller   = self::num($input['seller'] ?? 0);
        $mortgage = self::num($input['mortgage'] ?? 0);
        $booking  = self::num($input['booking'] ?? 0);
        $reno     = self::num($input['reno'] ?? 0);
        $expense  = self::num($input['expense'] ?? 0);
        $interest = self::num($input['interest'] ?? 0);

        $costComponents = [
            'seller' => $seller,
            'booking' => $booking,
            'renovation' => $reno,
            'company_expense' => $expense,
            'finance_cost' => $interest,
        ];

        if (class_exists('TpCommon\\Ledger\\DealPnl')) {
            $pnl = \TpCommon\Ledger\DealPnl::compute(['sale' => $sale], $costComponents);
            $totalCost = (float) $pnl['cost']['total'];
            $net = (float) $pnl['gross'];
            $margin = $pnl['margin_pct'];
            $source = 'TpCommon\\Ledger\\DealPnl';
        } else {
            $totalCost = array_sum($costComponents);
            $net = $sale - $totalCost;
            $margin = $sale > 0 ? round($net / $sale * 100, 2) : null;
            $source = 'local fallback';
        }

        $grossMargin = $sale - $seller;
        $realRevenue = $sale - $mortgage;
        [$advice, $severity] = self::advice($sale, $net, $margin, $reno, $grossMargin);

        return [
            'inputs' => [
                'sale' => $sale,
                'seller' => $seller,
                'mortgage' => $mortgage,
                'booking' => $booking,
                'reno' => $reno,
                'expense' => $expense,
                'interest' => $interest,
            ],
            'gross_margin' => $grossMargin,
            'real_revenue' => $realRevenue,
            'total_cost' => $totalCost,
            'net_profit' => $net,
            'profit_margin_pct' => $margin,
            'advice' => $advice,
            'severity' => $severity,
            'formula_source' => $source,
            'is_authoritative_record' => false,
        ];
    }

    private static function num(mixed $value): float
    {
        if (is_string($value)) {
            $value = str_replace([',', ' '], '', $value);
        }
        $n = is_numeric($value) ? (float) $value : 0.0;
        return max(0.0, $n);
    }

    /**
     * @return array{0:string,1:string}
     */
    private static function advice(float $sale, float $net, ?float $margin, float $reno, float $grossMargin): array
    {
        if ($sale <= 0) {
            return ['', 'neutral'];
        }
        if ($net <= 0) {
            return ['ดีลนี้ขาดทุน — ทบทวนราคาผู้ขาย ค่ารีโนเวท และค่าใช้จ่ายการเงินก่อนตัดสินใจ', 'danger'];
        }
        if ($margin !== null && $margin < 10) {
            return ['margin ต่ำ (' . $margin . '%) — เสี่ยงถ้าค่ารีโนเวทหรือดอกเบี้ยเพิ่ม', 'warning'];
        }
        if ($grossMargin > 0 && $reno / $grossMargin > 0.5) {
            return ['กำไรเป็นบวก แต่ค่ารีโนเวทกินส่วนต่างเกินครึ่ง — ควรล็อก BOQ และ contingency ให้ชัด', 'warning'];
        }
        return ['margin ' . ($margin ?? 0) . '% — อยู่ในเกณฑ์ดีสำหรับ what-if แต่ต้องเทียบตัวเลขจริงจาก CRM/ERP ก่อนปิดดีล', 'ok'];
    }
}
