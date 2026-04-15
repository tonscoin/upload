<?php /* panels/panel-sales.php — 매출·주문 */ ?>
<div class="panel" id="p-sales">
  <div class="card">
    <div class="card-hd">
      <span>🛒 매출·주문 내역
        <span id="sales-period" style="color:var(--t3);font-size:10px"></span>
        <span id="sales-dup-badge" style="display:none;margin-left:8px;background:#e53e3e;color:#fff;font-size:10px;font-weight:900;padding:2px 9px;border-radius:20px;animation:siren 1s infinite alternate;cursor:pointer" onclick="scrollToDuplicates()">⚠️ 중복 <span id="sales-dup-cnt">0</span>건</span>
      </span>
      <div style="display:flex;align-items:center;gap:8px">
        <div style="display:flex;gap:3px;background:var(--s3);border-radius:7px;padding:2px">
          <button id="sales-sort-date" class="btn bo on" style="padding:3px 9px;font-size:10px;border-radius:5px" onclick="setSalesSort('date')">주문일순</button>
          <button id="sales-sort-abc"  class="btn bo"    style="padding:3px 9px;font-size:10px;border-radius:5px" onclick="setSalesSort('abc')">가나다순</button>
          <button id="sales-sort-no"   class="btn bo"    style="padding:3px 9px;font-size:10px;border-radius:5px" onclick="setSalesSort('no')">회원번호순</button>
        </div>
        <span id="sales-sum" style="font-family:'JetBrains Mono',monospace;color:var(--amber);font-size:12px"></span>
      </div>
    </div>

    <!-- 중복 감지 패널 -->
    <div id="sales-dup-panel" style="display:none;margin-bottom:12px;border:2px solid #e53e3e;border-radius:10px;overflow:hidden">
      <div style="background:#e53e3e;color:#fff;padding:8px 14px;font-size:12px;font-weight:900;display:flex;align-items:center;gap:8px">
        🚨 중복 데이터 감지 — 아래 원본 외 항목을 삭제하세요
        <button onclick="$('sales-dup-panel').style.display='none'" style="margin-left:auto;background:rgba(255,255,255,.2);border:none;color:#fff;padding:3px 10px;border-radius:5px;cursor:pointer;font-size:11px">닫기</button>
      </div>
      <div id="sales-dup-list" style="padding:12px 14px;background:rgba(229,62,62,.06)"></div>
    </div>

    <!-- 일괄 작업 툴바 -->
    <div id="sales-toolbar" style="display:none;align-items:center;gap:8px;flex-wrap:wrap;padding:10px 12px;background:var(--s2);border:1px solid var(--bd);border-radius:8px;margin-bottom:10px">
      <label style="display:flex;align-items:center;gap:5px;cursor:pointer;font-size:11px;font-weight:700">
        <input type="checkbox" id="chk-all" onchange="salesCheckAll(this.checked)" style="width:14px;height:14px;cursor:pointer">
        전체선택
      </label>
      <span id="chk-count" style="font-size:11px;color:var(--blue);font-weight:700;min-width:60px"></span>
      <div style="height:16px;width:1px;background:var(--bd)"></div>
      <button class="btn bp" style="padding:5px 12px;font-size:11px" onclick="salesSaveSelected()">💾 선택 서버저장</button>
      <button class="btn br" style="padding:5px 12px;font-size:11px" onclick="salesDeleteSelected()">🗑 선택 삭제</button>
      <button class="btn bo" style="padding:5px 12px;font-size:11px" onclick="salesUncheckAll()">✕ 선택 해제</button>
      <span style="font-size:10px;color:var(--t3);margin-left:4px">💡 서버저장된 항목은 파일 삭제해도 유지됩니다</span>
    </div>

    <div class="tw" id="s-tbl"><div class="spin"></div></div>
  </div>
</div>

<style>
@keyframes siren {
  from { background:#e53e3e; box-shadow:0 0 0 0 rgba(229,62,62,.6); }
  to   { background:#c53030; box-shadow:0 0 0 6px rgba(229,62,62,0); }
}
.dup-row { background: rgba(229,62,62,.06) !important; }
.sale-row-saved td { background: rgba(46,125,50,.03); }
.sale-row-saved td:first-child { border-left: 3px solid var(--green); }
.sale-row-unsaved td:first-child { border-left: 3px solid transparent; }

.sales-chk { width:14px;height:14px;cursor:pointer;accent-color:var(--blue); }
.save-status-badge {
  font-size:9px;font-weight:700;padding:1px 6px;border-radius:4px;white-space:nowrap;
}
.badge-saved   { background:rgba(46,125,50,.12);color:var(--green); }
.badge-unsaved { background:rgba(230,81,0,.1);color:var(--amber); }
</style>

<script>
let _salesAllRows  = [];
let _salesDupKeys  = new Set();
let _selectedIdxs  = new Set();  // 선택된 행의 _row_id
let _salesSort     = 'date';     // 정렬 상태

function setSalesSort(mode) {
  _salesSort = mode;
  ['date','abc','no'].forEach(m => {
    const btn = document.getElementById('sales-sort-'+m);
    if (btn) btn.classList.toggle('on', m === mode);
  });
  if (_salesAllRows.length) renderSalesTable(_salesAllRows, $('s-tbl'));
}

function sortSalesRows(rows) {
  const sorted = rows.slice();
  if (_salesSort === 'abc') {
    sorted.sort((a,b) => {
      const na = a.member_name||a.name||'';
      const nb = b.member_name||b.name||'';
      return na.localeCompare(nb, 'ko');
    });
  } else if (_salesSort === 'no') {
    sorted.sort((a,b) => {
      const getNo = s => {
        const did = s.ddm_id||'';
        const nm  = s.member_name||s.name||'';
        for (const mn in S.members) {
          const mem = S.members[mn];
          if ((mem.login_id && mem.login_id===did)||(mem.name && mem.name===nm && !did)) return mem.member_no||'';
        }
        return '';
      };
      return (getNo(a)).localeCompare(getNo(b), 'ko', {numeric:true});
    });
  } else {
    // date 순 (기본: 주문일 오름차순)
    sorted.sort((a,b) => (a.order_date||'') < (b.order_date||'') ? -1 : (a.order_date||'') > (b.order_date||'') ? 1 : 0);
  }
  return sorted;
}

// ─── 매출·주문 로드 ───
async function loadSalesTable() {
  const per = period();
  $('sales-period').textContent = per;
  const el = $('s-tbl');
  el.innerHTML = '<div class="spin"></div>';
  _selectedIdxs.clear();
  updateToolbar();

  // S.sales에 이미 있으면 캐시 사용, 없으면 API 호출
  let rows;
  if (S.loaded.sales && S.sales.length) {
    rows = [];
    S.sales.forEach((s, idx) => {
      if ((s.order_date||'').startsWith(per)) {
        rows.push({...s, _global_idx: idx, _row_id: idx});
      }
    });
  } else {
    const d = await apiFetch(`api/data.php?action=sales&period=${per}`);
    rows = (d.data || []).map(s => ({
      ...s,
      _global_idx: s._global_idx !== undefined ? s._global_idx : -1,
      _row_id: s._global_idx !== undefined ? s._global_idx : Math.random()
    }));
  }

  _salesAllRows = rows;
  const totPV  = rows.reduce((a,r)=>a+(parseInt(r.pv)||0),0);
  const totAmt = rows.reduce((a,r)=>a+(parseInt(r.amount)||0),0);
  $('sales-sum').textContent = `PV ${fmt(totPV)} / ${fmtW(totAmt)}`;

  if (!rows.length) {
    el.innerHTML = '<div class="empty-msg">해당 기간 데이터 없음</div>';
    $('sales-toolbar').style.display = 'none';
    return;
  }

  $('sales-toolbar').style.display = 'flex';
  detectDuplicates(rows);
  renderSalesTable(rows, el);
}

// ─── 중복 감지 ───
function makeSaleKey(s) {
  const ddmId  = s.ddm_id      || '';
  const name   = s.member_name || s.name || '';
  const date   = s.order_date  || '';
  const amount = String(parseInt(s.amount)||0);
  const pv     = String(parseInt(s.pv)||0);
  return [ddmId, name, date, amount, pv].join('|');
}

function detectDuplicates(rows) {
  const keyCount = {}, keyRows = {};
  rows.forEach(s => {
    const k = makeSaleKey(s);
    keyCount[k] = (keyCount[k]||0) + 1;
    if (!keyRows[k]) keyRows[k] = [];
    keyRows[k].push(s);
  });
  _salesDupKeys = new Set(Object.keys(keyCount).filter(k => keyCount[k] > 1));

  if (_salesDupKeys.size === 0) {
    $('sales-dup-badge').style.display = 'none';
    $('sales-dup-panel').style.display = 'none';
    return;
  }

  let extraCount = 0;
  _salesDupKeys.forEach(k => { extraCount += keyCount[k] - 1; });
  $('sales-dup-cnt').textContent = extraCount;
  $('sales-dup-badge').style.display = '';
  $('sales-dup-panel').style.display = '';

  const dupHtml = [..._salesDupKeys].map(k => {
    const dRows = keyRows[k];
    const s = dRows[0];
    return `<div style="margin-bottom:12px;border:1px solid rgba(229,62,62,.3);border-radius:8px;overflow:hidden">
      <div style="background:rgba(229,62,62,.1);padding:6px 12px;font-size:11px;font-weight:700;color:#c53030">
        ⚠️ 중복 ${dRows.length}건 — ${s.member_name||s.name||''} (${s.ddm_id||''}) / ${s.order_date||''} / ${fmtW(parseInt(s.amount)||0)} / ${fmt(parseInt(s.pv)||0)} PV
      </div>
      <table style="width:100%;font-size:11px">
        <thead><tr style="background:rgba(229,62,62,.06)">
          <th style="padding:5px 10px">#</th><th>주문일</th><th>이름</th><th>DDM ID</th><th>금액</th><th>PV</th><th>저장상태</th><th>삭제</th>
        </tr></thead>
        <tbody>
        ${dRows.map((r,i) => `<tr style="${i===0?'opacity:.6':''}">
          <td style="padding:4px 10px;color:var(--t3)">${r._global_idx >= 0 ? r._global_idx : '?'}</td>
          <td>${r.order_date||''}</td>
          <td><b>${r.member_name||r.name||''}</b></td>
          <td class="mono" style="color:var(--t3)">${r.ddm_id||''}</td>
          <td class="mono" style="color:var(--amber)">${fmtW(parseInt(r.amount)||0)}</td>
          <td class="mono" style="color:var(--blue)">${fmt(parseInt(r.pv)||0)}</td>
          <td>${r.batch_id
            ? '<span class="save-status-badge badge-saved">💾 서버저장됨</span>'
            : '<span class="save-status-badge badge-unsaved">미저장</span>'
          }</td>
          <td>${i===0
            ? '<span style="font-size:9px;color:var(--t3)">원본 유지</span>'
            : `<button onclick="deleteSaleRow(${r._global_idx},'${makeSaleKey(r).replace(/'/g,"\\'")}',this)"
                style="background:#e53e3e;color:#fff;border:none;padding:3px 10px;border-radius:5px;font-size:10px;cursor:pointer;font-weight:700">🗑 삭제</button>`
          }</td>
        </tr>`).join('')}
        </tbody>
      </table>
    </div>`;
  }).join('');
  $('sales-dup-list').innerHTML = dupHtml;
}

// ─── 테이블 렌더 ───
function renderSalesTable(rows, el) {
  const sorted = sortSalesRows(rows);
  el.innerHTML = `<table><thead><tr>
    <th style="width:32px;text-align:center">
      <input type="checkbox" id="chk-head" class="sales-chk" onchange="salesCheckAll(this.checked)">
    </th>
    <th>#</th><th>주문일</th><th>이름</th><th>DDM ID</th><th>회원번호</th><th>상품명</th><th>주문종류</th><th>금액</th><th>PV</th>
    <th>저장상태</th><th>관리</th>
  </tr></thead><tbody>
  ${sorted.map((s,i) => {
    const ot = String(s.order_type||'').trim();
    const isRep = (ot==='2'||ot==='002');
    const typeLabel = isRep
      ? '<span style="background:rgba(0,137,123,.15);color:var(--teal);padding:2px 7px;border-radius:5px;font-size:10px;font-weight:700">🔄 재구매</span>'
      : '<span style="background:rgba(26,86,219,.1);color:var(--blue);padding:2px 7px;border-radius:5px;font-size:10px;font-weight:700">🆕 신규</span>';
    const isDup   = _salesDupKeys.has(makeSaleKey(s));
    const isSaved = !!s.batch_id;
    const rid     = s._row_id;
    const rowClass    = isDup ? 'dup-row' : (isSaved ? 'sale-row-saved' : 'sale-row-unsaved');
    const statusBadge = isSaved
        ? `<span class="save-status-badge badge-saved">💾 저장됨</span>`
        : `<span class="save-status-badge badge-unsaved">⬜ 미저장</span>`;

    const _skey = makeSaleKey(s).replace(/'/g, '__');
    const manageBtn = isSaved
        ? `<button onclick="deleteSaleRow(${s._global_idx}, '${_skey}', this)"
            class="btn bo" style="padding:3px 9px;font-size:10px;color:var(--amber);border-color:var(--amber)">🗑 삭제</button>`
        : `<button onclick="salesSaveSelected();"
            class="btn bo" style="padding:3px 9px;font-size:10px;color:var(--green);border-color:var(--green)">💾 저장</button>`;

    return `<tr class="${rowClass}" id="sale-tr-${rid}">
      <td style="text-align:center">
        <input type="checkbox" class="sales-chk row-chk" data-rid="${rid}" onchange="salesRowCheck(this)"
          style="width:14px;height:14px;cursor:pointer;accent-color:var(--blue)" ${isSaved?'':''}> 
      </td>
      <td style="color:var(--t3)">${i+1}</td>
      <td>${s.order_date||''}</td>
      <td><b>${s.member_name||s.name||''}</b></td>
      <td class="mono" style="color:var(--t3)">${s.ddm_id||''}</td>
      <td class="mono" style="color:var(--t3);font-size:10px">${(()=>{
        // DDM ID 또는 이름으로 회원번호 조회
        const did = s.ddm_id||'';
        const nm  = s.member_name||s.name||'';
        for (const mn in S.members) {
          const mem = S.members[mn];
          if ((mem.login_id && mem.login_id===did) || (mem.name && mem.name===nm && !did)) return mem.member_no||'-';
        }
        return '-';
      })()}</td>
      <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis">${s.product_name||''}</td>
      <td style="text-align:center">${typeLabel}</td>
      <td class="mono" style="color:var(--amber)">${fmtW(parseInt(s.amount)||0)}</td>
      <td class="mono" style="color:var(--blue)">${fmt(parseInt(s.pv)||0)}</td>
      <td>${statusBadge}</td>
      <td style="white-space:nowrap">${manageBtn}</td>
    </tr>`;
  }).join('')}
  </tbody></table>`;
  updateChkCount();
}

// ─── 체크박스 제어 ───
function salesCheckAll(checked) {
  document.querySelectorAll('.row-chk').forEach(chk => {
    chk.checked = checked;
    const rid = chk.dataset.rid;
    if (checked) _selectedIdxs.add(rid);
    else _selectedIdxs.delete(rid);
  });
  const headChk = $('chk-head');
  if (headChk) headChk.checked = checked;
  const allChk = $('chk-all');
  if (allChk) allChk.checked = checked;
  updateChkCount();
}
function salesUncheckAll() { salesCheckAll(false); }
function salesRowCheck(chk) {
  const rid = chk.dataset.rid;
  if (chk.checked) _selectedIdxs.add(rid);
  else _selectedIdxs.delete(rid);
  updateChkCount();
  // 헤드 체크박스 동기화
  const total = document.querySelectorAll('.row-chk').length;
  const checked = document.querySelectorAll('.row-chk:checked').length;
  const headChk = $('chk-head');
  if (headChk) headChk.checked = (checked === total && total > 0);
  const allChk = $('chk-all');
  if (allChk) allChk.checked = (checked === total && total > 0);
  updateChkCount();
}
function updateChkCount() {
  const cnt = document.querySelectorAll('.row-chk:checked').length;
  const el = $('chk-count');
  if (el) el.textContent = cnt > 0 ? `${cnt}건 선택됨` : '';
}
function updateToolbar() {
  const tb = $('sales-toolbar');
  if (tb) tb.style.display = _salesAllRows.length > 0 ? 'flex' : 'none';
}

// ─── 개별 저장 ───

async function salesSaveSelected() {
  const rids = [..._selectedIdxs];
  if (!rids.length) { alert('저장할 항목을 먼저 선택하세요.'); return; }
  const rows = _salesAllRows.filter(r => rids.includes(String(r._row_id)) && r._global_idx >= 0);
  if (!rows.length) { alert('저장 가능한 항목이 없습니다.'); return; }
  try {
    const res  = await fetch('api/save_sales.php', {
      method:'POST',
      headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
      body: JSON.stringify({ indices: rows.map(r => r._global_idx) })
    });
    const d = await res.json();
    if (d.ok) { alert(`${d.saved_count ?? rows.length}건 저장 완료`); loadSalesTable(); }
    else alert('저장 실패: ' + (d.error||''));
  } catch(e) { alert('오류: ' + e.message); }
}

async function salesDeleteSelected() {
  const rids = [..._selectedIdxs];
  if (!rids.length) { alert('삭제할 항목을 먼저 선택하세요.'); return; }
  const rows   = _salesAllRows.filter(r => rids.includes(String(r._row_id)));
  const saved   = rows.filter(r => r.batch_id && r._global_idx >= 0);
  const unsaved = rows.filter(r => !r.batch_id);
  if (!confirm(`총 ${rows.length}건을 삭제하시겠습니까?`)) return;
  // 서버 저장된 건 삭제
  for (const r of saved) {
    try {
      await fetch('api/delete_sale.php', {
        method:'POST',
        headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
        body: JSON.stringify({ global_idx: r._global_idx, sale_key: makeSaleKey(r) })
      });
    } catch(e) {}
  }
  // 미저장 건 로컬에서만 제거
  unsaved.forEach(r => {
    _salesAllRows = _salesAllRows.filter(x => x._row_id !== r._row_id);
    S.sales = S.sales.filter((_, i) => i !== r._global_idx);
  });
  _selectedIdxs.clear();
  loadSalesTable();
}

async function deleteSaleRow(globalIdx, saleKey, btn) {
  if (!confirm('이 매출 건을 삭제하시겠습니까?')) return;
  try {
    const res = await fetch('api/delete_sale.php', {
      method:'POST',
      headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
      body: JSON.stringify({ global_idx: globalIdx, sale_key: saleKey })
    });
    const d = await res.json();
    if (d.ok) loadSalesTable();
    else alert('삭제 실패: ' + (d.error||''));
  } catch(e) { alert('오류: ' + e.message); }
}
</script>
