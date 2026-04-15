<?php /* panels/panel-comm-repurchase.php — 직추재구매수당 */ ?>
<div class="panel" id="p-comm-repurchase">
  <div class="comm-detail-header">
    <div style="font-size:18px">🔄</div>
    <div>
      <div style="font-size:14px;font-weight:900">직추재구매수당</div>
      <div style="font-size:11px;color:var(--t3)">직추천 3명 이상 해당월 구매 시 · 직추천 PV 합계 × 3% · 월지급</div>
    </div>
    <div class="comm-period-bar" style="margin-left:auto">
      <input type="month" id="repurchase-month" value="<?= date('Y-m') ?>">
      <button class="btn bp" onclick="loadCommRepurchase()">📊 조회</button>
    </div>
  </div>
  <div id="repurchase-kpi" class="comm-kpi-row" style="display:none"></div>
  <div id="repurchase-tbl"><div class="empty-msg">기간 선택 후 [조회] 버튼을 누르세요.</div></div>
</div>

<script>
let _repurchRows = [];
let _repurchSort = 'amt';

function repurchSortBy(mode) {
  _repurchSort = mode;
  repurchRenderTable();
}

function repurchRenderTable() {
  const el = $('repurchase-tbl');
  if (!el || !_repurchRows.length) return;
  const rows = _repurchRows.slice();
  if (_repurchSort === 'abc') rows.sort((a,b)=>(a.name||'').localeCompare(b.name||'','ko'));
  else rows.sort((a,b)=>b.total-a.total);
  const maxV = rows[0]?.total || 1;

  const sortBar = '<div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;flex-wrap:wrap">'
    +'<span style="font-size:11px;color:var(--t3)">정렬:</span>'
    +'<div style="display:flex;gap:3px;background:var(--s3);border-radius:6px;padding:2px">'
    +'<button class="btn bo'+(_repurchSort==='amt'?' on':'')+'" style="padding:2px 8px;font-size:10px;border-radius:5px" onclick="repurchSortBy(\'amt\')">금액순</button>'
    +'<button class="btn bo'+(_repurchSort==='abc'?' on':'')+'" style="padding:2px 8px;font-size:10px;border-radius:5px" onclick="repurchSortBy(\'abc\')">가나다순</button>'
    +'</div></div>';

  el.innerHTML = sortBar + `<div class="tw"><table>
    <thead><tr>
      <th>#</th><th>이름</th><th>등급</th>
      <th>본인 PV</th><th>구매 직추천 수</th><th>직추천 PV 합계</th>
      <th>해당 직추천</th>
      <th style="text-align:right">수당(3%)</th>
      <th style="min-width:100px"></th>
    </tr></thead>
    <tbody>${rows.map((r,i) => `<tr onclick="openMo('${r.member_no}')" style="cursor:pointer">
      <td style="color:var(--t3)">${i+1}</td>
      <td><b>${r.name}</b></td>
      <td><span class="gb g${r.grade}">${r.grade}</span></td>
      <td class="mono" style="color:var(--t3)">${fmt(r.own_pv)}</td>
      <td style="text-align:center;font-weight:700;color:var(--teal)">${r.ref_count}명</td>
      <td class="mono" style="font-weight:700">${fmt(r.ref_pv)}</td>
      <td style="font-size:10px">
        <div style="display:flex;flex-wrap:wrap;gap:3px">
          ${r.ref_details.map(d => {
            const lines = d.salesList.map(sl => sl.date+' PV '+fmt(sl.pv)+' 매출 '+fmtW(sl.amount)).join('<br>');
            const tip = encodeURIComponent('<b>'+d.name+'</b> ('+d.salesList.length+'건)<br>'+lines);
            return '<span class="tt-tag" style="background:rgba(0,105,92,.1);color:var(--teal);padding:2px 7px;border-radius:5px;cursor:default;white-space:nowrap" data-tt="'+tip+'" onmouseenter="ttShow(this,decodeURIComponent(this.dataset.tt))" onmouseleave="ttHide()">'+d.name+(d.salesList.length>1?'('+d.salesList.length+'건)':'')+'</span>';
          }).join('')}
        </div>
      </td>
      <td style="text-align:right;font-weight:700;color:var(--teal)">${fmtW(r.total)}</td>
      <td><div style="height:6px;background:var(--s3);border-radius:3px">
        <div style="width:${Math.round(r.total/maxV*100)}%;height:100%;background:linear-gradient(90deg,#00897b,#80cbc4);border-radius:3px"></div>
      </div></td>
    </tr>`).join('')}</tbody>
  </table></div>`;
}

async function loadCommRepurchase() {
  const periodVal = $('repurchase-month')?.value;
  if (!periodVal) return;
  const el = $('repurchase-tbl');
  el.innerHTML = '<div class="spin"></div>';
  await ensureData(periodVal);

  const sales  = S.sales.filter(s => (s.order_date||'').startsWith(periodVal));
  const pvMap  = buildPvMap(sales);
  const refCh  = buildRefCh();
  const qual   = buildQualified(periodVal, S.sales);
  const m = S.members;

  const rows = [];
  Object.keys(qual).forEach(no => {
    const repurchSales = sales.filter(s => {
      const buyNo = findMemberNo(s);
      return buyNo && m[buyNo]?.referrer_no === no && isRepurchase(s);
    });
    const activeRefs = [...new Set(repurchSales.map(s => findMemberNo(s)).filter(Boolean))];
    if (activeRefs.length < 3) return;
    const tv  = activeRefs.reduce((a, c) => a + (pvMap[c] || 0), 0);
    const amt = Math.round(tv * 0.03);
    if (amt <= 0) return;
    const refDetails = activeRefs.map(c => {
      const cSales = repurchSales.filter(s => findMemberNo(s)===c);
      const salesList = cSales.map(s => ({ date: s.order_date||'', pv: parseInt(s.pv)||0, amount: parseInt(s.amount)||0 }));
      return { name: m[c]?.name||c, pv: pvMap[c]||0, salesList };
    });
    rows.push({
      member_no: no,
      name: m[no]?.name || no,
      grade: getEffectiveGrade(no, pvMap[no]||0),
      own_pv: pvMap[no]||0,
      ref_count: activeRefs.length,
      ref_pv: tv,
      total: amt,
      ref_details: refDetails,
    });
  });

  _repurchRows = rows;
  const total = _repurchRows.reduce((s,r) => s+r.total, 0);

  showKpi('repurchase-kpi', `
    <div class="comm-kpi"><div class="comm-kpi-v" style="color:var(--green)">${fmtW(total)}</div><div class="comm-kpi-l">총 지급 수당</div></div>
    <div class="comm-kpi"><div class="comm-kpi-v" style="color:var(--teal)">${_repurchRows.length}</div><div class="comm-kpi-l">수당 수령 회원</div></div>
    <div class="comm-kpi"><div class="comm-kpi-v" style="color:var(--blue)">${_repurchRows.length>0?fmtW(Math.round(total/_repurchRows.length)):'-'}</div><div class="comm-kpi-l">1인 평균 수당</div></div>
  `);

  if (!_repurchRows.length) {
    el.innerHTML = '<div class="empty-msg">해당 기간 직추재구매수당 데이터가 없습니다.<br><small style="color:var(--t3)">조건: 자격자이며 직추천인 중 3명 이상이 해당 월 구매</small></div>';
    return;
  }
  repurchRenderTable();
}
</script>
