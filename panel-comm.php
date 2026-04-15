<?php /* panels/panel-comm.php — 수당 계산 (통합) */ ?>
<div class="panel" id="p-comm">
  <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap;padding:16px 18px;background:var(--s1);border:1px solid var(--bd);border-radius:12px">
    <div>
      <div style="font-size:14px;font-weight:900;margin-bottom:2px">💰 통합 수당 계산</div>
      <div style="font-size:11px;color:var(--t3)">주지급(추천·매칭·바이너리)은 주 단위 CAP 적용 · 월지급(직급·로또·직추재구매)은 월 기준</div>
    </div>
    <div style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      <div style="display:flex;align-items:center;gap:6px;background:var(--s2);border:1px solid var(--bd);border-radius:8px;padding:4px 8px">
        <span style="font-size:11px;color:var(--t3);white-space:nowrap">기준:</span>
        <button id="comm-btn-week"  class="btn bo on" style="padding:4px 10px;font-size:11px" onclick="commTogglePeriod('week')">주단위</button>
        <button id="comm-btn-month" class="btn bo"    style="padding:4px 10px;font-size:11px" onclick="commTogglePeriod('month')">월단위</button>
      </div>
      <input type="week"  id="comm-week"  value="<?= date('Y') ?>-W<?= date('W') ?>"
        style="background:var(--s1);border:1px solid var(--bd);color:var(--t1);padding:5px 10px;border-radius:8px;font-size:11px;font-family:inherit;outline:none">
      <input type="month" id="comm-month" value="<?= date('Y-m') ?>" style="display:none;background:var(--s1);border:1px solid var(--bd);color:var(--t1);padding:5px 10px;border-radius:8px;font-size:11px;font-family:inherit;outline:none">
      <button class="btn bp" onclick="calcComm()">🧮 수당 계산</button>
      <button class="btn bg" onclick="calcCommSave()">💾 서버 저장</button>
    </div>
  </div>
  <div id="comm-wrap"><div class="empty-msg">기간을 선택하고 수당 계산 버튼을 누르세요.</div></div>
</div>

<script>
let _commMode = 'week';

function commTogglePeriod(mode) {
  _commMode = mode;
  document.getElementById('comm-btn-week').classList.toggle('on', mode === 'week');
  document.getElementById('comm-btn-month').classList.toggle('on', mode === 'month');
  document.getElementById('comm-week').style.display  = mode === 'week'  ? '' : 'none';
  document.getElementById('comm-month').style.display = mode === 'month' ? '' : 'none';
}

function getCommPeriod() {
  if (_commMode === 'week') {
    const wv = document.getElementById('comm-week')?.value || '';
    if (!wv) return { periodStr: period(), weekStr: null };
    const [yr, wk] = wv.split('-W').map(Number);
    const jan4 = new Date(yr, 0, 4);
    const firstMonday = new Date(jan4.getTime() - ((jan4.getDay()+6)%7)*86400000);
    const weekStart = new Date(firstMonday.getTime() + (wk-1)*7*86400000);
    const mo = `${weekStart.getFullYear()}-${String(weekStart.getMonth()+1).padStart(2,'0')}`;
    return { periodStr: mo, weekStr: wv, weekStart };
  } else {
    const mv = document.getElementById('comm-month')?.value || period();
    return { periodStr: mv, weekStr: null };
  }
}

async function calcComm() {
  const { periodStr, weekStr } = getCommPeriod();
  if (!periodStr) return;
  const wrap = $('comm-wrap');
  wrap.innerHTML = '<div class="spin"></div>';
  await ensureData(periodStr);
  const data = _commMode === 'week' && weekStr
    ? calcAllCommWeek(weekStr)
    : calcAllComm(periodStr);
  S.pvMap = data.pvMap;
  S.legPV = data.legPVQual || data.legPV;
  renderCommResult(data, wrap, _commMode, weekStr || periodStr);
}

async function calcCommSave() {
  await calcComm();
  const { periodStr } = getCommPeriod();
  try { await apiFetch(`api/calc.php?action=save&period=${periodStr}`); } catch(e) {}
}

function renderCommResult(data, wrap, mode, periodLabel) {
  const rows        = data.data || [];
  const totalPayout = data.total_payout || 0;
  const totalPV     = data.total_pv     || 0;
  const totalSales  = data.total_sales  || 0;

  const types = [
    { key:'ref',     label:'💵 추천수당',      cls:'c-ref',    desc:'구매 PV × 10%' },
    { key:'match',   label:'🎯 추천매칭',       cls:'c-match',  desc:'하위 PV × 깊이별요율' },
    { key:'bin',     label:'⚖️ 바이너리',       cls:'c-bin',    desc:'소실적 × 10%' },
    { key:'rank',    label:'👑 직급수당',       cls:'c-rank',   desc:'전체PV 12% 풀 배분' },
    { key:'repurch', label:'🔄 직추재구매',     cls:'c-rep',    desc:'직추천 PV × 3%' },
    { key:'lotto',   label:'🎰 로또보너스',     cls:'c-lotto',  desc:'전체PV 3% 풀 점수배분' },
  ];
  const sums = {};
  types.forEach(t => { sums[t.key] = rows.reduce((s,r)=>s+(r[t.key]||0),0); });

  // 전체 비율 계산
  const amtPct = totalSales > 0 ? (totalPayout / totalSales * 100) : 0;
  const pvPct  = totalPV    > 0 ? (totalPayout / totalPV    * 100) : 0;
  const amtColor = amtPct > 40 ? 'var(--red)' : amtPct > 25 ? 'var(--amber)' : 'var(--purple)';
  const pvColor  = pvPct  > 60 ? 'var(--red)' : pvPct  > 40 ? 'var(--amber)' : 'var(--teal)';

  const modeNote = mode === 'week'
    ? `<div style="background:rgba(26,86,219,.08);border:1px solid rgba(26,86,219,.2);border-radius:8px;padding:7px 12px;font-size:11px;color:var(--blue);margin-bottom:12px">
        📅 <b>${periodLabel}</b> 기준 · 추천·매칭·바이너리는 해당 주 CAP 적용 · 직급·로또·직추재구매는 해당 주 포함 월 기준
       </div>` : '';

  const proRataNote = (data.binRatio||1) < 1.0
    ? `<div style="background:rgba(245,124,0,.1);border:1px solid rgba(245,124,0,.3);border-radius:8px;padding:8px 12px;font-size:11px;color:var(--amber);margin-bottom:12px">
        ⚠️ 바이너리 총 지급률이 전체 PV의 80%를 초과하여 프로라타 ${((data.binRatio||1)*100).toFixed(1)}% 적용됨
       </div>` : '';

  // ── 주급/월급 예상 라벨 계산 ──
  const _weeklyPayout  = (sums['ref']||0) + (sums['match']||0) + (sums['bin']||0);
  const _monthlyPayout = (sums['rank']||0) + (sums['lotto']||0) + (sums['repurch']||0);

  let _weeklyLabel = '', _monthlyLabel = '';
  if (mode === 'week' && periodLabel.includes('-W')) {
    const [_yr, _wk] = periodLabel.split('-W').map(Number);
    const _jan4 = new Date(_yr, 0, 4);
    const _fMon = new Date(_jan4.getTime() - ((_jan4.getDay()+6)%7)*86400000);
    const _ws   = new Date(_fMon.getTime() + (_wk-1)*7*86400000);
    const _we   = new Date(_ws.getTime() + 6*86400000);
    const _fd   = d => `${d.getMonth()+1}월${d.getDate()}일`;
    _weeklyLabel  = `${_fd(_ws)}~${_fd(_we)}, ${periodLabel}`;
    const _mo = `${_ws.getFullYear()}-${String(_ws.getMonth()+1).padStart(2,'0')}`;
    const [_my, _mm] = _mo.split('-');
    _monthlyLabel = `${parseInt(_mm)}월`;
  } else {
    // 월단위
    const [_my, _mm] = (periodLabel||'').split('-');
    _weeklyLabel  = _mm ? `${parseInt(_mm)}월 전체 주 합산` : periodLabel;
    _monthlyLabel = _mm ? `${parseInt(_mm)}월` : periodLabel;
  }

  let html = `
    ${modeNote}
    ${proRataNote}

    <!-- ① 핵심 KPI -->
    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:10px">
      <div class="stat"><div class="stat-lbl">전체 매출</div><div class="stat-val mono" style="color:var(--amber);font-size:18px">${fmtW(totalSales)}</div><div class="stat-sub">${periodLabel}</div></div>
      <div class="stat"><div class="stat-lbl">전체 PV</div><div class="stat-val mono" style="color:var(--green)">${fmt(totalPV)}</div><div class="stat-sub">${periodLabel}</div></div>
      <div class="stat"><div class="stat-lbl">총 예상 수당</div><div class="stat-val mono" style="color:var(--amber);font-size:18px">${fmtW(totalPayout)}</div><div class="stat-sub">${periodLabel}</div></div>
      <div class="stat"><div class="stat-lbl">수당 수령자</div><div class="stat-val" style="color:var(--blue)">${rows.length}</div><div class="stat-sub">명</div></div>
      <div class="stat"><div class="stat-lbl">자격자</div><div class="stat-val" style="color:var(--teal)">${Object.keys(data.qual||{}).length}</div><div class="stat-sub">10만PV 달성</div></div>
    </div>

    <!-- ① -b 주급/월급 예상 수당 KPI -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
      <div class="stat" style="border:2px solid rgba(26,86,219,.3);background:rgba(26,86,219,.05)">
        <div class="stat-lbl" style="color:var(--blue);font-weight:700">📅 주급 수당 예상</div>
        <div class="stat-val mono" style="color:var(--blue);font-size:22px">${fmtW(_weeklyPayout)}</div>
        <div class="stat-sub" style="color:var(--blue);opacity:.75">${_weeklyLabel}</div>
        <div style="font-size:10px;color:var(--t3);margin-top:5px">추천수당 + 추천매칭수당 + 바이너리수당</div>
      </div>
      <div class="stat" style="border:2px solid rgba(46,125,50,.3);background:rgba(46,125,50,.05)">
        <div class="stat-lbl" style="color:var(--green);font-weight:700">📆 월급 수당 예상</div>
        <div class="stat-val mono" style="color:var(--green);font-size:22px">${fmtW(_monthlyPayout)}</div>
        <div class="stat-sub" style="color:var(--green);opacity:.75">${_monthlyLabel}</div>
        <div style="font-size:10px;color:var(--t3);margin-top:5px">직급수당 + 로또보너스 + 직추재구매수당</div>
      </div>
    </div>

    <!-- ② 수당 지급률 게이지 -->
    <div class="card" style="margin-bottom:16px;background:var(--s2);border:1px solid var(--bd)">
      <div class="card-hd" style="margin-bottom:12px">📊 수당 지급률 분석
        <span style="font-size:10px;color:var(--t3);font-weight:400">실제 수당 지출 비율 — 높을수록 수익성 주의</span>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
        <!-- 매출 대비 -->
        <div style="background:var(--s1);border-radius:10px;padding:14px 16px;border:1px solid var(--bd)">
          <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:8px">
            <span style="font-size:11px;font-weight:700;color:var(--t2)">💳 매출 대비 총 수당</span>
            <span style="font-size:24px;font-weight:900;color:${amtColor};font-family:'JetBrains Mono',monospace">${amtPct.toFixed(1)}%</span>
          </div>
          <div style="height:12px;background:var(--s3);border-radius:6px;overflow:hidden;margin-bottom:8px;position:relative">
            <div style="width:${Math.min(amtPct,100).toFixed(1)}%;height:100%;border-radius:6px;background:linear-gradient(90deg,${amtColor},${amtColor}88);transition:width .6s"></div>
            <div style="position:absolute;top:0;left:25%;height:100%;width:1px;background:rgba(0,0,0,.15)"></div>
            <div style="position:absolute;top:0;left:40%;height:100%;width:1px;background:rgba(0,0,0,.15)"></div>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--t3);margin-bottom:4px">
            <span>0%</span><span style="color:var(--green)">25%</span><span style="color:var(--amber)">40%</span><span style="color:var(--red)">↑위험</span>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:11px;margin-top:6px">
            <span style="color:var(--t3)">매출: <b style="color:var(--t1)">${fmtW(totalSales)}</b></span>
            <span style="color:var(--t3)">수당: <b style="color:${amtColor}">${fmtW(totalPayout)}</b></span>
          </div>
        </div>
        <!-- PV 대비 -->
        <div style="background:var(--s1);border-radius:10px;padding:14px 16px;border:1px solid var(--bd)">
          <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:8px">
            <span style="font-size:11px;font-weight:700;color:var(--t2)">📦 PV 대비 총 수당</span>
            <span style="font-size:24px;font-weight:900;color:${pvColor};font-family:'JetBrains Mono',monospace">${pvPct.toFixed(1)}%</span>
          </div>
          <div style="height:12px;background:var(--s3);border-radius:6px;overflow:hidden;margin-bottom:8px;position:relative">
            <div style="width:${Math.min(pvPct,100).toFixed(1)}%;height:100%;border-radius:6px;background:linear-gradient(90deg,${pvColor},${pvColor}88);transition:width .6s"></div>
            <div style="position:absolute;top:0;left:40%;height:100%;width:1px;background:rgba(0,0,0,.15)"></div>
            <div style="position:absolute;top:0;left:60%;height:100%;width:1px;background:rgba(0,0,0,.15)"></div>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--t3);margin-bottom:4px">
            <span>0%</span><span style="color:var(--green)">40%</span><span style="color:var(--amber)">60%</span><span style="color:var(--red)">↑위험</span>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:11px;margin-top:6px">
            <span style="color:var(--t3)">PV: <b style="color:var(--t1)">${fmt(totalPV)}</b></span>
            <span style="color:var(--t3)">수당: <b style="color:${pvColor}">${fmtW(totalPayout)}</b></span>
          </div>
        </div>
      </div>

      <!-- 수당 유형별 비율 테이블 -->
      <div style="background:var(--s1);border-radius:10px;padding:14px 16px;border:1px solid var(--bd)">
        <div style="font-size:11px;font-weight:700;color:var(--t2);margin-bottom:10px">수당 유형별 지급률</div>
        <div style="display:grid;grid-template-columns:130px 1fr 110px 90px 90px;gap:8px;padding:5px 0;border-bottom:1px solid var(--bd);font-size:10px;font-weight:700;color:var(--t3)">
          <span>수당 종류</span><span>비중 (전체수당 내)</span><span style="text-align:right">금액</span><span style="text-align:right">매출대비</span><span style="text-align:right">PV대비</span>
        </div>
        ${types.map(t => {
          const amt = sums[t.key] || 0;
          if (!amt) return '';
          const tAmtPct   = totalSales  > 0 ? (amt / totalSales  * 100) : 0;
          const tPvPct    = totalPV     > 0 ? (amt / totalPV     * 100) : 0;
          const tSharePct = totalPayout > 0 ? (amt / totalPayout * 100) : 0;
          return `
            <div style="display:grid;grid-template-columns:130px 1fr 110px 90px 90px;gap:8px;align-items:center;padding:7px 0;border-bottom:1px solid var(--bd)">
              <span style="font-size:11px;font-weight:600">${t.label}</span>
              <div style="display:flex;align-items:center;gap:6px">
                <div style="flex:1;height:8px;background:var(--s3);border-radius:4px;overflow:hidden">
                  <div style="width:${tSharePct.toFixed(1)}%;height:100%;background:${t.cls.replace('c-','')};border-radius:4px" class="${t.cls}-bar"></div>
                </div>
                <span style="font-size:10px;color:var(--t3);white-space:nowrap">${tSharePct.toFixed(0)}%</span>
              </div>
              <span class="mono" style="text-align:right;font-weight:700;font-size:11px;color:var(--t1)">${fmtW(amt)}</span>
              <span style="text-align:right;font-size:12px;font-weight:800;color:var(--purple)">${tAmtPct.toFixed(1)}%</span>
              <span style="text-align:right;font-size:12px;font-weight:800;color:var(--teal)">${tPvPct.toFixed(1)}%</span>
            </div>`;
        }).join('')}
        <div style="display:grid;grid-template-columns:130px 1fr 110px 90px 90px;gap:8px;align-items:center;padding:9px 0;font-weight:900;background:var(--s2);border-radius:0 0 6px 6px;margin:0 -4px;padding:8px 4px">
          <span style="font-size:11px">합계</span>
          <span></span>
          <span class="mono" style="text-align:right;color:var(--green)">${fmtW(totalPayout)}</span>
          <span style="text-align:right;font-size:13px;color:${amtColor}">${amtPct.toFixed(1)}%</span>
          <span style="text-align:right;font-size:13px;color:${pvColor}">${pvPct.toFixed(1)}%</span>
        </div>
      </div>
    </div>

    <!-- ③ 수당 유형 카드 -->
    <div class="comm-summary-grid">
      ${types.map(t=>`<div class="comm-card ${t.cls}">
        <div class="comm-card-lbl">${t.label}</div>
        <div class="comm-card-amt">${fmtW(sums[t.key])}</div>
        <div class="comm-card-sub">${rows.filter(r=>r[t.key]>0).length}명 · ${t.desc}</div>
      </div>`).join('')}
    </div>

    <!-- ④ 회원별 수당 테이블 -->
    <div style="display:flex;align-items:center;gap:10px;margin:16px 0 10px;flex-wrap:wrap">
      <span style="font-size:13px;font-weight:700">💰 회원별 수당 내역</span>
      <div style="display:flex;align-items:center;gap:6px;margin-left:auto;background:var(--s2);border:1px solid var(--bd);border-radius:8px;padding:3px 6px">
        <span style="font-size:10px;color:var(--t3)">정렬:</span>
        <button id="comm-sort-amt" class="btn bo on" style="padding:3px 10px;font-size:10px" onclick="commSortTable('amt')">금액순</button>
        <button id="comm-sort-abc" class="btn bo"    style="padding:3px 10px;font-size:10px" onclick="commSortTable('abc')">가나다순</button>
      </div>
    </div>
    <div class="tw" id="comm-member-tbl"><table>
      <thead><tr>
        <th>#</th><th>이름</th><th>등급</th><th>PV</th>
        <th>추천</th><th>매칭</th><th>바이너리</th><th>직급</th><th>직추재구매</th><th>로또</th>
        <th style="text-align:right">합계</th>
        <th style="text-align:right;color:var(--purple)">매출대비%</th>
      </tr></thead>
      <tbody id="comm-member-tbody">${rows.map((r,i)=>{
        const rPct = totalSales>0 && r.pv>0
          ? (r.total / (r.pv) * 100).toFixed(1)
          : '-';
        const mInfo = S.members[r.member_no]||{};
        return `<tr onclick="openMo('${r.member_no}')" style="cursor:pointer">
          <td style="color:var(--t3)">${i+1}</td>
          <td><b>${r.name}</b><div style="font-size:9px;color:var(--t3);line-height:1.4">${mInfo.member_no||r.member_no} · ${mInfo.login_id||''}</div></td>
          <td><span class="gb g${r.grade}">${r.grade}</span></td>
          <td class="mono" style="color:var(--blue)">${fmt(r.pv||0)}</td>
          <td class="mono">${r.ref?fmtW(r.ref):'-'}</td>
          <td class="mono">${r.match?fmtW(r.match):'-'}</td>
          <td class="mono">${r.bin?fmtW(r.bin):'-'}</td>
          <td class="mono">${r.rank?fmtW(r.rank):'-'}</td>
          <td class="mono">${r.repurch?fmtW(r.repurch):'-'}</td>
          <td class="mono">${r.lotto?fmtW(r.lotto):'-'}</td>
          <td style="text-align:right;font-weight:900;color:var(--green)">${fmtW(r.total)}</td>
          <td style="text-align:right;font-size:11px;font-weight:700;color:var(--purple)">${rPct !== '-' ? rPct+'%' : '-'}</td>
        </tr>`;
      }).join('')}</tbody>
    </table></div>`;

  wrap.innerHTML = html;

  // 정렬 상태 저장용
  window._commRows = rows;
  window._commTotalSales = totalSales;

  // 수당 유형별 바 색상 적용 (클래스로 분기)
  const barColors = {'c-ref':'var(--blue)','c-match':'var(--purple)','c-bin':'var(--amber)','c-rank':'var(--red)','c-rep':'var(--teal)','c-lotto':'var(--rose)'};
  types.forEach(t => {
    wrap.querySelectorAll(`.${t.cls}-bar`).forEach(el => {
      el.style.background = barColors[t.cls] || 'var(--blue)';
    });
  });
}

function commSortTable(mode) {
  document.getElementById('comm-sort-amt')?.classList.toggle('on', mode==='amt');
  document.getElementById('comm-sort-abc')?.classList.toggle('on', mode==='abc');
  const rows = (window._commRows||[]).slice();
  if (mode==='abc') rows.sort((a,b)=>(a.name||'').localeCompare(b.name||'','ko'));
  else rows.sort((a,b)=>b.total-a.total);
  const totalSales = window._commTotalSales||0;
  const tbody = document.getElementById('comm-member-tbody');
  if (!tbody) return;
  tbody.innerHTML = rows.map((r,i)=>{
    const rPct = totalSales>0&&r.pv>0 ? (r.total/r.pv*100).toFixed(1) : '-';
    const mInfo = S.members[r.member_no]||{};
    return `<tr onclick="openMo('${r.member_no}')" style="cursor:pointer">
      <td style="color:var(--t3)">${i+1}</td>
      <td><b>${r.name}</b><div style="font-size:9px;color:var(--t3);line-height:1.4">${mInfo.member_no||r.member_no} · ${mInfo.login_id||''}</div></td>
      <td><span class="gb g${r.grade}">${r.grade}</span></td>
      <td class="mono" style="color:var(--blue)">${fmt(r.pv||0)}</td>
      <td class="mono">${r.ref?fmtW(r.ref):'-'}</td>
      <td class="mono">${r.match?fmtW(r.match):'-'}</td>
      <td class="mono">${r.bin?fmtW(r.bin):'-'}</td>
      <td class="mono">${r.rank?fmtW(r.rank):'-'}</td>
      <td class="mono">${r.repurch?fmtW(r.repurch):'-'}</td>
      <td class="mono">${r.lotto?fmtW(r.lotto):'-'}</td>
      <td style="text-align:right;font-weight:900;color:var(--green)">${fmtW(r.total)}</td>
      <td style="text-align:right;font-size:11px;font-weight:700;color:var(--purple)">${rPct!=='-'?rPct+'%':'-'}</td>
    </tr>`;
  }).join('');
}
</script>
