<?php
if (!defined('TP_FINANCE')) { http_response_code(403); exit('Forbidden'); }
$e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$rules = [
    ['เงินเข้าจากลูกค้า', 'TP-ASSET INCOME', 'รายรับจากลูกค้าเข้าบัญชี INCOME เสมอ'],
    ['เงินกู้สำหรับโปรเจกต์', 'TP-ASSET PROJECT', 'เงินกู้ที่ใช้กับโปรเจกต์เข้าบัญชี PROJECT'],
    ['จ่ายค่าจอง', 'TP-ASSET PROJECT', 'ค่าจองเป็นต้นทุนโครงการ'],
    ['จ่ายค่าซื้อทรัพย์', 'TP-ASSET PROJECT', 'ต้นทุนการได้มาของทรัพย์'],
    ['จ่ายค่ารีโนเวท', 'TP-ASSET PROJECT', 'ต้นทุนปรับปรุงทรัพย์'],
    ['จ่ายเงินเดือน/ค่าใช้จ่ายบริษัท', 'TP-ASSET EXPENSE', 'ค่าใช้จ่ายดำเนินงานบริษัท'],
    ['ปิดดีลแล้ว โอนกำไร', 'TP-ASSET HOLDING', 'กำไรสุทธิย้ายไปเก็บที่ HOLDING'],
];
?>
<p class="lead">เงินแต่ละประเภทควรเข้า/ออกบัญชีไหน</p>
<section class="guide-list">
    <?php foreach ($rules as [$situation, $acct, $why]): ?>
    <div class="guide-row">
        <div class="guide-when"><?= $e($situation) ?></div>
        <div class="guide-arrow">→</div>
        <div class="guide-acct"><?= $e($acct) ?></div>
        <div class="guide-why"><?= $e($why) ?></div>
    </div>
    <?php endforeach; ?>
</section>
<div class="callout">
    <strong>ข้อควรจำ:</strong> เงินที่ผ่านไปปิดจำนอง/จ่ายผู้ขายโดยตรง อาจ<em>ไม่ใช่</em>รายได้ของบริษัท —
    รายได้จริงดูจาก “ส่วนต่างที่บริษัทได้รับจริง”
</div>
