<?php
if (!defined('TP_FINANCE')) { http_response_code(403); exit('Forbidden'); }
$e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$from = isset($_GET['from']) ? (string)$_GET['from'] : null;
$to = isset($_GET['to']) ? (string)$_GET['to'] : null;
$report = FlowAuditService::report($from, $to);
$m = static fn ($n) => FinanceReadService::money((float)$n);
$labels = ['ok' => 'พร้อม', 'warning' => 'ต้องตรวจ', 'danger' => 'ผิดกฎ'];
?>
<p class="lead">ตรวจความพร้อมและรายการ ERP ledger ว่า reference type ควรไหลเข้าบัญชี Finance Flow ใด หน้า audit นี้อ่านอย่างเดียวและไม่แก้รายการแทน ERP</p>

<form class="audit-filter" method="get">
    <input type="hidden" name="page" value="flow-audit">
    <label>
        <span>จากวันที่</span>
        <input type="date" name="from" value="<?= $e($report['from']) ?>">
    </label>
    <label>
        <span>ถึงวันที่</span>
        <input type="date" name="to" value="<?= $e($report['to']) ?>">
    </label>
    <button type="submit">ตรวจ</button>
</form>

<?php if (empty($report['ledger']['available'])): ?>
    <div class="callout warnbox">
        ⚠️ ยังอ่าน ERP ledger ไม่ได้ — <?= $e($report['ledger']['reason'] ?? 'ไม่พร้อม') ?><br>
        <span class="muted">ตั้ง <code>TP_ERP_API_KEY</code> และ <code>TP_ERP_API_URL</code> ให้ชี้ไปที่ tp-erp API v1 เพื่อเปิดการตรวจรายการจริง</span>
    </div>
<?php endif; ?>

<section class="audit-summary">
    <div class="pl-stat"><span>ตรวจแล้ว</span><b><?= (int)$report['totals']['scanned'] ?></b></div>
    <div class="pl-stat"><span>ผิดกฎ</span><b class="danger-text"><?= (int)$report['totals']['danger'] ?></b></div>
    <div class="pl-stat"><span>ต้องตรวจ</span><b class="warn-text"><?= (int)$report['totals']['warning'] ?></b></div>
    <div class="pl-stat"><span>ตรวจ placement ไม่ได้</span><b><?= (int)$report['totals']['blind_placement'] ?></b></div>
</section>

<h2 class="section-title">Control Checks</h2>
<section class="audit-checks">
    <?php foreach ($report['checks'] as $check): ?>
    <article class="audit-check status-<?= $e($check['status']) ?>">
        <div>
            <strong><?= $e($check['name']) ?></strong>
            <p><?= $e($check['detail']) ?></p>
        </div>
        <span class="status-pill"><?= $e($labels[$check['status']] ?? $check['status']) ?></span>
    </article>
    <?php endforeach; ?>
</section>

<h2 class="section-title">Reference Breakdown</h2>
<?php if (!$report['breakdown']): ?>
    <div class="callout">ยังไม่มีรายการ ledger ในช่วงวันที่นี้ หรือยังเชื่อม ERP ไม่พร้อม</div>
<?php else: ?>
    <section class="audit-table">
        <?php foreach ($report['breakdown'] as $row): ?>
        <div class="audit-row">
            <div>
                <strong><?= $e($row['reference_type']) ?></strong>
                <span><?= $e($row['transaction_type']) ?><?= $row['finance_account'] ? ' · ' . $e($row['finance_account']) : '' ?></span>
            </div>
            <div class="audit-num">
                <b><?= (int)$row['count'] ?></b>
                <span><?= $e($m($row['total'])) ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<h2 class="section-title">Findings</h2>
<?php if (!$report['findings']): ?>
    <div class="callout">ยังไม่พบรายการผิด policy ในช่วงวันที่นี้</div>
<?php else: ?>
    <section class="audit-findings">
        <?php foreach ($report['findings'] as $f): ?>
        <article class="audit-finding status-<?= $e($f['severity']) ?>">
            <div class="audit-finding-head">
                <strong>#<?= (int)$f['id'] ?> · <?= $e($f['reference_type']) ?></strong>
                <span class="status-pill"><?= $e($labels[$f['severity']] ?? $f['severity']) ?></span>
            </div>
            <p><?= $e($f['message']) ?></p>
            <div class="audit-finding-meta">
                <?= $e($f['date']) ?> · <?= $e($f['transaction_type']) ?> · <?= $e($m($f['amount'])) ?>
                <?php if ($f['description'] !== ''): ?> · <?= $e($f['description']) ?><?php endif; ?>
            </div>
        </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<div class="callout">
    <strong>ช่องว่างที่ต้องปิดต่อใน ERP:</strong> หาก check ยังเตือนเรื่อง schema ให้รัน
    <code>php scripts/run_finance_flow_bank_link.php</code> ที่ tp-erp จากนั้น dry-run
    <code>php scripts/backfill_finance_flow_bank_links.php</code> ก่อน apply รายการเก่า แล้วค่อยให้ write path ของ ERP/CRM ส่ง <code>bank_account_id</code>
    เพื่อให้ Flow Audit ตรวจ placement ระหว่าง INCOME / PROJECT / EXPENSE / HOLDING ได้ครบ
</div>
