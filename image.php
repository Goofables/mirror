<?php
#die($_SERVER["REQUEST_URI"]);
//print_r(strtotime("Wed, 24 Feb 2021 22:00:48 GMT"));
//die();
error_reporting(0);

function getMimeType($filename) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $filename);
    finfo_close($finfo);
    return $mime;
}

$name = str_replace("/", "", $_SERVER["REQUEST_URI"]);
$name = substr($name, 0, 1) . "/" . $name;
if (!file_exists($name)) {
    $name = "d/d550a48882df2e48c0505c98127e510c.jpeg";
    http_response_code(404);
}
$fp = fopen("storage/" . $name, 'rb');
if (!$fp) die(http_response_code(404));
//$image_info = getimagesize($name);
//$image_info["mime"]
header("Content-Type: " . getMimeType($name));
header("Content-Length: " . filesize($name));
header("Cache-Control: max-age=86400,min-fresh=3600,public");

fpassthru($fp);
