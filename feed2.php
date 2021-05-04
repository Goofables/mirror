<?php
declare(strict_types=1);
require "utils.php";

$start = 0;
$amount = 500;
if (isset($_GET["start"])) $start = (int)$_GET["start"];
$connection = db_login();
$args = "";
$rev = (isset($_GET["rev"]) ? "ASC" : "DESC");

$count = 0;
$album = (isset($_GET["album"]) ? htmlspecialchars($_GET["album"]) : "r/Aww");
if ($album === "All") {
    $countStatement = $connection->prepare("SELECT COUNT(DISTINCT checksum) AS C FROM mirror.image");
    $statement = $connection->prepare("SELECT DISTINCT checksum,album,size FROM mirror.image ORDER BY TIME " . $rev . ", checksum LIMIT ? OFFSET ?");
    $statement->bind_param("ss", $amount, $start);
    $args = "album=All&";
} else {
    $countStatement = $connection->prepare("SELECT COUNT(DISTINCT checksum) AS C FROM mirror.image WHERE album = ?");
    $countStatement->bind_param("s", $album);
    $statement = $connection->prepare("SELECT DISTINCT checksum,size FROM mirror.image WHERE album = ? ORDER BY TIME " . $rev . ", checksum LIMIT ? OFFSET ?");
    $statement->bind_param("sss", $album, $amount, $start);
    $args = "album=" . $album . "&";
}
$countStatement->execute();
$count = $countStatement->get_result()->fetch_assoc()["C"];
$statement->execute();

$result = $statement->get_result();
//
//if (isset($_GET["all"])) {
//   $all = "all&";
//}


$amt = 0;
if ($result == null || $result->num_rows === 0) {
    $connection->close();
    if ($start >= $amount) echo '<a href="?' . $args . 'start=' . ($start - $amount) . '"><< Back (Image ' . ($start - $amount) . '-' . $start . ')</a>';
    else echo "X";
    stop("No Data");
}

?>
<style>
    .image {
        width: 10%;
        vertical-align: top;
    }

    .image p {
        margin-top: 0;
        margin-bottom: 10px;
    }

    img {
        width: 100%;
        min-height: 100px;
    }


    @media all and (max-width: 900px) {
    }

</style>
<script>
    function video(num) {
        // console.log(document.getElementById("image" + num).childNodes[5].src);
        document.getElementById("image" + num).innerHTML = '<video style="width: 100%;" controls ><source onerror="link(' + num + ')" src="' + document.getElementById("image" + num).children[0].src + '">Error</video>'
    }

    function link(num) {
        // console.log(document.getElementById("image" + num).children);
        let link = document.getElementById("image" + num).children[0].children[0].src;
        document.getElementById("image" + num).innerHTML = '<a href="' + link + '">' + link.substring(link.lastIndexOf('/') + 1) + '</a>';
    }
</script>
<table style="width: 100%;">
    <tbody id="img-table">
    <tr id="Head">
        <td><?php if ($start >= $amount) echo '<a href="?' . $args . 'start=' . ($start - $amount) . '"><< Back (Image ' . ($start - $amount) . '-' . $start . ')</a>';
            else echo "X" ?></td>
        <td></td>
        <td></td>
        <td></td>
        <td colspan="2" style="text-align: center;">
            Image <?php echo $start . "-" . ($start + $amount) . " / " . $count; ?></td>
        <td></td>
        <td></td>
        <td></td>
        <td style="text-align: right;"><?php echo '<a href="?' . $args . 'start=' . ($start + $amount) . '">Next (Image ' . ($start + $amount) . '-' . ($start + 2 * $amount) . ') >></a>'; ?></td>
        <?php
        while ($row = $result->fetch_assoc()) {
            if ($amt % 10 == 0) echo '</tr><tr id="tr' . $amt / 10 . '">';
            $amt++;
            ?>
            <td class="image">
                <a href="https://i.mxsmp.com/<?php echo $row["checksum"] ?>"><?php echo ($start + $amt) . ' : ' . substr($row["checksum"], 0, 6) . " " . round($row["size"] / 1024) . "KB"; ?></a>
                <p id="image<?php echo $amt ?>">
                    <!---<button style="width: 100%" onclick="del(<?php echo $amt ?>)"><?php echo $amt ?></button><br>--->
                    <?php
                    //                    if (strpos($row["url"], ".mp4") !== false)
                    //                        echo '<video style="width: 100%" controls><source src="' . $row["url"] . '">Error</video>';
                    //                    else?>
                    <img loading="lazy" alt="Error"
                         src="https://i.mxsmp.com/<?php echo $row["checksum"]; ?>"
                         title="<?php if ($album === "All") echo($row["album"] === null ? "No Album" : $row["album"]); ?>">
                </p></td>
            <?php
        }
        ?>

    </tr>
    <tr>
        <td><?php if ($start >= $amount) echo '<a href="?' . $args . 'start=' . ($start - $amount) . '"><< Back (Image ' . ($start - $amount) . '-' . $start . ')</a>';
            else echo "X" ?></td>
        <td></td>
        <td></td>
        <td></td>
        <td colspan="2" style="text-align: center;">Image <?php echo $start . "-" . ($start + $amount); ?></td>
        <td></td>
        <td></td>
        <td></td>
        <td style="text-align: right;">
            <?php if ($amt === $amount) echo '<a href="?' . $args . 'start=' . ($start + $amount) . '">Next (Image ' . ($start + $amount) . '-' . ($start + 2 * $amount) . ') >></a>';
            else echo "No More!" ?></td>
    </tr>
    </tbody>
</table>