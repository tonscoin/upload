<?php /* panels/panel-members.php — 회원 목록 (수정#1/#2/#3) */ ?>
<div class="panel" id="p-members">
  <div class="card">
    <div class="card-hd">
      <span>👥 회원 목록</span>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <input class="srch" id="m-srch" placeholder="이름 / 회원번호 / ID" oninput="filterMembers(this.value)" style="width:180px">
        <select id="m-grade-filter" onchange="filterMembers($('m-srch').value)"
          style="background:var(--s1);border:1px solid var(--bd);color:var(--t1);padding:6px 10px;border-radius:8px;font-size:11px;font-family:inherit;outline:none">
          <option value="">전체 등급</option>
          <option value="플래티넘">플래티넘</option>
          <option value="골드">골드</option>
          <option value="플러스">플러스</option>
          <option value="베이직">베이직</option>
          <option value="미달성">미달성</option>
        </select>
        <select id="m-maintain-filter" onchange="filterMembers($('m-srch').value)"
          style="background:var(--s1);border:1px solid var(--bd);color:var(--t1);padding:6px 10px;border-radius:8px;font-size:11px;font-family:inherit;outline:none">
          <option value="">전체 유지상태</option>
          <option value="유지">유지 중</option>
          <option value="미유지">미유지</option>
        </select>
        <div style="display:flex;gap:4px;background:var(--s3);border-radius:8px;padding:2px">
          <button id="m-sort-reg" class="btn bo on" style="padding:3px 9px;font-size:10px;border-radius:6px" onclick="setMemberSort('reg')">등록순</button>
          <button id="m-sort-abc" class="btn bo"    style="padding:3px 9px;font-size:10px;border-radius:6px" onclick="setMemberSort('abc')">가나다순</button>
          <button id="m-sort-no"  class="btn bo"    style="padding:3px 9px;font-size:10px;border-radius:6px" onclick="setMemberSort('no')">회원번호순</button>
        </div>
        <span id="m-count" style="font-size:11px;color:var(--t3)"></span>
      </div>
    </div>
    <div class="tw" id="m-tbl"><div class="spin"></div></div>
  </div>
</div>

<script>
let ALL_MEMBERS = [];
let _memberSort = 'reg'; // 'reg' | 'abc' | 'no'

function setMemberSort(mode) {
  _memberSort = mode;
  ['reg','abc','no'].forEach(m => {
    const btn = document.getElementById('m-sort-'+m);
    if (btn) btn.classList.toggle('on', m === mode);
  });
  filterMembers(document.getElementById('m-srch')?.value || '');
}

async function loadMembersTable(q='') {
  const el = $('m-tbl');
  el.innerHTML = '<div class="spin"></div>';
  const per = period();
  const d = await apiFetch(`api/data.php?action=members&period=${per}`);
  ALL_MEMBERS = d.data || [];
  S.members = {};
  ALL_MEMBERS.forEach(m => {
    S.members[m.member_no] = m;
    if (m.max_grade) S.gradeMap[m.member_no] = m.max_grade;
  });
  S.loaded.members = true;
  filterMembers(q);
}

function filterMembers(q) {
  const gradeF    = document.getElementById('m-grade-filter')?.value    || '';
  const maintainF = document.getElementById('m-maintain-filter')?.value || '';
  let list = ALL_MEMBERS.slice();
  if (q) list = list.filter(m =>
    (m.name||'').includes(q)||(m.member_no||'').includes(q)||(m.login_id||'').includes(q));
  if (gradeF) list = list.filter(m => (m.grade||'') === gradeF);
  if (maintainF === '유지')   list = list.filter(m => (m.maintain_status||'미유지') !== '미유지');
  if (maintainF === '미유지') list = list.filter(m => (m.maintain_status||'미유지') === '미유지');
  // 정렬
  if (_memberSort === 'abc') {
    list.sort((a,b) => (a.name||'').localeCompare(b.name||'', 'ko'));
  } else if (_memberSort === 'no') {
    list.sort((a,b) => (a.member_no||'').localeCompare(b.member_no||'', 'ko', {numeric:true}));
  }
  // 등록순(기본)은 API 반환 순서 유지
  renderMembersTable(list, $('m-tbl'));
}

function getMaintainBadge(status) {
  const map = {
    '플래티넘유지':{ bg:'rgba(46,125,50,.12)',  color:'#2e7d32', label:'🟢 플래티넘유지' },
    '골드유지':    { bg:'rgba(245,124,0,.12)',   color:'#f57c00', label:'🟡 골드유지' },
    '플러스유지':  { bg:'rgba(26,86,219,.12)',   color:'#1a56db', label:'🔵 플러스유지' },
    '베이직유지':  { bg:'rgba(123,31,162,.12)',  color:'#7b1fa2', label:'🟣 베이직유지' },
    'PV있음':      { bg:'rgba(0,137,123,.12)',   color:'#00897b', label:'🟤 PV있음' },
    '미유지':      { bg:'rgba(158,158,158,.12)', color:'#9e9e9e', label:'⚪ 미유지' },
  };
  const s = map[status] || map['미유지'];
  return '<span style="font-size:9px;font-weight:700;padding:2px 7px;border-radius:12px;background:'+s.bg+';color:'+s.color+';white-space:nowrap">'+s.label+'</span>';
}

function renderMembersTable(list, el) {
  if (!list.length) { el.innerHTML='<div class="empty-msg">데이터가 없습니다.</div>'; return; }
  const cntEl = document.getElementById('m-count');
  if (cntEl) cntEl.textContent = '총 '+list.length+'명';

  let html = '<table><thead><tr>'
    + '<th>#</th><th>회원번호</th><th>이름</th><th>ID</th>'
    + '<th>실매출 등급</th>'
    + '<th title="관리자 부여 인정등급">인정등급</th>'
    + '<th title="이번달 PV 기준">유지상태</th>'
    + '<th>이번달 PV</th><th>누적 PV</th>'
    + '<th>추천인</th><th>후원인</th><th>위치</th><th>가입일</th>'
    + '</tr></thead><tbody>';

  list.forEach(function(m, i) {
    const honorBadge = m.honor_grade
      ? '<span class="gb g'+m.honor_grade+'" style="font-size:9px">'+m.honor_grade+'</span>'
        + (m.honor_active ? '' : ' <span style="font-size:9px;color:#9e9e9e">(정지)</span>')
      : '<span style="font-size:10px;color:#9e9e9e">-</span>';

    const monthPv = m.month_pv || 0;
    const cumPv   = m.cum_pv   || 0;

    html += '<tr onclick="openMo(\''+m.member_no+'\')" style="cursor:pointer">'
      + '<td style="color:var(--t3)">'+(i+1)+'</td>'
      + '<td class="mono" style="color:var(--t3)">'+(m.member_no||'')+'</td>'
      + '<td><b>'+(m.name||'')+'</b></td>'
      + '<td class="mono" style="color:var(--t3)">'+(m.login_id||'')+'</td>'
      + '<td><span class="gb g'+(m.grade||'미달성')+'">'+(m.grade||'미달성')+'</span></td>'
      + '<td>'+honorBadge+'</td>'
      + '<td>'+getMaintainBadge(m.maintain_status||'미유지')+'</td>'
      + '<td class="mono" style="color:'+(monthPv>0?'var(--blue)':'var(--t3)')+'">'+( monthPv>0?fmt(monthPv):'-' )+'</td>'
      + '<td class="mono" style="color:var(--t2);font-size:10px">'+( cumPv>0?fmt(cumPv):'-' )+'</td>'
      + '<td>'+(m.referrer_name||m.referrer_no||'')+'</td>'
      + '<td>'+(m.sponsor_name||m.sponsor_no||'')+'</td>'
      + '<td><span class="'+(m.position==='L'?'pl':'pr')+'">'+(m.position||'')+'</span></td>'
      + '<td style="color:var(--t2)">'+(m.join_date||'').substring(0,10)+'</td>'
      + '</tr>';
  });
  html += '</tbody></table>';
  el.innerHTML = html;
}
</script>
