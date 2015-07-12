<?php
    // Start session to keep temporary session info
    session_start();
    // Send you away to the login if you're not a student to keep out unauthorised users
    if (!$_SESSION["stp"]) {
        header("Location: logout.php");
    }
    if ($_GET["group"]) {
        $groupfile = json_decode(file_get_contents("groups/" . $_GET["group"] . "/index.txt"), true);
        if (!$groupfile["members"][$_SESSION["uid"]]) {
            header("Location: student.php");
            exit();
        }
    }
    // Decode user data file to find parents
    $userdata = json_decode(file_get_contents("users/" . $_SESSION["uid"] . ".txt"), true);
    $parents = $userdata["parents"];
    // Decode user data array to find groups
    $groups = $userdata["groups"];
    function cmp($a, $b) {
        return ($a["timestamp"] - $b["timestamp"]) * -1;
    }
    function cmp2($a, $b) {
        return $a["due"] - $b["due"];
    }
    if ($_GET["group"])
        $posts = json_decode(file_get_contents(($_GET["group"]) ? "groups/" . $_GET["group"] . "/posts.txt" : "rootposts.txt"), true);
    else {
        $posts = array();
        // Not included in GitHub. Replace with your database connection
        $db = include("sqlconnect.php");
        $result = $db->query("SELECT id, content, timestamp, poster_id, poster_name, receivers, priority, teacher FROM rootposts");
        if ($result->num_rows > 0) {
            while ($post = $result->fetch_assoc()) {
                $post["timestamp"] = strtotime($post["timestamp"]);
                $posts[$post["id"]] = $post;
            }
        }
        $cresult = $db->query("SELECT id, content, timestamp, poster_id, poster_name, post FROM rootcomments");
        if ($cresult->num_rows > 0) {
            while ($comment = $cresult->fetch_assoc()) {
                $comment["timestamp"] = strtotime($comment["timestamp"]);
                $posts[$comment["post"]]["comments"][$comment["id"]] = $comment;
            }
        }
    }
    $hw = ($_GET["group"]) ? json_decode(file_get_contents("groups/" . $_GET["group"] . "/homework.txt"), true) : array();
    if (!$_GET["group"] && $groups) {
        foreach ($groups as $group) {
            $posts += json_decode(file_get_contents("groups/" . $group["id"] . "/posts.txt"), true);
            $hw += json_decode(file_get_contents("groups/" . $group["id"] . "/homework.txt"), true);
        }
        unset($group);
    }
    usort($posts, "cmp");
    if ($hw) {
        foreach ($hw as $assignment) {
            if ($assignment["due"] < strtotime("+2 days") && $assignment["due"] > time() && !$assignment["subs"][$_SESSION["uid"]]) {
                $assignments[] = $assignment;
            }
        }
        if ($assignments) {
            usort($assignments, "cmp2");
        }
    }
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
                refcheck = setInterval("check(<?php if ($_GET["group"]) echo '"' . $_GET["group"] . '"'; ?>)", 60000);
            });
            $([window, document]).blur(function () {
                if (!found) {
                    clearInterval(refcheck);
                }
            }).focus(function () {
                if (!found) {
                    check(<?php if ($_GET["group"]) echo '"' . $_GET["group"] . '"'; ?>);
                    refcheck = setInterval("check(<?php if ($_GET["group"]) echo '"' . $_GET["group"] . '"'; ?>)", 60000);
                }
            });
        </script>
        <script>
            function sendin() {
                $('.vertical-menu').addClass("open");
                $('.page').addClass("open");
            }
            function takeout() {
                $('.page').removeClass("open");
                $('.vertical-menu').removeClass("open");
            }
            function save(id, r) {
                $.post("editpost.php", { content: $("p[contenteditable=true]").html(), id: id, r: r });
                $("p.content").attr("contenteditable", "false");
                $("i.edit").slideDown();
                $(".save").slideUp();
            }
            function savec(p, c, r) {
                $.post("editpost.php", { content: $("p[contenteditable=true]").html(), p: p, c: c, r: r });
                $("p.content").attr("contenteditable", "false");
                $("i.edit").slideDown();
                $(".save").slideUp();
            }
            function del(id, r) {
                $.Dialog({
                    shadow: true,
                    flat: true,
                    title: 'Flat window',
                    content: '',
                    padding: 10,
                    onShow: function (_dialog) {
                        var content = '<form method="post" action="#">' +
                                    '<div class="form-actions">' +
                                    '<a class="button primary" href="deletepost.php?id=' + id + '&r=' + r + '&return=' + document.URL + '">Yes</a> ' +
                                    '<a class="button primary" href="student.php">No!</a> ' +
                                    '</div>' +
                                    '</form>';
                        $.Dialog.title("Are you sure?");
                        $.Dialog.content(content);
                        $.Metro.initInputs();
                    }
                });
            }
            function delc(p, c, r) {
                $.Dialog({
                    shadow: true,
                    flat: true,
                    title: 'Flat window',
                    content: '',
                    padding: 10,
                    onShow: function (_dialog) {
                        var content = '<form method="post" action="#">' +
                                    '<div class="form-actions">' +
                                    '<a class="button primary" href="deletepost.php?p=' + p + '&c=' + c + '&r=' + r + '&return=student.php' + location.search + '">Yes</a> ' +
                                    '<a class="button primary" href="student.php">No!</a> ' +
                                    '</div>' +
                                    '</form>';
                        $.Dialog.title("Are you sure?");
                        $.Dialog.content(content);
                        $.Metro.initInputs();
                    }
                });
            }
            function showComments(id, r) {
                $.post("load.php", { post: id, r: r })
                .done(function (data) {
                    $("#" + id + " > .comment").remove();
                    $("#" + id).append(data);
                })
            }
            function check(g) {
                $.post("refresh.php", { g: g })
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
        <link rel="import" href="postbox.php">
        <meta name="apple-mobile-web-app-status-bar-style" content="black">
        <link rel="apple-touch-startup-image" href="logo192.png">
        <link rel="manifest" href="manifest.json">

        <style>
            body {
                font-family: Roboto, 'Helvetica Neue', Helvetica, Arial;
            }
            core-toolbar {
                color: #f1f1f1;
                fill: #f1f1f1;
            }
        </style>
        <style>
            /* Colour vertical menu */
            .vertical-menu {
                background-color: #303f9f !important;
            }
            /*Right align for Misha's delightful closing sidebar */
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
            /* Style the box that comes up if you haven't added a parent */
            .description {
                position: absolute;
                right: 0.5cm;
                top: 3cm;
                padding: 5mm;
                padding-right: 1cm;
                background-color: #303f9f;
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
        <paper-drawer-panel force-narrow>
            <paper-header-panel mode="scroll" drawer>
                <paper-toolbar class="tall" style="background-image: linear-gradient(
      rgba(0, 0, 0, 0.3),
      rgba(0, 0, 0, 0.3)
    ),url('<?=$_SESSION["cover"]; ?>');background-size: cover">
                    <div title>PencilCase</div>
                    <div class="middle">
                        <img src="<?php echo $_SESSION["photo"]; ?>" />
                    </div>
                    <div class="user-div bottom fg-white bg-transparent" style="left: 0px"> <?php echo $_SESSION["name"]; ?><a href="logout.php"><i class="icon-exit fg-white on-left"></i></a><a href="settings.php"><i class="icon-cog fg-white"></i></a></div>
                </paper-toolbar>
                    <paper-menu>
                        <a class="fg-black" href="?"><paper-item>All Groups</paper-item></a>
                        <?php if ($groups) { foreach ($groups as $group): ?>
                        <a class="fg-black" href="?group=<?php echo $group["id"]; ?>"><paper-item><?php echo $group["name"]; ?></paper-item></a>
                        <?php endforeach; } ?>
                        <a class="fg-black" href="addremgroup.php"><paper-item><i class="icon-plus-2 on-left"></i>Add/Remove</paper-item></a>
                        <section>
                            <a class="fg-black" href="?view=teacher"><paper-item><i class="icon-filter on-left"></i>Teacher Posts</paper-item></a>
                        </section>
                        <section>
                            <a class="fg-black" href="page.php?p=homework<?= ($_GET["group"]) ? "&g=" . $_GET["group"] : ""; ?>"><paper-item><i class="icon-book fg-black on-left"></i>Homework</paper-item></a>
                            <?php if ($_SESSION["stp"] === "teacher") { ?><a class="fg-black" href="presentation.php<?= ($_GET["group"]) ? "?g=" . $_GET["group"] : ""; ?>"><paper-item><i class="icon-screen fg-black on-left"></i>Start Presentation</paper-item></a><?php } ?>
                            <a class="fg-black" href="points.php"><paper-item><i class="icon-bars fg-black on-left"></i>Points</paper-item></a>
                        </section>
                        <section>
                            <a class="fg-black" href="feedback.php"><paper-item><i class="icon-lamp-2 fg-black on-left"></i>Feedback Centre</paper-item></a>
                        </section>
                        <?php if ($_SESSION["uid"] == 107079368442804920970 || $_SESSION["uid"] == 106839686885505110020 || $_SESSION["uid"] == 104898751143469146088): ?>
                        <section>
                            <!-- Only shows if you are a dev -->
                            <a class="fg-black" href="cloud.php" target="_blank"><paper-item><i class="icon-code fg-black on-left"></i>Engineer Centre</paper-item></a>
                                <paper-item><img src="http://i.imgur.com/Wwc5u.gif" alt="Made with a Mac."></paper-item>
                                <paper-item><a href="http://windows.microsoft.com/en-us/internet-explorer/download-ie"><img src="http://www.microsoft.com/LIBRARY/IMAGES/GIFS/GENERAL/IE_ANIMATED.GIF" alt="IE"></a></paper-item>
                        </section>
                        <?php endif; ?>
                </paper-menu>
                    <!--It's Javert for the sidebar! <paper-item><img alt = "Javert your eyes kids!" src = "http://i.imgur.com/UiXAker.gif"></paper-item>-->
            </paper-header-panel>
            <paper-header-panel mode="waterfall" main>
                <paper-toolbar>
                    <paper-icon-button icon="menu" paper-drawer-toggle></paper-icon-button>
                    <div title>Feed</div>
                </paper-toolbar>
                    <!-- Renders big page full of chat messages -->
                    <div class="page">
                        <div class="refresh" id="rbwrapper">
                            <div class="refreshbar">
                                <p class="content">There are new posts. Press the button in the bottom-right to load them.</p>
                            </div>
                        </div>
                        <post-box query="<?php echo $_SERVER["QUERY_STRING"]; ?>" group="<?php echo $_GET["group"]; ?>"></post-box>
                        <div class="posts">
                        <?php if ($_SESSION["presentation"]) { ?>
                            <div class="post important">
                                <h2 class="header">PencilCase Engineers</h2>
                                <p class="content">Your presentation is still running.</p>
                                <a class="button" href="presentation.php?g=<?=$_SESSION["g"];?>&i=<?=$_SESSION["i"];?>">Resume</a>
                                <a class="button" href="presentation.php?g=<?=$_SESSION["g"];?>&i=<?=count($_SESSION["presentation"]) - 1;?>">End</a>
                            </div>
                        <?php } if (!$parents && !$userdata["dismisswelcome"] && !$_GET["groups"]): ?>
                        <!-- Box that shows if you haven't added parents -->
                            <div class="post">
                                <h2 class="header">PencilCase Engineers</h2>
                                <p class="content">Welcome to PencilCase! To get started, add a <?php echo ($_SESSION["stp"] == "student") ? "parent" : "group"; ?> by going to settings.</p>
                                <a class='delete icon-remove fg-black' href="removewelcome.php"></a>
                            </div>
                        <?php endif;
                        if ($assignments) {
                        foreach ($assignments as $assignment): ?>
                            <div class="post" id="<?php echo $assignment["id"]; ?>">
                                <h2 class="header"><?php echo $assignment["title"]; ?></h2>
                                <h4 class="receivers"><?php echo $assignment["subject"]; ?> Assignment</h4>
                                <?php if ($assignment["online"]) { ?><a class="button<?php if ($assignment["subs"][$_SESSION["uid"]]) echo " bg-green"; ?>" href="page.php?s=<?php echo $assignment["id"]; ?>&g=<?php echo $_GET["g"]; ?>"<?php if ($assignment["subs"][$_SESSION["uid"]]) echo " disabled"; ?>>Submit<?php if ($assignment["subs"][$_SESSION["uid"]]) echo "ted"; ?></a><?php } else { ?><a class="button<?php if ($assignment["subs"][$_SESSION["uid"]]) echo " bg-green"; ?>" href="page.php?a=<?php echo $assignment["id"]; ?>&g=<?php echo $_GET["g"]; ?>"<?php if ($assignment["subs"][$_SESSION["uid"]]) echo " disabled"; ?>><?php if (!$assignment["subs"][$_SESSION["uid"]]) echo "Mark as "; ?>Done</a><?php } ?>
                                <h3 class="datetime"><?php
            if ($assignment["due"] < time())
                echo "Late";
            elseif ($assignment["due"] < strtotime("+1 minute"))
                echo "Due in " . $assignment["due"] - time() . " seconds";
            elseif ($assignment["due"] < strtotime("+1 hour"))
                echo "Due in " . floor(($assignment["due"] - time()) / 60) . " minutes";
            elseif ($assignment["due"] < strtotime("Tomorrow"))
                echo "Due at " . date("g:i A", $assignment["due"]);
            elseif ($assignment["due"] < strtotime("+2 days midnight"))
                echo "Due tomorrow at " . date("g:i A", $assignment["due"]);
            elseif ($assignment["due"] < strtotime("+1 week"))
                echo "Due " . date("l, g:i A", $assignment["due"]);
            elseif ($assignment["due"] < strtotime("January 1 +1 year"))
                echo "Due " . date("F j, g:i A", $assignment["due"]);
            else
                echo "Due " . date("F j Y, g:i A", $assignment["due"]);
            ?></h3>
                                <p class="content"><?php echo $assignment["instructions"]; ?></p>
                                <?php if ($assignment["link"]) { ?>
                                <a class="content" href="<?php echo $assignment["link"]["url"]; ?>"><div class="comment">
                                    <h3><?php echo $assignment["link"]["title"]; ?></h3>
                                </div></a><?php } ?>
                            </div>
                            <?php endforeach; }
                            foreach ($posts as $post):
                            if ($_GET["view"] != "teacher" || $post["teacher"]) {
            $formattedreceivers = $post["receivers"];
            if ($post["receivers"] == "all") {
                $formattedreceivers = "Everyone";
            } elseif (!is_array($post["receivers"])) {
                $pgroup = json_decode(file_get_contents("groups/" . $post["receivers"] . "/index.txt"), true);
                $formattedreceivers = $pgroup["name"];
            } else {
                $formattedreceivers = "Some groups";
            }
            ?>
                            <div class="post" id="<?php echo $post["id"]; ?>">
                                <a onclick='save(<?php echo $post["id"]; ?>, "<?php echo (is_array($post["receivers"]) ? rawurlencode(json_encode($post["receivers"])) : $post["receivers"]); ?>");' class='save button'>Save</a><p class="save">Edit away... Don't forget to save.</p>
                                <h2 class="header"><?php echo $post["poster_name"]; ?></h2>
                                <h4 class="receivers">> <?php echo $formattedreceivers; ?></h4>
                                <h3 class="datetime"><?php
            if ($post["timestamp"] > strtotime("-1 minute"))
                echo abs($post["timestamp"] - time()) . " seconds ago";
            elseif ($post["timestamp"] > strtotime("-1 hour"))
                echo floor(abs(($post["timestamp"] - time()) / 60)) . " minutes ago";
            elseif ($post["timestamp"] > strtotime("00:00"))
                echo date("g:i A", $post["timestamp"]);
            elseif ($post["timestamp"] > strtotime("Yesterday 00:00"))
                echo "Yesterday, " . date("g:i A", $post["timestamp"]);
            elseif ($post["timestamp"] > strtotime("-1 week"))
                echo date("l, g:i A", $post["timestamp"]);
            elseif ($post["timestamp"] > strtotime("January 1"))
                echo date("F j, g:i A", $post["timestamp"]);
            else
                echo date("F j Y, g:i A", $post["timestamp"]);
            ?></h3>
                                <p class="content"><?php echo $post["content"]; ?></p>
                                <i class="comments icon-comments-4" onclick='if (!$(this).siblings(".postcomment")[0]) {$(".postcomment:first").clone().appendTo($(this).parent()).slideDown().find("textarea").val("");$(this).siblings(".postcomment").find(".p").val("<?php echo $post["id"]; ?>");$(this).siblings(".postcomment").find(".g").val("<?php echo (is_array($post["receivers"]) ? rawurlencode(json_encode($post["receivers"])) : $post["receivers"]); ?>");$(this).siblings(".postcomment").find("#r").val("student.php" + location.search);} else {$(this).siblings(".postcomment").slideUp();}'></i>
                                <?php if ($post["poster_id"] == $_SESSION["uid"]) { ?>
                                <i class='edit icon-pencil' onclick='$(this).siblings("p.content").attr("contenteditable", "true");$(this).slideUp();$(this).siblings(".save").slideDown();'></i>
                                <i class='delete icon-remove' onclick='del(<?php echo $post["id"]; ?>, "<?php echo (is_array($post["receivers"]) ? rawurlencode(json_encode($post["receivers"])) : $post["receivers"]); ?>");'></i><?php } ?>
                                <?php if ($post["comments"]) { ?><div class="comment">
                                    <?php if (count($post["comments"]) > 1) { ?><a onclick="showComments(<?php echo $post["id"]; ?>, '<?php echo (is_array($post["receivers"]) ? rawurlencode(json_encode($post["receivers"])) : $post["receivers"]); ?>')">Show all <?php echo count($post["comments"]); ?> comments</a><?php } ?>
                                    <a onclick='savec(<?php $temp = end(array_values($post["comments"])); echo $post["id"]; ?>, <?php echo $temp["id"]; ?>, "<?php echo (is_array($post["receivers"]) ? rawurlencode(json_encode($post["receivers"])) : $post["receivers"]); ?>");' class='save button'>Save</a><p class="save">Edit away... Don't forget to save.</p>
                                    <h3><?php echo $temp["poster_name"]; ?></h3>
                                    <p class="content"><?php echo $temp["content"]; ?></p>
                                <?php if ($temp["poster_id"] == $_SESSION["uid"]) { ?>
                                <i class='edit icon-pencil' onclick='$(this).siblings("p.content").attr("contenteditable", "true");$(this).slideUp();$(this).siblings(".save").slideDown();'></i>
                                <i class='delete icon-remove' onclick='delc(<?php echo $post["id"]; ?>, <?php echo $temp["id"]; ?>, "<?php echo (is_array($post["receivers"]) ? rawurlencode(json_encode($post["receivers"])) : $post["receivers"]); ?>");'></i><?php } ?>
                                </div><?php } ?>
                            </div>
                            <?php }
                            endforeach; ?>
                            <div class="postbox postcomment">
                                <form method="post" action="comment.php">
                                    <textarea name="post" placeholder="Comment away..."></textarea>
                                    <input type="hidden" value="student.php" name="r" id="r" />
                                    <input type="hidden" value="0" name="p" class="p" />
                                    <input type="hidden" value="0" name="g" class="g" />
                                    <button class="share" type="submit">Post Comment</button>
                                </form>
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