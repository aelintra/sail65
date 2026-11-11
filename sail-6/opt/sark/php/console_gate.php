<?php
/**
 * SAIL session gate for /console (shellinabox).
 * Issues a short-lived signed cookie, then redirects to the ProxyPass URL.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '../php/srksessions/common.php';

if (empty($_SESSION['user'])) {
	header('Location: /php/sarklogin/main.php');
	exit;
}

$keyfile = '/opt/sark/cache/console.hmac.key';
if (!is_readable($keyfile)) {
	http_response_code(503);
	exit('Console gate not configured (missing HMAC key)');
}

$key = trim(file_get_contents($keyfile));
if ($key === '') {
	http_response_code(503);
	exit('Console gate not configured (empty HMAC key)');
}

$ttl = 3600;
$exp = time() + $ttl;
$sig = hash_hmac('sha256', (string) $exp, $key);
$val = $exp . '.' . $sig;

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
setcookie('SAIL_CONSOLE', $val, array(
	'expires' => $exp,
	'path' => '/',
	'secure' => $secure,
	'httponly' => true,
	'samesite' => 'Lax',
));

header('Cache-Control: no-store');
header('Location: /console/');
exit;
