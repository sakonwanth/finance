<?php
if (!defined('TP_FINANCE')) { http_response_code(403); exit('Forbidden'); }
$accounts = require BASE_PATH . '/views/data/accounts.php';
$e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<p class="lead">โครงสร้างบัญชี 4 ตัวของบริษัท และทางลัดเข้าแต่ละโมดูล — ตัวเลขการเงินจริง (รายรับ/กำไร) จะดึงจาก tp-erp/tp-crm ในเฟสถัดไป</p>

<section class="card-grid">
    <?php foreach ($accounts as $a): ?>
    <a class="acct-card" href="?page=account-structure" style="--accent: <?= $e($a['color']) ?>">
        <div class="acct-name"><?= $e($a['name']) ?></div>
        <div class="acct-purpose"><?= $e($a['purpose']) ?></div>
    </a>
    <?php endforeach; ?>
</section>

<h2 class="section-title">สรุปด่วน <span class="src-tag">ข้อมูลจาก tp-erp</span></h2>
<?php $snap = FinanceReadService::snapshot(); ?>
<?php if (!$snap['available']): ?>
    <div class="callout warnbox">
        ⚠️ ยังไม่ได้เชื่อมข้อมูลจริง — <?= $e($snap['reason'] ?? 'ไม่พร้อม') ?><br>
        <span class="muted">ตั้ง <code>TP_ERP_API_KEY</code> บนเซิร์ฟเวอร์เพื่อแสดงตัวเลขจาก tp-erp</span>
    </div>
<?php else: ?>
    <section class="quick-grid">
        <div class="quick-card">
            <div class="quick-label">เงินสดรวม (ทุกบัญชี)</div>
            <div class="quick-value"><?= $e(FinanceReadService::money($snap['cash_total'])) ?></div>
        </div>
        <div class="quick-card">
            <div class="quick-label">Gross Margin</div>
            <div class="quick-value"><?= $snap['gross_margin_pct'] === null ? '—' : $e($snap['gross_margin_pct']) . ' %' ?></div>
        </div>
    </section>
    <p class="note">เงินสดจากบัญชีจริงใน tp-erp · gross margin = เดือนปัจจุบัน · ดู P&L แยกหมวดที่หน้า “กำไร-ขาดทุน”
        <?php if (!empty($snap['period'])): ?>(<?= $e(($snap['period']['year'] ?? '') . '-' . ($snap['period']['month'] ?? '')) ?>)<?php endif; ?>
    </p>
<?php endif; ?>
