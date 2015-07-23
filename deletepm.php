<?php
    if ($_GET["id"]) {
        session_start();
        $db = include("sqlconnect.php");
        $mysqli = $db->manual();
        $stmt = $mysqli->prepare("SELECT sender FROM pms WHERE id = ?");
        $stmt->bind_param('i', $id);
        $id = $_GET["id"];
        $stmt->execute();
        $stmt->bind_result($sender);
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
        header("Location: " . $_GET["return"]);
    }
?>