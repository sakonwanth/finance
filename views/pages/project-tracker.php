<?php
if (!defined('TP_FINANCE')) { http_response_code(403); exit('Forbidden'); }
$e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$pp = FinanceReadService::projectPnl();
$m  = static fn ($n) => FinanceReadService::money($n);
?>
<p class="lead">กำไร-ขาดทุนรายโปรเจกต์ (read-only จาก tp-erp) — รายรับ/ต้นทุน/กำไรต่อโปรเจกต์
    <span class="src-tag">ข้อมูลจาก tp-erp</span></p>

<?php if (!$pp['available']): ?>
    <div class="callout warnbox">
        ⚠️ ยังไม่ได้เชื่อมข้อมูลจริง — <?= $e($pp['reason'] ?? 'ไม่พร้อม') ?><br>
        <span class="muted">ตั้ง <code>TP_ERP_API_KEY</code> บนเซิร์ฟเวอร์</span>
    </div>
<?php elseif (!$pp['projects']): ?>
    <p class="muted">ไม่มีข้อมูลโปรเจกต์ในปี <?= $e($pp['year']) ?></p>
<?php else: ?>
    <?php $t = $pp['totals']; ?>
    <section class="pl-summary three">
        <div class="pl-stat"><span>รายรับรวม</span><b><?= $e($m($t['revenue'])) ?></b></div>
        <div class="pl-stat"><span>ต้นทุนรวม</span><b><?= $e($m($t['cost'])) ?></b></div>
        <div class="pl-stat"><span>กำไรรวม</span><b class="<?= $t['profit'] >= 0 ? 'pos' : 'neg' ?>"><?= $e($m($t['profit'])) ?></b></div>
    </section>
    <p class="note muted"><?= (int)$t['count'] ?> โปรเจกต์ · ปี <?= $e($pp['year']) ?></p>

    <section class="proj-list">
        <?php foreach ($pp['projects'] as $p): ?>
        <div class="proj-card">
            <div class="proj-head">
                <span class="proj-name"><?= $e($p['name']) ?><?php if ($p['code']): ?> <small class="muted">(<?= $e($p['code']) ?>)</small><?php endif; ?></span>
                <?php if ($p['status']): ?><span class="proj-status"><?= $e($p['status']) ?></span><?php endif; ?>
            </div>
            <div class="proj-nums">
                <span>รายรับ <b><?= $e($m($p['revenue'])) ?></b></span>
                <span>ต้นทุน <b><?= $e($m($p['cost'])) ?></b></span>
                <span>กำไร <b class="<?= $p['profit'] >= 0 ? 'pos' : 'neg' ?>"><?= $e($m($p['profit'])) ?></b></span>
            </div>
        </div>
        <?php endforeach; ?>
    </section>
    <p class="note">ตัวเลขทั้งหมดมาจาก tp-erp (project-pl: revenue จาก AR/projects, cost จาก expenses+PO+timesheets) — tp-finance ไม่คำนวณ/เก็บเอง (single source)</p>
<?php endif; ?>
