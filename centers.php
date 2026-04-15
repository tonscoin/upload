<?php
// api/centers.php — 센터 관리 API
require_once dirname(__DIR__) . '/config.php';
requireLogin();

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');
$file   = DATA_DIR . 'centers.json';

function readCenters($file) {
    if (!file_exists($file)) return array();
    $d = json_decode(file_get_contents($file), true);
    return is_array($d) ? $d : array();
}

// 목록 조회
if ($action === 'list') {
    jsonOut(array('ok' => true, 'data' => readCenters($file)));
}

// 추가/수정
if ($action === 'save') {
    $input = json_decode(file_get_contents('php://input'), true) ?: array();
    $member_no  = trim($input['member_no']  ?? '');
    $login_id   = trim($input['login_id']   ?? '');
    $name       = trim($input['name']       ?? '');
    $start_date = trim($input['start_date'] ?? date('Y-m-d'));
    $note       = trim($input['note']       ?? '');

    if (!$member_no) { jsonOut(array('ok'=>false,'error'=>'member_no 필요')); }

    $centers = readCenters($file);
    $found = false;
    foreach ($centers as &$c) {
        if ($c['member_no'] === $member_no) {
            $c['login_id']   = $login_id;
            $c['name']       = $name;
            $c['start_date'] = $start_date;
            $c['note']       = $note;
            $c['updated']    = date('Y-m-d H:i:s');
            $found = true;
            break;
        }
    }
    unset($c);
    if (!$found) {
        $centers[] = array(
            'member_no'  => $member_no,
            'login_id'   => $login_id,
            'name'       => $name,
            'start_date' => $start_date,
            'note'       => $note,
            'created'    => date('Y-m-d H:i:s'),
            'updated'    => date('Y-m-d H:i:s'),
        );
    }
    writeJson($file, $centers);
    jsonOut(array('ok'=>true));
}

// 삭제
if ($action === 'delete') {
    $input = json_decode(file_get_contents('php://input'), true) ?: array();
    $member_no = trim($input['member_no'] ?? '');
    if (!$member_no) { jsonOut(array('ok'=>false,'error'=>'member_no 필요')); }
    $centers = readCenters($file);
    $centers = array_values(array_filter($centers, function($c) use ($member_no) {
        return $c['member_no'] !== $member_no;
    }));
    writeJson($file, $centers);
    jsonOut(array('ok'=>true));
}

jsonOut(array('ok'=>false,'error'=>'unknown action'));
