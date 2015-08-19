<?php
    session_start();
    if (!isset($_SESSION["stp"])) {
        header("Location: logout.php");
    }
    if ((($_SESSION["uid"] == "INSERT ENGINEER USER ID HERE" || $_POST["group"] != "all") && ($_SESSION["uid"] == "INSERT ENGINEER USER ID HERE" || $_POST["group"] != "engineers")) && $_POST["post"] && $_POST["group"] != "all") {
        $posts = json_decode(file_get_contents(($_POST["group"] == "engineers") ? "rootposts.txt" : "groups/" . $_POST["group"] . "/posts.txt"), true);
        $id = mt_rand(100000000, 999999999);
        $posts[$id] = array("content" => strip_tags(preg_replace("/\r\n|\r|\n/", '<br>', $_POST["post"]), "<p><br><br/><a>"), "timestamp" => time(), "poster_id" => $_SESSION["uid"], "poster_name" => $_SESSION["name"], "receivers" => ($_POST["group"] == "engineers") ? "all": $_POST["group"], "id" => $id);
        if ($_SESSION["stp"] == "teacher")
            $post["teacher"] = TRUE;
        file_put_contents(($_POST["group"] == "engineers") ? "rootposts.txt" : "groups/" . $_POST["group"] . "/posts.txt", json_encode($posts));
        if ($_POST["group"] == "engineers") {
            $db = include("sqlconnect.php");
            $content = strip_tags(preg_replace("/\r\n|\r|\n/", '<br>', $_POST["post"]), "<p><br><br/><a>");
            $poster_id = $_SESSION["uid"];
            $poster_name = $_SESSION["name"];
            $receivers = "all";
            $priority = 0;
            $teacher = ($_SESSION["stp"] == "teacher");
            $vals = array('issssdi', &$id, &$content, &$poster_id, &$poster_name, &$receivers, &$priority, &$teacher);
            $post = $db->prep("INSERT INTO rootposts (id, content, timestamp, poster_id, poster_name, receivers, priority, teacher) VALUES (?, ?, now(), ?, ?, ?, ?, ?)", $vals);
        }
    }
    $userdata = json_decode(file_get_contents("users/" . $_SESSION["uid"] . ".txt"), true);
    // Decode user data array to find groups
    $groups = $userdata["groups"];
    if ($_POST["post"] && $_POST["group"] == "all") {
        $id = mt_rand(100000000, 999999999);
        $post = array("content" => strip_tags(preg_replace("/\r\n|\r|\n/", '<br>', $_POST["post"]), "<p><br><br/><a>"), "timestamp" => time(), "poster_id" => $_SESSION["uid"], "poster_name" => $_SESSION["name"], "receivers" => $groups, "id" => $id);
        if ($_SESSION["stp"] == "teacher")
            $post["teacher"] = TRUE;
        foreach ($groups as $group) {
            $posts = json_decode(file_get_contents("groups/" . $group["id"] . "/posts.txt"), true);
            $posts[$id] = $post;
            file_put_contents("groups/" . $group["id"] . "/posts.txt", json_encode($posts));
        }
    }
    if ($_POST["r"])
        header("Location: " . $_GET["r"]);
?>