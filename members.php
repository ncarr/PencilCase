<?php
    session_start();
    if (!$_SESSION["stp"]) {
        header("Location: logout.php");
        $_SESSION["return"] = "members.php";
    }
    $user = json_decode(file_get_contents("users/" . $_SESSION["uid"] . ".txt"), true);
    foreach ($user["groups"] as $group) {
        if ($group["owner"]) {
            $groups[] = $group;
        }
    }
    if ($_POST["them"] && $_POST["g"]) {
        foreach ($_POST["them"] as $tbd) {
            $them = json_decode(file_get_contents("groups/" . $_POST["g"] . "/index.txt"), true);
            if ($tbd["type"] == "member") {
                $me = json_decode(file_get_contents("users/" . $tbd["id"] . ".txt"), true);
                unset($me["groups"][$_POST["g"]]);
                file_put_contents("users/" . $tbd["id"] . ".txt", json_encode($me));
            }
            for ($i = 0; ($i < count($them[$tbd["type"] . "s"]) && $i != -1); $i++) {
                if ($them[$tbd["type"] . "s"][$i]["id"] == $tbd["id"]) {
                    unset($them[$tbd["type"] . "s"][$i]);
                    $i = -1;
                }
            }
            file_put_contents("groups/" . $_POST["g"] . "/index.txt", json_encode($them));
        }
    }
    if ($_POST["add"] && $_POST["g"]) {
        foreach ($_POST["add"] as $tba) {
            $them = json_decode(file_get_contents("groups/" . $_POST["g"] . "/index.txt"), true);
            $me = json_decode(file_get_contents("users/" . $tba["id"] . ".txt"), true);
            $me["groups"][$_POST["g"]] = array("name" => $them["name"], "id" => $_POST["g"], "verified" => TRUE);
            unset($me["requests"][$_POST["g"]]);
            file_put_contents("users/" . $tba["id"] . ".txt", json_encode($me));
            unset($them["requests"][$tba["id"]]);
            $them["members"][$tba["id"]] = array("id" => $tba["id"], "name" => $me["name"]);
            file_put_contents("groups/" . $_POST["g"] . "/index.txt", json_encode($them));
        }
    }
    if ($_POST["ppl"] && $_POST["g"]) {
        $db = include("sqlconnect.php");
        $myid = $_SESSION["uid"];
        $mysqli = $db->manual();
        $stmt = $mysqli->prepare("INSERT INTO points (id, description, timestamp, sender, receiver, name, positive) VALUES (?, ?, NULL, ?, ?, ?, ?)");
        $posop = $mysqli->prepare("SELECT receiver, positive, COUNT(*) FROM points WHERE receiver = ? AND positive = 1 GROUP BY receiver, positive");
        $negop = $mysqli->prepare("SELECT receiver, positive, COUNT(*) FROM points WHERE receiver = ? AND positive = 0 GROUP BY receiver, positive");
        foreach ($_POST["ppl"] as $tbgp) {
            $stmt->bind_param('issssi', $id, $desc, $myid, $recipient, $name, $positive);
            $posop->bind_param('s', $recipient2);
            $negop->bind_param('s', $recipient3);
            $me = json_decode(file_get_contents("users/" . $tbgp["id"] . ".txt"), true);
            $id = mt_rand(100000000, 999999999);
            $desc = "No description";
            $myid = $_SESSION["uid"];
            $recipient = $tbgp["id"];
            $name = "Positive point";
            $positive = 1;
            $stmt->execute();
            $recipient2 = $tbgp["id"];
            $posop->execute();
            $posop->bind_result($dontcare, $meneither, $count);
            $posop->fetch();
            echo $positives = $count;
            $posop->reset();
            $recipient3 = $tbgp["id"];
            $negop->execute();
            $negop->bind_result($dontcare2, $meneither2, $count2);
            $negop->fetch();
            echo $negatives = $count2;
            $negop->reset();
            $me["percent"] = round($positives / ($positives + $negatives) * 100);
            file_put_contents("users/" . $tbgp["id"] . ".txt", json_encode($me));
        }
        $stmt->close();
        $posop->close();
        $negop->close();
        $mysqli->close();
    }
    if ($_GET["g"] || count($groups) == 1) {
        $members = json_decode(file_get_contents("groups/" . (($_GET["g"]) ?: $groups[0]["id"]) . "/index.txt"), true);
        $requests = $members["requests"];
        $members = $members["members"];
    }
    if (!$_POST["them"] && !$_POST["g"]) {
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
        <title>Group Members</title>
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
            .user-id-image span {
                width: 100%;
                height: 100%;
                line-height: 40px;
                vertical-align: middle;
            }
            .metro .page button {
                display: none;
            }
        </style>
        <script>
            function sendin() {
                $('.vertical-menu').addClass("open");
                $('.page').addClass("open");
            }
            function takeout() {
                $('.page').removeClass("open");
                $('.vertical-menu').removeClass("open");
            }
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
            function del() {
                if ($(".selected")[0]) {
                    var selected = $(".selected");
                    var them = [];
                    for (i = 0; i < selected.length; i++) {
                        var it = selected.map(function (ti) { if (ti == i) { return this; } });
                        if (it.hasClass("request")) {
                            them.push({ id: it.data("p-id"), type: "request" });
                        } else {
                            them.push({ id: it.data("p-id"), type: "member" });
                        }
                    }
                    $.post("members.php", { them: them, g: "<?php echo ($_GET["g"]) ?: $groups[0]["id"]; ?>" })
                        .done(function () {
                            selected.slideUp();
                        });
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
                        .done(function () {
                            selected.addClass("bg-green").removeClass("bg-cyan");
                            setTimeout(function () { selected.removeClass("bg-green").addClass("bg-cyan") }, 2000)
                        });
                }
            }
            function add() {
                if ($(".selected")[0]) {
                    var selected = $(".selected");
                    var them = [];
                    for (i = 0; i < selected.length; i++) {
                        var it = selected.map(function (ti) { if (ti == i) { return this; } });
                        if (it.hasClass("request")) {
                            them.push({ id: it.data("p-id"), type: "request" });
                        }
                    }
                    $.post("members.php", { add: them, g: "<?php echo ($_GET["g"]) ?: $groups[0]["id"]; ?>" })
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
                        <a class="fg-black" href="points.php"><paper-item>View Points</paper-item></a>
                        <a class="fg-black" href="#"><paper-item>Manage Members</paper-item></a>
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
                    <div title>Group Members</div>
                    <a href="feedback.php"><paper-icon-button icon="bug-report"></paper-icon-button></a>
                </paper-toolbar>
                <div class="page" unselectable="on">
                    <?php if ($members):
                    foreach ($members as $member): ?>
                    <div class="tile member bg-cyan" onclick="$(this).toggleClass('selected');userclick();" data-p-id="<?php echo $member["id"]; ?>">
                        <div class="tile-status">
                            <span class="name"><?php echo $member["name"]; ?></span>
                            <div class="badge"></div>
                        </div>
                    </div>
                    <?php endforeach;
                    foreach ($requests as $request): ?>
                    <div class="tile request bg-cyan" onclick="$(this).toggleClass('selected');userclick();" data-p-id="<?php echo $request["id"]; ?>">
                        <div class="tile-status">
                            <span class="name"><?php echo $request["name"]; ?></span>
                            <div class="badge alert"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <button onclick="del();" class="del">Delete Member(s)</button>
                    <button onclick="add();" class="add">Accept Request(s)</button>
                    <button onclick="points();" class="points">Give a point</button>
                    <?php else:
                    if ($groups) { ?>
                    <form method="get">
                    <?php foreach ($groups as $group): ?>
                        <input type="radio" name="g" value="<?php echo $group["id"]; ?>" /><?php echo $group["name"]; ?>
                    <?php endforeach; ?>
                        <input type="submit" />
                    </form>
                    <?php } else { ?>
                    <h3>No groups to manage.</h3>
                    <?php } endif; ?>
                </div>
            </paper-header-panel>
        </paper-drawer-panel>
    </body>
</html>
<?php } ?>
