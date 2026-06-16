<?php
/**
 * AccountMappingService — maps the four finance flow accounts to ERP accounts.
 * Read-only: it reports mapping readiness; it never creates or updates ERP accounts.
 */
if (!defined('TP_FINANCE')) { http_response_code(403); exit('Forbidden'); }

final class AccountMappingService
{
    /**
     * @return array{available:bool,reason:?string,rows:list<array<string,mixed>>}
     */
    public static function rows(): array
    {
        $accounts = require BASE_PATH . '/views/data/accounts.php';
        $snap = FinanceReadService::snapshot();
        $bankById = [];
        foreach (($snap['bank_accounts'] ?? []) as $bank) {
            $bankById[(int)$bank['id']] = $bank;
        }

        $rows = [];
        foreach ($accounts as $a) {
            $bankId = self::envInt((string)$a['erp_bank_env']);
            $glCode = trim((string)(getenv((string)$a['gl_env']) ?: ''));
            $bank = $bankId !== null ? ($bankById[$bankId] ?? null) : null;

            $issues = [];
            if ($bankId === null) {
                $issues[] = 'ยังไม่ตั้ง ' . $a['erp_bank_env'];
            } elseif ($snap['available'] && $bank === null) {
                $issues[] = 'ไม่พบ bank account id นี้ใน tp-erp';
            }
            if ($glCode === '') {
                $issues[] = 'ยังไม่ตั้ง ' . $a['gl_env'];
            }
            if (!$snap['available']) {
                $issues[] = 'ยังยืนยันกับ tp-erp ไม่ได้: ' . (string)($snap['reason'] ?? 'ไม่พร้อม');
            }

            $rows[] = [
                'key' => (string)$a['key'],
                'name' => (string)$a['name'],
                'purpose' => (string)$a['purpose'],
                'color' => (string)$a['color'],
                'flow_role' => (string)$a['flow_role'],
                'bank_env' => (string)$a['erp_bank_env'],
                'gl_env' => (string)$a['gl_env'],
                'bank_id' => $bankId,
                'bank_name' => $bank['name'] ?? null,
                'bank_balance' => isset($bank['balance']) ? (float)$bank['balance'] : null,
                'gl_code' => $glCode !== '' ? $glCode : null,
                'allowed_refs' => $a['allowed_refs'] ?? [],
                'status' => $issues === [] ? 'ok' : 'warning',
                'issues' => $issues,
            ];
        }

        return [
            'available' => (bool)$snap['available'],
            'reason' => $snap['reason'] ?? null,
            'rows' => $rows,
        ];
    }

    private static function envInt(string $name): ?int
    {
        $value = trim((string)(getenv($name) ?: ''));
        if ($value === '' || !ctype_digit($value)) {
            return null;
        }
        $id = (int)$value;
        return $id > 0 ? $id : null;
    }
}
