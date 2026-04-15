<?php
// api/save_sales.php — 선택된 매출 건들을 saved_sales.json에 영구 저장
// saved_sales.json은 배치 삭제와 무관하게 유지되는 독립 저장소
// PHP 5.6+ 완전 호환
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

require_once dirname(__DIR__) . '/config.php';
requireLogin();

$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);
$items = isset($input['items']) ? $input['items'] : array();

if (empty($items)) {
    jsonOut(array('ok'=>false,'error'=>'저장할 항목 없음'), 400);
}

// ── 중복 키 ──
function _savedKey($s) {
    $d  = isset($s['ddm_id'])      ? strval($s['ddm_id'])      : '';
    $n  = isset($s['member_name']) ? strval($s['member_name']) : '';
    $dt = isset($s['order_date'])  ? strval($s['order_date'])  : '';
    $a  = isset($s['amount'])      ? strval(intval($s['amount'])) : '0';
    $p  = isset($s['pv'])          ? strval(intval($s['pv']))     : '0';
    return $d.'|'.$n.'|'.$dt.'|'.$a.'|'.$p;
}

// 기존 saved_sales.json 로드
$existing    = file_exists(FILE_SAVED_SALES) ? readJson(FILE_SAVED_SALES) : array();
$existingKeys = array();
foreach ($existing as $e) {
    $existingKeys[_savedKey($e)] = true;
}

$savedItems  = array();
$dupCount    = 0;

foreach ($items as $item) {
    $s = $item;
    // 임시 필드 제거
    unset($s['_global_idx']);
    unset($s['_cancelled']);
    unset($s['_saved_permanent']);

    $k = _savedKey($s);
    if (isset($existingKeys[$k])) {
        $dupCount++;
        continue;
    }

    // 영구저장 마킹 — batch_id는 유지(출처 추적용), 영구저장 플래그 추가
    $s['_saved_permanent'] = true;
    $savedItems[]          = $s;
    $existingKeys[$k]      = true;
}

$savedCount = count($savedItems);

if ($savedCount > 0) {
    $merged = array_merge($existing, $savedItems);
    writeJson(FILE_SAVED_SALES, array_values($merged));
}

$msg = "선택저장 완료: {$savedCount}건";
if ($dupCount > 0) $msg .= " (이미 저장된 {$dupCount}건 제외)";
$msg .= "\n파일 업로드 배치를 삭제해도 이 데이터는 유지됩니다.";

jsonOut(array(
    'ok'          => true,
    'saved_count' => $savedCount,
    'dup_count'   => $dupCount,
    'message'     => $msg,
));
