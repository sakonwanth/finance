<?php
if (!defined('TP_FINANCE')) { http_response_code(403); exit('Forbidden'); }
$e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$snap = FinanceReadService::snapshot();
?>
<p class="lead">สรุปกำไร-ขาดทุน (read-only จาก tp-erp) — รายรับ/รายจ่ายแยกหมวด posted ของเดือนปัจจุบัน
    <span class="src-tag">ข้อมูลจาก tp-erp</span></p>

<?php if (!$snap['available']): ?>
    <div class="callout warnbox">
        ⚠️ ยังไม่ได้เชื่อมข้อมูลจริง — <?= $e($snap['reason'] ?? 'ไม่พร้อม') ?><br>
        <span class="muted">ตั้ง <code>TP_ERP_API_KEY</code> บนเซิร์ฟเวอร์</span>
    </div>
<?php else: ?>
    <section class="pl-summary">
        <div class="pl-stat"><span>เงินสดรวม</span><b><?= $e(FinanceReadService::money($snap['cash_total'])) ?></b></div>
        <div class="pl-stat"><span>Gross Margin</span><b><?= $snap['gross_margin_pct'] === null ? '—' : $e($snap['gross_margin_pct']) . ' %' ?></b></div>
    </section>

    <h2 class="section-title">รายรับแยกหมวด (Top)</h2>
    <?php if (!$snap['revenue_categories']): ?>
        <p class="muted">ไม่มีรายการ posted ในงวดนี้</p>
    <?php else: ?>
    <section class="pl-list">
        <?php foreach ($snap['revenue_categories'] as $c): ?>
        <div class="pl-row"><span><?= $e($c['name']) ?></span><b class="pos"><?= $e(FinanceReadService::money($c['amount'])) ?></b></div>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <h2 class="section-title">รายจ่ายแยกหมวด (Top)</h2>
    <?php if (!$snap['expense_categories']): ?>
        <p class="muted">ไม่มีรายการ posted ในงวดนี้</p>
    <?php else: ?>
    <section class="pl-list">
        <?php foreach ($snap['expense_categories'] as $c): ?>
        <div class="pl-row"><span><?= $e($c['name']) ?></span><b class="neg"><?= $e(FinanceReadService::money($c['amount'])) ?></b></div>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <p class="note">หมายเหตุ: เป็นยอด posted เดือนปัจจุบัน (top หมวด) — ยอดรวมเต็ม/งบกำไรขาดทุนทางการดูที่ tp-erp income statement · ตัวเลขทั้งหมดมาจาก tp-erp (single source) tp-finance ไม่เก็บเอง</p>
<?php endif; ?>
