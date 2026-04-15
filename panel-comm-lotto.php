<?php /* panels/panel-comm-lotto.php — 로또보너스 */ ?>
<div class="panel" id="p-comm-lotto">
  <div class="comm-detail-header">
    <div style="font-size:18px">🎰</div>
    <div>
      <div style="font-size:14px;font-weight:900">로또보너스</div>
      <div style="font-size:11px;color:var(--t3)">추천인 99만원 이상 구매 5건당 1점 · 전체 PV 3% 풀 · 점수비례 배분 · 월지급</div>
    </div>
    <div class="comm-period-bar" style="margin-left:auto">
      <input type="month" id="lotto-month" value="<?= date('Y-m') ?>">
      <button class="btn bp" onclick="loadCommLotto()">📊 조회</button>
    </div>
  </div>
  <div id="lotto-kpi" class="comm-kpi-row" style="display:none"></div>
  <div id="lotto-tbl"><div class="empty-msg">기간 선택 후 [조회] 버튼을 누르세요.</div></div>
</div>

<!-- 로또 상세 팝업 -->
<div id="lotto-pop-overlay" onclick="lottoPopClose()"
  style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10000"></div>
<div id="lotto-pop"
  style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);
         background:var(--s1);border:1px solid var(--bd);border-radius:14px;
         box-shadow:0 8px 40px rgba(0,0,0,.3);z-index:10001;
         width:min(520px,92vw);max-height:80vh;overflow:hidden;flex-direction:column">
  <div style="display:flex;align-items:center;justify-content:space-between;
              padding:14px 18px;border-bottom:1px solid var(--bd)">
    <div style="font-size:13px;font-weight:900" id="lotto-pop-title">🔍 구매 상세</div>
    <button onclick="lottoPopClose()"
      style="background:none;border:none;font-size:18px;cursor:pointer;color:var(--t3);padding:0 4px;line-height:1">✕</button>
  </div>
  <div id="lotto-pop-body" style="overflow-y:auto;padding:14px 18px;flex:1"></div>
</div>

<script>
let _lottoRows = [];
let _lottoSort = 'score'; // 기본: 점수순
let _lottoBannerHtml = '';

function lottoSortBy(mode) {
  _lottoSort = mode;
  lottoRenderTable();
}

function lottoRenderTable() {
  const el = $('lotto-tbl');
  if (!el || !_lottoRows.length) return;
  const rows = _lottoRows.slice();
  if (_lottoSort === 'abc') rows.sort((a,b)=>(a.name||'').localeCompare(b.name||'','ko'));
  else if (_lottoSort === 'amt') rows.sort((a,b)=>b.total-a.total);
  else rows.sort((a,b)=>b.score-a.score);

  const sortBar = '<div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;flex-wrap:wrap">'
    +'<span style="font-size:11px;color:var(--t3)">정렬:</span>'
    +'<div style="display:flex;gap:3px;background:var(--s3);border-radius:6px;padding:2px">'
    +'<button class="btn bo'+(_lottoSort==='score'?' on':'')+'" style="padding:2px 8px;font-size:10px;border-radius:5px" onclick="lottoSortBy(\'score\')">점수순</button>'
    +'<button class="btn bo'+(_lottoSort==='amt'?' on':'')+'" style="padding:2px 8px;font-size:10px;border-radius:5px" onclick="lottoSortBy(\'amt\')">금액순</button>'
    +'<button class="btn bo'+(_lottoSort==='abc'?' on':'')+'" style="padding:2px 8px;font-size:10px;border-radius:5px" onclick="lottoSortBy(\'abc\')">가나다순</button>'
    +'</div></div>';

  el.innerHTML = _lottoBannerHtml + sortBar + `
    <div class="tw"><table>
      <thead><tr>
        <th>#</th><th>이름</th><th>등급</th>
        <th>99만원↑ 구매건수</th><th>점수</th><th>비율</th>
        <th style="text-align:right">지급액</th>
      </tr></thead>
      <tbody>${rows.map((r,i) => `<tr onclick="openMo('${r.member_no}')" style="cursor:pointer">
        <td style="color:var(--t3)">${i+1}</td>
        <td><b>${r.name}</b><br><span style="font-size:9px;color:var(--t3)">${r.member_no} · ${(S.members[r.member_no]||{}).login_id||''}</span></td>
        <td><span class="gb g${r.grade}">${r.grade}</span></td>
        <td style="text-align:center">
          <span style="font-weight:700;color:var(--blue)">${r.ref_count}건</span>
          <button onclick="event.stopPropagation();lottoPopOpen('${r.name}', _lottoRows.find(x=>x.member_no==='${r.member_no}').details)"
            style="margin-left:6px;background:none;border:1px solid var(--bd);border-radius:6px;
                   padding:2px 7px;cursor:pointer;font-size:11px;color:var(--t2);
                   vertical-align:middle;line-height:1.4"
            title="구매 상세 보기">🔍</button>
        </td>
        <td style="text-align:center;font-weight:900;color:var(--purple)">${r.score}점</td>
        <td style="color:var(--t3);font-size:10px">${r.pct}%</td>
        <td style="text-align:right;font-weight:700;color:var(--rose)">${fmtW(r.total)}</td>
      </tr>`).join('')}</tbody>
    </table></div>`;
}

// ── 로또 팝업 ──
function lottoPopOpen(refName, buyers) {
  document.getElementById('lotto-pop-title').textContent = '🔍 ' + refName + ' — 99만원↑ 구매 상세';
  buyers = (buyers||[]).slice().sort((a,b) => (a.date||'').localeCompare(b.date||''));
  const byMember = {};
  buyers.forEach(b => {
    if (!byMember[b.name]) byMember[b.name] = { cnt:0, total:0, dates:[] };
    byMember[b.name].cnt++;
    byMember[b.name].total += b.amount;
    byMember[b.name].dates.push({ date: b.date, amount: b.amount });
  });
  let html = `<div style="font-size:11px;color:var(--t3);margin-bottom:12px">총 <b style="color:var(--blue)">${buyers.length}건</b> · 99만원 이상 구매 내역</div>`;
  Object.entries(byMember).forEach(([name, d]) => {
    html += `<div style="margin-bottom:12px;border:1px solid var(--bd);border-radius:10px;overflow:hidden">
      <div style="background:var(--s2);padding:8px 12px;display:flex;justify-content:space-between;align-items:center">
        <span style="font-weight:700;font-size:13px">${name}</span>
        <span style="font-size:11px;color:var(--t3)"><b style="color:var(--blue)">${d.cnt}건</b> · 합계 <b style="color:var(--amber)">${fmtW(d.total)}</b></span>
      </div>
      <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="background:var(--s3)">
          <th style="padding:5px 12px;text-align:left;font-weight:600;color:var(--t2)">구매일자</th>
          <th style="padding:5px 12px;text-align:right;font-weight:600;color:var(--t2)">금액</th>
        </tr></thead>
        <tbody>${d.dates.map((row,i) => `
          <tr style="border-top:1px solid var(--bd);background:${i%2===0?'transparent':'var(--s2)'}">
            <td style="padding:6px 12px;color:var(--t2)">${row.date||'-'}</td>
            <td style="padding:6px 12px;text-align:right;font-weight:700;color:var(--amber);font-family:'JetBrains Mono',monospace">${fmtW(row.amount)}</td>
          </tr>`).join('')}
        </tbody>
      </table>
    </div>`;
  });
  document.getElementById('lotto-pop-body').innerHTML = html;
  const pop = document.getElementById('lotto-pop');
  pop.style.display = 'flex';
  document.getElementById('lotto-pop-overlay').style.display = 'block';
}

function lottoPopClose() {
  document.getElementById('lotto-pop').style.display = 'none';
  document.getElementById('lotto-pop-overlay').style.display = 'none';
}

document.addEventListener('keydown', e => { if(e.key==='Escape') lottoPopClose(); });

async function loadCommLotto() {
  const periodVal = $('lotto-month')?.value;
  if (!periodVal) return;
  const el = $('lotto-tbl');
  el.innerHTML = '<div class="spin"></div>';
  await ensureData(periodVal);

  const sales    = S.sales.filter(s => (s.order_date||'').startsWith(periodVal));
  const pvMap    = buildPvMap(sales);
  const totalPV  = Object.values(pvMap).reduce((a,b)=>a+b, 0);
  const lottoPool = Math.round(totalPV * 0.03);
  const qual     = buildQualified(periodVal, S.sales);
  const m = S.members;

  const refCount   = {};
  const refDetails = {};

  sales.forEach(s => {
    const buyNo = findMemberNo(s);
    if (!buyNo) return;
    const refNo = m[buyNo]?.referrer_no;
    if (!refNo) return;
    const sAmt = parseInt(s.amount)||0;
    if (sAmt >= 990000) {
      refCount[refNo] = (refCount[refNo]||0) + 1;
      if (!refDetails[refNo]) refDetails[refNo] = [];
      refDetails[refNo].push({ name: m[buyNo]?.name||buyNo, date: s.order_date||'', amount: sAmt });
    }
  });

  const scores = {};
  let totalScore = 0;
  Object.entries(refCount).forEach(([no, cnt]) => {
    if (cnt < 5 || !qual[no]) return;
    const score = Math.floor(cnt / 5);
    scores[no] = score;
    totalScore += score;
  });

  _lottoRows = [];
  if (totalScore > 0 && lottoPool > 0) {
    Object.entries(scores).forEach(([no, score]) => {
      const amt = Math.round(lottoPool * score / totalScore);
      _lottoRows.push({
        member_no: no,
        name:      m[no]?.name || no,
        grade:     getEffectiveGrade(no, pvMap[no]||0),
        ref_count: refCount[no] || 0,
        score,
        pct:       (score / totalScore * 100).toFixed(1),
        total:     amt,
        details:   refDetails[no] || [],
      });
    });
  }

  showKpi('lotto-kpi', `
    <div class="comm-kpi"><div class="comm-kpi-v" style="color:var(--rose)">${fmtW(lottoPool)}</div><div class="comm-kpi-l">로또 풀 (총PV 3%)</div></div>
    <div class="comm-kpi"><div class="comm-kpi-v" style="color:var(--purple)">${totalScore}점</div><div class="comm-kpi-l">전체 점수</div></div>
    <div class="comm-kpi"><div class="comm-kpi-v" style="color:var(--blue)">${_lottoRows.length}</div><div class="comm-kpi-l">달성 회원 수</div></div>
  `);

  if (!_lottoRows.length) {
    el.innerHTML = `<div class="empty-msg">해당 기간 로또보너스 수령자가 없습니다.<br>
      <small style="color:var(--t3)">조건: 자격자이며, 추천인이 99만원↑ 구매 5건 이상 (5건=1점, 10건=2점, 15건=3점…)</small></div>`;
    return;
  }

  _lottoBannerHtml = `
    <div style="background:linear-gradient(135deg,rgba(173,20,87,.07),rgba(244,143,177,.07));border:1px solid rgba(173,20,87,.2);border-radius:10px;padding:12px 16px;margin-bottom:14px;font-size:12px">
      🎰 <b>총 PV ${fmt(totalPV)}</b> × 3% = 풀 <b style="color:var(--rose)">${fmtW(lottoPool)}</b> ÷ 총 <b>${totalScore}점</b> → 1점당 <b style="color:var(--purple)">${fmtW(totalScore?Math.round(lottoPool/totalScore):0)}</b>
    </div>`;

  lottoRenderTable();
}
</script>
