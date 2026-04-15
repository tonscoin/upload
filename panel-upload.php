<?php /* panels/panel-upload.php — 파일 업로드 */ ?>
<div class="panel" id="p-upload">
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;max-width:920px">

    <!-- 회원정보 업로드 -->
    <div class="card">
      <div class="card-hd">👥 회원정보 업로드</div>
      <div style="font-size:11px;color:var(--t3);margin-bottom:12px;line-height:1.7">
        중복 회원번호는 자동 업데이트, 신규 회원은 추가됩니다.<br>
        필수 컬럼: <code style="background:var(--s3);padding:1px 5px;border-radius:3px">회원번호, 이름, ID, 등급, 추천인번호, 후원인번호, 위치</code>
      </div>
      <div class="umode">
        <button class="umode-btn on" id="um-s" onclick="setUMode('m','server',this)">☁️ 서버 저장</button>
        <button class="umode-btn"    id="um-l" onclick="setUMode('m','local',this)">🖥️ PC 전용</button>
      </div>
      <div class="udrop" id="mdrop" onclick="$('mfile').click()"
           ondrop="onDrop(event,'m')" ondragover="event.preventDefault();this.classList.add('drag')" ondragleave="this.classList.remove('drag')">
        <h3>📂 회원정보 CSV</h3>
        <p>드래그 또는 클릭 · CSV UTF-8 인코딩</p>
      </div>
      <input type="file" id="mfile" accept=".csv,.txt" style="display:none" onchange="onFile(this,'m')">
      <div class="uprog"><div class="uprog-fill" id="mprog"></div></div>
      <div class="ures" id="mres"></div>
      <div id="m-history" style="margin-top:14px"></div>
    </div>

    <!-- 매출 업로드 -->
    <div class="card">
      <div class="card-hd">🛒 매출 업로드</div>
      <div style="font-size:11px;color:var(--t3);margin-bottom:12px;line-height:1.7">
        중복 주문(주문일+ID+상품명+금액 동일)은 자동 <b style="color:var(--blue)">갱신</b>, 새 주문은 <b style="color:var(--green)">신규 추가</b>됩니다.<br>
        필수 컬럼: <code style="background:var(--s3);padding:1px 5px;border-radius:3px">주문일, 이름 or DDM ID, BV, 주문금액</code>
      </div>
      <div class="umode">
        <button class="umode-btn on" id="us-s" onclick="setUMode('s','server',this)">☁️ 서버 저장</button>
        <button class="umode-btn"    id="us-l" onclick="setUMode('s','local',this)">🖥️ PC 전용</button>
      </div>
      <div class="udrop" id="sdrop" onclick="$('sfile').click()"
           ondrop="onDrop(event,'s')" ondragover="event.preventDefault();this.classList.add('drag')" ondragleave="this.classList.remove('drag')">
        <h3>📂 매출 CSV</h3>
        <p>드래그 또는 클릭 · CSV UTF-8 인코딩</p>
      </div>
      <input type="file" id="sfile" accept=".csv,.txt" style="display:none" onchange="onFile(this,'s')">
      <div class="uprog"><div class="uprog-fill" id="sprog"></div></div>
      <div class="ures" id="sres"></div>
      <div id="s-batches" style="margin-top:14px"></div>
    </div>
  </div>

  <!-- 안내 -->
  <div class="card" style="max-width:920px;margin-top:0">
    <div class="card-hd">ℹ️ CSV 업로드 안내</div>
    <div style="font-size:11px;line-height:2.2;color:var(--t2)">
      <b style="color:var(--t1)">☁️ 서버 저장</b> — 서버 data/ 폴더에 JSON으로 저장됩니다. 재접속 후에도 유지되며 수당 계산에 자동 반영됩니다.<br>
      <b style="color:var(--t1)">🖥️ PC 전용</b> — 파일이 서버에 전송되지 않습니다. 브라우저 메모리에서만 처리되며 새로고침 시 초기화됩니다.<br>
      <b style="color:var(--amber)">CSV 저장 방법</b> — 엑셀에서 <b>「다른 이름으로 저장」→「CSV UTF-8(쉼표로 구분)」</b> 선택 후 업로드하세요.
    </div>
  </div>
</div>

<script>
// ─── 업로드 패널 ───

function setUMode(t, mode, el) {
  S.uMode[t] = mode;
  const prefix = t === 'm' ? 'um' : 'us';
  [$(`${prefix}-s`), $(`${prefix}-l`)].forEach(b => b.classList.remove('on'));
  el.classList.add('on');
}

function onDrop(e, t) {
  e.preventDefault();
  const drop = t === 'm' ? $('mdrop') : $('sdrop');
  if (drop) drop.classList.remove('drag');
  const f = e.dataTransfer.files[0];
  if (f) processFile(f, t);
}

function onFile(inp, t) {
  if (inp.files[0]) processFile(inp.files[0], t);
}

function processFile(file, t) {
  const pfx  = t === 'm' ? 'm' : 's';
  const mode = S.uMode[t] || 'server';
  const prog = $(pfx + 'prog');
  const res  = $(pfx + 'res');

  prog.style.width    = '20%';
  res.style.display   = 'none';
  res.textContent     = '';

  if (mode === 'local') {
    // ── PC 전용 로컬 처리 ──
    const reader = new FileReader();
    reader.onload = ev => {
      try {
        prog.style.width = '70%';
        const rows = parseCSVLocal(ev.target.result);
        if (!rows.length) { showRes(res, '❌ 파싱된 데이터가 없습니다. CSV 형식을 확인하세요.', false); prog.style.width='0'; return; }
        prog.style.width = '100%';
        if (t === 'm') {
          S.members = {};
          rows.forEach(r => { const m = toMember(r); if (m.member_no) S.members[m.member_no] = m; });
          S.loaded.members = true;
          showRes(res, `✅ PC 전용 로드 완료\n${Object.keys(S.members).length}명 (서버 미저장)`, true);
        } else {
          const parsed = rows.map(toSale).filter(r => (r.member_name || r.ddm_id) && r._cancelled !== 'O');
          // _cancelled 임시 필드 제거 후 저장
          S.sales = parsed.map(r => { const s = {...r}; delete s._cancelled; return s; });
          S.loaded.sales = true;
          showRes(res, `✅ PC 전용 로드 완료\n${S.sales.length}건 (서버 미저장)`, true);
        }
      } catch(e) {
        prog.style.width = '0';
        showRes(res, '❌ 파싱 오류: ' + e.message, false);
      }
    };
    reader.onerror = () => { prog.style.width='0'; showRes(res,'❌ 파일 읽기 실패',false); };
    reader.readAsText(file, 'UTF-8');

  } else {
    // ── 서버 저장 ──
    const fd = new FormData();
    fd.append('file', file);
    fd.append('type', t === 'm' ? 'members' : 'sales');
    prog.style.width = '40%';

    fetch('api/upload.php', {
      method: 'POST',
      body: fd,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(async response => {
      prog.style.width = '80%';
      const text = await response.text();
      if (!text || text.trim() === '') {
        throw new Error('서버 응답이 비어 있습니다 (서버 오류 로그를 확인하세요)');
      }
      // JSON 파싱 시도
      let d;
      try {
        d = JSON.parse(text);
      } catch(parseErr) {
        // 서버 오류 메시지 포함 가능성 — 앞부분 표시
        const preview = text.substring(0, 200).replace(/</g,'&lt;');
        throw new Error('서버 응답이 JSON이 아닙니다:\n' + text.substring(0, 300));
      }
      return d;
    })
    .then(d => {
      prog.style.width = '100%';
      if (!d.ok) {
        showRes(res, '❌ ' + (d.error || '업로드 실패'), false);
        return;
      }
      const stats = d.stats || {};
      if (t === 'm') {
        showRes(res,
          `✅ 서버 저장 완료!\n신규: ${stats.new_count ?? 0}명 / 갱신: ${stats.updated_count ?? 0}명 / 총: ${stats.total_count ?? 0}명`,
          true);
        S.loaded.members = false;
        S.members = {};
      } else {
        showRes(res,
          `✅ 서버 저장 완료!\n신규: ${stats.new_count ?? 0}건 / 갱신: ${stats.updated_count ?? 0}건 / 건너뜀: ${stats.skipped_count ?? 0}건\nPV: ${(stats.total_pv ?? 0).toLocaleString()} / 금액: ₩${(stats.total_amount ?? 0).toLocaleString()}`,
          true);
        S.loaded.sales = false;
        S.sales = [];
      }
      loadUploadHistory();
      // 파일 input 초기화
      if (t === 'm') $('mfile').value = '';
      else           $('sfile').value = '';
    })
    .catch(err => {
      prog.style.width = '0';
      showRes(res, '❌ ' + err.message, false);
    });
  }
}

// ── 업로드 이력 로드 ──
async function loadUploadHistory() {
  try {
    const [mRes, sRes] = await Promise.all([
      safeFetch('api/upload_history.php?type=members'),
      safeFetch('api/upload_history.php?type=sales'),
    ]);
    if (mRes && mRes.ok) renderMemberHistory(mRes.data || []);
    if (sRes && sRes.ok) renderSalesBatches(sRes.data || []);
  } catch(e) {
    console.error('업로드 이력 로드 실패:', e);
  }
}

async function safeFetch(url) {
  try {
    const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const text = await r.text();
    if (!text || !text.trim()) return null;
    return JSON.parse(text);
  } catch(e) {
    console.error('safeFetch error:', url, e);
    return null;
  }
}

function renderMemberHistory(history) {
  const el = $('m-history');
  if (!el) return;
  if (!history.length) {
    el.innerHTML = '<p style="color:var(--t3);font-size:11px">업로드 이력이 없습니다.</p>';
    return;
  }
  let html = '<div style="border-top:1px solid var(--bd);padding-top:12px"><div style="font-size:11px;font-weight:700;margin-bottom:8px">📋 최근 업로드 이력</div>';
  history.slice(0, 5).forEach(h => {
    const { new_count=0, updated_count=0, total_count=0 } = h.stats || {};
    const date = new Date((h.timestamp || 0) * 1000).toLocaleString('ko-KR');
    html += `<div style="padding:10px 12px;background:var(--s2);border-radius:7px;margin-bottom:6px;font-size:11px;border:1px solid var(--bd)">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px">
        <div style="flex:1">
          <b>${h.filename}</b> <span style="color:var(--t3);margin-left:6px">${date}</span>
          <div style="margin-top:4px">
            <span style="color:var(--green);font-weight:700">신규 ${new_count}명</span> /
            <span style="color:var(--blue);font-weight:700">갱신 ${updated_count}명</span> /
            <span style="color:var(--t2)">총 ${total_count}명</span>
          </div>
        </div>
        <button onclick="deleteMemberUpload('${h.upload_id}')" class="btn br" style="padding:5px 10px;font-size:10px;flex-shrink:0">🗑️ 삭제</button>
      </div></div>`;
  });
  html += '</div>';
  el.innerHTML = html;
}

function renderSalesBatches(history) {
  const el = $('s-batches');
  if (!el) return;
  if (!history.length) {
    el.innerHTML = '<p style="color:var(--t3);font-size:11px">업로드된 배치가 없습니다.</p>';
    return;
  }
  let html = '<div style="border-top:1px solid var(--bd);padding-top:12px"><div style="font-size:11px;font-weight:700;margin-bottom:8px">📦 업로드된 매출 배치</div>';
  history.forEach(h => {
    const { count=0, new_count=0, updated_count=0, skipped_count=0, total_pv=0, total_amount=0, date_from='', date_to='' } = h.stats || {};
    const date = new Date((h.timestamp || 0) * 1000).toLocaleString('ko-KR');
    html += `<div style="padding:10px 12px;background:rgba(26,86,219,.04);border-radius:7px;margin-bottom:8px;font-size:11px;border:1px solid rgba(26,86,219,.15)">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px">
        <div style="flex:1">
          <b>${h.filename}</b> <span style="color:var(--t3);margin-left:6px">${date}</span>
          <div style="margin-top:5px;line-height:1.8">
            <span style="color:var(--green);font-weight:700">신규 ${Number(new_count).toLocaleString()}건</span> /
            <span style="color:var(--blue);font-weight:700">갱신 ${Number(updated_count).toLocaleString()}건</span> /
            건너뜀 ${Number(skipped_count).toLocaleString()}건<br>
            PV <b style="color:var(--green)">${Number(total_pv).toLocaleString()}</b> /
            금액 <b style="color:var(--amber)">₩${Number(total_amount).toLocaleString()}</b>
            ${date_from && date_to ? `<br>기간: ${date_from} ~ ${date_to}` : ''}
          </div>
        </div>
        <button onclick="deleteSalesBatch('${h.upload_id}')" class="btn br" style="padding:5px 10px;font-size:10px;flex-shrink:0">🗑️ 삭제</button>
      </div></div>`;
  });
  html += '</div>';
  el.innerHTML = html;
}

async function deleteMemberUpload(uploadId) {
  if (!confirm('이 업로드 이력을 삭제하시겠습니까?\n회원 목록 전체 데이터도 함께 삭제됩니다.')) return;
  try {
    const r = await fetch('api/delete_upload.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({ upload_id: uploadId, type: 'members' })
    });
    const text = await r.text();
    if (!text.trim()) { alert('서버 응답 없음'); return; }
    const d = JSON.parse(text);
    if (d.ok) {
      alert('회원 데이터 및 업로드 이력이 삭제되었습니다.');
      S.loaded.members = false;
      S.members = {};
      await loadUploadHistory();
    } else {
      alert('삭제 실패: ' + (d.error || '알 수 없는 오류'));
    }
  } catch(e) {
    alert('삭제 중 오류: ' + e.message);
  }
}

async function deleteSalesBatch(uploadId) {
  if (!confirm('이 배치를 삭제하시겠습니까?\n관련된 모든 매출 데이터가 삭제됩니다.')) return;
  try {
    const res = await safeFetch('api/delete_upload.php');  // POST fallback
    const r2  = await fetch('api/delete_upload.php', {
      method: 'POST',
      headers: { 'Content-Type':'application/json', 'X-Requested-With':'XMLHttpRequest' },
      body: JSON.stringify({ upload_id: uploadId, type: 'sales' })
    });
    const text = await r2.text();
    if (!text.trim()) { alert('서버 응답 없음'); return; }
    const d = JSON.parse(text);
    if (d.ok) {
      alert(`배치 삭제 완료!\n${d.deleted_count ?? 0}건의 매출 데이터가 제거되었습니다.`);
      S.loaded.sales = false;
      S.sales = [];
      await loadUploadHistory();
    } else {
      alert('삭제 실패: ' + (d.error || '알 수 없는 오류'));
    }
  } catch(e) {
    alert('삭제 중 오류: ' + e.message);
  }
}
</script>
