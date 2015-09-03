<?php
    session_start();
    if (!$_SESSION["stp"]) {
        header("Location: logout.php");
    }
    $points = json_decode(file_get_contents("users/" . $_SESSION["uid"] . ".txt"), true);
    $db = include("sqlconnect.php");
    $myid = $_SESSION["uid"];
    $mysqli = $db->manual();
    $stmt = $mysqli->prepare("SELECT id, description, timestamp, sender, name, positive FROM points WHERE receiver = ?");
    $stmt->bind_param('s', &$myid);
    $stmt->execute();
    $stmt->bind_result($id, $desc, $timestamp, $sender, $name, $pos);
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        while ($stmt->fetch()) {
            $point["timestamp"] = $timestamp = strtotime($timestamp);
            $point["display_timestamp"] = date("l, g:i A", $timestamp);
            if ($names[$sender])
                $point["sender_display_name"] = $names[$sender];
            else {
                $sdname = json_decode(file_get_contents("users/" . $sender . ".txt"), true);
                $point["sender_display_name"] = $names[$sender] = $sdname["name"];
            }
            $point["id"] = $id;
            $point["description"] = $desc;
            $point["sender"] = $sender;
            $point["name"] = $name;
            $posts[$point["id"]] = $point;
            if ($pos)
                $positives[] = $point;
            else
                $negatives[] = $point;
        }
    }
    $stmt->close();
    $mysqli->close();
    if ($_SESSION["stp"] == "teacher") {
        foreach ($points["groups"] as $group) {
            if ($group["owner"])
                $groups[] = $group;
        }
        if ($_GET["g"] || count($groups) == 1) {
            $members = json_decode(file_get_contents("groups/" . (($_GET["g"]) ?: $groups[0]["id"]) . "/index.txt"), true);
            $members = $members["members"];
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <script src="bower_components/webcomponentsjs/webcomponents-lite.min.js"></script>
        <link rel="import" href="bower_components/paper-spinner/paper-spinner.html">
        <link rel="import" href="bower_components/paper-toolbar/paper-toolbar.html">
        <link rel="import" href="bower_components/iron-icons/iron-icons.html">
        <link rel="import" href="bower_components/iron-media-query/iron-media-query.html">
        <link rel="import" href="bower_components/paper-icon-button/paper-icon-button.html">
        <link rel="import" href="bower_components/paper-drawer-panel/paper-drawer-panel.html">
        <link rel="import" href="bower_components/paper-header-panel/paper-header-panel.html">
        <link rel="import" href="bower_components/paper-menu/paper-menu.html">
        <link rel="import" href="bower_components/paper-item/paper-item.html">
        <?php include_once("header.php"); ?>
        <title>PencilCase Points</title>
        <style>
            .right {
                float: right;
                z-index: 3;
                cursor: pointer;
                position: relative;
                padding-right: 8px;
            }
            .title {
                z-index: 2;
            }
            .vertical-menu {
                background-color: #303f9f !important;
            }
            li.title a i {
                font-size: 3rem !important;
            }
            li.title a {
                display: inline !important;
            }
            /* Styles account stuff */
            .metro .topbar .tile-area .user-id {
                top: calc(1cm - 56px / 2);
            }
            .user-id-image, .user-id-image img {
                background-color: #303f9f !important;
                border-radius: 40px;
                box-shadow: 0px 2px 5px rgba(0,0,0,0.26);
            }
            .metro .topbar .tile-area .user-id:hover {
                background-color: #3f51b5;
            }
            .user-id-image:hover {
                background-color: #1a237e !important;
            }
            .user-id-image span {
                width: 100%;
                height: 100%;
                line-height: 40px;
                vertical-align: middle;
            }
            .metro .page.open {
                width: calc(100% - 300px);
                left: 300px;
            }
            .metro .page.close {
                width: 100%;
                left: 0px;
            }
        </style>
        <script>
            function userclick() {
                if ($(".selected")[0]) {
                    if ($(".selected").hasClass("request") && !$(".selected").hasClass("member")) {
                        $(".del").slideDown();
                        $(".add").slideDown();
                        $(".points").slideUp();
                    }
                    if ($(".selected").hasClass("request") && $(".selected").hasClass("member")) {
                        $(".del").slideDown();
                        $(".add").slideUp();
                        $(".points").slideUp();
                    }
                    if (!$(".selected").hasClass("request") && $(".selected").hasClass("member")) {
                        $(".del").slideDown();
                        $(".add").slideUp();
                        $(".points").slideDown();
                    }
                } else {
                    $("button").slideUp();
                }
            }
            function points() {
                if ($(".selected")[0]) {
                    var selected = $(".selected");
                    var them = [];
                    for (i = 0; i < selected.length; i++) {
                        var it = selected.map(function (ti) { if (ti == i) { return this; } });
                        if (it.hasClass("member")) {
                            them.push({ id: it.data("p-id"), type: "member" });
                        }
                    }
                    $.post("members.php", { ppl: them, g: "<?php echo ($_GET["g"]) ?: $groups[0]["id"]; ?>" })
                }
            }
        </script>
    </head>
    <body class="metro" unresolved>
        <!-- Fancy spinny preloader thingy -->
        <div id="loader-wrapper">
            <svg version="1.1" viewBox="0.0 0.0 192.0 192.0" fill="none" stroke="none" stroke-linecap="square" stroke-miterlimit="10" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><clipPath id="p.0"><path d="m0 0l192.0 0l0 192.0l-192.0 0l0 -192.0z" clip-rule="nonzero"></path></clipPath><g clip-path="url(#p.0)"><path fill="#000000" fill-opacity="0.0" d="m0 0l192.0 0l0 192.0l-192.0 0z" fill-rule="nonzero"></path><path fill="#3f51b5" d="m7.92126 96.1601l0 0c0 -48.557568 39.434193 -87.92126 88.07874 -87.92126l0 0c23.359932 0 45.763107 9.263106 62.281067 25.751541c16.517975 16.488434 25.797668 38.851547 25.797668 62.169716l0 0c0 48.55758 -39.43419 87.92126 -88.078735 87.92126l0 0c-48.644547 0 -88.07874 -39.363678 -88.07874 -87.92126z" fill-rule="nonzero"></path><path stroke="#303f9f" stroke-width="16.0" stroke-linejoin="round" stroke-linecap="butt" d="m7.92126 96.1601l0 0c0 -48.557568 39.434193 -87.92126 88.07874 -87.92126l0 0c23.359932 0 45.763107 9.263106 62.281067 25.751541c16.517975 16.488434 25.797668 38.851547 25.797668 62.169716l0 0c0 48.55758 -39.43419 87.92126 -88.078735 87.92126l0 0c-48.644547 0 -88.07874 -39.363678 -88.07874 -87.92126z" fill-rule="nonzero"></path><path fill="#3848aa" d="m165.14435 136.50656l-101.852585 11.502457l9.537617 -103.8804z" fill-rule="nonzero"></path><path fill="#3848aa" d="m176.1215 97.00388l0 0c-0.23155212 21.875305 -9.41481 42.70153 -25.412003 57.630486c-15.997177 14.928955 -37.412567 22.658127 -59.260803 21.388214z" fill-rule="nonzero"></path><path fill="#3848aa" d="m63.15484 147.75328l84.661415 -27.905518l-56.220467 56.283463z" fill-rule="nonzero"></path><path fill="#3848aa" d="m91.76377 176.0052l39.24485 -40.561264l34.298462 1.3801575z" fill-rule="nonzero"></path><path fill="#3848aa" d="m164.84775 136.44096l11.292175 -39.469658l-47.32367 -47.396477l-11.292175 39.469666z" fill-rule="nonzero"></path><path fill="#ffffff" d="m53.136482 44.110237l19.968506 0l0 86.51969l-19.968506 0z" fill-rule="nonzero"></path><path fill="#ffffff" d="m112.93218 44.110428l0 0c14.328308 0.055130005 25.91822 11.6791 25.931328 26.007515c0.013092041 14.328407 -11.555565 25.973549 -25.88375 26.054863z" fill-rule="nonzero"></path><path fill="#c5cae9" d="m93.073494 44.110237l19.968498 0l0 52.06299l-19.968498 0z" fill-rule="nonzero"></path><path fill="#283593" d="m73.10499 130.62993l-9.984253 17.259842l-9.984253 -17.259842z" fill-rule="nonzero"></path><path fill="#7986cb" d="m53.136482 44.110237l19.968506 0l0 19.968506l-19.968506 0z" fill-rule="nonzero"></path></g></svg>
            <paper-spinner id="loader" active></paper-spinner>
            <div class="loader-section"></div>
        </div>
        <!-- Account stuff -->
        <paper-drawer-panel force-narrow>
            <paper-header-panel mode="scroll" drawer>
                <paper-toolbar class="tall" style="background-image: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url('<?php echo $_SESSION["cover"]; ?>'); background-size: cover">
                    <div title>Settings</div>
                    <div class="top">
                        <img src="<?php echo $_SESSION["photo"]; ?>" />
                    </div>
                    <div class="bottom" style="text-align: left;">
                        <p class="fg-white"><?php echo $_SESSION["name"]; ?></p>
                        <p style="color: rgba(255, 255, 255, 0.8)"><?php echo $_SESSION["email"]; ?></p>
                    </div>
                </paper-toolbar>
                    <paper-menu>
                        <a class="fg-black" href="settings.php"><paper-item>Add <?php echo ($_SESSION["stp"] === "student") ? "Parent" : "Child" ; ?></paper-item></a>
                        <a class="fg-black" href="#"><paper-item>View Points</paper-item></a>
                        <a class="fg-black" href="members.php"><paper-item>Manage Members</paper-item></a>
                        <a class="fg-black" href="addremgroup.php"><paper-item>Manage Groups</paper-item></a>
                        <a class="fg-black" href="deleteaccount.php"><paper-item>Delete Account</a></paper-item>
                        <a class="fg-black" href="about.php"><paper-item>About PencilCase</paper-item></a>
                        <section>
                            <a class="fg-black" href="student.php"><paper-item>Feed</paper-item></a>
                        </section>
                    </paper-menu>
                    <!--It's Javert for the sidebar! <paper-item><img alt = "Javert your eyes kids!" src = "http://i.imgur.com/UiXAker.gif"></paper-item>-->
            </paper-header-panel>
            <paper-header-panel mode="waterfall" main>
                <paper-toolbar>
                    <paper-icon-button icon="menu" paper-drawer-toggle></paper-icon-button>
                    <div title>Points</div>
                    <a href="feedback.php"><paper-icon-button icon="bug-report"></paper-icon-button></a>
                </paper-toolbar>
                <div class="page" unselectable="on">
                    <?php if ($_SESSION["stp"] == "student") { ?>
                    <h2><?php if (!$points["points"]): ?>No data<?php else: echo $points["percent"]; ?>%</h2>
                    <h3>Positive</h3>
                    <?php foreach ($positives as $positive): ?>
                    <p><?php echo $positive["name"]; ?></p><br/>
                    <?php endforeach; ?>
                    <h3>Negative</h3>
                    <?php foreach ($negatives as $negative): ?>
                    <p><?php echo $negative["name"]; ?></p>
                    <?php endforeach; endif; } elseif ($_SESSION["stp"] == "teacher") { 
                    if ($members) {
                    foreach ($members as $member) { ?>
                    <div class="tile member bg-cyan" onclick="$(this).toggleClass('selected');userclick();" data-p-id="<?php echo $member["id"]; ?>">
                        <div class="tile-status">
                            <span class="name"><?php echo $member["name"]; ?></span>
                            <div class="badge"></div>
                        </div>
                    </div>
                    <?php } ?>
                    <button onclick="points();" class="points">Give a point</button>
                    <?php } elseif ($groups) { ?>
                    <form method="get">
                    <?php foreach ($groups as $group): ?>
                        <input type="radio" name="g" value="<?php echo $group["id"]; ?>" /><?php echo $group["name"]; ?>
                    <?php endforeach; ?>
                        <input type="submit" />
                    </form>
                    <?php } else { ?>
                    <h2>No students to give points to</h2>
                    <?php }
                    } else { ?>
                    <h2>Cannot receive points</h2>
                    <?php } ?>
                </div>
            </paper-header-panel>
        </paper-drawer-panel>
    </body>
</html>
