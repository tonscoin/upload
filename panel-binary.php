<?php /* panels/panel-binary.php — 바이너리 레그 */ ?>
<div class="panel" id="p-binary">
  <div class="card">
    <div class="card-hd">
      <span>🌳 바이너리 레그 (후원 구조)</span>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <input class="srch" id="bt-srch" placeholder="회원번호·이름 검색" oninput="hlNode(this.value,'bt')">
        <button class="btn bo" onclick="$('bt-srch').value='';hlNode('','bt')">✕</button>
      </div>
    </div>
    <div id="bt-controls" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:10px 0 14px;border-bottom:1px solid var(--bd);margin-bottom:14px">
      <div style="display:flex;align-items:center;gap:8px">
        <span style="font-size:11px;color:var(--t3);white-space:nowrap">표시 대수:</span>
        <select id="bt-depth" onchange="renderBT($('binary-wrap'))"
          style="background:var(--s1);border:1px solid var(--bd);color:var(--t1);padding:5px 10px;border-radius:8px;font-size:11px;font-family:inherit;outline:none;cursor:pointer">
          <option value="3">3대</option><option value="5">5대</option><option value="7" selected>7대</option>
          <option value="10">10대</option><option value="15">15대</option><option value="20">20대</option>
        </select>
      </div>
      <div style="display:flex;align-items:center;gap:6px">
        <span style="font-size:11px;color:var(--t3);white-space:nowrap">줌:</span>
        <button class="btn bo" style="padding:5px 10px;font-size:14px" onclick="btZoom(-0.15)">−</button>
        <span id="bt-zoom-lbl" style="font-size:11px;color:var(--blue);min-width:40px;text-align:center;font-family:'JetBrains Mono',monospace">100%</span>
        <button class="btn bo" style="padding:5px 10px;font-size:14px" onclick="btZoom(0.15)">+</button>
        <button class="btn bo" style="padding:5px 10px" onclick="btZoomReset()">초기화</button>
      </div>
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <input class="srch" id="bt-root-inp" placeholder="시작 회원번호 입력" style="width:160px" onkeydown="if(event.key==='Enter')btSetRoot()">
        <button class="btn ba" onclick="btSetRoot()">▶ 여기서부터 보기</button>
        <button id="bt-up-btn" class="btn bo" onclick="btGoUp()" style="display:none">⬆ 위로 보기</button>
        <button class="btn bo" onclick="btResetRoot()">전체 보기</button>
      </div>
    </div>
    <div id="binary-wrap"><div class="empty-msg">파일을 업로드하거나<br>서버에 저장된 데이터를 불러오세요.</div></div>
  </div>
</div>

<script>
// ─── 바이너리 트리 ───
let BT_DATA = null;
let BT_ZOOM = 1.0;
let BT_ROOT = null;
let BT_ROOT_HISTORY = []; // 위로보기 히스토리

async function loadBinaryTree() {
  const wrap = $('binary-wrap');
  wrap.innerHTML = '<div class="spin"></div>';
  if (S.loaded.members && Object.keys(S.members).length) {
    BT_DATA = buildTree('binary');
  } else {
    const d = await apiFetch('api/data.php?action=members');
    (d.data||[]).forEach(m => { S.members[m.member_no]=m; });
    S.loaded.members = true;
    BT_DATA = buildTree('binary');
  }
  const per = period();
  if (!Object.keys(S.pvMap).length) {
    const d = await apiFetch(`api/data.php?action=pv&period=${per}`);
    S.pvMap = d.pv_map || {};
    if (d.grade_map) S.gradeMap = d.grade_map;
  }
  BT_ROOT = null;
  if (!Object.keys(S.legPV).length) {
    apiFetch(`api/calc.php?action=preview&period=${per}`)
      .then(d => { if(d&&d.leg_pv) S.legPV=d.leg_pv; renderBT(wrap); })
      .catch(() => renderBT(wrap));
  } else {
    renderBT(wrap);
  }
}

function buildTree(type) {
  const map = {};
  Object.values(S.members).forEach(m => { map[m.member_no]={...m,cL:null,cR:null,children:[]}; });
  const roots = [];
  if (type === 'binary') {
    Object.values(map).forEach(m => {
      if (m.sponsor_no && map[m.sponsor_no]) {
        if (m.position==='R') map[m.sponsor_no].cR=m.member_no;
        else                  map[m.sponsor_no].cL=m.member_no;
      } else roots.push(m.member_no);
    });
  } else {
    Object.values(map).forEach(m => {
      if (m.referrer_no && map[m.referrer_no]) map[m.referrer_no].children.push(m.member_no);
      else roots.push(m.member_no);
    });
  }
  return { tree:map, roots:[...new Set(roots)] };
}

function btZoom(delta) { BT_ZOOM=Math.min(2.0,Math.max(0.2,BT_ZOOM+delta)); applyBtZoom(); }
function btZoomReset() { BT_ZOOM=1.0; applyBtZoom(); }
function applyBtZoom() {
  const inner=$('bt-inner');
  if (inner) inner.style.transform=`scale(${BT_ZOOM})`;
  $('bt-zoom-lbl').textContent=Math.round(BT_ZOOM*100)+'%';
}

function btSetRoot() {
  const v=$('bt-root-inp').value.trim();
  if (!v) { btResetRoot(); return; }
  if (!BT_DATA) return;
  let no = null;
  if (BT_DATA.tree[v]) no=v;
  else { for(const m of Object.values(BT_DATA.tree)){ if(m.name===v||m.login_id===v){no=m.member_no;break;} } }
  if (!no) { alert('해당 회원을 찾을 수 없습니다: '+v); return; }
  if (BT_ROOT && BT_ROOT !== no) BT_ROOT_HISTORY.push(BT_ROOT);
  BT_ROOT=no; btUpdateUpBtn(); renderBT($('binary-wrap'));
}
function btResetRoot() {
  BT_ROOT=null; BT_ROOT_HISTORY=[]; $('bt-root-inp').value='';
  btUpdateUpBtn(); renderBT($('binary-wrap'));
}
function btFocusNode(no) {
  if (BT_ROOT && BT_ROOT !== no) BT_ROOT_HISTORY.push(BT_ROOT);
  BT_ROOT=no; $('bt-root-inp').value=no;
  btUpdateUpBtn(); renderBT($('binary-wrap')); $('binary-wrap').scrollTop=0;
}
function btGoUp() {
  if (!BT_DATA || !BT_ROOT) return;
  // 현재 루트의 sponsor(후원인) 찾기
  const cur = BT_DATA.tree[BT_ROOT];
  const parentNo = cur?.sponsor_no || null;
  if (!parentNo || !BT_DATA.tree[parentNo]) {
    alert('더 이상 위 단계가 없습니다.'); return;
  }
  BT_ROOT_HISTORY.push(BT_ROOT);
  BT_ROOT = parentNo;
  $('bt-root-inp').value = parentNo;
  btUpdateUpBtn(); renderBT($('binary-wrap')); $('binary-wrap').scrollTop=0;
}
function btUpdateUpBtn() {
  const btn = $('bt-up-btn');
  if (!btn) return;
  if (BT_ROOT && BT_DATA) {
    const cur = BT_DATA.tree[BT_ROOT];
    const hasParent = cur?.sponsor_no && BT_DATA.tree[cur.sponsor_no];
    btn.style.display = hasParent ? '' : 'none';
  } else {
    btn.style.display = 'none';
  }
}

function renderBT(wrap) {
  if (!BT_DATA) { wrap.innerHTML='<div class="empty-msg">데이터 없음</div>'; return; }
  const {tree,roots}=BT_DATA;
  const maxDepth=parseInt($('bt-depth')?.value||'7');
  const startRoots=BT_ROOT?[BT_ROOT]:roots.slice(0,10);
  const html=startRoots.map(r=>renderBTNode(tree,r,0,maxDepth)).join('');
  wrap.innerHTML=`
    <div id="bt-inner" style="position:relative;display:inline-flex;flex-wrap:wrap;gap:28px;padding:20px;transform-origin:top left;transform:scale(${BT_ZOOM})">
      ${html}
      <svg id="bt-svg" style="position:absolute;top:0;left:0;pointer-events:none;overflow:visible" width="0" height="0"></svg>
    </div>`;
  requestAnimationFrame(() => btDrawLines(tree, startRoots, maxDepth));
}

function btDrawLines(tree, roots, maxDepth) {
  const svg = document.getElementById('bt-svg');
  const inner = document.getElementById('bt-inner');
  if (!svg || !inner) return;
  const scale = BT_ZOOM;
  const base = inner.getBoundingClientRect();
  const lines = [];

  function toLocal(rect) {
    return {
      left:   (rect.left   - base.left)  / scale,
      top:    (rect.top    - base.top)   / scale,
      right:  (rect.right  - base.left)  / scale,
      bottom: (rect.bottom - base.top)   / scale,
      width:  rect.width  / scale,
      height: rect.height / scale,
    };
  }

  function draw(no, depth) {
    if (!no || !tree[no] || depth >= maxDepth) return;
    const m = tree[no];
    const pe = document.getElementById('bt-'+no);
    if (!pe) return;
    const pr = toLocal(pe.getBoundingClientRect());
    const px = pr.left + pr.width / 2;
    const py = pr.bottom;
    const children = [m.cL, m.cR].filter(Boolean);
    children.forEach(cNo => {
      const ce = document.getElementById('bt-'+cNo);
      if (!ce) return;
      const cr = toLocal(ce.getBoundingClientRect());
      const cx = cr.left + cr.width / 2;
      const cy = cr.top;
      const midY = py + (cy - py) * 0.45;
      lines.push(`<path d="M${px},${py} V${midY} H${cx} V${cy}" fill="none" stroke="var(--bd)" stroke-width="1.5" stroke-linecap="round"/>`);
      draw(cNo, depth + 1);
    });
  }
  roots.forEach(r => draw(r, 0));
  const w = inner.scrollWidth / scale;
  const h = inner.scrollHeight / scale;
  svg.setAttribute('width', w);
  svg.setAttribute('height', h);
  svg.setAttribute('viewBox', `0 0 ${w} ${h}`);
  svg.innerHTML = lines.join('');
}

function getRankForNode(no) {
  const lp=S.legPV[no]||{L:0,R:0};
  return calcRankJs(lp);
}

function getBTGradeInfo(no, monthPv, treeNode) {
  // 누적 실매출 등급 — tree 노드 데이터를 우선 사용 (S.members 캐시 타이밍 문제 방지)
  const mem = treeNode || S.members[no] || {};
  const maxGrade   = mem.max_grade || mem.grade || '';
  // 인정등급
  const honorInfo  = S.honorMap[no] || null;
  const honorGrade = honorInfo ? (honorInfo.grade || '') : '';
  // 최종 유효 등급
  const effGrade   = getEffectiveGrade(no, monthPv);
  // 뱃지/테두리: max_grade 기준 (한번 달성하면 영구 유지)
  // max_grade 없으면 이달 PV 등급으로 폴백
  const salesGrade = maxGrade && maxGrade !== '미달성' ? maxGrade : (calcGradeJs(monthPv) || '미달성');
  const isHonorOnly = honorGrade && (!maxGrade || maxGrade === '미달성');
  return { salesGrade, honorGrade, honorInfo, effGrade, isHonorOnly };
}

function renderBTNode(tree,no,depth,maxDepth) {
  if (!no||!tree[no]||depth>=maxDepth) return '';
  const m=tree[no];
  const monthPv=S.pvMap[no]||0;
  const { salesGrade, honorGrade, honorInfo, effGrade, isHonorOnly } = getBTGradeInfo(no, monthPv, m);
  const lNo=m.cL, rNo=m.cR;
  const hasChild=(lNo||rNo)&&(depth+1<maxDepth);
  const rank=getRankForNode(no)||'미달성';
  const hasRank=rank!=='미달성';
  // 직급 색상 (등급과 별도 색 사용)
  // 직급 뱃지 색상 — 진한 배경 + 흰(또는 어두운) 글자로 시인성 확보
  const rankSolidBg  = {'1스타':'#0288d1','2스타':'#2e7d32','3스타':'#f9a825','4스타':'#d84315','5스타':'#ad1457'};
  const rankTextColor= {'1스타':'#fff',   '2스타':'#fff',   '3스타':'#1a1a1a','4스타':'#fff',   '5스타':'#fff'};
  const displayId=m.login_id||m.name||no;

  // 등급 뱃지 영역: 실매출이 있으면 실매출 우선, 인정등급이 있으면 IN뱃지 추가
  const gradeBadge = salesGrade !== '미달성'
    ? `<span class="gb g${salesGrade}" style="font-size:8px">${salesGrade}</span>`
      + (honorGrade ? `<span class="honor-in-badge">IN</span>` : '')
    : honorGrade
      ? `<span class="gb g${honorGrade}" style="font-size:8px">${honorGrade}</span><span class="honor-in-badge">IN</span>`
      : `<span class="gb g미달성" style="font-size:8px">미달성</span>`;

  // 직급 뱃지 — 진한 단색 배경, 테두리와 동일 색, 글자는 흰/검정
  const rankBadge = hasRank
    ? `<div style="position:absolute;top:-9px;right:-6px;background:${rankSolidBg[rank]};color:${rankTextColor[rank]};
        font-size:8px;font-weight:900;padding:2px 6px;border-radius:8px;z-index:2;white-space:nowrap;
        box-shadow:0 1px 3px rgba(0,0,0,.25);letter-spacing:.3px">${rank.replace('스타','★')}</div>`
    : '';

  const focusBtn=`<button onclick="event.stopPropagation();btFocusNode('${no}')"
    style="margin-top:5px;width:100%;padding:3px 0;background:rgba(26,86,219,.12);border:1px solid rgba(26,86,219,.3);
    border-radius:5px;color:var(--blue);font-size:8px;cursor:pointer;font-family:inherit">▶ 여기서부터 보기</button>`;

  // 이달 PV 색상: 실매출이 있으면 파란색, 없으면 흐리게
  const pvColor = monthPv > 0 ? 'var(--blue)' : 'var(--t3)';
  const pvLabel = monthPv > 0 ? `${fmt(monthPv)} PV` : '- PV';

  // 테두리: 뱃지와 동일한 salesGrade(누적 max_grade) 기준 — 뱃지색 = 테두리색 항상 일치
  const GRADE_BORDER = {'베이직':'#7c4dff','플러스':'#0288d1','골드':'#f57c00','플래티넘':'#00897b','미달성':'#b0bec5','회원':'#b0bec5'};
  const _displayGrade = salesGrade !== '미달성' ? salesGrade : (honorGrade || '미달성');
  const borderColor  = hasRank ? rankSolidBg[rank] : (GRADE_BORDER[_displayGrade] || '#b0bec5');
  const borderWidth  = hasRank ? '2.5px' : '2px';
  return `<div class="tnode" style="display:inline-flex;flex-direction:column;align-items:center">
    <div class="tcard g${_displayGrade}" id="bt-${no}" onclick="openMo('${no}')"
      style="position:relative;${hasRank?'border-color:'+rankSolidBg[rank]+'!important;border-width:2.5px!important':''}">
      <div class="tc-pos ${m.position==='L'?'pl':'pr'}">${m.position||''}</div>
      ${rankBadge}
      <div class="tc-name">${m.name||no}</div>
      <div style="font-size:9px;color:var(--t3);font-family:'JetBrains Mono',monospace;margin-top:2px">${displayId}</div>
      <div class="tc-pv" style="color:${pvColor}">${pvLabel}</div>
      <div style="margin-top:4px">${gradeBadge}</div>
      ${hasRank?`<div style="margin-top:3px"><span class="gb g${rank}" style="font-size:8px">${rank}</span></div>`:''}
      ${focusBtn}
    </div>
    ${hasChild?`
    <div style="display:flex;gap:24px;margin-top:36px;align-items:flex-start">
      <div style="display:flex;flex-direction:column;align-items:center">
        <div class="tchild-lbl lbl-L" style="margin-bottom:4px">◀ L</div>
        ${lNo?renderBTNode(tree,lNo,depth+1,maxDepth):'<div class="empty-slot"><p>빈 슬롯 L</p></div>'}
      </div>
      <div style="display:flex;flex-direction:column;align-items:center">
        <div class="tchild-lbl lbl-R" style="margin-bottom:4px">R ▶</div>
        ${rNo?renderBTNode(tree,rNo,depth+1,maxDepth):'<div class="empty-slot"><p>빈 슬롯 R</p></div>'}
      </div>
    </div>`:''}
  </div>`;
}



function hlNode(q,type) {
  document.querySelectorAll('.tcard,.rtree-card,.rtree-card2').forEach(c=>c.classList.remove('hl'));
  if (!q) return;
  const prefix=type==='bt'?'bt-':'rt-';
  const tree=(type==='bt'?BT_DATA:RT_DATA)?.tree||{};
  Object.values(tree).forEach(m=>{
    if((m.name||'').includes(q)||(m.member_no||'').includes(q)||(m.login_id||'').includes(q)){
      const el=$(prefix+m.member_no);
      if(el){el.classList.add('hl');el.scrollIntoView({behavior:'smooth',block:'center'});}
    }
  });
}
</script>
