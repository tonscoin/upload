<?php
// api/delete_saved_sale.php — saved_sales.json에서 개별 건 삭제
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

require_once dirname(__DIR__) . '/config.php';
requireLogin();

$raw  = file_get_contents('php://input');
$input = json_decode($raw, true);
$key  = isset($input['sale_key']) ? $input['sale_key'] : '';

if ($key === '') {
    jsonOut(array('ok'=>false,'error'=>'sale_key 누락'), 400);
}

function _svKey2($s) {
    $d  = isset($s['ddm_id'])      ? strval($s['ddm_id'])      : '';
    $n  = isset($s['member_name']) ? strval($s['member_name']) : '';
    $dt = isset($s['order_date'])  ? strval($s['order_date'])  : '';
    $a  = isset($s['amount'])      ? strval(intval($s['amount'])) : '0';
    $p  = isset($s['pv'])          ? strval(intval($s['pv']))     : '0';
    return $d.'|'.$n.'|'.$dt.'|'.$a.'|'.$p;
}

if (!file_exists(FILE_SAVED_SALES)) {
    jsonOut(array('ok'=>false,'error'=>'saved_sales.json 없음'), 404);
}

$saved  = readJson(FILE_SAVED_SALES);
$before = count($saved);
$filtered = array();
foreach ($saved as $s) {
    if (_svKey2($s) !== $key) $filtered[] = $s;
}
$deleted = $before - count($filtered);

if ($deleted === 0) {
    jsonOut(array('ok'=>false,'error'=>'해당 건을 찾을 수 없습니다'), 404);
}

writeJson(FILE_SAVED_SALES, array_values($filtered));
jsonOut(array('ok'=>true,'message'=>'영구저장 데이터 삭제 완료','deleted'=>$deleted));
