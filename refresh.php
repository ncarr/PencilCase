<?php
    session_start();
    if (!$_SESSION["stp"])
        die();
    if ($_POST["g"]) {
        $groupfile = json_decode(file_get_contents("groups/" . $_POST["g"] . "/updates.txt"), true);
        if ($groupfile) {
            $last = array_pop($groupfile);
            if ($last["timestamp"] >= $_SESSION["lastref"])
                echo 1;
        }
    } elseif ($_POST["p"] = "pms") {
        $db = include("sqlconnect.php");
        $mysqli = $db->manual();
        $stmt = $mysqli->prepare("SELECT timestamp FROM pms WHERE receiver = ? AND timestamp >= ?");
        $stmt->bind_param('si', $me, $lastr);
        $me = $_SESSION["uid"];
        $lastr = date("Y-m-d H:i:s", $_SESSION["lastref"]);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0)
            echo 1;
        $stmt->close();
        $mysqli->close();
    } elseif ($_SESSION["uid"]) {
        $userdata = json_decode(file_get_contents("users/" . $_SESSION["uid"] . ".txt"), true);
        $groups = $userdata["groups"];
        if ($groups) {
            foreach ($groups as $group) {
                $groupfile = json_decode(file_get_contents("groups/" . $group . "/updates.txt"), true);
                if ($groupfile) {
                    $last = array_pop($groupfile);
                    if ($last["timestamp"] >= $_SESSION["lastref"])
                        echo 1;
                }
            }
        }
    }
    $_SESSION["lastref"] = time();
    die();
?>