<?php
$request	= json_decode(file_get_contents("php://input"), true);
$chunk		= $request['chunk'];
$order		= $request['order'];
$filename	= $request['filename'];
$ext		= pathinfo($filename, PATHINFO_EXTENSION);

if (!in_array($ext, ['webm', 'mp4'])) {
	exit("Extension $ext non allowed");
}

$binarydata = pack("C*", ...$chunk);
$filePath = $filename;
$out = fopen("{$filePath}", $order == 0 ? "wb" : "ab");
if ($out) {
	fwrite($out, $binarydata);
	fclose($out);
}