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

$amt = 0;
if ($result == null || $result->num_rows === 0) {
    $connection->close();
    if ($start >= $amount) echo '<a href="?' . $args . 'start=' . ($start - $amount) . '"><< Back (Image ' . ($start - $amount) . '-' . $start . ')</a>';
    else echo "X";
    stop("No Data");
}

?>
<head>
    <title>Images</title>
    <style>
        .navbar {
            display: flex;
            flex-wrap: nowrap;
            background: #dddddd;
            width: 100%;
            padding: 7px;
            box-sizing: border-box;
            border-radius: 5px;
        }

        .left {
            width: 100%;
        }

        .middle {
            text-align: center;
            width: 100%;
        }

        .right {
            text-align: right;
            width: 100%;
        }

        .allImages {
            display: flex;
            flex-wrap: wrap;
        }

        img {
            max-width: 100%;
            max-height: 100%;
        }

        .image {
            width: 200px;
            min-height: 200px;
            /*height: 200px;*/
            /*background-color: violet;*/
            margin: 5px;
            flex-grow: 1;
        }

        .image:after {
            content: '';
            display: block;
            /*padding-bottom: 100%;*/
        }

        .image:nth-last-child(9) ~ div {
            height: 0;
        }


        h1 {
            font-size: large;
            margin: 0;
        }
    </style>
</head>
<body>
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
<div class="navbar">
    <div class="left">
        <?php if ($start >= $amount) echo '<a href="?' . $args . 'start=' . ($start - $amount) . '"><< Back (Image ' . ($start - $amount) . '-' . $start . ')</a>';
        else echo "X" ?>
    </div>
    <div class="middle">
        <h1>Image <?php echo $start . "-" . ($start + $amount) . " / " . $count; ?></h1>
    </div>
    <div class="right">
        <?php echo '<a href="?' . $args . 'start=' . ($start + $amount) . '">Next (Image ' . ($start + $amount) . '-' . ($start + 2 * $amount) . ') >></a>'; ?>
    </div>
</div>

<div class="allImages">
    <?php
    while ($row = $result->fetch_assoc()) {
        if ($amt % 10 == 0) echo '</tr><tr id="tr' . $amt / 10 . '">';
        $amt++;
        ?>
        <div class="image">
            <a href="https://i.mxsmp.com/<?php echo $row["checksum"] ?>"><?php echo ($start + $amt) . ' : ' . substr($row["checksum"], 0, 6) . " " . round($row["size"] / 1024) . "KB"; ?></a>
            <!---<button style="width: 100%" onclick="del(<?php echo $amt ?>)"><?php echo $amt ?></button><br>--->
            <?php
            //                    if (strpos($row["url"], ".mp4") !== false)
            //                        echo '<video style="width: 100%" controls><source src="' . $row["url"] . '">Error</video>';
            //                    else?>
            <br>
            <img id="image<?php echo $amt ?>" loading="lazy" alt="Error"
                 src="https://i.mxsmp.com/<?php echo $row["checksum"]; ?>"
                 title="<?php if ($album === "All") echo($row["album"] === null ? "No Album" : $row["album"]); ?>">
        </div>
        <?php
    }
    ?>

</div>
<div class="navbar">
    <div class="left"><?php if ($start >= $amount) echo '<a href="?' . $args . 'start=' . ($start - $amount) . '"><< Back (Image ' . ($start - $amount) . '-' . $start . ')</a>';
        else echo "X" ?>
    </div>
    <div class="middle">Image <?php echo $start . "-" . ($start + $amount); ?></div>
    <div class="right">
        <?php if ($amt === $amount) echo '<a href="?' . $args . 'start=' . ($start + $amount) . '">Next (Image ' . ($start + $amount) . '-' . ($start + 2 * $amount) . ') >></a>';
        else echo "No More!" ?></div>
</div>
</body>