<?php
    session_start();
    if (!isset($_SESSION["stp"])) {
        header("Location: logout.php");
    }
    if ($_POST["p"] && $_POST["post"]) {
        if (strpos($_POST["g"], "%7B") === FALSE) {
            $posts = json_decode(file_get_contents(($_POST["g"] == "all") ? "rootposts.txt" : "groups/" . $_POST["g"] . "/posts.txt"), true);
            $id = mt_rand(100000000, 999999999);
            $posts[$_POST["p"]]["comments"][$id] = array("content" => htmlspecialchars($_POST["post"]), "timestamp" => time(), "poster_id" => $_SESSION["uid"], "poster_name" => $_SESSION["name"], "id" => $id);
            file_put_contents(($_POST["g"] == "all") ? "rootposts.txt" : "groups/" . $_POST["g"] . "/posts.txt", json_encode($posts));
            if ($_POST["g"] == "all") {
                $db = include("sqlconnect.php");
                $content = $db->escape_string(strip_tags(preg_replace("/\r\n|\r|\n/", '<br>', $_POST["post"])));
                $poster_id = $_SESSION["uid"];
                $poster_name = $db->escape_string($_SESSION["name"]);
                $postno = $_POST["p"];
                $vals = array('isssi', &$id, &$content, &$poster_id, &$poster_name, &$postno);
                $db->prep("INSERT INTO rootcomments (id, content, timestamp, poster_id, poster_name, post) VALUES (?, ?, now(), ?, ?, ?)", $vals);
            }
        } else {
            $groups = json_decode(rawurldecode($_POST["g"]), true);
            foreach ($groups as $group) {
                $posts = json_decode(file_get_contents("groups/" . $group["id"] . "/posts.txt"), true);
                $id = mt_rand(100000000, 999999999);
                $posts[$_POST["p"]]["comments"][$id] = array("content" => htmlspecialchars($_POST["post"]), "timestamp" => time(), "poster_id" => $_SESSION["uid"], "poster_name" => $_SESSION["name"], "id" => $id);
                file_put_contents("groups/" . $group["id"] . "/posts.txt", json_encode($posts));
            }
        }
    }
    if ($_POST["r"])
        header("Location: " . $_POST["r"]);
?>