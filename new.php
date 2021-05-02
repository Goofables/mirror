<?php
declare(strict_types=1);
require "utils.php";

// Parse the url
$url = $_SERVER["REQUEST_URI"];
if (substr($url, 0, 10) === "/http%3A//" || substr($url, 0, 11) === "/https%3A//") $url = urldecode($url);
if (!(substr($url, 0, 8) === "/http://" || substr($url, 0, 9) === "/https://")) stop("That doesnt look like a valid url!");
$url = substr($url, 1);
$host = explode("/", explode("//", $url)[1])[0];
if (strpos($host, "imgur.com") !== false && strpos(substr($url, -5), ".") === false) $url .= ".jpg";

// login to db
$connection = db_login();


$link = $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["HTTP_HOST"] . "/";


// is url in db?
$redownload = false;
$statement = $connection->prepare("SELECT checksum FROM mirror.image WHERE url = ?;");
$statement->bind_param("s", $url);
$statement->execute();
if ($result = $statement->get_result())
    if ($result->num_rows > 0) {
        $name = $result->fetch_assoc()["checksum"];
        if (filesize("storage/" . substr($name, 0, 1) . "/" . $name) > 0) {
            header("State: Old");
//            header("SZ: " . filesize("storage/" . substr($name, 0, 1) . "/" . $name));
//            header("LNK: " . "storage/" . substr($name, 0, 1) . "/" . $name);
            header("Location: " . $link . $name);
            stop($link . $name, 301);
        } else $redownload = true;
    }

// is url local?
$host = gethostbyname($host);
if (substr($host, 0, 4) === "127." || substr($host, 0, 3) === "10." || $host === gethostbyname("random.mxsmp.com"))
    stop("That's not very nice. :(", 403);


// Download and checksum file
$buff = "";
$ext = "";
$len = 0;
$name = "";
try {
    /** @noinspection PhpUsageOfSilenceOperatorInspection */
    $request = @fopen($url, 'rb');
    if (!$request) stop("That image does not like me :(");
    $b2b = sodium_crypto_generichash_init();
    $size = -1;
    foreach ($http_response_header as $header)
        if (strpos(strtolower($header), "content-length:") === 0) {
            $size = (int)substr($header, 15);
//            echo strpos(strtolower($header), "content-length:")."<br>";
//            echo substr($header, 1) . "<br>";
            break;
        }

    while (!feof($request)) {
        $tmp = fread($request, 1024 * 8);
        if ($buff === "") {
            $image_info = getimagesizefromstring($tmp);

            if ($image_info) $ext = image_type_to_extension($image_info[2]);
            elseif (!$ext = decode_mime_headers($http_response_header))
                stop("Unsupported image type :(", 415);

        }
        if (!$tmp) break;
        $len += strlen($tmp);
//        print_r($http_response_header);
        if ($len > $size) stop("Something is wrong with the image :( " . $len . "/" . $size);
        if ($len > 100000000) stop("Image too big :(", 413);
        sodium_crypto_generichash_update($b2b, $tmp);
        $buff .= $tmp;
    }
    fclose($request);
    if ($ext == "" || $len < 1) stop("Something is wrong with the image :(");
    $b2b = bin2hex(sodium_crypto_generichash_final($b2b, 16));
    $name = $b2b . $ext;
} catch (Exception $e) {
    stop("Lmao what?", 500);
}

// is in db
$in_db = is_in_db($name, $connection);


// get album
if (isset(getallheaders()["Album"])) $album = getallheaders()["Album"]; else $album = null;

// add to db
add_to_db($connection, $name, $url, $_SERVER["REMOTE_ADDR"], $len, $album);


// save file
if (!$in_db || $redownload) {
    $folder = "storage/" . substr($name, 0, 1);
    $new_file = fopen($folder . "/" . $name, 'wb');
    fwrite($new_file, $buff);
    fclose($new_file);
    if ($len > 100000)
        gen_thumbnail($name, $ext);
}

$link .= $name;
// return link
header("Location: " . $link);
header("State: " . ($in_db ? "Alt" : "New"));
stop($link, 301);

/*
 * cat /etc/mime.types
 */