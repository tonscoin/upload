<?php /* panels/panel-comm-center.php — 센터수당 */ ?>
<div class="panel" id="p-comm-center">

  <!-- 센터 관리 -->
  <div class="card" style="margin-bottom:14px">
    <div class="card-hd">
      🏢 센터 관리
      <button class="btn ba" onclick="centerAddOpen()">➕ 센터 추가</button>
    </div>
    <div id="center-mgr-list"><div class="spin"></div></div>
  </div>

  <!-- 센터수당 조회 -->
  <div class="card">
    <div class="card-hd">
      📊 센터수당 조회
      <span style="font-size:11px;color:var(--t3);font-weight:400">센터 인정일 이후 산하 회원 구매 PV × 5% · 월지급</span>
    </div>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:14px">
      <input type="month" id="center-month" value="<?= date('Y-m') ?>"
        style="background:var(--s1);border:1px solid var(--bd);color:var(--t1);padding:6px 10px;border-radius:8px;font-size:11px;font-family:inherit;outline:none">
      <button class="btn bp" onclick="loadCommCenter()">📊 조회</button>
    </div>
    <div id="center-kpi" class="comm-kpi-row" style="display:none"></div>
    <div id="center-tbl"><div class="empty-msg">기간 선택 후 [조회] 버튼을 누르세요.</div></div>
  </div>
</div>

<!-- 센터 추가/수정 모달 -->
<div id="center-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:14px;padding:24px;width:440px;max-width:95vw;box-shadow:0 8px 40px rgba(0,0,0,.2)">
    <div style="font-size:15px;font-weight:900;margin-bottom:16px">🏢 센터 등록/수정</div>
    <input type="hidden" id="cm-member-no">
    <div style="margin-bottom:10px">
      <label style="font-size:11px;font-weight:700;color:var(--t3);display:block;margin-bottom:4px">회원 검색</label>
      <div style="display:flex;gap:6px">
        <input class="srch" id="cm-search" placeholder="이름·ID·회원번호" style="flex:1" oninput="cmSearchLive(this.value)">
        <button class="btn ba" onclick="cmSearch()">🔍</button>
      </div>
      <div id="cm-search-drop" style="display:none;border:1px solid var(--bd);border-radius:8px;max-height:160px;overflow-y:auto;margin-top:4px;background:#fff;box-shadow:0 4px 12px rgba(0,0,0,.12)"></div>
    </div>
    <div id="cm-selected" style="display:none;background:var(--s2);border-radius:8px;padding:10px 12px;margin-bottom:10px;font-size:12px">
      <b id="cm-sel-name"></b> <span id="cm-sel-id" style="color:var(--t3);margin-left:6px"></span>
    </div>
    <div style="margin-bottom:10px">
      <label style="font-size:11px;font-weight:700;color:var(--t3);display:block;margin-bottom:4px">센터 인정일 <span style="color:var(--red)">*</span></label>
      <input type="date" id="cm-start-date" value="<?= date('Y-m-d') ?>"
        style="width:100%;padding:9px 11px;border:1.5px solid var(--bd);border-radius:8px;font-size:12px;font-family:inherit;outline:none">
    </div>
    <div style="margin-bottom:16px">
      <label style="font-size:11px;font-weight:700;color:var(--t3);display:block;margin-bottom:4px">메모</label>
      <input type="text" id="cm-note" placeholder="선택사항"
        style="width:100%;padding:9px 11px;border:1.5px solid var(--bd);border-radius:8px;font-size:12px;font-family:inherit;outline:none">
    </div>
    <div style="display:flex;gap:8px;justify-content:flex-end">
      <button class="btn bo" onclick="centerModalClose()">취소</button>
      <button class="btn ba" onclick="centerSave()">💾 저장</button>
    </div>
  </div>
</div>

<script>
let _centers = [];

async function loadCenterMgr() {
  const d = await apiFetch('api/centers.php?action=list');
  _centers = d.data || [];
  const el = document.getElementById('center-mgr-list');
  if (!_centers.length) {
    el.innerHTML = '<div class="empty-msg">등록된 센터가 없습니다. [센터 추가] 버튼을 눌러 추가하세요.</div>';
    return;
  }
  el.innerHTML = `<div class="tw"><table>
    <thead><tr><th>#</th><th>이름</th><th>ID</th><th>회원번호</th><th>인정일</th><th>메모</th><th>관리</th></tr></thead>
    <tbody>${_centers.map((c,i) => `<tr>
      <td style="color:var(--t3)">${i+1}</td>
      <td><b>${c.name||''}</b></td>
      <td class="mono" style="color:var(--t3)">${c.login_id||''}</td>
      <td class="mono" style="color:var(--t3)">${c.member_no||''}</td>
      <td style="color:var(--blue);font-weight:700">${c.start_date||''}</td>
      <td style="color:var(--t3);font-size:11px">${c.note||'-'}</td>
      <td style="white-space:nowrap">
        <button class="btn bo" style="padding:3px 8px;font-size:10px;margin-right:4px" onclick="centerEdit('${c.member_no}')">✏️ 수정</button>
        <button class="btn bo" style="padding:3px 8px;font-size:10px;color:var(--red);border-color:var(--red)" onclick="centerDel('${c.member_no}','${(c.name||'').replace(/'/g,"\\'")}')">🗑 삭제</button>
      </td>
    </tr>`).join('')}</tbody>
  </table></div>`;
}

function centerAddOpen() {
  document.getElementById('cm-member-no').value = '';
  document.getElementById('cm-search').value = '';
  document.getElementById('cm-search-drop').style.display = 'none';
  document.getElementById('cm-selected').style.display = 'none';
  document.getElementById('cm-start-date').value = new Date().toISOString().substring(0,10);
  document.getElementById('cm-note').value = '';
  document.getElementById('center-modal').style.display = 'flex';
}

function centerEdit(memberNo) {
  const c = _centers.find(x => x.member_no === memberNo);
  if (!c) return;
  document.getElementById('cm-member-no').value = c.member_no;
  document.getElementById('cm-sel-name').textContent = c.name || '';
  document.getElementById('cm-sel-id').textContent = c.login_id || '';
  document.getElementById('cm-selected').style.display = '';
  document.getElementById('cm-start-date').value = c.start_date || '';
  document.getElementById('cm-note').value = c.note || '';
  document.getElementById('center-modal').style.display = 'flex';
}

function centerModalClose() {
  document.getElementById('center-modal').style.display = 'none';
}

async function centerSave() {
  const memberNo = document.getElementById('cm-member-no').value;
  const startDate = document.getElementById('cm-start-date').value;
  if (!memberNo) { alert('회원을 선택해 주세요.'); return; }
  if (!startDate) { alert('인정일을 입력해 주세요.'); return; }
  const m = S.members[memberNo] || {};
  const res = await fetch('api/centers.php?action=save', {
    method:'POST',
    headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
    body: JSON.stringify({
      member_no: memberNo,
      login_id: m.login_id || '',
      name: m.name || '',
      start_date: startDate,
      note: document.getElementById('cm-note').value,
    })
  });
  const d = await res.json();
  if (d.ok) { centerModalClose(); loadCenterMgr(); }
  else alert('저장 실패: ' + (d.error||''));
}

async function centerDel(memberNo, name) {
  if (!confirm(`[${name}] 센터를 삭제하시겠습니까?`)) return;
  const res = await fetch('api/centers.php?action=delete', {
    method:'POST',
    headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
    body: JSON.stringify({ member_no: memberNo })
  });
  const d = await res.json();
  if (d.ok) loadCenterMgr();
  else alert('삭제 실패');
}

// 회원 검색
function cmSearchLive(q) {
  if (!q || q.length < 1) { document.getElementById('cm-search-drop').style.display='none'; return; }
  const results = Object.values(S.members).filter(m =>
    (m.name||'').includes(q)||(m.login_id||'').includes(q)||(m.member_no||'').includes(q)
  ).slice(0,10);
  const drop = document.getElementById('cm-search-drop');
  if (!results.length) { drop.style.display='none'; return; }
  drop.innerHTML = results.map(m =>
    `<div onclick="centerSelectMember('${m.member_no}')"
      style="padding:9px 12px;cursor:pointer;border-bottom:1px solid var(--bd);font-size:12px"
      onmouseover="this.style.background='var(--s2)'" onmouseout="this.style.background=''">
      <b>${m.name}</b>
      <span style="color:var(--t3);margin-left:8px;font-size:11px">${m.login_id||''} · ${m.member_no}</span>
    </div>`
  ).join('');
  drop.style.display = '';
}

function cmSearch() { cmSearchLive(document.getElementById('cm-search').value); }

function centerSelectMember(no) {
  const m = S.members[no] || {};
  document.getElementById('cm-member-no').value = no;
  document.getElementById('cm-sel-name').textContent = m.name || no;
  document.getElementById('cm-sel-id').textContent = (m.login_id||'') + ' · ' + no;
  document.getElementById('cm-selected').style.display = '';
  document.getElementById('cm-search-drop').style.display = 'none';
  document.getElementById('cm-search').value = m.name || '';
}

// 센터수당 조회
async function loadCommCenter() {
  const periodVal = document.getElementById('center-month')?.value;
  if (!periodVal) return;
  const el = document.getElementById('center-tbl');
  el.innerHTML = '<div class="spin"></div>';
  await ensureData(periodVal);

  // centers.json 다시 로드
  const cd = await apiFetch('api/centers.php?action=list');
  _centers = cd.data || [];

  if (!_centers.length) {
    el.innerHTML = '<div class="empty-msg">등록된 센터가 없습니다. 위에서 센터를 추가하세요.</div>';
    return;
  }

  const m = S.members;
  const allSales = S.sales.filter(s => (s.order_date||'').startsWith(periodVal));

  // 후원 하위 맵 (센터 산하 = 후원 구조)
  const sponsorCh = {};
  Object.values(m).forEach(mb => {
    if (mb.sponsor_no) {
      if (!sponsorCh[mb.sponsor_no]) sponsorCh[mb.sponsor_no] = [];
      sponsorCh[mb.sponsor_no].push(mb.member_no);
    }
  });

  // 하위 전체 회원 목록
  function getSubMembers(rootNo) {
    const subs = []; const queue = [rootNo]; const visited = {};
    while (queue.length) {
      const cur = queue.shift();
      if (visited[cur]) continue; visited[cur] = true;
      (sponsorCh[cur]||[]).forEach(child => { subs.push(child); queue.push(child); });
    }
    return subs;
  }

  const rows = [];
  _centers.forEach(center => {
    const no = center.member_no;
    const startDate = center.start_date || '1900-01-01'; // 센터 인정일
    if (!m[no]) return;

    const subMembers = getSubMembers(no);

    // 인정일 이후 산하 회원(본인 포함) 구매 PV만 합산
    let subPv = 0;
    let ownPv = 0;
    allSales.forEach(s => {
      const saleDate = s.order_date || '';
      if (saleDate < startDate) return; // 인정일 이전 제외
      const buyer = findMemberNo(s);
      if (!buyer) return;
      const pv = parseInt(s.pv) || 0;
      if (buyer === no) { ownPv += pv; }
      else if (subMembers.includes(buyer)) { subPv += pv; }
    });

    const totalPv = ownPv + subPv;
    const amt = (Math.floor(totalPv * 0.05 / 10)) * 10; // 10원 단위 절삭

    rows.push({
      member_no: no,
      name: m[no]?.name || no,
      login_id: m[no]?.login_id || '',
      grade: getEffectiveGrade(no, ownPv),
      start_date: startDate,
      own_pv: ownPv,
      sub_pv: subPv,
      total_pv: totalPv,
      total: amt,
    });
  });

  rows.sort((a,b) => b.total - a.total);
  const grandTotal = rows.reduce((s,r) => s+r.total, 0);
  const maxV = rows[0]?.total || 1;

  showKpi('center-kpi', `
    <div class="comm-kpi"><div class="comm-kpi-v" style="color:var(--green)">${fmtW(grandTotal)}</div><div class="comm-kpi-l">총 지급 수당</div></div>
    <div class="comm-kpi"><div class="comm-kpi-v" style="color:var(--blue)">${rows.length}</div><div class="comm-kpi-l">센터 수</div></div>
    <div class="comm-kpi"><div class="comm-kpi-v" style="color:var(--amber)">${rows.length>0?fmtW(Math.round(grandTotal/rows.length)):'-'}</div><div class="comm-kpi-l">평균 수당</div></div>
  `);

  if (!rows.length) { el.innerHTML = '<div class="empty-msg">해당 기간 수당 없음</div>'; return; }

  el.innerHTML = `<div class="tw"><table>
    <thead><tr>
      <th>#</th><th>이름</th><th>등급</th><th>인정일</th>
      <th>본인 PV</th><th>산하 PV</th><th>합계 PV</th>
      <th style="text-align:right">수당(5%)</th>
      <th style="min-width:80px"></th>
    </tr></thead>
    <tbody>${rows.map((r,i) => `<tr onclick="openMo('${r.member_no}')" style="cursor:pointer">
      <td style="color:var(--t3)">${i+1}</td>
      <td><b>${r.name}</b><br><span style="font-size:9px;color:var(--t3)">${r.member_no} · ${r.login_id}</span></td>
      <td><span class="gb g${r.grade}">${r.grade}</span></td>
      <td style="color:var(--blue);font-size:11px;font-weight:700">${r.start_date}</td>
      <td class="mono" style="color:var(--t3)">${fmt(r.own_pv)}</td>
      <td class="mono" style="font-weight:700">${fmt(r.sub_pv)}</td>
      <td class="mono" style="color:var(--blue)">${fmt(r.total_pv)}</td>
      <td style="text-align:right;font-weight:900;color:var(--teal)">${fmtW(r.total)}</td>
      <td><div style="height:6px;background:var(--s3);border-radius:3px">
        <div style="width:${Math.round(r.total/maxV*100)}%;height:100%;background:linear-gradient(90deg,#00695c,#4db6ac);border-radius:3px"></div>
      </div></td>
    </tr>`).join('')}</tbody>
  </table></div>`;
}
</script>
