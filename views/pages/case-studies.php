<?php
if (!defined('TP_FINANCE')) { http_response_code(403); exit('Forbidden'); }
$e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$cases = [
    ['A', 'ซื้อบ้านจริง + รีโนเวท + ขาย', 'ลูกค้าโอนเข้า INCOME → จัดสรรเข้า PROJECT เพื่อซื้อ+รีโนเวท → ขายได้ → กำไรสุทธิเข้า HOLDING'],
    ['B', 'ใช้เงินกู้ 100% ทั้งโครงการ', 'เงินกู้เข้า PROJECT → จ่ายต้นทุนทั้งหมดจาก PROJECT → รายได้หักต้นทุน+ดอกเบี้ย → กำไรเข้า HOLDING'],
    ['C', 'Flip + ค่าจอง + รีโนเวท', 'ค่าจอง/รีโนเวทจาก PROJECT → ขายต่อ → รายได้จริงคือส่วนต่าง → กำไรเข้า HOLDING'],
    ['D', 'Same-day transfer / เช็คหลายใบ', 'ธนาคารผู้ซื้อจ่ายตรงปิดจำนองเดิม/ผู้ขาย — บริษัทได้เฉพาะ “ส่วนต่างจริง” เข้า INCOME (เงินผ่านไม่ใช่รายได้)'],
];
?>
<p class="lead">ตัวอย่าง Flow เงิน 4 เคส (รายละเอียดตัวเลข + P&L จริงจะเชื่อมกับ Deal Calculator/erp ในเฟสถัดไป)</p>
<section class="case-list">
    <?php foreach ($cases as [$id, $title, $flow]): ?>
    <details class="case">
        <summary><span class="case-id">Case <?= $e($id) ?></span> <?= $e($title) ?></summary>
        <div class="case-flow"><?= $e($flow) ?></div>
    </details>
    <?php endforeach; ?>
</section>
