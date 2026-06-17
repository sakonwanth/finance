<?php
if (!defined('TP_FINANCE')) { http_response_code(403); exit('Forbidden'); }
$accounts = require BASE_PATH . '/views/data/accounts.php';
$e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<section class="dashboard-hero">
    <div class="hero-panel">
        <div class="hero-kicker"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Finance control layer</div>
        <h2>ควบคุม Flow เงินของบริษัทจากแหล่งข้อมูลเดียว</h2>
        <p>โครงสร้างบัญชี 4 ใบ, mapping กับ ERP, audit policy และมุมมองกำไรขาดทุนอยู่ในหน้าระบบเดียว โดย tp-finance อ่านข้อมูลจริงจาก tp-erp/tp-crm และไม่สร้างตัวเลขการเงินซ้ำเอง</p>
        <div class="hero-actions">
            <a class="btn primary" href="?page=flow-audit"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> ตรวจ Flow Audit</a>
            <a class="btn" href="?page=calculator"><i class="fa-solid fa-calculator" aria-hidden="true"></i> คำนวณดีล</a>
            <a class="btn" href="?page=profit-loss"><i class="fa-solid fa-chart-pie" aria-hidden="true"></i> ดู P&L</a>
        </div>
    </div>
    <aside class="module-panel">
        <h2>งานที่ใช้บ่อย</h2>
        <div class="module-list">
            <a class="module-link" href="?page=account-mapping">
                <i class="fa-solid fa-code-branch" aria-hidden="true"></i>
                <span>บัญชี ERP <small>ตรวจ bank account และ GL mapping</small></span>
            </a>
            <a class="module-link" href="?page=project-tracker">
                <i class="fa-solid fa-diagram-project" aria-hidden="true"></i>
                <span>รายโปรเจกต์ <small>รายรับ ต้นทุน และกำไรต่อโปรเจกต์</small></span>
            </a>
            <a class="module-link" href="?page=case-pnl">
                <i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i>
                <span>กำไรต่อเคส <small>อ่านจาก CustomerCaseProfitService</small></span>
            </a>
        </div>
    </aside>
</section>

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
