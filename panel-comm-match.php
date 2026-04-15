<?php /* panels/panel-comm-match.php — 추천매칭수당 */ ?>
<div class="panel" id="p-comm-match">
  <div class="comm-detail-header">
    <div style="font-size:18px">🎯</div>
    <div>
      <div style="font-size:14px;font-weight:900">추천매칭수당</div>
      <div style="font-size:11px;color:var(--t3)">하위 추천 PV × 깊이별 요율 · 1대 20% / 2~7대 10% / 8~12대 4% · 주지급</div>
    </div>
    <div style="font-size:11px;color:var(--t3);background:var(--s2);border:1px solid var(--bd);border-radius:8px;padding:8px 12px;margin-left:8px">
      베이직 3대 / 플러스 5대 / 골드 8대 / 플래티넘 12대
    </div>
    <div class="comm-period-bar" style="margin-left:auto">
      <button class="btn bo on" onclick="togglePeriod(this,'match','week')">주단위</button>
      <button class="btn bo"    onclick="togglePeriod(this,'match','month')">월단위</button>
      <input type="week"  id="match-week"  value="<?= date('Y') ?>-W<?= date('W') ?>">
      <input type="month" id="match-month" value="<?= date('Y-m') ?>" style="display:none">
      <button class="btn bp" onclick="loadCommMatch()">📊 조회</button>
    </div>
  </div>
  <div id="match-kpi" class="comm-kpi-row" style="display:none"></div>
  <div id="match-tbl"><div class="empty-msg">기간 선택 후 [조회] 버튼을 누르세요.</div></div>
</div>

<script>
let _matchRows = [];
let _matchSort = 'amt';

function matchSortBy(mode) {
  _matchSort = mode;
  matchRenderTable();
}

function matchRenderTable() {
  const el = $('match-tbl');
  if (!el || !_matchRows.length) return;
  const rows = _matchRows.slice();
  if (_matchSort === 'abc') rows.sort((a,b)=>(a.name||'').localeCompare(b.name||'','ko'));
  else rows.sort((a,b)=>b.total-a.total);

  const sortBar = '<div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;flex-wrap:wrap">'
    +'<span style="font-size:11px;color:var(--t3)">정렬:</span>'
    +'<div style="display:flex;gap:3px;background:var(--s3);border-radius:6px;padding:2px">'
    +'<button class="btn bo'+(_matchSort==='amt'?' on':'')+'" style="padding:2px 8px;font-size:10px;border-radius:5px" onclick="matchSortBy(\'amt\')">금액순</button>'
    +'<button class="btn bo'+(_matchSort==='abc'?' on':'')+'" style="padding:2px 8px;font-size:10px;border-radius:5px" onclick="matchSortBy(\'abc\')">가나다순</button>'
    +'</div></div>';

  el.innerHTML = sortBar + `<div class="tw"><table>
    <thead><tr>
      <th>#</th><th>이름</th><th>등급</th><th>내 PV</th>
      <th>깊이별 수당 내역</th>
      <th style="text-align:right">합계</th>
    </tr></thead>
    <tbody>${rows.map((r,i)=>`<tr onclick="openMo('${r.member_no}')" style="cursor:pointer">
      <td style="color:var(--t3)">${i+1}</td>
      <td><b>${r.name}</b><br><span style="font-size:9px;color:var(--t3)">${r.member_no} · ${(S.members[r.member_no]||{}).login_id||''}</span></td>
      <td><span class="gb g${r.grade}">${r.grade}</span></td>
      <td class="mono" style="color:var(--blue)">${fmt(r.my_pv)}</td>
      <td style="font-size:10px">
        <div style="display:flex;flex-wrap:wrap;gap:3px">
        ${r.depth_detail.map(d => {
          const tip = d.members.map(mb => {
            const salesLines = (mb.sales||[]).map(sl =>
              '&nbsp;&nbsp;' + sl.date + ' PV ' + fmt(sl.pv) + ' → <b>' + fmtW(sl.comm) + '</b>'
            ).join('<br>');
            return '<b>' + mb.name + '</b> (' + (mb.sales?.length||1) + '건 합계 ' + fmtW(mb.comm) + ')<br>' + salesLines;
          }).join('<br><hr style=\'border:0;border-top:1px solid rgba(255,255,255,.15);margin:3px 0\'>');
          const tipEncoded = encodeURIComponent('<b>'+d.depth+'대 ('+d.count+'명) · 요율 '+(d.rate*100).toFixed(1)+'%</b><hr style=\'border:0;border-top:1px solid rgba(255,255,255,.2);margin:4px 0\'>' + tip);
          return '<span class="tt-tag" style="background:rgba(106,27,154,.1);color:var(--purple);padding:3px 8px;border-radius:6px;cursor:default;white-space:nowrap;position:relative" data-tt="' + tipEncoded + '" onmouseenter="ttShow(this,decodeURIComponent(this.dataset.tt))" onmouseleave="ttHide()">' +
            d.depth + '대(' + d.count + '명) <b>' + fmtW(d.amt) + '</b>' +
          '</span>';
        }).join('')}
        </div>
      </td>
      <td style="text-align:right;font-weight:700;color:var(--purple)">${fmtW(r.total)}</td>
    </tr>`).join('')}</tbody>
  </table></div>`;
}

async function loadCommMatch() {
  const mode = S.periodMode['match'] || 'week';
  const periodVal = mode==='week' ? $('match-week')?.value : $('match-month')?.value;
  if (!periodVal) return;
  const el = $('match-tbl');
  el.innerHTML = '<div class="spin"></div>';
  await ensureData(periodVal);

  const sales  = filterSalesByPeriod(S.sales, periodVal, mode);
  const pvMap  = buildPvMap(sales);
  const refCh  = buildRefCh();
  const qual   = mode==='week' ? buildQualifiedForWeek(periodVal, S.sales)
                               : buildQualified(periodVal, S.sales);
  const m = S.members;

  const matchMap = {};
  Object.keys(qual).forEach(no => {
    const myPv = pvMap[no] || 0;
    const grade    = getEffectiveGrade(no, myPv);
    const maxDepth = MATCH_DEPTH_MAX[grade] || 3;
    const found    = traverseDepthJs(refCh, no, maxDepth);
    const depthDetail = [];
    let totalAmt = 0;

    Object.entries(found).forEach(([depth, list]) => {
      const d    = parseInt(depth);
      const rate = matchRate(d);
      let depthAmt = 0;
      const depthMembers = [];
      list.forEach(childNo => {
        const childSales = sales.filter(s => findMemberNo(s) === childNo);
        if (!childSales.length) return;
        let memberAmt = 0;
        childSales.forEach(s => {
          const spv = parseInt(s.pv) || 0;
          if (!spv) return;
          memberAmt += Math.round(spv * rate);
        });
        if (!memberAmt) return;
        const childPv  = childSales.reduce((a,s)=>a+(parseInt(s.pv)||0),0);
        const childAmt = childSales.reduce((a,s)=>a+(parseInt(s.amount)||0),0);
        depthAmt += memberAmt;
        const salesDetail = childSales
          .filter(s => parseInt(s.pv)||0)
          .map(s => ({ date: s.order_date||'', pv: parseInt(s.pv)||0, amount: parseInt(s.amount)||0, comm: Math.round((parseInt(s.pv)||0)*rate) }));
        depthMembers.push({ name: m[childNo]?.name||childNo, pv: childPv, amount: childAmt, comm: memberAmt, sales: salesDetail });
      });
      if (depthAmt > 0) {
        depthDetail.push({ depth: d, count: depthMembers.length, rate, amt: depthAmt, members: depthMembers });
        totalAmt += depthAmt;
      }
    });
    if (totalAmt > 0) {
      matchMap[no] = {
        member_no: no,
        name: m[no]?.name || no,
        grade,
        my_pv: myPv,
        total: totalAmt,
        depth_detail: depthDetail,
      };
    }
  });

  _matchRows = Object.values(matchMap);
  const total = _matchRows.reduce((s,r)=>s+r.total, 0);

  showKpi('match-kpi', `
    <div class="comm-kpi"><div class="comm-kpi-v" style="color:var(--green)">${fmtW(total)}</div><div class="comm-kpi-l">총 지급 수당</div></div>
    <div class="comm-kpi"><div class="comm-kpi-v" style="color:var(--blue)">${_matchRows.length}</div><div class="comm-kpi-l">수당 수령 회원</div></div>
    <div class="comm-kpi"><div class="comm-kpi-v" style="color:var(--amber)">${_matchRows.length>0?fmtW([..._matchRows].sort((a,b)=>b.total-a.total)[0].total):'-'}</div><div class="comm-kpi-l">최고 수당액</div></div>
  `);

  if (!_matchRows.length) { el.innerHTML='<div class="empty-msg">해당 기간 추천매칭수당 데이터가 없습니다.</div>'; return; }
  matchRenderTable();
}
</script>
