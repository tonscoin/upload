<?php
// api/honor.php — 인정자격 회원 관리 (수정#5: 기간 설정 추가)
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

require_once dirname(__DIR__) . '/config.php';
requireLogin();

define('FILE_HONOR', DATA_DIR . 'honor_members.json');

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// ── 목록 조회 ──
if ($action === 'list') {
    $list = readJson(FILE_HONOR);
    jsonOut(array('ok'=>true,'data'=>array_values($list)));
}

// ── 저장/수정 (수정#5: grade_expire_date, qual_expire_date 저장) ──
if ($action === 'save') {
    $raw   = file_get_contents('php://input');
    $input = json_decode($raw, true);
    $no    = isset($input['member_no']) ? trim($input['member_no']) : '';
    if (!$no) jsonOut(array('ok'=>false,'error'=>'member_no 필요'), 400);

    $list  = readJson(FILE_HONOR);
    $found = false;
    foreach ($list as &$item) {
        if ($item['member_no'] === $no) {
            $item['grade']                = isset($input['grade'])                ? $input['grade']                : $item['grade'];
            $item['active']               = isset($input['active'])               ? (bool)$input['active']         : $item['active'];
            $item['note']                 = isset($input['note'])                 ? $input['note']                 : '';
            // 수정#5: 기간 만료일 저장 (null=무기한)
            $item['grade_expire_date']    = isset($input['grade_expire_date'])    ? $input['grade_expire_date']    : null;
            $item['qual_expire_date']     = isset($input['qual_expire_date'])     ? $input['qual_expire_date']     : null;
            // 자격취득일 (없으면 null = 주 시작일 기준 자동 적용)
            $item['honor_acquired_date']  = isset($input['honor_acquired_date'])  ? $input['honor_acquired_date']  : (isset($item['honor_acquired_date']) ? $item['honor_acquired_date'] : null);
            $item['updated']              = date('Y-m-d H:i:s');
            $found = true;
            break;
        }
    }
    unset($item);

    if (!$found) {
        $members = readJson(FILE_MEMBERS);
        $mInfo   = null;
        foreach ($members as $m) {
            if ($m['member_no'] === $no) { $mInfo = $m; break; }
        }
        $list[] = array(
            'member_no'           => $no,
            'name'                => $mInfo ? (isset($mInfo['name'])     ? $mInfo['name']     : '') : (isset($input['name'])     ? $input['name']     : ''),
            'login_id'            => $mInfo ? (isset($mInfo['login_id']) ? $mInfo['login_id'] : '') : (isset($input['login_id']) ? $input['login_id'] : ''),
            'grade'               => isset($input['grade'])               ? $input['grade']               : '베이직',
            'active'              => isset($input['active'])              ? (bool)$input['active']        : true,
            'note'                => isset($input['note'])                ? $input['note']                : '',
            'grade_expire_date'   => isset($input['grade_expire_date'])   ? $input['grade_expire_date']   : null,
            'qual_expire_date'    => isset($input['qual_expire_date'])    ? $input['qual_expire_date']     : null,
            'honor_acquired_date' => isset($input['honor_acquired_date']) ? $input['honor_acquired_date'] : null,
            'created'             => date('Y-m-d H:i:s'),
            'updated'             => date('Y-m-d H:i:s'),
        );
    }
    writeJson(FILE_HONOR, array_values($list));
    jsonOut(array('ok'=>true,'message'=>$found?'인정자격 수정 완료':'인정자격 추가 완료'));
}

// ── 삭제 ──
if ($action === 'delete') {
    $raw   = file_get_contents('php://input');
    $input = json_decode($raw, true);
    $no    = isset($input['member_no']) ? trim($input['member_no']) : '';
    if (!$no) jsonOut(array('ok'=>false,'error'=>'member_no 필요'), 400);

    $list = readJson(FILE_HONOR);
    $new  = array();
    foreach ($list as $item) {
        if ($item['member_no'] !== $no) $new[] = $item;
    }
    writeJson(FILE_HONOR, array_values($new));
    jsonOut(array('ok'=>true,'message'=>'인정자격 삭제 완료'));
}

jsonOut(array('ok'=>false,'error'=>'unknown action'), 400);
