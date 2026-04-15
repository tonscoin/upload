<?php
/**
 * api/upload.php — CSV 업로드 처리
 * PHP 5.6 이상 완전 호환
 * 서버 오류가 있어도 항상 JSON으로 응답
 */

// ① 가장 먼저: 출력 버퍼링 + 에러 숨기기
ob_start();
error_reporting(0);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

// ② config 로드
require_once dirname(__DIR__) . '/config.php';

// ③ 로그인 확인
requireLogin();

// 항상 JSON으로만 응답
function outJson($data, $code = 200) {
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── 입력 검증 ──
$type = isset($_POST['type']) ? $_POST['type'] : '';
if (!in_array($type, array('members', 'sales'))) {
    outJson(array('ok'=>false, 'error'=>'type은 members 또는 sales만 가능합니다'));
}

if (!isset($_FILES['file'])) {
    outJson(array('ok'=>false, 'error'=>'파일이 전송되지 않았습니다'));
}

$fileError = isset($_FILES['file']['error']) ? $_FILES['file']['error'] : UPLOAD_ERR_NO_FILE;
if ($fileError !== UPLOAD_ERR_OK) {
    $errMsgs = array(
        UPLOAD_ERR_INI_SIZE   => '파일이 너무 큽니다 (서버 upload_max_filesize 초과)',
        UPLOAD_ERR_FORM_SIZE  => '파일이 너무 큽니다',
        UPLOAD_ERR_PARTIAL    => '파일이 일부만 업로드되었습니다',
        UPLOAD_ERR_NO_FILE    => '파일이 선택되지 않았습니다',
        UPLOAD_ERR_NO_TMP_DIR => '서버 임시 폴더가 없습니다',
        UPLOAD_ERR_CANT_WRITE => '서버 파일 쓰기 실패 (권한 확인)',
    );
    $msg = isset($errMsgs[$fileError]) ? $errMsgs[$fileError] : ('업로드 오류 코드: ' . $fileError);
    outJson(array('ok'=>false, 'error'=>$msg));
}

$tmpFile  = $_FILES['file']['tmp_name'];
$fileName = basename($_FILES['file']['name']);
$ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (!in_array($ext, array('csv', 'txt'))) {
    outJson(array('ok'=>false, 'error'=>'CSV 파일만 가능합니다 (엑셀 → 다른이름저장 → CSV UTF-8)'));
}

if (!is_readable($tmpFile)) {
    outJson(array('ok'=>false, 'error'=>'업로드된 파일을 읽을 수 없습니다'));
}

// ── CSV 파싱 ──
function doParseCSV($filePath) {
    $raw = @file_get_contents($filePath);
    if ($raw === false || trim($raw) === '') {
        return array('error' => '파일을 읽을 수 없거나 비어 있습니다');
    }

    // BOM 제거
    if (substr($raw, 0, 3) === "\xEF\xBB\xBF") {
        $raw = substr($raw, 3);
    }

    // 인코딩 변환 (EUC-KR / CP949 → UTF-8)
    if (function_exists('mb_detect_encoding')) {
        $enc = mb_detect_encoding($raw, array('UTF-8','EUC-KR','CP949','ASCII'), true);
        if ($enc && strtoupper($enc) !== 'UTF-8' && strtoupper($enc) !== 'ASCII') {
            $converted = @mb_convert_encoding($raw, 'UTF-8', $enc);
            if ($converted !== false && $converted !== '') {
                $raw = $converted;
            }
        }
    }

    // 줄 분리
    $raw   = str_replace(array("\r\n", "\r"), "\n", $raw);
    $lines = explode("\n", $raw);

    // 첫 비어있지 않은 줄 = 헤더
    $headerLine = '';
    $startIdx   = 0;
    foreach ($lines as $i => $line) {
        if (trim($line) !== '') {
            $headerLine = $line;
            $startIdx   = $i + 1;
            break;
        }
    }
    if ($headerLine === '') {
        return array('error' => '헤더를 찾을 수 없습니다');
    }

    $headers = csvParseLine($headerLine);
    foreach ($headers as $k => $h) {
        $headers[$k] = trim(str_replace("\xEF\xBB\xBF", '', $h));
    }

    $data = array();
    for ($i = $startIdx; $i < count($lines); $i++) {
        $line = $lines[$i];
        if (trim($line) === '') continue;

        $vals    = csvParseLine($line);
        $row     = array();
        $hasData = false;

        foreach ($headers as $j => $h) {
            if ($h === '') continue;
            $v       = isset($vals[$j]) ? trim($vals[$j]) : '';
            $row[$h] = $v;
            if ($v !== '') $hasData = true;
        }

        if ($hasData) $data[] = $row;
    }

    if (empty($data)) {
        return array('error' => '유효한 데이터 행이 없습니다. 헤더 행만 있거나 모두 비어 있습니다.');
    }

    return array('data' => $data);
}

// RFC 4180 CSV 한 줄 파싱
function csvParseLine($line) {
    $result = array();
    $cur    = '';
    $inQ    = false;
    $len    = strlen($line);

    for ($i = 0; $i < $len; $i++) {
        $ch = $line[$i];
        if ($ch === '"') {
            if ($inQ && $i + 1 < $len && $line[$i+1] === '"') {
                $cur .= '"';
                $i++;
            } else {
                $inQ = !$inQ;
            }
        } elseif ($ch === ',' && !$inQ) {
            $result[] = $cur;
            $cur      = '';
        } else {
            $cur .= $ch;
        }
    }
    $result[] = $cur;
    return $result;
}

// 다중 키로 컬럼 값 가져오기
function getCol($row, $keys) {
    foreach ($keys as $k) {
        if (isset($row[$k]) && trim($row[$k]) !== '') {
            return trim($row[$k]);
        }
    }
    return '';
}

// 등급 표준화
function normGrade($raw) {
    $raw = trim($raw);
    $map = array(
        '플래티넘'=>'플래티넘','PLATINUM'=>'플래티넘','platinum'=>'플래티넘','Platinum'=>'플래티넘',
        '골드'=>'골드','GOLD'=>'골드','gold'=>'골드','Gold'=>'골드',
        '플러스'=>'플러스','PLUS'=>'플러스','plus'=>'플러스','Plus'=>'플러스',
        '베이직'=>'베이직','BASIC'=>'베이직','basic'=>'베이직','Basic'=>'베이직',
        // '회원' 등급은 미달성으로 처리 (아직 등급 미달성 신규 회원)
        '회원'=>'미달성','MEMBER'=>'미달성','member'=>'미달성',
    );
    return isset($map[$raw]) ? $map[$raw] : '미달성';
}

// 숫자 정리 (콤마, 공백, 원 제거)
function toInt($v) {
    $s = str_replace(',', '', strval($v));
    // 소수점 있으면 float 변환 후 정수화 (800000.00 → 800000)
    if (strpos($s, '.') !== false) {
        return intval(round(floatval($s)));
    }
    return intval(preg_replace('/[^0-9\-]/', '', $s));
}

// 날짜 정규화
function normDate($raw) {
    $raw = trim($raw);
    if ($raw === '') return '';
    // 한국어 요일 표기 제거: (월)(화)(수)(목)(금)(토)(일) 등
    $raw = preg_replace('/\([가-힣]+\)/', '', $raw);
    $raw = trim($raw);
    // YYYY-MM-DD / YYYY/MM/DD / YYYY.MM.DD
    if (preg_match('/(\d{4})[-\/\.](\d{1,2})[-\/\.](\d{1,2})/', $raw, $m)) {
        return $m[1] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[3], 2, '0', STR_PAD_LEFT);
    }
    // YYYYMMDD
    if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $raw, $m)) {
        return $m[1] . '-' . $m[2] . '-' . $m[3];
    }
    return $raw;
}

// ── CSV 파싱 실행 ──
$parsed = doParseCSV($tmpFile);
if (isset($parsed['error'])) {
    outJson(array('ok'=>false, 'error'=>$parsed['error']));
}
$rows = $parsed['data'];

// ══════════════════════════════════════════
// 회원정보 처리
// ══════════════════════════════════════════
if ($type === 'members') {
    $existing  = readJson(FILE_MEMBERS);
    $memberMap = array();
    foreach ($existing as $m) {
        if (!empty($m['member_no'])) {
            $memberMap[$m['member_no']] = $m;
        }
    }

    $newCount = $updateCount = $skippedCount = 0;

    foreach ($rows as $row) {
        $memberNo = getCol($row, array('회원번호','회원ID','member_no','번호','ID번호','회원No','No'));
        if (!$memberNo) {
            $skippedCount++;
            continue;
        }

        $csvGrade    = normGrade(getCol($row, array('등급','grade','회원등급','직급','현재등급')));
        $existingMax = isset($memberMap[$memberNo]['max_grade']) ? $memberMap[$memberNo]['max_grade'] : '미달성';
        $newMax      = higherGrade($existingMax, $csvGrade);

        $member = array(
            'member_no'      => $memberNo,
            'name'           => getCol($row, array('이름','성명','name','회원명')),
            'login_id'       => getCol($row, array('ID','로그인ID','login_id','아이디','DDM ID','DDM_ID')),
            'grade'          => $newMax,
            'max_grade'      => $newMax,
            'referrer_no'    => getCol($row, array('추천인번호','추천인ID','추천번호','추천인No')),
            'referrer_name'  => getCol($row, array('추천인명','추천인','추천인이름')),
            'sponsor_no'     => getCol($row, array('후원인번호','후원인ID','후원번호','후원인No')),
            'sponsor_name'   => getCol($row, array('후원인명','후원인','후원인이름')),
            'position'       => strtoupper(getCol($row, array('위치','position','배치위치','L/R'))),
            'phone'          => getCol($row, array('휴대폰','연락처','전화번호','phone','핸드폰')),
            'join_date'      => normDate(getCol($row, array('가입일','등록일','join_date','가입날짜'))),
            'center'         => getCol($row, array('센터','center','소속센터')),
            'bank_name'      => getCol($row, array('은행명','은행','bank')),
            'account_holder' => getCol($row, array('예금주','계좌주','예금주명')),
            'account_no'     => getCol($row, array('계좌번호','계좌','account')),
            'email'          => getCol($row, array('이메일','email','Email')),
            'memo'           => getCol($row, array('비고','메모','memo')),
        );

        if (isset($memberMap[$memberNo])) $updateCount++;
        else $newCount++;
        $memberMap[$memberNo] = $member;
    }

    if ($newCount + $updateCount === 0) {
        outJson(array('ok'=>false, 'error' =>
            "처리된 회원이 없습니다.\nCSV 첫 행(헤더)을 확인하세요.\n필수: 회원번호, 이름, ID"));
    }

    $finalMembers = array_values($memberMap);
    $writeResult  = writeJson(FILE_MEMBERS, $finalMembers);

    if ($writeResult === false) {
        outJson(array('ok'=>false, 'error'=>'파일 저장 실패. data/ 폴더 권한(777)을 확인하세요.'));
    }

    $stats = array(
        'total_count'   => count($finalMembers),
        'new_count'     => $newCount,
        'updated_count' => $updateCount,
        'skipped_count' => $skippedCount,
    );
    $uploadId = saveUploadHistory('members', $fileName, $stats);

    outJson(array(
        'ok'        => true,
        'count'     => count($finalMembers),
        'stats'     => $stats,
        'upload_id' => $uploadId,
        'message'   => "완료: 신규 {$newCount}명 / 갱신 {$updateCount}명 / 건너뜀 {$skippedCount}명",
    ));
}

// ══════════════════════════════════════════
// 매출정보 처리 — 신규/갱신 스마트 병합
// ══════════════════════════════════════════
if ($type === 'sales') {
    if (!is_dir(DIR_SALES_BATCHES)) {
        @mkdir(DIR_SALES_BATCHES, 0755, true);
    }

    // ── CSV 파싱 ──
    $parsedRows  = array();
    $skippedCount = 0;

    foreach ($rows as $row) {
        $orderDate  = normDate(getCol($row, array('주문일','판매일','order_date','날짜','일자','주문날짜')));
        $memberName = getCol($row, array('이름','회원명','name','구매자','회원이름'));
        $ddmId      = getCol($row, array('DDM ID','DDM_ID','ddm_id','ID','로그인ID','아이디'));

        if (!$orderDate && !$memberName && !$ddmId) { $skippedCount++; continue; }

        // ERP CSV 취소 건 스킵
        $cancelVal = strtoupper(trim(getCol($row, array('취소','취소여부','cancel'))));
        if ($cancelVal === 'O') { $skippedCount++; continue; }

        $pv     = toInt(getCol($row, array('BV','PV','bv','pv','포인트','BV1')));
        $pv2    = toInt(getCol($row, array('BV2','PV2','bv2','pv2')));
        $amount = toInt(getCol($row, array('주문금액','판매금액','amount','금액','주문가격')));

        $rawType = trim(str_replace(array("\t","\r","\n"), '', getCol($row, array('주문종류','order_type','구분','타입'))));
        if ($rawType === '001' || $rawType === '일반주문') $rawType = '1';
        if ($rawType === '002' || $rawType === '유지주문') $rawType = '2';

        $parsedRows[] = array(
            'order_date'   => $orderDate,
            'member_name'  => $memberName,
            'ddm_id'       => $ddmId,
            'phone'        => getCol($row, array('연락처','휴대폰','phone')),
            'product_name' => getCol($row, array('상품명','제품명','product','품목')),
            'amount'       => $amount,
            'pv'           => $pv,
            'pv2'          => $pv2,
            'order_type'   => $rawType,
            'memo'         => getCol($row, array('비고','메모','memo')),
        );
    }

    if (empty($parsedRows)) {
        outJson(array('ok'=>false, 'error'=>
            "유효한 매출 데이터가 없습니다.\n헤더 예시: 주문일,이름,DDM ID,상품명,주문금액,BV"));
    }

    // ── 동일성 판별 키 생성 ──
    // 주문일 + DDM ID(없으면 이름) + 상품명 + 금액 조합으로 중복 판단
    function makeSaleUniqueKey($s) {
        $id = !empty($s['ddm_id']) ? $s['ddm_id'] : $s['member_name'];
        return implode('|', array(
            trim($s['order_date']   ?? ''),
            strtolower(trim($id)),
            trim($s['product_name'] ?? ''),
            strval(intval($s['amount'] ?? 0)),
        ));
    }

    // ── 기존 sales.json 읽어서 키맵 생성 ──
    $existing   = readJson(FILE_SALES);
    $existingMap = array();  // key => index in $existing
    foreach ($existing as $idx => $s) {
        $k = makeSaleUniqueKey($s);
        if ($k !== '|||0') $existingMap[$k] = $idx;
    }

    // ── 신규/갱신 분류 ──
    $batchId     = 'batch_' . date('YmdHis') . '_' . substr(uniqid(), -4);
    $newCount    = 0;
    $updateCount = 0;
    $totalPV     = 0;
    $totalAmount = 0;
    $dates       = array();
    $batchData   = array();  // 이번 배치에 포함된 행(신규+갱신 모두)

    foreach ($parsedRows as $sale) {
        $key  = makeSaleUniqueKey($sale);
        $sale['batch_id'] = $batchId;

        if (isset($existingMap[$key])) {
            // ── 갱신: 기존 행 덮어쓰기 (batch_id는 원래 것 유지, 나머지 필드 갱신) ──
            $origBatchId = isset($existing[$existingMap[$key]]['batch_id'])
                         ? $existing[$existingMap[$key]]['batch_id'] : $batchId;
            // 영구저장 여부 보존
            $savedPerm   = isset($existing[$existingMap[$key]]['_saved_permanent'])
                         ? $existing[$existingMap[$key]]['_saved_permanent'] : false;
            $sale['batch_id']          = $origBatchId;
            $sale['_saved_permanent']  = $savedPerm;
            $sale['_updated_at']       = date('Y-m-d H:i:s');
            $existing[$existingMap[$key]] = $sale;
            $updateCount++;
        } else {
            // ── 신규 추가 ──
            $existing[]  = $sale;
            $newCount++;
        }

        $batchData[]  = $sale;
        $totalPV     += intval($sale['pv'] ?? 0);
        $totalAmount += intval($sale['amount'] ?? 0);
        if ($sale['order_date']) $dates[] = $sale['order_date'];
    }

    // ── 배치 파일 저장 (이번 업로드 내역 백업용) ──
    $batchFile = DIR_SALES_BATCHES . $batchId . '.json';
    writeJson($batchFile, $batchData);

    // ── 병합된 전체 sales.json 저장 ──
    $writeResult = writeJson(FILE_SALES, array_values($existing));
    if ($writeResult === false) {
        outJson(array('ok'=>false, 'error'=>'파일 저장 실패. data/ 폴더 권한(777)을 확인하세요.'));
    }

    // 매출 기반 max_grade 자동 갱신
    _updateMemberGrades();

    sort($dates);
    $stats = array(
        'batch_id'      => $batchId,
        'count'         => count($batchData),
        'new_count'     => $newCount,
        'updated_count' => $updateCount,
        'skipped_count' => $skippedCount,
        'total_pv'      => $totalPV,
        'total_amount'  => $totalAmount,
        'date_from'     => !empty($dates) ? $dates[0] : '',
        'date_to'       => !empty($dates) ? end($dates) : '',
    );
    $uploadId = saveUploadHistory('sales', $fileName, $stats);

    outJson(array(
        'ok'        => true,
        'count'     => count($batchData),
        'stats'     => $stats,
        'upload_id' => $uploadId,
        'message'   => "완료: 신규 {$newCount}건 / 갱신 {$updateCount}건 / 건너뜀 {$skippedCount}건",
    ));
}

outJson(array('ok'=>false, 'error'=>'알 수 없는 오류'));

// ── 매출 기반 max_grade 자동 갱신 ──
function _updateMemberGrades() {
    $members = readJson(FILE_MEMBERS);
    if (empty($members)) return;

    $sales = readJson(FILE_SALES);

    $idMap   = array();
    $nameMap = array();
    foreach ($members as $m) {
        if (!empty($m['login_id'])) $idMap[$m['login_id']]  = $m['member_no'];
        if (!empty($m['name']))     $nameMap[$m['name']]    = $m['member_no'];
    }

    $pvMap = array();
    foreach ($sales as $s) {
        $did  = isset($s['ddm_id'])      ? $s['ddm_id']      : '';
        $name = isset($s['member_name']) ? $s['member_name'] : '';
        $no   = isset($idMap[$did])   ? $idMap[$did]
              : (isset($nameMap[$name]) ? $nameMap[$name] : null);
        if (!$no) continue;
        $pvMap[$no] = (isset($pvMap[$no]) ? $pvMap[$no] : 0) + intval(isset($s['pv']) ? $s['pv'] : 0);
    }

    $updated = false;
    foreach ($members as &$m) {
        $no  = $m['member_no'];
        $pv  = isset($pvMap[$no]) ? $pvMap[$no] : 0;
        $pvG = calcGrade($pv);
        $cur = isset($m['max_grade']) ? $m['max_grade'] : '미달성';
        $new = higherGrade($cur, $pvG);
        if ($new !== $cur) {
            $m['max_grade'] = $new;
            $m['grade']     = $new;
            $updated        = true;
        }
    }
    unset($m);

    if ($updated) writeJson(FILE_MEMBERS, $members);
}
