<?php
    session_start();
    if (!isset($_SESSION["stp"])) {
        header("Location: logout.php");
    }
    $userdata = json_decode(file_get_contents("users/" . $_SESSION["uid"] . ".txt"), true);
    // Decode user data array to find groups
    $groups = $userdata["groups"];
    if ($groups) {
        foreach ($groups as $group) {
            $g = json_decode(file_get_contents("groups/" . $group["id"] . "/index.txt"), true);
            $members = $g["members"];
            if ($members) {
                foreach ($members as $member) {
                    if ($member["owner"] && $member["id"] != $_SESSION["uid"])
                        $owners[] = $member;
                }
            }
        }
        unset($group);
    }
?>
<link rel="import" href="bower_components/polymer/polymer.html" />
<dom-module id="post-box">
    <style>
        * {
            box-sizing: border-box;
            -moz-box-sizing: border-box;
            -webkit-box-sizing: border-box;
        }
        textarea {
            width: 100%;
            height: 240px;
            padding: 10px;
            background-color: #9fa8da;
            font-family: "Roboto", "Open Sans", "Segoe UI Light", sans-serif;
            border: none;
            resize: none;
            color: black;
        }
        button.share, .group {
            margin: 10px;
            padding: 10px;
            border: none;
            background-color: #5c6bc0;
            color: white;
            box-shadow: 2px 0px 5px rgba(0,0,0,0.26);
            font-family: "Roboto", "Open Sans", "Segoe UI Light", sans-serif;
        }
    </style>
    <template>
        <form method="post" action="sendpm.php">
            <textarea name="post" placeholder="Enter your text here" maxlength="140" required></textarea>
            <input type="hidden" value="1" name="r" />
            <button class="share" type="submit">Send to...</button>
            <select name="w" class="group" required><?php if ($owners) { ?><option value="" selected disabled>Select someone...</option><?php foreach ($owners as $owner): ?><option value="<?php echo $owner["id"]; ?>"<?php if ($owner["id"] == $_GET["w"]) echo " selected"; ?>><?php echo $owner["name"]; ?></option><?php endforeach; } else { ?><option value="Null" selected disabled>You have no groups</option><?php } ?></select>
        </form>
    </template>
</dom-module>
<script>
    Polymer({ is: "post-box",
        hostAttributes: {
            group: String,
            query: String
        }
    });
</script>