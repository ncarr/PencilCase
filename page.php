<?php
    session_start();
    // Send you away to the login if you're not signed in to keep out unauthorised users
    if (!$_SESSION["stp"]) {
        header("Location: logout.php");
    }
    // Decode user data file to find parents
    $userdata = json_decode(file_get_contents("users/" . $_SESSION["uid"] . ".txt"), true);
    $parents = $userdata["parents"];
    // Decode user data array to find groups
    $groups = $userdata["groups"];
    if ($_POST["title"] && $_POST["due"] && $_POST["subject"]) {
        $hw = json_decode(file_get_contents("groups/" . $_POST["g"] . "/homework.txt"), true);
        $id = mt_rand(100000000, 999999999);
        $hw[$id] = array("title" => htmlspecialchars($_POST["title"]), "due" => strtotime($_POST["due"]), "receivers" => $_POST["g"], "subject" => $_POST["subject"], "id" => $id);
        if ($_POST["link"]) {
            $uri = parse_url($_POST["link"], PHP_URL_SCHEME) === null ? "http://" . $_POST["link"] : $_POST["link"];
            $url = file_get_contents($uri);
            if (strlen($url) > 0) {
                preg_match("/\<title\>(.*)\<\/title\>/", $url, $utitle);
            }
            $hw[$id]["link"]["title"] = $utitle[1];
            $hw[$id]["link"]["url"] = $uri;
        }
        if ($_POST["instructions"])
            $hw[$id]["instructions"] = $_POST["instructions"];
        if (!$_POST["mandatory"])
            $hw[$id]["optional"] = TRUE;
        if ($_POST["online"])
            $hw[$id]["online"] = TRUE;
        if ($_POST["peer"])
            $hw[$id]["peer"] = $_POST["peer"];
        if ($_POST["mark"])
            $hw[$id]["mark"] = TRUE;
        if ($_POST["present"])
            $hw[$id]["present"] = TRUE;
        file_put_contents("groups/" . $_POST["g"] . "/homework.txt", json_encode($hw));
    }
    if ($_GET["p"] && $_GET["g"]) {
        if (file_exists("groups/" . $_GET["g"] . "/homework.txt")) {
            $homework = json_decode(file_get_contents("groups/" . $_GET["g"] . "/homework.txt"), true);
        } else {
            $homework = array();
        }
    }
    if ($_GET["s"] && $_GET["g"]) {
        $hw = json_decode(file_get_contents("groups/" . $_GET["g"] . "/homework.txt"), true);
        $hw = $hw[$_GET["s"]];
    }
    if ($_GET["v"] && $_GET["g"]) {
        $hws = json_decode(file_get_contents("groups/" . $_GET["g"] . "/homework.txt"), true);
        $hw = $hws[$_GET["v"]];
        $temp = $hw["subs"][$_SESSION["uid"]]["peer"];
        foreach ($temp as $peer) {
            $peers[] = $hws["subs"][$peer];
        }
    }
    if ($_GET["a"] && $_GET["g"]) {
        $hws = json_decode(file_get_contents("groups/" . $_GET["g"] . "/homework.txt"), true);
        $hw = $hws[$_GET["a"]];
        $id = $_SESSION["uid"];
        $hw["subs"][$id] = array("poster_name" => $_SESSION["name"], "id" => $_SESSION["uid"]);
        if ($_POST["link"]) {
            $uri = parse_url($_POST["link"], PHP_URL_SCHEME) === null ? "http://" . $_POST["link"] : $_POST["link"];
            $url = file_get_contents($uri);
            if (strlen($url) > 0) {
                preg_match("/\<title\>(.*)\<\/title\>/", $url, $utitle);
            }
            $hw["subs"][$id]["link"]["title"] = $utitle[1];
            $hw["subs"][$id]["link"]["url"] = $uri;
        }
        if ($_POST["peer"]) {
            foreach ($hw["subs"] as $peer) {
                if ($peer["id"] != $id && count($peer["peers"] < $_POST["peer"])) {
                    $hw["subs"][$id]["peers"][] = $peer["id"];
                    $hw["subs"][$peer["id"]]["peers"][] = $id;
                }
            }
        }
        if ($_POST["comments"]) {
            $hw["subs"][$id]["comments"] = htmlspecialchars($_POST["comments"]);
        }
        $hws[$_GET["a"]] = $hw;
        file_put_contents("groups/" . $_GET["g"] . "/homework.txt", json_encode($hws));
        header("Location: ?v=" . $_GET["a"] . "&g=" . $_GET["g"]);
    }
    if ($_GET["u"] && $_GET["g"]) {
        $hw = json_decode(file_get_contents("groups/" . $_GET["g"] . "/homework.txt"), true);
        unset($hw[$_GET["u"]]["subs"][$_SESSION["uid"]]);
        file_put_contents("groups/" . $_GET["g"] . "/homework.txt", json_encode($hw));
        header("Location: ?p=homework&g=" . $_GET["g"]);
    }
    if ($_GET["d"] && $_GET["g"]) {
        $hw = json_decode(file_get_contents("groups/" . $_GET["g"] . "/homework.txt"), true);
        unset($hw[$_GET["d"]]);
        file_put_contents("groups/" . $_GET["g"] . "/homework.txt", json_encode($hw));
        header("Location: ?p=homework&g=" . $_GET["g"]);
    }
    if (count($groups) === 1 && !$_GET["g"]) {
        $group = reset($groups);
        header("Location: ?p=" . $_GET["p"] . "&g=" . $group["id"]);
        exit();
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
        <!-- Here's where I include all my JS and CSS -->
        <?php include_once("header.php"); ?>
        <title>Class Pages</title>
        <script>
            function sendin() {
                $('.vertical-menu').addClass("open");
                $('.page').addClass("open");
            }
            function takeout() {
                $('.page').removeClass("open");
                $('.vertical-menu').removeClass("open");
            }
        </script>
        <script src="metro/js/metro-accordion.js"></script>
        <style>
            .postbox input:first-child {
                border-bottom: 1px solid black;
                border-top: none;
            }
            .postbox input {
                border-top: 1px solid black;
            }
            .postbox {
                height: 650px;
            }
            .input-control {
                display: block;
                margin: 10px;
                color: white;
            }
            .metro .page button {
                display: block;
            }
            .metro .post a.content {
                float: none;
            }
        </style>
    </head>
    <body class="metro" unresolved>
        <!-- Fancy spinny preloader thingy -->
        <div id="loader-wrapper">
            <p>PencilCase is loading...</p>
            <paper-spinner id="loader" active></paper-spinner>
            <div class="loader-section"></div>
        </div>
        <!-- Account stuff -->
        <paper-drawer-panel force-narrow>
            <paper-header-panel mode="scroll" drawer>
                <paper-toolbar class="tall" style="background-image: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url('<?php echo $_SESSION["cover"]; ?>'); background-size: cover">
                    <div title>PencilCase</div>
                    <div class="top">
                        <img src="<?php echo $_SESSION["photo"]; ?>" />
                    </div>
                    <div class="bottom" style="text-align: left;">
                        <p class="fg-white"><?php echo $_SESSION["name"]; ?></p>
                        <p style="color: rgba(255, 255, 255, 0.8)"><?php echo $_SESSION["email"]; ?></p>
                    </div>
                </paper-toolbar>
                    <paper-menu>
                        <paper-item><a class="fg-black" href="?">All Groups</a></paper-item>
                        <?php if ($groups) { foreach ($groups as $group): ?>
                        <paper-item><a class="fg-black" href="?group=<?php echo $group["id"]; ?>"><?php echo $group["name"]; ?></a></paper-item>
                        <?php endforeach; } ?>
                        <section>
                            <paper-item><a class="fg-black" href="student.php<?php echo ($_GET["group"]) ? "?group=" . $_GET["g"] : ""; ?>"><i class="icon-comments-4 fg-black on-left"></i>Feed</a></paper-item>
                        </section>
                        <section>
                            <paper-item><a class="fg-black" href="feedback.php"><i class="icon-lamp-2 fg-black on-left"></i>Feedback Centre</a></paper-item>
                        </section>
                </paper-menu>
                    <!--It's Javert for the sidebar! <paper-item><img alt = "Javert your eyes kids!" src = "http://i.imgur.com/UiXAker.gif"></paper-item>-->
            </paper-header-panel>
            <paper-header-panel mode="waterfall" main>
            <?php if (!$_GET["p"] && !$_GET["s"] && !$_GET["v"]) { ?>
                <paper-toolbar>
                    <paper-icon-button icon="menu" paper-drawer-toggle></paper-icon-button>
                    <div title>Pages</div>
                </paper-toolbar>
                <!-- Renders big page full of chat messages -->
                <div class="page">
                    <div class="listview">
                        <?php if ($_GET["g"]) { ?>
                        <a href="?p=homework&g=<?php echo $_GET["g"]; ?>" class="list shadow">
                            <div class="list-content">
                                <div class="data">
                                    <span class="list-title">Homework</span>
                                    <span class="list-remark">Last edited: <?php
        if (file_exists("groups/" . $_GET["g"] . "/homework.txt"))
            $time = filemtime("groups/" . $_GET["g"] . "/homework.txt");
        if (!$time)
            echo "Never";
        elseif ($time > strtotime("-1 minute"))
            echo abs($time - time()) . " seconds ago";
        elseif ($time > strtotime("-1 hour"))
            echo floor(abs(($time - time()) / 60)) . " minutes ago";
        elseif ($time > strtotime("00:00"))
            echo date("g:i A", $time);
        elseif ($time > strtotime("Yesterday 00:00"))
            echo "Yesterday, " . date("g:i A", $time);
        elseif ($time > strtotime("-1 week"))
            echo date("l, g:i A", $time);
        elseif ($time > strtotime("January 1"))
            echo date("F j, g:i A", $time);
        else
            echo date("F j Y, g:i A", $time);
        ?></span>
                                </div>
                            </div>
                        </a>
                        <?php } else {
                        foreach ($groups as $group) {?>
                        <a href="?g=<?php echo $group["id"]; ?>" class="list shadow">
                            <div class="list-content">
                                <div class="data">
                                    <span class="list-title"><?php echo $group["name"]; ?></span>
                                </div>
                            </div>
                        </a>
                        <?php }
                        } ?>
                    </div>
                </div>
                    <?php } elseif ($_GET["s"] && $_GET["g"]) { ?>
                <!-- Top bar -->
                <paper-toolbar>
                    <paper-icon-button icon="arrow-back" onclick="history.back(1);"></paper-icon-button>
                    <div title>Hand in <?= $hw["title"]; ?></div>
                </paper-toolbar>
                <!-- Renders big page full of chat messages -->
                <div class="page">
                    <div class="posts">
                        <div class="post" id="<?php echo $hw["id"]; ?>">
                            <h2 class="header"><?php echo $hw["title"]; ?></h2>
                            <h4 class="receivers"><?php echo $hw["subject"]; ?> Assignment</h4>
                            <h3 class="datetime"><?php
        if ($hw["due"] < time())
            echo "Late";
        elseif ($hw["due"] < strtotime("+1 minute"))
            echo "Due in " . $hw["due"] - time() . " seconds";
        elseif ($hw["due"] < strtotime("+1 hour"))
            echo "Due in " . floor(($hw["due"] - time()) / 60) . " minutes";
        elseif ($hw["due"] < strtotime("Tomorrow"))
            echo "Due at " . date("g:i A", $hw["due"]);
        elseif ($hw["due"] < strtotime("+2 days midnight"))
            echo "Due tomorrow at " . date("g:i A", $hw["due"]);
        elseif ($hw["due"] < strtotime("+1 week"))
            echo "Due " . date("l, g:i A", $hw["due"]);
        elseif ($hw["due"] < strtotime("January 1 +1 year"))
            echo "Due " . date("F j, g:i A", $hw["due"]);
        else
            echo "Due " . date("F j Y, g:i A", $hw["due"]);
        ?></h3>
                            <p class="content"><?php echo $hw["instructions"]; ?></p>
                            <?php if ($hw["link"]) { ?>
                            <a class="content" href="<?php echo $hw["link"]["url"]; ?>"><div class="comment">
                                <h3><?php echo $hw["link"]["title"]; ?></h3>
                            </div></a><?php } ?>
                        </div>
                    </div>
                    <div class="postbox">
                        <form method="post" action="?a=<?= $_GET["s"]; ?>&g=<?= $_GET["g"]; ?>">
                            <h2 class="header">Your Submission</h2>
                            <input type="url" name="link" placeholder="Optional link to your work (Make sure we have permission to see it)" />
                            <textarea name="comments" placeholder="Optional comments..."></textarea>
                            <input type="hidden" value="" name="g" class="g" />
                            <button class="share" type="submit">Hand in</button>
                        </form>
                    </div>
                </div>
                <?php } elseif ($_GET["v"] && $_GET["g"]) { ?>
                <!-- Top bar -->
                <paper-toolbar>
                    <paper-icon-button icon="arrow-back" onclick="history.back(1);"></paper-icon-button>
                    <div title>View <?= $hw["title"]; ?></div>
                </paper-toolbar>
                <!-- Renders big page full of chat messages -->
                <div class="page">
                    <div class="posts">
                        <?php if ($_SESSION["stp"] == "teacher" || $_GET["stest"]) {
                        if ($hw["mark"] && !$hw["marked"]) { ?>
                        <h2>Needs to be marked.</h2>
                        <?php } if ($hw["present"] && !$hw["presented"]) { ?>
                        <h2>Needs to be presented.</h2>
                        <?php } if ($hw["mark"] && $hw["marked"] && !$hw["present"] || !$hw["mark"] && $hw["presented"] && $hw["present"] || !$hw["mark"] && !$hw["present"]) { ?>
                        <h2>Complete!</h2>
                        <?php }
                        } ?>
                        <div class="post" id="<?php echo $hw["id"]; ?>">
                            <h2 class="header"><?php echo $hw["title"]; ?></h2>
                            <h4 class="receivers"><?php echo $hw["subject"]; ?> Assignment</h4>
                            <?php if ($_SESSION["stp"] == "teacher" || $_GET["stest"]) { ?>
                            <a class="button" href="?d=<?php echo $_GET["v"]; ?>&g=<?php echo $_GET["g"]; ?>">Delete assignment and submissions</a>
                            <?php } ?>
                            <h3 class="datetime"><?php
        if ($hw["due"] < time())
            echo "Late";
        elseif ($hw["due"] < strtotime("+1 minute"))
            echo "Due in " . $hw["due"] - time() . " seconds";
        elseif ($hw["due"] < strtotime("+1 hour"))
            echo "Due in " . floor(($hw["due"] - time()) / 60) . " minutes";
        elseif ($hw["due"] < strtotime("Tomorrow"))
            echo "Due at " . date("g:i A", $hw["due"]);
        elseif ($hw["due"] < strtotime("+2 days midnight"))
            echo "Due tomorrow at " . date("g:i A", $hw["due"]);
        elseif ($hw["due"] < strtotime("+1 week"))
            echo "Due " . date("l, g:i A", $hw["due"]);
        elseif ($hw["due"] < strtotime("January 1 +1 year"))
            echo "Due " . date("F j, g:i A", $hw["due"]);
        else
            echo "Due " . date("F j Y, g:i A", $hw["due"]);
        ?></h3>
                            <p class="content"><?php echo $hw["instructions"]; ?></p>
                            <?php if ($hw["link"]) { ?>
                            <a class="content" href="<?php echo $hw["link"]["url"]; ?>"><div class="comment">
                                <h3><?php echo $hw["link"]["title"]; ?></h3>
                                <p style="opacity: 0.5;"><?php echo $hw["link"]["url"]; ?></p>
                            </div></a><?php } ?>
                        </div>
                    <?php if ($_SESSION["stp"] == "teacher" || $_GET["stest"]) { ?>
                    <h2>Submissions</h2>
                    <div class="accordion" data-role="accordion">
                        <?php foreach ($hw["subs"] as $sub) { ?>
                         <div class="accordion-frame">
                            <a href="#" class="heading"><?php echo $sub["poster_name"]; ?></a>
                            <div class="content">
                                <p><?php echo $sub["comments"]; ?></p>
                                <hr/>
                                <a href="<?php echo $sub["link"]["url"]; ?>">
                                    <h3><?php echo $sub["link"]["title"]; ?></h3>
                                    <p style="opacity: 0.5;"><?php echo $sub["link"]["url"]; ?></p>
                                </a>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                    <?php } else { ?>
                    <div class="post">
                            <a class="button" href="?u=<?php echo $_GET["v"]; ?>&g=<?php echo $_GET["g"]; ?>">Unsubmit</a>
                            <h2 class="header">Your Submission</h2>
                            <?php if ($hw["subs"][$_SESSION["uid"]]["comments"]) { ?>
                            <p><?php echo $hw["subs"][$_SESSION["uid"]]["comments"]; ?></p>
                            <?php } if ($hw["subs"][$_SESSION["uid"]]["link"]) { ?>
                            <a class="content" href="<?php echo $hw["subs"][$_SESSION["uid"]]["link"]["url"]; ?>"><div class="comment">
                                <h3><?php echo $hw["subs"][$_SESSION["uid"]]["link"]["title"]; ?></h3>
                                <p style="opacity: 0.5;"><?php echo $hw["subs"][$_SESSION["uid"]]["link"]["url"]; ?></p>
                            </div></a>
                            <?php } if (!$hw["subs"][$_SESSION["uid"]]["comments"] && !$hw["subs"][$_SESSION["uid"]]["link"]) { ?>
                            <p>No data</p>
                            <?php } ?>
                    </div>
                    <?php foreach ($peers as $peer) { ?>
                    <div class="post">
                            <h2 class="header">Peer to Mark</h2>
                            <?php if ($peer["comments"]) { ?>
                            <p><?php echo $peer["comments"]; ?></p>
                            <?php } if ($peer["link"]) { ?>
                            <a class="content" href="<?php echo $peer["link"]["url"]; ?>"><div class="comment">
                                <h3><?php echo $peer["link"]["title"]; ?></h3>
                                <p style="opacity: 0.5;"><?php echo $peer["link"]["url"]; ?></p>
                            </div></a>
                            <?php } if (!$peer["comments"] && !$peer["link"]) { ?>
                            <p>No data to mark</p>
                            <?php }
                    } ?>
                    </div>
                    <?php } ?>
                    </div>
                </div>
                <?php } else { ?>
                <!-- Top bar -->
                <paper-toolbar>
                    <paper-icon-button icon="menu" paper-drawer-toggle></paper-icon-button>
                    <div title>Homework</div>
                </paper-toolbar>
                <div class="page">
                    <?php if ($_SESSION["stp"] == "teacher") { ?>
                    <div class="postbox">
                        <form method="post">
                            <input type="text" name="title" placeholder="Assignment title" required />
                            <textarea name="instructions" placeholder="Optional instructions..."></textarea>
                            <input type="url" name="link" placeholder="Optional link to instructions (Make sure we have permission to see it)" />
                            <input type="datetime-local" name="due" placeholder="Due date e.g. Thursday 23:59" required />
                            <input type="text" name="subject" placeholder="Subject (e.g. science)" required />
                            <input type="number" name="peer" placeholder="Number of students with which to peer edit" />
                            <input type="hidden" value="<?php echo $_GET["g"]; ?>" name="g" class="g" />
                            <div class="input-control switch">
                                <label>
                                Mandatory
                                    <input type="checkbox" name="mandatory" checked />
                                    <span class="check"></span>
                                </label>
                            </div>
                            <div class="input-control switch">
                                <label>
                                Let students hand this in through PencilCase
                                    <input type="checkbox" name="online" checked />
                                    <span class="check"></span>
                                </label>
                            </div>
                            <br/>
                            <span class="fg-white on-right-more">Will this assignment be:</span>
                            <div class="input-control checkbox">
                                <label>
                                    <input type="checkbox" name="mark" checked />
                                    <span class="check"></span>
                                    Marked
                                </label>
                            </div>
                            <div class="input-control checkbox">
                                <label>
                                    <input type="checkbox" name="present" />
                                    <span class="check"></span>
                                    Presented
                                </label>
                            </div>
                            <button class="share" type="submit">Create assignment</button>
                        </form>
                    </div>
                    <?php } ?>
                    <div class="posts">
                        <?php foreach ($homework as $assignment): ?>
                        <div class="post" id="<?php echo $assignment["id"]; ?>">
                            <a onclick='save(<?php echo $assignment["id"]; ?>, "<?php echo $assignment["receivers"]; ?>");' class='save button'>Save</a><p class="save">Edit away... Don't forget to save.</p>
                            <h2 class="header"><?php echo $assignment["title"]; ?></h2>
                            <h4 class="receivers"><?php echo $assignment["subject"]; ?> Assignment</h4>
                            <?php if ($_SESSION["stp"] == "teacher") { ?>
                            <a class="button" href="?v=<?php echo $assignment["id"]; ?>&g=<?php echo $_GET["g"]; ?>"><?php if ($assignment["mark"] && !$assignment["marked"]) echo "Mark"; if ($assignment["mark"] && !$assignment["marked"] && $assignment["present"] && !$assignment["presented"]) echo "/"; if ($assignment["present"] && !$assignment["presented"]) echo "Present"; if ($assignment["mark"] && $assignment["marked"] && !$assignment["present"] || !$assignment["mark"] && $assignment["presented"] && $assignment["present"] || !$assignment["mark"] && !$assignment["present"]) echo "View"; ?></a><?php } elseif (!$assignment["online"]) { ?>
                            <a class="button<?php if ($assignment["subs"][$_SESSION["uid"]]) echo " bg-green"; ?>" href="?a=<?php echo $assignment["id"]; ?>&g=<?php echo $_GET["g"]; ?>"<?php if ($assignment["subs"][$_SESSION["uid"]]) echo " disabled"; ?>><?php if (!$assignment["subs"][$_SESSION["uid"]]) echo "Mark as "; ?>Done</a><?php } elseif ($assignment["online"] && $_SESSION["stp"] == "student") { ?>
                            <a class="button<?php if ($assignment["subs"][$_SESSION["uid"]]) echo " bg-green"; ?>" href="<?php if (!$assignment["subs"][$_SESSION["uid"]]) {?>?s=<?php echo $assignment["id"]; ?>&g=<?php echo $_GET["g"]; } else {?>?v=<?php echo $assignment["id"]; ?>&g=<?php echo $_GET["g"]; } ?>">Submit<?php if ($assignment["subs"][$_SESSION["uid"]]) echo "ted"; ?></a><?php } ?>
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
                                <p style="opacity: 0.5;"><?php echo $assignment["link"]["url"]; ?></p>
                            </div></a><?php } ?>
                        </div>
                        <?php endforeach;
                        if (!$homework) { ?>
                        <div class="post">
                            <h2 class="header">Woohoo! No homework!</h2>
                            <p class="content">Let's hope this isn't an error!</p>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                    <?php } ?>
            </paper-header-panel>
    </body>
</html>