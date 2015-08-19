<?php
    session_start();
    if (strpos($_POST["r"], "%7B") === FALSE) {
        $posts = json_decode(file_get_contents(($_POST["r"] == "all") ? "rootposts.txt" : "groups/" . $_POST["r"] . "/posts.txt"), true);
        if ($posts[$_POST["id"]])
            $post = &$posts[$_POST["id"]];
        else
            $post = &$posts[$_POST["p"]]["comments"][$_POST["c"]];
        if ($_SESSION["uid"] != $post["poster_id"]) {
            header("Location: " . $_SESSION["stp"] . ".php");
            exit();
        }
        if ($_POST["id"]) {
            $post["content"] = strip_tags(preg_replace("/\r\n|\r|\n|<\/p><p>/", '<br>', $_POST["content"]), "<p><br><br/><a>");
            file_put_contents(($_POST["r"] == "all") ? "rootposts.txt" : "groups/" . $_POST["r"] . "/posts.txt", json_encode($posts));
            if ($_POST["r"] == "all") {
                $db = include("sqlconnect.php");
                $content = $post["content"];
                $id = $_POST["id"];
                $vals = array('si', &$content, &$id);
                $db->prep("UPDATE rootposts SET content=? WHERE id=?", $vals);
            }
        }
        if ($_POST["p"] && $_POST["c"]) {
            $post["content"] = htmlspecialchars(strip_tags(htmlspecialchars_decode($_POST["content"])));
            file_put_contents(($_POST["r"] == "all") ? "rootposts.txt" : "groups/" . $_POST["r"] . "/posts.txt", json_encode($posts));
            if ($_POST["r"] == "all") {
                $db = include("sqlconnect.php");
                $content = $post["content"];
                $id = $_POST["c"];
                $postno = $_POST["p"];
                $vals = array('sii', &$content, &$id, &$postno);
                $db->prep("UPDATE rootcomments SET content=? WHERE id=? AND post=?", $vals);
            }
        }
    } else {
        $groups = json_decode(rawurldecode($_POST["r"]), true);
        foreach ($groups as $group) {
            $posts = json_decode(file_get_contents("groups/" . $group["id"] . "/posts.txt"), true);
            if ($posts[$_POST["id"]])
                $post = &$posts[$_POST["id"]];
            else
                $post = &$posts[$_POST["p"]]["comments"][$_POST["c"]];
            if ($_SESSION["uid"] != $post["poster_id"]) {
                header("Location: " . $_SESSION["stp"] . ".php");
                exit();
            }
            if ($_POST["id"]) {
                $post["content"] = strip_tags(preg_replace("/\r\n|\r|\n|<\/p><p>|<\/div><div>/", '<br>', $_POST["content"]), "<p><br><br/><a>");
                echo json_encode($posts) . "onjtrshi";
                file_put_contents("groups/" . $group["id"] . "/posts.txt", json_encode($posts));
            }
            if ($_POST["p"] && $_POST["c"]) {
                $post["content"] = htmlspecialchars(strip_tags(htmlspecialchars_decode($_POST["content"])));
                echo json_encode($posts);
                file_put_contents("groups/" . $group["id"] . "/posts.txt", json_encode($posts));
            }
        }
    }
?>