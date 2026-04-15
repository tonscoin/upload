<?php
// ============================================
// 리셀라 MLM 관리시스템 설정 (완전 호환판)
// PHP 5.6 이상 호환 · 서브디렉토리 세션 완전 수정
// ============================================

define('SYSTEM_TITLE',    '리셀라 MLM 관리');
define('DATA_DIR',        __DIR__ . '/data/');
define('SESSION_KEY',     'resella_mlm_auth');
define('SESSION_TIMEOUT', 7200);

// ── JSON 데이터 파일 경로 ──
define('FILE_MEMBERS',        DATA_DIR . 'members.json');
define('FILE_SALES',          DATA_DIR . 'sales.json');
define('FILE_SAVED_SALES',    DATA_DIR . 'saved_sales.json');  // 매출·주문에서 선택저장한 영구 데이터
define('FILE_CALC',           DATA_DIR . 'calc_');
define('FILE_UPLOAD_HISTORY', DATA_DIR . 'upload_history.json');
define('DIR_SALES_BATCHES',   DATA_DIR . 'sales_batches/');

// ── 센터 수당 대상 회원 login_id 목록 ──
define('CENTER_MEMBER_IDS', json_encode(array()));

// ── 보상플랜 상수 ──
define('PLAN', json_encode(array(
    'grade_pv'        => array('베이직'=>100000,'플러스'=>320000,'골드'=>560000,'플래티넘'=>800000),
    'min_qualify_pv'  => 100000,
    'binary_rate'     => 0.10,
    'binary_cap'      => array('베이직'=>500000,'플러스'=>1500000,'골드'=>3000000,'플래티넘'=>10000000),
    'referral_rate'   => 0.10,
    'matching_depth'  => array('베이직'=>3,'플러스'=>5,'골드'=>8,'플래티넘'=>12),
    'matching_rates'  => array('d1'=>0.20,'d2_7'=>0.10,'d8_12'=>0.04),
    'repurchase_rate' => 0.03,
    'repurchase_min'  => 3,
    'center_rate'     => 0.05,
    'rank_pool_total' => 0.12,
    'rank_rates'      => array('1스타'=>0.03,'2스타'=>0.02,'3스타'=>0.02,'4스타'=>0.03,'5스타'=>0.02),
    'rank_pv'         => array('1스타'=>2000000,'2스타'=>5000000,'3스타'=>10000000,'4스타'=>20000000,'5스타'=>50000000),
    'lotto_pool_rate' => 0.03,
    'lotto_min_pv'    => 990000,
    'lotto_step'      => 5,
)));

// ── .env 파일 로드 ──
function loadEnv() {
    $candidates = array(
        dirname(dirname(__DIR__)) . '/.env',
        dirname(__DIR__) . '/.env',
        '/home/.env',
        '/var/www/.env',
    );
    foreach ($candidates as $path) {
        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#') continue;
                if (strpos($line, '=') === false) continue;
                $parts = explode('=', $line, 2);
                $_ENV[trim($parts[0])] = trim($parts[1]);
            }
            return true;
        }
    }
    return false;
}
loadEnv();

define('ADMIN_ID',      isset($_ENV['ADMIN_ID'])      ? $_ENV['ADMIN_ID']      : 'admin');
define('ADMIN_PW_HASH', isset($_ENV['ADMIN_PW_HASH']) ? $_ENV['ADMIN_PW_HASH'] : '$2y$10$YourHashHere');

// ── 세션 시작 ──
// PHP 5.6/7.x/8.x 모두 호환 · 서브디렉토리에서도 쿠키 공유
function startSession() {
    if (session_status() !== PHP_SESSION_NONE) return;

    // 세션 쿠키 path를 '/'로 고정 — /don/ 서브디렉토리에서도 세션 유지
    // PHP 7.3+ 배열 방식 대신 구형 방식 사용 (PHP 5.6 호환)
    $p = session_get_cookie_params();
    session_set_cookie_params(
        $p['lifetime'],  // 유효기간
        '/',             // ★ path: 도메인 전체 (서브디렉토리 문제 해결 핵심)
        $p['domain'],    // 도메인
        $p['secure'],    // HTTPS only
        true             // httponly
    );

    session_name('RESELLA_SESS');  // 세션명 고정 (다른 앱과 충돌 방지)
    session_start();
}

// ── AJAX 요청 판단 ──
function isAjax() {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return true;
    }
    if (isset($_SERVER['HTTP_ACCEPT']) &&
        strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        return true;
    }
    return false;
}

// ── 로그인 확인 ──
function requireLogin() {
    startSession();

    if (empty($_SESSION[SESSION_KEY])) {
        if (isAjax()) {
            // 버퍼 완전 비우기
            while (ob_get_level() > 0) ob_end_clean();
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo '{"ok":false,"error":"\ub85c\uadf8\uc778\uc774 \ud544\uc694\ud569\ub2c8\ub2e4. \ud398\uc774\uc9c0\ub97c \uc0c8\ub85c\uace0\uce68 \ud6c4 \ub2e4\uc2dc \ub85c\uadf8\uc778\ud558\uc138\uc694."}';
            exit;
        }
        $loginPage = _loginUrl();
        header('Location: ' . $loginPage);
        exit;
    }

    if (isset($_SESSION['last_act']) && (time() - $_SESSION['last_act']) > SESSION_TIMEOUT) {
        session_destroy();
        if (isAjax()) {
            while (ob_get_level() > 0) ob_end_clean();
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo '{"ok":false,"error":"\uc138\uc158\uc774 \ub9cc\ub8cc\ub418\uc5c8\uc2b5\ub2c8\ub2e4. \ub2e4\uc2dc \ub85c\uadf8\uc778\ud558\uc138\uc694."}';
            exit;
        }
        header('Location: ' . _loginUrl() . '?to=1');
        exit;
    }

    $_SESSION['last_act'] = time();
}

// ── 로그인 URL (호출 위치에 관계없이 정확한 경로) ──
function _loginUrl() {
    // config.php 위치 = 앱 루트
    // api/ 또는 panels/ 에서 호출하면 ../ 한 단계 위로
    $configDir  = str_replace('\\', '/', __DIR__);
    $scriptDir  = str_replace('\\', '/', dirname(realpath($_SERVER['SCRIPT_FILENAME'])));
    if ($scriptDir !== $configDir) {
        return '../login.php';
    }
    return 'login.php';
}

// ── JSON 출력 ──
function jsonOut($data, $code = 200) {
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// ── JSON 파일 읽기 ──
function readJson($path) {
    if (!file_exists($path)) return array();
    $raw = @file_get_contents($path);
    if ($raw === false || trim($raw) === '') return array();
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : array();
}

// ── JSON 파일 쓰기 ──
function writeJson($path, $data) {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    return file_put_contents($path, $json, LOCK_EX);
}

// ── 등급 계산 ──
function calcGrade($pv) {
    $pv = intval($pv);
    if ($pv >= 800000) return '플래티넘';
    if ($pv >= 560000) return '골드';
    if ($pv >= 320000) return '플러스';
    if ($pv >= 100000) return '베이직';
    return '미달성';
}

function higherGrade($a, $b) {
    $order = array('미달성'=>0,'회원'=>0,'베이직'=>1,'플러스'=>2,'골드'=>3,'플래티넘'=>4);
    $oa = isset($order[$a]) ? $order[$a] : 0;
    $ob = isset($order[$b]) ? $order[$b] : 0;
    return $oa >= $ob ? $a : $b;
}

// ── 업로드 이력 저장 ──
function saveUploadHistory($type, $fileName, $stats) {
    $history  = readJson(FILE_UPLOAD_HISTORY);
    $newEntry = array(
        'upload_id' => uniqid('upload_', true),
        'type'      => $type,
        'filename'  => $fileName,
        'timestamp' => time(),
        'stats'     => $stats,
    );
    array_unshift($history, $newEntry);
    $history = array_slice($history, 0, 100);
    writeJson(FILE_UPLOAD_HISTORY, $history);
    return $newEntry['upload_id'];
}
