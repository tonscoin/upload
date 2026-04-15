<?php /* panels/panel-honor.php — 인정자격 회원 관리 (수정#2/#5) */ ?>
<div class="panel" id="p-honor">
  <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap;padding:16px 18px;background:var(--s1);border:1px solid var(--bd);border-radius:12px">
    <div>
      <div style="font-size:14px;font-weight:900;margin-bottom:2px">🏅 인정자격 회원 관리</div>
      <div style="font-size:11px;color:var(--t3)">PV 미달 회원에게 관리자가 자격·등급·기간을 부여 · 수당 계산에 자동 반영 · 기간 만료 시 자동 제외</div>
    </div>
  </div>

  <!-- 부여 폼 -->
  <div class="card" style="margin-bottom:16px">
    <div class="card-hd">➕ 인정자격 부여 / 수정</div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;padding:4px 0 8px;position:relative">
      <div style="flex:1;min-width:180px;position:relative">
        <div style="font-size:11px;color:var(--t3);margin-bottom:4px">회원 검색 (이름 · ID · 회원번호)</div>
        <input id="honor-search" class="srch" placeholder="검색어 입력" style="width:100%"
          oninput="honorSearchLive(this.value)" onkeydown="if(event.key==='Enter')honorSearch()">
        <div id="honor-search-drop" style="display:none;position:absolute;top:100%;left:0;right:0;z-index:999;background:var(--s1);border:1px solid var(--bd);border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.15);max-height:220px;overflow-y:auto"></div>
      </div>
      <button class="btn ba" onclick="honorSearch()">🔍 검색</button>
    </div>

    <div id="honor-selected" style="display:none;border:1px solid var(--bd);border-radius:10px;padding:16px;background:var(--s2);margin-top:8px">
      <div style="font-size:12px;font-weight:700;margin-bottom:12px;color:var(--blue)">선택된 회원</div>
      <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-start">
        <div style="min-width:160px">
          <div id="hs-name" style="font-size:15px;font-weight:900"></div>
          <div id="hs-info" style="font-size:11px;color:var(--t3);margin-top:2px"></div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;flex:1">

          <!-- 인정 등급 -->
          <div>
            <div style="font-size:10px;color:var(--t3);margin-bottom:4px;font-weight:700">🏅 인정 등급</div>
            <select id="hs-grade" style="background:var(--s1);border:1px solid var(--bd);color:var(--t1);padding:6px 10px;border-radius:8px;font-size:12px;font-family:inherit;outline:none;width:100%">
              <option value="베이직">베이직 (추천 3대)</option>
              <option value="플러스">플러스 (추천 5대)</option>
              <option value="골드">골드 (추천 8대)</option>
              <option value="플래티넘">플래티넘 (추천 12대)</option>
            </select>
          </div>

          <!-- 인정등급 유지 기간 (수정#5) -->
          <div>
            <div style="font-size:10px;color:var(--t3);margin-bottom:4px;font-weight:700">📅 등급 유지 기간</div>
            <select id="hs-grade-months" onchange="updateExpireDates()"
              style="background:var(--s1);border:1px solid var(--bd);color:var(--t1);padding:6px 10px;border-radius:8px;font-size:12px;font-family:inherit;outline:none;width:100%">
              <option value="0">무기한</option>
              <option value="1">1개월</option>
              <option value="2">2개월</option>
              <option value="3">3개월</option>
              <option value="6">6개월</option>
              <option value="12">12개월</option>
            </select>
            <div id="hs-grade-expire-disp" style="font-size:10px;color:var(--amber);margin-top:3px"></div>
          </div>

          <!-- 자격 유지 기간 (수정#5) -->
          <div>
            <div style="font-size:10px;color:var(--t3);margin-bottom:4px;font-weight:700">🎯 수당자격 유지 기간</div>
            <select id="hs-qual-months" onchange="updateExpireDates()"
              style="background:var(--s1);border:1px solid var(--bd);color:var(--t1);padding:6px 10px;border-radius:8px;font-size:12px;font-family:inherit;outline:none;width:100%">
              <option value="0">무기한</option>
              <option value="1">1개월</option>
              <option value="2">2개월</option>
              <option value="3">3개월</option>
              <option value="6">6개월</option>
              <option value="12">12개월</option>
            </select>
            <div id="hs-qual-expire-disp" style="font-size:10px;color:var(--amber);margin-top:3px"></div>
          </div>

          <!-- 자격 활성화 -->
          <div>
            <div style="font-size:10px;color:var(--t3);margin-bottom:4px;font-weight:700">⚡ 자격 활성화</div>
            <select id="hs-active" style="background:var(--s1);border:1px solid var(--bd);color:var(--t1);padding:6px 10px;border-radius:8px;font-size:12px;font-family:inherit;outline:none;width:100%">
              <option value="1">✅ 활성 (수당 반영)</option>
              <option value="0">⛔ 정지 (수당 미반영)</option>
            </select>
          </div>

          <!-- 메모 -->
          <div>
            <div style="font-size:10px;color:var(--t3);margin-bottom:4px;font-weight:700">📝 메모</div>
            <input id="hs-note" placeholder="사유 메모 (선택)"
              style="background:var(--s1);border:1px solid var(--bd);color:var(--t1);padding:6px 10px;border-radius:8px;font-size:12px;font-family:inherit;outline:none;width:100%">
          </div>

          <!-- 자격취득일 (수당 기산일) -->
          <div>
            <div style="font-size:10px;color:var(--t3);margin-bottom:4px;font-weight:700">📌 자격취득일 <span style="color:var(--blue);font-weight:400">(수당 기산일)</span></div>
            <input type="date" id="hs-acquired-date"
              style="background:var(--s1);border:1px solid var(--bd);color:var(--t1);padding:6px 10px;border-radius:8px;font-size:12px;font-family:inherit;outline:none;width:100%">
            <div style="font-size:10px;color:var(--t3);margin-top:3px">미입력: 주 시작일 자동 적용 (해당 주 전체 수당)</div>
          </div>

          <div style="display:flex;align-items:flex-end">
            <button class="btn bp" style="width:100%" onclick="honorSave()">💾 저장</button>
          </div>
        </div>
      </div>
    </div>
    <div id="honor-search-result" style="margin-top:8px"></div>
  </div>

  <!-- 목록 -->
  <div class="card">
    <div class="card-hd">
      <span>🏅 인정자격 회원 목록 <span id="honor-cnt" style="font-size:11px;color:var(--t3);font-weight:400"></span></span>
      <div style="display:flex;gap:6px;align-items:center">
        <div style="display:flex;gap:3px;background:var(--s3);border-radius:7px;padding:2px">
          <button id="hs-sort-reg" class="btn bo on" style="padding:3px 9px;font-size:10px;border-radius:5px" onclick="setHonorSort('reg')">등록순</button>
          <button id="hs-sort-abc" class="btn bo"    style="padding:3px 9px;font-size:10px;border-radius:5px" onclick="setHonorSort('abc')">가나다순</button>
          <button id="hs-sort-no"  class="btn bo"    style="padding:3px 9px;font-size:10px;border-radius:5px" onclick="setHonorSort('no')">회원번호순</button>
        </div>
        <button class="btn bo" onclick="loadHonorList()">🔄 새로고침</button>
      </div>
    </div>
    <div id="honor-list-wrap"><div class="empty-msg">인정자격 회원이 없습니다.</div></div>
  </div>
</div>

<script>
let _honorSelected = null;
let _honorSort = 'reg';
let _honorFullList = [];

function setHonorSort(mode) {
  _honorSort = mode;
  ['reg','abc','no'].forEach(m => {
    const btn = document.getElementById('hs-sort-'+m);
    if (btn) btn.classList.toggle('on', m === mode);
  });
  renderHonorList(_honorFullList);
}
// ── 만료일 계산 & 표시 ──
function updateExpireDates() {
  const gm = parseInt(document.getElementById('hs-grade-months').value) || 0;
  const qm = parseInt(document.getElementById('hs-qual-months').value)  || 0;
  const now = new Date();

  const gDisp = document.getElementById('hs-grade-expire-disp');
  const qDisp = document.getElementById('hs-qual-expire-disp');

  if (gm === 0) {
    gDisp.textContent = '만료 없음';
    gDisp.style.color = 'var(--t3)';
  } else {
    const exp = new Date(now.getFullYear(), now.getMonth() + gm, 1);
    gDisp.textContent = `만료: ${exp.getFullYear()}-${String(exp.getMonth()+1).padStart(2,'0')} 이전까지`;
    gDisp.style.color = 'var(--amber)';
  }
  if (qm === 0) {
    qDisp.textContent = '만료 없음';
    qDisp.style.color = 'var(--t3)';
  } else {
    const exp = new Date(now.getFullYear(), now.getMonth() + qm, 1);
    qDisp.textContent = `만료: ${exp.getFullYear()}-${String(exp.getMonth()+1).padStart(2,'0')} 이전까지`;
    qDisp.style.color = 'var(--amber)';
  }
}

function calcExpireDate(months) {
  if (!months || months === 0) return null;
  const now = new Date();
  const exp = new Date(now.getFullYear(), now.getMonth() + months, 1);
  return exp.getFullYear() + '-' + String(exp.getMonth()+1).padStart(2,'0') + '-01';
}

async function loadHonorPanel() {
  await ensureData(period());
  loadHonorList();
}

async function loadHonorList() {
  const wrap = document.getElementById('honor-list-wrap');
  wrap.innerHTML = '<div class="spin"></div>';
  const d = await apiFetch('api/honor.php?action=list');
  const list = d.data || [];
  document.getElementById('honor-cnt').textContent = '(' + list.length + '명)';
  S.honorMap = {};
  list.forEach(h => { S.honorMap[h.member_no] = h; });
  S.loaded.honor = true;
  _honorFullList = list;

  if (!list.length) {
    wrap.innerHTML = '<div class="empty-msg">인정자격 부여된 회원이 없습니다.</div>';
    return;
  }
  renderHonorList(list);
}

async function renderHonorList(rawList) {
  const wrap = document.getElementById('honor-list-wrap');
  let list = rawList.slice();
  if (_honorSort === 'abc') {
    list.sort((a,b) => (a.name||'').localeCompare(b.name||'', 'ko'));
  } else if (_honorSort === 'no') {
    list.sort((a,b) => (a.member_no||'').localeCompare(b.member_no||'', 'ko', {numeric:true}));
  }

  const gradeColors = { '베이직':'var(--blue)', '플러스':'var(--purple)', '골드':'var(--amber)', '플래티넘':'var(--green)' };
  const depthLabel  = { '베이직':'3대', '플러스':'5대', '골드':'8대', '플래티넘':'12대' };
  const per = period();
  const pvSnap = S.pvMap;
  const today = new Date().toISOString().substring(0,7);

  let html = '<div class="tw"><table><thead><tr>'
    + '<th>#</th><th>이름</th><th>ID</th><th>회원번호</th>'
    + '<th>인정등급</th><th>매칭</th><th>실적등급</th><th>이번달PV</th>'
    + '<th>자격취득일</th><th>등급만료</th><th>자격만료</th>'
    + '<th>활성화</th><th>메모</th><th>관리</th>'
    + '</tr></thead><tbody>';

  const order = { '미달성':0, '베이직':1, '플러스':2, '골드':3, '플래티넘':4 };

  list.forEach(function(h, i) {
    const gc       = gradeColors[h.grade] || 'var(--t2)';
    const dl       = depthLabel[h.grade]  || '-';
    const monthPv  = pvSnap[h.member_no] || 0;
    const m        = S.members[h.member_no] || {};
    const maxGrade = m.max_grade || m.grade || '미달성';
    const pvGrade  = calcGradeJs(monthPv);
    const realGrade = (order[maxGrade]||0) >= (order[pvGrade]||0) ? maxGrade : pvGrade;
    const isBeyond  = (order[realGrade]||0) >= (order[h.grade]||0);

    // 만료 표시
    const gExp = h.grade_expire_date ? h.grade_expire_date.substring(0,7) : null;
    const qExp = h.qual_expire_date  ? h.qual_expire_date.substring(0,7)  : null;
    const gExpired = gExp && gExp <= today;
    const qExpired = qExp && qExp <= today;

    const gExpHtml = gExp
      ? '<span style="font-size:10px;font-weight:700;color:' + (gExpired?'var(--red)':'var(--amber)') + '">'
        + (gExpired ? '⛔ 만료' : '⏳ ' + gExp) + '</span>'
      : '<span style="font-size:10px;color:var(--t3)">무기한</span>';
    const qExpHtml = qExp
      ? '<span style="font-size:10px;font-weight:700;color:' + (qExpired?'var(--red)':'var(--amber)') + '">'
        + (qExpired ? '⛔ 만료' : '⏳ ' + qExp) + '</span>'
      : '<span style="font-size:10px;color:var(--t3)">무기한</span>';

    html += '<tr>'
      + '<td style="color:var(--t3)">'+(i+1)+'</td>'
      + '<td><b>'+(h.name||'')+'</b></td>'
      + '<td class="mono" style="color:var(--t3)">'+(h.login_id||'')+'</td>'
      + '<td class="mono" style="color:var(--t3)">'+h.member_no+'</td>'
      + '<td><span class="gb g'+h.grade+'">'+h.grade+'</span></td>'
      + '<td style="color:'+gc+';font-weight:700;font-size:12px">'+dl+'</td>'
      + '<td><span class="gb g'+realGrade+'">'+realGrade+'</span>'
        + (isBeyond && realGrade !== '미달성'
          ? ' <span style="font-size:9px;color:var(--green);font-weight:700">✅ 실자격</span>'
          : ' <span style="font-size:9px;color:var(--t3)">인정중</span>') + '</td>'
      + '<td class="mono" style="color:var(--blue);font-size:11px">'+(monthPv>0?fmt(monthPv)+' PV':'-')+'</td>'
      + '<td>'+(h.honor_acquired_date
          ? '<span style="font-size:10px;font-weight:700;color:var(--blue)">📌 '+h.honor_acquired_date+'</span>'
          : '<span style="font-size:10px;color:var(--t3)">주시작일 자동</span>')+'</td>'
      + '<td>'+gExpHtml+'</td>'
      + '<td>'+qExpHtml+'</td>'
      + '<td>'+(h.active
          ? '<span style="background:rgba(46,125,50,.12);color:var(--green);padding:2px 8px;border-radius:5px;font-size:11px;font-weight:700">✅ 활성</span>'
          : '<span style="background:rgba(211,47,47,.1);color:var(--red);padding:2px 8px;border-radius:5px;font-size:11px;font-weight:700">⛔ 정지</span>')+'</td>'
      + '<td style="font-size:11px;color:var(--t3)">'+(h.note||'-')+'</td>'
      + '<td style="white-space:nowrap">'
        + '<button onclick="honorEdit(\''+h.member_no+'\')" class="btn bo" style="padding:3px 8px;font-size:10px;margin-right:4px">✏️ 수정</button>'
        + '<button onclick="honorDelete(\''+h.member_no+'\',\''+((h.name||'').replace(/'/g,"\\'"))+'\')" class="btn bo" style="padding:3px 8px;font-size:10px;color:var(--red);border-color:var(--red)">🗑 삭제</button>'
      + '</td></tr>';
  });

  html += '</tbody></table></div>'
    + '<div style="margin-top:12px;padding:10px 14px;background:rgba(26,86,219,.06);border:1px solid rgba(26,86,219,.15);border-radius:8px;font-size:11px;color:var(--t3)">'
    + '💡 <b>자격취득일</b>: 미입력 시 해당 주 시작일 기준 자동 적용 (=해당 주 전체 매출에 수당) · <b>등급 유지 기간</b>: 설정한 개월 수 동안만 인정등급 적용 · <b>자격 유지 기간</b>: 설정한 개월 수 동안만 수당 자격 부여 · 기간 만료 후 자동 제외'
    + '</div>';

  wrap.innerHTML = html;
}

function honorSearchLive(q) {
  const drop = document.getElementById('honor-search-drop');
  if (!q) { drop.style.display='none'; return; }
  const members = Object.values(S.members);
  const results = members.filter(m =>
    (m.name||'').includes(q)||(m.login_id||'').includes(q)||(m.member_no||'').includes(q)
  ).slice(0,12);
  if (!results.length) { drop.style.display='none'; return; }
  drop.innerHTML = results.map(function(m) {
    const g = m.max_grade || m.grade || '미달성';
    const isHonor = !!S.honorMap[m.member_no];
    return '<div onclick="honorSelectMember(\''+m.member_no+'\')"'
      + ' style="padding:9px 14px;cursor:pointer;border-bottom:1px solid var(--bd);display:flex;align-items:center;gap:8px"'
      + ' onmouseover="this.style.background=\'var(--s2)\'" onmouseout="this.style.background=\'\'">'
      + '<span class="gb g'+g+'" style="font-size:10px">'+g+'</span>'
      + '<span style="font-weight:700">'+(m.name||'')+'</span>'
      + '<span style="font-size:10px;color:var(--t3)">'+(m.login_id||'')+' · '+m.member_no+'</span>'
      + (isHonor ? '<span style="margin-left:auto;font-size:9px;background:rgba(26,86,219,.12);color:var(--blue);padding:1px 6px;border-radius:4px">인정중</span>' : '')
      + '</div>';
  }).join('');
  drop.style.display = 'block';
}

function honorSearch() {
  const q = document.getElementById('honor-search').value.trim();
  if (q) honorSearchLive(q);
}

function honorSelectMember(no) {
  document.getElementById('honor-search-drop').style.display = 'none';
  const m = S.members[no];
  if (!m) return;
  _honorSelected = m;
  const existing = S.honorMap[no];
  document.getElementById('hs-name').textContent = m.name || no;
  document.getElementById('hs-info').textContent = 'ID: '+(m.login_id||'-')+' · 회원번호: '+no+' · 현재등급: '+(m.max_grade||m.grade||'미달성');
  document.getElementById('hs-grade').value        = existing ? (existing.grade||'베이직') : '베이직';
  document.getElementById('hs-active').value       = existing ? (existing.active ? '1' : '0') : '1';
  document.getElementById('hs-note').value         = existing ? (existing.note||'') : '';
  document.getElementById('hs-acquired-date').value = existing ? (existing.honor_acquired_date||'') : '';
  document.getElementById('hs-grade-months').value = '0';
  document.getElementById('hs-qual-months').value  = '0';
  updateExpireDates();
  document.getElementById('honor-selected').style.display = '';
  document.getElementById('honor-search').value = m.name || no;
}

function honorEdit(no) {
  const h = S.honorMap[no];
  const m = S.members[no] || {};
  if (!h) return;
  _honorSelected = Object.assign({}, m, { member_no: no });
  document.getElementById('honor-search').value = h.name || no;
  document.getElementById('hs-name').textContent  = h.name || no;
  document.getElementById('hs-info').textContent  = 'ID: '+(h.login_id||'-')+' · 회원번호: '+no;
  document.getElementById('hs-grade').value        = h.grade  || '베이직';
  document.getElementById('hs-active').value       = h.active ? '1' : '0';
  document.getElementById('hs-note').value         = h.note   || '';
  document.getElementById('hs-acquired-date').value = h.honor_acquired_date || '';
  document.getElementById('hs-grade-months').value = '0';
  document.getElementById('hs-qual-months').value  = '0';
  updateExpireDates();
  document.getElementById('honor-selected').style.display = '';
  document.getElementById('honor-selected').scrollIntoView({ behavior:'smooth', block:'center' });
}

async function honorSave() {
  if (!_honorSelected) { alert('회원을 먼저 검색·선택하세요.'); return; }
  const gMonths = parseInt(document.getElementById('hs-grade-months').value) || 0;
  const qMonths = parseInt(document.getElementById('hs-qual-months').value)  || 0;
  const payload = {
    member_no:         _honorSelected.member_no,
    name:              _honorSelected.name || '',
    login_id:          _honorSelected.login_id || '',
    grade:             document.getElementById('hs-grade').value,
    active:            document.getElementById('hs-active').value === '1',
    note:              document.getElementById('hs-note').value.trim(),
    honor_acquired_date: document.getElementById('hs-acquired-date').value || null,
    grade_expire_date: calcExpireDate(gMonths),  // 수정#5
    qual_expire_date:  calcExpireDate(qMonths),  // 수정#5
  };
  try {
    const res = await fetch('api/honor.php?action=save', {
      method:'POST',
      headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
      body: JSON.stringify(payload)
    });
    const d = await res.json();
    if (d.ok) {
      document.getElementById('honor-search-result').innerHTML =
        '<div style="color:var(--green);font-size:12px;padding:6px 0">✅ ' + d.message + '</div>';
      document.getElementById('honor-selected').style.display = 'none';
      _honorSelected = null;
      document.getElementById('honor-search').value = '';
      S.loaded.honor = false;
      await loadHonorList();
      setTimeout(function() { document.getElementById('honor-search-result').innerHTML = ''; }, 3000);
    } else { alert('저장 실패: ' + (d.error||'')); }
  } catch(e) { alert('오류: ' + e.message); }
}

async function honorDelete(no, name) {
  if (!confirm('['+name+'] 인정자격을 삭제하시겠습니까?')) return;
  try {
    const res = await fetch('api/honor.php?action=delete', {
      method:'POST',
      headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
      body: JSON.stringify({ member_no: no })
    });
    const d = await res.json();
    if (d.ok) { S.loaded.honor = false; await loadHonorList(); }
    else alert('삭제 실패: ' + (d.error||''));
  } catch(e) { alert('오류: ' + e.message); }
}

document.addEventListener('click', function(e) {
  const drop = document.getElementById('honor-search-drop');
  const inp  = document.getElementById('honor-search');
  if (drop && inp && !drop.contains(e.target) && e.target !== inp)
    drop.style.display = 'none';
});
</script>
