<?php /* panels/panel-dash-yearly.php — 현황 요약 (연도별) */ ?>
<div class="panel" id="p-dash-yearly">
  <div class="card">
    <div class="card-hd">
      <span>📅 <span id="yearly-title">연도별 현황 요약</span></span>
      <div style="display:flex;align-items:center;gap:8px;position:relative">
        <style>
          #yd-list { display:none; position:absolute; top:calc(100% + 4px); right:0; border:2px solid #1a56db; border-radius:8px; box-shadow:0 6px 20px rgba(0,0,0,.3); z-index:9999; min-width:110px; overflow:hidden; background:#fff; }
          .yd-item { padding:10px 20px !important; font-size:14px !important; font-weight:800 !important; color:#111 !important; background:#fff !important; cursor:pointer !important; white-space:nowrap !important; border-bottom:1px solid #e5e7eb !important; display:block !important; line-height:1.4 !important; }
          .yd-item:hover { background:#dbeafe !important; color:#1a56db !important; }
          .yd-item:last-child { border-bottom:none !important; }
        </style>
        <div id="yd-wrap" style="position:relative">
          <button id="yd-btn" onclick="toggleYearDrop()"
            style="background:#1a56db;color:#fff;border:none;padding:6px 14px;border-radius:8px;font-size:12px;font-weight:700;font-family:inherit;cursor:pointer;display:flex;align-items:center;gap:6px;white-space:nowrap">
            <span id="yd-label"><?php echo date('Y'); ?>년</span>
            <span style="font-size:10px">▼</span>
          </button>
          <div id="yd-list"></div>
        </div>
        <button class="btn bp" onclick="loadYearly()">📊 조회</button>
      </div>
    </div>

    <!-- 연간 KPI -->
    <div class="stat-g" id="yearly-kpi" style="margin-bottom:20px">
      <div class="stat"><div class="stat-lbl">연간 총 매출</div><div class="stat-val mono" id="yk-amt" style="color:var(--amber)">—</div><div class="stat-sub">선택 연도</div></div>
      <div class="stat"><div class="stat-lbl">연간 총 PV</div><div class="stat-val mono" id="yk-pv" style="color:var(--green)">—</div><div class="stat-sub">선택 연도</div></div>
      <div class="stat"><div class="stat-lbl">연간 예상 수당</div><div class="stat-val" id="yk-pay" style="color:var(--purple)">—</div><div class="stat-sub">월별 합산</div></div>
      <div class="stat"><div class="stat-lbl">활성 회원</div><div class="stat-val" id="yk-mem" style="color:var(--blue)">—</div><div class="stat-sub">PV 발생 회원</div></div>
    </div>

    <!-- 연간 비율 게이지 -->
    <div id="yearly-ratio-section" style="display:none;margin-bottom:20px">
      <div style="font-size:12px;font-weight:700;color:var(--t1);margin-bottom:10px">📊 연간 수당 지급률</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
        <div style="background:var(--s2);border-radius:10px;padding:14px 16px">
          <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:8px">
            <span style="font-size:11px;font-weight:700;color:var(--t2)">💳 매출 대비 총 수당</span>
            <span id="yk-ratio-amt-pct" style="font-size:22px;font-weight:900;color:var(--purple);font-family:'JetBrains Mono',monospace">—%</span>
          </div>
          <div style="height:10px;background:var(--s3);border-radius:5px;overflow:hidden;margin-bottom:6px">
            <div id="yk-ratio-amt-bar" style="height:100%;width:0%;border-radius:5px;transition:width .6s;background:linear-gradient(90deg,var(--purple),#ce93d8)"></div>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--t3)">
            <span>매출 <b id="yk-ratio-amt-s" style="color:var(--t2)">—</b></span>
            <span>수당 <b id="yk-ratio-amt-p" style="color:var(--purple)">—</b></span>
          </div>
        </div>
        <div style="background:var(--s2);border-radius:10px;padding:14px 16px">
          <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:8px">
            <span style="font-size:11px;font-weight:700;color:var(--t2)">📦 PV 대비 총 수당</span>
            <span id="yk-ratio-pv-pct" style="font-size:22px;font-weight:900;color:var(--teal);font-family:'JetBrains Mono',monospace">—%</span>
          </div>
          <div style="height:10px;background:var(--s3);border-radius:5px;overflow:hidden;margin-bottom:6px">
            <div id="yk-ratio-pv-bar" style="height:100%;width:0%;border-radius:5px;transition:width .6s;background:linear-gradient(90deg,var(--teal),#80cbc4)"></div>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--t3)">
            <span>PV <b id="yk-ratio-pv-p" style="color:var(--t2)">—</b></span>
            <span>수당 <b id="yk-ratio-pv-s" style="color:var(--teal)">—</b></span>
          </div>
        </div>
      </div>
    </div>

    <!-- 월별 상세 테이블 (비율 컬럼 추가) -->
    <div class="card-hd" style="margin-top:8px">📋 월별 상세 내역</div>
    <div class="tw" id="yearly-tbl">
      <div class="empty-msg">연도를 선택하고 [조회] 버튼을 누르세요.</div>
    </div>
  </div>

  <!-- 수당 유형별 연간 합계 + 비율 -->
  <div class="card">
    <div class="card-hd">💰 수당 유형별 연간 합계 및 비율</div>
    <div id="yearly-comm-breakdown"><div class="empty-msg">조회 후 표시됩니다.</div></div>
  </div>
</div>

<script>
let _ydSelected = '<?php echo date('Y'); ?>';

(function buildYearList() {
  const list = document.getElementById('yd-list');
  if (!list) return;
  const curYear = new Date().getFullYear();
  for (let y = curYear; y >= curYear - 4; y--) {
    const el = document.createElement('div');
    el.className = 'yd-item';
    el.textContent = y + '년';
    el.setAttribute('data-year', y);
    el.addEventListener('click', function() { selectYear(parseInt(this.getAttribute('data-year'))); });
    list.appendChild(el);
  }
})();

function toggleYearDrop() {
  const list = document.getElementById('yd-list');
  list.style.display = list.style.display === 'none' ? 'block' : 'none';
}
function selectYear(y) {
  _ydSelected = String(y);
  document.getElementById('yd-label').textContent = y + '년';
  document.getElementById('yd-list').style.display = 'none';
  document.getElementById('yearly-title').textContent = y + '년 연도별 현황 요약';
}
document.addEventListener('click', function(e) {
  const wrap = document.getElementById('yd-wrap');
  if (wrap && !wrap.contains(e.target)) document.getElementById('yd-list').style.display = 'none';
});

async function loadYearly() {
  const year = _ydSelected;
  const titleEl = document.getElementById('yearly-title');
  if (titleEl) titleEl.textContent = year + '년 연도별 현황 요약';
  $('yearly-tbl').innerHTML = '<div class="spin"></div>';
  $('yearly-comm-breakdown').innerHTML = '<div class="spin"></div>';
  $('yearly-ratio-section').style.display = 'none';

  if (S._salesYear && S._salesYear !== year) { S.loaded.sales = false; S.sales = []; }
  await ensureData(year + '-01');

  const prevYear = String(parseInt(year) - 1);
  let prevSales = [];
  if (!S._prevSalesYear || S._prevSalesYear !== prevYear) {
    try {
      const pd = await apiFetch(`api/data.php?action=sales&period=${prevYear}`);
      prevSales = pd.data || [];
      S._prevSales = prevSales; S._prevSalesYear = prevYear;
    } catch(e) {}
  } else { prevSales = S._prevSales || []; }

  const prevDec = prevSales.filter(s => (s.order_date||'').startsWith(prevYear + '-12'));
  const allSalesWithPrevDec = [...prevDec, ...S.sales];
  const yearlySales = S.sales.filter(s => (s.order_date||'').startsWith(year));

  const monthData = {};
  for (let mo = 1; mo <= 12; mo++) {
    const key = `${year}-${String(mo).padStart(2,'0')}`;
    monthData[key] = { pv:0, amount:0, orders:0, activeMembers:new Set() };
  }
  yearlySales.forEach(s => {
    const mo = (s.order_date||'').substring(0,7);
    if (!monthData[mo]) return;
    monthData[mo].pv     += parseInt(s.pv)||0;
    monthData[mo].amount += parseInt(s.amount)||0;
    monthData[mo].orders++;
    const no = findMemberNo(s);
    if (no) monthData[mo].activeMembers.add(no);
  });

  let totalPV=0, totalAmt=0, totalOrders=0;
  const allActive = new Set();
  Object.values(monthData).forEach(md => {
    totalPV += md.pv; totalAmt += md.amount; totalOrders += md.orders;
    md.activeMembers.forEach(n => allActive.add(n));
  });
  $('yk-pv').textContent  = fmt(totalPV);
  $('yk-amt').textContent = fmtW(totalAmt);
  $('yk-mem').textContent = fmt(allActive.size);

  const commBreakdown = { ref:0, match:0, bin:0, rank:0, repurch:0, lotto:0 };
  let totalPay = 0;
  const monthRows = [];
  const origSales = S.sales;
  S.sales = allSalesWithPrevDec;

  for (const [mo, mData] of Object.entries(monthData)) {
    if (mData.pv === 0) {
      monthRows.push({ mo, comm:0, pv:0, amount:mData.amount, orders:mData.orders, active:mData.activeMembers.size });
      continue;
    }
    const result = calcAllComm(mo);
    const moComm = result.total_payout || 0;
    totalPay += moComm;
    (result.data||[]).forEach(r => {
      commBreakdown.ref    += r.ref     || 0;
      commBreakdown.match  += r.match   || 0;
      commBreakdown.bin    += r.bin     || 0;
      commBreakdown.rank   += r.rank    || 0;
      commBreakdown.repurch+= r.repurch || 0;
      commBreakdown.lotto  += r.lotto   || 0;
    });
    monthRows.push({ mo, comm:moComm, pv:mData.pv, amount:mData.amount, orders:mData.orders, active:mData.activeMembers.size });
  }
  S.sales = origSales;
  $('yk-pay').textContent = fmtW(totalPay);

  // ── 연간 비율 게이지 ──
  if (totalPay > 0) {
    $('yearly-ratio-section').style.display = 'block';
    const amtPct = totalAmt > 0 ? (totalPay / totalAmt * 100) : 0;
    const pvPct  = totalPV  > 0 ? (totalPay / totalPV  * 100) : 0;
    const amtColor = amtPct > 40 ? 'var(--red)' : amtPct > 25 ? 'var(--amber)' : 'var(--purple)';
    const pvColor  = pvPct  > 60 ? 'var(--red)' : pvPct  > 40 ? 'var(--amber)' : 'var(--teal)';
    $('yk-ratio-amt-pct').textContent = amtPct.toFixed(1) + '%';
    $('yk-ratio-pv-pct').textContent  = pvPct.toFixed(1)  + '%';
    $('yk-ratio-amt-pct').style.color = amtColor;
    $('yk-ratio-pv-pct').style.color  = pvColor;
    $('yk-ratio-amt-bar').style.width = Math.min(amtPct, 100) + '%';
    $('yk-ratio-pv-bar').style.width  = Math.min(pvPct,  100) + '%';
    $('yk-ratio-amt-bar').style.background = `linear-gradient(90deg,${amtColor},${amtColor}88)`;
    $('yk-ratio-pv-bar').style.background  = `linear-gradient(90deg,${pvColor},${pvColor}88)`;
    $('yk-ratio-amt-s').textContent = fmtW(totalAmt);
    $('yk-ratio-amt-p').textContent = fmtW(totalPay);
    $('yk-ratio-pv-p').textContent  = fmt(totalPV);
    $('yk-ratio-pv-s').textContent  = fmtW(totalPay);
  }

  // ── 월별 테이블 (매출대비% / PV대비% 컬럼 추가) ──
  const maxComm = Math.max(...monthRows.map(r=>r.comm), 1);
  $('yearly-tbl').innerHTML = `<table>
    <thead><tr>
      <th>월</th><th>매출 합계</th><th>PV 합계</th><th>주문</th><th>활성회원</th>
      <th>예상 수당</th>
      <th style="color:var(--purple)">매출대비%</th>
      <th style="color:var(--teal)">PV대비%</th>
      <th style="min-width:80px"></th>
    </tr></thead>
    <tbody>
    ${monthRows.map(r => {
      const rAmtPct = r.amount > 0 ? (r.comm / r.amount * 100) : 0;
      const rPvPct  = r.pv     > 0 ? (r.comm / r.pv     * 100) : 0;
      const rAmtColor = rAmtPct > 40 ? 'var(--red)' : rAmtPct > 25 ? 'var(--amber)' : 'var(--green)';
      const rPvColor  = rPvPct  > 60 ? 'var(--red)' : rPvPct  > 40 ? 'var(--amber)' : 'var(--teal)';
      return `<tr>
        <td><b>${r.mo.substring(5)}월</b></td>
        <td class="mono">${fmtW(r.amount)}</td>
        <td class="mono">${fmt(r.pv)}</td>
        <td>${fmt(r.orders)}</td>
        <td>${fmt(r.active)}</td>
        <td class="mono" style="color:${r.comm>0?'var(--green)':'var(--t3)'};font-weight:700">${r.comm>0?fmtW(r.comm):'-'}</td>
        <td style="text-align:center">
          ${r.comm>0 ? `<span style="font-size:11px;font-weight:800;color:${rAmtColor}">${rAmtPct.toFixed(1)}%</span>` : '<span style="color:var(--t3)">-</span>'}
        </td>
        <td style="text-align:center">
          ${r.comm>0 ? `<span style="font-size:11px;font-weight:800;color:${rPvColor}">${rPvPct.toFixed(1)}%</span>` : '<span style="color:var(--t3)">-</span>'}
        </td>
        <td><div style="height:5px;background:var(--s3);border-radius:3px">
          <div style="width:${Math.round(r.comm/maxComm*100)}%;height:100%;background:var(--grad);border-radius:3px"></div>
        </div></td>
      </tr>`;
    }).join('')}
    <tr style="background:var(--s2);font-weight:900">
      <td>합계</td>
      <td class="mono">${fmtW(totalAmt)}</td>
      <td class="mono">${fmt(totalPV)}</td>
      <td>${fmt(totalOrders)}</td>
      <td>${fmt(allActive.size)}</td>
      <td class="mono" style="color:var(--purple)">${fmtW(totalPay)}</td>
      <td style="text-align:center;color:var(--purple);font-weight:900">${totalAmt>0?(totalPay/totalAmt*100).toFixed(1)+'%':'-'}</td>
      <td style="text-align:center;color:var(--teal);font-weight:900">${totalPV>0?(totalPay/totalPV*100).toFixed(1)+'%':'-'}</td>
      <td></td>
    </tr>
    </tbody></table>`;

  // ── 수당 유형별 비율 ──
  const commItems = [
    { key:'ref',     label:'💵 추천수당',     color:'var(--blue)' },
    { key:'match',   label:'🎯 추천매칭수당', color:'var(--purple)' },
    { key:'bin',     label:'⚖️ 바이너리수당', color:'var(--amber)' },
    { key:'rank',    label:'👑 직급수당',     color:'var(--red)' },
    { key:'repurch', label:'🔄 직추재구매',   color:'var(--teal)' },
    { key:'lotto',   label:'🎰 로또보너스',   color:'var(--rose)' },
  ];
  const maxB = Math.max(...commItems.map(c=>commBreakdown[c.key]||0), 1);
  $('yearly-comm-breakdown').innerHTML = `
    <div style="display:grid;grid-template-columns:160px 1fr 100px 90px 90px;align-items:center;gap:8px;padding:6px 0;border-bottom:2px solid var(--bd);font-size:10px;font-weight:700;color:var(--t3)">
      <span>수당 종류</span><span>비중</span><span style="text-align:right">금액</span><span style="text-align:right">매출대비%</span><span style="text-align:right">PV대비%</span>
    </div>
    ${commItems.map(c => {
      const amt = commBreakdown[c.key] || 0;
      if (!amt) return '';
      const amtPct = totalAmt > 0 ? (amt / totalAmt * 100) : 0;
      const pvPct  = totalPV  > 0 ? (amt / totalPV  * 100) : 0;
      const barW   = Math.round(amt / maxB * 100);
      return `
        <div style="display:grid;grid-template-columns:160px 1fr 100px 90px 90px;align-items:center;gap:8px;padding:9px 0;border-bottom:1px solid var(--bd)">
          <span style="font-size:11px;font-weight:700">${c.label}</span>
          <div style="height:8px;background:var(--s3);border-radius:4px;overflow:hidden">
            <div style="width:${barW}%;height:100%;background:${c.color};border-radius:4px;transition:width .5s"></div>
          </div>
          <span class="mono" style="text-align:right;color:${c.color};font-weight:700;font-size:11px">${fmtW(amt)}</span>
          <span style="text-align:right;font-size:12px;font-weight:800;color:var(--purple)">${amtPct.toFixed(1)}%</span>
          <span style="text-align:right;font-size:12px;font-weight:800;color:var(--teal)">${pvPct.toFixed(1)}%</span>
        </div>`;
    }).join('')}
    <div style="display:grid;grid-template-columns:160px 1fr 100px 90px 90px;align-items:center;gap:8px;padding:10px 0;background:var(--s2);border-radius:0 0 8px 8px;margin-top:4px">
      <span style="font-size:11px;font-weight:900;padding-left:4px">합계</span>
      <span></span>
      <span class="mono" style="text-align:right;color:var(--purple);font-weight:900">${fmtW(totalPay)}</span>
      <span style="text-align:right;font-size:13px;font-weight:900;color:var(--purple)">${totalAmt>0?(totalPay/totalAmt*100).toFixed(1)+'%':'-'}</span>
      <span style="text-align:right;font-size:13px;font-weight:900;color:var(--teal)">${totalPV>0?(totalPay/totalPV*100).toFixed(1)+'%':'-'}</span>
    </div>`;
}
</script>
