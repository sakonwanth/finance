<?php
if (!defined('TP_FINANCE')) { http_response_code(403); exit('Forbidden'); }
$e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$data = CrmReadService::casePnlSummaries(50);
$m = static fn ($n) => CrmReadService::money($n);
?>
<p class="lead">กำไร-ขาดทุนต่อเคสลูกค้าอนุมัติแล้ว อ่านจาก tp-crm ผ่าน CustomerCaseProfitService
    <span class="src-tag">ข้อมูลจาก tp-crm</span></p>

<?php if (!$data['available']): ?>
    <div class="callout warnbox">
        ⚠️ ยังไม่ได้เชื่อมข้อมูล Case P&L — <?= $e($data['reason'] ?? 'ไม่พร้อม') ?><br>
        <span class="muted">ออก CRM API key scope <code>finance.read</code> แล้วตั้ง <code>TP_CRM_API_KEY</code> ใน tp-finance</span>
    </div>
<?php elseif (!$data['items']): ?>
    <p class="muted">ยังไม่มีเคสอนุมัติที่มีข้อมูล P&L</p>
<?php else: ?>
    <?php
    $totalRevenue = array_sum(array_column($data['items'], 'revenue'));
    $totalCosts = array_sum(array_column($data['items'], 'costs'));
    $totalProfit = array_sum(array_column($data['items'], 'profit'));
    $gapCases = array_sum(array_map(static fn ($r) => ((int)$r['gap_count']) > 0 ? 1 : 0, $data['items']));
    ?>
    <section class="pl-summary three">
        <div class="pl-stat"><span>รายได้รวม</span><b><?= $e($m($totalRevenue)) ?></b></div>
        <div class="pl-stat"><span>ต้นทุนรวม</span><b><?= $e($m($totalCosts)) ?></b></div>
        <div class="pl-stat"><span>กำไรรวม</span><b class="<?= $totalProfit >= 0 ? 'pos' : 'neg' ?>"><?= $e($m($totalProfit)) ?></b></div>
    </section>
    <p class="note muted"><?= (int)$data['count'] ?> เคส · <?= (int)$gapCases ?> เคสมี gap · source: <?= $e($data['source']) ?></p>

    <section class="case-pnl-list">
        <?php foreach ($data['items'] as $row): ?>
        <article class="case-pnl-card">
            <div class="case-pnl-head">
                <div>
                    <h2><?= $e($row['customer_code'] ?: ('#' . $row['customer_id'])) ?></h2>
                    <p><?= $e($row['name'] ?: 'ไม่ระบุชื่อ') ?></p>
                </div>
                <span class="gap-pill <?= $row['gap_count'] > 0 ? 'has-gap' : '' ?>"><?= (int)$row['gap_count'] ?> gap</span>
            </div>
            <div class="case-pnl-nums">
                <span>รายได้ <b><?= $e($m($row['revenue'])) ?></b></span>
                <span>ต้นทุน <b><?= $e($m($row['costs'])) ?></b></span>
                <span>กำไร <b class="<?= $row['profit'] >= 0 ? 'pos' : 'neg' ?>"><?= $e($m($row['profit'])) ?></b></span>
                <span>Margin <b><?= $row['margin_pct'] === null ? '—' : $e($row['margin_pct']) . ' %' ?></b></span>
            </div>
        </article>
        <?php endforeach; ?>
    </section>
    <p class="note">ตัวเลขมาจาก tp-crm CustomerCaseProfitService ซึ่งรวม deal received, project revenue, ledger income, deal costs, debt closure, renovation, BOQ และ acquisition ตามสูตรกลาง DealPnl</p>
<?php endif; ?>
