<?php
if (!defined('TP_FINANCE')) { http_response_code(403); exit('Forbidden'); }
$accounts = require BASE_PATH . '/views/data/accounts.php';
$e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<p class="lead">บัญชีหลัก 4 บัญชี — วัตถุประสงค์ ตัวอย่างเงินเข้า/ออก และข้อควรระวัง</p>

<?php foreach ($accounts as $a): ?>
<section class="detail-card" style="--accent: <?= $e($a['color']) ?>">
    <h2 class="detail-name"><?= $e($a['name']) ?></h2>
    <dl class="detail-list">
        <dt>วัตถุประสงค์</dt><dd><?= $e($a['purpose']) ?></dd>
        <dt>เงินเข้า</dt><dd><?= $e($a['in']) ?></dd>
        <dt>เงินออก</dt><dd><?= $e($a['out']) ?></dd>
        <dt>ข้อควรระวัง</dt><dd class="warn"><?= $e($a['warn']) ?></dd>
    </dl>
</section>
<?php endforeach; ?>
