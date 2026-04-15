<?php /* panels/panel-pv.php — PV 현황 */ ?>
<div class="panel" id="p-pv">
  <div class="card">
    <div class="card-hd"><span>📈 회원별 PV 현황</span></div>
    <div id="pv-tbl"><div class="spin"></div></div>
  </div>
</div>

<script>
// ─── PV 현황 ───
let _pvAllRows = [];
let _pvSort = 'pv';

async function loadPV() {
  const per = period();
  const el  = $('pv-tbl');
  el.innerHTML = '<div class="spin"></div>';
  let rows;
  if (S.loaded.members && S.loaded.sales) {
    const pv = {};
    S.sales.filter(s=>(s.order_date||'').startsWith(per)).forEach(s=>{
      const no = findMemberNo(s); if(!no) return;
      pv[no] = (pv[no]||0)+(s.pv||0);
    });
    rows = Object.values(S.members).map(m => {
      const monthPv = pv[m.member_no]||0;
      return {...m, personal_pv:monthPv, grade:getEffectiveGrade(m.member_no, monthPv)};
    });
    S.pvMap = pv;
  } else {
    const d = await apiFetch(`api/data.php?action=pv&period=${per}`);
    rows = d.data||[]; S.pvMap = d.pv_map||{};
    rows = rows.map(r=>({...r, grade:getEffectiveGrade(r.member_no, r.personal_pv||0)}));
  }
  if (!rows.length) { el.innerHTML='<div class="empty-msg">데이터 없음</div>'; return; }
  _pvAllRows = rows;
  pvRenderTable();
}

function pvSortBy(mode) {
  _pvSort = mode;
  pvRenderTable();
}

function pvRenderTable() {
  const el = $('pv-tbl');
  if (!el) return;
  const rows = _pvAllRows.slice();

  if (_pvSort === 'abc') {
    rows.sort((a,b) => (a.name||'').localeCompare(b.name||'', 'ko'));
  } else if (_pvSort === 'no') {
    rows.sort((a,b) => (a.member_no||'').localeCompare(b.member_no||''));
  } else {
    rows.sort((a,b) => b.personal_pv - a.personal_pv);
  }

  const total = rows.reduce((a,r)=>a+(r.personal_pv||0),0);

  el.innerHTML = `
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;flex-wrap:wrap">
      <span style="font-size:11px;color:var(--t3)">정렬:</span>
      <div style="display:flex;gap:3px;background:var(--s3);border-radius:6px;padding:2px">
        <button id="pv-sort-pv"  class="btn bo${_pvSort==='pv' ?' on':''}" style="padding:2px 10px;font-size:10px;border-radius:5px" onclick="pvSortBy('pv')">이번달 PV순</button>
        <button id="pv-sort-abc" class="btn bo${_pvSort==='abc'?' on':''}" style="padding:2px 10px;font-size:10px;border-radius:5px" onclick="pvSortBy('abc')">가나다순</button>
        <button id="pv-sort-no"  class="btn bo${_pvSort==='no' ?' on':''}" style="padding:2px 10px;font-size:10px;border-radius:5px" onclick="pvSortBy('no')">회원번호순</button>
      </div>
      <span style="font-size:11px;color:var(--t3);margin-left:4px">${rows.length}명</span>
    </div>
    <div class="tw"><table><thead><tr>
      <th>#</th><th>회원번호</th><th>이름</th><th>ID</th>
      <th>이번달 PV</th><th>달성 등급</th><th>추천인</th><th>위치</th>
    </tr></thead><tbody>
    ${rows.map((r,i)=>`<tr onclick="openMo('${r.member_no}')">
      <td style="color:var(--t3)">${i+1}</td>
      <td class="mono" style="color:var(--t3)">${r.member_no||''}</td>
      <td><b>${r.name||''}</b></td>
      <td class="mono" style="color:var(--t3)">${r.login_id||''}</td>
      <td class="mono" style="color:${r.personal_pv>0?'var(--blue)':'var(--t2)'}"><b>${fmt(r.personal_pv||0)}</b></td>
      <td><span class="gb g${r.grade}">${r.grade}</span></td>
      <td>${r.referrer_name||r.referrer_no||''}</td>
      <td><span class="${r.position==='L'?'pl':'pr'}">${r.position||''}</span></td>
    </tr>`).join('')}
    <tr style="background:var(--s2)">
      <td colspan="4" style="font-weight:700">합계</td>
      <td class="mono" style="color:var(--green);font-weight:700">${fmt(total)}</td>
      <td colspan="3"></td>
    </tr>
    </tbody></table></div>`;
}
</script>
