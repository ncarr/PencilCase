<?php
    session_start();
    if (!isset($_SESSION["stp"])) {
        header("Location: logout.php");
    }
    $userdata = json_decode(file_get_contents("users/" . $_SESSION["uid"] . ".txt"), true);
    // Decode user data array to find groups
    $groupsdata = $userdata["groups"];
    foreach ($groupsdata as $groupdata) {
        if ($groupdata["verified"] || $groupdata["owner"])
            $groups[$groupdata["id"]] = $groupdata;
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
        <form method="post" action="post.php?r=student.php">
            <textarea name="post" placeholder="Enter your text here" maxlength="599" required></textarea>
            <input type="hidden" value="1" name="r" />
            <button class="share" type="submit">Post to...</button>
            <select name="group" class="group" required><?php if ($groups) { ?><option value="" selected disabled>Select a group...</option><?php if (count($groups) > 1) { ?><option value="all">All groups</option><?php } if ($_SESSION["uid"] == 107079368442804920970 || $_SESSION["uid"] == 106839686885505110020 || $_SESSION["uid"] == 104898751143469146088) { ?><option value="engineers">Every user</option><?php } foreach ($groups as $group): ?><option value="<?php echo $group["id"]; ?>" selected?="{{group == '<?php echo $group["id"]; ?>'}}"><?php echo $group["name"]; ?></option><?php endforeach; } else { ?><option value="Null" selected disabled>You have no groups</option><?php } ?></select>
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