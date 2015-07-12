<?php
    session_start();
    if (!$_SESSION["stp"]) {
        die();
    }
    if ($_POST["g"]) {
        $groupfile = json_decode(file_get_contents("groups/" . $_POST["g"] . "/updates.txt"), true);
        if ($groupfile) {
            $last = array_pop($groupfile);
            if ($last["timestamp"] >= time() - 60) {
                $out = 1;
            }
            if ($out) {
                echo 1;
            }
            die();
        }
    } elseif ($_SESSION["uid"]) {
        $userdata = json_decode(file_get_contents("users/" . $_SESSION["uid"] . ".txt"), true);
        $groups = $userdata["groups"];
        if ($groups) {
            foreach ($groups as $group) {
                $groupfile = json_decode(file_get_contents("groups/" . $group . "/updates.txt"), true);
                if ($groupfile) {
                    $last = array_pop($groupfile);
                    if ($last["timestamp"] >= time() - 60) {
                        $out = 1;
                    }
                    if ($out) {
                        echo 1;
                    }
                    die();
                }
            }
        }
    }
?>