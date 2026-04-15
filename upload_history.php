<?php
// api/upload_history.php  ─ 업로드 이력 조회
// PHP 5.6+ 완전 호환
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

require_once dirname(__DIR__) . '/config.php';
requireLogin();

$type = isset($_GET['type']) ? $_GET['type'] : '';

// 파일이 없으면 빈 배열 반환
if (!file_exists(FILE_UPLOAD_HISTORY)) {
    jsonOut(array(
        'ok'    => true,
        'data'  => array(),
        'total' => 0,
    ));
}

$history = readJson(FILE_UPLOAD_HISTORY);

// 타입 필터링
if ($type && in_array($type, array('members', 'sales'))) {
    $filtered = array();
    foreach ($history as $h) {
        if (isset($h['type']) && $h['type'] === $type) {
            $filtered[] = $h;
        }
    }
    $history = array_values($filtered);
}

// 최신순 정렬
usort($history, function($a, $b) {
    $ta = isset($a['timestamp']) ? $a['timestamp'] : 0;
    $tb = isset($b['timestamp']) ? $b['timestamp'] : 0;
    return $tb - $ta;
});

jsonOut(array(
    'ok'    => true,
    'data'  => $history,
    'total' => count($history),
));
