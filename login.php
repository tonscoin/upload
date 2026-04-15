<?php
require_once __DIR__ . '/config.php';
startSession();

if (!empty($_SESSION[SESSION_KEY])) { header('Location: index.php'); exit; }

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim(isset($_POST['id']) ? $_POST['id'] : '');
    $pw = trim(isset($_POST['pw']) ? $_POST['pw'] : '');
    $ok = ($id === ADMIN_ID) && password_verify($pw, ADMIN_PW_HASH);
    if ($ok) {
        $_SESSION[SESSION_KEY] = true;
        $_SESSION['last_act']  = time();
        header('Location: index.php'); exit;
    }
    $err = '아이디 또는 비밀번호가 올바르지 않습니다.';
}
$timeout = isset($_GET['to']);
?><!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>로그인 · <?php echo SYSTEM_TITLE; ?></title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;700;900&display=swap');
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Noto Sans KR',sans-serif;background:#f0f2f5;min-height:100vh;display:flex;align-items:center;justify-content:center}
.box{width:360px;background:#fff;border:1px solid #dee2e6;border-radius:16px;padding:44px 36px;box-shadow:0 8px 32px rgba(0,0,0,.08)}
.logo{text-align:center;margin-bottom:36px}
.logo .tag{display:inline-block;background:linear-gradient(135deg,#1a56db,#0ea5e9);color:#fff;font-size:10px;font-weight:700;padding:3px 12px;border-radius:20px;letter-spacing:2px;margin-bottom:12px}
.logo h1{color:#212529;font-size:20px;font-weight:900}
.logo p{color:#868e96;font-size:12px;margin-top:6px}
label{display:block;color:#495057;font-size:11px;font-weight:700;letter-spacing:1px;margin-bottom:7px;margin-top:16px}
input{width:100%;padding:12px 14px;background:#f8f9fa;border:1px solid #dee2e6;border-radius:9px;color:#212529;font-size:14px;font-family:inherit;outline:none;transition:.2s}
input:focus{border-color:#1a56db;background:#fff;box-shadow:0 0 0 3px rgba(26,86,219,.1)}
.btn{width:100%;margin-top:24px;padding:14px;background:linear-gradient(135deg,#1a56db,#0ea5e9);border:none;border-radius:9px;color:#fff;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit}
.btn:hover{opacity:.88}
.err{background:rgba(211,47,47,.08);border:1px solid rgba(211,47,47,.25);color:#d32f2f;padding:10px 14px;border-radius:8px;font-size:12px;margin-bottom:4px}
.warn{background:rgba(245,124,0,.08);border:1px solid rgba(245,124,0,.25);color:#f57c00;padding:10px 14px;border-radius:8px;font-size:12px;margin-bottom:4px}
</style>
</head>
<body>
<div class="box">
  <div class="logo">
    <div class="tag">MLM SYSTEM</div>
    <h1><?php echo SYSTEM_TITLE; ?></h1>
    <p>내부 관리자 전용 · <?php echo date('Y'); ?></p>
  </div>
  <?php if ($timeout): ?><div class="warn" style="margin-bottom:16px">세션이 만료되었습니다. 다시 로그인하세요.</div><?php endif; ?>
  <?php if ($err): ?><div class="err" style="margin-bottom:16px"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
  <form method="POST" autocomplete="on">
    <label for="id">아이디</label>
    <input id="id" name="id" type="text" placeholder="아이디 입력" autocomplete="username" value="<?php echo htmlspecialchars(isset($_POST['id']) ? $_POST['id'] : ''); ?>">
    <label for="pw">비밀번호</label>
    <input id="pw" name="pw" type="password" placeholder="비밀번호 입력" autocomplete="current-password">
    <button class="btn" type="submit">로그인</button>
  </form>
</div>
</body>
</html>
