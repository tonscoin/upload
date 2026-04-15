<?php
// api/calc.php  ─ 수당 계산 엔진 (PHP 5.6+ 완전 호환)
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

require_once dirname(__DIR__) . '/config.php';
requireLogin();

$action = isset($_GET['action']) ? $_GET['action'] : 'preview';
$period = isset($_GET['period']) ? $_GET['period'] : date('Y-m');

if ($action === 'history') {
    $file = FILE_CALC . $period . '.json';
    jsonOut(readJson($file));
}

$result = runCalc($period);
if ($action === 'save') {
    writeJson(FILE_CALC . $period . '.json', $result);
    $result['saved'] = true;
}
jsonOut($result);

// ═══════════════════════════════════════════
function strStartsWith2($str, $prefix) {
    return strncmp($str, $prefix, strlen($prefix)) === 0;
}

function runCalc($period) {
    $members = readJson(FILE_MEMBERS);
    // saved_sales.json(영구저장) + sales.json(배치) 병합
    $salesBatch  = readJson(FILE_SALES);
    $salesSaved  = file_exists(FILE_SAVED_SALES) ? readJson(FILE_SAVED_SALES) : array();
    $svKeys      = array();
    foreach ($salesSaved as $sv) {
        $k = (isset($sv['ddm_id'])?strval($sv['ddm_id']):'').'|'.
             (isset($sv['member_name'])?strval($sv['member_name']):'').'|'.
             (isset($sv['order_date'])?strval($sv['order_date']):'').'|'.
             strval(intval(isset($sv['amount'])?$sv['amount']:0)).'|'.
             strval(intval(isset($sv['pv'])?$sv['pv']:0));
        $svKeys[$k] = true;
    }
    $sales = $salesSaved;
    foreach ($salesBatch as $sb) {
        $k = (isset($sb['ddm_id'])?strval($sb['ddm_id']):'').'|'.
             (isset($sb['member_name'])?strval($sb['member_name']):'').'|'.
             (isset($sb['order_date'])?strval($sb['order_date']):'').'|'.
             strval(intval(isset($sb['amount'])?$sb['amount']:0)).'|'.
             strval(intval(isset($sb['pv'])?$sb['pv']:0));
        if (!isset($svKeys[$k])) $sales[] = $sb;
    }

    if (empty($members)) return array('error' => '회원 데이터 없음. 먼저 파일을 업로드하세요.');

    // ── 인덱스 구성 ──
    $mIdx = array(); $idMap = array(); $nameMap = array();
    foreach ($members as $m) {
        $mIdx[$m['member_no']] = $m;
        if (!empty($m['login_id'])) $idMap[$m['login_id']]  = $m['member_no'];
        if (!empty($m['name']))     $nameMap[$m['name']]    = $m['member_no'];
    }

    // ── period 파싱 ──
    $parts   = explode('-', $period);
    $periodY = intval($parts[0]);
    $periodM = intval($parts[1]);
    $prevM   = $periodM === 1 ? 12 : $periodM - 1;
    $prevY   = $periodM === 1 ? $periodY - 1 : $periodY;
    $prevPeriod = sprintf('%04d-%02d', $prevY, $prevM);

    // ── 직전달 회원별 PV ──
    $prevPvMap = array();
    foreach ($sales as $s) {
        $od = isset($s['order_date']) ? $s['order_date'] : '';
        if (!strStartsWith2($od, $prevPeriod)) continue;
        $no = _findNo($s, $idMap, $nameMap);
        if (!$no) continue;
        $prevPvMap[$no] = (isset($prevPvMap[$no]) ? $prevPvMap[$no] : 0) + intval(isset($s['pv']) ? $s['pv'] : 0);
    }

    // ── 조회달 회원별 PV 및 주문 ──
    $pvMap = array(); $ordersByMember = array(); $allOrdersThisMonth = array();
    foreach ($sales as $s) {
        $od = isset($s['order_date']) ? $s['order_date'] : '';
        if (!strStartsWith2($od, $period)) continue;
        $no = _findNo($s, $idMap, $nameMap);
        if (!$no) continue;
        $pv = intval(isset($s['pv']) ? $s['pv'] : 0);
        $pvMap[$no] = (isset($pvMap[$no]) ? $pvMap[$no] : 0) + $pv;
        if (!isset($ordersByMember[$no])) $ordersByMember[$no] = array();
        $ordersByMember[$no][] = $s;
        $merged = $s;
        $merged['member_no'] = $no;
        $allOrdersThisMonth[] = $merged;
    }

    // ── 인정자격 회원 로드 ──
    $honorFile = DATA_DIR . 'honor_members.json';
    $honorList = file_exists($honorFile) ? readJson($honorFile) : array();
    $honorMap  = array();
    foreach ($honorList as $h) {
        if (!empty($h['member_no'])) {
            $honorMap[$h['member_no']] = $h;
            // 수당 자격 부여는 active=true 인 경우만
            if (!empty($h['active']) && !isset($qualified[$h['member_no']])) {
                $qualified[$h['member_no']] = 'honor';
            }
        }
    }

    // ── 자격 판단: 직전달 또는 당월 10만PV 이상 ──
    if (!isset($qualified)) $qualified = array();
    // qualDate: 회원별 누적 PV가 처음 10만 이상 된 날짜
    $qualDateMap = array(); // { member_no => 'YYYY-MM-DD' }
    $cumPvTemp = array();
    // 전체 매출 날짜순 정렬 후 누적
    $allSortedSales = $sales;
    usort($allSortedSales, function($a, $b) {
        $da = isset($a['order_date']) ? $a['order_date'] : '';
        $db = isset($b['order_date']) ? $b['order_date'] : '';
        return strcmp($da, $db);
    });
    foreach ($allSortedSales as $s) {
        $od = isset($s['order_date']) ? $s['order_date'] : '';
        $no = _findNo($s, $idMap, $nameMap);
        if (!$no || !$od) continue;
        $pv = intval(isset($s['pv']) ? $s['pv'] : 0);
        $cumPvTemp[$no] = (isset($cumPvTemp[$no]) ? $cumPvTemp[$no] : 0) + $pv;
        if ($cumPvTemp[$no] >= 100000 && !isset($qualDateMap[$no])) {
            $qualDateMap[$no] = $od;
        }
    }
    // 직전달 10만PV 이상이면 자격 부여
    foreach ($prevPvMap as $no => $pv) {
        if ($pv >= 100000 && !isset($qualified[$no])) {
            $qualified[$no] = 'prev';
        }
    }
    // 당월 10만PV 이상이면 자격 부여
    foreach ($pvMap as $no => $pv) {
        if ($pv >= 100000 && !isset($qualified[$no])) {
            $qualified[$no] = 'curr';
        }
    }

    // ── 등급 산정 (인정등급은 active 무관하게 항상 반영) ──
    $gradeMap = array();
    foreach ($mIdx as $no => $m) {
        $maxGrade  = isset($m['max_grade']) ? $m['max_grade'] : (isset($m['grade']) ? $m['grade'] : '미달성');
        $monthPv   = isset($pvMap[$no]) ? $pvMap[$no] : 0;
        $pvGrade   = calcGrade($monthPv);
        // 인정등급: active 여부와 무관하게 등급 계산에 항상 반영
        $honorGrade = isset($honorMap[$no]) ? (isset($honorMap[$no]['grade']) ? $honorMap[$no]['grade'] : '미달성') : '미달성';
        $g = higherGrade($maxGrade, $pvGrade);
        $g = higherGrade($g, $honorGrade);
        $gradeMap[$no] = $g;
    }

    // ── 추천 자식 맵 ──
    $refChildren = array();
    foreach ($mIdx as $no => $m) {
        $ref = isset($m['referrer_no']) ? $m['referrer_no'] : null;
        if ($ref && isset($mIdx[$ref])) {
            if (!isset($refChildren[$ref])) $refChildren[$ref] = array();
            $refChildren[$ref][] = $no;
        }
    }

    // ── 바이너리 레그 PV 구성 (qualDate 적용) ──
    // 각 매출 건별로: 상위 회원의 qualDate 이후 매출만 레그에 반영
    $legPV = array();
    foreach ($allOrdersThisMonth as $s) {
        $no  = isset($s['member_no']) ? $s['member_no'] : null;
        if (!$no) continue;
        $pv  = intval(isset($s['pv']) ? $s['pv'] : 0);
        if ($pv <= 0) continue;
        $saleDate = isset($s['order_date']) ? $s['order_date'] : '';
        $cur = isset($mIdx[$no]['sponsor_no']) ? $mIdx[$no]['sponsor_no'] : null;
        $pos = isset($mIdx[$no]['position'])   ? $mIdx[$no]['position']   : 'L';
        while ($cur && isset($mIdx[$cur])) {
            // 상위 회원이 자격자이고, 매출일 >= 상위 회원의 qualDate인 경우만 반영
            if (isset($qualified[$cur])) {
                $qualDate = isset($qualDateMap[$cur]) ? $qualDateMap[$cur] : '';
                if ($qualDate === '' || $saleDate >= $qualDate) {
                    if (!isset($legPV[$cur])) $legPV[$cur] = array('L' => 0, 'R' => 0);
                    $legPV[$cur][$pos] += $pv;
                }
            }
            $pos = isset($mIdx[$cur]['position'])   ? $mIdx[$cur]['position']   : 'L';
            $cur = isset($mIdx[$cur]['sponsor_no']) ? $mIdx[$cur]['sponsor_no'] : null;
        }
    }

    $comms      = array();
    $BINARY_CAP = array('베이직'=>500000, '플러스'=>1500000, '골드'=>3000000, '플래티넘'=>10000000);
    $DEPTH_MAX  = array('베이직'=>3, '플러스'=>5, '골드'=>8, '플래티넘'=>12);

    // 01 추천보너스 10% (qualDate 이후 구매건만 적용)
    foreach ($ordersByMember as $buyerNo => $orders) {
        $refNo = isset($mIdx[$buyerNo]['referrer_no']) ? $mIdx[$buyerNo]['referrer_no'] : null;
        if (!$refNo || !isset($qualified[$refNo])) continue;
        $refQualDate = isset($qualDateMap[$refNo]) ? $qualDateMap[$refNo] : '';
        foreach ($orders as $o) {
            $saleDate = isset($o['order_date']) ? $o['order_date'] : '';
            if ($refQualDate !== '' && $saleDate < $refQualDate) continue;
            $pv  = intval(isset($o['pv']) ? $o['pv'] : 0);
            $amt = round($pv * 0.10);
            if ($amt > 0) _addComm($comms, $refNo, isset($mIdx[$refNo]['name']) ? $mIdx[$refNo]['name'] : '', '추천수당', $pv, 10, $amt, "from_buyer:{$buyerNo}");
        }
    }

    // 02 추천매칭보너스 (qualDate 이후 구매건만 적용)
    foreach ($qualified as $no => $_) {
        $noQualDate = isset($qualDateMap[$no]) ? $qualDateMap[$no] : '';
        $grade    = isset($gradeMap[$no]) ? $gradeMap[$no] : '베이직';
        $maxDepth = isset($DEPTH_MAX[$grade]) ? $DEPTH_MAX[$grade] : 3;
        $found    = _traverseDepth($refChildren, $no, $maxDepth);
        foreach ($found as $depth => $list) {
            if ($depth === 1) $rate = 0.04;
            elseif ($depth <= 7) $rate = 0.02;
            else $rate = 0.008;
            foreach ($list as $childNo) {
                if (!isset($ordersByMember[$childNo])) continue;
                foreach ($ordersByMember[$childNo] as $o) {
                    $saleDate = isset($o['order_date']) ? $o['order_date'] : '';
                    if ($noQualDate !== '' && $saleDate < $noQualDate) continue;
                    $pv = intval(isset($o['pv']) ? $o['pv'] : 0);
                    if ($pv <= 0) continue;
                    $amt = round($pv * $rate);
                    if ($amt > 0) _addComm($comms, $no, isset($mIdx[$no]['name']) ? $mIdx[$no]['name'] : '', '추천매칭수당', $pv, $rate*100, $amt, "depth:{$depth},child:{$childNo}");
                }
            }
        }
    }

    // 03 바이너리수당 10%
    $binaryRows = array();
    foreach ($qualified as $no => $_) {
        $l = isset($legPV[$no]['L']) ? $legPV[$no]['L'] : 0;
        $r = isset($legPV[$no]['R']) ? $legPV[$no]['R'] : 0;
        if ($l <= 0 || $r <= 0) continue;
        $small = min($l, $r);
        $grade = isset($gradeMap[$no]) ? $gradeMap[$no] : '베이직';
        $cap   = isset($BINARY_CAP[$grade]) ? $BINARY_CAP[$grade] : 500000;
        $raw   = round($small * 0.10);
        $amt   = min($raw, $cap);
        if ($amt > 0) {
            $binaryRows[] = array('no'=>$no, 'amt'=>$amt, 'l'=>$l, 'r'=>$r, 'cap'=>$cap, 'small'=>$small);
        }
    }
    $totalPV = array_sum($pvMap);
    $binaryTotal = 0;
    foreach ($binaryRows as $br) $binaryTotal += $br['amt'];
    $binaryLimit = round($totalPV * 0.80);
    $binaryRatio = ($binaryTotal > $binaryLimit && $binaryTotal > 0) ? $binaryLimit / $binaryTotal : 1.0;
    foreach ($binaryRows as $br) {
        $finalAmt = round($br['amt'] * $binaryRatio);
        if ($finalAmt > 0) _addComm($comms, $br['no'], isset($mIdx[$br['no']]['name']) ? $mIdx[$br['no']]['name'] : '', '바이너리수당',
            $br['small'], 10, $finalAmt, "L:{$br['l']},R:{$br['r']},cap:{$br['cap']},ratio:".round($binaryRatio,4));
    }

    // 04 직급수당
    $RANK_PV    = array('1스타'=>2000000, '2스타'=>5000000, '3스타'=>10000000, '4스타'=>20000000, '5스타'=>50000000);
    $RANK_RATES = array('1스타'=>0.03, '2스타'=>0.02, '3스타'=>0.02, '4스타'=>0.03, '5스타'=>0.02);
    $rankOrder  = array('1스타','2스타','3스타','4스타','5스타');

    $rankMembers = array();
    foreach ($qualified as $no => $_) {
        $l = isset($legPV[$no]['L']) ? $legPV[$no]['L'] : 0;
        $r = isset($legPV[$no]['R']) ? $legPV[$no]['R'] : 0;
        $lesser = min($l, $r);
        $revOrder = array_reverse($rankOrder);
        foreach ($revOrder as $rank) {
            if ($lesser >= $RANK_PV[$rank]) {
                if (!isset($rankMembers[$rank])) $rankMembers[$rank] = array();
                $rankMembers[$rank][] = $no;
                break;
            }
        }
    }

    foreach ($rankOrder as $rank) {
        $poolAmt   = round($totalPV * $RANK_RATES[$rank]);
        $receivers = array();
        $rankIdx   = array_search($rank, $rankOrder);
        $higherRanks = array_slice($rankOrder, $rankIdx);
        foreach ($higherRanks as $r2) {
            if (!isset($rankMembers[$r2])) continue;
            foreach ($rankMembers[$r2] as $no) {
                $receivers[$no] = true;
            }
        }
        $receivers = array_keys($receivers);
        if (count($receivers) > 0 && $poolAmt > 0) {
            $per = round($poolAmt / count($receivers));
            foreach ($receivers as $no) {
                _addComm($comms, $no, isset($mIdx[$no]['name']) ? $mIdx[$no]['name'] : '', "직급수당_{$rank}", $totalPV, $RANK_RATES[$rank]*100, $per, "pool:{$poolAmt},rcv:".count($receivers));
            }
        }
    }

    // 05 로또보너스
    $lottoPool    = round($totalPV * 0.03);
    $lottoRefCount = array();
    foreach ($allOrdersThisMonth as $o) {
        $buyerNo = $o['member_no'];
        $refNo   = isset($mIdx[$buyerNo]['referrer_no']) ? $mIdx[$buyerNo]['referrer_no'] : null;
        if (!$refNo) continue;
        $amount  = intval(isset($o['amount']) ? $o['amount'] : 0);
        if ($amount >= 990000) {
            $lottoRefCount[$refNo] = (isset($lottoRefCount[$refNo]) ? $lottoRefCount[$refNo] : 0) + 1;
        }
    }
    $lottoScores = array();
    foreach ($lottoRefCount as $no => $cnt) {
        if ($cnt < 5) continue;
        if (!isset($qualified[$no])) continue;
        $lottoScores[$no] = intval($cnt / 5);
    }
    $totalScore = array_sum($lottoScores);
    if ($totalScore > 0 && $lottoPool > 0) {
        foreach ($lottoScores as $no => $score) {
            $amt = round($lottoPool * $score / $totalScore);
            $rc  = isset($lottoRefCount[$no]) ? $lottoRefCount[$no] : 0;
            if ($amt > 0) _addComm($comms, $no, isset($mIdx[$no]['name']) ? $mIdx[$no]['name'] : '', '로또보너스', $totalPV, 3,
                $amt, "score:{$score},total_score:{$totalScore},ref_count:{$rc}");
        }
    }

    // 06 직추재구매수당 3% (주문종류 002 또는 2 = 재구매만)
    foreach ($qualified as $no => $_) {
        $directRefs = isset($refChildren[$no]) ? $refChildren[$no] : array();
        // 재구매 주문만 필터링하여 직추천인별 집계
        $repurchRefSet = array();
        foreach ($allOrdersThisMonth as $o) {
            $buyerNo   = isset($o['member_no']) ? $o['member_no'] : '';
            $orderType = isset($o['order_type']) ? trim($o['order_type']) : '';
            $isRepurch = ($orderType === '002' || $orderType === '2');
            if (!$isRepurch) continue;
            $refNo = isset($mIdx[$buyerNo]['referrer_no']) ? $mIdx[$buyerNo]['referrer_no'] : null;
            if ($refNo !== $no) continue;
            $repurchRefSet[$buyerNo] = true;
        }
        $activeDirRefs = array();
        foreach ($directRefs as $c) {
            if (isset($repurchRefSet[$c])) $activeDirRefs[] = $c;
        }
        if (count($activeDirRefs) < 3) continue;
        $totalRefPv = 0;
        foreach ($activeDirRefs as $c) $totalRefPv += isset($pvMap[$c]) ? $pvMap[$c] : 0;
        $amt = round($totalRefPv * 0.03);
        if ($amt > 0) _addComm($comms, $no, isset($mIdx[$no]['name']) ? $mIdx[$no]['name'] : '', '직추재구매수당', $totalRefPv, 3, $amt, count($activeDirRefs).'명(재구매)');
    }

    // 07 센터수당 5%
    $centerIds = json_decode(CENTER_MEMBER_IDS, true);
    if (!is_array($centerIds)) $centerIds = array();
    foreach ($centerIds as $cid) {
        $cno = isset($idMap[$cid]) ? $idMap[$cid] : null;
        if (!$cno) continue;
        $subPv      = _getSubtreePV($cno, $mIdx, $pvMap);
        $ownPv      = isset($pvMap[$cno]) ? $pvMap[$cno] : 0;
        $totalSubPv = $subPv + $ownPv;
        $amt        = round($totalSubPv * 0.05);
        if ($amt > 0) _addComm($comms, $cno, isset($mIdx[$cno]['name']) ? $mIdx[$cno]['name'] : '', '센터수당', $totalSubPv, 5, $amt, "subtree:{$subPv},own:{$ownPv}");
    }

    // 요약
    $summaryMap = array();
    foreach ($comms as $c) {
        $k = $c['member_no'];
        if (!isset($summaryMap[$k])) {
            $summaryMap[$k] = array(
                'member_no' => $k,
                'name'      => $c['member_name'],
                'grade'     => isset($gradeMap[$k]) ? $gradeMap[$k] : '미달성',
                'my_pv'     => isset($pvMap[$k]) ? $pvMap[$k] : 0,
                'leg_l'     => isset($legPV[$k]['L']) ? $legPV[$k]['L'] : 0,
                'leg_r'     => isset($legPV[$k]['R']) ? $legPV[$k]['R'] : 0,
                'total'     => 0,
                'by_type'   => array(),
            );
        }
        $summaryMap[$k]['total'] += $c['amount'];
        $t = $c['type'];
        if (strncmp($t, '직급수당_', strlen('직급수당_')) === 0) $t = '직급수당';
        $summaryMap[$k]['by_type'][$t] = (isset($summaryMap[$k]['by_type'][$t]) ? $summaryMap[$k]['by_type'][$t] : 0) + $c['amount'];
    }
    $summary = array_values($summaryMap);
    usort($summary, function($a, $b) { return $b['total'] - $a['total']; });

    // 로또 정보
    $lottoInfo = array();
    foreach ($lottoScores as $no => $sc) {
        $lottoInfo[] = array(
            'no'    => $no,
            'name'  => isset($mIdx[$no]['name']) ? $mIdx[$no]['name'] : $no,
            'score' => $sc,
            'count' => isset($lottoRefCount[$no]) ? $lottoRefCount[$no] : 0,
        );
    }

    return array(
        'period'        => $period,
        'prev_period'   => $prevPeriod,
        'calc_time'     => date('Y-m-d H:i:s'),
        'total_pv'      => $totalPV,
        'total_members' => count($pvMap),
        'qualified_cnt' => count($qualified),
        'total_payout'  => array_sum(array_column($comms, 'amount')),
        'summary'       => $summary,
        'pv_map'        => $pvMap,
        'leg_pv'        => $legPV,
        'grade_map'     => $gradeMap,
        'rank_members'  => $rankMembers,
        'lotto_pool'    => $lottoPool,
        'lotto_scores'  => $lottoScores,
        'lotto_info'    => $lottoInfo,
        'binary_ratio'  => round($binaryRatio, 4),
        'comms'         => $comms,
    );
}

// ── 헬퍼 함수들 ──

function _findNo($s, $idMap, $nameMap) {
    $did  = isset($s['ddm_id'])      ? $s['ddm_id']      : '';
    $name = isset($s['member_name']) ? $s['member_name'] : '';
    if ($did  && isset($idMap[$did]))    return $idMap[$did];
    if ($name && isset($nameMap[$name])) return $nameMap[$name];
    return null;
}

function _getSubtreePV($rootNo, $mIdx, $pvMap) {
    static $ch = null;
    if ($ch === null) {
        $ch = array();
        foreach ($mIdx as $no => $m) {
            $p = isset($m['sponsor_no']) ? $m['sponsor_no'] : null;
            if ($p) {
                if (!isset($ch[$p])) $ch[$p] = array();
                $ch[$p][] = $no;
            }
        }
    }
    $total = 0;
    $q     = array($rootNo);
    $vis   = array();
    while (!empty($q)) {
        $cur = array_shift($q);
        if (isset($vis[$cur])) continue;
        $vis[$cur] = true;
        if (!isset($ch[$cur])) continue;
        foreach ($ch[$cur] as $c) {
            $total += isset($pvMap[$c]) ? $pvMap[$c] : 0;
            $q[] = $c;
        }
    }
    return $total;
}

function _addComm(&$arr, $no, $name, $type, $basePv, $rate, $amt, $detail = '') {
    if ($amt <= 0) return;
    $arr[] = array(
        'member_no'   => $no,
        'member_name' => $name,
        'type'        => $type,
        'base_pv'     => $basePv,
        'rate'        => $rate,
        'amount'      => $amt,
        'detail'      => $detail,
    );
}

function _traverseDepth($refCh, $rootNo, $maxDepth) {
    $res = array();
    $q   = array(array($rootNo, 0));
    while (!empty($q)) {
        $item = array_shift($q);
        $no   = $item[0];
        $d    = $item[1];
        if (!isset($refCh[$no])) continue;
        foreach ($refCh[$no] as $c) {
            $nd = $d + 1;
            if ($nd > $maxDepth) continue;
            if (!isset($res[$nd])) $res[$nd] = array();
            $res[$nd][] = $c;
            $q[] = array($c, $nd);
        }
    }
    return $res;
}
