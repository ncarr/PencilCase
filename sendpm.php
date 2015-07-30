<?php
    session_start();
    if (!isset($_SESSION["stp"])) {
        header("Location: logout.php");
    }
    if ($_POST["w"] && $_POST["post"]) {
        $db = include("sqlconnect.php");
        $id = mt_rand(100000000, 999999999);
        $content = strip_tags(preg_replace("/\r\n|\r|\n/", '<br>', $_POST["post"]));
        $poster_id = $_SESSION["uid"];
        $receiver = $_POST["w"];
        $vals = array('isss', &$id, &$content, &$poster_id, &$receiver);
        $db->prep("INSERT INTO pms (id, content, timestamp, sender, receiver) VALUES (?, ?, now(), ?, ?)", $vals);
        $receiver = $_POST["w"];
        $vals2 = array('s', &$receiver);
        $db->prep("INSERT INTO pmu (receiver, timestamp) VALUES (?, now())", $vals2);
    }
    if ($_POST["r"])
        header("Location: pm.php");
?>