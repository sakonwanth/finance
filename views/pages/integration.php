<?php
if (!defined('TP_FINANCE')) { http_response_code(403); exit('Forbidden'); }
$e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$rows = IntegrationStatusService::all();
$labels = ['ok' => 'พร้อม', 'warning' => 'ต้องตั้งค่า', 'error' => 'มีปัญหา'];
?>
<p class="lead">สถานะการเชื่อมระบบแบบ read-only — tp-finance ใช้หน้านี้ตรวจว่าแหล่งข้อมูลหลักพร้อมก่อนแสดงตัวเลขจาก CRM / ERP / HR / CHECKIN / COMMON</p>

<section class="integration-list">
    <?php foreach ($rows as $r): ?>
    <div class="integration-card status-<?= $e($r['status']) ?>">
        <div class="integration-head">
            <div>
                <h2><?= $e($r['name']) ?></h2>
                <p><?= $e($r['role']) ?></p>
            </div>
            <span class="status-pill"><?= $e($labels[$r['status']] ?? $r['status']) ?></span>
        </div>
        <div class="integration-detail"><?= $e($r['detail']) ?></div>
        <dl class="integration-meta">
            <dt>Owner</dt><dd><?= $e($r['owner']) ?></dd>
            <?php if (!empty($r['endpoint'])): ?><dt>Endpoint</dt><dd><?= $e($r['endpoint']) ?></dd><?php endif; ?>
        </dl>
    </div>
    <?php endforeach; ?>
</section>

<div class="callout">
    <strong>กฎการพัฒนา:</strong> หน้านี้ตรวจสถานะเท่านั้น ไม่เขียนข้อมูลกลับไป ERP/CRM/HR/CHECKIN
    หากพบ warning ให้ตั้งค่า API key หรือ endpoint ที่ระบบเจ้าของข้อมูลก่อน ไม่สร้างทางลัดเขียน DB จาก tp-finance
</div>
