<?php
    if ($_POST["id"]) {
        session_start();
        $db = include("sqlconnect.php");
        $mysqli = $db->manual();
        $stmt = $mysqli->prepare("SELECT sender, receiver FROM pms WHERE id = ?");
        $stmt->bind_param('i', $id);
        $id = $_POST["id"];
        $stmt->execute();
        $stmt->bind_result($sender, $receiver);
        $stmt->store_result();
        if ($stmt->num_rows == 1)
            $stmt->fetch();
        else {
            header("Location: pm.php?editexistenceerr");
            exit();
        }
        $stmt->close();
        $mysqli->close();
        if ($_SESSION["uid"] != $sender && $_SESSION["uid"] != "INSERT ENGINEER ID HERE") {
            header("Location: pm.php?editautherr");
            exit();
        }
        $id = $_POST["id"];
        $content = strip_tags(preg_replace("/<\/p>|<p>/", '', preg_replace("/\r\n|\r|\n|<\/p><p>/", '<br>', $_POST["content"])), "<p><br><br/><a>");
        $vals = array('si', &$content, &$id);
        $db->prep("UPDATE pms SET content=? WHERE id=?", $vals);
        $vals2 = array('s', &$receiver);
        $db->prep("INSERT INTO pmu (receiver, timestamp) VALUES (?, now())", $vals2);
    }
?>