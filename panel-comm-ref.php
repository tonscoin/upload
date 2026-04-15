<?php /* panels/panel-comm-ref.php — 추천수당 */ ?>
<div class="panel" id="p-comm-ref">
  <div class="comm-detail-header">
    <div style="font-size:18px">💵</div>
    <div>
      <div style="font-size:14px;font-weight:900">추천수당</div>
      <div style="font-size:11px;color:var(--t3)">직추천인 구매 PV × 10% · 수당 자격자만 수령 · 주지급</div>
    </div>
    <div class="comm-period-bar" style="margin-left:auto">
      <button class="btn bo on" onclick="togglePeriod(this,'ref','week')">주단위</button>
      <button class="btn bo"    onclick="togglePeriod(this,'ref','month')">월단위</button>
      <input type="week"  id="ref-week"  value="<?= date('Y') ?>-W<?= date('W') ?>">
      <input type="month" id="ref-month" value="<?= date('Y-m') ?>" style="display:none">
      <button class="btn bp" onclick="loadCommRef()">📊 조회</button>
    </div>
  </div>
  <div id="ref-kpi" class="comm-kpi-row" style="display:none"></div>
  <div id="ref-tbl"><div class="empty-msg">기간 선택 후 [조회] 버튼을 누르세요.</div></div>
</div>

<script>
let _refRows = [];
let _refSort = 'amt';

function refSortBy(mode) {
  _refSort = mode;
  refRenderTable();
}

function refRenderTable() {
  const el = $('ref-tbl');
  if (!el || !_refRows.length) return;
  const rows = _refRows.slice();
  if (_refSort === 'abc') rows.sort((a,b)=>(a.name||'').localeCompare(b.name||'','ko'));
  else rows.sort((a,b)=>b.total-a.total);
  const maxV = rows[0]?.total || 1;

  const sortBar = '<div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;flex-wrap:wrap">'
    +'<span style="font-size:11px;color:var(--t3)">정렬:</span>'
    +'<div style="display:flex;gap:3px;background:var(--s3);border-radius:6px;padding:2px">'
    +'<button class="btn bo'+(_refSort==='amt'?' on':'')+'" style="padding:2px 8px;font-size:10px;border-radius:5px" onclick="refSortBy(\'amt\')">금액순</button>'
    +'<button class="btn bo'+(_refSort==='abc'?' on':'')+'" style="padding:2px 8px;font-size:10px;border-radius:5px" onclick="refSortBy(\'abc\')">가나다순</button>'
    +'</div></div>';

  el.innerHTML = sortBar + `<div class="tw"><table>
    <thead><tr>
      <th>#</th><th>이름</th><th>등급</th>
      <th>추천 건수</th><th>추천 PV 합계</th><th>추천인 목록</th>
      <th style="text-align:right">수당(10%)</th><th style="min-width:120px"></th>
    </tr></thead>
    <tbody>${rows.map((r,i)=>`<tr onclick="openMo('${r.member_no}')" style="cursor:pointer">
      <td style="color:var(--t3)">${i+1}</td>
      <td><b>${r.name}</b><br><span style="font-size:9px;color:var(--t3)">${r.member_no} · ${(S.members[r.member_no]||{}).login_id||''}</span></td>
      <td><span class="gb g${r.grade}">${r.grade}</span></td>
      <td style="text-align:center;font-weight:700;color:var(--blue)">${r.count}건</td>
      <td class="mono">${fmt(r.total_pv)}</td>
      <td style="font-size:10px;max-width:200px">
        <div class="tt-wrap" style="display:flex;flex-wrap:wrap;gap:3px">
          ${(()=>{
            const byName={};
            r.buyers.forEach(b=>{if(!byName[b.name])byName[b.name]=[];byName[b.name].push(b);});
            return Object.entries(byName).map(([name,bList])=>{
              const lines=bList.map(b=>b.date+' PV '+fmt(b.pv)+' → '+fmtW(Math.round(b.pv*0.10))).join('<br>');
              const tip=encodeURIComponent('<b>'+name+'</b> ('+bList.length+'건)<br>'+lines);
              return '<span class="tt-tag" style="background:rgba(26,86,219,.08);color:var(--blue);padding:2px 7px;border-radius:5px;cursor:default;white-space:nowrap" data-tt="'+tip+'" onmouseenter="ttShow(this,decodeURIComponent(this.dataset.tt))" onmouseleave="ttHide()">'+name+(bList.length>1?'('+bList.length+'건)':'')+'</span>';
            }).join('');
          })()}
        </div>
      </td>
      <td style="text-align:right;font-weight:700;color:var(--blue)">${fmtW(r.total)}</td>
      <td><div style="height:6px;background:var(--s3);border-radius:3px"><div style="width:${Math.round(r.total/maxV*100)}%;height:100%;background:linear-gradient(90deg,#1a56db,#42a5f5);border-radius:3px"></div></div></td>
    </tr>`).join('')}</tbody>
  </table></div>`;
}

async function loadCommRef() {
  const mode = S.periodMode['ref'] || 'week';
  const periodVal = mode==='week' ? $('ref-week')?.value : $('ref-month')?.value;
  if (!periodVal) return;
  const el = $('ref-tbl');
  el.innerHTML = '<div class="spin"></div>';
  await ensureData(periodVal);

  const sales = filterSalesByPeriod(S.sales, periodVal, mode);
  const pvMap = buildPvMap(sales);
  const qual  = mode==='week' ? buildQualifiedForWeek(periodVal, S.sales)
                              : buildQualified(periodVal, S.sales);
  const m = S.members;

  const refMap = {};
  sales.forEach(s => {
    const buyNo = findMemberNo(s);
    if (!buyNo) return;
    const refNo = m[buyNo]?.referrer_no;
    if (!refNo || !qual[refNo]) return;
    const pv  = parseInt(s.pv) || 0;
    const amt = Math.round(pv * 0.10);
    if (!refMap[refNo]) refMap[refNo] = {
      member_no: refNo,
      name: m[refNo]?.name || refNo,
      grade: getEffectiveGrade(refNo, pvMap[refNo]||0),
      total: 0, count: 0, total_pv: 0,
      buyers: []
    };
    refMap[refNo].total    += amt;
    refMap[refNo].count++;
    refMap[refNo].total_pv += pv;
    refMap[refNo].buyers.push({ name: m[buyNo]?.name||buyNo, pv, amount: parseInt(s.amount)||0, date: s.order_date||'' });
  });

  _refRows = Object.values(refMap);
  const total = _refRows.reduce((s,r)=>s+r.total, 0);
  const maxV  = _refRows.reduce((a,r)=>Math.max(a,r.total),1);

  showKpi('ref-kpi', `
    <div class="comm-kpi"><div class="comm-kpi-v" style="color:var(--green)">${fmtW(total)}</div><div class="comm-kpi-l">총 지급 수당</div></div>
    <div class="comm-kpi"><div class="comm-kpi-v" style="color:var(--blue)">${_refRows.length}</div><div class="comm-kpi-l">수당 수령 회원</div></div>
    <div class="comm-kpi"><div class="comm-kpi-v" style="color:var(--amber)">${_refRows.length>0?fmtW(Math.round(total/_refRows.length)):'-'}</div><div class="comm-kpi-l">1인 평균 수당</div></div>
  `);

  if (!_refRows.length) { el.innerHTML='<div class="empty-msg">해당 기간 추천수당 데이터가 없습니다.</div>'; return; }
  refRenderTable();
}
</script>
