<?php
// api/delete_sale.php — 개별 매출 건 삭제 (중복 제거용)
// PHP 5.6+ 완전 호환
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

require_once dirname(__DIR__) . '/config.php';
requireLogin();

$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);

// 전체 배열에서의 실제 인덱스
$idx = isset($input['sale_index']) ? intval($input['sale_index']) : -1;
// 검증용 키 (JS의 makeSaleKey와 동일: ddm_id|member_name|order_date|amount|pv)
$key = isset($input['sale_key']) ? $input['sale_key'] : '';

if ($idx < 0 || $key === '') {
    jsonOut(array('ok'=>false,'error'=>'sale_index 또는 sale_key 누락'), 400);
}

$sales = readJson(FILE_SALES);

if (!isset($sales[$idx])) {
    jsonOut(array('ok'=>false,'error'=>'해당 인덱스 없음 (index='.$idx.',total='.count($sales).')'), 404);
}

$s = $sales[$idx];
// JS makeSaleKey와 동일하게: [ddm_id, member_name, order_date, amount, pv].join('|')
// amount, pv는 parseInt/intval로 정수 문자열 통일
$ddmId      = isset($s['ddm_id'])       ? strval($s['ddm_id'])       : '';
$memberName = isset($s['member_name'])  ? strval($s['member_name'])  : '';
$orderDate  = isset($s['order_date'])   ? strval($s['order_date'])   : '';
$amount     = isset($s['amount'])       ? strval(intval($s['amount'])) : '0';
$pv         = isset($s['pv'])           ? strval(intval($s['pv']))     : '0';

$sKey = $ddmId . '|' . $memberName . '|' . $orderDate . '|' . $amount . '|' . $pv;

if ($sKey !== $key) {
    jsonOut(array('ok'=>false,'error'=>'키 불일치','server_key'=>$sKey,'client_key'=>$key), 409);
}

array_splice($sales, $idx, 1);
writeJson(FILE_SALES, array_values($sales));

jsonOut(array('ok'=>true,'message'=>'삭제 완료','deleted_index'=>$idx));
