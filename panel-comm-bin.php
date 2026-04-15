<?php /* panels/panel-comm-bin.php — 바이너리수당 */ ?>
<div class="panel" id="p-comm-bin">
  <div class="comm-detail-header">
    <div style="font-size:18px">⚖️</div>
    <div>
      <div style="font-size:14px;font-weight:900">바이너리수당</div>
      <div style="font-size:11px;color:var(--t3)">좌·우 소실적 PV의 10% · 등급별 주간 CAP · 전체 지급 80% 초과 시 프로라타 · 주지급</div>
    </div>
    <div class="comm-period-bar" style="margin-left:auto">
      <button id="bin-tab-week"  class="btn bo on" onclick="binSwitchTab('week')">주단위</button>
      <button id="bin-tab-month" class="btn bo"    onclick="binSwitchTab('month')">월단위</button>
      <button id="bin-tab-range" class="btn bo"    onclick="binSwitchTab('range')">📅 기간합산</button>
      <span id="bin-ctrl-week" style="display:flex;align-items:center;gap:6px">
        <input type="week" id="bin-week" value="<?= date('Y') ?>-W<?= date('W') ?>">
      </span>
      <span id="bin-ctrl-month" style="display:none;align-items:center;gap:6px">
        <input type="month" id="bin-month" value="<?= date('Y-m') ?>">
      </span>
      <span id="bin-ctrl-range" style="display:none;align-items:center;gap:6px">
        <input type="date" id="bin-range-from" value="<?= date('Y-m-01') ?>"
          style="background:var(--s1);border:1px solid var(--bd);color:var(--t1);padding:5px 9px;border-radius:7px;font-size:11px;font-family:inherit;outline:none">
        <span style="font-size:11px;color:var(--t3)">~</span>
        <input type="date" id="bin-range-to" value="<?= date('Y-m-d') ?>"
          style="background:var(--s1);border:1px solid var(--bd);color:var(--t1);padding:5px 9px;border-radius:7px;font-size:11px;font-family:inherit;outline:none">
      </span>
      <button class="btn bp" onclick="loadCommBin()">📊 조회</button>
    </div>
  </div>
  <div id="bin-kpi" class="comm-kpi-row" style="display:none"></div>
  <div id="bin-tbl"><div class="empty-msg">기간 선택 후 [조회] 버튼을 누르세요.</div></div>
</div>

<script>
var _binTab          = 'week';
var _binRows         = [];          // 현재 렌더된 행 목록 (final_amt 포함)
var _binSort         = 'amt';
var _binProRataNote  = '';
var _binOpenNo       = null;        // 현재 펼쳐진 회원번호
var _binCurrentWeek  = '';          // 상세 렌더에 사용할 주차값
var _binRawRowsCache = {};          // member_no → rawRow (이월 계산용)
var _binRatio        = 1.0;

// 기간합산 전용
var _binRangeMemberRows = [];
var _binRangeSort       = 'amt';

/* ───────────────────────────────────────────────
   탭 전환
─────────────────────────────────────────────── */
function binSwitchTab(tab) {
  _binTab = tab;
  ['week','month','range'].forEach(function(t) {
    var btn  = document.getElementById('bin-tab-' + t);
    var ctrl = document.getElementById('bin-ctrl-' + t);
    if (btn)  btn.classList.toggle('on', t === tab);
    if (ctrl) ctrl.style.display = (t === tab) ? 'flex' : 'none';
  });
  S.periodMode['bin'] = (tab === 'range') ? 'week' : tab;
}

/* ───────────────────────────────────────────────
   정렬
─────────────────────────────────────────────── */
function binSortBy(mode) {
  _binSort = mode;
  _binOpenNo = null;   // 정렬 시 펼쳐진 상세 닫기
  binRenderTable();
}

/* ───────────────────────────────────────────────
   메인 테이블 렌더 (accordion 방식)
─────────────────────────────────────────────── */
function binRenderTable() {
  if (!_binRows.length) return;
  var el = $('bin-tbl');
  if (!el) return;

  var rows = _binRows.slice();
  if (_binSort === 'abc') {
    rows.sort(function(a,b){ return (a.name||'').localeCompare(b.name||'','ko'); });
  } else {
    rows.sort(function(a,b){ return b.final_amt - a.final_amt; });
  }
  var maxV       = rows[0] ? rows[0].final_amt : 1;
  var hasProRata = _binProRataNote.length > 0;

  var sortBar = '<div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;flex-wrap:wrap">'
    + '<span style="font-size:11px;color:var(--t3)">정렬:</span>'
    + '<div style="display:flex;gap:3px;background:var(--s3);border-radius:6px;padding:2px">'
    + '<button class="btn bo' + (_binSort === 'amt' ? ' on' : '') + '" style="padding:2px 8px;font-size:10px;border-radius:5px" onclick="binSortBy(\'amt\')">금액순</button>'
    + '<button class="btn bo' + (_binSort === 'abc' ? ' on' : '') + '" style="padding:2px 8px;font-size:10px;border-radius:5px" onclick="binSortBy(\'abc\')">가나다순</button>'
    + '</div>'
    + '<span style="font-size:10px;color:var(--t3);margin-left:4px">💡 행 클릭 시 레그 상세 펼침</span>'
    + '</div>';

  var tbodyRows = rows.map(function(r, i) {
    var loginId  = (S.members[r.member_no] || {}).login_id || '';
    var isOpen   = (_binOpenNo === r.member_no);
    var legRatio = (r.leg_l + r.leg_r) > 0
      ? Math.round(r.leg_l / (r.leg_l + r.leg_r) * 100) : 50;
    var isLBig   = r.leg_l >= r.leg_r;

    // 요약 행
    var summaryRow = '<tr id="bin-row-' + r.member_no + '"'
      + ' onclick="binToggleDetail(\'' + r.member_no + '\')"'
      + ' style="cursor:pointer;transition:background .15s'
      + (isOpen ? ';background:rgba(245,124,0,.06);border-left:3px solid var(--amber)' : '') + '">'
      + '<td style="color:var(--t3)">' + (i+1) + '</td>'
      + '<td>'
      +   '<div style="display:flex;align-items:center;gap:6px">'
      +   '<b>' + r.name + '</b>'
      +   '<span style="font-size:9px;color:' + (isOpen ? 'var(--amber)' : 'var(--t3)') + '">'
      +     (isOpen ? '▲ 닫기' : '▼ 상세') + '</span>'
      +   '</div>'
      +   '<span style="font-size:9px;color:var(--t3)">' + r.member_no + ' · ' + loginId + '</span>'
      + '</td>'
      + '<td><span class="gb g' + r.grade + '">' + r.grade + '</span></td>'
      // 좌/우 레그를 미니 바 포함해서 표시
      + '<td>'
      +   '<div class="mono" style="font-size:12px;color:var(--blue);font-weight:700">' + fmt(r.leg_l) + '</div>'
      +   '<div style="height:3px;background:var(--s3);border-radius:2px;margin-top:3px;width:60px">'
      +   '<div style="width:' + legRatio + '%;height:100%;background:var(--blue);border-radius:2px"></div>'
      +   '</div>'
      + '</td>'
      + '<td>'
      +   '<div class="mono" style="font-size:12px;color:var(--red);font-weight:700">' + fmt(r.leg_r) + '</div>'
      +   '<div style="height:3px;background:var(--s3);border-radius:2px;margin-top:3px;width:60px">'
      +   '<div style="width:' + (100-legRatio) + '%;height:100%;background:var(--red);border-radius:2px"></div>'
      +   '</div>'
      + '</td>'
      + '<td>'
      +   '<div class="mono" style="font-weight:700">' + fmt(r.small) + '</div>'
      +   '<div style="font-size:9px;color:var(--t3);margin-top:1px">'
      +     (isLBig ? '🔴 우레그 소' : '🔵 좌레그 소') + '</div>'
      + '</td>'
      + '<td class="mono" style="color:var(--t3);font-size:10px">' + fmtW(r.cap) + '</td>'
      + '<td class="mono" style="color:var(--t3);font-size:10px">' + fmtW(r.raw) + '</td>'
      + '<td style="text-align:right;font-weight:700;color:var(--amber)">' + fmtW(r.final_amt)
      +   (hasProRata ? '<span style="font-size:9px;color:var(--t3);margin-left:3px">조정</span>' : '') + '</td>'
      + '<td style="width:120px;max-width:120px">'
      +   '<div style="height:6px;background:var(--s3);border-radius:3px;overflow:hidden;width:100%">'
      +   '<div style="width:' + Math.round(r.final_amt / maxV * 100) + '%;height:100%;'
      +   'background:linear-gradient(90deg,#f57c00,#ffb74d);border-radius:3px"></div>'
      +   '</div>'
      + '</td>'

      + '</tr>';

    // 상세 accordion 행
    var detailRow = '<tr id="bin-detail-row-' + r.member_no + '" style="display:' + (isOpen ? 'table-row' : 'none') + '">'
      + '<td colspan="10" style="padding:0;background:var(--s1)">'
      + '<div id="bin-detail-inner-' + r.member_no + '" style="padding:14px 18px 18px">'
      + (isOpen ? _binBuildDetail(r.member_no) : '')
      + '</div>'
      + '</td></tr>';

    return summaryRow + detailRow;
  }).join('');

  el.innerHTML = _binProRataNote + sortBar
    + '<div class="tw"><table>'
    + '<thead><tr>'
    + '<th>#</th><th>이름</th><th>등급</th>'
    + '<th>좌 PV</th><th>우 PV</th><th>소실적</th>'
    + '<th>주간 CAP</th><th>산출액</th>'
    + '<th style="text-align:right">최종 수당</th>'
    + '<th style="width:120px;max-width:120px"></th>'   /* ← 변경 */
    + '</tr></thead>'
    + '<tbody>' + tbodyRows + '</tbody>'
    + '</table></div>';
}

/* ───────────────────────────────────────────────
   Accordion 토글
─────────────────────────────────────────────── */
function binToggleDetail(no) {
  var detailRow  = document.getElementById('bin-detail-row-' + no);
  var innerDiv   = document.getElementById('bin-detail-inner-' + no);
  var summaryRow = document.getElementById('bin-row-' + no);
  if (!detailRow || !innerDiv) return;

  var isOpen = detailRow.style.display !== 'none';

  // 이전에 열린 행 닫기
  if (_binOpenNo && _binOpenNo !== no) {
    var prevDetail  = document.getElementById('bin-detail-row-' + _binOpenNo);
    var prevSummary = document.getElementById('bin-row-' + _binOpenNo);
    if (prevDetail)  prevDetail.style.display = 'none';
    if (prevSummary) {
      prevSummary.style.background = '';
      prevSummary.style.borderLeft = '';
      var hint = prevSummary.querySelector('span[style*="color"]');
      // 닫힘 표시 텍스트 갱신은 re-render 없이 DOM 직접 조작
      var hintSpan = prevSummary.querySelector('td:nth-child(2) span:last-child');
      if (hintSpan) { hintSpan.textContent = '▼ 상세'; hintSpan.style.color = 'var(--t3)'; }
    }
  }

  if (isOpen) {
    // 닫기
    detailRow.style.display = 'none';
    summaryRow.style.background = '';
    summaryRow.style.borderLeft = '';
    var hintSpan = summaryRow.querySelector('td:nth-child(2) span:last-child');
    if (hintSpan) { hintSpan.textContent = '▼ 상세'; hintSpan.style.color = 'var(--t3)'; }
    _binOpenNo = null;
  } else {
    // 열기 - 상세 HTML 생성 (lazy)
    innerDiv.innerHTML = _binBuildDetail(no);
    detailRow.style.display = 'table-row';
    summaryRow.style.background = 'rgba(245,124,0,.06)';
    summaryRow.style.borderLeft = '3px solid var(--amber)';
    var hintSpan = summaryRow.querySelector('td:nth-child(2) span:last-child');
    if (hintSpan) { hintSpan.textContent = '▲ 닫기'; hintSpan.style.color = 'var(--amber)'; }
    _binOpenNo = no;
    // 부드럽게 스크롤
    setTimeout(function(){ detailRow.scrollIntoView({ behavior:'smooth', block:'nearest' }); }, 50);
  }
}

/* ───────────────────────────────────────────────
   상세 HTML 빌더 (_cwRenderDetail의 bin 파트 이식)
─────────────────────────────────────────────── */
function _binBuildDetail(no) {
  var r   = _binRows.find(function(x){ return x.member_no === no; });
  var raw = _binRawRowsCache[no];
  if (!r) return '<div style="color:var(--t3);font-size:12px;padding:10px">데이터 없음</div>';

  var weekVal  = _binCurrentWeek;
  var mem      = S.members[no] || {};

  // 이월 PV (raw 캐시에서, 없으면 0)
  var carryL = (raw && raw.carryL != null) ? raw.carryL : 0;
  var carryR = (raw && raw.carryR != null) ? raw.carryR : 0;
  var legL   = r.leg_l;
  var legR   = r.leg_r;
  var small  = r.small;
  var cap    = r.cap;
  var rawAmt = r.raw;
  var capAmt = Math.min(rawAmt, cap);
  var isCap  = rawAmt > cap;
  var finalAmt = r.final_amt;
  var ratio    = _binRatio;
  var isRatio  = ratio < 0.9999;
  var isLBig   = legL >= legR;
  var newL     = legL - carryL;
  var newR     = legR - carryR;

  // 다음 주 이월 PV 계산
  var usedPV   = finalAmt > 0 ? Math.round(finalAmt / 0.10) : small;
  var nextCarryL = isLBig  ? Math.max(0, legL - usedPV) : 0;
  var nextCarryR = !isLBig ? Math.max(0, legR - usedPV) : 0;

  var binId = 'bind-' + no;
  var html  = '';

  // ── 상단 타이틀 바 ──
  html += '<div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;padding-bottom:12px;border-bottom:2px solid rgba(245,124,0,.3)">'
    + '<div style="width:36px;height:36px;border-radius:50%;background:rgba(245,124,0,.12);'
    +   'display:flex;align-items:center;justify-content:center;font-size:18px">⚖️</div>'
    + '<div>'
    +   '<div style="font-size:13px;font-weight:900;color:var(--t1)">' + (mem.name||no) + ' 바이너리수당 상세</div>'
    +   '<div style="font-size:10px;color:var(--t3);margin-top:1px">'
    +     (mem.login_id ? mem.login_id + ' · ' : '') + no
    +     (weekVal ? ' · ' + weekVal : '')
    +   '</div>'
    + '</div>'
    + '<div style="margin-left:auto;text-align:right">'
    +   '<div style="font-size:10px;color:var(--t3)">최종 지급액</div>'
    +   '<div style="font-size:20px;font-weight:900;font-family:\'JetBrains Mono\',monospace;color:var(--amber)">' + fmtW(finalAmt) + '</div>'
    + '</div>'
    + '</div>';

  // ── 좌/우 레그 카드 2칸 ──
  html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">';

  // 좌레그 카드
  html += '<div onclick="_binToggleLegDetail(\'' + binId + '-L\')"'
    + ' style="cursor:pointer;background:rgba(26,86,219,.05);'
    + 'border:2px solid ' + (isLBig ? 'rgba(26,86,219,.45)' : 'rgba(26,86,219,.18)') + ';'
    + 'border-radius:12px;padding:14px;position:relative;transition:background .2s"'
    + ' onmouseover="this.style.background=\'rgba(26,86,219,.1)\'"'
    + ' onmouseout="this.style.background=\'rgba(26,86,219,.05)\'">'
    + (isLBig ? '<div style="position:absolute;top:8px;right:8px;background:var(--blue);color:#fff;font-size:9px;font-weight:700;padding:2px 7px;border-radius:5px">대레그 ▲</div>' : '')
    + '<div style="font-size:11px;font-weight:700;color:var(--blue);margin-bottom:8px">🔵 좌레그 (L)</div>'
    + '<div style="font-size:22px;font-weight:900;font-family:\'JetBrains Mono\',monospace;color:var(--blue)">' + fmt(legL) + '</div>'
    + '<div style="font-size:10px;color:var(--t3);margin-top:1px">PV</div>'
    + '<div style="margin-top:10px;display:flex;flex-direction:column;gap:4px">'
    +   '<div style="display:flex;justify-content:space-between;font-size:10px">'
    +     '<span style="color:var(--t3)">이전 이월</span>'
    +     '<span style="font-family:\'JetBrains Mono\',monospace;color:var(--blue)">' + fmt(carryL) + '</span></div>'
    +   '<div style="display:flex;justify-content:space-between;font-size:10px">'
    +     '<span style="color:var(--t3)">이번 주 신규</span>'
    +     '<span style="font-family:\'JetBrains Mono\',monospace;color:var(--blue);font-weight:700">+' + fmt(newL) + '</span></div>'
    + '</div>'
    + '<div style="margin-top:8px;height:5px;background:var(--s3);border-radius:3px;overflow:hidden">'
    +   '<div style="width:' + ((legL+legR>0) ? Math.round(legL/(legL+legR)*100) : 0) + '%;height:100%;background:var(--blue);border-radius:3px"></div>'
    + '</div>'
    + '<div style="font-size:9px;color:var(--t3);margin-top:4px;text-align:right">👆 클릭 · 기여 회원 보기</div>'
    + '</div>';

  // 우레그 카드
  html += '<div onclick="_binToggleLegDetail(\'' + binId + '-R\')"'
    + ' style="cursor:pointer;background:rgba(198,40,40,.05);'
    + 'border:2px solid ' + (!isLBig ? 'rgba(198,40,40,.45)' : 'rgba(198,40,40,.18)') + ';'
    + 'border-radius:12px;padding:14px;position:relative;transition:background .2s"'
    + ' onmouseover="this.style.background=\'rgba(198,40,40,.1)\'"'
    + ' onmouseout="this.style.background=\'rgba(198,40,40,.05)\'">'
    + (!isLBig ? '<div style="position:absolute;top:8px;right:8px;background:var(--red);color:#fff;font-size:9px;font-weight:700;padding:2px 7px;border-radius:5px">대레그 ▲</div>' : '')
    + '<div style="font-size:11px;font-weight:700;color:var(--red);margin-bottom:8px">🔴 우레그 (R)</div>'
    + '<div style="font-size:22px;font-weight:900;font-family:\'JetBrains Mono\',monospace;color:var(--red)">' + fmt(legR) + '</div>'
    + '<div style="font-size:10px;color:var(--t3);margin-top:1px">PV</div>'
    + '<div style="margin-top:10px;display:flex;flex-direction:column;gap:4px">'
    +   '<div style="display:flex;justify-content:space-between;font-size:10px">'
    +     '<span style="color:var(--t3)">이전 이월</span>'
    +     '<span style="font-family:\'JetBrains Mono\',monospace;color:var(--red)">' + fmt(carryR) + '</span></div>'
    +   '<div style="display:flex;justify-content:space-between;font-size:10px">'
    +     '<span style="color:var(--t3)">이번 주 신규</span>'
    +     '<span style="font-family:\'JetBrains Mono\',monospace;color:var(--red);font-weight:700">+' + fmt(newR) + '</span></div>'
    + '</div>'
    + '<div style="margin-top:8px;height:5px;background:var(--s3);border-radius:3px;overflow:hidden">'
    +   '<div style="width:' + ((legL+legR>0) ? Math.round(legR/(legL+legR)*100) : 0) + '%;height:100%;background:var(--red);border-radius:3px"></div>'
    + '</div>'
    + '<div style="font-size:9px;color:var(--t3);margin-top:4px;text-align:right">👆 클릭 · 기여 회원 보기</div>'
    + '</div>';

  html += '</div>'; // grid

  // ── 레그 기여 회원 토글 영역 (주단위일 때만 유의미) ──
  html += '<div id="' + binId + '-L" style="display:none;margin-bottom:8px">';
  if (_binTab === 'week' && weekVal) {
    html += _binLegMemberDetail(no, 'L', weekVal, newL, carryL);
  } else {
    html += '<div style="padding:12px;background:rgba(26,86,219,.04);border:1px solid rgba(26,86,219,.2);border-radius:10px;font-size:11px;color:var(--t3);text-align:center">'
      + '기여 회원 상세는 주단위 조회 시 확인 가능합니다.</div>';
  }
  html += '</div>';

  html += '<div id="' + binId + '-R" style="display:none;margin-bottom:8px">';
  if (_binTab === 'week' && weekVal) {
    html += _binLegMemberDetail(no, 'R', weekVal, newR, carryR);
  } else {
    html += '<div style="padding:12px;background:rgba(198,40,40,.04);border:1px solid rgba(198,40,40,.2);border-radius:10px;font-size:11px;color:var(--t3);text-align:center">'
      + '기여 회원 상세는 주단위 조회 시 확인 가능합니다.</div>';
  }
  html += '</div>';

  // ── 수당 계산 과정 ──
  html += '<div style="background:var(--s1);border:1px solid var(--bd);border-radius:12px;overflow:hidden;margin-bottom:10px">';
  html += '<div style="padding:12px 14px;border-bottom:1px solid var(--bd)">';
  html += '<div style="font-size:11px;font-weight:700;color:var(--t2);margin-bottom:8px">⚙️ 수당 계산 과정</div>';
  html += '<div style="display:flex;flex-direction:column;gap:5px;font-size:11px">';
  html += '<div style="display:flex;justify-content:space-between">'
    + '<span style="color:var(--t3)">소실적 (소레그, ' + (isLBig ? '우' : '좌') + '레그)</span>'
    + '<span style="font-family:\'JetBrains Mono\',monospace;font-weight:700;color:var(--amber)">' + fmt(small) + ' PV</span></div>';
  html += '<div style="display:flex;justify-content:space-between">'
    + '<span style="color:var(--t3)">× 10%</span>'
    + '<span style="font-family:\'JetBrains Mono\',monospace">' + fmtW(rawAmt) + '</span></div>';
  if (isCap) {
    html += '<div style="display:flex;justify-content:space-between">'
      + '<span style="color:var(--amber)">⚠️ 주간 CAP 적용 (상한 ' + fmtW(cap) + ')</span>'
      + '<span style="font-family:\'JetBrains Mono\',monospace;color:var(--amber)">' + fmtW(capAmt) + '</span></div>';
  }
  if (isRatio) {
    html += '<div style="display:flex;justify-content:space-between">'
      + '<span style="color:var(--red)">⚠️ 프로라타 조정 (' + (ratio*100).toFixed(1) + '%)</span>'
      + '<span style="font-family:\'JetBrains Mono\',monospace;color:var(--red)">' + fmtW(finalAmt) + '</span></div>';
  }
  html += '<div style="display:flex;justify-content:space-between;padding-top:6px;border-top:1px solid var(--bd);font-weight:900">'
    + '<span>최종 지급액</span>'
    + '<span style="font-family:\'JetBrains Mono\',monospace;color:var(--amber);font-size:14px">' + fmtW(finalAmt) + '</span></div>';
  html += '</div></div>';

  // ── 다음 주 이월 PV (주단위 조회 시만 표시) ──
  if (_binTab === 'week') {
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
        + '💡 ' + (nextCarryL >= nextCarryR ? '좌' : '우') + '레그 '
        + fmt(Math.max(nextCarryL, nextCarryR)) + ' PV가 다음 주로 이월됩니다.</div>';
    }
    html += '</div>';
  }

  html += '</div>'; // 계산 카드 닫기

  return html;
}

/* ───────────────────────────────────────────────
   레그 토글 (bin 전용)
─────────────────────────────────────────────── */
function _binToggleLegDetail(id) {
  var el = document.getElementById(id);
  if (!el) return;
  el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

/* ───────────────────────────────────────────────
   레그 기여 회원 상세 HTML
   (_cwLegMemberDetail과 동일 로직, bin 네임스페이스)
─────────────────────────────────────────────── */
function _binLegMemberDetail(no, side, weekVal, newPV, carryPV) {
  var color     = side === 'L' ? 'var(--blue)' : 'var(--red)';
  var colorRgb  = side === 'L' ? '26,86,219'  : '198,40,40';
  var sideLabel = side === 'L' ? '🔵 좌레그'  : '🔴 우레그';
  var contribs  = _binGetLegContribs(no, side, weekVal);
  var totalNewPV = contribs.reduce(function(a,c){ return a + c.pv; }, 0);

  var html = '<div style="background:rgba(' + colorRgb + ',.04);border:1px solid rgba(' + colorRgb + ',.2);'
    + 'border-radius:10px;overflow:hidden;margin-top:4px">';

  // 헤더
  html += '<div style="padding:10px 14px;background:rgba(' + colorRgb + ',.08);border-bottom:1px solid rgba(' + colorRgb + ',.2);'
    + 'display:flex;align-items:center;justify-content:space-between">'
    + '<div style="font-size:11px;font-weight:700;color:' + color + '">' + sideLabel + ' 이번 주 기여 회원</div>'
    + '<div style="font-size:11px;font-weight:900;font-family:\'JetBrains Mono\',monospace;color:' + color + '">'
    +   fmt(totalNewPV) + ' PV</div>'
    + '</div>';

  if (contribs.length === 0) {
    html += '<div style="padding:14px;text-align:center;font-size:11px;color:var(--t3)">'
      + '이번 주 신규 매출 없음'
      + (carryPV > 0
          ? '<br><span style="color:' + color + ';font-weight:700">이전 주 이월 ' + fmt(carryPV) + ' PV</span>가 합산됨'
          : '')
      + '</div>';
  } else {
    if (carryPV > 0) {
      html += '<div style="padding:8px 14px;background:rgba(' + colorRgb + ',.06);border-bottom:1px solid rgba(' + colorRgb + ',.15);'
        + 'display:flex;align-items:center;justify-content:space-between;font-size:10px">'
        + '<span style="color:var(--t3)">📦 이전 주 이월 포함</span>'
        + '<span style="font-family:\'JetBrains Mono\',monospace;font-weight:700;color:' + color + '">+' + fmt(carryPV) + ' PV</span>'
        + '</div>';
    }

    html += '<div class="tw"><table>'
      + '<thead><tr style="background:rgba(' + colorRgb + ',.06)">'
      + '<th style="padding:6px 10px;text-align:left;font-size:10px">날짜</th>'
      + '<th style="padding:6px 10px;text-align:left;font-size:10px">회원</th>'
      + '<th style="padding:6px 10px;text-align:right;font-size:10px">매출금액</th>'
      + '<th style="padding:6px 10px;text-align:right;font-size:10px">PV</th>'
      + '<th style="padding:6px 10px;font-size:10px;min-width:80px"></th>'
      + '</tr></thead><tbody>';

    contribs.forEach(function(c) {
      var barPct = totalNewPV > 0 ? Math.round(c.pv / totalNewPV * 100) : 0;
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
      + '<td style="padding:8px 10px;text-align:right;font-family:\'JetBrains Mono\',monospace;color:' + color + '">' + fmt(totalNewPV) + '</td>'
      + '<td></td></tr>';

    if (carryPV > 0) {
      html += '<tr style="background:rgba(' + colorRgb + ',.03)">'
        + '<td colspan="3" style="padding:6px 10px;font-size:11px;color:var(--t3)">이전 주 이월</td>'
        + '<td style="padding:6px 10px;text-align:right;font-family:\'JetBrains Mono\',monospace;font-size:11px;color:' + color + '">' + fmt(carryPV) + '</td>'
        + '<td></td></tr>';
      html += '<tr style="background:rgba(' + colorRgb + ',.08);font-weight:900">'
        + '<td colspan="3" style="padding:8px 10px;font-size:12px">총 레그 PV</td>'
        + '<td style="padding:8px 10px;text-align:right;font-family:\'JetBrains Mono\',monospace;font-size:13px;color:' + color + '">' + fmt(totalNewPV + carryPV) + '</td>'
        + '<td></td></tr>';
    }

    html += '</tbody></table></div>';
  }

  html += '</div>';
  return html;
}

/* ───────────────────────────────────────────────
   레그 기여 매출 목록 추출
   (_cwGetLegContribs와 동일 로직)
─────────────────────────────────────────────── */
function _binGetLegContribs(no, side, weekVal) {
  var wSales  = filterSalesByPeriod(S.sales, weekVal, 'week');
  var wQual   = buildQualifiedForWeek(weekVal, S.sales);
  var result  = [];

  wSales.forEach(function(s) {
    var buyNo = findMemberNo(s);
    if (!buyNo) return;
    var pv = parseInt(s.pv) || 0;
    if (!pv) return;
    var saleDate = s.order_date || '';

    var cur = (S.members[buyNo] || {}).sponsor_no;
    var pos = (S.members[buyNo] || {}).position || 'L';
    while (cur && S.members[cur]) {
      if (cur === no) {
        if (wQual[no]) {
          var qd = wQual[no].qualDate || '';
          if (qd && saleDate < qd) break;
        }
        if (pos === side) {
          result.push({
            buyerNo:   buyNo,
            buyerName: (S.members[buyNo] || {}).name    || buyNo,
            loginId:   (S.members[buyNo] || {}).login_id || '',
            date:      saleDate,
            pv:        pv,
            amount:    parseInt(s.amount) || 0,
          });
        }
        break;
      }
      pos = (S.members[cur] || {}).position || 'L';
      cur = (S.members[cur] || {}).sponsor_no;
    }
  });

  result.sort(function(a,b){ return a.date < b.date ? -1 : a.date > b.date ? 1 : 0; });
  return result;
}

/* ───────────────────────────────────────────────
   loadCommBin  (주/월 단위)
─────────────────────────────────────────────── */
async function loadCommBin() {
  if (_binTab === 'range') {
    await loadCommBinRange();
    return;
  }

  var mode      = _binTab;
  var periodVal = mode === 'week' ? $('bin-week').value : $('bin-month').value;
  if (!periodVal) return;

  _binCurrentWeek = (mode === 'week') ? periodVal : '';
  _binOpenNo      = null;

  var el = $('bin-tbl');
  el.innerHTML = '<div class="spin"></div>';
  await ensureData(periodVal);

  var sales = filterSalesByPeriod(S.sales, periodVal, mode);
  var pvMap = buildPvMap(sales);

  var qual = {};
  if (mode === 'week') {
    qual = buildQualifiedForWeek(periodVal, S.sales);
  } else {
    getWeeksInMonth(periodVal).forEach(function(wk) {
      var wq = buildQualifiedForWeek(wk, S.sales);
      Object.keys(wq).forEach(function(no) {
        if (!qual[no] || wq[no].qualDate < qual[no].qualDate) qual[no] = wq[no];
      });
    });
  }

  var legPV   = buildLegPVQual(sales, qual);
  var m       = S.members;
  var totalPV = Object.values(pvMap).reduce(function(a,b){ return a+b; }, 0);

  // ★ 추가: 주단위일 때 이전 주 이월 PV 로드
  var prevCarry = {};
  if (mode === 'week') {
    var prevWeekStr = getPrevWeekStr(periodVal);
    prevCarry = calcBinCarryForWeek(prevWeekStr);
  }

  // rawRows 생성 및 이월 캐시 초기화
  _binRawRowsCache = {};
  var rawRows = [];
  Object.keys(qual).forEach(function(no) {
    // ★ 변경: 이월 + 신규 합산
    var carry  = (mode === 'week') ? (prevCarry[no] || { L:0, R:0 }) : { L:0, R:0 };
    var newLeg = legPV[no] || { L:0, R:0 };
    var l = carry.L + newLeg.L;
    var r = carry.R + newLeg.R;
    if (!l || !r) return;
    var small = Math.min(l, r);
    var grade = getEffectiveGrade(no, pvMap[no] || 0);
    var cap   = GRADE_WEEKLY_CAPS[grade] || 0;
    var raw   = Math.round(small * 0.10);
    var amt   = Math.min(raw, cap);
    if (amt > 0) {
      // ★ 변경: prevCarry에서 직접 가져옴 (_cwData 의존 제거)
      _binRawRowsCache[no] = {
        carryL: carry.L,
        carryR: carry.R
      };
      rawRows.push({
        member_no: no,
        name:  (m[no]||{}).name || no,
        grade: grade,
        leg_l: l, leg_r: r, small: small, cap: cap, raw: raw, amt: amt
      });
    }
  });

  var binTotal = rawRows.reduce(function(s,r){ return s+r.amt; }, 0);
  var binLimit = Math.round(totalPV * 0.80);
  _binRatio    = (binTotal > binLimit && binTotal > 0) ? binLimit / binTotal : 1.0;

  _binRows = rawRows.map(function(r) {
    return Object.assign({}, r, { final_amt: Math.round(r.amt * _binRatio) });
  });
  _binSort = 'amt';

  var totalPayout = _binRows.reduce(function(s,r){ return s + r.final_amt; }, 0);

  _binProRataNote = _binRatio < 1.0
    ? '<div style="background:rgba(245,124,0,.1);border:1px solid rgba(245,124,0,.3);border-radius:8px;'
      + 'padding:8px 12px;font-size:11px;color:var(--amber);margin-bottom:10px">'
      + '⚠️ 바이너리 지급 총액 ' + fmtW(binTotal) + '이 전체 PV의 80%(' + fmtW(binLimit) + ')를 초과하여 '
      + '프로라타 ' + (_binRatio*100).toFixed(1) + '% 적용'
      + '</div>'
    : '';

  showKpi('bin-kpi',
    '<div class="comm-kpi"><div class="comm-kpi-v" style="color:var(--green)">' + fmtW(totalPayout) + '</div><div class="comm-kpi-l">총 지급 수당</div></div>'
    + '<div class="comm-kpi"><div class="comm-kpi-v" style="color:var(--blue)">' + _binRows.length + '</div><div class="comm-kpi-l">수당 수령 회원</div></div>'
    + '<div class="comm-kpi"><div class="comm-kpi-v" style="color:var(--amber)">' + (_binRows.length > 0 ? fmtW(_binRows[0].final_amt) : '-') + '</div><div class="comm-kpi-l">최고 수당액</div></div>'
  );

  if (!_binRows.length) {
    el.innerHTML = '<div class="empty-msg">해당 기간 바이너리수당 데이터가 없습니다.</div>';
    return;
  }
  binRenderTable();
}


/* ───────────────────────────────────────────────
   기간 합산 조회 (기존 코드 그대로 유지)
─────────────────────────────────────────────── */
async function loadCommBinRange() {
  var fromVal = $('bin-range-from').value;
  var toVal   = $('bin-range-to').value;
  if (!fromVal || !toVal) { alert('시작일과 종료일을 입력하세요.'); return; }
  if (fromVal > toVal)    { alert('시작일이 종료일보다 늦습니다.'); return; }

  var el = $('bin-tbl');
  el.innerHTML = '<div class="spin"></div>';

  await ensureData(fromVal);
  if (fromVal.substring(0,4) !== toVal.substring(0,4)) await ensureData(toVal);

  var weeks = _getWeeksInRange(fromVal, toVal);
  if (!weeks.length) { el.innerHTML = '<div class="empty-msg">해당 기간에 주차가 없습니다.</div>'; return; }

  var m            = S.members;
  var memberTotals = {};
  var grandTotal   = 0;
  var weekResults  = [];

  for (var wi = 0; wi < weeks.length; wi++) {
    var wk     = weeks[wi];
    var wSales = filterSalesByPeriod(S.sales, wk, 'week');
    var wPvMap = buildPvMap(wSales);
    var wQual  = buildQualifiedForWeek(wk, S.sales);
    var wLegPV = buildLegPVQual(wSales, wQual);
    var wTotalPV = Object.values(wPvMap).reduce(function(a,b){ return a+b; }, 0);

    if (wTotalPV === 0) {
      weekResults.push({ wk:wk, totalPV:0, payout:0, ratio:1.0, count:0 });
      continue;
    }

    var rawRows = [];
    Object.keys(wQual).forEach(function(no) {
      var l = (wLegPV[no] || {}).L || 0;
      var r = (wLegPV[no] || {}).R || 0;
      if (!l || !r) return;
      var small = Math.min(l, r);
      var grade = getEffectiveGrade(no, wPvMap[no] || 0);
      var cap   = GRADE_WEEKLY_CAPS[grade] || 0;
      var raw   = Math.round(small * 0.10);
      var amt   = Math.min(raw, cap);
      if (amt > 0) rawRows.push({ no:no, name:(m[no]||{}).name||no, grade:grade, small:small, cap:cap, raw:raw, amt:amt });
    });

    var binTotal = rawRows.reduce(function(s,r){ return s+r.amt; }, 0);
    var binLimit = Math.round(wTotalPV * 0.80);
    var ratio    = (binTotal > binLimit && binTotal > 0) ? binLimit / binTotal : 1.0;

    var wPayout = 0;
    rawRows.forEach(function(r) {
      var fin = Math.round(r.amt * ratio);
      wPayout    += fin;
      grandTotal += fin;
      if (!memberTotals[r.no]) memberTotals[r.no] = { name:r.name, grade:r.grade, weeks:[], total:0 };
      memberTotals[r.no].total += fin;
      memberTotals[r.no].weeks.push({ wk:wk, final:fin, small:r.small, cap:r.cap });
    });

    weekResults.push({ wk:wk, totalPV:wTotalPV, payout:wPayout, ratio:ratio, count:rawRows.length });
  }

  var maxWeekPay = Math.max.apply(null, weekResults.map(function(w){ return w.payout; }).concat([1]));

  showKpi('bin-kpi',
    '<div class="comm-kpi"><div class="comm-kpi-v" style="color:var(--green)">' + fmtW(grandTotal) + '</div><div class="comm-kpi-l">기간 합산 수당</div></div>'
    + '<div class="comm-kpi"><div class="comm-kpi-v" style="color:var(--blue)">' + weeks.length + '주</div><div class="comm-kpi-l">포함 주차</div></div>'
    + '<div class="comm-kpi"><div class="comm-kpi-v" style="color:var(--amber)">' + Object.keys(memberTotals).length + '명</div><div class="comm-kpi-l">수당 수령 회원</div></div>'
  );

  var weekRows = weekResults.map(function(w) {
    return '<tr>'
      + '<td style="font-weight:700;color:var(--blue)">' + w.wk + '</td>'
      + '<td class="mono">' + (w.totalPV > 0 ? fmt(w.totalPV) : '-') + '</td>'
      + '<td style="text-align:center">' + (w.count > 0 ? w.count + '명' : '-') + '</td>'
      + '<td style="font-size:10px">' + (w.ratio < 1.0
          ? '<span style="color:var(--amber);font-weight:700">' + (w.ratio*100).toFixed(1) + '%</span>'
          : '<span style="color:var(--t3)">없음</span>') + '</td>'
      + '<td style="text-align:right;font-weight:700;color:' + (w.payout>0 ? 'var(--amber)' : 'var(--t3)') + '">'
      +   (w.payout>0 ? fmtW(w.payout) : '-') + '</td>'
      + '<td><div style="height:5px;background:var(--s3);border-radius:3px">'
      +   '<div style="width:' + Math.round(w.payout/maxWeekPay*100) + '%;height:100%;background:linear-gradient(90deg,#f57c00,#ffb74d);border-radius:3px"></div>'
      + '</div></td>'
      + '</tr>';
  }).join('');

  _binRangeMemberRows = Object.entries(memberTotals)
    .map(function(e){ return Object.assign({ no:e[0] }, e[1]); });
  _binRangeSort = 'amt';

  var weekSummaryHtml =
    '<div style="margin-bottom:18px">'
    + '<div style="font-size:12px;font-weight:700;margin-bottom:8px;color:var(--t1)">📅 주차별 내역'
    + '<span style="font-size:10px;color:var(--t3);font-weight:400;margin-left:8px">' + fromVal + ' ~ ' + toVal + '</span></div>'
    + '<div class="tw"><table><thead><tr>'
    + '<th>주차</th><th>PV 합계</th><th>수령 인원</th><th>프로라타</th>'
    + '<th style="text-align:right">주차 수당</th><th style="min-width:140px"></th>'
    + '</tr></thead><tbody>' + weekRows
    + '<tr style="background:var(--s2);font-weight:900"><td>합계</td><td></td><td></td><td></td>'
    + '<td style="text-align:right;color:var(--green);font-size:14px">' + fmtW(grandTotal) + '</td><td></td></tr>'
    + '</tbody></table></div></div>';

  var memberSectionHtml = _binRangeMemberRows.length
    ? '<div id="bin-range-member-section">'
      + '<div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;flex-wrap:wrap">'
      + '<div style="font-size:12px;font-weight:700;color:var(--t1)">&#x1F464; 회원별 기간 합산</div>'
      + '<div style="margin-left:auto;display:flex;gap:3px;background:var(--s3);border-radius:6px;padding:2px">'
      + '<button class="btn bo on" id="bin-range-sort-amt" style="padding:2px 8px;font-size:10px;border-radius:5px" onclick="binRangeSortBy(\'amt\')">금액순</button>'
      + '<button class="btn bo"    id="bin-range-sort-abc" style="padding:2px 8px;font-size:10px;border-radius:5px" onclick="binRangeSortBy(\'abc\')">가나다순</button>'
      + '</div></div>'
      + '<div id="bin-range-member-tbl"></div>'
      + '</div>'
    : '<div class="empty-msg">해당 기간 바이너리수당 데이터가 없습니다.</div>';

  el.innerHTML = weekSummaryHtml + memberSectionHtml;
  binRangeRenderMembers();
}

function binRangeSortBy(mode) {
  _binRangeSort = mode;
  var btnAmt = document.getElementById('bin-range-sort-amt');
  var btnAbc = document.getElementById('bin-range-sort-abc');
  if (btnAmt) btnAmt.classList.toggle('on', mode === 'amt');
  if (btnAbc) btnAbc.classList.toggle('on', mode === 'abc');
  binRangeRenderMembers();
}

function binRangeRenderMembers() {
  var tblEl = document.getElementById('bin-range-member-tbl');
  if (!tblEl || !_binRangeMemberRows.length) return;

  var rows = _binRangeMemberRows.slice();
  if (_binRangeSort === 'abc') {
    rows.sort(function(a,b){ return (a.name||'').localeCompare(b.name||'','ko'); });
  } else {
    rows.sort(function(a,b){ return b.total - a.total; });
  }
  var maxMemberPay = Math.max.apply(null, rows.map(function(r){ return r.total; }).concat([1]));

  var memberHtml = rows.map(function(r, i) {
    var wkLines = r.weeks.map(function(w){
      return w.wk + ': ' + fmtW(w.final) + ' (소실적 ' + fmt(w.small) + ')';
    }).join('<br>');
    var tipEnc  = encodeURIComponent('<b>' + r.name + ' 주차별 내역</b>' + wkLines);
    var loginId = (S.members[r.no]||{}).login_id || '';
    return '<tr onclick="openMo(\'' + r.no + '\')" style="cursor:pointer">'
      + '<td style="color:var(--t3)">' + (i+1) + '</td>'
      + '<td><b>' + r.name + '</b><br><span style="font-size:9px;color:var(--t3)">' + r.no + ' · ' + loginId + '</span></td>'
      + '<td><span class="gb g' + r.grade + '">' + r.grade + '</span></td>'
      + '<td style="text-align:center">'
      +   '<span class="tt-tag" style="cursor:default;color:var(--blue);font-weight:700"'
      +   ' data-tt="' + tipEnc + '"'
      +   ' onmouseenter="ttShow(this,decodeURIComponent(this.dataset.tt))"'
      +   ' onmouseleave="ttHide()">' + r.weeks.length + '주</span>'
      + '</td>'
      + '<td style="text-align:right;font-weight:900;color:var(--amber);font-size:13px">' + fmtW(r.total) + '</td>'
      + '<td><div style="height:6px;background:var(--s3);border-radius:3px">'
      +   '<div style="width:' + Math.round(r.total/maxMemberPay*100) + '%;height:100%;background:linear-gradient(90deg,#f57c00,#ffb74d);border-radius:3px"></div>'
      + '</div></td>'
      + '</tr>';
  }).join('');

  tblEl.innerHTML = '<div class="tw"><table><thead><tr>'
    + '<th>#</th><th>이름</th><th>등급</th>'
    + '<th style="text-align:center">수령 주차</th>'
    + '<th style="text-align:right">기간 합산 수당</th>'
    + '<th style="min-width:140px"></th>'
    + '</tr></thead><tbody>' + memberHtml + '</tbody></table></div>';
}

function _getWeeksInRange(fromDate, toDate) {
  var weeks = {};
  var from  = new Date(fromDate);
  var to    = new Date(toDate);
  var cur   = new Date(from);
  while (cur <= to) {
    weeks[_isoWeek(cur)] = true;
    cur.setDate(cur.getDate() + 1);
  }
  return Object.keys(weeks).sort();
}

function _isoWeek(d) {
  var date = new Date(d);
  date.setHours(0,0,0,0);
  date.setDate(date.getDate() + 3 - (date.getDay()+6)%7);
  var week1 = new Date(date.getFullYear(), 0, 4);
  var wn = 1 + Math.round(((date - week1) / 86400000 - 3 + (week1.getDay()+6)%7) / 7);
  return date.getFullYear() + '-W' + String(wn).padStart(2,'0');
}
</script>
