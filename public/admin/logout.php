<?php
require_once __DIR__ . '/../../includes/environment.php';
require_once __DIR__ . '/../../includes/admin_auth.php';

session_start();
$_SESSION = array();
session_destroy();
header('Location: /admin/login.php');
exit;
