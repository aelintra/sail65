<?php
/**
 * Session-backed file download / inline stream.
 * Used for call recordings (origrecs), logs, pcap, greetings, voicemail.
 */
require_once $_SERVER["DOCUMENT_ROOT"] . "../php/srksessions/common.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "../php/srkHelperClass";

$wantDl = !empty($_GET['dl']);
$inlineReq = !empty($_GET['inline']) || !empty($_GET['play']);

if (empty($_SESSION['user'])) {
	// Media players can't follow an HTML login redirect
	$accept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
	if ($inlineReq || isset($_SERVER['HTTP_RANGE']) ||
		stripos($accept, 'audio/') !== false) {
		http_response_code(401);
		exit;
	}
	header("Location: /php/sarklogin/main.php");
	exit;
}

$raw = isset($_GET['dfile']) ? strip_tags($_GET['dfile']) : '';
if ($raw === '') {
	http_response_code(400);
	exit('Missing file');
}

// Greetings historically pass dtype=greet with a full path or basename
if (isset($_GET['dtype']) && $_GET['dtype'] === 'greet' && $raw[0] !== '/') {
	$raw = '/usr/share/asterisk/sounds/' . basename($raw);
}

$fullPath = realpath($raw);
if ($fullPath === false || !is_file($fullPath)) {
	http_response_code(404);
	exit('Not found');
}

$allowedRoots = array(
	'/opt/sark/media/recordings',
	'/var/spool/asterisk/voicemail',
	'/var/log',
	'/usr/share/asterisk/sounds',
);
$ok = false;
foreach ($allowedRoots as $root) {
	$rootReal = realpath($root);
	if ($rootReal !== false && strpos($fullPath, $rootReal . DIRECTORY_SEPARATOR) === 0) {
		$ok = true;
		break;
	}
}
if (!$ok) {
	http_response_code(403);
	exit('Forbidden');
}

$helper = new helper;
$statOut = array();
exec('stat -c %a ' . escapeshellarg($fullPath), $statOut);
$fileperms = isset($statOut[0]) ? trim($statOut[0]) : '';
if (!is_readable($fullPath)) {
	$helper->request_syscmd("/bin/chmod +r " . escapeshellarg($fullPath));
}

$fsize = filesize($fullPath);
$path_parts = pathinfo($fullPath);
$ext = strtolower(isset($path_parts['extension']) ? $path_parts['extension'] : '');
$basename = $path_parts['basename'];

$mimes = array(
	'wav' => 'audio/wav',
	'mp3' => 'audio/mpeg',
	'gsm' => 'audio/x-gsm',
	'ulaw' => 'audio/basic',
	'alaw' => 'audio/basic',
	'pcap' => 'application/vnd.tcpdump.pcap',
	'cap' => 'application/vnd.tcpdump.pcap',
	'txt' => 'text/plain',
	'log' => 'text/plain',
);
$ctype = isset($mimes[$ext]) ? $mimes[$ext] : 'application/octet-stream';
$isRecording = (strpos($fullPath, '/opt/sark/media/recordings/') === 0)
	&& (strpos($ctype, 'audio/') === 0);

// Explicit dl=1 → attachment. Call-recording audio streams inline so jPlayer
// works even when the client omits inline=1 (cached JS / href fallback).
$inline = !$wantDl && ($inlineReq || $isRecording);

if ($inline) {
	$disposition = 'inline';
} else {
	$disposition = 'attachment';
	$ctype = 'application/octet-stream';
}

header('Cache-Control: private');
header('Content-Type: ' . $ctype);
header('Content-Disposition: ' . $disposition . '; filename="' . str_replace('"', '', $basename) . '"');
header('Accept-Ranges: bytes');

$start = 0;
$end = $fsize - 1;
if ($inline && isset($_SERVER['HTTP_RANGE']) &&
	preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)) {
	if ($m[1] !== '') {
		$start = intval($m[1]);
	}
	if ($m[2] !== '') {
		$end = intval($m[2]);
	}
	if ($end >= $fsize) {
		$end = $fsize - 1;
	}
	if ($start > $end || $start < 0) {
		http_response_code(416);
		header("Content-Range: bytes */$fsize");
		exit;
	}
	http_response_code(206);
	header("Content-Range: bytes $start-$end/$fsize");
	header('Content-Length: ' . ($end - $start + 1));
} else {
	header('Content-Length: ' . $fsize);
}

$fd = fopen($fullPath, 'rb');
if (!$fd) {
	http_response_code(500);
	exit('Open failed');
}
if ($start > 0) {
	fseek($fd, $start);
}
$left = $end - $start + 1;
while ($left > 0 && !feof($fd)) {
	$chunk = fread($fd, min(8192, $left));
	if ($chunk === false) {
		break;
	}
	echo $chunk;
	$left -= strlen($chunk);
}
fclose($fd);

if ($fileperms !== '' && ctype_digit($fileperms)) {
	$helper->request_syscmd("/bin/chmod " . escapeshellarg($fileperms) . " " . escapeshellarg($fullPath));
}
exit;
