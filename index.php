<?php
require_once __DIR__ . '/config.php';
requireLogin();
?><!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= SYSTEM_TITLE ?></title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;500;700;900&family=JetBrains+Mono:wght@400;700&display=swap');
:root{
  --bg:#f0f2f5; --s1:#ffffff; --s2:#f8f9fa; --s3:#e9ecef;
  --bd:#dee2e6; --bd2:#ced4da;
  --t1:#1a1d23; --t2:#495057; --t3:#868e96;
  --blue:#1a56db; --blue2:#1e88e5; --sky:#0ea5e9;
  --green:#2e7d32; --red:#c62828; --amber:#e65100;
  --purple:#6a1b9a; --teal:#00695c; --rose:#ad1457;
  --grad:linear-gradient(135deg,#1a56db,#42a5f5);
  --grad2:linear-gradient(135deg,#2e7d32,#66bb6a);
  --sidebar-w:220px;
}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Noto Sans KR',sans-serif;background:var(--bg);color:var(--t1);height:100vh;overflow:hidden}
.mono{font-family:'JetBrains Mono',monospace}

/* ── LAYOUT ── */
#app{display:flex;height:100vh}
#sidebar{width:var(--sidebar-w);flex-shrink:0;background:var(--s1);border-right:1px solid var(--bd);display:flex;flex-direction:column;overflow-y:auto;transition:transform .25s cubic-bezier(.4,0,.2,1);z-index:300}
#main{flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0}
#topbar{height:52px;background:var(--s1);border-bottom:1px solid var(--bd);display:flex;align-items:center;padding:0 20px;gap:10px;flex-shrink:0;box-shadow:0 1px 4px rgba(0,0,0,.06)}
#content{flex:1;overflow-y:auto;padding:18px 20px;background:var(--bg)}

/* ══════════════════════════════
   모바일 햄버거 메뉴
══════════════════════════════ */
/* 햄버거 버튼 — 기본 숨김 */
#menu-toggle{
  display:none;
  align-items:center;justify-content:center;
  width:36px;height:36px;
  background:transparent;
  border:1px solid var(--bd2);
  border-radius:8px;
  cursor:pointer;
  flex-shrink:0;
  font-size:18px;
  color:var(--t1);
  transition:.15s;
}
#menu-toggle:hover{background:var(--s3)}

/* 오버레이 — 사이드바 열렸을 때 배경 어둡게 */
#sidebar-overlay{
  display:none;
  position:fixed;inset:0;
  background:rgba(0,0,0,.42);
  z-index:299;
  backdrop-filter:blur(2px);
  -webkit-backdrop-filter:blur(2px);
  animation:fadeInOv .22s ease;
}
#sidebar-overlay.open{display:block}
@keyframes fadeInOv{from{opacity:0}to{opacity:1}}

@media(max-width:768px){
  /* 햄버거 버튼 노출 */
  #menu-toggle{display:flex}

  /* 사이드바: 화면 밖으로 숨김 */
  #sidebar{
    position:fixed;
    left:0;top:0;bottom:0;
    transform:translateX(-100%);
    box-shadow:4px 0 24px rgba(0,0,0,.18);
    z-index:300;
  }
  /* 열린 상태 */
  #sidebar.open{transform:translateX(0)}

  /* 탑바 패딩 줄임 */
  #topbar{padding:0 12px;gap:7px}
  #content{padding:12px 14px}

  /* 기간 입력 줄임 */
  input[type=month],input[type=week]{font-size:11px;padding:5px 7px}

  /* topbar-title 폰트 조정 */
  #topbar-title{font-size:12px}
}

/* ── SIDEBAR ── */
.sb-logo{padding:16px 14px 12px;background:var(--grad);color:#fff;flex-shrink:0}
.sb-logo .badge{font-size:8px;font-weight:700;letter-spacing:2px;opacity:.85;text-transform:uppercase}
.sb-logo h2{font-size:13px;font-weight:900;margin-top:3px;letter-spacing:-.3px}
.sb-sec{padding:4px 0;border-bottom:1px solid var(--bd)}
.sb-lbl{font-size:8px;color:var(--t3);font-weight:700;letter-spacing:1.5px;padding:8px 14px 3px;text-transform:uppercase}
.sb-item{display:flex;align-items:center;gap:8px;padding:8px 14px;cursor:pointer;font-size:11.5px;font-weight:500;color:var(--t2);border-left:3px solid transparent;transition:.12s;user-select:none}
.sb-item:hover{color:var(--blue);background:rgba(26,86,219,.05)}
.sb-item.on{color:var(--blue);background:rgba(26,86,219,.09);border-left-color:var(--blue);font-weight:700}
.sb-item .ico{font-size:13px;width:18px;text-align:center;flex-shrink:0}
.sb-foot{margin-top:auto;padding:10px 12px;border-top:1px solid var(--bd)}
.btn-lo{width:100%;padding:7px;background:rgba(198,40,40,.08);border:1px solid rgba(198,40,40,.2);border-radius:7px;color:var(--red);font-size:11px;font-weight:700;cursor:pointer;font-family:inherit;transition:.15s}
.btn-lo:hover{background:rgba(198,40,40,.15)}

/* ── TOPBAR ── */
#topbar-title{font-size:13px;font-weight:700;color:var(--t1)}
.topbar-r{margin-left:auto;display:flex;align-items:center;gap:8px}
input[type=month],input[type=week]{background:var(--s1);border:1px solid var(--bd);color:var(--t1);padding:5px 10px;border-radius:7px;font-size:11px;font-family:inherit;outline:none;transition:.15s}
input[type=month]:focus,input[type=week]:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(26,86,219,.1)}

/* ── BUTTONS ── */
.btn{display:inline-flex;align-items:center;gap:5px;padding:6px 13px;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;border:none;font-family:inherit;transition:.15s;white-space:nowrap}
.btn:hover{opacity:.88;transform:translateY(-1px)}
.btn:active{transform:translateY(0)}
.bp{background:var(--grad);color:#fff;box-shadow:0 2px 6px rgba(26,86,219,.25)}
.bg{background:rgba(46,125,50,.1);border:1px solid rgba(46,125,50,.25);color:var(--green)}
.ba{background:rgba(230,81,0,.1);border:1px solid rgba(230,81,0,.25);color:var(--amber)}
.bo{background:transparent;border:1px solid var(--bd2);color:var(--t2)}
.bo:hover{border-color:var(--blue);color:var(--blue)}
.bo.on{background:rgba(26,86,219,.1);border-color:var(--blue);color:var(--blue)}
.br{background:rgba(198,40,40,.08);border:1px solid rgba(198,40,40,.2);color:var(--red)}

/* ── PANELS ── */
.panel{display:none}.panel.on{display:block}

/* ── CARDS ── */
.card{background:var(--s1);border:1px solid var(--bd);border-radius:12px;padding:16px 18px;margin-bottom:14px;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.card-hd{font-size:12px;font-weight:700;color:var(--t1);margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;gap:8px}
.stat-g{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:14px}
.stat{background:var(--s1);border:1px solid var(--bd);border-radius:12px;padding:14px 16px;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.stat-lbl{font-size:9px;color:var(--t3);font-weight:700;letter-spacing:.5px;text-transform:uppercase}
.stat-val{font-size:22px;font-weight:900;margin-top:3px;font-family:'JetBrains Mono',monospace}
.stat-sub{font-size:9px;color:var(--t3);margin-top:1px}

/* ── TABLE ── */
.tw{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:11px}
thead th{background:var(--s2);padding:9px 11px;text-align:left;color:var(--t1);font-weight:700;border-bottom:2px solid var(--bd);white-space:nowrap;position:sticky;top:0;z-index:1}
tbody td{padding:8px 11px;border-bottom:1px solid var(--bd);color:var(--t1);white-space:nowrap}
tbody tr:hover td{background:rgba(26,86,219,.04)}

/* ── 등급 뱃지 ── */
.gb{display:inline-block;padding:2px 7px;border-radius:20px;font-size:9px;font-weight:700;white-space:nowrap}
.g회원,.g미달성{background:rgba(134,142,150,.13);color:#546e7a}
.g베이직{background:rgba(103,58,183,.13);color:#512da8}
.g플러스{background:rgba(2,119,189,.13);color:#0277bd}
.g골드{background:rgba(245,124,0,.15);color:#e65100}
.g플래티넘{background:rgba(0,121,107,.15);color:#00695c}
.g1스타{background:rgba(41,182,246,.18);color:#0277bd;border:1px solid rgba(41,182,246,.4)}
.g2스타{background:rgba(102,187,106,.18);color:#1b5e20;border:1px solid rgba(102,187,106,.4)}
.g3스타{background:rgba(255,202,40,.22);color:#6d4c00;border:1px solid rgba(255,202,40,.5)}
.g4스타{background:rgba(255,112,67,.18);color:#bf360c;border:1px solid rgba(255,112,67,.4)}
.g5스타{background:rgba(236,64,122,.18);color:#880e4f;border:1px solid rgba(236,64,122,.4)}
.honor-in-badge{display:inline-block;background:#e53935;color:#fff;font-size:8px;font-weight:900;padding:1px 4px;border-radius:4px;margin-left:2px;vertical-align:middle;letter-spacing:.5px}

.tcard.g미달성,.rtree-card.g미달성,.rtree-card2.g미달성{border-color:#b0bec5!important}
.tcard.g회원,.rtree-card.g회원,.rtree-card2.g회원{border-color:#b0bec5!important}
.tcard.g베이직,.rtree-card.g베이직,.rtree-card2.g베이직{border-color:#7c4dff!important;background:rgba(103,58,183,.04)!important}
.tcard.g플러스,.rtree-card.g플러스,.rtree-card2.g플러스{border-color:#0288d1!important;background:rgba(2,119,189,.04)!important}
.tcard.g골드,.rtree-card.g골드,.rtree-card2.g골드{border-color:#f57c00!important;background:rgba(245,124,0,.05)!important}
.tcard.g플래티넘,.rtree-card.g플래티넘,.rtree-card2.g플래티넘{border-color:#00897b!important;background:rgba(0,121,107,.05)!important}
.pl{color:var(--blue);font-weight:700}.pr{color:var(--amber);font-weight:700}

/* ── UPLOAD ── */
.umode{display:flex;gap:8px;margin-bottom:12px}
.umode-btn{flex:1;padding:9px 8px;border-radius:7px;border:1px solid var(--bd);background:var(--s2);color:var(--t2);cursor:pointer;font-size:11px;font-weight:700;font-family:inherit;text-align:center;transition:.15s}
.umode-btn.on{border-color:var(--blue);color:var(--blue);background:rgba(26,86,219,.08)}
.udrop{border:2px dashed var(--bd2);border-radius:10px;padding:28px;text-align:center;cursor:pointer;transition:.2s;background:var(--s2)}
.udrop:hover,.udrop.drag{border-color:var(--blue);background:rgba(26,86,219,.04)}
.udrop h3{font-size:13px;font-weight:700;margin-bottom:6px}
.udrop p{font-size:11px;color:var(--t2)}
.uprog{height:3px;background:var(--s3);border-radius:2px;margin-top:10px;overflow:hidden}
.uprog-fill{height:100%;background:var(--grad);border-radius:2px;width:0;transition:width .3s}
.ures{background:var(--s3);border:1px solid var(--bd);border-radius:7px;padding:10px 12px;font-size:11px;margin-top:10px;display:none;white-space:pre-wrap}

/* ── TREE ── */
#ref-wrap{overflow:auto;padding:16px;min-height:400px}
.rtree-node{display:inline-flex;flex-direction:column;align-items:center;margin:6px}
.rtree-card{background:var(--s1);border:2px solid var(--bd);border-radius:10px;padding:10px 14px;min-width:160px;text-align:center;cursor:pointer;transition:.2s;box-shadow:0 1px 4px rgba(0,0,0,.07)}
.rtree-card:hover{border-color:var(--blue);transform:translateY(-2px);box-shadow:0 4px 10px rgba(26,86,219,.18)}
.rtree-card.hl{border-color:var(--amber);box-shadow:0 0 10px rgba(230,81,0,.3)}
.rtree-children{display:flex;gap:16px;margin-top:12px;position:relative;flex-wrap:wrap;justify-content:center}
.rtree-conn{height:14px;width:2px;background:var(--bd2);margin:0 auto}
#binary-wrap{overflow:auto;padding:16px;min-height:400px}
#bt-inner{transform-origin:top left;transition:transform .2s}
.tnode{display:inline-flex;flex-direction:column;align-items:center}
.tcard{background:var(--s1);border:2px solid var(--bd);border-radius:10px;padding:9px 12px;min-width:130px;text-align:center;cursor:pointer;transition:.2s;position:relative;margin:2px;box-shadow:0 1px 4px rgba(0,0,0,.07)}
.tcard:hover{border-color:var(--blue);transform:translateY(-2px)}
.tcard.hl{border-color:var(--amber);box-shadow:0 0 10px rgba(230,81,0,.3)}
.tc-name{font-size:12px;font-weight:700}
.tc-pv{font-size:10px;color:var(--blue);font-family:'JetBrains Mono',monospace;margin-top:3px;font-weight:700}
.tc-pos{position:absolute;top:3px;right:7px;font-size:9px;font-weight:700}
.tchildren{display:flex;gap:24px;margin-top:0;position:relative;align-items:flex-start;justify-content:center}
.tchildren::before{display:none}
.tconn{height:18px;width:2px;background:var(--bd2);margin:0 auto}
.tchild-lbl{font-size:9px;font-weight:700;text-align:center;margin-bottom:3px;padding:2px 7px;border-radius:4px}
.lbl-L{color:var(--blue);background:rgba(26,86,219,.1)}
.lbl-R{color:var(--amber);background:rgba(230,81,0,.1)}
.empty-slot{background:var(--s2);border:2px dashed var(--bd);border-radius:10px;padding:8px 12px;min-width:110px;text-align:center;opacity:.5}
.empty-slot p{font-size:9px;color:var(--t3)}
.rtree-card2{background:var(--s1);border:2px solid var(--bd);border-radius:9px;padding:8px 12px;min-width:120px;max-width:150px;text-align:center;cursor:pointer;transition:.2s;position:relative;z-index:1;margin-bottom:3px;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.rtree-card2:hover{transform:translateY(-2px)}
.rtree-card2.hl{border-color:var(--amber)!important}

/* ── COMMISSION ── */
.ctype-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--bd)}
.camt{font-size:13px;font-weight:900;color:var(--green);font-family:'JetBrains Mono',monospace}
.comm-detail-header{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px;padding:12px 14px;background:var(--s1);border:1px solid var(--bd);border-radius:10px}
.comm-period-bar{display:flex;align-items:center;gap:7px;flex-wrap:wrap}
.comm-kpi-row{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:12px}
.comm-kpi{background:var(--s1);border:1px solid var(--bd);border-radius:10px;padding:12px 14px;text-align:center}
.comm-kpi-v{font-size:18px;font-weight:900;font-family:'JetBrains Mono',monospace}
.comm-kpi-l{font-size:9px;color:var(--t3);font-weight:700;letter-spacing:.5px;margin-top:2px;text-transform:uppercase}
.rank-row{display:flex;align-items:center;gap:8px;padding:9px 10px;border-bottom:1px solid var(--bd);cursor:pointer;transition:.12s;border-radius:6px}
.rank-row:hover{background:var(--s3)}
.rank-pool-bar{flex:1;height:5px;background:var(--s3);border-radius:3px;overflow:hidden}
.rank-pool-fill{height:100%;border-radius:3px}

/* ── 수당 카드 ── */
.comm-summary-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;margin-bottom:16px}
.comm-card{background:var(--s1);border:1px solid var(--bd);border-radius:10px;padding:14px 16px;position:relative;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05);transition:.15s}
.comm-card:hover{transform:translateY(-2px);box-shadow:0 4px 10px rgba(0,0,0,.1)}
.comm-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.comm-card.c-ref::before{background:linear-gradient(90deg,#1a56db,#42a5f5)}
.comm-card.c-match::before{background:linear-gradient(90deg,#6a1b9a,#ce93d8)}
.comm-card.c-bin::before{background:linear-gradient(90deg,#e65100,#ffb74d)}
.comm-card.c-rank::before{background:linear-gradient(90deg,#c62828,#ef9a9a)}
.comm-card.c-rep::before{background:linear-gradient(90deg,#00695c,#80cbc4)}
.comm-card.c-lotto::before{background:linear-gradient(90deg,#ad1457,#f48fb1)}
.comm-card.c-center::before{background:linear-gradient(90deg,#37474f,#90a4ae)}
.comm-card-lbl{font-size:9px;color:var(--t3);font-weight:700;letter-spacing:.5px;text-transform:uppercase}
.comm-card-amt{font-size:19px;font-weight:900;color:var(--t1);font-family:'JetBrains Mono',monospace;margin:4px 0}
.comm-card-sub{font-size:10px;color:var(--t3)}

/* ── 직급 뱃지 ── */
.rank-badge{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;font-size:9px;font-weight:900;letter-spacing:-.3px;box-shadow:0 2px 5px rgba(0,0,0,.15);flex-shrink:0}
.rank-1스타{background:linear-gradient(135deg,#90caf9,#42a5f5);color:#fff}
.rank-2스타{background:linear-gradient(135deg,#a5d6a7,#66bb6a);color:#fff}
.rank-3스타{background:linear-gradient(135deg,#fff176,#ffd600);color:#333}
.rank-4스타{background:linear-gradient(135deg,#ffcc80,#ffa726);color:#fff}
.rank-5스타{background:linear-gradient(135deg,#ef9a9a,#ef5350);color:#fff}

/* ── MODAL ── */
.mo{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;display:none;align-items:center;justify-content:center}
.mo.open{display:flex}
.mo-box{background:var(--s1);border:1px solid var(--bd);border-radius:14px;padding:22px;width:480px;max-height:82vh;overflow-y:auto;box-shadow:0 8px 28px rgba(0,0,0,.15)}
.mo-hd{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
.mo-title{font-size:15px;font-weight:900}
.mo-close{background:none;border:none;color:var(--t2);cursor:pointer;font-size:19px;line-height:1;padding:2px}
.ir{display:flex;gap:0;margin-bottom:7px;font-size:12px}
.ir-lbl{width:100px;color:var(--t3);flex-shrink:0;font-weight:600}
.ir-val{color:var(--t1);font-weight:600}

/* 모바일 모달 풀화면 */
@media(max-width:768px){
  .mo-box{width:calc(100vw - 32px);max-height:88vh}
}

/* ── MISC ── */
.spin{width:28px;height:28px;border:3px solid var(--bd);border-top-color:var(--blue);border-radius:50%;animation:sp .7s linear infinite;margin:36px auto}
@keyframes sp{to{transform:rotate(360deg)}}
.empty-msg{text-align:center;color:var(--t2);font-size:12px;padding:36px;line-height:1.8}
.srch{background:var(--s1);border:1px solid var(--bd);color:var(--t1);padding:6px 11px;border-radius:7px;font-size:11px;font-family:inherit;outline:none;width:190px;transition:.15s}
.srch:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(26,86,219,.1)}
.divider{height:1px;background:var(--bd);margin:12px 0}
.rt-wrap{overflow:auto;padding:16px}

/* ── 말풍선 tooltip ── */
#tt-box{position:fixed;z-index:99999;background:rgba(20,20,30,.93);color:#fff;padding:9px 13px;border-radius:9px;font-size:11px;line-height:1.65;max-width:280px;pointer-events:none;box-shadow:0 4px 18px rgba(0,0,0,.35);display:none;border:1px solid rgba(255,255,255,.1)}
#tt-box hr{border:0;border-top:1px solid rgba(255,255,255,.18);margin:5px 0}
.tt-tag{transition:opacity .1s}
.tt-tag:hover{opacity:.8}
</style>
</head>
<body>

<!-- 사이드바 오버레이 (모바일) -->
<div id="sidebar-overlay" onclick="closeSidebar()"></div>

<div id="app">

<!-- ════════ SIDEBAR ════════ -->
<div id="sidebar">
  <div class="sb-logo">
    <div class="badge">MLM SYSTEM v3</div>
    <h2>리셀라 관리</h2>
  </div>
  <div class="sb-sec">
    <div class="sb-lbl">대시보드</div>
    <div class="sb-item on" data-panel="dash"><span class="ico">📊</span>현황 요약 (월별)</div>
    <div class="sb-item" data-panel="dash-yearly"><span class="ico">📅</span>현황 요약 (연도별)</div>
  </div>
  <div class="sb-sec">
    <div class="sb-lbl">데이터</div>
    <div class="sb-item" data-panel="upload"><span class="ico">📤</span>파일 업로드</div>
    <div class="sb-item" data-panel="members"><span class="ico">👥</span>회원 목록</div>
    <div class="sb-item" data-panel="honor"><span class="ico">🏅</span>인정자격 회원</div>
    <div class="sb-item" data-panel="sales"><span class="ico">🛒</span>매출·주문</div>
  </div>
  <div class="sb-sec">
    <div class="sb-lbl">조직도</div>
    <div class="sb-item" data-panel="binary"><span class="ico">🌳</span>바이너리 레그</div>
    <div class="sb-item" data-panel="referral"><span class="ico">🔗</span>추천 레그</div>
    <div class="sb-item" data-panel="pv"><span class="ico">📈</span>PV 현황</div>
  </div>
  <div class="sb-sec">
    <div class="sb-lbl">수당 관리</div>
    <div class="sb-item" data-panel="comm"><span class="ico">💰</span>수당 계산 (통합)</div>
    <div class="sb-item" data-panel="comm-honor"><span class="ico">🏅</span>인정자격회원 수당</div>
    <div class="sb-item" data-panel="comm-detail"><span class="ico">👤</span>개인 수당 상세</div>
    <div class="sb-item" data-panel="comm-weekly"><span class="ico">📅</span>주지급 수당</div>
    <div class="sb-item" data-panel="comm-monthly"><span class="ico">🗓️</span>월지급 수당</div>
    <div class="sb-item" data-panel="comm-ref"><span class="ico">💵</span>추천수당</div>
    <div class="sb-item" data-panel="comm-match"><span class="ico">🎯</span>추천매칭수당</div>
    <div class="sb-item" data-panel="comm-bin"><span class="ico">⚖️</span>바이너리수당</div>
    <div class="sb-item" data-panel="comm-rank"><span class="ico">👑</span>직급수당</div>
    <!-- ★ 직추재구매수당 메뉴 제거 -->
    <div class="sb-item" data-panel="comm-lotto"><span class="ico">🎰</span>로또보너스</div>
    <div class="sb-item" data-panel="comm-center"><span class="ico">🏢</span>센터수당</div>
  </div>
  <div class="sb-foot">
    <button class="btn-lo" onclick="location.href='logout.php'">🚪 로그아웃</button>
  </div>
</div>

<!-- ════════ MAIN ════════ -->
<div id="main">
  <div id="topbar">
    <!-- ★ 햄버거 버튼 (모바일에서만 표시) -->
    <button id="menu-toggle" onclick="toggleSidebar()" aria-label="메뉴 열기">☰</button>
    <span id="topbar-title" style="font-size:13px;font-weight:700">현황 요약</span>
    <div class="topbar-r">
      <span style="font-size:10px;color:var(--t3)">기준 월</span>
      <input type="month" id="period" value="<?= date('Y-m') ?>" onchange="onPeriodChange()">
      <button class="btn bo" onclick="refreshPanel()" style="padding:5px 10px;font-size:12px">🔄</button>
    </div>
  </div>
  <div id="content">
    <?php
    // ★ panel-comm-repurchase 제거
    $panels = [
      'panel-dash','panel-dash-yearly',
      'panel-upload','panel-members','panel-honor','panel-sales',
      'panel-binary','panel-referral','panel-pv',
      'panel-comm','panel-comm-honor','panel-comm-detail',
      'panel-comm-weekly','panel-comm-monthly',
      'panel-comm-ref','panel-comm-match',
      'panel-comm-bin','panel-comm-rank',
      'panel-comm-lotto','panel-comm-center',
    ];
    foreach ($panels as $p) {
      $path = __DIR__ . "/panels/{$p}.php";
      if (file_exists($path)) include $path;
    }
    ?>
  </div>
</div>
</div>

<!-- ════════ 회원 상세 모달 ════════ -->
<div class="mo" id="mo">
  <div class="mo-box">
    <div class="mo-hd">
      <div class="mo-title" id="mo-name">회원 상세</div>
      <button class="mo-close" onclick="closeMo()">✕</button>
    </div>
    <div id="mo-body"></div>
  </div>
</div>

<script src="js/globals.js"></script>
<script>
// ══════════════════════════════════════════
//  네비게이션
// ══════════════════════════════════════════
const PANEL_TITLES = {
  'dash':'현황 요약 (월별)', 'dash-yearly':'현황 요약 (연도별)',
  'upload':'파일 업로드', 'members':'회원 목록', 'honor':'인정자격 회원', 'sales':'매출·주문',
  'binary':'바이너리 레그', 'referral':'추천 레그', 'pv':'PV 현황',
  'comm':'수당 계산 (통합)', 'comm-detail':'개인 수당 상세',
  'comm-weekly':'주지급 수당', 'comm-monthly':'월지급 수당',
  'comm-ref':'추천수당', 'comm-match':'추천매칭수당',
  'comm-bin':'바이너리수당', 'comm-rank':'직급수당',
  // ★ comm-repurchase 제거
  'comm-lotto':'로또보너스', 'comm-center':'센터수당'
};

document.querySelectorAll('.sb-item').forEach(el => {
  el.addEventListener('click', () => {
    const name = el.dataset.panel;
    document.querySelectorAll('.sb-item').forEach(x => x.classList.remove('on'));
    document.querySelectorAll('.panel').forEach(x => x.classList.remove('on'));
    el.classList.add('on');
    const panel = $('p-'+name);
    if (panel) panel.classList.add('on');
    $('topbar-title').textContent = PANEL_TITLES[name] || name;
    onPanelOpen(name);
    // ★ 모바일: 메뉴 선택 후 사이드바 자동 닫기
    closeSidebar();
  });
});

function onPanelOpen(name) {
  if (name==='dash')         loadDash();
  if (name==='members')      loadMembersTable && loadMembersTable();
  if (name==='honor')        loadHonorPanel && loadHonorPanel();
  if (name==='comm-honor')   loadHonorCommPanel && loadHonorCommPanel();
  if (name==='sales')        loadSalesTable && loadSalesTable();
  if (name==='binary')       loadBinaryTree && loadBinaryTree();
  if (name==='referral')     loadReferralTree && loadReferralTree();
  if (name==='pv')           loadPV && loadPV();
  if (name==='upload')       loadUploadHistory && loadUploadHistory();
  if (name==='comm-center')  { ensureData(period()).then(()=>loadCenterMgr()); }
}

function refreshPanel() {
  const active = document.querySelector('.sb-item.on');
  if (active) active.click();
}
function onPeriodChange() {
  const active = document.querySelector('.sb-item.on');
  if (!active) return;
  const name = active.dataset.panel;
  if (['dash','pv','members','binary','referral'].includes(name)) onPanelOpen(name);
}

// ══════════════════════════════════════════
//  ★ 햄버거 메뉴 열기/닫기
// ══════════════════════════════════════════
function toggleSidebar() {
  const sb  = document.getElementById('sidebar');
  const ov  = document.getElementById('sidebar-overlay');
  const btn = document.getElementById('menu-toggle');
  const isOpen = sb.classList.contains('open');
  if (isOpen) {
    sb.classList.remove('open');
    ov.classList.remove('open');
    btn.textContent = '☰';
    btn.setAttribute('aria-label', '메뉴 열기');
    document.body.style.overflow = '';
  } else {
    sb.classList.add('open');
    ov.classList.add('open');
    btn.textContent = '✕';
    btn.setAttribute('aria-label', '메뉴 닫기');
    document.body.style.overflow = 'hidden'; // 스크롤 잠금
  }
}
function closeSidebar() {
  const sb  = document.getElementById('sidebar');
  const ov  = document.getElementById('sidebar-overlay');
  const btn = document.getElementById('menu-toggle');
  if (!sb.classList.contains('open')) return;
  sb.classList.remove('open');
  ov.classList.remove('open');
  btn.textContent = '☰';
  btn.setAttribute('aria-label', '메뉴 열기');
  document.body.style.overflow = '';
}

// ESC 키로 사이드바 닫기
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeSidebar();
});

// ── 모달 ──
function openMo(no) {
  const m   = S.members[no] || {};
  const pv  = S.pvMap[no]  || 0;
  const lp  = S.legPV[no]  || {L:0, R:0};
  const rank  = calcRankJs(lp);
  const grade = getEffectiveGrade(no, pv);
  $('mo-name').textContent = m.name || no;

  let carryL = 0, carryR = 0, carryWeek = '';
  if (S.binCarry && Object.keys(S.binCarry).length > 0) {
    const weeks = Object.keys(S.binCarry).sort();
    for (let i = weeks.length - 1; i >= 0; i--) {
      const wk = weeks[i];
      if (S.binCarry[wk] && S.binCarry[wk][no]) {
        carryL = S.binCarry[wk][no].L || 0;
        carryR = S.binCarry[wk][no].R || 0;
        carryWeek = wk;
        break;
      }
    }
  }
  const hasCarry = (carryL > 0 || carryR > 0) && carryWeek;

  const legLHtml = `<span class="mono pl">${fmt(lp.L)}</span>`
    + (hasCarry ? ` <span style="font-size:10px;color:var(--t3)">(잔여누적: <b class="pl">${fmt(carryL)}</b>)</span>` : '');
  const legRHtml = `<span class="mono pr">${fmt(lp.R)}</span>`
    + (hasCarry ? ` <span style="font-size:10px;color:var(--t3)">(잔여누적: <b class="pr">${fmt(carryR)}</b>)</span>` : '');

  const fields = [
    ['이름',    m.name || no],
    ['ID',      m.login_id || ''],
    ['등급',    `<span class="gb g${grade}">${grade}</span>`],
    ['이번달 PV', `<span class="mono" style="color:var(--blue);font-weight:700">${fmt(pv)}</span>`],
    ['L 레그 PV', legLHtml],
    ['R 레그 PV', legRHtml],
    ['달성 직급', rank !== '미달성' ? `<span class="gb g${rank}">${rank}</span>` : '-'],
    ['추천인',  `${m.referrer_name||''} ${m.referrer_no ? '('+( (S.members[m.referrer_no]||{}).login_id || m.referrer_no )+')' : ''}`],
    ['후원인',  `${m.sponsor_name||''} ${m.sponsor_no ? '('+( (S.members[m.sponsor_no]||{}).login_id || m.sponsor_no )+')' : ''}`],
    ['위치',    `<span class="${m.position==='L'?'pl':'pr'}">${m.position||'-'}</span>`],
    ['가입일',  (m.join_date||'').substring(0,10)],
    ['센터',    m.center || ''],
    ['전화',    m.phone || ''],
    ['은행',    [m.bank_name, m.account_holder, m.account_no].filter(Boolean).join(' ')],
  ];
  $('mo-body').innerHTML = fields.map(([k,v])=>
    `<div class="ir"><div class="ir-lbl">${k}</div><div class="ir-val">${v||'-'}</div></div>`
  ).join('')
  + (hasCarry ? `<div style="margin-top:8px;padding:8px 12px;background:rgba(26,86,219,.06);border:1px solid rgba(26,86,219,.15);border-radius:8px;font-size:10px;color:var(--t3)">
    📌 잔여 누적은 <b style="color:var(--blue)">${carryWeek}</b> 주 마감 후 실적 차감 기준입니다.
  </div>` : '');
  $('mo').classList.add('open');
}
function closeMo() { $('mo').classList.remove('open'); }
$('mo').addEventListener('click', e => { if (e.target === $('mo')) closeMo(); });

// ── 초기 로드 ──
window.addEventListener('DOMContentLoaded', () => {
  if (typeof loadUploadHistory === 'function') loadUploadHistory();
  loadDash();
  initPanDrag('binary-wrap');
  initPanDrag('ref-wrap');
});

// ── 드래그 스크롤 ──
function initPanDrag(id) {
  const el = document.getElementById(id);
  if (!el) return;
  let isDragging = false, startX = 0, startY = 0, scrollLeft = 0, scrollTop = 0;
  el.addEventListener('mousedown', function(e) {
    if (e.target.closest('button, input, select, a')) return;
    isDragging = true;
    startX = e.clientX; startY = e.clientY;
    scrollLeft = el.scrollLeft; scrollTop = el.scrollTop;
    el.style.cursor = 'grabbing';
    el.style.userSelect = 'none';
    e.preventDefault();
  });
  window.addEventListener('mousemove', function(e) {
    if (!isDragging) return;
    el.scrollLeft = scrollLeft - (e.clientX - startX);
    el.scrollTop  = scrollTop  - (e.clientY - startY);
  });
  window.addEventListener('mouseup', function() {
    if (!isDragging) return;
    isDragging = false;
    el.style.cursor = 'grab';
    el.style.userSelect = '';
  });
  el.style.cursor = 'grab';
}
</script>

<!-- ── 말풍선 tooltip ── -->
<div id="tt-box"></div>
<script>
(function() {
  const box = document.getElementById('tt-box');
  window.ttShow = function(el, html) {
    box.innerHTML = html;
    box.style.display = 'block';
    _positionTt(el);
  };
  window.ttHide = function() { box.style.display = 'none'; };
  function _positionTt(el) {
    const r  = el.getBoundingClientRect();
    const bw = box.offsetWidth  || 240;
    const bh = box.offsetHeight || 80;
    const vw = window.innerWidth;
    const gap = 8;
    let left = r.left + r.width / 2 - bw / 2;
    let top  = r.top - bh - gap;
    if (left < 8) left = 8;
    if (left + bw > vw - 8) left = vw - bw - 8;
    if (top < 8) top = r.bottom + gap;
    box.style.left = left + 'px';
    box.style.top  = top  + 'px';
  }
  window.addEventListener('scroll', () => { box.style.display='none'; }, true);
  window.addEventListener('resize', () => { box.style.display='none'; });
})();
</script>
</body>
</html>
