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

<h2 class="section-title">สรุปด่วน</h2>
<section class="quick-grid">
    <?php foreach (['รับเงินลูกค้า','เงินกู้','ค่าใช้จ่ายบริษัท','กำไรสุทธิ'] as $q): ?>
    <div class="quick-card">
        <div class="quick-label"><?= $e($q) ?></div>
        <div class="quick-value muted">— <span class="pill">เฟส 3</span></div>
    </div>
    <?php endforeach; ?>
</section>
<p class="note">หมายเหตุ: ตัวเลขสรุปด่วนเป็น read-only จาก tp-erp reports + tp-crm case P&L (ยังไม่เชื่อมในเฟสนี้)</p>
