<?php /* panels/panel-comm-weekly.php — 주지급 수당 */ ?>
<div class="panel" id="p-comm-weekly">

  <div class="card" style="margin-bottom:14px">
    <div class="card-hd">
      📅 주지급 수당 조회
      <span style="font-size:11px;color:var(--t3);font-weight:400">추천수당 · 추천매칭수당 · 바이너리수당</span>
    </div>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
      <input type="week" id="cw-week" value="<?= date('Y') ?>-W<?= date('W') ?>"
        style="background:var(--s1);border:1px solid var(--bd);color:var(--t1);padding:6px 10px;border-radius:8px;font-size:11px;font-family:inherit;outline:none">
      <input class="srch" id="cw-search" placeholder="이름·ID·회원번호 검색" style="width:200px"
        oninput="cwFilterList(this.value)">
      <button class="btn bp" onclick="loadCommWeekly()">🔍 주지급 수당 조회</button>
    </div>
  </div>

  <!-- KPI -->
  <div id="cw-kpi-row" style="display:none;margin-bottom:14px">
    <!-- 1행: 기간/총액/인원 -->
    <div class="stat-g" style="margin-bottom:10px">
      <div class="stat"><div class="stat-lbl">정산 기간</div><div class="stat-val" id="cw-kpi-period" style="font-size:16px;color:var(--blue)">-</div></div>
      <div class="stat"><div class="stat-lbl">총 주지급 수당</div><div class="stat-val mono" id="cw-kpi-total" style="color:var(--green)">-</div><div class="stat-sub">전체 합산</div></div>
      <div class="stat"><div class="stat-lbl">수당 수령자</div><div class="stat-val" id="cw-kpi-count" style="color:var(--blue)">-</div><div class="stat-sub">명</div></div>
    </div>
    <!-- 2행: 수당 구성별 합계 바 -->
    <div style="background:var(--s1);border:1px solid var(--bd);border-radius:12px;padding:14px 18px">
      <div style="font-size:11px;font-weight:700;color:var(--t3);margin-bottom:10px">📊 수당 구성별 합계</div>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px">
        <!-- 추천수당 -->
        <div style="background:rgba(26,86,219,.06);border:1px solid rgba(26,86,219,.15);border-radius:10px;padding:12px 14px">
          <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px">
            <span style="font-size:13px">💵</span>
            <span style="font-size:11px;font-weight:700;color:var(--blue)">추천수당</span>
          </div>
          <div class="mono" id="cw-kpi-ref" style="font-size:18px;font-weight:900;color:var(--blue)">-</div>
          <div style="margin-top:6px;height:4px;background:var(--s3);border-radius:2px;overflow:hidden">
            <div id="cw-kpi-ref-bar" style="height:100%;background:var(--blue);border-radius:2px;width:0%;transition:width .4s"></div>
          </div>
          <div id="cw-kpi-ref-pct" style="font-size:10px;color:var(--t3);margin-top:3px;text-align:right">0%</div>
        </div>
        <!-- 추천매칭수당 -->
        <div style="background:rgba(106,27,154,.06);border:1px solid rgba(106,27,154,.15);border-radius:10px;padding:12px 14px">
          <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px">
            <span style="font-size:13px">🎯</span>
            <span style="font-size:11px;font-weight:700;color:var(--purple)">추천매칭수당</span>
          </div>
          <div class="mono" id="cw-kpi-match" style="font-size:18px;font-weight:900;color:var(--purple)">-</div>
          <div style="margin-top:6px;height:4px;background:var(--s3);border-radius:2px;overflow:hidden">
            <div id="cw-kpi-match-bar" style="height:100%;background:var(--purple);border-radius:2px;width:0%;transition:width .4s"></div>
          </div>
          <div id="cw-kpi-match-pct" style="font-size:10px;color:var(--t3);margin-top:3px;text-align:right">0%</div>
        </div>
        <!-- 바이너리수당 -->
        <div style="background:rgba(245,124,0,.06);border:1px solid rgba(245,124,0,.15);border-radius:10px;padding:12px 14px">
          <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px">
            <span style="font-size:13px">⚖️</span>
            <span style="font-size:11px;font-weight:700;color:var(--amber)">바이너리수당</span>
          </div>
          <div class="mono" id="cw-kpi-bin" style="font-size:18px;font-weight:900;color:var(--amber)">-</div>
          <div style="margin-top:6px;height:4px;background:var(--s3);border-radius:2px;overflow:hidden">
            <div id="cw-kpi-bin-bar" style="height:100%;background:var(--amber);border-radius:2px;width:0%;transition:width .4s"></div>
          </div>
          <div id="cw-kpi-bin-pct" style="font-size:10px;color:var(--t3);margin-top:3px;text-align:right">0%</div>
        </div>
      </div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:340px 1fr;gap:14px;align-items:flex-start">

    <!-- 왼쪽: 회원 목록 -->
    <div class="card" style="padding:0;overflow:hidden">
      <div style="padding:10px 14px;background:var(--s2);border-bottom:1px solid var(--bd);display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <span style="font-size:12px;font-weight:700">💰 수당 발생 회원</span>
        <span id="cw-list-cnt" style="font-size:11px;color:var(--t3)"></span>
        <div style="margin-left:auto;display:flex;gap:3px;background:var(--s3);border-radius:6px;padding:2px">
          <button id="cw-sort-amt" class="btn bo on" style="padding:2px 8px;font-size:10px;border-radius:5px" onclick="cwSortList('amt')">금액순</button>
          <button id="cw-sort-abc" class="btn bo"    style="padding:2px 8px;font-size:10px;border-radius:5px" onclick="cwSortList('abc')">가나다순</button>
        </div>
      </div>
      <div id="cw-member-list" style="max-height:600px;overflow-y:auto">
        <div class="empty-msg" style="padding:30px">기간 선택 후 [주지급 수당 조회] 버튼을 누르세요.</div>
      </div>
    </div>

    <!-- 오른쪽: 상세 -->
    <div id="cw-detail-wrap">
      <div class="card" style="display:flex;align-items:center;justify-content:center;min-height:300px">
        <div style="text-align:center;color:var(--t3)">
          <div style="font-size:40px;margin-bottom:12px">📅</div>
          <div style="font-size:13px;font-weight:700">회원을 클릭하면</div>
          <div style="font-size:11px;margin-top:4px">주지급 수당 상세 내역이 표시됩니다</div>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
let _cwAllRows = [];
let _cwSelected = null;
let _cwData = null;
let _cwSort = 'amt';

async function loadCommWeekly() {
  const weekVal = document.getElementById('cw-week').value;
  if (!weekVal) return;

  document.getElementById('cw-member-list').innerHTML = '<div class="spin"></div>';
  document.getElementById('cw-detail-wrap').innerHTML = '<div class="card" style="min-height:200px"><div class="spin"></div></div>';

  const [yr, wk] = weekVal.split('-W').map(Number);
  const jan4 = new Date(yr, 0, 4);
  const firstMonday = new Date(jan4.getTime() - ((jan4.getDay()+6)%7)*86400000);
  const weekStart = new Date(firstMonday.getTime() + (wk-1)*7*86400000);
  const periodStr = weekStart.getFullYear() + '-' + String(weekStart.getMonth()+1).padStart(2,'0');

  await ensureData(periodStr);
  const data = calcAllCommWeek(weekVal);
  _cwData = data;

  const rows = (data.data || []).map(r => {
    const ref   = r.ref   || 0;
    const match = r.match || 0;
    const bin   = r.bin   || 0;
    const total = ref + match + bin;
    if (total <= 0) return null;
    return { ...r, ref, match, bin, total };
  }).filter(Boolean);

  _cwAllRows = rows;

  const _cwTotal = rows.reduce((a,r)=>a+r.total,0);
  const _cwRef   = rows.reduce((a,r)=>a+(r.ref||0),0);
  const _cwMatch = rows.reduce((a,r)=>a+(r.match||0),0);
  const _cwBin   = rows.reduce((a,r)=>a+(r.bin||0),0);

  document.getElementById('cw-kpi-row').style.display = 'block';
  document.getElementById('cw-kpi-period').textContent = weekVal;
  document.getElementById('cw-kpi-total').textContent  = fmtW(_cwTotal);
  document.getElementById('cw-kpi-count').textContent  = rows.length + '명';
  document.getElementById('cw-kpi-ref').textContent    = fmtW(_cwRef);
  document.getElementById('cw-kpi-match').textContent  = fmtW(_cwMatch);
  document.getElementById('cw-kpi-bin').textContent    = fmtW(_cwBin);

  // 비율 바 (requestAnimationFrame으로 트랜지션 적용)
  requestAnimationFrame(() => {
    const _cwPct = (v) => _cwTotal > 0 ? Math.round(v/_cwTotal*100) : 0;
    document.getElementById('cw-kpi-ref-bar').style.width   = _cwPct(_cwRef)   + '%';
    document.getElementById('cw-kpi-match-bar').style.width = _cwPct(_cwMatch) + '%';
    document.getElementById('cw-kpi-bin-bar').style.width   = _cwPct(_cwBin)   + '%';
    document.getElementById('cw-kpi-ref-pct').textContent   = _cwPct(_cwRef)   + '%';
    document.getElementById('cw-kpi-match-pct').textContent = _cwPct(_cwMatch) + '%';
    document.getElementById('cw-kpi-bin-pct').textContent   = _cwPct(_cwBin)   + '%';
  });

  cwRenderList(rows);
  document.getElementById('cw-detail-wrap').innerHTML =
    '<div class="card" style="display:flex;align-items:center;justify-content:center;min-height:200px"><div style="text-align:center;color:var(--t3)"><div style="font-size:32px;margin-bottom:8px">👆</div><div style="font-size:12px">왼쪽 목록에서 회원을 클릭하세요</div></div></div>';
}

function cwFilterList(q) {
  if (!_cwAllRows.length) return;
  const filtered = q
    ? _cwAllRows.filter(r => (r.name||'').includes(q)||(r.member_no||'').includes(q))
    : _cwAllRows;
  cwRenderList(filtered);
}

function cwSortList(mode) {
  _cwSort = mode;
  ['amt','abc'].forEach(m => document.getElementById('cw-sort-'+m)?.classList.toggle('on', m===mode));
  cwRenderList(_cwAllRows);
}

function cwRenderList(rows) {
  const sorted = rows.slice();
  if (_cwSort === 'abc') sorted.sort((a,b)=>(a.name||'').localeCompare(b.name||'','ko'));
  else sorted.sort((a,b)=>b.total-a.total);

  document.getElementById('cw-list-cnt').textContent = sorted.length + '명';
  if (!sorted.length) {
    document.getElementById('cw-member-list').innerHTML = '<div class="empty-msg" style="padding:20px">주지급 수당 발생 회원 없음</div>';
    return;
  }
  const maxTotal = Math.max(...sorted.map(r=>r.total), 1);
  document.getElementById('cw-member-list').innerHTML = sorted.map(r => {
    const isSel = _cwSelected && _cwSelected.member_no === r.member_no;
    return '<div onclick="cwSelectMember(\'' + r.member_no + '\')"'
      + ' style="padding:10px 14px;cursor:pointer;border-bottom:1px solid var(--bd);'
      + (isSel ? 'background:rgba(26,86,219,.08);border-left:3px solid var(--blue)' : 'border-left:3px solid transparent')
      + '">'
      + '<div style="display:flex;align-items:center;gap:8px;margin-bottom:5px">'
      + '<span style="font-size:12px;font-weight:700">' + (r.name||r.member_no) + '</span>'
      + '<span class="gb g' + r.grade + '" style="font-size:9px">' + r.grade + '</span>'
      + '<span style="font-size:9px;color:var(--t3);margin-left:4px">' + (r.member_no||'') + '</span>'
      + '<span style="font-size:9px;color:var(--t3);margin-left:2px">(' + ((S.members[r.member_no]||{}).login_id||'') + ')</span>'
      + '<span style="margin-left:auto;font-size:12px;font-weight:900;color:var(--green)">' + fmtW(r.total) + '</span>'
      + '</div>'
      + '<div style="height:4px;background:var(--s3);border-radius:2px">'
      + '<div style="width:' + Math.round(r.total/maxTotal*100) + '%;height:100%;background:linear-gradient(90deg,#1a56db,#42a5f5);border-radius:2px"></div>'
      + '</div>'
      + '<div style="display:flex;gap:6px;margin-top:4px;flex-wrap:wrap">'
      + (r.ref   ? '<span style="font-size:9px;color:var(--blue)">추천 '    + fmtW(r.ref)   + '</span>' : '')
      + (r.match ? '<span style="font-size:9px;color:var(--purple)">매칭 '  + fmtW(r.match) + '</span>' : '')
      + (r.bin   ? '<span style="font-size:9px;color:var(--amber)">바이너리 '+ fmtW(r.bin)   + '</span>' : '')
      + '</div></div>';
  }).join('');
}

function cwSelectMember(no) {
  if (!_cwData) return;
  _cwSelected = (_cwData.data||[]).find(r=>r.member_no===no) || null;
  cwRenderDetail(no);
  cwRenderList(_cwAllRows);
}

// ─────────────────────────────────────────────────────────
// cwRenderDetail : 수당 발생 상세 내역 (건별 테이블)
// ─────────────────────────────────────────────────────────
function cwRenderDetail(no) {
  const r = _cwAllRows.find(x=>x.member_no===no);
  if (!r) return;
  const mem     = S.members[no] || {};
  const weekVal = document.getElementById('cw-week').value;
  const detail  = r.detail || { ref:[], match:[], bin:[] };
  const total   = (r.ref||0) + (r.match||0) + (r.bin||0);

  // ── 헤더 배너 ──
  let html = '<div class="card" style="padding:0;overflow:hidden">'
    + '<div style="padding:14px 18px;background:linear-gradient(135deg,#1a56db,#42a5f5);color:#fff">'
    + '<div style="display:flex;align-items:center;gap:12px">'
    + '<div style="width:44px;height:44px;border-radius:50%;background:rgba(255,255,255,.2);'
    +   'display:flex;align-items:center;justify-content:center;font-size:20px">📅</div>'
    + '<div>'
    + '<div style="font-size:16px;font-weight:900">' + (mem.name||no) + '</div>'
    + '<div style="font-size:11px;opacity:.85;margin-top:2px">'
    +   (mem.login_id ? mem.login_id + ' · ' : '') + (mem.member_no||no) + ' · ' + weekVal + ' 주지급 수당'
    + '</div></div>'
    + '<div style="margin-left:auto;text-align:right">'
    + '<div style="font-size:11px;opacity:.8">총 수당</div>'
    + '<div style="font-size:24px;font-weight:900;font-family:\'JetBrains Mono\',monospace">' + fmtW(total) + '</div>'
    + '</div></div></div>';

  // ── 3칸 요약 카드 ──
  const summaryItems = [
    { label:'💵 추천수당',     color:'var(--blue)',   desc:'직추천 PV × 10%',             val: r.ref   },
    { label:'🎯 추천매칭수당', color:'var(--purple)', desc:'하위 PV × 깊이별 매칭율',      val: r.match },
    { label:'⚖️ 바이너리수당', color:'var(--amber)',  desc:'소실적 × 10% (주간 CAP)', val: r.bin   },
  ];
  html += '<div style="display:grid;grid-template-columns:repeat(3,1fr);border-bottom:1px solid var(--bd)">';
  summaryItems.forEach(it => {
    const pct = total > 0 ? Math.round((it.val||0)/total*100) : 0;
    html += '<div style="padding:14px;text-align:center;border-right:1px solid var(--bd)">'
      + '<div style="font-size:10px;color:var(--t3);margin-bottom:4px">' + it.label + '</div>'
      + '<div style="font-size:18px;font-weight:900;color:' + it.color + ';font-family:\'JetBrains Mono\',monospace">' + fmtW(it.val||0) + '</div>'
      + '<div style="font-size:9px;color:var(--t3);margin-top:2px">' + it.desc + '</div>'
      + '<div style="font-size:10px;font-weight:700;color:' + it.color + ';opacity:.7">' + pct + '%</div>'
      + '</div>';
  });
  html += '</div>';

  html += '<div style="padding:16px 18px">';

  // ══════════════════════════════════════
  // ① 추천수당 발생 상세
  // ══════════════════════════════════════
  if (detail.ref && detail.ref.length) {
    html += _cwSecHdr('💵 추천수당 발생 상세', 'var(--blue)', r.ref);
    html += '<div class="tw" style="margin-bottom:20px"><table>'
      + '<thead><tr style="background:rgba(26,86,219,.07)">'
      + '<th style="padding:7px 10px;text-align:left">날짜</th>'
      + '<th style="padding:7px 10px;text-align:left">구매 회원</th>'
      + '<th style="padding:7px 10px;text-align:right">PV</th>'
      + '<th style="padding:7px 10px;text-align:right">요율</th>'
      + '<th style="padding:7px 10px;text-align:right">수당</th>'
      + '</tr></thead><tbody>';

    detail.ref.slice().sort((a,b)=>(a.date||'')<(b.date||'')?-1:1).forEach(d => {
      html += '<tr>'
        + '<td style="padding:7px 10px;color:var(--t3);font-size:11px;white-space:nowrap">' + (d.date||'-') + '</td>'
        + '<td style="padding:7px 10px">'
        +   '<b style="font-size:12px">' + (d.buyerName||'-') + '</b>'
        +   '<span style="font-size:9px;color:var(--t3);margin-left:6px">' + (d.buyerNo||'') + '</span></td>'
        + '<td style="padding:7px 10px;text-align:right;font-family:\'JetBrains Mono\',monospace;color:var(--blue)">' + fmt(d.pv||0) + '</td>'
        + '<td style="padding:7px 10px;text-align:right;color:var(--t3)">' + (d.rate||10) + '%</td>'
        + '<td style="padding:7px 10px;text-align:right;font-weight:700;font-family:\'JetBrains Mono\',monospace;color:var(--blue)">' + fmtW(d.amt||0) + '</td>'
        + '</tr>';
    });

    html += '<tr style="background:var(--s2);font-weight:900">'
      + '<td colspan="4" style="padding:8px 10px;font-size:12px">합계</td>'
      + '<td style="padding:8px 10px;text-align:right;font-size:13px;font-family:\'JetBrains Mono\',monospace;color:var(--blue)">' + fmtW(r.ref) + '</td>'
      + '</tr>';
    html += '</tbody></table></div>';
  }

  // ══════════════════════════════════════
  // ② 추천매칭수당 발생 상세
  // ══════════════════════════════════════
  if (detail.match && detail.match.length) {
    html += _cwSecHdr('🎯 추천매칭수당 발생 상세', 'var(--purple)', r.match);
    html += '<div class="tw" style="margin-bottom:12px"><table>'
      + '<thead><tr style="background:rgba(106,27,154,.07)">'
      + '<th style="padding:7px 10px;text-align:left">날짜</th>'
      + '<th style="padding:7px 10px;text-align:left">하위 회원</th>'
      + '<th style="padding:7px 10px;text-align:center">대수</th>'
      + '<th style="padding:7px 10px;text-align:right">PV</th>'
      + '<th style="padding:7px 10px;text-align:right">요율</th>'
      + '<th style="padding:7px 10px;text-align:right">수당</th>'
      + '</tr></thead><tbody>';

    detail.match.slice().sort((a,b)=>{
      if ((a.date||'') !== (b.date||'')) return (a.date||'') < (b.date||'') ? -1 : 1;
      return (a.depth||0) - (b.depth||0);
    }).forEach(d => {
      html += '<tr>'
        + '<td style="padding:7px 10px;color:var(--t3);font-size:11px;white-space:nowrap">' + (d.date||'-') + '</td>'
        + '<td style="padding:7px 10px">'
        +   '<b style="font-size:12px">' + (d.buyerName||'-') + '</b>'
        +   '<span style="font-size:9px;color:var(--t3);margin-left:6px">' + (d.buyerNo||'') + '</span></td>'
        + '<td style="padding:7px 10px;text-align:center">'
        +   '<span style="background:rgba(106,27,154,.12);color:var(--purple);padding:2px 8px;border-radius:4px;font-size:10px;font-weight:700">' + (d.depth||'-') + '대</span></td>'
        + '<td style="padding:7px 10px;text-align:right;font-family:\'JetBrains Mono\',monospace;color:var(--blue)">' + fmt(d.pv||0) + '</td>'
        + '<td style="padding:7px 10px;text-align:right;color:var(--t3)">' + (d.rate||'-') + '%</td>'
        + '<td style="padding:7px 10px;text-align:right;font-weight:700;font-family:\'JetBrains Mono\',monospace;color:var(--purple)">' + fmtW(d.amt||0) + '</td>'
        + '</tr>';
    });

    html += '<tr style="background:var(--s2);font-weight:900">'
      + '<td colspan="5" style="padding:8px 10px;font-size:12px">합계</td>'
      + '<td style="padding:8px 10px;text-align:right;font-size:13px;font-family:\'JetBrains Mono\',monospace;color:var(--purple)">' + fmtW(r.match) + '</td>'
      + '</tr>';
    html += '</tbody></table></div>';

    // 대수별 소계 배지
    const depthMap = {};
    detail.match.forEach(d => {
      const k = d.depth || 0;
      depthMap[k] = (depthMap[k]||0) + (d.amt||0);
    });
    const depthKeys = Object.keys(depthMap).map(Number).sort((a,b)=>a-b);
    if (depthKeys.length > 0) {
      html += '<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px;padding:10px 12px;'
        + 'background:rgba(106,27,154,.04);border:1px solid rgba(106,27,154,.15);border-radius:8px">'
        + '<span style="font-size:11px;font-weight:700;color:var(--purple);line-height:24px;margin-right:4px">대수별 소계</span>';
      depthKeys.forEach(k => {
        html += '<span style="font-size:11px;background:rgba(106,27,154,.1);color:var(--purple);padding:3px 10px;border-radius:5px">'
          + k + '대 <b>' + fmtW(depthMap[k]) + '</b></span>';
      });
      html += '</div>';
    }
  }

  // ══════════════════════════════════════
  // ③ 바이너리수당 발생 상세 (인터랙티브)
  // ══════════════════════════════════════
  if (detail.bin && detail.bin.length) {
    html += _cwSecHdr('⚖️ 바이너리수당 발생 상세', 'var(--amber)', r.bin);
    const b      = detail.bin[0];
    const legL   = b.legL   || 0;
    const legR   = b.legR   || 0;
    const small  = b.small  || Math.min(legL, legR);
    const cap    = b.cap    || 0;
    const carryL = b.carryL || 0;
    const carryR = b.carryR || 0;
    const newL   = legL - carryL;   // 이번 주 신규 L 레그
    const newR   = legR - carryR;   // 이번 주 신규 R 레그
    const rawAmt = Math.round(small * 0.10);
    const capAmt = cap ? Math.min(rawAmt, cap) : rawAmt;
    const ratio  = (b.ratio !== undefined) ? b.ratio : 1;
    const isCap  = rawAmt > capAmt;
    const isRatio = ratio < 0.9999;
    const finalAmt = b.amt || 0;
    // 다음 주 이월 PV 계산
    const usedPV = Math.round(finalAmt / 0.10);
    const nextCarryL = legL >= legR ? Math.max(0, legL - usedPV) : 0;
    const nextCarryR = legR >  legL ? Math.max(0, legR - usedPV) : 0;
    const isLBig = legL >= legR;

    // ── 레그 시각화 카드 (클릭으로 상세 토글) ──
    const binId = 'bin-detail-' + no;
    html += '<div style="margin-bottom:14px">';

    // 좌/우 레그 2칸 카드
    html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">';

    // 좌레그 카드
    html += '<div onclick="_cwToggleLegDetail(\'' + binId + '-L\')"'
      + ' style="cursor:pointer;background:rgba(26,86,219,.05);border:2px solid ' + (isLBig ? 'rgba(26,86,219,.4)' : 'rgba(26,86,219,.2)') + ';border-radius:12px;padding:14px;position:relative;transition:all .2s"'
      + ' onmouseover="this.style.background=\'rgba(26,86,219,.1)\'" onmouseout="this.style.background=\'rgba(26,86,219,.05)\'">'
      + (isLBig ? '<div style="position:absolute;top:8px;right:8px;background:var(--blue);color:#fff;font-size:9px;font-weight:700;padding:2px 7px;border-radius:5px">대레그 ▲</div>' : '')
      + '<div style="font-size:11px;font-weight:700;color:var(--blue);margin-bottom:8px">🔵 좌레그 (L)</div>'
      + '<div style="font-size:22px;font-weight:900;font-family:\'JetBrains Mono\',monospace;color:var(--blue)">' + fmt(legL) + '</div>'
      + '<div style="font-size:10px;color:var(--t3);margin-top:2px">PV</div>'
      + '<div style="margin-top:10px;display:flex;flex-direction:column;gap:4px">'
      + '<div style="display:flex;justify-content:space-between;font-size:10px">'
      +   '<span style="color:var(--t3)">이전 이월</span><span style="font-family:\'JetBrains Mono\',monospace;color:var(--blue)">' + fmt(carryL) + '</span></div>'
      + '<div style="display:flex;justify-content:space-between;font-size:10px">'
      +   '<span style="color:var(--t3)">이번 주 신규</span><span style="font-family:\'JetBrains Mono\',monospace;color:var(--blue);font-weight:700">+' + fmt(newL) + '</span></div>'
      + '</div>'
      + '<div style="margin-top:8px;height:5px;background:var(--s3);border-radius:3px;overflow:hidden">'
      + '<div style="width:' + (legL + legR > 0 ? Math.round(legL/(legL+legR)*100) : 0) + '%;height:100%;background:var(--blue);border-radius:3px"></div>'
      + '</div>'
      + '<div style="font-size:9px;color:var(--t3);margin-top:4px;text-align:right">👆 클릭하여 기여 회원 보기</div>'
      + '</div>';

    // 우레그 카드
    html += '<div onclick="_cwToggleLegDetail(\'' + binId + '-R\')"'
      + ' style="cursor:pointer;background:rgba(198,40,40,.05);border:2px solid ' + (!isLBig ? 'rgba(198,40,40,.4)' : 'rgba(198,40,40,.2)') + ';border-radius:12px;padding:14px;position:relative;transition:all .2s"'
      + ' onmouseover="this.style.background=\'rgba(198,40,40,.1)\'" onmouseout="this.style.background=\'rgba(198,40,40,.05)\'">'
      + (!isLBig ? '<div style="position:absolute;top:8px;right:8px;background:var(--red);color:#fff;font-size:9px;font-weight:700;padding:2px 7px;border-radius:5px">대레그 ▲</div>' : '')
      + '<div style="font-size:11px;font-weight:700;color:var(--red);margin-bottom:8px">🔴 우레그 (R)</div>'
      + '<div style="font-size:22px;font-weight:900;font-family:\'JetBrains Mono\',monospace;color:var(--red)">' + fmt(legR) + '</div>'
      + '<div style="font-size:10px;color:var(--t3);margin-top:2px">PV</div>'
      + '<div style="margin-top:10px;display:flex;flex-direction:column;gap:4px">'
      + '<div style="display:flex;justify-content:space-between;font-size:10px">'
      +   '<span style="color:var(--t3)">이전 이월</span><span style="font-family:\'JetBrains Mono\',monospace;color:var(--red)">' + fmt(carryR) + '</span></div>'
      + '<div style="display:flex;justify-content:space-between;font-size:10px">'
      +   '<span style="color:var(--t3)">이번 주 신규</span><span style="font-family:\'JetBrains Mono\',monospace;color:var(--red);font-weight:700">+' + fmt(newR) + '</span></div>'
      + '</div>'
      + '<div style="margin-top:8px;height:5px;background:var(--s3);border-radius:3px;overflow:hidden">'
      + '<div style="width:' + (legL + legR > 0 ? Math.round(legR/(legL+legR)*100) : 0) + '%;height:100%;background:var(--red);border-radius:3px"></div>'
      + '</div>'
      + '<div style="font-size:9px;color:var(--t3);margin-top:4px;text-align:right">👆 클릭하여 기여 회원 보기</div>'
      + '</div>';
    html += '</div>';

    // 좌레그 기여 회원 상세 (숨김 토글)
    html += '<div id="' + binId + '-L" style="display:none;margin-bottom:8px">';
    html += _cwLegMemberDetail(no, 'L', weekVal, newL, carryL);
    html += '</div>';

    // 우레그 기여 회원 상세 (숨김 토글)
    html += '<div id="' + binId + '-R" style="display:none;margin-bottom:8px">';
    html += _cwLegMemberDetail(no, 'R', weekVal, newR, carryR);
    html += '</div>';

    // 계산 결과 + 다음 주 이월
    html += '<div style="background:var(--s1);border:1px solid var(--bd);border-radius:12px;overflow:hidden;margin-bottom:8px">';
    // 계산 행
    html += '<div style="padding:12px 14px;border-bottom:1px solid var(--bd)">';
    html += '<div style="font-size:11px;font-weight:700;color:var(--t2);margin-bottom:8px">⚙️ 수당 계산</div>';
    html += '<div style="display:flex;flex-direction:column;gap:5px;font-size:11px">';
    html += '<div style="display:flex;justify-content:space-between">'
      + '<span style="color:var(--t3)">소실적 (소레그)</span>'
      + '<span style="font-family:\'JetBrains Mono\',monospace;font-weight:700;color:var(--amber)">' + fmt(small) + ' PV</span></div>';
    html += '<div style="display:flex;justify-content:space-between">'
      + '<span style="color:var(--t3)">× 10%</span>'
      + '<span style="font-family:\'JetBrains Mono\',monospace">' + fmtW(rawAmt) + '</span></div>';
    if (isCap) html += '<div style="display:flex;justify-content:space-between">'
      + '<span style="color:var(--amber)">CAP 절삭 (' + fmtW(cap) + ')</span>'
      + '<span style="font-family:\'JetBrains Mono\',monospace;color:var(--amber)">' + fmtW(capAmt) + '</span></div>';
    if (isRatio) html += '<div style="display:flex;justify-content:space-between">'
      + '<span style="color:var(--amber)">프로라타 (' + Math.round(ratio*100) + '%)</span>'
      + '<span style="font-family:\'JetBrains Mono\',monospace;color:var(--amber)">' + fmtW(finalAmt) + '</span></div>';
    html += '<div style="display:flex;justify-content:space-between;padding-top:6px;border-top:1px solid var(--bd);font-weight:900">'
      + '<span>최종 지급액</span>'
      + '<span style="font-family:\'JetBrains Mono\',monospace;color:var(--amber);font-size:14px">' + fmtW(finalAmt) + '</span></div>';
    html += '</div></div>';
    // 다음 주 이월
    html += '<div style="padding:12px 14px;background:rgba(0,0,0,.02)">';
    html += '<div style="font-size:11px;font-weight:700;color:var(--t2);margin-bottom:8px">📦 다음 주 이월 PV</div>';
    html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">';
    html += '<div style="text-align:center;background:rgba(26,86,219,.06);border:1px solid rgba(26,86,219,.2);border-radius:8px;padding:10px">'
      + '<div style="font-size:10px;color:var(--t3);margin-bottom:4px">🔵 좌레그 이월</div>'
      + '<div style="font-size:16px;font-weight:900;font-family:\'JetBrains Mono\',monospace;color:var(--blue)">' + fmt(nextCarryL) + '</div>'
      + '<div style="font-size:9px;color:var(--t3)">PV</div></div>';
    html += '<div style="text-align:center;background:rgba(198,40,40,.06);border:1px solid rgba(198,40,40,.2);border-radius:8px;padding:10px">'
      + '<div style="font-size:10px;color:var(--t3);margin-bottom:4px">🔴 우레그 이월</div>'
      + '<div style="font-size:16px;font-weight:900;font-family:\'JetBrains Mono\',monospace;color:var(--red)">' + fmt(nextCarryR) + '</div>'
      + '<div style="font-size:9px;color:var(--t3)">PV</div></div>';
    html += '</div>';
    if (nextCarryL > 0 || nextCarryR > 0) {
      html += '<div style="margin-top:8px;font-size:10px;color:var(--t3);text-align:center">'
        + '💡 ' + (nextCarryL > nextCarryR ? '좌' : '우') + '레그 ' + fmt(Math.max(nextCarryL, nextCarryR)) + ' PV가 다음 주로 이월됩니다.</div>';
    }
    html += '</div></div>';
    html += '</div>';
  }

  // ── 합계 footer ──
  html += '<div style="padding:12px 16px;background:var(--s2);border-radius:10px;display:flex;justify-content:space-between;align-items:center">'
    + '<span style="font-size:13px;font-weight:700">💰 주지급 수당 합계</span>'
    + '<span style="font-size:22px;font-weight:900;font-family:\'JetBrains Mono\',monospace;color:var(--green)">' + fmtW(total) + '</span>'
    + '</div>';

  html += '</div></div>';  // padding div + card div
  document.getElementById('cw-detail-wrap').innerHTML = html;
}

// ── 헬퍼: 섹션 헤더 (제목 + 소계) ──
function _cwSecHdr(label, color, amt) {
  return '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;padding-bottom:6px;border-bottom:2px solid ' + color + '33">'
    + '<div style="font-size:12px;font-weight:700;color:' + color + '">' + label + '</div>'
    + '<div style="font-size:14px;font-weight:900;font-family:\'JetBrains Mono\',monospace;color:' + color + '">' + fmtW(amt||0) + '</div>'
    + '</div>';
}

// ── 헬퍼: 바이너리 정보 셀 ──
function _cwInfoCell(label, val, color) {
  return '<div style="text-align:center;padding:10px 6px;background:var(--s1);border-radius:8px;border:1px solid var(--bd)">'
    + '<div style="font-size:10px;color:var(--t3);margin-bottom:4px">' + label + '</div>'
    + '<div style="font-size:14px;font-weight:900;font-family:\'JetBrains Mono\',monospace;color:' + color + '">' + val + '</div>'
    + '</div>';
}

// ── 헬퍼: 바이너리 계산 행 (3열) ──
function _cwCalcRow3(label, val, desc, color) {
  return '<tr>'
    + '<td style="padding:7px 10px;border-bottom:1px solid var(--bd)">' + label + '</td>'
    + '<td style="padding:7px 10px;border-bottom:1px solid var(--bd);text-align:right;font-weight:700;font-family:\'JetBrains Mono\',monospace;color:' + (color||'var(--t1)') + '">' + val + '</td>'
    + '<td style="padding:7px 10px;border-bottom:1px solid var(--bd);font-size:10px;color:var(--t3)">' + desc + '</td>'
    + '</tr>';
}
</script>

<script>
// ══════════════════════════════════════════════════════
// 바이너리 레그 상세: 토글 + 기여 회원 목록
// ══════════════════════════════════════════════════════

function _cwToggleLegDetail(id) {
  const el = document.getElementById(id);
  if (!el) return;
  const isOpen = el.style.display !== 'none';
  el.style.display = isOpen ? 'none' : 'block';
}

// 해당 회원(no)의 특정 레그(side='L'|'R')에 이번 주 기여한 매출 목록을 반환
function _cwGetLegContribs(no, side, weekVal) {
  const wSales = filterSalesByPeriod(S.sales, weekVal, 'week');
  const wQual  = buildQualifiedForWeek(weekVal, S.sales);
  const result = []; // { buyerNo, buyerName, date, pv, amount }

  wSales.forEach(s => {
    const buyNo = findMemberNo(s);
    if (!buyNo) return;
    const pv = parseInt(s.pv) || 0;
    if (!pv) return;
    const saleDate = s.order_date || '';

    // 후원 라인 타고 올라가면서 no에 도달할 때 pos 확인
    let cur = S.members[buyNo]?.sponsor_no;
    let pos = S.members[buyNo]?.position || 'L';
    while (cur && S.members[cur]) {
      if (cur === no) {
        // qualDate 체크
        if (wQual[no]) {
          const qd = wQual[no].qualDate || '';
          if (qd && saleDate < qd) break;
        }
        if (pos === side) {
          result.push({
            buyerNo:   buyNo,
            buyerName: S.members[buyNo]?.name || buyNo,
            loginId:   S.members[buyNo]?.login_id || '',
            date:      saleDate,
            pv:        pv,
            amount:    parseInt(s.amount) || 0,
          });
        }
        break;
      }
      pos = S.members[cur]?.position || 'L';
      cur = S.members[cur]?.sponsor_no;
    }
  });

  // 날짜순 정렬
  result.sort((a, b) => (a.date < b.date ? -1 : a.date > b.date ? 1 : 0));
  return result;
}

// 레그 기여 회원 상세 HTML 생성
function _cwLegMemberDetail(no, side, weekVal, newPV, carryPV) {
  const color     = side === 'L' ? 'var(--blue)' : 'var(--red)';
  const colorRgb  = side === 'L' ? '26,86,219' : '198,40,40';
  const sideLabel = side === 'L' ? '🔵 좌레그' : '🔴 우레그';
  const contribs  = _cwGetLegContribs(no, side, weekVal);
  const totalNewPV = contribs.reduce((a, c) => a + c.pv, 0);

  let html = '<div style="background:rgba(' + colorRgb + ',.04);border:1px solid rgba(' + colorRgb + ',.2);'
    + 'border-radius:10px;overflow:hidden;margin-top:4px">';

  // 헤더
  html += '<div style="padding:10px 14px;background:rgba(' + colorRgb + ',.08);border-bottom:1px solid rgba(' + colorRgb + ',.2);'
    + 'display:flex;align-items:center;justify-content:space-between">'
    + '<div style="font-size:11px;font-weight:700;color:' + color + '">' + sideLabel + ' 이번 주 기여 회원</div>'
    + '<div style="font-size:11px;font-weight:900;font-family:\'JetBrains Mono\',monospace;color:' + color + '">'
    +   fmt(totalNewPV) + ' PV</div>'
    + '</div>';

  if (contribs.length === 0) {
    // 신규 없음 - 이월분만 있는 경우
    html += '<div style="padding:14px;text-align:center;font-size:11px;color:var(--t3)">'
      + '이번 주 신규 매출 없음'
      + (carryPV > 0 ? '<br><span style="color:' + color + ';font-weight:700">이전 주 이월 ' + fmt(carryPV) + ' PV</span>가 합산됨' : '')
      + '</div>';
  } else {
    // 이월 PV 표시 배너 (있을 때만)
    if (carryPV > 0) {
      html += '<div style="padding:8px 14px;background:rgba(' + colorRgb + ',.06);border-bottom:1px solid rgba(' + colorRgb + ',.15);'
        + 'display:flex;align-items:center;justify-content:space-between;font-size:10px">'
        + '<span style="color:var(--t3)">📦 이전 주 이월 포함</span>'
        + '<span style="font-family:\'JetBrains Mono\',monospace;font-weight:700;color:' + color + '">+' + fmt(carryPV) + ' PV</span>'
        + '</div>';
    }

    // 기여 회원 테이블
    html += '<div class="tw"><table>'
      + '<thead><tr style="background:rgba(' + colorRgb + ',.06)">'
      + '<th style="padding:6px 10px;text-align:left;font-size:10px">날짜</th>'
      + '<th style="padding:6px 10px;text-align:left;font-size:10px">회원</th>'
      + '<th style="padding:6px 10px;text-align:right;font-size:10px">매출금액</th>'
      + '<th style="padding:6px 10px;text-align:right;font-size:10px">PV</th>'
      + '<th style="padding:6px 10px;font-size:10px;min-width:80px"></th>'
      + '</tr></thead><tbody>';

    let runningPV = carryPV;
    contribs.forEach((c, i) => {
      runningPV += c.pv;
      const barPct = totalNewPV > 0 ? Math.round(c.pv / totalNewPV * 100) : 0;
      html += '<tr style="border-bottom:1px solid rgba(' + colorRgb + ',.08)">'
        + '<td style="padding:7px 10px;font-size:10px;color:var(--t3);white-space:nowrap">' + c.date + '</td>'
        + '<td style="padding:7px 10px">'
        +   '<div style="font-size:11px;font-weight:700">' + c.buyerName + '</div>'
        +   '<div style="font-size:9px;color:var(--t3)">' + c.buyerNo
        +     (c.loginId ? ' · ' + c.loginId : '') + '</div>'
        + '</td>'
        + '<td style="padding:7px 10px;text-align:right;font-size:11px;font-family:\'JetBrains Mono\',monospace;color:var(--t2)">'
        +   (c.amount > 0 ? fmtW(c.amount) : '-') + '</td>'
        + '<td style="padding:7px 10px;text-align:right;font-size:11px;font-family:\'JetBrains Mono\',monospace;font-weight:700;color:' + color + '">'
        +   fmt(c.pv) + '</td>'
        + '<td style="padding:7px 10px">'
        +   '<div style="height:5px;background:var(--s3);border-radius:3px;overflow:hidden">'
        +   '<div style="width:' + barPct + '%;height:100%;background:' + color + ';border-radius:3px"></div>'
        +   '</div>'
        +   '<div style="font-size:9px;color:var(--t3);text-align:right;margin-top:2px">' + barPct + '%</div>'
        + '</td>'
        + '</tr>';
    });

    // 합계 행
    html += '<tr style="background:rgba(' + colorRgb + ',.06);font-weight:900">'
      + '<td colspan="3" style="padding:8px 10px;font-size:11px">이번 주 신규 합계</td>'
      + '<td style="padding:8px 10px;text-align:right;font-family:\'JetBrains Mono\',monospace;color:' + color + '">'
      +   fmt(totalNewPV) + '</td>'
      + '<td></td>'
      + '</tr>';
    if (carryPV > 0) {
      html += '<tr style="background:rgba(' + colorRgb + ',.03)">'
        + '<td colspan="3" style="padding:6px 10px;font-size:11px;color:var(--t3)">이전 주 이월</td>'
        + '<td style="padding:6px 10px;text-align:right;font-family:\'JetBrains Mono\',monospace;font-size:11px;color:' + color + '">'
        +   fmt(carryPV) + '</td>'
        + '<td></td>'
        + '</tr>';
      html += '<tr style="background:rgba(' + colorRgb + ',.08);font-weight:900">'
        + '<td colspan="3" style="padding:8px 10px;font-size:12px">총 레그 PV</td>'
        + '<td style="padding:8px 10px;text-align:right;font-family:\'JetBrains Mono\',monospace;font-size:13px;color:' + color + '">'
        +   fmt(totalNewPV + carryPV) + '</td>'
        + '<td></td>'
        + '</tr>';
    }

    html += '</tbody></table></div>';
  }

  html += '</div>';
  return html;
}
</script>
