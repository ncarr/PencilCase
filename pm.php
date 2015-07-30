<?php
    // Start session to keep temporary session info
    session_start();
    // Send you away to the login if you're not a student to keep out unauthorised users
    if (!$_SESSION["stp"]) {
        header("Location: logout.php");
        exit();
    }
    function cmp($a, $b) {
        return ($a["timestamp"] - $b["timestamp"]) * -1;
    }
    $db = include("sqlconnect.php");
    $posts = array();
    if ($_GET["w"]) {
        $myid = $_SESSION["uid"];
        $wid = $_GET["w"];
        $mysqli = $db->manual();
        $stmt = $mysqli->prepare("SELECT id, content, timestamp, sender, receiver FROM pms WHERE sender IN (?, ?) AND receiver IN (?, ?)");
        $stmt->bind_param('ssss', &$myid, &$wid, &$myid, &$wid);
        $stmt->execute();
        $stmt->bind_result($id, $content, $timestamp, $sender, $receiver);
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $name = json_decode(file_get_contents("users/" . $_GET["w"] . ".txt"), true);
            $name = $name["name"];
            while ($stmt->fetch()) {
                $post["timestamp"] = $timestamp = strtotime($timestamp);
                if ($timestamp > strtotime("-1 minute"))
                    $post["display_timestamp"] = abs($timestamp - time()) . " seconds ago";
                elseif ($timestamp > strtotime("-1 hour"))
                    $post["display_timestamp"] = floor(abs(($timestamp - time()) / 60)) . " minutes ago";
                elseif ($timestamp > strtotime("00:00"))
                    $post["display_timestamp"] = date("g:i A", $timestamp);
                elseif ($timestamp > strtotime("Yesterday 00:00"))
                    $post["display_timestamp"] = "Yesterday, " . date("g:i A", $timestamp);
                elseif ($timestamp > strtotime("-1 week"))
                    $post["display_timestamp"] = date("l, g:i A", $timestamp);
                elseif ($timestamp > strtotime("January 1"))
                    $post["display_timestamp"] = date("F j, g:i A", $timestamp);
                else
                    $post["display_timestamp"] = date("F j Y, g:i A", $timestamp);
                if ($sender == $_GET["w"])
                    $post["sender_display_name"] = $name;
                else
                    $post["sender_display_name"] = $_SESSION["name"];
                if ($receiver == $_GET["w"])
                    $post["receiver_display_name"] = $name;
                else
                    $post["receiver_display_name"] = $_SESSION["name"];
                $post["id"] = $id;
                $post["content"] = $content;
                $post["sender"] = $sender;
                $post["receiver"] = $receiver;
                $posts[$post["id"]] = $post;
            }
        }
        $stmt->close();
        $mysqli->close();
    } else {
        $myid = $_SESSION["uid"];
        $mysqli = $db->manual();
        $stmt = $mysqli->prepare("SELECT id, content, timestamp, sender, receiver FROM pms WHERE sender = ? OR receiver = ?");
        $stmt->bind_param('ss', $myid, $myid);
        $stmt->execute();
        $stmt->bind_result($id, $content, $timestamp, $sender, $receiver);
        $stmt->store_result();
        $names[$_SESSION["uid"]] = $_SESSION["name"];
        if ($stmt->num_rows > 0) {
            while ($stmt->fetch()) {
                $post["timestamp"] = $timestamp = strtotime($timestamp);
                if ($timestamp > strtotime("-1 minute"))
                    $post["display_timestamp"] = abs($timestamp - time()) . " seconds ago";
                elseif ($timestamp > strtotime("-1 hour"))
                    $post["display_timestamp"] = floor(abs(($timestamp - time()) / 60)) . " minutes ago";
                elseif ($timestamp > strtotime("00:00"))
                    $post["display_timestamp"] = date("g:i A", $timestamp);
                elseif ($timestamp > strtotime("Yesterday 00:00"))
                    $post["display_timestamp"] = "Yesterday, " . date("g:i A", $timestamp);
                elseif ($timestamp > strtotime("-1 week"))
                    $post["display_timestamp"] = date("l, g:i A", $timestamp);
                elseif ($timestamp > strtotime("January 1"))
                    $post["display_timestamp"] = date("F j, g:i A", $timestamp);
                else
                    $post["display_timestamp"] = date("F j Y, g:i A", $timestamp);
                if ($names[$sender])
                    $post["sender_display_name"] = $names[$sender];
                else {
                    $name = json_decode(file_get_contents("users/" . $sender . ".txt"), true);
                    $post["sender_display_name"] = $names[$sender] = $name["name"];
                }
                if ($names[$receiver])
                    $post["receiver_display_name"] = $names[$receiver];
                else {
                    $name = json_decode(file_get_contents("users/" . $receiver . ".txt"), true);
                    $post["receiver_display_name"] = $names[$receiver] = $name["name"];
                }
                $post["id"] = $id;
                $post["content"] = $content;
                $post["sender"] = $sender;
                $post["receiver"] = $receiver;
                $posts[$post["id"]] = $post;
            }
        }
        $stmt->close();
        $mysqli->close();
    }
    usort($posts, "cmp");
    $userdata = json_decode(file_get_contents("users/" . $_SESSION["uid"] . ".txt"), true);
    // Decode user data array to find groups
    $groups = $userdata["groups"];
    if ($groups) {
        foreach ($groups as $group) {
            $g = json_decode(file_get_contents("groups/" . $group["id"] . "/index.txt"), true);
            $members = $g["members"];
            if ($members) {
                $i = 0;
                foreach ($members as $member) {
                    if ($member["owner"] && $member["id"] != $_SESSION["uid"]) {
                        $owners[] = $member;
                        $i++;
                        if ($member["id"] == $_GET["w"])
                            $windex = $i;
                    }
                }
            }
        }
        unset($group);
    }
    $_SESSION["lastref"] = time();
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <title>PencilCase</title>
        <!-- Here's where I include all my JS and CSS -->
        <?php include_once("header.php"); ?>
        <script>
            var refcheck,
            found;
            $(document).ready(function () {
                refcheck = setInterval("check", 60000);
            });
            $([window, document]).blur(function () {
                if (!found) {
                    clearInterval(refcheck);
                }
            }).focus(function () {
                if (!found) {
                    check();
                    refcheck = setInterval("check", 60000);
                }
            });
        </script>
        <script>
            function save(id) {
                $.post("editpm.php", { content: $("p[contenteditable=true]").html(), id: id });
                $("p.content").attr("contenteditable", "false");
                $("i.edit").slideDown();
                $(".save").slideUp();
            }
            function del(id) {
                $.Dialog({
                    shadow: true,
                    flat: true,
                    title: 'Flat window',
                    content: '',
                    padding: 10,
                    onShow: function (_dialog) {
                        var content = '<form method="post" action="#">' +
                                    '<div class="form-actions">' +
                                    '<a class="button primary" href="deletepm.php?id=' + id + '&return=pm.php' + location.search + '">Yes</a> ' +
                                    '<a class="button primary" href="pm.php">No!</a> ' +
                                    '</div>' +
                                    '</form>';
                        $.Dialog.title("Are you sure?");
                        $.Dialog.content(content);
                        $.Metro.initInputs();
                    }
                });
            }
            function check() {
                $.post("refresh.php", { p: "pms" })
                .done(function (data) {
                    if (data) {
                        $(".refresh").fadeIn();
                        clearInterval(refcheck);
                        found = true;
                    }
                })
            }
        </script>
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
        <link rel="import" href="bower_components/paper-fab/paper-fab.html">
        <link rel="import" href="pmbox.php<?php if ($_GET["w"]) echo "?w=" . $_GET["w"]; ?>">
        <meta name="apple-mobile-web-app-status-bar-style" content="black">
        <link rel="apple-touch-startup-image" href="logo192.png">
        <link rel="manifest" href="manifest.json">
        <style is="custom-style">
            paper-toolbar.side {
                --paper-toolbar-background: #303f9f;
            }
        </style>
        <style>
            body {
                font-family: Roboto, 'Helvetica Neue', Helvetica, Arial;
            }
            .metro .post a.content {
                float: none;
            }
            .metro .posts .post.important {
                background-color: #ef9a9a;
            }
            #refresh {
                position: fixed;
                bottom: 30px;
                right: 30px;
            }
            #rbwrapper {
                text-align: center;
            }
            .refresh {
                display: none;
            }
            .pwrapper {
                text-align: center;
            }
            .posts {
                max-width: 999px;
                display: inline-block;
            }
            .user-div [class*="icon-"].fg-white {
                background-color: #3f51b5;
            }
            .content.paper-menu a.iron-selected paper-item {
                font-weight: bold;
            }
        </style>
    </head>
    <!-- Adds Metro UI CSS (metroui.org.ua) styling to whole page -->
    <body class="metro" unresolved>
        <!-- Fancy spinny preloader thingy -->
        <div id="loader-wrapper">
            <svg version="1.1" viewBox="0.0 0.0 192.0 192.0" fill="none" stroke="none" stroke-linecap="square" stroke-miterlimit="10" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><clipPath id="p.0"><path d="m0 0l192.0 0l0 192.0l-192.0 0l0 -192.0z" clip-rule="nonzero"></path></clipPath><g clip-path="url(#p.0)"><path fill="#000000" fill-opacity="0.0" d="m0 0l192.0 0l0 192.0l-192.0 0z" fill-rule="nonzero"></path><path fill="#3f51b5" d="m7.92126 96.1601l0 0c0 -48.557568 39.434193 -87.92126 88.07874 -87.92126l0 0c23.359932 0 45.763107 9.263106 62.281067 25.751541c16.517975 16.488434 25.797668 38.851547 25.797668 62.169716l0 0c0 48.55758 -39.43419 87.92126 -88.078735 87.92126l0 0c-48.644547 0 -88.07874 -39.363678 -88.07874 -87.92126z" fill-rule="nonzero"></path><path stroke="#303f9f" stroke-width="16.0" stroke-linejoin="round" stroke-linecap="butt" d="m7.92126 96.1601l0 0c0 -48.557568 39.434193 -87.92126 88.07874 -87.92126l0 0c23.359932 0 45.763107 9.263106 62.281067 25.751541c16.517975 16.488434 25.797668 38.851547 25.797668 62.169716l0 0c0 48.55758 -39.43419 87.92126 -88.078735 87.92126l0 0c-48.644547 0 -88.07874 -39.363678 -88.07874 -87.92126z" fill-rule="nonzero"></path><path fill="#3848aa" d="m165.14435 136.50656l-101.852585 11.502457l9.537617 -103.8804z" fill-rule="nonzero"></path><path fill="#3848aa" d="m176.1215 97.00388l0 0c-0.23155212 21.875305 -9.41481 42.70153 -25.412003 57.630486c-15.997177 14.928955 -37.412567 22.658127 -59.260803 21.388214z" fill-rule="nonzero"></path><path fill="#3848aa" d="m63.15484 147.75328l84.661415 -27.905518l-56.220467 56.283463z" fill-rule="nonzero"></path><path fill="#3848aa" d="m91.76377 176.0052l39.24485 -40.561264l34.298462 1.3801575z" fill-rule="nonzero"></path><path fill="#3848aa" d="m164.84775 136.44096l11.292175 -39.469658l-47.32367 -47.396477l-11.292175 39.469666z" fill-rule="nonzero"></path><path fill="#ffffff" d="m53.136482 44.110237l19.968506 0l0 86.51969l-19.968506 0z" fill-rule="nonzero"></path><path fill="#ffffff" d="m112.93218 44.110428l0 0c14.328308 0.055130005 25.91822 11.6791 25.931328 26.007515c0.013092041 14.328407 -11.555565 25.973549 -25.88375 26.054863z" fill-rule="nonzero"></path><path fill="#c5cae9" d="m93.073494 44.110237l19.968498 0l0 52.06299l-19.968498 0z" fill-rule="nonzero"></path><path fill="#283593" d="m73.10499 130.62993l-9.984253 17.259842l-9.984253 -17.259842z" fill-rule="nonzero"></path><path fill="#7986cb" d="m53.136482 44.110237l19.968506 0l0 19.968506l-19.968506 0z" fill-rule="nonzero"></path></g></svg>
            <paper-spinner id="loader" active></paper-spinner>
            <div class="loader-section"></div>
        </div>
        <!-- Account stuff -->
        <paper-drawer-panel>
            <paper-header-panel mode="scroll" drawer>
                <paper-toolbar class="side">
                    <div title>Chats</div>
                    <div class="user-div bottom fg-white bg-transparent" style="left: 0px"><a href="student.php"><i class="icon-home fg-white"></i></a></div>
                </paper-toolbar>
                <paper-menu<?php if ($_GET["w"]) echo ' selected="' . $windex . '"'; else echo ' selected="0"'; ?>>
                    <?php if ($owners) { ?><a class="fg-black" href="pm.php"><paper-item>All</paper-item></a>
                    <?php foreach ($owners as $owner): ?><a class="fg-black" href="pm.php?w=<?php echo $owner["id"]; ?>"><paper-item><?php echo $owner["name"]; ?></paper-item></a>
                    <?php endforeach; } else { ?><paper-item>No chats</paper-item><?php } ?>
                </paper-menu>
            </paper-header-panel>
            <paper-header-panel mode="waterfall" main>
                <paper-toolbar>
                    <paper-icon-button icon="menu" paper-drawer-toggle></paper-icon-button>
                    <div title>Private Messages</div>
                </paper-toolbar>
                    <!-- Renders big page full of chat messages -->
                    <div class="page">
                        <div class="refresh" id="rbwrapper">
                            <div class="refreshbar">
                                <p class="content">There are new messages. Press the button in the bottom-right to load them.</p>
                            </div>
                        </div>
                        <post-box></post-box>
                        <div class="pwrapper">
                        <div class="posts">
                            <?php foreach ($posts as $post): ?>
                            <div class="post" id="<?php echo $post["id"]; ?>">
                                <a onclick='save(<?php echo $post["id"]; ?>);' class='save button'>Save</a><p class="save">Edit away... Don't forget to save.</p>
                                <h2 class="header"><?php echo $post["sender_display_name"]; ?></h2>
                                <?php if ($post["receiver"] != $_SESSION["uid"]) { ?><h4 class="receivers">> <?php echo $post["receiver_display_name"]; ?></h4><?php } ?>
                                <h3 class="datetime"><?php echo $post["display_timestamp"]; ?></h3>
                                <p class="content"><?php echo $post["content"]; ?></p>
                                <?php if ($post["sender"] == $_SESSION["uid"]) { ?>
                                <i class='edit icon-pencil' onclick='$(this).siblings("p.content").attr("contenteditable", "true");$(this).slideUp();$(this).siblings(".save").slideDown();'></i>
                                <i class='delete icon-remove' onclick='del(<?php echo $post["id"]; ?>);'></i><?php } ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        </div>
                        <a href="<?php echo $_SERVER["REQUEST_URI"]; ?>"><paper-fab icon="refresh" class="refresh" id="refresh"></paper-fab></a>
                    </div>
            </paper-header-panel>
        </paper-drawer-panel>
    </body>
</html>
<!-- Misha's little easter egg makes a return. April fool's day joke maybe?
<li class="fg-white">PencilCase best experienced with:</li>
<img src="http://www.microsoft.com/LIBRARY/IMAGES/GIFS/GENERAL/IE_ANIMATED.GIF" /> -->