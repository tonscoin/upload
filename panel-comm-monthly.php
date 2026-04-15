<?php /* panels/panel-comm-monthly.php — 월지급 수당 */ ?>
<div class="panel" id="p-comm-monthly">

  <div class="card" style="margin-bottom:14px">
    <div class="card-hd">
      🗓️ 월지급 수당 조회
      <span style="font-size:11px;color:var(--t3);font-weight:400">직급수당 · 로또보너스 · 직추재구매수당</span>
    </div>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
      <input type="month" id="cm-month" value="<?= date('Y-m') ?>"
        style="background:var(--s1);border:1px solid var(--bd);color:var(--t1);padding:6px 10px;border-radius:8px;font-size:11px;font-family:inherit;outline:none">
      <input class="srch" id="cm-search" placeholder="이름·ID·회원번호 검색" style="width:200px"
        oninput="cmFilterList(this.value)">
      <button class="btn bp" onclick="loadCommMonthly()">🔍 월지급 수당 조회</button>
    </div>
  </div>

  <!-- KPI -->
  <div id="cm-kpi-row" style="display:none;margin-bottom:14px">
    <!-- 1행: 기간/총액/인원 -->
    <div class="stat-g" style="margin-bottom:10px">
      <div class="stat"><div class="stat-lbl">정산 기간</div><div class="stat-val" id="cm-kpi-period" style="font-size:16px;color:var(--green)">-</div></div>
      <div class="stat"><div class="stat-lbl">총 월지급 수당</div><div class="stat-val mono" id="cm-kpi-total" style="color:var(--green)">-</div><div class="stat-sub">전체 합산</div></div>
      <div class="stat"><div class="stat-lbl">수당 수령자</div><div class="stat-val" id="cm-kpi-count" style="color:var(--blue)">-</div><div class="stat-sub">명</div></div>
    </div>
    <!-- 2행: 수당 구성별 합계 바 -->
    <div style="background:var(--s1);border:1px solid var(--bd);border-radius:12px;padding:14px 18px">
      <div style="font-size:11px;font-weight:700;color:var(--t3);margin-bottom:10px">📊 수당 구성별 합계</div>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px">
        <!-- 직급수당 -->
        <div style="background:rgba(198,40,40,.06);border:1px solid rgba(198,40,40,.15);border-radius:10px;padding:12px 14px">
          <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px">
            <span style="font-size:13px">👑</span>
            <span style="font-size:11px;font-weight:700;color:var(--red)">직급수당</span>
          </div>
          <div class="mono" id="cm-kpi-rank" style="font-size:18px;font-weight:900;color:var(--red)">-</div>
          <div style="margin-top:6px;height:4px;background:var(--s3);border-radius:2px;overflow:hidden">
            <div id="cm-kpi-rank-bar" style="height:100%;background:var(--red);border-radius:2px;width:0%;transition:width .4s"></div>
          </div>
          <div id="cm-kpi-rank-pct" style="font-size:10px;color:var(--t3);margin-top:3px;text-align:right">0%</div>
        </div>
        <!-- 로또보너스 -->
        <div style="background:rgba(173,20,87,.06);border:1px solid rgba(173,20,87,.15);border-radius:10px;padding:12px 14px">
          <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px">
            <span style="font-size:13px">🎰</span>
            <span style="font-size:11px;font-weight:700;color:var(--rose)">로또보너스</span>
          </div>
          <div class="mono" id="cm-kpi-lotto" style="font-size:18px;font-weight:900;color:var(--rose)">-</div>
          <div style="margin-top:6px;height:4px;background:var(--s3);border-radius:2px;overflow:hidden">
            <div id="cm-kpi-lotto-bar" style="height:100%;background:var(--rose);border-radius:2px;width:0%;transition:width .4s"></div>
          </div>
          <div id="cm-kpi-lotto-pct" style="font-size:10px;color:var(--t3);margin-top:3px;text-align:right">0%</div>
        </div>
        <!-- 직추재구매수당 -->
        <div style="background:rgba(0,105,92,.06);border:1px solid rgba(0,105,92,.15);border-radius:10px;padding:12px 14px">
          <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px">
            <span style="font-size:13px">🔄</span>
            <span style="font-size:11px;font-weight:700;color:var(--teal)">직추재구매수당</span>
          </div>
          <div class="mono" id="cm-kpi-repurch" style="font-size:18px;font-weight:900;color:var(--teal)">-</div>
          <div style="margin-top:6px;height:4px;background:var(--s3);border-radius:2px;overflow:hidden">
            <div id="cm-kpi-repurch-bar" style="height:100%;background:var(--teal);border-radius:2px;width:0%;transition:width .4s"></div>
          </div>
          <div id="cm-kpi-repurch-pct" style="font-size:10px;color:var(--t3);margin-top:3px;text-align:right">0%</div>
        </div>
      </div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:340px 1fr;gap:14px;align-items:flex-start">

    <!-- 왼쪽: 회원 목록 -->
    <div class="card" style="padding:0;overflow:hidden">
      <div style="padding:10px 14px;background:var(--s2);border-bottom:1px solid var(--bd);display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <span style="font-size:12px;font-weight:700">💰 수당 발생 회원</span>
        <span id="cm-list-cnt" style="font-size:11px;color:var(--t3)"></span>
        <div style="margin-left:auto;display:flex;gap:3px;background:var(--s3);border-radius:6px;padding:2px">
          <button id="cm-sort-amt" class="btn bo on" style="padding:2px 8px;font-size:10px;border-radius:5px" onclick="cmSortList('amt')">금액순</button>
          <button id="cm-sort-abc" class="btn bo"    style="padding:2px 8px;font-size:10px;border-radius:5px" onclick="cmSortList('abc')">가나다순</button>
        </div>
      </div>
      <div id="cm-member-list" style="max-height:600px;overflow-y:auto">
        <div class="empty-msg" style="padding:30px">기간 선택 후 [월지급 수당 조회] 버튼을 누르세요.</div>
      </div>
    </div>

    <!-- 오른쪽: 상세 -->
    <div id="cm-detail-wrap">
      <div class="card" style="display:flex;align-items:center;justify-content:center;min-height:300px">
        <div style="text-align:center;color:var(--t3)">
          <div style="font-size:40px;margin-bottom:12px">🗓️</div>
          <div style="font-size:13px;font-weight:700">회원을 클릭하면</div>
          <div style="font-size:11px;margin-top:4px">월지급 수당 상세 내역이 표시됩니다</div>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
var _cmAllRows = [];
var _cmSelected = null;
var _cmData = null;
var _cmSort = 'amt';

async function loadCommMonthly() {
  var monthVal = document.getElementById('cm-month').value;
  if (!monthVal) return;

  document.getElementById('cm-member-list').innerHTML = '<div class="spin"></div>';
  document.getElementById('cm-detail-wrap').innerHTML = '<div class="card" style="min-height:200px"><div class="spin"></div></div>';

  await ensureData(monthVal);
  var data = calcAllComm(monthVal);
  _cmData = data;

  var rows = (data.data || []).map(function(r) {
    var rank    = r.rank    || 0;
    var lotto   = r.lotto   || 0;
    var repurch = r.repurch || 0;
    var total   = rank + lotto + repurch;
    if (total <= 0) return null;
    return Object.assign({}, r, { rank:rank, lotto:lotto, repurch:repurch, total:total });
  }).filter(Boolean);

  _cmAllRows  = rows;
  _cmSelected = null;

  var _cmTotal   = rows.reduce(function(a,r){ return a+r.total; }, 0);
  var _cmRankSum = rows.reduce(function(a,r){ return a+(r.rank||0); }, 0);
  var _cmLotto   = rows.reduce(function(a,r){ return a+(r.lotto||0); }, 0);
  var _cmRepurch = rows.reduce(function(a,r){ return a+(r.repurch||0); }, 0);

  document.getElementById('cm-kpi-row').style.display = 'block';
  document.getElementById('cm-kpi-period').textContent  = monthVal;
  document.getElementById('cm-kpi-total').textContent   = fmtW(_cmTotal);
  document.getElementById('cm-kpi-count').textContent   = rows.length + '명';
  document.getElementById('cm-kpi-rank').textContent    = fmtW(_cmRankSum);
  document.getElementById('cm-kpi-lotto').textContent   = fmtW(_cmLotto);
  document.getElementById('cm-kpi-repurch').textContent = fmtW(_cmRepurch);

  requestAnimationFrame(function() {
    function _cmPct(v) { return _cmTotal > 0 ? Math.round(v/_cmTotal*100) : 0; }
    document.getElementById('cm-kpi-rank-bar').style.width    = _cmPct(_cmRankSum) + '%';
    document.getElementById('cm-kpi-lotto-bar').style.width   = _cmPct(_cmLotto)   + '%';
    document.getElementById('cm-kpi-repurch-bar').style.width = _cmPct(_cmRepurch) + '%';
    document.getElementById('cm-kpi-rank-pct').textContent    = _cmPct(_cmRankSum) + '%';
    document.getElementById('cm-kpi-lotto-pct').textContent   = _cmPct(_cmLotto)   + '%';
    document.getElementById('cm-kpi-repurch-pct').textContent = _cmPct(_cmRepurch) + '%';
  });

  cmRenderList(rows);
  document.getElementById('cm-detail-wrap').innerHTML =
    '<div class="card" style="display:flex;align-items:center;justify-content:center;min-height:200px">'
    + '<div style="text-align:center;color:var(--t3)">'
    + '<div style="font-size:32px;margin-bottom:8px">👆</div>'
    + '<div style="font-size:12px">왼쪽 목록에서 회원을 클릭하세요</div>'
    + '</div></div>';
}

function cmFilterList(q) {
  if (!_cmAllRows.length) return;
  var filtered = q
    ? _cmAllRows.filter(function(r){ return (r.name||'').includes(q)||(r.member_no||'').includes(q)||(r.login_id||'').includes(q); })
    : _cmAllRows;
  cmRenderList(filtered);
}

function cmSortList(mode) {
  _cmSort = mode;
  ['amt','abc'].forEach(function(m) {
    var btn = document.getElementById('cm-sort-' + m);
    if (btn) btn.classList.toggle('on', m === mode);
  });
  cmRenderList(_cmAllRows);
}

function cmRenderList(rows) {
  var sorted = rows.slice();
  if (_cmSort === 'abc') sorted.sort(function(a,b){ return (a.name||'').localeCompare(b.name||'','ko'); });
  else sorted.sort(function(a,b){ return b.total - a.total; });

  document.getElementById('cm-list-cnt').textContent = sorted.length + '명';
  if (!sorted.length) {
    document.getElementById('cm-member-list').innerHTML = '<div class="empty-msg" style="padding:20px">월지급 수당 발생 회원 없음</div>';
    return;
  }
  var maxTotal = Math.max.apply(null, sorted.map(function(r){ return r.total; }).concat([1]));
  document.getElementById('cm-member-list').innerHTML = sorted.map(function(r) {
    var isSel   = _cmSelected && _cmSelected.member_no === r.member_no;
    var loginId = (S.members[r.member_no]||{}).login_id || '';
    return '<div id="cm-item-' + r.member_no + '" onclick="cmSelectMember(\'' + r.member_no + '\')"'
      + ' style="padding:10px 14px;cursor:pointer;border-bottom:1px solid var(--bd);'
      + (isSel ? 'background:rgba(46,125,50,.08);border-left:3px solid var(--green)' : 'border-left:3px solid transparent')
      + '">'
      + '<div style="display:flex;align-items:center;gap:8px;margin-bottom:5px">'
      + '<span style="font-size:12px;font-weight:700">' + (r.name||r.member_no) + '</span>'
      + '<span class="gb g' + r.grade + '" style="font-size:9px">' + r.grade + '</span>'
      + '<span style="font-size:9px;color:var(--t3);margin-left:4px">' + (r.member_no||'') + '</span>'
      + '<span style="font-size:9px;color:var(--t3);margin-left:2px">(' + loginId + ')</span>'
      + '<span style="margin-left:auto;font-size:12px;font-weight:900;color:var(--green)">' + fmtW(r.total) + '</span>'
      + '</div>'
      + '<div style="height:4px;background:var(--s3);border-radius:2px">'
      + '<div style="width:' + Math.round(r.total/maxTotal*100) + '%;height:100%;background:linear-gradient(90deg,#2e7d32,#66bb6a);border-radius:2px"></div>'
      + '</div>'
      + '<div style="display:flex;gap:6px;margin-top:4px;flex-wrap:wrap">'
      + (r.rank    ? '<span style="font-size:9px;color:var(--red)">직급 '    + fmtW(r.rank)    + '</span>' : '')
      + (r.lotto   ? '<span style="font-size:9px;color:var(--rose)">로또 '   + fmtW(r.lotto)   + '</span>' : '')
      + (r.repurch ? '<span style="font-size:9px;color:var(--teal)">재구매 ' + fmtW(r.repurch) + '</span>' : '')
      + '</div></div>';
  }).join('');
}

function cmSelectMember(no) {
  var found = null;
  for (var i = 0; i < _cmAllRows.length; i++) {
    if (_cmAllRows[i].member_no === no) { found = _cmAllRows[i]; break; }
  }
  if (!found) return;
  _cmSelected = found;

  // 선택 표시: id 기반으로 안전하게 처리
  document.querySelectorAll('#cm-member-list > div').forEach(function(el) {
    if (el.id === 'cm-item-' + no) {
      el.style.background = 'rgba(46,125,50,.08)';
      el.style.borderLeft = '3px solid var(--green)';
    } else {
      el.style.background = '';
      el.style.borderLeft = '3px solid transparent';
    }
  });

  cmRenderDetail(no);
}

// ─────────────────────────────────────────────────────
//  cmRenderDetail : 월지급 수당 상세
// ─────────────────────────────────────────────────────
function cmRenderDetail(no) {
  var r = null;
  for (var i = 0; i < _cmAllRows.length; i++) {
    if (_cmAllRows[i].member_no === no) { r = _cmAllRows[i]; break; }
  }
  if (!r) {
    document.getElementById('cm-detail-wrap').innerHTML =
      '<div class="card"><div class="empty-msg">회원 데이터를 찾을 수 없습니다.</div></div>';
    return;
  }

  var m        = S.members[no] || {};
  var monthVal = document.getElementById('cm-month').value;
  var data     = _cmData || {};
  var pvMap    = data.pvMap    || {};
  var legPV    = data.legPV    || {};
  var rankMems = data.rankMems || {};
  var lp       = legPV[no]    || { L:0, R:0 };
  var totalPV  = Object.values(pvMap).reduce(function(a,b){ return a+b; }, 0);
  var myRank   = calcRankJs(lp);
  var lesser   = Math.min(lp.L, lp.R);

  var rankAmt    = r.rank    || 0;
  var lottoAmt   = r.lotto   || 0;
  var repurchAmt = r.repurch || 0;
  var total      = rankAmt + lottoAmt + repurchAmt;

  // ── 헤더 배너 ──
  var html = '<div class="card" style="padding:0;overflow:hidden">'
    + '<div style="padding:14px 18px;background:linear-gradient(135deg,#2e7d32,#66bb6a);color:#fff">'
    + '<div style="display:flex;align-items:center;gap:12px">'
    + '<div style="width:44px;height:44px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:20px">🗓️</div>'
    + '<div>'
    + '<div style="font-size:16px;font-weight:900">' + (m.name||no) + '</div>'
    + '<div style="font-size:11px;opacity:.85;margin-top:2px">'
    + (m.login_id ? m.login_id + ' · ' : '') + (m.member_no||no) + ' · ' + monthVal + ' 월지급 수당'
    + '</div></div>'
    + '<div style="margin-left:auto;text-align:right">'
    + '<div style="font-size:11px;opacity:.8">총 월지급</div>'
    + '<div style="font-size:24px;font-weight:900;font-family:\'JetBrains Mono\',monospace">' + fmtW(total) + '</div>'
    + '</div></div></div>';

  // ── 3칸 요약 ──
  var summaryItems = [
    { label:'👑 직급수당',   color:'var(--red)',  val:rankAmt,    desc:'직급 풀 배분' },
    { label:'🎰 로또보너스', color:'#e91e63',     val:lottoAmt,   desc:'PV 3% 풀·점수배분' },
    { label:'🔄 직추재구매', color:'var(--teal)', val:repurchAmt, desc:'직추천 재구매 PV × 3%' },
  ];
  html += '<div style="display:grid;grid-template-columns:repeat(3,1fr);border-bottom:1px solid var(--bd)">';
  summaryItems.forEach(function(it) {
    var pct = total > 0 ? Math.round(it.val / total * 100) : 0;
    html += '<div style="padding:14px;text-align:center;border-right:1px solid var(--bd)">'
      + '<div style="font-size:10px;color:var(--t3);margin-bottom:4px">' + it.label + '</div>'
      + '<div style="font-size:18px;font-weight:900;color:' + it.color + ';font-family:\'JetBrains Mono\',monospace">' + fmtW(it.val) + '</div>'
      + '<div style="font-size:9px;color:var(--t3);margin-top:2px">' + it.desc + '</div>'
      + (it.val > 0 ? '<div style="font-size:10px;font-weight:700;color:' + it.color + ';opacity:.7">' + pct + '%</div>' : '')
      + '</div>';
  });
  html += '</div>';
  html += '<div style="padding:16px 18px">';

  // ══════════════════════════════════════
  // ① 직급수당 상세
  // ══════════════════════════════════════
  if (rankAmt > 0) {
    html += _cmSecHdr('👑 직급수당 발생 상세', 'var(--red)', rankAmt);

    html += '<div style="margin-bottom:10px;padding:10px 12px;background:rgba(198,40,40,.05);border:1px solid rgba(198,40,40,.15);border-radius:8px;font-size:11px">'
      + '<span style="color:var(--t3)">달성 직급: </span>'
      + (myRank !== '미달성'
          ? '<span class="gb g' + myRank + '">' + myRank + '</span>'
          : '<span style="color:var(--t3)">미달성</span>')
      + '&nbsp;·&nbsp;<span style="color:var(--t3)">소실적: </span>'
      + '<span class="mono" style="color:var(--amber);font-weight:700">' + fmt(lesser) + ' PV</span>'
      + '&nbsp;·&nbsp;<span style="color:var(--t3)">월 총 PV: </span>'
      + '<span class="mono">' + fmt(totalPV) + '</span>'
      + '</div>';

    // 어떤 직급 풀에서 얼마 받았는지
    var rankBreakdown = [];
    if (myRank !== '미달성') {
      var myRankIdx = RANK_ORDER.indexOf(myRank);
      RANK_ORDER.forEach(function(rank, poolIdx) {
        if (myRankIdx < poolIdx) return; // 달성 직급보다 높은 풀은 수령 못함
        var pool = RANK_POOL[rank]; if (!pool) return;
        var poolAmt = Math.round(totalPV * pool.rate);
        var receivers = {};
        RANK_ORDER.slice(poolIdx).forEach(function(r2) {
          (rankMems[r2]||[]).forEach(function(n){ receivers[n] = true; });
        });
        var rcvCnt = Object.keys(receivers).length;
        if (rcvCnt <= 0 || poolAmt <= 0) return;
        var perPerson  = Math.round(poolAmt / rcvCnt);
        var isAchiever = (rankMems[rank]||[]).indexOf(no) >= 0;
        rankBreakdown.push({ rank:rank, poolAmt:poolAmt, rcvCnt:rcvCnt, perPerson:perPerson, isAchiever:isAchiever });
      });
    }

    if (rankBreakdown.length) {
      html += '<div class="tw" style="margin-bottom:20px"><table>'
        + '<thead><tr style="background:rgba(198,40,40,.07)">'
        + '<th style="padding:7px 10px;text-align:left">직급 풀</th>'
        + '<th style="padding:7px 10px;text-align:right">풀 총액</th>'
        + '<th style="padding:7px 10px;text-align:center">수령 인원</th>'
        + '<th style="padding:7px 10px;text-align:center">구분</th>'
        + '<th style="padding:7px 10px;text-align:right">1인당</th>'
        + '</tr></thead><tbody>';
      rankBreakdown.forEach(function(rb) {
        html += '<tr>'
          + '<td style="padding:7px 10px"><span class="gb g' + rb.rank + '">' + rb.rank + '</span></td>'
          + '<td style="padding:7px 10px;text-align:right;font-family:\'JetBrains Mono\',monospace;color:var(--t2)">' + fmtW(rb.poolAmt) + '</td>'
          + '<td style="padding:7px 10px;text-align:center;color:var(--t3)">' + rb.rcvCnt + '명</td>'
          + '<td style="padding:7px 10px;text-align:center"><span style="font-size:10px;padding:2px 8px;border-radius:5px;background:'
          + (rb.isAchiever ? 'rgba(46,125,50,.12);color:var(--green)' : 'rgba(26,86,219,.08);color:var(--blue)')
          + '">' + (rb.isAchiever ? '달성' : '중복수령') + '</span></td>'
          + '<td style="padding:7px 10px;text-align:right;font-weight:900;font-family:\'JetBrains Mono\',monospace;color:var(--red)">' + fmtW(rb.perPerson) + '</td>'
          + '</tr>';
      });
      html += '<tr style="background:var(--s2);font-weight:900">'
        + '<td colspan="4" style="padding:8px 10px;font-size:12px">합계</td>'
        + '<td style="padding:8px 10px;text-align:right;font-size:13px;font-family:\'JetBrains Mono\',monospace;color:var(--red)">' + fmtW(rankAmt) + '</td>'
        + '</tr></tbody></table></div>';
    } else {
      html += '<div style="color:var(--t3);font-size:11px;padding:8px 0">직급 풀 내역을 계산할 수 없습니다.</div>';
    }
  }

  // ══════════════════════════════════════
  // ② 로또보너스 상세
  // ══════════════════════════════════════
  if (lottoAmt > 0) {
    html += _cmSecHdr('🎰 로또보너스 발생 상세', '#e91e63', lottoAmt);

    var lottoPool   = data.lottoPool   || 0;
    var lottoScores = data.lottoScores || {};
    var myScore     = lottoScores[no]  || 0;
    var totalScore  = Object.values(lottoScores).reduce(function(a,b){ return a+b; }, 0);

    var lottoBuyers = [];
    var monthSales  = S.sales.filter(function(s){ return (s.order_date||'').startsWith(monthVal); });
    monthSales.forEach(function(s) {
      var buyNo = findMemberNo(s);
      if (!buyNo) return;
      if ((S.members[buyNo]||{}).referrer_no !== no) return;
      if ((parseInt(s.amount)||0) >= 990000) {
        lottoBuyers.push({ name:(S.members[buyNo]||{}).name||buyNo, date:s.order_date||'', amount:parseInt(s.amount)||0 });
      }
    });
    lottoBuyers.sort(function(a,b){ return (a.date||'').localeCompare(b.date||''); });

    html += '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:12px">'
      + _cmInfoCell('🎯 99만원↑ 건수',  lottoBuyers.length + '건', '#e91e63')
      + _cmInfoCell('⭐ 획득 점수',      myScore + '점',           'var(--purple)')
      + _cmInfoCell('📊 전체 점수',      totalScore + '점',         'var(--t2)')
      + _cmInfoCell('💰 로또 풀',        fmtW(lottoPool),           'var(--rose)')
      + '</div>';

    html += '<div style="margin-bottom:10px;padding:8px 12px;background:rgba(173,20,87,.05);border:1px solid rgba(173,20,87,.15);border-radius:8px;font-size:11px">'
      + fmtW(lottoPool) + ' × (' + myScore + '점 ÷ ' + totalScore + '점) = <b style="color:#e91e63">' + fmtW(lottoAmt) + '</b>'
      + '</div>';

    if (lottoBuyers.length) {
      html += '<div class="tw" style="margin-bottom:20px"><table>'
        + '<thead><tr style="background:rgba(173,20,87,.07)">'
        + '<th style="padding:7px 10px;text-align:left">날짜</th>'
        + '<th style="padding:7px 10px;text-align:left">구매 회원</th>'
        + '<th style="padding:7px 10px;text-align:right">구매금액</th>'
        + '</tr></thead><tbody>';
      lottoBuyers.forEach(function(b) {
        html += '<tr>'
          + '<td style="padding:7px 10px;color:var(--t3);font-size:11px">' + (b.date||'-') + '</td>'
          + '<td style="padding:7px 10px;font-weight:700">' + b.name + '</td>'
          + '<td style="padding:7px 10px;text-align:right;font-family:\'JetBrains Mono\',monospace;color:#e91e63;font-weight:700">' + fmtW(b.amount) + '</td>'
          + '</tr>';
      });
      html += '<tr style="background:var(--s2);font-weight:700">'
        + '<td colspan="2" style="padding:8px 10px">총 ' + lottoBuyers.length + '건 (5건 = 1점)</td>'
        + '<td style="padding:8px 10px;text-align:right;color:#e91e63">' + fmtW(lottoAmt) + '</td>'
        + '</tr></tbody></table></div>';
    }
  }

  // ══════════════════════════════════════
  // ③ 직추재구매수당 상세
  // ══════════════════════════════════════
  if (repurchAmt > 0) {
    html += _cmSecHdr('🔄 직추재구매수당 발생 상세', 'var(--teal)', repurchAmt);

    var monthSales2   = S.sales.filter(function(s){ return (s.order_date||'').startsWith(monthVal); });
    var repurchDetails = [];
    var seenRefs       = {};

    monthSales2.filter(function(s){ return isRepurchase(s); }).forEach(function(s) {
      var buyNo = findMemberNo(s);
      if (!buyNo) return;
      if ((S.members[buyNo]||{}).referrer_no !== no) return;
      seenRefs[buyNo] = true;
      repurchDetails.push({
        name:   (S.members[buyNo]||{}).name || buyNo,
        date:   s.order_date || '',
        pv:     parseInt(s.pv) || 0,
        amount: parseInt(s.amount) || 0,
      });
    });
    repurchDetails.sort(function(a,b){ return (a.date||'').localeCompare(b.date||''); });

    var totalRepurchPV = repurchDetails.reduce(function(a,x){ return a+x.pv; }, 0);
    var activeRefCount = Object.keys(seenRefs).length;

    html += '<div style="margin-bottom:10px;padding:8px 12px;background:rgba(0,105,92,.05);border:1px solid rgba(0,105,92,.2);border-radius:8px;font-size:11px">'
      + '재구매한 직추천 수: <b style="color:var(--teal)">' + activeRefCount + '명</b>'
      + '&nbsp;·&nbsp;직추천 재구매 PV 합계: <b style="color:var(--teal)">' + fmt(totalRepurchPV) + '</b>'
      + '&nbsp;·&nbsp;' + fmt(totalRepurchPV) + ' × 3% = <b style="color:var(--teal)">' + fmtW(repurchAmt) + '</b>'
      + '</div>';

    if (repurchDetails.length) {
      html += '<div class="tw" style="margin-bottom:20px"><table>'
        + '<thead><tr style="background:rgba(0,105,92,.07)">'
        + '<th style="padding:7px 10px;text-align:left">날짜</th>'
        + '<th style="padding:7px 10px;text-align:left">구매 회원</th>'
        + '<th style="padding:7px 10px;text-align:right">PV</th>'
        + '<th style="padding:7px 10px;text-align:right">매출</th>'
        + '</tr></thead><tbody>';
      repurchDetails.forEach(function(d) {
        html += '<tr>'
          + '<td style="padding:7px 10px;color:var(--t3);font-size:11px">' + (d.date||'-') + '</td>'
          + '<td style="padding:7px 10px;font-weight:700">' + d.name + '</td>'
          + '<td style="padding:7px 10px;text-align:right;font-family:\'JetBrains Mono\',monospace;color:var(--blue)">' + fmt(d.pv) + '</td>'
          + '<td style="padding:7px 10px;text-align:right;font-family:\'JetBrains Mono\',monospace;color:var(--teal)">' + fmtW(d.amount) + '</td>'
          + '</tr>';
      });
      html += '<tr style="background:var(--s2);font-weight:700">'
        + '<td colspan="2" style="padding:8px 10px">합계 (' + activeRefCount + '명 재구매)</td>'
        + '<td style="padding:8px 10px;text-align:right;color:var(--blue)">' + fmt(totalRepurchPV) + '</td>'
        + '<td style="padding:8px 10px;text-align:right;color:var(--teal)">' + fmtW(repurchAmt) + '</td>'
        + '</tr></tbody></table></div>';
    }
  }

  // ── 합계 footer ──
  html += '<div style="padding:12px 16px;background:var(--s2);border-radius:10px;display:flex;justify-content:space-between;align-items:center">'
    + '<span style="font-size:13px;font-weight:700">💰 월지급 수당 합계</span>'
    + '<span style="font-size:22px;font-weight:900;font-family:\'JetBrains Mono\',monospace;color:var(--green)">' + fmtW(total) + '</span>'
    + '</div>';

  html += '</div></div>';
  document.getElementById('cm-detail-wrap').innerHTML = html;
}

function _cmSecHdr(label, color, amt) {
  return '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;padding-bottom:6px;border-bottom:2px solid ' + color + '33">'
    + '<div style="font-size:12px;font-weight:700;color:' + color + '">' + label + '</div>'
    + '<div style="font-size:14px;font-weight:900;font-family:\'JetBrains Mono\',monospace;color:' + color + '">' + fmtW(amt||0) + '</div>'
    + '</div>';
}

function _cmInfoCell(label, val, color) {
  return '<div style="text-align:center;padding:10px 6px;background:var(--s1);border-radius:8px;border:1px solid var(--bd)">'
    + '<div style="font-size:10px;color:var(--t3);margin-bottom:4px">' + label + '</div>'
    + '<div style="font-size:14px;font-weight:900;font-family:\'JetBrains Mono\',monospace;color:' + color + '">' + val + '</div>'
    + '</div>';
}
</script>
