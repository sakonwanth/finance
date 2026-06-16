<?php
/**
 * FlowAuditService — read-only controls for Finance Flow ↔ ERP ledger placement.
 * It audits policy readiness and ERP ledger rows; it never writes corrections.
 */
if (!defined('TP_FINANCE')) { http_response_code(403); exit('Forbidden'); }

final class FlowAuditService
{
    /**
     * @return array<string,mixed>
     */
    public static function report(?string $from = null, ?string $to = null): array
    {
        $rules = self::rulesByReference();
        $map = AccountMappingService::rows();
        $accountByKey = [];
        foreach (($map['rows'] ?? []) as $row) {
            $accountByKey[(string)$row['key']] = $row;
        }

        $ledger = FinanceReadService::cashflowLedger($from, $to);
        $rows = $ledger['rows'] ?? [];
        $ledgerMeta = is_array($ledger['meta'] ?? null) ? $ledger['meta'] : [];
        $findings = [];
        $breakdown = [];
        $severityCounts = ['danger' => 0, 'warning' => 0];
        $blindPlacement = 0;
        $hasBankPayload = false;

        foreach ($rows as $tx) {
            if (!is_array($tx)) { continue; }
            $bankId = $tx['bank_account_id'] ?? null;
            if ($bankId !== null) {
                $hasBankPayload = true;
            }

            $refRaw = trim((string)($tx['reference_type'] ?? ''));
            $ref = $refRaw !== '' ? $refRaw : 'manual';
            $type = trim((string)($tx['type'] ?? ''));
            $amount = (float)($tx['amount'] ?? 0);
            $rule = $rules[$ref] ?? null;
            $key = $ref . '|' . ($type ?: '-');
            if (!isset($breakdown[$key])) {
                $breakdown[$key] = [
                    'reference_type' => $ref,
                    'transaction_type' => $type ?: '—',
                    'finance_account' => $rule['account'] ?? null,
                    'count' => 0,
                    'total' => 0.0,
                ];
            }
            $breakdown[$key]['count']++;
            $breakdown[$key]['total'] += $amount;

            if ($refRaw === '') {
                self::addFinding($findings, $severityCounts, 'warning', $tx, $ref, 'reference_type ว่าง จึงตรวจแบบ manual ได้เท่านั้น');
            }

            if ($rule === null) {
                self::addFinding($findings, $severityCounts, 'danger', $tx, $ref, 'reference_type นี้ยังไม่มี policy ว่าควรเข้าบัญชี Finance Flow ใด');
                continue;
            }

            if (($rule['direction'] ?? '') !== '' && $type !== (string)$rule['direction']) {
                self::addFinding($findings, $severityCounts, 'danger', $tx, $ref, 'transaction_type ไม่ตรง policy ที่กำหนดไว้');
            }

            $account = $accountByKey[(string)$rule['account']] ?? null;
            $expectedBankId = isset($account['bank_id']) ? (int)$account['bank_id'] : 0;
            if ($bankId === null) {
                $blindPlacement++;
            } elseif ($expectedBankId > 0 && (int)$bankId !== $expectedBankId) {
                self::addFinding($findings, $severityCounts, 'danger', $tx, $ref, 'bank_account_id ไม่ตรงกับ account mapping');
            } elseif ($expectedBankId <= 0) {
                self::addFinding($findings, $severityCounts, 'warning', $tx, $ref, 'ยังไม่ได้ตั้ง bank account mapping สำหรับบัญชีปลายทางนี้');
            }
        }

        $mappingWarnings = 0;
        foreach (($map['rows'] ?? []) as $row) {
            if (($row['status'] ?? '') !== 'ok') {
                $mappingWarnings++;
            }
        }

        $checks = [
            [
                'name' => 'Rule catalog',
                'status' => count($rules) > 0 ? 'ok' : 'danger',
                'detail' => 'มี policy สำหรับ reference type ' . count($rules) . ' แบบ',
            ],
            [
                'name' => 'Account mapping',
                'status' => $mappingWarnings === 0 ? 'ok' : 'warning',
                'detail' => $mappingWarnings === 0
                    ? 'บัญชี Finance Flow ทั้ง 4 ใบผูก ERP bank/GL พร้อม'
                    : 'ยังมีบัญชีที่ต้องตั้งค่า ' . $mappingWarnings . ' รายการ',
            ],
            [
                'name' => 'ERP ledger read',
                'status' => !empty($ledger['available']) ? 'ok' : 'warning',
                'detail' => !empty($ledger['available'])
                    ? 'อ่าน ledger ได้ ' . count($rows) . ' รายการ'
                    : (string)($ledger['reason'] ?? 'ยังอ่าน ledger ไม่ได้'),
            ],
            [
                'name' => 'Bank placement payload',
                'status' => self::bankPayloadReady($ledger, $rows, $hasBankPayload, $ledgerMeta) ? 'ok' : 'warning',
                'detail' => self::bankPayloadDetail($ledger, $rows, $hasBankPayload, $ledgerMeta),
            ],
        ];

        usort($breakdown, static function (array $a, array $b): int {
            return strcmp((string)$a['reference_type'], (string)$b['reference_type'])
                ?: strcmp((string)$a['transaction_type'], (string)$b['transaction_type']);
        });

        return [
            'from' => $ledger['from'] ?? date('Y-m-01'),
            'to' => $ledger['to'] ?? date('Y-m-t'),
            'ledger' => $ledger,
            'checks' => $checks,
            'rules' => array_values($rules),
            'breakdown' => array_values($breakdown),
            'findings' => array_slice($findings, 0, 30),
            'totals' => [
                'scanned' => count($rows),
                'pass' => max(0, count($rows) - $severityCounts['danger'] - $severityCounts['warning']),
                'warning' => $severityCounts['warning'],
                'danger' => $severityCounts['danger'],
                'blind_placement' => $blindPlacement,
                'mapping_warnings' => $mappingWarnings,
            ],
        ];
    }

    /**
     * @param list<array<string,mixed>> $rows
     * @param array<string,mixed> $meta
     */
    private static function bankPayloadReady(array $ledger, array $rows, bool $hasBankPayload, array $meta): bool
    {
        if (empty($ledger['available']) || count($rows) === 0) {
            return true;
        }
        $mode = (string)($meta['bank_account_payload'] ?? '');
        return $hasBankPayload || $mode === 'native' || $mode === 'native_without_name';
    }

    /**
     * @param list<array<string,mixed>> $rows
     * @param array<string,mixed> $meta
     */
    private static function bankPayloadDetail(array $ledger, array $rows, bool $hasBankPayload, array $meta): string
    {
        if (empty($ledger['available'])) {
            return 'รออ่าน ERP ledger ก่อนจึงตรวจ placement ได้';
        }
        if (count($rows) === 0) {
            return 'ไม่มีรายการในช่วงวันที่นี้';
        }
        $mode = (string)($meta['bank_account_payload'] ?? '');
        if ($hasBankPayload || $mode === 'native') {
            return 'ERP ส่ง bank_account_id พร้อมชื่อบัญชีแล้ว';
        }
        if ($mode === 'native_without_name') {
            return 'ERP ส่ง bank_account_id แล้ว แต่ยังไม่มีชื่อบัญชีใน payload';
        }
        if ($mode === 'missing_column') {
            return 'schema erp_company_transactions ยังไม่มี bank_account_id สำหรับตรวจ placement';
        }
        return 'ERP /cashflow/ledger ยังไม่ส่ง bank_account_id จึงตรวจ placement จริงไม่ได้';
    }

    /**
     * @return array<string,array<string,string>>
     */
    private static function rulesByReference(): array
    {
        return [
            'deal' => self::rule('deal', 'income', 'income', 'เงินรับจากดีลขาย/บริการ'),
            'customer_payment' => self::rule('customer_payment', 'income', 'income', 'เงินรับลูกค้า'),
            'ledger_income' => self::rule('ledger_income', 'income', 'income', 'รายรับจาก ledger'),
            'loan_drawdown' => self::rule('loan_drawdown', 'project', 'income', 'เงินกู้เข้าโปรเจกต์'),
            'property' => self::rule('property', 'project', 'expense', 'ซื้อทรัพย์'),
            'property_cost' => self::rule('property_cost', 'project', 'expense', 'ต้นทุนทรัพย์'),
            'debt_closure' => self::rule('debt_closure', 'project', 'expense', 'ปิดหนี้/เคลียร์ภาระ'),
            'renovation' => self::rule('renovation', 'project', 'expense', 'รีโนเวท'),
            'deal_cost' => self::rule('deal_cost', 'project', 'expense', 'ต้นทุนตรงของดีล'),
            'manual' => self::rule('manual', 'expense', 'expense', 'ค่าใช้จ่าย manual ที่ไม่มีโมดูลต้นทาง'),
            'payroll' => self::rule('payroll', 'expense', 'expense', 'เงินเดือนจาก HR/payroll'),
            'line_bot' => self::rule('line_bot', 'expense', 'expense', 'ค่าใช้จ่ายจาก LINE finance'),
            'line_expense' => self::rule('line_expense', 'expense', 'expense', 'ค่าใช้จ่ายจาก TP Expense'),
            'company_expense' => self::rule('company_expense', 'expense', 'expense', 'ค่าใช้จ่ายบริษัท'),
            'profit_transfer' => self::rule('profit_transfer', 'holding', 'income', 'โอนกำไรหลังปิดดีล'),
            'retained_earnings' => self::rule('retained_earnings', 'holding', 'income', 'กำไรสะสม'),
        ];
    }

    /**
     * @return array<string,string>
     */
    private static function rule(string $ref, string $account, string $direction, string $description): array
    {
        return [
            'reference_type' => $ref,
            'account' => $account,
            'direction' => $direction,
            'description' => $description,
        ];
    }

    /**
     * @param list<array<string,mixed>> $findings
     * @param array{danger:int,warning:int} $counts
     */
    private static function addFinding(array &$findings, array &$counts, string $severity, array $tx, string $ref, string $message): void
    {
        if (!isset($counts[$severity])) {
            $counts[$severity] = 0;
        }
        $counts[$severity]++;
        $findings[] = [
            'severity' => $severity,
            'id' => (int)($tx['id'] ?? 0),
            'date' => (string)($tx['date'] ?? ''),
            'reference_type' => $ref,
            'transaction_type' => (string)($tx['type'] ?? ''),
            'amount' => (float)($tx['amount'] ?? 0),
            'description' => (string)($tx['description'] ?? ''),
            'message' => $message,
        ];
    }
}
