<?php
// api/data.php  ─ 회원/매출 데이터 조회
// PHP 5.6+ 완전 호환 (arrow function, str_starts_with, ?? 제거)
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

require_once dirname(__DIR__) . '/config.php';
requireLogin();

$action = isset($_GET['action']) ? $_GET['action'] : 'members';
$period = isset($_GET['period']) ? $_GET['period'] : date('Y-m');

// str_starts_with 폴리필
function strStartsWith($str, $prefix) {
    return strncmp($str, $prefix, strlen($prefix)) === 0;
}

// ── 매출 중복 키 함수 (전역 — 모든 action에서 공유) ──
function _sKey($s) {
    $d  = isset($s['ddm_id'])      ? strval($s['ddm_id'])      : '';
    $n  = isset($s['member_name']) ? strval($s['member_name']) : '';
    $dt = isset($s['order_date'])  ? strval($s['order_date'])  : '';
    $a  = isset($s['amount'])      ? strval(intval($s['amount'])) : '0';
    $p  = isset($s['pv'])          ? strval(intval($s['pv']))     : '0';
    return $d.'|'.$n.'|'.$dt.'|'.$a.'|'.$p;
}

// ── saved_sales + sales 병합 헬퍼 ──
function mergeSales($salesBatch, $salesSaved) {
    $savedKeys = array();
    foreach ($salesSaved as $sv) { $savedKeys[_sKey($sv)] = true; }
    $merged = $salesSaved;
    foreach ($salesBatch as $sb) {
        if (!isset($savedKeys[_sKey($sb)])) $merged[] = $sb;
    }
    return $merged;
}

// ── saved_sales 로드 헬퍼 ──
function loadSalesAll() {
    $salesBatch = readJson(FILE_SALES);
    $salesSaved = file_exists(FILE_SAVED_SALES) ? readJson(FILE_SAVED_SALES) : array();
    // saved에 _saved_permanent 마킹
    foreach ($salesSaved as &$sv) { $sv['_saved_permanent'] = true; }
    unset($sv);
    return mergeSales($salesBatch, $salesSaved);
}

// ── 회원 목록 ──
if ($action === 'members') {
    $members   = readJson(FILE_MEMBERS);
    $allSales  = loadSalesAll();  // 전체 매출 (누적 PV 계산용)

    $curPeriod = $period ? $period : date('Y-m');
    $idMap = array(); $nameMap = array();
    foreach ($members as $m) {
        if (!empty($m['login_id'])) $idMap[$m['login_id']] = $m['member_no'];
        if (!empty($m['name']))     $nameMap[$m['name']]   = $m['member_no'];
    }

    // ── 수정#1: 누적 PV 집계 (전체 매출 기준, 기간 무관)
    $cumPvMap  = array();  // 누적 PV
    $monthPvMap = array(); // 선택 월 PV
    foreach ($allSales as $s) {
        $did  = isset($s['ddm_id'])      ? $s['ddm_id']      : '';
        $name = isset($s['member_name']) ? $s['member_name'] : '';
        $no   = isset($idMap[$did])    ? $idMap[$did]
              : (isset($nameMap[$name]) ? $nameMap[$name] : null);
        if (!$no) continue;
        $pv = intval(isset($s['pv']) ? $s['pv'] : 0);
        $cumPvMap[$no] = (isset($cumPvMap[$no]) ? $cumPvMap[$no] : 0) + $pv;
        $od = isset($s['order_date']) ? $s['order_date'] : '';
        if (strStartsWith($od, $curPeriod)) {
            $monthPvMap[$no] = (isset($monthPvMap[$no]) ? $monthPvMap[$no] : 0) + $pv;
        }
    }

    // 인정자격 맵
    $honorList = readJson(DATA_DIR . 'honor_members.json');
    $honorMap  = array();
    foreach ($honorList as $h) {
        if (!empty($h['member_no'])) $honorMap[$h['member_no']] = $h;
    }

    $result = array();
    $membersChanged = false;
    foreach ($members as &$m) {
        $no        = $m['member_no'];
        $cumPv     = isset($cumPvMap[$no])   ? $cumPvMap[$no]   : 0;
        $monthPv   = isset($monthPvMap[$no]) ? $monthPvMap[$no] : 0;

        // 누적 PV 기반 등급 계산 → 실제 누적 매출로 항상 재계산 (higherGrade 미사용 — 데이터 정합성 보장)
        $cumGrade  = calcGrade($cumPv);
        $savedMax  = isset($m['max_grade']) ? $m['max_grade'] : '미달성';
        $newMax    = $cumGrade;  // 실제 누적 PV 기반 등급만 사용
        if ($newMax !== $savedMax) {
            $m['max_grade']   = $newMax;
            $m['cum_pv']      = $cumPv;
            $membersChanged   = true;
        }

        // 인정등급 (수정#2)
        $honorGrade  = '';
        $honorActive = false;
        $honorNote   = '';
        if (isset($honorMap[$no])) {
            $h = $honorMap[$no];
            $honorGrade  = isset($h['grade'])  ? $h['grade']  : '';
            $honorActive = !empty($h['active']);
            $honorNote   = isset($h['note'])   ? $h['note']   : '';
        }

        // 실효 등급: 누적max vs 월PV vs 인정등급 중 최고
        $pvGrade     = calcGrade($monthPv);
        $effectGrade = higherGrade($newMax, $pvGrade);
        if ($honorGrade) $effectGrade = higherGrade($effectGrade, $honorGrade);

        // 유지 상태 판단 (수정#3): 이번달 PV로 자격 유지 여부
        $maintainStatus = '미유지';
        if ($monthPv >= 800000)      $maintainStatus = '플래티넘유지';
        elseif ($monthPv >= 560000)  $maintainStatus = '골드유지';
        elseif ($monthPv >= 320000)  $maintainStatus = '플러스유지';
        elseif ($monthPv >= 100000)  $maintainStatus = '베이직유지';
        elseif ($monthPv > 0)        $maintainStatus = 'PV있음';

        $m['grade']           = $effectGrade;
        $m['month_pv']        = $monthPv;
        $m['cum_pv']          = $cumPv;
        $m['honor_grade']     = $honorGrade;   // 수정#2: 인정등급 별도 항목
        $m['honor_active']    = $honorActive;
        $m['honor_note']      = $honorNote;
        $m['maintain_status'] = $maintainStatus; // 수정#3: 유지상태
        $result[] = $m;
    }
    unset($m);

    // 누적 PV로 max_grade 갱신된 경우 members.json 저장
    if ($membersChanged) {
        writeJson(FILE_MEMBERS, array_values($result));
    }

    jsonOut(array('data' => $result, 'total' => count($result)));
}

// ── 매출 목록 ──
if ($action === 'sales') {
    $sales = loadSalesAll();

    if ($period) {
        $filtered = array();
        foreach ($sales as $globalIdx => $s) {
            $od = isset($s['order_date']) ? $s['order_date'] : '';
            if (strStartsWith($od, $period)) {
                $s['_global_idx'] = $globalIdx;
                $filtered[] = $s;
            }
        }
        $sales = array_values($filtered);
    } else {
        foreach ($sales as $globalIdx => &$s) {
            $s['_global_idx'] = $globalIdx;
        }
        unset($s);
        $sales = array_values($sales);
    }
    jsonOut(array('data' => $sales, 'total' => count($sales), 'period' => $period));
}

// ── PV 현황 ──
if ($action === 'pv') {
    $sales   = loadSalesAll();
    $members = readJson(FILE_MEMBERS);

    $idMap = array(); $nameMap = array(); $memberIdx = array();
    foreach ($members as $m) {
        if (!empty($m['login_id'])) $idMap[$m['login_id']] = $m['member_no'];
        if (!empty($m['name']))     $nameMap[$m['name']]   = $m['member_no'];
        $memberIdx[$m['member_no']] = $m;
    }

    $pvMap = array();
    foreach ($sales as $s) {
        $od = isset($s['order_date']) ? $s['order_date'] : '';
        if (!strStartsWith($od, $period)) continue;
        $did  = isset($s['ddm_id'])      ? $s['ddm_id']      : '';
        $name = isset($s['member_name']) ? $s['member_name'] : '';
        $no   = isset($idMap[$did])    ? $idMap[$did]
              : (isset($nameMap[$name]) ? $nameMap[$name] : null);
        if (!$no) continue;
        $pvMap[$no] = (isset($pvMap[$no]) ? $pvMap[$no] : 0) + intval(isset($s['pv']) ? $s['pv'] : 0);
    }

    $rows = array();
    foreach ($members as $m) {
        $no      = $m['member_no'];
        $monthPv = isset($pvMap[$no]) ? $pvMap[$no] : 0;
        $pvGrade = calcGrade($monthPv);
        $maxGrade = isset($m['max_grade']) ? $m['max_grade'] : '미달성';
        $grade   = higherGrade($maxGrade, $pvGrade);

        $rows[] = array(
            'member_no'     => $no,
            'name'          => isset($m['name'])          ? $m['name']          : '',
            'login_id'      => isset($m['login_id'])       ? $m['login_id']      : '',
            'grade'         => $grade,
            'personal_pv'   => $monthPv,
            'sponsor_no'    => isset($m['sponsor_no'])     ? $m['sponsor_no']    : '',
            'sponsor_name'  => isset($m['sponsor_name'])   ? $m['sponsor_name']  : '',
            'referrer_no'   => isset($m['referrer_no'])    ? $m['referrer_no']   : '',
            'referrer_name' => isset($m['referrer_name'])  ? $m['referrer_name'] : '',
            'position'      => isset($m['position'])       ? $m['position']      : '',
            'join_date'     => isset($m['join_date'])      ? $m['join_date']     : '',
        );
    }
    usort($rows, function($a, $b) { return $b['personal_pv'] - $a['personal_pv']; });
    jsonOut(array('data' => $rows, 'pv_map' => $pvMap, 'period' => $period));
}

// ── 통계 ──
if ($action === 'stats') {
    $members = readJson(FILE_MEMBERS);
    $sales   = loadSalesAll();
    $thisMon = array();
    foreach ($sales as $s) {
        $od = isset($s['order_date']) ? $s['order_date'] : '';
        if (strStartsWith($od, $period)) $thisMon[] = $s;
    }
    $thisMon = array_values($thisMon);
    $totalPv  = 0;
    $totalAmt = 0;
    foreach ($thisMon as $s) {
        $totalPv  += intval(isset($s['pv'])     ? $s['pv']     : 0);
        $totalAmt += intval(isset($s['amount']) ? $s['amount'] : 0);
    }
    jsonOut(array(
        'members' => count($members),
        'orders'  => count($thisMon),
        'pv'      => $totalPv,
        'amount'  => $totalAmt,
        'period'  => $period,
    ));
}

// ── 배치 목록 ──
if ($action === 'batches') {
    $sales   = readJson(FILE_SALES);
    $batches = array();
    foreach ($sales as $s) {
        $bid = isset($s['batch_id']) ? $s['batch_id'] : 'unknown';
        if (!isset($batches[$bid])) {
            $batches[$bid] = array('batch_id'=>$bid,'count'=>0,'total_pv'=>0,'total_amount'=>0,'dates'=>array());
        }
        $batches[$bid]['count']++;
        $batches[$bid]['total_pv']     += intval(isset($s['pv'])     ? $s['pv']     : 0);
        $batches[$bid]['total_amount'] += intval(isset($s['amount']) ? $s['amount'] : 0);
        $od = isset($s['order_date']) ? $s['order_date'] : '';
        if ($od) $batches[$bid]['dates'][] = $od;
    }
    foreach ($batches as $bid => $b) {
        $batches[$bid]['date_from'] = !empty($b['dates']) ? min($b['dates']) : '';
        $batches[$bid]['date_to']   = !empty($b['dates']) ? max($b['dates']) : '';
        unset($batches[$bid]['dates']);
    }
    $bArr = array_values($batches);
    usort($bArr, function($a, $b) { return strcmp($b['batch_id'], $a['batch_id']); });
    jsonOut(array('data' => $bArr));
}

// ── 설정 조회 ──
if ($action === 'config') {
    $centerIds = json_decode(CENTER_MEMBER_IDS, true);
    if (!is_array($centerIds)) $centerIds = array();
    jsonOut(array('center_ids' => $centerIds));
}

jsonOut(array('error' => 'unknown action'), 400);
