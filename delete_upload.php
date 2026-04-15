<?php
// api/delete_upload.php  ─ 업로드 배치 삭제
// PHP 5.6+ 완전 호환
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

require_once dirname(__DIR__) . '/config.php';
requireLogin();

// POST 데이터를 JSON으로 받기
$rawInput = file_get_contents('php://input');
$input    = json_decode($rawInput, true);
$uploadId = isset($input['upload_id']) ? $input['upload_id'] : '';
$type     = isset($input['type'])      ? $input['type']      : '';

if (!$uploadId || !$type) {
    jsonOut(array('ok' => false, 'error' => 'upload_id 또는 type 누락'), 400);
}

$historyFile = FILE_UPLOAD_HISTORY;

if (!file_exists($historyFile)) {
    jsonOut(array('ok' => false, 'error' => '이력 파일 없음'), 404);
}

$history     = readJson($historyFile);
$targetUpload = null;
$targetIndex  = null;

foreach ($history as $index => $h) {
    $hid = isset($h['upload_id']) ? $h['upload_id'] : '';
    if ($hid === $uploadId) {
        $targetUpload = $h;
        $targetIndex  = $index;
        break;
    }
}

if ($targetUpload === null) {
    jsonOut(array('ok' => false, 'error' => '해당 업로드를 찾을 수 없습니다'), 404);
}

// 회원 업로드 삭제
if ($type === 'members') {
    $membersFile = FILE_MEMBERS;

    // members.json 초기화 여부 (요청에 reset_data: true 가 있을 때만)
    $resetData = isset($input['reset_data']) && $input['reset_data'] === true;

    if (file_exists($membersFile)) {
        writeJson($membersFile, array());
    }

    // 이력에서 제거
    array_splice($history, $targetIndex, 1);
    writeJson($historyFile, $history);

    jsonOut(array(
        'ok'         => true,
        'message'    => '회원정보 업로드 이력이 삭제되었습니다',
        'reset_data' => $resetData,
    ));
}

// 매출 배치 삭제
if ($type === 'sales') {
    $stats   = isset($targetUpload['stats']) ? $targetUpload['stats'] : array();
    $batchId = isset($stats['batch_id'])     ? $stats['batch_id']     : '';
    if (!$batchId) {
        jsonOut(array('ok' => false, 'error' => 'batch_id가 없습니다'), 400);
    }

    // 배치 파일 삭제
    $batchFile = DIR_SALES_BATCHES . $batchId . '.json';
    if (file_exists($batchFile)) {
        @unlink($batchFile);
    }

    // 통합 매출에서 해당 배치 제거
    $salesFile = FILE_SALES;
    if (!file_exists($salesFile)) {
        jsonOut(array('ok' => false, 'error' => '매출 파일 없음'), 404);
    }

    $sales       = readJson($salesFile);
    $beforeCount = count($sales);
    $filtered    = array();
    foreach ($sales as $s) {
        $sbid = isset($s['batch_id']) ? $s['batch_id'] : '';
        if ($sbid !== $batchId) $filtered[] = $s;
    }
    $filtered     = array_values($filtered);
    $deletedCount = $beforeCount - count($filtered);

    writeJson($salesFile, $filtered);

    // saved_sales.json 에서도 해당 배치 제거
    if (defined('FILE_SAVED_SALES') && file_exists(FILE_SAVED_SALES)) {
        $savedSales = readJson(FILE_SAVED_SALES);
        $savedFiltered = array_values(array_filter($savedSales, function($sv) use ($batchId) {
            return (isset($sv['batch_id']) ? $sv['batch_id'] : '') !== $batchId;
        }));
        writeJson(FILE_SAVED_SALES, $savedFiltered);
    }

    // 이력에서 제거
    array_splice($history, $targetIndex, 1);
    writeJson($historyFile, $history);

    jsonOut(array(
        'ok'            => true,
        'message'       => "배치 삭제 완료 ({$deletedCount}건 제거)",
        'batch_id'      => $batchId,
        'deleted_count' => $deletedCount,
    ));
}

jsonOut(array('ok' => false, 'error' => '알 수 없는 타입'), 400);
