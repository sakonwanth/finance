<?php
/**
 * Owned config: the 4-account structure (tp-finance OWNS this explanatory content).
 * Moves to a `finance_accounts` table in a later build phase (see DESIGN.md).
 */
if (!defined('TP_FINANCE')) { http_response_code(403); exit('Forbidden'); }
return [
    [
        'key' => 'income', 'name' => 'TP-ASSET INCOME', 'color' => '#16a34a',
        'purpose' => 'รับเงินลูกค้า / เป็นทางผ่านของรายรับจากลูกค้า',
        'in'  => 'เงินที่ลูกค้าโอนเข้า',
        'out' => 'ไม่ควรใช้เป็นบัญชีจ่าย',
        'warn'=> 'อย่าใช้บัญชีนี้จ่ายต้นทุน/ค่าใช้จ่าย',
        'erp_bank_env' => 'FINANCE_INCOME_BANK_ACCOUNT_ID',
        'gl_env' => 'FINANCE_INCOME_GL_ACCOUNT_CODE',
        'allowed_refs' => ['deal', 'customer_payment', 'ledger_income'],
        'flow_role' => 'customer_money_in',
    ],
    [
        'key' => 'project', 'name' => 'TP-ASSET PROJECT', 'color' => '#2563eb',
        'purpose' => 'บัญชีกลางของแต่ละโปรเจกต์ / รับเงินกู้ที่ใช้กับโปรเจกต์',
        'in'  => 'เงินกู้โครงการ, เงินโอนจาก INCOME ที่จัดสรรเข้าโปรเจกต์',
        'out' => 'ค่าจอง ค่าซื้อทรัพย์ ค่ารีโนเวท ต้นทุนโครงการ',
        'warn'=> 'แยกต่อโปรเจกต์ให้ชัด ไม่ปนค่าใช้จ่ายบริษัท',
        'erp_bank_env' => 'FINANCE_PROJECT_BANK_ACCOUNT_ID',
        'gl_env' => 'FINANCE_PROJECT_GL_ACCOUNT_CODE',
        'allowed_refs' => ['loan_drawdown', 'property', 'property_cost', 'debt_closure', 'renovation', 'deal_cost'],
        'flow_role' => 'project_cost_center',
    ],
    [
        'key' => 'expense', 'name' => 'TP-ASSET EXPENSE', 'color' => '#d97706',
        'purpose' => 'ค่าใช้จ่ายบริษัท',
        'in'  => 'เงินจัดสรรสำหรับค่าใช้จ่ายดำเนินงาน',
        'out' => 'เงินเดือน ค่าโฆษณา ค่าแอดมิน ค่าสำนักงาน ค่าระบบ',
        'warn'=> 'ไม่ปนกับต้นทุนโครงการ (PROJECT)',
        'erp_bank_env' => 'FINANCE_EXPENSE_BANK_ACCOUNT_ID',
        'gl_env' => 'FINANCE_EXPENSE_GL_ACCOUNT_CODE',
        'allowed_refs' => ['manual', 'payroll', 'line_bot', 'line_expense', 'company_expense'],
        'flow_role' => 'company_operating_expense',
    ],
    [
        'key' => 'holding', 'name' => 'TP-ASSET HOLDING', 'color' => '#7c3aed',
        'purpose' => 'เก็บกำไรที่สรุปแล้ว / เงินสะสม-กำไรสุทธิ',
        'in'  => 'กำไรสุทธิหลังหักต้นทุนและค่าใช้จ่าย',
        'out' => 'ไม่ควรใช้จ่ายปะปนกับบัญชีอื่น',
        'warn'=> 'ใช้เก็บกำไรเท่านั้น',
        'erp_bank_env' => 'FINANCE_HOLDING_BANK_ACCOUNT_ID',
        'gl_env' => 'FINANCE_HOLDING_GL_ACCOUNT_CODE',
        'allowed_refs' => ['profit_transfer', 'retained_earnings'],
        'flow_role' => 'closed_profit_storage',
    ],
];
