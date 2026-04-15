<?php /* panels/panel-dash.php — 현황 요약 (월별) */ ?>
<div class="panel on" id="p-dash">

  <!-- ① 핵심 KPI 4개 -->
  <div class="stat-g" id="dash-stats">
    <div class="stat"><div class="stat-lbl">총 회원</div><div class="stat-val" id="s-mem" style="color:var(--blue)">…</div><div class="stat-sub">등록 회원수</div></div>
    <div class="stat"><div class="stat-lbl">이번달 매출</div><div class="stat-val mono" id="s-amt" style="color:var(--amber)">…</div><div class="stat-sub">선택 월 합계</div></div>
    <div class="stat"><div class="stat-lbl">이번달 PV</div><div class="stat-val mono" id="s-pv" style="color:var(--green)">…</div><div class="stat-sub">선택 월 합계</div></div>
    <div class="stat"><div class="stat-lbl">예상 수당</div><div class="stat-val" id="s-pay" style="color:var(--purple)">…</div><div class="stat-sub">이번달 합산</div></div>
  </div>

  <!-- ② 수당 비율 게이지 카드 (핵심 추가!) -->
  <div class="card" id="dash-ratio-card" style="display:none;margin-bottom:16px">
    <div class="card-hd">📊 수당 지급률 분석
      <span style="font-size:10px;color:var(--t3);font-weight:400">매출·PV 대비 실제 수당 지출 비율</span>
    </div>

    <!-- 전체 수당 비율 게이지 2개 -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px">

      <!-- 매출 대비 수당 -->
      <div style="background:var(--s2);border-radius:10px;padding:14px 16px">
        <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:8px">
          <span style="font-size:11px;font-weight:700;color:var(--t2)">💳 매출 대비 총 수당</span>
          <span id="ratio-amt-pct" style="font-size:22px;font-weight:900;color:var(--purple);font-family:'JetBrains Mono',monospace">—%</span>
        </div>
        <div style="height:10px;background:var(--s3);border-radius:5px;overflow:hidden;margin-bottom:6px">
          <div id="ratio-amt-bar" style="height:100%;width:0%;border-radius:5px;transition:width .6s ease;background:linear-gradient(90deg,var(--purple),#ce93d8)"></div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--t3)">
          <span>매출 <b id="ratio-amt-sales" style="color:var(--t2)">—</b></span>
          <span>수당 <b id="ratio-amt-pay" style="color:var(--purple)">—</b></span>
        </div>
      </div>

      <!-- PV 대비 수당 -->
      <div style="background:var(--s2);border-radius:10px;padding:14px 16px">
        <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:8px">
          <span style="font-size:11px;font-weight:700;color:var(--t2)">📦 PV 대비 총 수당</span>
          <span id="ratio-pv-pct" style="font-size:22px;font-weight:900;color:var(--teal);font-family:'JetBrains Mono',monospace">—%</span>
        </div>
        <div style="height:10px;background:var(--s3);border-radius:5px;overflow:hidden;margin-bottom:6px">
          <div id="ratio-pv-bar" style="height:100%;width:0%;border-radius:5px;transition:width .6s ease;background:linear-gradient(90deg,var(--teal),#80cbc4)"></div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--t3)">
          <span>PV <b id="ratio-pv-pv" style="color:var(--t2)">—</b></span>
          <span>수당 <b id="ratio-pv-pay" style="color:var(--teal)">—</b></span>
        </div>
      </div>
    </div>

    <!-- 수당 유형별 비율 바 -->
    <div style="font-size:11px;font-weight:700;color:var(--t2);margin-bottom:10px">수당 유형별 · 매출 대비 지급률</div>
    <div id="dash-type-ratios"></div>
  </div>

  <!-- ③ 수당 유형별 카드 (클릭 가능) -->
  <div id="dash-comm-summary" style="display:none;margin-bottom:16px">
    <div class="comm-summary-grid" id="dash-comm-cards"></div>
  </div>

  <!-- ④ TOP10 + 등급분포 -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
    <div class="card">
      <div class="card-hd">💰 수당 TOP 10</div>
      <div id="d-comm"><div class="spin"></div></div>
    </div>
    <div class="card">
      <div class="card-hd">📊 등급 분포</div>
      <div id="d-grade"><div class="spin"></div></div>
    </div>
  </div>

  <div class="card">
    <div class="card-hd">📋 자격자 현황 <span id="d-qual-cnt" style="font-size:11px;color:var(--t3);font-weight:400"></span></div>
    <div id="d-qual"><div class="spin"></div></div>
  </div>
</div>

<script>
async function loadDash() {
  const per = period();

  // 기본 통계
  let totalSales = 0, totalPV = 0;
  try {
    const stats = await apiFetch(`api/data.php?action=stats&period=${per}`);
    $('s-mem').textContent = fmt(stats.members || 0);
    totalPV    = stats.pv     || 0;
    totalSales = stats.amount || 0;
    $('s-pv').textContent  = fmt(totalPV);
    $('s-amt').textContent = fmtW(totalSales);
  } catch(e) {}

  // 수당 계산
  await ensureData(per);
  const result = calcAllComm(per);
  S.pvMap = result.pvMap;
  S.legPV = result.legPVQual || result.legPV;

  const totalPayout = result.total_payout || 0;
  // calcAllComm이 total_sales/total_pv 돌려주면 그 값 우선
  if (result.total_sales) totalSales = result.total_sales;
  if (result.total_pv)    totalPV    = result.total_pv;

  $('s-pay').textContent = fmtW(totalPayout);

  // ── 수당 비율 게이지 렌더 ──
  renderDashRatios(totalSales, totalPV, totalPayout, result.data || []);

  // 수당 유형별 카드
  const rows = result.data || [];
  const commTypes = [
    { key:'ref',     label:'💵 추천수당',    cls:'c-ref',   panel:'comm-ref' },
    { key:'match',   label:'🎯 추천매칭',    cls:'c-match', panel:'comm-match' },
    { key:'bin',     label:'⚖️ 바이너리',    cls:'c-bin',   panel:'comm-bin' },
    { key:'rank',    label:'👑 직급수당',    cls:'c-rank',  panel:'comm-rank' },
    { key:'repurch', label:'🔄 직추재구매',  cls:'c-rep',   panel:'comm-repurchase' },
    { key:'lotto',   label:'🎰 로또보너스',  cls:'c-lotto', panel:'comm-lotto' },
  ];
  const sums = {};
  commTypes.forEach(t => { sums[t.key] = rows.reduce((s,r)=>s+(r[t.key]||0),0); });
  $('dash-comm-cards').innerHTML = commTypes.map(t=>`
    <div class="comm-card ${t.cls}" style="cursor:pointer" onclick="document.querySelector('[data-panel=${t.panel}]').click()">
      <div class="comm-card-lbl">${t.label}</div>
      <div class="comm-card-amt">${fmtW(sums[t.key])}</div>
      <div class="comm-card-sub">${rows.filter(r=>r[t.key]>0).length}명 수령</div>
    </div>`).join('');
  $('dash-comm-summary').style.display = 'block';

  renderCommTop(rows);
  renderGradeDist(Object.values(S.members));
  renderQualTable(result.qual || {}, result.pvMap || {}, result.legPVQual || result.legPV || {});
}

// ── 수당 비율 게이지 렌더 ──
function renderDashRatios(totalSales, totalPV, totalPayout, rows) {
  // 수당이 없으면 표시값 초기화 후 카드 숨김 (이전 월 값이 남지 않도록)
  if (!totalPayout) {
    $('ratio-amt-pct').textContent = '—%';
    $('ratio-pv-pct').textContent  = '—%';
    $('ratio-amt-bar').style.width = '0%';
    $('ratio-pv-bar').style.width  = '0%';
    $('ratio-amt-sales').textContent = '—';
    $('ratio-amt-pay').textContent   = '—';
    $('ratio-pv-pv').textContent     = '—';
    $('ratio-pv-pay').textContent    = '—';
    $('dash-type-ratios').innerHTML  = '';
    $('dash-ratio-card').style.display = 'none';
    return;
  }
  $('dash-ratio-card').style.display = 'block';

  // 전체 비율
  const amtPct = totalSales > 0 ? (totalPayout / totalSales * 100) : 0;
  const pvPct  = totalPV    > 0 ? (totalPayout / totalPV    * 100) : 0;

  $('ratio-amt-pct').textContent  = amtPct.toFixed(1) + '%';
  $('ratio-pv-pct').textContent   = pvPct.toFixed(1)  + '%';
  $('ratio-amt-bar').style.width  = Math.min(amtPct, 100) + '%';
  $('ratio-pv-bar').style.width   = Math.min(pvPct,  100) + '%';
  $('ratio-amt-sales').textContent = fmtW(totalSales);
  $('ratio-amt-pay').textContent   = fmtW(totalPayout);
  $('ratio-pv-pv').textContent     = fmt(totalPV);
  $('ratio-pv-pay').textContent    = fmtW(totalPayout);

  // 색상: 비율이 높을수록 경고색
  const amtColor = amtPct > 40 ? 'var(--red)' : amtPct > 25 ? 'var(--amber)' : 'var(--purple)';
  const pvColor  = pvPct  > 60 ? 'var(--red)' : pvPct  > 40 ? 'var(--amber)' : 'var(--teal)';
  $('ratio-amt-pct').style.color = amtColor;
  $('ratio-pv-pct').style.color  = pvColor;
  $('ratio-amt-bar').style.background = `linear-gradient(90deg,${amtColor},${amtColor}88)`;
  $('ratio-pv-bar').style.background  = `linear-gradient(90deg,${pvColor},${pvColor}88)`;

  // 유형별 비율 바
  const typeList = [
    { key:'ref',     label:'💵 추천수당',     color:'var(--blue)' },
    { key:'match',   label:'🎯 추천매칭수당', color:'var(--purple)' },
    { key:'bin',     label:'⚖️ 바이너리수당', color:'var(--amber)' },
    { key:'rank',    label:'👑 직급수당',     color:'var(--red)' },
    { key:'repurch', label:'🔄 직추재구매',   color:'var(--teal)' },
    { key:'lotto',   label:'🎰 로또보너스',   color:'var(--rose)' },
  ];
  const sums = {};
  typeList.forEach(t => { sums[t.key] = rows.reduce((s,r)=>s+(r[t.key]||0),0); });

  $('dash-type-ratios').innerHTML = typeList.map(t => {
    const amt = sums[t.key] || 0;
    if (!amt) return '';
    const pAmtPct = totalSales > 0 ? (amt / totalSales * 100) : 0;
    const pPvPct  = totalPV    > 0 ? (amt / totalPV    * 100) : 0;
    const barW    = totalSales > 0 ? Math.min(pAmtPct / Math.max(amtPct, 1) * 100, 100) : 0;
    return `
      <div style="display:grid;grid-template-columns:130px 1fr 80px 80px;align-items:center;gap:10px;padding:6px 0;border-bottom:1px solid var(--bd)">
        <span style="font-size:11px;font-weight:600">${t.label}</span>
        <div style="height:7px;background:var(--s3);border-radius:4px;overflow:hidden">
          <div style="width:${barW.toFixed(1)}%;height:100%;background:${t.color};border-radius:4px;transition:width .5s ease"></div>
        </div>
        <span style="font-size:10px;font-weight:700;color:${t.color};text-align:right">매출 ${pAmtPct.toFixed(1)}%</span>
        <span style="font-size:10px;font-weight:700;color:var(--t3);text-align:right">PV ${pPvPct.toFixed(1)}%</span>
      </div>`;
  }).join('');
}

function renderCommTop(rows) {
  const top = rows.slice(0, 10);
  if (!top.length) { $('d-comm').innerHTML='<div class="empty-msg">수당 데이터 없음</div>'; return; }
  const max = Math.max(...top.map(r=>r.total||0), 1);
  $('d-comm').innerHTML = top.map((r,i)=>`
    <div class="ctype-row" onclick="openMo('${r.member_no}')" style="cursor:pointer">
      <span style="font-size:11px;color:var(--t3);min-width:18px">${i+1}</span>
      <span style="width:60px;font-size:11px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${r.name}</span>
      <span class="gb g${r.grade}" style="margin:0 6px">${r.grade}</span>
      <div style="flex:1;height:6px;background:var(--s3);border-radius:3px;margin-right:8px">
        <div style="width:${Math.round(r.total/max*100)}%;height:100%;background:var(--grad);border-radius:3px"></div>
      </div>
      <span class="camt">${fmtW(r.total)}</span>
    </div>`).join('');
}

function renderGradeDist(members) {
  const counts = { '플래티넘':0, '골드':0, '플러스':0, '베이직':0, '미달성':0 };
  members.forEach(m => {
    const g = m.max_grade || m.grade || '미달성';
    if (counts[g] !== undefined) counts[g]++; else counts['미달성']++;
  });
  const total = members.length || 1;
  const colors = { '플래티넘':'var(--green)', '골드':'var(--amber)', '플러스':'var(--blue)', '베이직':'var(--purple)', '미달성':'var(--t3)' };
  $('d-grade').innerHTML = Object.entries(counts).map(([g,c])=>`
    <div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid var(--bd)">
      <span class="gb g${g}" style="min-width:54px;text-align:center">${g}</span>
      <div style="flex:1;height:6px;background:var(--s3);border-radius:3px">
        <div style="width:${Math.round(c/total*100)}%;height:100%;background:${colors[g]};border-radius:3px"></div>
      </div>
      <span style="font-size:12px;font-weight:700;min-width:28px;text-align:right">${c}</span>
      <span style="font-size:10px;color:var(--t3);min-width:36px;text-align:right">${Math.round(c/total*100)}%</span>
    </div>`).join('');
}

function renderQualTable(qual, pvMap, legPV) {
  const qualList = Object.keys(qual);
  $('d-qual-cnt').textContent = `(${qualList.length}명 자격)`;
  if (!qualList.length) { $('d-qual').innerHTML='<div class="empty-msg" style="padding:20px">자격자 없음</div>'; return; }
  const m = S.members;
  const rows = qualList.map(no => ({
    no, name: m[no]?.name||no,
    grade: getEffectiveGrade(no, pvMap[no]||0),
    pv: pvMap[no]||0,
    l: legPV[no]?.L||0,
    r: legPV[no]?.R||0,
    type: qual[no],
  })).sort((a,b)=>b.pv-a.pv);

  $('d-qual').innerHTML = `<div class="tw"><table>
    <thead><tr><th>이름</th><th>등급</th><th>이번달 PV</th><th>좌 PV</th><th>우 PV</th><th>자격 구분</th></tr></thead>
    <tbody>${rows.map(r=>`<tr onclick="openMo('${r.no}')" style="cursor:pointer">
      <td><b>${r.name}</b></td>
      <td><span class="gb g${r.grade}">${r.grade}</span></td>
      <td class="mono" style="color:var(--blue)">${fmt(r.pv)}</td>
      <td class="mono pl">${fmt(r.l)}</td>
      <td class="mono pr">${fmt(r.r)}</td>
      <td><span style="font-size:9px;padding:2px 7px;border-radius:5px;background:${r.type==='prev'?'rgba(46,125,50,.12)':'rgba(26,86,219,.12)'};color:${r.type==='prev'?'var(--green)':'var(--blue)'}">${r.type==='prev'?'직전달 달성':'당월 달성'}</span></td>
    </tr>`).join('')}</tbody>
  </table></div>`;
}
</script>
