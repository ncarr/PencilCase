<?php
    if ($_GET["id"]) {
        session_start();
        $db = include("sqlconnect.php");
        $mysqli = $db->manual();
        $stmt = $mysqli->prepare("SELECT sender, receiver FROM pms WHERE id = ?");
        $stmt->bind_param('i', $id);
        $id = $_GET["id"];
        $stmt->execute();
        $stmt->bind_result($sender, $receiver);
        $stmt->store_result();
        if ($stmt->num_rows == 1)
            $stmt->fetch();
        else {
            header("Location: pm.php?delexistenceerr");
            exit();
        }
        $stmt->close();
        $mysqli->close();
        if ($_SESSION["uid"] != $sender && $_SESSION["uid"] != "INSERT ENGINEER ID HERE") {
            header("Location: pm.php?delautherr");
            exit();
        }
        $id = $_GET["id"];
        $vals = array('i', &$id);
        $db->prep("DELETE FROM pms WHERE id = ?", $vals);
        $vals2 = array('s', &$receiver);
        $db->prep("INSERT INTO pmu (receiver, timestamp) VALUES (?, now()) ON DUPLICATE KEY UPDATE timestamp = VALUES(timestamp)", $vals2);
        header("Location: " . $_GET["return"]);
    }
?>