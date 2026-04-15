<?php /* panels/panel-referral.php — 추천 레그 */ ?>
<div class="panel" id="p-referral">
  <div class="card">
    <div class="card-hd">
      <span>🔗 추천 레그 (추천 구조)</span>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <input class="srch" id="rt-srch" placeholder="이름·ID 검색" oninput="hlNode(this.value,'rt')">
        <button class="btn bo" onclick="$('rt-srch').value='';hlNode('','rt')">✕</button>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:10px 0 14px;border-bottom:1px solid var(--bd);margin-bottom:14px">
      <div style="display:flex;align-items:center;gap:8px">
        <span style="font-size:11px;color:var(--t3);white-space:nowrap">표시 대수:</span>
        <select id="rt-depth" onchange="renderRT($('ref-wrap'))"
          style="background:var(--s1);border:1px solid var(--bd);color:var(--t1);padding:5px 10px;border-radius:8px;font-size:11px;font-family:inherit;outline:none;cursor:pointer">
          <option value="1">1대</option><option value="2">2대</option>
          <option value="3" selected>3대</option><option value="5">5대</option>
          <option value="7">7대</option><option value="10">10대</option>
          <option value="12">12대</option><option value="99">전체</option>
        </select>
      </div>
      <div style="display:flex;align-items:center;gap:6px">
        <span style="font-size:11px;color:var(--t3);white-space:nowrap">줌:</span>
        <button class="btn bo" style="padding:5px 10px;font-size:14px" onclick="rtZoom(-0.15)">−</button>
        <span id="rt-zoom-lbl" style="font-size:11px;color:var(--blue);min-width:40px;text-align:center;font-family:'JetBrains Mono',monospace">100%</span>
        <button class="btn bo" style="padding:5px 10px;font-size:14px" onclick="rtZoom(0.15)">+</button>
        <button class="btn bo" style="padding:5px 10px" onclick="rtZoomReset()">초기화</button>
      </div>
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <input class="srch" id="rt-root-inp" placeholder="회원번호·이름·ID 입력" style="width:180px" onkeydown="if(event.key==='Enter')rtSetRoot()">
        <button class="btn ba" onclick="rtSetRoot()">▶ 여기서부터 보기</button>
        <button id="rt-up-btn" class="btn bo" onclick="rtGoUp()" style="display:none">⬆ 위로 보기</button>
        <button class="btn bo" onclick="rtResetRoot()">전체 보기</button>
      </div>
    </div>
    <div id="ref-wrap" style="overflow:auto"><div class="empty-msg">파일을 업로드하거나<br>서버에 저장된 데이터를 불러오세요.</div></div>
  </div>
</div>

<style>
.rt-level { display:flex; flex-direction:column; align-items:center; }
.rt-vline-down { width:2px; min-height:20px; background:var(--bd); flex-shrink:0; }
.rt-children-row { display:flex; flex-wrap:nowrap; gap:0; align-items:flex-start; position:relative; }
.rt-hbar { position:absolute; top:0; height:2px; background:var(--bd); pointer-events:none; }
.rt-child-col { display:flex; flex-direction:column; align-items:center; padding:0 8px; }
.rt-child-col::before { content:''; display:block; width:2px; height:20px; background:var(--bd); flex-shrink:0; }
.rt-children-row.single > .rt-hbar { display:none !important; }
</style>

<script>
let RT_DATA=null, RT_ZOOM=1.0, RT_ROOT=null;
let RT_ROOT_HISTORY=[];

async function loadReferralTree() {
  const wrap=$('ref-wrap');
  wrap.innerHTML='<div class="spin"></div>';
  if (!S.loaded.members||!Object.keys(S.members).length) {
    const d=await apiFetch('api/data.php?action=members');
    (d.data||[]).forEach(m=>{S.members[m.member_no]=m;});
    S.loaded.members=true;
  }
  const per=period();
  if (!Object.keys(S.pvMap).length) {
    const d=await apiFetch(`api/data.php?action=pv&period=${per}`);
    S.pvMap=d.pv_map||{};
  }
  RT_DATA=buildTree('referral');
  RT_ROOT=null;
  if (!Object.keys(S.legPV).length) {
    apiFetch(`api/calc.php?action=preview&period=${per}`)
      .then(d=>{if(d&&d.leg_pv)S.legPV=d.leg_pv;renderRT(wrap);})
      .catch(()=>renderRT(wrap));
  } else { renderRT(wrap); }
}

function rtSetRoot() {
  const v=$('rt-root-inp').value.trim();
  if (!v){rtResetRoot();return;}
  if (!RT_DATA)return;
  let no=null;
  if (RT_DATA.tree[v])no=v;
  else{for(const m of Object.values(RT_DATA.tree)){if(m.name===v||m.login_id===v){no=m.member_no;break;}}}
  if (!no){alert('해당 회원을 찾을 수 없습니다: '+v);return;}
  if (RT_ROOT && RT_ROOT!==no) RT_ROOT_HISTORY.push(RT_ROOT);
  RT_ROOT=no; rtUpdateUpBtn(); renderRT($('ref-wrap'));
}
function rtResetRoot(){
  RT_ROOT=null; RT_ROOT_HISTORY=[]; $('rt-root-inp').value='';
  rtUpdateUpBtn(); renderRT($('ref-wrap'));
}
function rtFocusNode(no){
  if (RT_ROOT && RT_ROOT!==no) RT_ROOT_HISTORY.push(RT_ROOT);
  RT_ROOT=no; $('rt-root-inp').value=no;
  rtUpdateUpBtn(); renderRT($('ref-wrap')); $('ref-wrap').scrollTop=0;
}
function rtGoUp() {
  if (!RT_DATA||!RT_ROOT) return;
  const cur = RT_DATA.tree[RT_ROOT];
  const parentNo = cur?.referrer_no || null;
  if (!parentNo || !RT_DATA.tree[parentNo]) { alert('더 이상 위 단계가 없습니다.'); return; }
  RT_ROOT_HISTORY.push(RT_ROOT);
  RT_ROOT=parentNo;
  $('rt-root-inp').value=parentNo;
  rtUpdateUpBtn(); renderRT($('ref-wrap')); $('ref-wrap').scrollTop=0;
}
function rtUpdateUpBtn() {
  const btn=$('rt-up-btn');
  if (!btn) return;
  if (RT_ROOT && RT_DATA) {
    const cur=RT_DATA.tree[RT_ROOT];
    const hasParent=cur?.referrer_no && RT_DATA.tree[cur.referrer_no];
    btn.style.display=hasParent?'':'none';
  } else { btn.style.display='none'; }
}

function renderRT(wrap) {
  if (!RT_DATA){wrap.innerHTML='<div class="empty-msg">데이터 없음</div>';return;}
  const {tree,roots}=RT_DATA;
  const maxDepth=parseInt($('rt-depth')?.value||'3');
  const startRoots=RT_ROOT?[RT_ROOT]:roots;
  const html=startRoots.map(r=>renderRTNode(tree,r,0,maxDepth)).join('');
  wrap.innerHTML=`<div id="rt-inner" style="display:inline-flex;flex-wrap:wrap;gap:32px;padding:20px;transform-origin:top left;transform:scale(${RT_ZOOM})">${html}</div>`;
  requestAnimationFrame(()=>rtPlaceHbars());
}

function rtPlaceHbars() {
  const inner=document.getElementById('rt-inner');
  if (!inner)return;
  const zoom = RT_ZOOM || 1;
  inner.querySelectorAll('.rt-children-row:not(.single)').forEach(row=>{
    const cols=Array.from(row.querySelectorAll(':scope>.rt-child-col'));
    if (cols.length<2)return;
    const hbar=row.querySelector(':scope>.rt-hbar');
    if (!hbar)return;
    // offsetLeft 기반으로 줌 무관하게 정확한 레이아웃 좌표 계산
    // (offsetLeft/offsetWidth는 CSS 변환 전 레이아웃 좌표를 반환)
    const rowRect = row.getBoundingClientRect();
    const fc = cols[0];
    const lc = cols[cols.length-1];
    const fcRect = fc.getBoundingClientRect();
    const lcRect = lc.getBoundingClientRect();
    // 화면 좌표 → 레이아웃 좌표: 줌 역변환 적용
    const rowLeft = rowRect.left;
    const x1 = ((fcRect.left + fcRect.right) / 2 - rowLeft) / zoom;
    const x2 = ((lcRect.left + lcRect.right) / 2 - rowLeft) / zoom;
    hbar.style.left = x1 + 'px';
    hbar.style.width = Math.max(0, x2 - x1) + 'px';
  });
}

function renderRTNode(tree,no,depth,maxDepth) {
  if (!no||!tree[no]||depth>=maxDepth)return'';
  const m=tree[no];
  const monthPv=S.pvMap[no]||0;
  // 실매출 등급 = 누적 max_grade (tree 노드 직접 사용 — 캐시 타이밍 문제 방지)
  const _maxGrade  = m.max_grade || m.grade || '';
  const salesGrade = (_maxGrade && _maxGrade !== '미달성') ? _maxGrade : (calcGradeJs(monthPv) || '미달성');
  const honorInfo  = S.honorMap[no]||null;
  const honorGrade = honorInfo?(honorInfo.grade||''):'';
  const effGrade   = getEffectiveGrade(no,monthPv);
  const rank=getRankForNode(no);
  const hasRank=rank!=='미달성';
  // 직급 뱃지 색상 — 진한 단색 배경 + 흰/어두운 글자 (시인성 확보)
  const rankSolidBg  = {'1스타':'#0288d1','2스타':'#2e7d32','3스타':'#f9a825','4스타':'#d84315','5스타':'#ad1457'};
  const rankTextColor= {'1스타':'#fff',   '2스타':'#fff',   '3스타':'#1a1a1a','4스타':'#fff',   '5스타':'#fff'};
  const displayId=m.login_id||'';
  const children=(m.children||[]);
  const hasChildren=children.length>0&&(depth+1<maxDepth);
  const totalBelow=countDescendants(tree,no);

  // 등급 뱃지: 실매출 우선, 인정등급이면 IN뱃지
  const gradeBadge = salesGrade!=='미달성'
    ? `<span class="gb g${salesGrade}" style="font-size:8px">${salesGrade}</span>`
      + (honorGrade?`<span class="honor-in-badge">IN</span>`:'')
    : honorGrade
      ? `<span class="gb g${honorGrade}" style="font-size:8px">${honorGrade}</span><span class="honor-in-badge">IN</span>`
      : `<span class="gb g미달성" style="font-size:8px">미달성</span>`;

  const rankBadge = hasRank
    ? `<div style="position:absolute;top:-9px;right:-6px;background:${rankSolidBg[rank]};color:${rankTextColor[rank]};
        font-size:8px;font-weight:900;padding:2px 6px;border-radius:8px;z-index:2;white-space:nowrap;
        box-shadow:0 1px 3px rgba(0,0,0,.25);letter-spacing:.3px">${rank.replace('스타','★')}</div>`
    : '';

  const pvColor = monthPv>0?'var(--blue)':'var(--t3)';
  const pvLabel = monthPv>0?`${fmt(monthPv)} PV`:'- PV';

  const focusBtn=`<button onclick="event.stopPropagation();rtFocusNode('${no}')"
    style="margin-top:5px;width:100%;padding:3px 0;background:rgba(26,86,219,.12);border:1px solid rgba(26,86,219,.3);border-radius:5px;color:var(--blue);font-size:8px;cursor:pointer;font-family:inherit">▶ 여기서부터 보기</button>`;

  let childrenHtml='';
  if (hasChildren) {
    const isSingle=children.length===1;
    childrenHtml=`<div class="rt-vline-down"></div>
      <div class="rt-children-row${isSingle?' single':''}">
        <div class="rt-hbar"></div>
        ${children.map(c=>`<div class="rt-child-col">${renderRTNode(tree,c,depth+1,maxDepth)}</div>`).join('')}
      </div>`;
  } else if (children.length>0) {
    childrenHtml=`<div style="font-size:9px;color:var(--t3);text-align:center;padding:6px 0 0">▼ +${children.length}명 더 있음</div>`;
  }

  // 테두리: 뱃지와 동일한 salesGrade(누적 max_grade) 기준 — 뱃지색 = 테두리색 항상 일치
  const GRADE_BORDER = {'베이직':'#7c4dff','플러스':'#0288d1','골드':'#f57c00','플래티넘':'#00897b','미달성':'#b0bec5','회원':'#b0bec5'};
  const _displayGrade = salesGrade !== '미달성' ? salesGrade : (honorGrade || '미달성');
  const borderColor  = hasRank ? rankSolidBg[rank] : (GRADE_BORDER[_displayGrade] || '#b0bec5');
  const borderWidth  = hasRank ? '2.5px' : '2px';

  return `<div class="rt-level">
    <div class="tcard g${_displayGrade}" id="rt-${no}" onclick="openMo('${no}')" style="position:relative;${hasRank?'border-color:'+rankSolidBg[rank]+'!important;border-width:2.5px!important':''}">
      ${rankBadge}
      <div class="tc-name">${m.name||no}</div>
      ${displayId?`<div style="font-size:9px;color:var(--t3);font-family:'JetBrains Mono',monospace;margin-top:1px">${displayId}</div>`:''}
      <div class="tc-pv" style="color:${pvColor}">${pvLabel}</div>
      <div style="margin-top:3px">${gradeBadge}</div>
      ${hasRank?`<div style="margin-top:2px"><span class="gb g${rank}" style="font-size:8px">${rank}</span></div>`:''}
      ${children.length>0?`<div style="font-size:9px;color:var(--t3);margin-top:3px">직추천 ${children.length}명 · 하위 총 ${totalBelow}명</div>`:''}
      ${focusBtn}
    </div>
    ${childrenHtml}
  </div>`;
}

function countDescendants(tree,no) {
  let cnt=0;
  const q=[...(tree[no]?.children||[])];
  const vis={};
  while(q.length){const cur=q.shift();if(vis[cur]||!tree[cur])continue;vis[cur]=true;cnt++;(tree[cur].children||[]).forEach(c=>q.push(c));}
  return cnt;
}

function rtZoom(delta){RT_ZOOM=Math.min(2.0,Math.max(0.2,RT_ZOOM+delta));applyRtZoom();}
function rtZoomReset(){RT_ZOOM=1.0;applyRtZoom();}
function applyRtZoom(){
  const inner=$('rt-inner');
  if(inner){inner.style.transform=`scale(${RT_ZOOM})`;inner.style.transformOrigin='top left';}
  $('rt-zoom-lbl').textContent=Math.round(RT_ZOOM*100)+'%';
  requestAnimationFrame(()=>rtPlaceHbars());
}
</script>
