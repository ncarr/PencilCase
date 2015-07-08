<?php
    session_start();
    if (strpos($_GET["r"], "{") === FALSE) {
        $posts = json_decode(file_get_contents(($_GET["r"] == "all") ? "rootposts.txt" : "groups/" . $_GET["r"] . "/posts.txt"), true);
        $post = ($posts[$_GET["id"]]) ?: $posts[$_GET["p"]]["comments"][$_GET["c"]];
        if ($_SESSION["uid"] != $post["poster_id"] && $_SESSION["uid"] != "INSERT ENGINEER ID HERE") {
            header("Location: " . $_SESSION["stp"] . ".php");
            exit();
        }
        if ($_GET["id"]) {
            unset($posts[$_GET["id"]]);
            file_put_contents(($_GET["r"] == "all") ? "rootposts.txt" : "groups/" . $_GET["r"] . "/posts.txt", json_encode($posts));
            if ($_GET["r"] == "all") {
                $db = include("sqlconnect.php");
                $id = $_GET["id"];
                $vals = array('i', &$id);
                $db->prep("DELETE FROM rootposts WHERE id=?", $vals);
            }
            header("Location: " . $_GET["return"]);
        }
        if ($_GET["p"] && $_GET["c"]) {
            unset($posts[$_GET["p"]]["comments"][$_GET["c"]]);
            file_put_contents(($_GET["r"] == "all") ? "rootposts.txt" : "groups/" . $_GET["r"] . "/posts.txt", json_encode($posts));
            if ($_GET["r"] == "all") {
                $db = include("sqlconnect.php");
                $id = $_GET["c"];
                $postno = $_GET["p"];
                $vals = array('ii', &$id, &$postno);
                $db->prep("DELETE FROM rootcomments WHERE id=? AND post=?", $vals);
            }
            header("Location: " . $_GET["return"]);
        }
    } else {
        $groups = json_decode($_GET["r"], true);
        foreach ($groups as $group) {
            $posts = json_decode(file_get_contents("groups/" . $group["id"] . "/posts.txt"), true);
            $post = ($posts[$_GET["id"]]) ?: $posts[$_GET["p"]]["comments"][$_GET["c"]];
            if ($_SESSION["uid"] != $post["poster_id"] && $_SESSION["uid"] != "INSERT ENGINEER ID HERE") {
                header("Location: " . $_SESSION["stp"] . ".php");
                exit();
            }
            if ($_GET["id"]) {
                unset($posts[$_GET["id"]]);
                file_put_contents("groups/" . $group["id"] . "/posts.txt", json_encode($posts));
                header("Location: " . $_GET["return"]);
            }
            if ($_GET["p"] && $_GET["c"]) {
                unset($posts[$_GET["p"]]["comments"][$_GET["c"]]);
                file_put_contents("groups/" . $group["id"] . "/posts.txt", json_encode($posts));
                header("Location: " . $_GET["return"]);
            }
        }
    }
?>