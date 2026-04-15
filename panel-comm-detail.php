<?php /* panels/panel-comm-detail.php — 개인 수당 상세 조회 (신규) */ ?>
<div class="panel" id="p-comm-detail">

  <!-- 상단: 기간 선택 + 조회 -->
  <div class="card" style="margin-bottom:14px">
    <div class="card-hd">
      👤 개인 수당 상세 조회
      <span style="font-size:11px;color:var(--t3);font-weight:400">회원별 · 주차/월차 수당 내역</span>
    </div>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
      <!-- 주/월 토글 -->
      <div style="display:flex;gap:0;border:1px solid var(--bd);border-radius:8px;overflow:hidden">
        <button id="cd-btn-week"  class="btn bo on" style="border-radius:0;border:none;padding:6px 14px" onclick="cdToggle('week')">주단위</button>
        <button id="cd-btn-month" class="btn bo"    style="border-radius:0;border:none;border-left:1px solid var(--bd);padding:6px 14px" onclick="cdToggle('month')">월단위</button>
      </div>
      <input type="week"  id="cd-week"  value="<?= date('Y') ?>-W<?= date('W') ?>"
        style="background:var(--s1);border:1px solid var(--bd);color:var(--t1);padding:6px 10px;border-radius:8px;font-size:11px;font-family:inherit;outline:none">
      <input type="month" id="cd-month" value="<?= date('Y-m') ?>" style="display:none;background:var(--s1);border:1px solid var(--bd);color:var(--t1);padding:6px 10px;border-radius:8px;font-size:11px;font-family:inherit;outline:none">
      <input class="srch" id="cd-search" placeholder="이름·ID·회원번호 검색" style="width:200px"
        oninput="cdFilterList(this.value)">
      <button class="btn bp" onclick="loadCommDetail()">🔍 수당 조회</button>
    </div>
  </div>

  <!-- 정산 요약 KPI -->
  <div id="cd-kpi-row" style="display:none;margin-bottom:14px">
    <div class="stat-g">
      <div class="stat"><div class="stat-lbl">정산 기간</div><div class="stat-val" id="cd-kpi-period" style="font-size:16px;color:var(--blue)">-</div><div class="stat-sub"></div></div>
      <div class="stat"><div class="stat-lbl">총 지급 수당</div><div class="stat-val mono" id="cd-kpi-total" style="color:var(--green)">-</div><div class="stat-sub">전체 합산</div></div>
      <div class="stat"><div class="stat-lbl">수당 수령자</div><div class="stat-val" id="cd-kpi-count" style="color:var(--blue)">-</div><div class="stat-sub">명</div></div>
      <div class="stat"><div class="stat-lbl">정산 구분</div><div class="stat-val" id="cd-kpi-mode" style="font-size:16px;color:var(--purple)">-</div><div class="stat-sub"></div></div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:340px 1fr;gap:14px;align-items:flex-start">

    <!-- 왼쪽: 수당 발생 회원 목록 -->
    <div class="card" style="padding:0;overflow:hidden">
      <div style="padding:10px 14px;background:var(--s2);border-bottom:1px solid var(--bd);display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <span style="font-size:12px;font-weight:700">💰 수당 발생 회원</span>
        <span id="cd-list-cnt" style="font-size:11px;color:var(--t3)"></span>
        <div style="margin-left:auto;display:flex;gap:4px;background:var(--s3);border-radius:6px;padding:2px">
          <button id="cd-sort-amt" class="btn bo on" style="padding:2px 8px;font-size:10px;border-radius:5px" onclick="cdSortList('amt')">금액순</button>
          <button id="cd-sort-abc" class="btn bo"    style="padding:2px 8px;font-size:10px;border-radius:5px" onclick="cdSortList('abc')">가나다순</button>
        </div>
      </div>
      <div id="cd-member-list" style="max-height:600px;overflow-y:auto">
        <div class="empty-msg" style="padding:30px">기간 선택 후 [수당 조회] 버튼을 누르세요.</div>
      </div>
    </div>

    <!-- 오른쪽: 선택 회원 수당 상세 (이미지 참고) -->
    <div id="cd-detail-wrap">
      <div class="card" style="display:flex;align-items:center;justify-content:center;min-height:300px">
        <div style="text-align:center;color:var(--t3)">
          <div style="font-size:40px;margin-bottom:12px">👤</div>
          <div style="font-size:13px;font-weight:700">회원을 클릭하면</div>
          <div style="font-size:11px;margin-top:4px">수당 상세 내역이 표시됩니다</div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
let _cdMode = 'week';
let _cdAllRows = [];
let _cdSelected = null;
let _cdData = null;

function cdToggle(mode) {
  _cdMode = mode;
  document.getElementById('cd-btn-week').classList.toggle('on', mode === 'week');
  document.getElementById('cd-btn-month').classList.toggle('on', mode === 'month');
  document.getElementById('cd-week').style.display  = mode === 'week'  ? '' : 'none';
  document.getElementById('cd-month').style.display = mode === 'month' ? '' : 'none';
}

async function loadCommDetail() {
  const periodVal = _cdMode === 'week'
    ? document.getElementById('cd-week').value
    : document.getElementById('cd-month').value;
  if (!periodVal) return;

  document.getElementById('cd-member-list').innerHTML = '<div class="spin"></div>';
  document.getElementById('cd-detail-wrap').innerHTML = '<div class="card" style="min-height:200px"><div class="spin"></div></div>';

  // 기간에 해당하는 월 도출
  let periodStr = periodVal;
  let weekStr   = null;
  if (_cdMode === 'week') {
    weekStr = periodVal;
    const [yr, wk] = periodVal.split('-W').map(Number);
    const jan4 = new Date(yr, 0, 4);
    const firstMonday = new Date(jan4.getTime() - ((jan4.getDay()+6)%7)*86400000);
    const weekStart = new Date(firstMonday.getTime() + (wk-1)*7*86400000);
    periodStr = weekStart.getFullYear() + '-' + String(weekStart.getMonth()+1).padStart(2,'0');
  }

  await ensureData(periodStr);
  const data = (_cdMode === 'week' && weekStr)
    ? calcAllCommWeek(weekStr)
    : calcAllComm(periodStr);
  _cdData = data;
  _cdAllRows = data.data || [];

  // KPI 업데이트
  document.getElementById('cd-kpi-row').style.display = 'block';
  document.getElementById('cd-kpi-period').textContent  = periodVal;
  document.getElementById('cd-kpi-total').textContent   = fmtW(data.total_payout||0);
  document.getElementById('cd-kpi-count').textContent   = _cdAllRows.length + '명';
  document.getElementById('cd-kpi-mode').textContent    = _cdMode === 'week' ? '주급 정산' : '월급 정산';

  cdRenderList(_cdAllRows);
  document.getElementById('cd-detail-wrap').innerHTML = '<div class="card" style="display:flex;align-items:center;justify-content:center;min-height:200px"><div style="text-align:center;color:var(--t3)"><div style="font-size:32px;margin-bottom:8px">👆</div><div style="font-size:12px">왼쪽 목록에서 회원을 클릭하세요</div></div></div>';
}

function cdFilterList(q) {
  if (!_cdAllRows.length) return;
  const filtered = q
    ? _cdAllRows.filter(r => (r.name||'').includes(q) || (r.member_no||'').includes(q))
    : _cdAllRows;
  cdRenderList(filtered);
}

function cdRenderList(rows) {
  document.getElementById('cd-list-cnt').textContent = rows.length + '명';
  if (!rows.length) {
    document.getElementById('cd-member-list').innerHTML = '<div class="empty-msg" style="padding:20px">수당 발생 회원 없음</div>';
    return;
  }
  const maxTotal = Math.max(...rows.map(r=>r.total||0), 1);
  document.getElementById('cd-member-list').innerHTML = rows.map(function(r) {
    const isSelected = _cdSelected && _cdSelected.member_no === r.member_no;
    return '<div onclick="cdSelectMember(\'' + r.member_no + '\')"'
      + ' style="padding:10px 14px;cursor:pointer;border-bottom:1px solid var(--bd);'
      + (isSelected ? 'background:rgba(26,86,219,.08);border-left:3px solid var(--blue)' : 'border-left:3px solid transparent')
      + '" onmouseover="if(!this.classList.contains(\'cd-sel\'))this.style.background=\'var(--s2)\'"'
      + ' onmouseout="if(!this.classList.contains(\'cd-sel\'))this.style.background=\'\'">'
      + '<div style="display:flex;align-items:center;gap:8px;margin-bottom:5px">'
      + '<span style="font-size:12px;font-weight:700">' + (r.name||r.member_no) + '</span>'
      + '<span class="gb g' + r.grade + '" style="font-size:9px">' + r.grade + '</span>'
      + '<span style="font-size:9px;color:var(--t3);margin-left:4px">' + (r.member_no||'') + '</span>'
      + '<span style="font-size:9px;color:var(--t3);margin-left:2px">(' + ((S.members[r.member_no]||{}).login_id||'') + ')</span>'
      + '<span style="margin-left:auto;font-size:12px;font-weight:900;color:var(--green)">' + fmtW(r.total) + '</span>'
      + '</div>'
      + '<div style="height:4px;background:var(--s3);border-radius:2px">'
      + '<div style="width:' + Math.round(r.total/maxTotal*100) + '%;height:100%;background:var(--grad);border-radius:2px"></div>'
      + '</div>'
      + '<div style="display:flex;gap:6px;margin-top:4px;flex-wrap:wrap">'
      + (r.ref   ? '<span style="font-size:9px;color:var(--blue)">추천 '+fmtW(r.ref)+'</span>' : '')
      + (r.match ? '<span style="font-size:9px;color:var(--purple)">매칭 '+fmtW(r.match)+'</span>' : '')
      + (r.bin   ? '<span style="font-size:9px;color:var(--amber)">바이너리 '+fmtW(r.bin)+'</span>' : '')
      + (r.rank  ? '<span style="font-size:9px;color:var(--red)">직급 '+fmtW(r.rank)+'</span>' : '')
      + (r.repurch?'<span style="font-size:9px;color:var(--teal)">직추재구 '+fmtW(r.repurch)+'</span>' : '')
      + (r.lotto ? '<span style="font-size:9px;color:var(--rose)">로또 '+fmtW(r.lotto)+'</span>' : '')
      + '</div>'
      + '</div>';
  }).join('');
}

function cdSelectMember(no) {
  const row = _cdAllRows.find(r => r.member_no === no);
  if (!row) return;
  _cdSelected = row;

  // 목록 선택 표시 갱신
  document.querySelectorAll('#cd-member-list > div').forEach(function(el) {
    const isThis = el.getAttribute('onclick') === 'cdSelectMember(\'' + no + '\')';
    el.style.background    = isThis ? 'rgba(26,86,219,.08)' : '';
    el.style.borderLeft    = isThis ? '3px solid var(--blue)' : '3px solid transparent';
  });

  cdRenderDetail(row);
}

function cdRenderDetail(r) {
  const m    = S.members[r.member_no] || {};
  const data = _cdData || {};
  const mode = _cdMode;
  const periodVal = mode === 'week'
    ? document.getElementById('cd-week').value
    : document.getElementById('cd-month').value;

  // 수당 항목 배열 (이미지 참고)
  const items = [
    { key:'ref',    label:'💵 추천수당',     color:'var(--blue)',   desc:'직추천 구매 PV × 10%',         val: r.ref    ||0 },
    { key:'match',  label:'🎯 추천매칭수당', color:'var(--purple)', desc:'하위 추천라인 PV × 깊이별 요율', val: r.match  ||0 },
    { key:'bin',    label:'⚖️ 바이너리수당', color:'var(--amber)',  desc:'좌우 소실적 × 10% · CAP 적용', val: r.bin    ||0 },
    { key:'rank',   label:'👑 직급수당',     color:'var(--red)',    desc:'전체 PV 풀 배분',               val: r.rank   ||0 },
    { key:'repurch',label:'🔄 직추재구매',   color:'var(--teal)',   desc:'직추천 재구매 PV × 3%',         val: r.repurch||0 },
    { key:'lotto',  label:'🎰 로또보너스',   color:'var(--rose)',   desc:'전체 PV 3% 풀 · 점수배분',      val: r.lotto  ||0 },
  ].filter(function(it) { return it.val > 0; });

  const totalSales = data.total_sales || 0;
  const totalPV    = data.total_pv    || 0;
  const legPV      = (data.legPV||{})[r.member_no] || {L:0, R:0};
  const pvRatio    = r.pv   > 0 ? (r.total / r.pv   * 100).toFixed(1) : '-';
  const amtRatio   = totalSales > 0 ? (r.total / totalSales * 100).toFixed(2) : '-';

  let html = '<div class="card" style="padding:0;overflow:hidden">'

  // ── 회원 헤더 ──
  + '<div style="padding:16px 18px;background:linear-gradient(135deg,#1a56db,#42a5f5);color:#fff">'
  + '<div style="display:flex;align-items:center;gap:12px">'
  + '<div style="width:48px;height:48px;border-radius:50%;background:rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;font-size:22px">👤</div>'
  + '<div>'
  + '<div style="font-size:18px;font-weight:900">' + (r.name||r.member_no) + '</div>'
  + '<div style="font-size:11px;opacity:.85;margin-top:2px">'
  + (m.login_id||'-') + ' · ' + r.member_no
  + ' · <span class="gb g'+r.grade+'" style="background:rgba(255,255,255,.2);color:#fff;font-size:10px">'+r.grade+'</span>'
  + '</div>'
  + '</div>'
  + '<div style="margin-left:auto;text-align:right">'
  + '<div style="font-size:10px;opacity:.8">총 수당</div>'
  + '<div style="font-size:26px;font-weight:900;font-family:\'JetBrains Mono\',monospace">' + fmtW(r.total) + '</div>'
  + '<div style="font-size:10px;opacity:.7">정산: ' + periodVal + ' (' + (mode==='week'?'주급':'월급') + ')</div>'
  + '</div>'
  + '</div></div>'

  // ── 정산 정보 바 ──
  + '<div style="padding:10px 18px;background:var(--s2);border-bottom:1px solid var(--bd);display:flex;gap:16px;flex-wrap:wrap;font-size:11px">'
  + '<span>이번 ' + (mode==='week'?'주':'달') + ' PV: <b style="color:var(--blue)">' + fmt(r.pv||0) + '</b></span>'
  + '<span>개인PV 대비: <b style="color:var(--purple)">' + pvRatio + '%</b></span>'
  + '<span>전체매출 대비: <b style="color:var(--teal)">' + amtRatio + '%</b></span>'
  + '<span>L레그: <b class="pl">' + fmt(legPV.L) + '</b></span>'
  + '<span>R레그: <b class="pr">' + fmt(legPV.R) + '</b></span>'
  + '</div>'

  // ── 수당 상세 테이블 (이미지 참고) ──
  + '<div style="padding:14px 18px">'
  + '<div style="font-size:12px;font-weight:700;color:var(--t2);margin-bottom:10px">📋 수당 항목별 상세</div>'
  + '<div style="border:1px solid var(--bd);border-radius:10px;overflow:hidden">'

  // 테이블 헤더
  + '<div style="display:grid;grid-template-columns:140px 1fr 110px 70px;gap:0;background:var(--s2);padding:8px 14px;font-size:10px;font-weight:700;color:var(--t3);border-bottom:1px solid var(--bd)">'
  + '<span>수당 구분</span><span>내역</span><span style="text-align:right">수당 발생액</span><span style="text-align:right">비율</span>'
  + '</div>';

  const totalPayout = r.total;
  items.forEach(function(it) {
    const pct = totalPayout > 0 ? (it.val / totalPayout * 100).toFixed(1) : '0';
    const barW = totalPayout > 0 ? Math.round(it.val / totalPayout * 100) : 0;
    html += '<div style="display:grid;grid-template-columns:140px 1fr 110px 70px;gap:0;padding:10px 14px;border-bottom:1px solid var(--bd);align-items:center">'
      + '<div>'
      + '<span style="font-size:11px;font-weight:700;color:' + it.color + '">' + it.label + '</span>'
      + '</div>'
      + '<div>'
      + '<div style="font-size:10px;color:var(--t3);margin-bottom:4px">' + it.desc + '</div>'
      + '<div style="height:5px;background:var(--s3);border-radius:3px;overflow:hidden">'
      + '<div style="width:' + barW + '%;height:100%;background:' + it.color + ';border-radius:3px;opacity:.7"></div>'
      + '</div>'
      + '</div>'
      + '<div style="text-align:right;font-size:13px;font-weight:900;color:' + it.color + ';font-family:\'JetBrains Mono\',monospace">' + fmtW(it.val) + '</div>'
      + '<div style="text-align:right;font-size:11px;font-weight:700;color:var(--t3)">' + pct + '%</div>'
      + '</div>';
  });

  // 합계 행
  html += '<div style="display:grid;grid-template-columns:140px 1fr 110px 70px;gap:0;padding:10px 14px;background:var(--s2);font-weight:900;align-items:center">'
    + '<span style="font-size:12px">합계</span>'
    + '<span></span>'
    + '<span style="text-align:right;font-size:14px;color:var(--green);font-family:\'JetBrains Mono\',monospace">' + fmtW(r.total) + '</span>'
    + '<span style="text-align:right;font-size:11px;color:var(--t3)">100%</span>'
    + '</div>'
    + '</div>'  // table end

  // ── 수당 구성 파이 차트 (간단 막대형) ──
  + '<div style="margin-top:14px">'
  + '<div style="font-size:11px;font-weight:700;color:var(--t2);margin-bottom:6px">수당 구성 비율</div>'
  + '<div style="display:flex;height:14px;border-radius:7px;overflow:hidden;gap:1px">';

  items.forEach(function(it) {
    const pct = totalPayout > 0 ? (it.val / totalPayout * 100) : 0;
    if (pct < 1) return;
    html += '<div title="' + it.label + ' ' + pct.toFixed(1) + '%" style="flex:' + pct.toFixed(1) + ';background:' + it.color + ';min-width:4px"></div>';
  });

  html += '</div>'
    + '<div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:6px">'
    + items.map(function(it) {
        const pct = totalPayout > 0 ? (it.val/totalPayout*100).toFixed(0) : 0;
        return '<span style="font-size:10px;display:flex;align-items:center;gap:3px">'
          + '<span style="width:8px;height:8px;border-radius:50%;background:'+it.color+';display:inline-block"></span>'
          + it.label.split(' ')[1] + ' ' + pct + '%</span>';
      }).join('')
    + '</div></div>'

  + '</div>'  // padding div
  + '</div>'; // card

  // ── 수당 상세 내역 (누구를 통해 얼마 발생했는지) ──
  html += buildCommDetailList(r, data, mode);

  document.getElementById('cd-detail-wrap').innerHTML = html;
}

function cdSortList(mode) {
  document.getElementById('cd-sort-amt')?.classList.toggle('on', mode==='amt');
  document.getElementById('cd-sort-abc')?.classList.toggle('on', mode==='abc');
  const rows = (_cdAllRows||[]).slice();
  if (mode==='abc') rows.sort((a,b)=>(a.name||'').localeCompare(b.name||'','ko'));
  else rows.sort((a,b)=>b.total-a.total);
  cdRenderList(rows);
}

function buildCommDetailList(r, data, mode) {
  const m = S.members;
  const pvMap  = data.pvMap  || {};
  const legPV  = data.legPV  || {};
  const no     = r.member_no;
  const periodVal = mode === 'week'
    ? document.getElementById('cd-week').value
    : document.getElementById('cd-month').value;

  // 해당 기간 매출 필터
  let sales = [];
  if (mode === 'week') {
    const [yr, wk] = periodVal.split('-W').map(Number);
    const jan4 = new Date(yr, 0, 4);
    const firstMonday = new Date(jan4.getTime() - ((jan4.getDay()+6)%7)*86400000);
    const weekStart = new Date(firstMonday.getTime() + (wk-1)*7*86400000);
    sales = S.sales.filter(s => {
      if (!s.order_date) return false;
      const d = new Date(s.order_date);
      return d.getFullYear() == yr && getWeekNumber(d) == wk;
    });
  } else {
    sales = S.sales.filter(s => (s.order_date||'').startsWith(periodVal));
  }

  const refCh = buildRefCh();
  let detailHtml = '<div style="margin-top:16px;border-top:2px solid var(--bd);padding-top:14px">'
    + '<div style="font-size:12px;font-weight:700;color:var(--t2);margin-bottom:10px">📋 수당 발생 상세 내역</div>';

  const sections = [];

  // 01 추천수당: 직추천 매출 건별
  if (r.ref > 0) {
    const rows = [];
    sales.forEach(s => {
      const buyNo = findMemberNo(s);
      if (!buyNo) return;
      if (m[buyNo]?.referrer_no !== no) return;
      const pv = parseInt(s.pv)||0; if (!pv) return;
      rows.push({ name: m[buyNo]?.name||buyNo, date: s.order_date||'', pv, amount: parseInt(s.amount)||0, comm: Math.round(pv*0.10) });
    });
    if (rows.length) sections.push({ label:'💵 추천수당', color:'var(--blue)', rows: rows.map(x=>`<tr><td>${x.date}</td><td>${x.name}</td><td class="mono">${fmt(x.pv)}</td><td class="mono">${fmtW(x.amount)}</td><td class="mono" style="color:var(--blue);font-weight:700">${fmtW(x.comm)}</td></tr>`), total: rows.reduce((a,x)=>a+x.comm,0) });
  }

  // 02 추천매칭수당: 깊이별 건별
  if (r.match > 0) {
    const maxDepth = MATCH_DEPTH_MAX[r.grade] || 3;
    const found = traverseDepthJs(refCh, no, maxDepth);
    const rows = [];
    Object.entries(found).forEach(([depth, list]) => {
      const d = parseInt(depth);
      const rate = matchRate(d);
      list.forEach(childNo => {
        sales.filter(s => findMemberNo(s)===childNo).forEach(s => {
          const pv = parseInt(s.pv)||0; if (!pv) return;
          rows.push({ name: m[childNo]?.name||childNo, depth: d, date: s.order_date||'', pv, amount: parseInt(s.amount)||0, comm: Math.round(pv*rate), rate });
        });
      });
    });
    if (rows.length) {
      // 대수별 소계 계산
      const depthSubtotal = {};
      rows.forEach(x => { depthSubtotal[x.depth] = (depthSubtotal[x.depth]||0) + x.comm; });
      const depthKeys = Object.keys(depthSubtotal).map(Number).sort((a,b)=>a-b);
      const depthSubHtml = depthKeys.length > 0
        ? '<div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px;padding:8px 10px;'
          + 'background:rgba(106,27,154,.04);border:1px solid rgba(106,27,154,.15);border-radius:8px">'
          + '<span style="font-size:11px;font-weight:700;color:var(--purple);line-height:24px;margin-right:4px">대수별 소계</span>'
          + depthKeys.map(k =>
              '<span style="font-size:11px;background:rgba(106,27,154,.1);color:var(--purple);padding:3px 10px;border-radius:5px">'
              + k + '대 <b>' + fmtW(depthSubtotal[k]) + '</b></span>'
            ).join('')
          + '</div>'
        : '';
      sections.push({
        label:'🎯 추천매칭수당',
        color:'var(--purple)',
        rows: rows.map(x=>`<tr><td>${x.date}</td><td>${x.name} <small style="color:var(--t3)">${x.depth}대(${x.depth===1?20:x.depth<=7?10:4}%)</small></td><td class="mono">${fmt(x.pv)}</td><td class="mono">${fmtW(x.amount)}</td><td class="mono" style="color:var(--purple);font-weight:700">${fmtW(x.comm)}</td></tr>`),
        total: rows.reduce((a,x)=>a+x.comm,0),
        footer: depthSubHtml
      });
    }
  }

  // 03 직추재구매수당: 재구매 건별
  if (r.repurch > 0) {
    const rows = [];
    sales.filter(s => isRepurchase(s)).forEach(s => {
      const buyNo = findMemberNo(s);
      if (!buyNo || m[buyNo]?.referrer_no !== no) return;
      const pv = parseInt(s.pv)||0; if (!pv) return;
      rows.push({ name: m[buyNo]?.name||buyNo, date: s.order_date||'', pv, amount: parseInt(s.amount)||0 });
    });
    if (rows.length) {
      const totalPv = rows.reduce((a,x)=>a+x.pv,0);
      sections.push({ label:'🔄 직추재구매수당', color:'var(--teal)', rows: rows.map(x=>`<tr><td>${x.date}</td><td>${x.name} <small style="color:var(--teal)">재구매</small></td><td class="mono">${fmt(x.pv)}</td><td class="mono">${fmtW(x.amount)}</td><td class="mono" style="color:var(--teal);font-weight:700">PV합 ${fmt(totalPv)} × 3%</td></tr>`), total: r.repurch });
    }
  }

  if (!sections.length) {
    detailHtml += '<div style="color:var(--t3);font-size:11px;padding:8px 0">상세 내역 없음</div>';
  } else {
    sections.forEach(sec => {
      detailHtml += '<div style="margin-bottom:12px;border:1px solid var(--bd);border-radius:10px;overflow:hidden">'
        + '<div style="background:var(--s2);padding:7px 12px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--bd)">'
        + '<span style="font-size:11px;font-weight:700;color:'+sec.color+'">'+sec.label+'</span>'
        + '<span style="font-size:12px;font-weight:900;color:'+sec.color+';font-family:\'JetBrains Mono\',monospace">'+fmtW(sec.total)+'</span>'
        + '</div>'
        + '<table style="width:100%;border-collapse:collapse;font-size:11px">'
        + '<thead><tr style="background:var(--s3)">'
        + '<th style="padding:4px 10px;text-align:left;font-weight:600;color:var(--t3)">날짜</th>'
        + '<th style="padding:4px 10px;text-align:left;font-weight:600;color:var(--t3)">회원</th>'
        + '<th style="padding:4px 10px;text-align:right;font-weight:600;color:var(--t3)">PV</th>'
        + '<th style="padding:4px 10px;text-align:right;font-weight:600;color:var(--t3)">매출</th>'
        + '<th style="padding:4px 10px;text-align:right;font-weight:600;color:var(--t3)">수당</th>'
        + '</tr></thead><tbody>'
        + sec.rows.join('')
        + '</tbody></table>'
        + (sec.footer ? '<div style="padding:8px 10px">' + sec.footer + '</div>' : '')
        + '</div>';
    });
  }

  detailHtml += '</div>';
  return detailHtml;
}
</script>
