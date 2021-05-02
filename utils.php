<?php
declare(strict_types=1);
//error_reporting(0);

/**
 * @param $headers
 * @return bool
 */
function decode_mime_headers($headers) {
    $mime = "";
    foreach ($headers as $header)
        if (strpos(strtolower($header), "content-type:") !== false) {
            $mime = substr($header, 14);
            break;
        }
    return decode_mime($mime);
}

/**
 * @param $mime
 * @return false|string
 */
function decode_mime($mime) {
    switch ($mime) {
        case "image/bmp":
            return ".bmp";
        case "image/gif":
            return ".gif";
        case "image/vnd.microsoft.icon":
            return ".ico";
        case "image/jpeg":
            return ".jpg";
        case "image/png":
            return ".png";
        case "image/tiff":
            return ".tif";
        case "image/webp":
            return ".webp";
        case "video/mpeg":
            return ".mpeg";
        case "video/mp4":
            return ".mp4";
        case "video/ogg":
            return ".ogv";
        case "video/mp2t":
            return ".ts";
        case "audio/3gpp":
        case "video/3gpp":
            return ".3gp";
        case "audio/3gpp2":
        case "video/3gpp2":
            return ".3g2";
        case "audio/aac":
            return ".aac";
        case "audio/midi":
        case "audio/x-midi":
            return ".mid";
        case "audio/mpeg":
            return ".mp3";
        case "audio/ogg":
            return ".oga";
        case "audio/opus":
            return ".opus";
        case "audio/wav":
            return ".wav";
        case "audio/webm":
            return ".weba";
    }
    return false;
}

/**
 * @param $message
 * @param int $code
 */
function stop($message, $code = 400) {
    http_response_code($code);
    die($message);
}

/**
 * @param $name
 * @param $connection
 * @param null $album
 * @return bool
 */
function is_in_db($name, $connection, $album = null): bool {
    $statement = $connection->prepare("SELECT checksum,album FROM mirror.image WHERE checksum = ?");
    $statement->bind_param("s", $name);
    $statement->execute();
    if ($result = $statement->get_result()) if ($content = $result->fetch_assoc()) {
        if ($album != null and $album != $content["album"]) {
            $statement = $connection->prepare("UPDATE mirror.image SET album = ? WHERE checksum = ?;");
            $statement->bind_param("ss", $album, $content["checksum"]);
            $statement->execute();
        }
        return true;
    }
    return false;
}

/**
 * @param $connection
 * @param $name
 * @param $url
 * @param $source
 * @param $size
 * @param $album
 */
function add_to_db($connection, $name, $url, $source, $size, $album = null) {
    $statement = $connection->prepare("INSERT INTO mirror.image (checksum, url, source, size, album) VALUES (?, ?, ?, ?, ?)");
    $statement->bind_param("sssss", $name, $url, $source, $size, $album);
    $statement->execute();
}

/**
 * @return mysqli
 */
function db_login(): mysqli {
    $connection = "";
    try {
        $mysqli_creds = json_decode(file_get_contents("db.json"));
        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        @$connection = new mysqli('127.0.0.1', $mysqli_creds->username, $mysqli_creds->password, 'mirror');
        $mysqli_creds = null;
        if ($connection->connect_errno) throw new Exception("Error: " . $connection->connect_errno . ". " . $connection->connect_error . "");
    } catch (Throwable $e) {
        stop("Server Error!<br>I lost my database ¯\_(ツ)_/¯", 500);
    }
    return $connection;
}

/**
 * @param $image
 * @param $ext
 * @return bool
 */
function gen_thumbnail($image, $ext): bool {
    return false;
    if (!in_array($ext, [".jpg", ".jpeg", ".png", ".gif"])) return false;
    $file = "storage/" . substr($image, 0, 1) . "/" . $image;
    list($width, $height) = getimagesize($file);
//    if ($width < 500 && $height < 500) return false;
    $large = max($width / 256, $height / 256);
//    if ($large < 2) return false;
    $thumb_width = $width / $large;
    $thumb_height = $height / $large;


    $thumb = "storage/thumbs/" . substr($image, 0, 1) . "/" . $image;

    $thumb_create = imagecreatetruecolor($thumb_width, $thumb_height);
    switch ($ext) {
        case 'png':
            $source = imagecreatefrompng($file);
            break;
        case 'gif':
            $source = imagecreatefromgif($file);
            break;
        case 'jpg' || 'jpeg':
        default:
            $source = imagecreatefromjpeg($file);
    }
    imagecopyresized($thumb_create, $source, 0, 0, 0, 0, $thumb_width, $thumb_height, $width, $height);
    switch ($ext) {
        case 'png':
            imagepng($thumb_create, $thumb, 100);
            break;

        case 'gif':
            imagegif($thumb_create, $thumb);
            break;
        case 'jpg' || 'jpeg':
        default:
            imagejpeg($thumb_create, $thumb, 100);
    }

    return true;
}