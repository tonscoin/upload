<?php /* panels/panel-comm-rank.php — 직급수당 */ ?>
<div class="panel" id="p-comm-rank">
  <div class="comm-detail-header">
    <div style="font-size:18px">👑</div>
    <div>
      <div style="font-size:14px;font-weight:900">직급수당</div>
      <div style="font-size:11px;color:var(--t3)">전체 PV 12% 재원 · 월 소실적 PV 기준 직급 · 중복 공유 · 월지급</div>
    </div>
    <div style="font-size:11px;color:var(--t3);background:var(--s2);border:1px solid var(--bd);border-radius:8px;padding:8px 12px;margin-left:8px">
      1★=200만 / 2★=500만 / 3★=1000만 / 4★=2000만 / 5★=5000만
    </div>
    <div class="comm-period-bar" style="margin-left:auto">
      <input type="month" id="rank-month" value="<?= date('Y-m') ?>">
      <button class="btn bp" onclick="loadCommRank()">📊 조회</button>
    </div>
  </div>
  <div id="rank-kpi" class="comm-kpi-row" style="display:none"></div>
  <div id="rank-tbl"><div class="empty-msg">기간 선택 후 [조회] 버튼을 누르세요.</div></div>
</div>

<script>
let _rankRows = [];
let _rankSort = 'amt';
let _rankPoolHtml = '';

function rankSortBy(mode) {
  _rankSort = mode;
  rankRenderTable();
}

function rankRenderTable() {
  const el = $('rank-tbl');
  if (!el || !_rankRows.length) return;
  const rows = _rankRows.slice();
  if (_rankSort === 'abc') rows.sort((a,b)=>(a.name||'').localeCompare(b.name||'','ko'));
  else rows.sort((a,b)=>b.total-a.total);
  const rankColors = { '1스타':'var(--blue)', '2스타':'var(--green)', '3스타':'var(--amber)', '4스타':'var(--rose)', '5스타':'var(--red)' };

  const sortBar = '<div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;flex-wrap:wrap">'
    +'<span style="font-size:11px;color:var(--t3)">정렬:</span>'
    +'<div style="display:flex;gap:3px;background:var(--s3);border-radius:6px;padding:2px">'
    +'<button class="btn bo'+(_rankSort==='amt'?' on':'')+'" style="padding:2px 8px;font-size:10px;border-radius:5px" onclick="rankSortBy(\'amt\')">금액순</button>'
    +'<button class="btn bo'+(_rankSort==='abc'?' on':'')+'" style="padding:2px 8px;font-size:10px;border-radius:5px" onclick="rankSortBy(\'abc\')">가나다순</button>'
    +'</div></div>';

  const memHtml = sortBar + `<div class="tw"><table>
    <thead><tr><th>#</th><th>이름</th><th>소실적 PV</th><th>직급 수령 내역</th><th style="text-align:right">수당합계</th></tr></thead>
    <tbody>${rows.map((r,i)=>`<tr onclick="openMo('${r.member_no}')" style="cursor:pointer">
      <td style="color:var(--t3)">${i+1}</td>
      <td><b>${r.name}</b><br><span style="font-size:9px;color:var(--t3)">${r.member_no} · ${(S.members[r.member_no]||{}).login_id||''}</span></td>
      <td class="mono" style="color:var(--amber)">${fmt(r.lesser)}</td>
      <td style="font-size:10px">${r.ranks.map(rk=>
        `<span style="background:${rk.type==='달성'?'rgba(46,125,50,.12)':'rgba(26,86,219,.08)'};color:${rk.type==='달성'?'var(--green)':'var(--blue)'};padding:2px 7px;border-radius:5px;margin-right:3px;white-space:nowrap">${rk.rank} ${rk.type}: ${fmtW(rk.amount)}</span>`
      ).join('')}</td>
      <td style="text-align:right;font-weight:700;color:var(--red)">${fmtW(r.total)}</td>
    </tr>`).join('')}</tbody>
  </table></div>`;

  el.innerHTML = _rankPoolHtml + memHtml;
}

async function loadCommRank() {
  const periodVal = $('rank-month')?.value;
  if (!periodVal) return;
  const el = $('rank-tbl');
  el.innerHTML = '<div class="spin"></div>';
  await ensureData(periodVal);

  const sales  = S.sales.filter(s=>(s.order_date||'').startsWith(periodVal));
  const pvMap  = buildPvMap(sales);
  const qual   = buildQualified(periodVal, S.sales);
  // 직급 소실적도 qualDate 적용: 자격취득일 이후 매출만 레그에 반영
  // buildQualified는 qualDate가 없으므로 buildQualifiedForWeek로 월 전체 qual 구성
  const _rankWQual = {};
  getWeeksInMonth(periodVal).forEach(wk => {
    const wq = buildQualifiedForWeek(wk, S.sales);
    Object.entries(wq).forEach(([no, v]) => {
      if (!_rankWQual[no] || v.qualDate < _rankWQual[no].qualDate) _rankWQual[no] = v;
    });
  });
  const legPV  = buildLegPVQual(sales, _rankWQual);
  const m = S.members;

  const totalPV = Object.values(pvMap).reduce((a,b)=>a+b,0);

  const rankMems = {};
  Object.keys(qual).forEach(no => {
    const lp = legPV[no] || {L:0, R:0};
    const rank = calcRankJs(lp);
    if (rank !== '미달성') {
      if (!rankMems[rank]) rankMems[rank] = [];
      rankMems[rank].push(no);
    }
  });

  const rankPayments = {};
  let grandTotal = 0;

  RANK_ORDER.forEach(rank => {
    const pool = RANK_POOL[rank]; if (!pool) return;
    const poolAmt = Math.round(totalPV * pool.rate);
    const receivers = {};
    RANK_ORDER.slice(RANK_ORDER.indexOf(rank)).forEach(r2 => {
      (rankMems[r2]||[]).forEach(no => { receivers[no] = true; });
    });
    const rcvArr = Object.keys(receivers);
    if (rcvArr.length > 0 && poolAmt > 0) {
      const perPerson = Math.round(poolAmt / rcvArr.length);
      rcvArr.forEach(no => {
        if (!rankPayments[no]) rankPayments[no] = { member_no:no, name:m[no]?.name||no, total:0, ranks:[], lesser: Math.min(legPV[no]?.L||0, legPV[no]?.R||0) };
        rankPayments[no].total += perPerson;
        const isAchiever = (rankMems[rank]||[]).includes(no);
        rankPayments[no].ranks.push({ rank, amount:perPerson, type: isAchiever ? '달성' : '중복' });
      });
      grandTotal += perPerson * rcvArr.length;
    }
  });

  _rankRows = Object.values(rankPayments);
  const rankColors = { '1스타':'var(--blue)', '2스타':'var(--green)', '3스타':'var(--amber)', '4스타':'var(--rose)', '5스타':'var(--red)' };

  showKpi('rank-kpi', `
    <div class="comm-kpi"><div class="comm-kpi-v" style="color:var(--green)">${fmtW(grandTotal)}</div><div class="comm-kpi-l">총 지급 수당</div></div>
    <div class="comm-kpi"><div class="comm-kpi-v" style="color:var(--amber)">${fmt(totalPV)}</div><div class="comm-kpi-l">월 총 PV</div></div>
    <div class="comm-kpi"><div class="comm-kpi-v" style="color:var(--blue)">${_rankRows.length}</div><div class="comm-kpi-l">수당 수령 회원</div></div>
  `);

  _rankPoolHtml = `<div style="margin-bottom:16px;background:var(--s1);border:1px solid var(--bd);border-radius:12px;padding:16px">
    <div style="font-size:12px;font-weight:700;margin-bottom:12px">직급별 풀 배분 현황 <span style="font-size:10px;color:var(--t3);font-weight:400">(전체 PV ${fmt(totalPV)} × 12% = 재원 ${fmtW(Math.round(totalPV*0.12))})</span></div>
    ${RANK_ORDER.map(rank => {
      const pool = RANK_POOL[rank]; if (!pool) return '';
      const poolAmt = Math.round(totalPV * pool.rate);
      const achievers = rankMems[rank] || [];
      const receivers = {};
      RANK_ORDER.slice(RANK_ORDER.indexOf(rank)).forEach(r2 => { (rankMems[r2]||[]).forEach(no => receivers[no]=true); });
      const rcvArr = Object.keys(receivers);
      const pp = rcvArr.length > 0 ? Math.round(poolAmt / rcvArr.length) : 0;
      return `<div class="rank-row">
        <span class="rank-badge rank-${rank}">${rank}</span>
        <div style="flex:1;margin:0 10px">
          <div style="font-size:11px;font-weight:700">${rank} 풀 (${(pool.rate*100).toFixed(0)}%)</div>
          <div style="font-size:10px;color:var(--t3)">달성 ${achievers.length}명 / 수령 ${rcvArr.length}명 / 1인당 ${fmtW(pp)}</div>
        </div>
        <div class="rank-pool-bar"><div class="rank-pool-fill" style="width:${totalPV?Math.min(100,Math.round(pool.rate*100)):0}%;background:${rankColors[rank]||'var(--blue)'}"></div></div>
        <span class="mono" style="min-width:90px;text-align:right;font-weight:700;color:${rankColors[rank]||'var(--blue)'}">${fmtW(poolAmt)}</span>
      </div>`;
    }).join('')}
  </div>`;

  if (!_rankRows.length) {
    el.innerHTML = _rankPoolHtml + '<div class="empty-msg">해당 기간 직급 달성자가 없습니다.</div>';
    return;
  }
  rankRenderTable();
}
</script>
