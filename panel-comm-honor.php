<?php /* panels/panel-comm-honor.php — 인정자격회원 수당 분석 */ ?>
<div class="panel" id="p-comm-honor">

  <!-- 헤더 -->
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap;padding:16px 18px;background:var(--s1);border:1px solid var(--bd);border-radius:12px">
    <div style="font-size:28px">🏅</div>
    <div>
      <div style="font-size:14px;font-weight:900;margin-bottom:2px">인정자격회원 수당 분석</div>
      <div style="font-size:11px;color:var(--t3)">관리자가 인정 부여한 회원에게 실제로 지급되는 수당 규모를 기간별로 파악합니다</div>
    </div>
    <div style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap;align-items:center">

      <!-- 조회 모드 탭 -->
      <div style="display:flex;align-items:center;gap:6px;background:var(--s2);border:1px solid var(--bd);border-radius:8px;padding:4px 8px">
        <span style="font-size:11px;color:var(--t3);white-space:nowrap">기간:</span>
        <button id="honor-comm-btn-week"  class="btn bo on" style="padding:4px 10px;font-size:11px" onclick="honorCommToggle('week')">주단위</button>
        <button id="honor-comm-btn-month" class="btn bo"    style="padding:4px 10px;font-size:11px" onclick="honorCommToggle('month')">월단위</button>
        <button id="honor-comm-btn-year"  class="btn bo"    style="padding:4px 10px;font-size:11px" onclick="honorCommToggle('year')">연간</button>
        <button id="honor-comm-btn-all"   class="btn bo"    style="padding:4px 10px;font-size:11px" onclick="honorCommToggle('all')">누계</button>
      </div>

      <!-- 기간 입력 -->
      <input type="week"  id="hc-week"  value="<?= date('Y') ?>-W<?= date('W') ?>"
        style="background:var(--s1);border:1px solid var(--bd);color:var(--t1);padding:5px 10px;border-radius:8px;font-size:11px;font-family:inherit;outline:none">
      <input type="month" id="hc-month" value="<?= date('Y-m') ?>" style="display:none;background:var(--s1);border:1px solid var(--bd);color:var(--t1);padding:5px 10px;border-radius:8px;font-size:11px;font-family:inherit;outline:none">
      <select id="hc-year" style="display:none;background:var(--s1);border:1px solid var(--bd);color:var(--t1);padding:5px 10px;border-radius:8px;font-size:11px;font-family:inherit;outline:none">
        <?php for($y=date('Y');$y>=2020;$y--) echo "<option value='$y'>$y 년</option>"; ?>
      </select>
      <span id="hc-all-label" style="display:none;font-size:11px;color:var(--t3);padding:5px 10px;border:1px solid var(--bd);border-radius:8px;background:var(--s1)">전체 누계</span>

      <button class="btn bp" onclick="loadHonorComm()">📊 조회</button>
    </div>
  </div>

  <!-- 결과 영역 -->
  <div id="honor-comm-wrap">
    <div class="empty-msg">기간을 선택하고 [조회] 버튼을 누르세요.</div>
  </div>
</div>

<script>
let _hcMode = 'week';

function honorCommToggle(mode) {
  _hcMode = mode;
  ['week','month','year','all'].forEach(m => {
    document.getElementById('honor-comm-btn-'+m).classList.toggle('on', m === mode);
  });
  document.getElementById('hc-week').style.display   = mode === 'week'  ? '' : 'none';
  document.getElementById('hc-month').style.display  = mode === 'month' ? '' : 'none';
  document.getElementById('hc-year').style.display   = mode === 'year'  ? '' : 'none';
  document.getElementById('hc-all-label').style.display = mode === 'all' ? '' : 'none';
}

async function loadHonorCommPanel() {
  await ensureData(period());
}

async function loadHonorComm() {
  const wrap = document.getElementById('honor-comm-wrap');
  wrap.innerHTML = '<div class="spin"></div>';

  // 인정자격 회원 목록 최신화
  if (!S.loaded.honor) {
    const hd = await apiFetch('api/honor.php?action=list');
    S.honorMap = {};
    (hd.data||[]).forEach(h => { S.honorMap[h.member_no] = h; });
    S.loaded.honor = true;
  }

  const honorList = Object.values(S.honorMap); // 전체 인정자격회원
  if (!honorList.length) {
    wrap.innerHTML = '<div class="empty-msg">인정자격 부여된 회원이 없습니다.</div>';
    return;
  }

  // ── 기간 범위 결정 ──
  let periods = []; // ['2025-01', '2025-02', ...]  또는 week 단위
  let modeLabel = '';
  let isWeekMode = false;

  if (_hcMode === 'week') {
    const wv = document.getElementById('hc-week').value;
    if (!wv) { wrap.innerHTML = '<div class="empty-msg">주를 선택하세요.</div>'; return; }
    await ensureData(wv);
    periods = [wv];
    modeLabel = wv + ' (주간)';
    isWeekMode = true;

  } else if (_hcMode === 'month') {
    const mv = document.getElementById('hc-month').value;
    if (!mv) { wrap.innerHTML = '<div class="empty-msg">월을 선택하세요.</div>'; return; }
    await ensureData(mv);
    periods = [mv];
    modeLabel = mv;

  } else if (_hcMode === 'year') {
    const yr = document.getElementById('hc-year').value;
    for (let mo = 1; mo <= 12; mo++) {
      periods.push(yr + '-' + String(mo).padStart(2,'0'));
    }
    await ensureData(yr + '-01');
    modeLabel = yr + '년 연간';

  } else { // all — 데이터 있는 모든 월
    const allMonths = [...new Set((S.sales||[]).map(s => (s.order_date||'').substring(0,7)).filter(Boolean))].sort();
    if (!allMonths.length) {
      // 데이터 없으면 현재 달만
      await ensureData(period());
      periods = [period()];
    } else {
      await ensureData(allMonths[0]);
      periods = allMonths;
    }
    modeLabel = '전체 누계';
  }

  // ── 기간별 수당 합산 ──
  // honorMemberSet: 인정자격회원 member_no 세트
  const honorSet = new Set(honorList.map(h => h.member_no));

  // 인정자격회원별 수당 누계
  const honorCommMap = {}; // { member_no: { ref, match, bin, rank, repurch, lotto, total, periods: [...] } }
  const trendData    = {}; // { 'YYYY-MM': { total, honorTotal } }

  for (const per of periods) {
    let data;
    if (isWeekMode) {
      data = calcAllCommWeek(per);
    } else {
      data = calcAllComm(per);
    }

    const perLabel = per;
    let perHonorTotal = 0;

    (data.data || []).forEach(r => {
      if (!honorSet.has(r.member_no)) return; // 인정자격회원만

      perHonorTotal += r.total;

      if (!honorCommMap[r.member_no]) {
        const h = S.honorMap[r.member_no] || {};
        honorCommMap[r.member_no] = {
          member_no: r.member_no,
          name: r.name,
          grade: h.grade || r.grade,        // 인정등급
          real_grade: r.grade,               // 실제 적용 등급
          login_id: h.login_id || (S.members[r.member_no]?.login_id || ''),
          honor_grade_expire: h.grade_expire_date ? h.grade_expire_date.substring(0,7) : null,
          honor_qual_expire:  h.qual_expire_date  ? h.qual_expire_date.substring(0,7)  : null,
          active: h.active,
          note: h.note || '',
          ref: 0, match: 0, bin: 0, rank: 0, repurch: 0, lotto: 0, total: 0,
          periodCount: 0,
          pv: 0,
        };
      }
      const cm = honorCommMap[r.member_no];
      cm.ref     += r.ref     || 0;
      cm.match   += r.match   || 0;
      cm.bin     += r.bin     || 0;
      cm.rank    += r.rank    || 0;
      cm.repurch += r.repurch || 0;
      cm.lotto   += r.lotto   || 0;
      cm.total   += r.total   || 0;
      cm.pv      += r.pv      || 0;
      cm.periodCount++;
    });

    // 트렌드용 (월/주 단위)
    if (!isWeekMode) {
      trendData[perLabel] = { total: (data.total_payout||0), honorTotal: perHonorTotal };
    }
  }

  const rows = Object.values(honorCommMap).sort((a,b) => b.total - a.total);
  const grandTotal    = rows.reduce((s,r) => s + r.total, 0);
  const grandRef      = rows.reduce((s,r) => s + r.ref, 0);
  const grandMatch    = rows.reduce((s,r) => s + r.match, 0);
  const grandBin      = rows.reduce((s,r) => s + r.bin, 0);
  const grandRank     = rows.reduce((s,r) => s + r.rank, 0);
  const grandRepurch  = rows.reduce((s,r) => s + r.repurch, 0);
  const grandLotto    = rows.reduce((s,r) => s + r.lotto, 0);

  // 전체 수당 합계 (비율 계산용)
  let allTotal = 0;
  for (const per of periods) {
    const d = isWeekMode ? calcAllCommWeek(per) : calcAllComm(per);
    allTotal += d.total_payout || 0;
  }
  const honorRatio = allTotal > 0 ? (grandTotal / allTotal * 100) : 0;

  // ── 트렌드 바 (월/연간/누계 모드) ──
  let trendHtml = '';
  if (!isWeekMode && Object.keys(trendData).length > 1) {
    const maxV = Math.max(...Object.values(trendData).map(d => d.honorTotal), 1);
    const trendRows = Object.entries(trendData).filter(([,d]) => d.honorTotal > 0);
    if (trendRows.length > 0) {
      trendHtml = `
      <div class="card" style="margin-bottom:16px">
        <div class="card-hd">📈 기간별 인정자격회원 수당 추이</div>
        <div style="display:flex;align-items:flex-end;gap:6px;height:100px;padding:10px 0 0">
          ${trendRows.map(([lbl, d]) => {
            const h = d.honorTotal;
            const barH = Math.max(Math.round(h / maxV * 80), h > 0 ? 4 : 0);
            return `<div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:3px;min-width:0">
              <div style="font-size:9px;color:var(--amber);font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%">${h > 0 ? fmtW(h) : ''}</div>
              <div style="width:100%;background:var(--s3);border-radius:4px 4px 0 0;position:relative;height:80px;display:flex;align-items:flex-end">
                <div style="width:100%;height:${barH}px;background:linear-gradient(180deg,var(--amber),var(--amber)88);border-radius:4px 4px 0 0;transition:height .4s"></div>
              </div>
              <div style="font-size:9px;color:var(--t3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%">${lbl.substring(5)||lbl}</div>
            </div>`;
          }).join('')}
        </div>
      </div>`;
    }
  }

  // ── 수당 유형별 구성 도넛 (간이 바 차트) ──
  const typeBreakdown = [
    { key:'ref',     label:'💵 추천수당',    amt: grandRef,    color:'var(--blue)' },
    { key:'match',   label:'🎯 추천매칭',    amt: grandMatch,  color:'var(--purple)' },
    { key:'bin',     label:'⚖️ 바이너리',    amt: grandBin,    color:'var(--amber)' },
    { key:'rank',    label:'👑 직급수당',    amt: grandRank,   color:'var(--red)' },
    { key:'repurch', label:'🔄 직추재구매',  amt: grandRepurch,color:'var(--teal)' },
    { key:'lotto',   label:'🎰 로또보너스',  amt: grandLotto,  color:'var(--rose)' },
  ].filter(t => t.amt > 0);

  const typeHtml = typeBreakdown.length ? `
    <div class="card" style="margin-bottom:16px">
      <div class="card-hd">🍰 수당 유형별 구성</div>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;padding:4px 0">
        ${typeBreakdown.map(t => {
          const pct = grandTotal > 0 ? (t.amt / grandTotal * 100) : 0;
          return `<div style="background:var(--s2);border-radius:10px;padding:12px 14px;border:1px solid var(--bd)">
            <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:6px">
              <span style="font-size:11px;font-weight:700">${t.label}</span>
              <span style="font-size:10px;color:${t.color};font-weight:800">${pct.toFixed(1)}%</span>
            </div>
            <div style="height:6px;background:var(--s3);border-radius:3px;overflow:hidden;margin-bottom:6px">
              <div style="width:${pct.toFixed(1)}%;height:100%;background:${t.color};border-radius:3px;transition:width .5s"></div>
            </div>
            <div style="font-size:12px;font-weight:900;color:var(--t1);font-family:'JetBrains Mono',monospace">${fmtW(t.amt)}</div>
          </div>`;
        }).join('')}
      </div>
    </div>` : '';

  // ── 회원 리스트 ──
  const today = new Date().toISOString().substring(0,7);
  const listHtml = rows.length ? `
    <div class="card">
      <div class="card-hd" style="margin-bottom:12px">
        👥 인정자격회원 수당 상세 리스트
        <span style="font-size:11px;color:var(--t3);font-weight:400;margin-left:6px">${rows.length}명</span>
        <div style="margin-left:auto;display:flex;gap:6px">
          <button class="btn bo" style="padding:3px 10px;font-size:10px" onclick="hcSort('total')">💰 금액순</button>
          <button class="btn bo" style="padding:3px 10px;font-size:10px" onclick="hcSort('name')">가나다순</button>
        </div>
      </div>
      <div class="tw" id="hc-list-tbl"><table>
        <thead><tr>
          <th>#</th>
          <th>회원번호</th>
          <th>아이디</th>
          <th>이름</th>
          <th>인정등급</th>
          <th>인정기간(등급)</th>
          <th>인정기간(자격)</th>
          <th>활성</th>
          <th style="text-align:right">추천</th>
          <th style="text-align:right">매칭</th>
          <th style="text-align:right">바이너리</th>
          <th style="text-align:right">직급</th>
          <th style="text-align:right">직추재구매</th>
          <th style="text-align:right">로또</th>
          <th style="text-align:right;color:var(--amber)">합계</th>
          <th style="text-align:right">비중</th>
        </tr></thead>
        <tbody id="hc-list-tbody">${renderHcRows(rows, grandTotal)}</tbody>
      </table></div>
      <div style="margin-top:10px;padding:10px 14px;background:rgba(245,124,0,.06);border:1px solid rgba(245,124,0,.2);border-radius:8px;font-size:11px;display:flex;gap:20px;flex-wrap:wrap">
        <span>💰 합계: <b style="color:var(--amber)">${fmtW(grandTotal)}</b></span>
        <span>💵 추천: <b>${fmtW(grandRef)}</b></span>
        <span>🎯 매칭: <b>${fmtW(grandMatch)}</b></span>
        <span>⚖️ 바이너리: <b>${fmtW(grandBin)}</b></span>
        <span>👑 직급: <b>${fmtW(grandRank)}</b></span>
        <span>🔄 직추재구매: <b>${fmtW(grandRepurch)}</b></span>
        <span>🎰 로또: <b>${fmtW(grandLotto)}</b></span>
      </div>
    </div>` : '<div class="empty-msg">해당 기간 인정자격회원에게 지급된 수당이 없습니다.</div>';

  wrap.innerHTML = `
    <!-- 기간 표시 배너 -->
    <div style="background:rgba(245,124,0,.08);border:1px solid rgba(245,124,0,.25);border-radius:10px;padding:10px 16px;font-size:12px;color:var(--amber);font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:10px">
      📅 조회 기간: ${modeLabel}
      <span style="margin-left:auto;font-size:11px;font-weight:400;color:var(--t3)">인정자격회원 ${honorList.length}명 등록 · 수당 수령 ${rows.length}명</span>
    </div>

    <!-- KPI 카드 -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-bottom:16px">
      <div class="stat" style="border:2px solid rgba(245,124,0,.3)">
        <div class="stat-lbl">인정자격회원 총 수당</div>
        <div class="stat-val mono" style="color:var(--amber);font-size:20px">${fmtW(grandTotal)}</div>
        <div class="stat-sub">${modeLabel}</div>
      </div>
      <div class="stat">
        <div class="stat-lbl">전체 수당 중 비중</div>
        <div class="stat-val" style="color:${honorRatio > 30 ? 'var(--red)' : 'var(--purple)'};font-size:22px;font-weight:900;font-family:'JetBrains Mono',monospace">${honorRatio.toFixed(1)}%</div>
        <div class="stat-sub">전체 수당 ${fmtW(allTotal)}</div>
      </div>
      <div class="stat">
        <div class="stat-lbl">수당 수령 인원</div>
        <div class="stat-val" style="color:var(--blue)">${rows.length}</div>
        <div class="stat-sub">/ 인정자격 ${honorList.length}명</div>
      </div>
      <div class="stat">
        <div class="stat-lbl">1인 평균 수당</div>
        <div class="stat-val mono" style="color:var(--green)">${rows.length > 0 ? fmtW(Math.round(grandTotal / rows.length)) : '-'}</div>
        <div class="stat-sub">수령자 기준</div>
      </div>
      <div class="stat">
        <div class="stat-lbl">최고 수령액</div>
        <div class="stat-val mono" style="color:var(--red)">${rows.length > 0 ? fmtW(rows[0].total) : '-'}</div>
        <div class="stat-sub">${rows.length > 0 ? rows[0].name : '-'}</div>
      </div>
    </div>

    <!-- 비중 게이지 -->
    <div class="card" style="margin-bottom:16px">
      <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:8px">
        <span style="font-size:12px;font-weight:700">🏅 인정자격회원 수당이 전체에서 차지하는 비중</span>
        <span style="font-size:22px;font-weight:900;color:${honorRatio>30?'var(--red)':honorRatio>15?'var(--amber)':'var(--green)'};font-family:'JetBrains Mono',monospace">${honorRatio.toFixed(1)}%</span>
      </div>
      <div style="height:14px;background:var(--s3);border-radius:7px;overflow:hidden;position:relative">
        <div style="width:${Math.min(honorRatio,100).toFixed(1)}%;height:100%;background:linear-gradient(90deg,var(--amber),var(--red));border-radius:7px;transition:width .7s ease"></div>
        <div style="position:absolute;top:0;left:15%;height:100%;width:1px;background:rgba(0,0,0,.15)"></div>
        <div style="position:absolute;top:0;left:30%;height:100%;width:1px;background:rgba(0,0,0,.15)"></div>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--t3);margin-top:4px">
        <span>0%</span>
        <span style="color:var(--green)">▲15% 주의</span>
        <span style="color:var(--red)">▲30% 경고</span>
        <span>100%</span>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:11px;margin-top:8px;flex-wrap:wrap;gap:6px">
        <span style="color:var(--t3)">인정자격 수당: <b style="color:var(--amber)">${fmtW(grandTotal)}</b></span>
        <span style="color:var(--t3)">전체 수당: <b style="color:var(--t1)">${fmtW(allTotal)}</b></span>
        <span style="color:var(--t3)">일반자격 수당: <b style="color:var(--blue)">${fmtW(allTotal - grandTotal)}</b></span>
      </div>
    </div>

    ${trendHtml}
    ${typeHtml}
    ${listHtml}
  `;

  // 정렬용 데이터 저장
  window._hcRows = rows;
  window._hcGrandTotal = grandTotal;
}

function renderHcRows(rows, grandTotal) {
  const today = new Date().toISOString().substring(0,7);
  return rows.map((r, i) => {
    const pct = grandTotal > 0 ? (r.total / grandTotal * 100).toFixed(1) : '0';
    const gExp = r.honor_grade_expire;
    const qExp = r.honor_qual_expire;
    const gExpired = gExp && gExp <= today;
    const qExpired = qExp && qExp <= today;

    const gExpHtml = gExp
      ? `<span style="font-size:10px;font-weight:700;color:${gExpired?'var(--red)':'var(--amber)'}">${gExpired?'⛔만료':'⏳'+gExp}</span>`
      : `<span style="font-size:10px;color:var(--t3)">무기한</span>`;
    const qExpHtml = qExp
      ? `<span style="font-size:10px;font-weight:700;color:${qExpired?'var(--red)':'var(--amber)'}">${qExpired?'⛔만료':'⏳'+qExp}</span>`
      : `<span style="font-size:10px;color:var(--t3)">무기한</span>`;

    return `<tr onclick="openMo('${r.member_no}')" style="cursor:pointer">
      <td style="color:var(--t3)">${i+1}</td>
      <td class="mono" style="color:var(--t3);font-size:11px">${r.member_no}</td>
      <td class="mono" style="color:var(--t3);font-size:11px">${r.login_id||'-'}</td>
      <td><b>${r.name}</b></td>
      <td><span class="gb g${r.grade}">${r.grade}</span></td>
      <td>${gExpHtml}</td>
      <td>${qExpHtml}</td>
      <td>${r.active
        ? '<span style="font-size:10px;background:rgba(46,125,50,.12);color:var(--green);padding:2px 6px;border-radius:4px;font-weight:700">✅활성</span>'
        : '<span style="font-size:10px;background:rgba(211,47,47,.1);color:var(--red);padding:2px 6px;border-radius:4px;font-weight:700">⛔정지</span>'}</td>
      <td class="mono" style="text-align:right">${r.ref   ? fmtW(r.ref)    : '-'}</td>
      <td class="mono" style="text-align:right">${r.match ? fmtW(r.match)  : '-'}</td>
      <td class="mono" style="text-align:right">${r.bin   ? fmtW(r.bin)    : '-'}</td>
      <td class="mono" style="text-align:right">${r.rank  ? fmtW(r.rank)   : '-'}</td>
      <td class="mono" style="text-align:right">${r.repurch ? fmtW(r.repurch) : '-'}</td>
      <td class="mono" style="text-align:right">${r.lotto ? fmtW(r.lotto)  : '-'}</td>
      <td style="text-align:right;font-weight:900;color:var(--amber)">${fmtW(r.total)}</td>
      <td style="text-align:right">
        <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px">
          <div style="width:40px;height:5px;background:var(--s3);border-radius:3px;overflow:hidden">
            <div style="width:${Math.min(parseFloat(pct),100)}%;height:100%;background:var(--amber);border-radius:3px"></div>
          </div>
          <span style="font-size:10px;font-weight:700;color:var(--amber)">${pct}%</span>
        </div>
      </td>
    </tr>`;
  }).join('');
}

function hcSort(mode) {
  const rows = (window._hcRows||[]).slice();
  if (mode === 'name') rows.sort((a,b)=>(a.name||'').localeCompare(b.name||'','ko'));
  else rows.sort((a,b)=>b.total-a.total);
  const tbody = document.getElementById('hc-list-tbody');
  if (tbody) tbody.innerHTML = renderHcRows(rows, window._hcGrandTotal||0);
}
</script>
