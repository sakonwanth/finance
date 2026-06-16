<?php
if (!defined('TP_FINANCE')) { http_response_code(403); exit('Forbidden'); }
$e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$map = AccountMappingService::rows();
$m = static fn ($n) => FinanceReadService::money($n);
?>
<p class="lead">Mapping บัญชี Finance Flow 4 ใบกับบัญชีธนาคาร/GL ใน tp-erp แบบ read-only เพื่อใช้เป็นฐานของ Flow Audit</p>

<?php if (!$map['available']): ?>
    <div class="callout warnbox">
        ⚠️ ยังยืนยันบัญชีจริงจาก tp-erp ไม่ได้ — <?= $e($map['reason'] ?? 'ไม่พร้อม') ?><br>
        <span class="muted">ยังสามารถตั้งค่า mapping ผ่าน env ได้ แต่ยอดคงเหลือจะแสดงเมื่อ <code>TP_ERP_API_KEY</code> พร้อม</span>
    </div>
<?php endif; ?>

<section class="mapping-list">
    <?php foreach ($map['rows'] as $row): ?>
    <article class="mapping-card status-<?= $e($row['status']) ?>" style="--accent: <?= $e($row['color']) ?>">
        <div class="mapping-head">
            <div>
                <h2><?= $e($row['name']) ?></h2>
                <p><?= $e($row['purpose']) ?></p>
            </div>
            <span class="status-pill"><?= $row['status'] === 'ok' ? 'พร้อม' : 'ต้องตั้งค่า' ?></span>
        </div>
        <dl class="mapping-meta">
            <dt>Flow role</dt><dd><?= $e($row['flow_role']) ?></dd>
            <dt>ERP Bank</dt><dd><?= $row['bank_id'] ? '#' . (int)$row['bank_id'] : '—' ?><?= $row['bank_name'] ? ' · ' . $e($row['bank_name']) : '' ?></dd>
            <dt>ยอดบัญชี</dt><dd><?= $row['bank_balance'] === null ? '—' : $e($m($row['bank_balance'])) ?></dd>
            <dt>GL Code</dt><dd><?= $e($row['gl_code'] ?? '—') ?></dd>
            <dt>Allowed refs</dt><dd><?= $e(implode(', ', $row['allowed_refs'])) ?></dd>
            <dt>Env</dt><dd><?= $e($row['bank_env']) ?> / <?= $e($row['gl_env']) ?></dd>
        </dl>
        <?php if ($row['issues']): ?>
            <div class="mapping-issues"><?= $e(implode(' · ', $row['issues'])) ?></div>
        <?php endif; ?>
    </article>
    <?php endforeach; ?>
</section>

<div class="callout">
    <strong>ขอบเขต:</strong> หน้านี้เป็น control map เท่านั้น การสร้างบัญชีธนาคารจริง การผูก GL และการ post journal ยังต้องทำใน tp-erp
</div>
