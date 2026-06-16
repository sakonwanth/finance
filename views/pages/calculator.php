<?php
if (!defined('TP_FINANCE')) { http_response_code(403); exit('Forbidden'); }
$e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
// What-if scratchpad — NOT persisted, NOT an authoritative financial record (see DESIGN.md).
// Data-driven P&L (real deals) จะใช้ DealPnl/erp ใน P3.
$fields = [
    ['sale',     'ราคาขายลูกค้า',          'รายได้จากการขายให้ลูกค้า'],
    ['seller',   'ราคาผู้ขาย / ต้นทุนได้มา', 'ราคาที่จ่ายเพื่อได้ทรัพย์มา'],
    ['mortgage', 'หนี้จำนองเดิม',          'อาจจ่ายตรงปิดจำนอง = เงินผ่าน ไม่ใช่รายได้บริษัท'],
    ['booking',  'ค่าจอง',                'ต้นทุนโครงการ'],
    ['reno',     'ค่ารีโนเวท',             'ต้นทุนปรับปรุงทรัพย์'],
    ['expense',  'ค่าใช้จ่ายบริษัท',        'ส่วนแบ่งค่าดำเนินงาน'],
    ['interest', 'ดอกเบี้ย / ค่าใช้จ่ายการเงิน', 'ถ้าใช้เงินกู้'],
];
?>
<p class="lead">กรอกตัวเลขดีลเพื่อประเมินกำไร-ขาดทุนแบบ what-if (คำนวณทันที) — เป็นเครื่องมือช่วยตัดสินใจ ไม่ใช่บันทึกบัญชีจริง</p>

<form id="calcForm" class="calc-form" autocomplete="off">
    <?php foreach ($fields as [$key, $label, $hint]): ?>
    <label class="calc-field">
        <span class="calc-label"><?= $e($label) ?></span>
        <input type="number" inputmode="decimal" step="any" min="0" name="<?= $e($key) ?>" data-k="<?= $e($key) ?>" placeholder="0">
        <span class="calc-hint"><?= $e($hint) ?></span>
    </label>
    <?php endforeach; ?>
</form>

<section class="calc-result" id="calcResult" aria-live="polite">
    <div class="res-row"><span>Gross Margin (ขาย − ผู้ขาย)</span><b data-r="gross">—</b></div>
    <div class="res-row"><span>เงินที่บริษัทได้รับจริง (ขาย − จำนอง)</span><b data-r="realrev">—</b></div>
    <div class="res-row"><span>ต้นทุนรวม</span><b data-r="cost">—</b></div>
    <div class="res-row total"><span>กำไร/ขาดทุนสุทธิ</span><b data-r="net">—</b></div>
    <div class="res-row"><span>Profit Margin %</span><b data-r="margin">—</b></div>
    <div class="res-advice" data-r="advice"></div>
</section>

<script>
(function(){
    var form = document.getElementById('calcForm');
    var R = document.getElementById('calcResult');
    var fmt = function(n){ return (isFinite(n)?n:0).toLocaleString('th-TH',{minimumFractionDigits:2,maximumFractionDigits:2}); };
    var set = function(k,v){ R.querySelector('[data-r="'+k+'"]').textContent = v; };
    function val(k){ var el=form.querySelector('[data-k="'+k+'"]'); return parseFloat(el && el.value)||0; }
    function payload(){
        return {
            sale: val('sale'), seller: val('seller'), mortgage: val('mortgage'),
            booking: val('booking'), reno: val('reno'), expense: val('expense'), interest: val('interest')
        };
    }
    function paint(d){
        set('gross', fmt(d.gross_margin) + ' ฿');
        set('realrev', fmt(d.real_revenue) + ' ฿');
        set('cost', fmt(d.total_cost) + ' ฿');
        set('net', (d.net_profit<0?'−':'') + fmt(Math.abs(d.net_profit)) + ' ฿');
        set('margin', d.profit_margin_pct===null ? '—' : d.profit_margin_pct + ' %');
        var nb = R.querySelector('[data-r="net"]');
        nb.style.color = d.net_profit>0 ? '#16a34a' : (d.net_profit<0 ? '#dc2626' : 'inherit');
        var advice = R.querySelector('[data-r="advice"]');
        advice.textContent = d.advice || '';
        advice.className = 'res-advice ' + (d.severity ? 'advice-' + d.severity : '');
    }
    function recalc(){
        fetch('api/calculate.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json', 'Accept':'application/json'},
            body: JSON.stringify(payload())
        }).then(function(res){ return res.json(); }).then(function(json){
            if (json && json.success && json.data) paint(json.data);
        }).catch(function(){
            set('gross', '—'); set('realrev', '—'); set('cost', '—'); set('net', '—'); set('margin', '—');
            R.querySelector('[data-r="advice"]').textContent = 'เชื่อม API คำนวณไม่ได้';
        });
    }
    var timer;
    form.addEventListener('input', function(){
        clearTimeout(timer);
        timer = setTimeout(recalc, 120);
    });
    recalc();
})();
</script>
