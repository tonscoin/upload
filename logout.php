<?php
require_once __DIR__ . '/config.php';
startSession();
session_destroy();
header('Location: login.php');
exit;
