<?php
    // Start session to keep temporary session info
    session_start();
    // Send you away to the login if you're not a parent to keep out unauthorised users
    if (!$_SESSION["stp"]) {
        header("Location: logout.php");
    }
    $user = json_decode(file_get_contents("users/" . $_SESSION["uid"] . ".txt"), true);
    $group = json_decode(file_get_contents("groups/" . $_GET["g"] . "/index.txt"), true);
    if (!$group["members"][$_SESSION["uid"]]["owner"]) {
        header("Location: addremgroup.php");
        exit();
    }
    if (isset($_POST["gname"])) {
        if (isset($_POST["gname"]) && isset($_POST["memnum"]) && isset($_POST["what"])) {
            if (($_POST["what"] === "Other" && isset($_POST["othergroup"])) || $_POST["what"] !== "Other") {
                if (($_POST["what"] === "Class" && isset($_POST["agenda"]) && isset($_POST["parents"]) && isset($_POST["homeroom"]) && isset($_POST["subjects"])) || $_POST["what"] !== "Class") {
                    if ((($_POST["what"] === "Class" && $_POST["agenda"] === "Yes" && isset($_POST["daynum"]) && isset($_POST["dayname"]) && count($_POST["dayname"]) == $_POST["daynum"]) || $_POST["agenda"] === "No") || $_POST["what"] !== "Class") {
                        $gid = $_GET["g"];
                        $put = $group;
                        $put["name"] = $_POST["gname"];
                        $put["allowmembers"] = $_POST["memnum"];
                        $put["what"] = $_POST["what"];

                        if ($_POST["what"] === "Class") {
                            $put["agenda"] = ($_POST["agenda"] === "Yes") ? TRUE : FALSE;
                            if ($_POST["agenda"] === "Yes") {
                                $put["daynum"] = $_POST["daynum"];
                                if ($_POST["daynum"]) {
                                    $put["dayname"] = $_POST["dayname"];
                                }
                            }
                            $put["parents"] = ($_POST["parents"] === "Yes") ? TRUE : FALSE;
                            $put["homeroom"] = ($_POST["homeroom"] === "Yes") ? TRUE : FALSE;
                            $put["subjects"] = ltrim(rtrim(explode(",", $_POST["subjects"])));
                        }
                        file_put_contents("groups/$gid/index.txt", json_encode($put));
                        $thx = TRUE;
                        $group = $put;
                    } else
                        $errors = TRUE;
                } else
                    $errors = TRUE;
            } else
                $errors = TRUE;
        } else
            $errors = TRUE;
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <!-- Here's where I include all my JS and CSS -->
        <script src="components/webcomponents.min.js"></script>
        <link rel="import" href="components/paper-spinner/paper-spinner.html">
        <link rel="import" href="components/core-toolbar/core-toolbar.html">
        <link rel="import" href="components/core-icons/core-icons.html">
        <link rel="import" href="components/core-media-query/core-media-query.html">
        <link rel="import" href="components/paper-icon-button/paper-icon-button.html">
        <link rel="import" href="components/font-roboto/roboto.html">
        <?php include_once("header.php");
        if ($errors): ?>
        <script src="../metro/jquery.notific8.js"></script>
        <script src="../metro/jquery.notific8.min.js"></script>
        <link rel="stylesheet" href="../metro/jquery.notific8.min.css">
        <script>
            $.notific8('Invalid input!', {theme: 'ruby', sticky: true});
        </script>
        <?php endif; ?>
        <title>Manage a group</title>
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
            $(document).ready(function () {
                if ($("[value=Class]:checked").length) {
                    $("#class").slideDown();
                }
                if ($("[value=Other]:checked").length) {
                    $("#othergroup").slideDown();
                }
            });
            function sendin() {
                $('.vertical-menu').addClass("open");
                $('.page').addClass("open");
            }
            function takeout() {
                $('.page').removeClass("open");
                $('.vertical-menu').removeClass("open");
            }
        </script>
    </head>
    <body class="metro" unresolved>
        <!-- Fancy spinny preloader thingy -->
        <div id="loader-wrapper">
            <p>Loading...</p>
            <paper-spinner id="loader" active></paper-spinner>
            <div class="loader-section"></div>
        </div>
        <nav class="vertical-menu">
            <ul>
                <div class="icon-cancel-2 fg-white large right" onclick="takeout()"></div>
                <li class="title fg-white"><a href="<?php echo $_SESSION["stp"]; ?>.php"><i class="icon-arrow-left-3 smaller fg-white on-left"></i></a>Account</li>
                <li><a class="fg-white" href="settings.php">Add <?= ($_SESSION["stp"] === "student") ? "Parent" : "Child" ; ?></a></li>
                <li><a class="fg-white" href="points.php">View Points</a></li>
                <li><a class="fg-white" href="members.php">Manage Members</a></li>
                <li><a class="fg-white" href="addremgroup.php">Manage Groups</a></li>
                <li><a class="fg-white" href="deleteaccount.php">Delete Account</a></li>
                <li><a class="fg-white" href="about.php">About PencilCase</a></li>
            </ul>
            <div class="user-div fg-white"><img src="<?php echo $_SESSION["photo"]; ?>" /> <?php echo $_SESSION["name"]; ?><a class="fg-white" href="logout.php"><i class="icon-exit fg-white smaller" unselectable="on"></i></a></div>
        </nav>
        <!-- Account stuff -->
        <core-toolbar>
            <paper-icon-button icon="menu" onclick="sendin();"></paper-icon-button>
            <span flex>Manage Group</span>
            <a href="feedback.php"><paper-icon-button icon="bug-report"></paper-icon-button></a>
        </core-toolbar>
        <div class="page">
            <?php if (!$thx): ?>
            <form method="post">
                <div class="input-control text"><input type="text" name="gname" placeholder="Group name" value="<?php echo $group["name"]; ?>" required /><button class="btn-clear"></button></div><br/>
                <div class="input-control text"><input type="number" name="memnum" min="1" step="1" placeholder="How many members are in your group?" value="<?php echo $group["allowmembers"]; ?>" required /></div>
                <label for="what">What is this group for?</label>
                <?php if ($_SESSION["stp"] === "teacher"): ?>
                <input type="radio" name="what" value="Class" onclick="$('#class').slideDown();$('#othergroup').slideUp();" <?php if ($group["what"] === "Class") { ?>checked<?php } ?> required />Class 
                <?php endif; ?>
                <input type="radio" name="what" value="Club" onclick="$('#class').slideUp();$('#othergroup').slideUp();" <?php if ($group["what"] === "Club") { ?>checked<?php } ?> />Club 
                <input type="radio" name="what" value="Other" onclick="$('#class').slideUp();$('#othergroup').slideDown();" <?php if ($group["what"] !== "Class" && $group["what"] !== "Club") { ?>checked<?php } ?> />Other<br/>
                <div class="input-control text" id="othergroup" style="display: none;"><input type="text" name="othergroup" placeholder="What is your group for?" /></div>
                <div id="class" style="display: none;">
                    <label for="agenda">Would you like to use our agenda software?</label>
                    <input type="radio" name="agenda" value="Yes" onclick="$('#agenda').slideDown();" />Yes 
                    <input type="radio" name="agenda" value="No" onclick="$('#agenda').slideUp();" />No
                    <div id="agenda" style="display: none;">
                        <label for="daynum">How many days of the week do you teach?</label>
                        <input type="radio" name="daynum" value="5" onclick="$('#dayname').slideDown();" />5 
                        <input type="radio" name="daynum" value="1" onclick="$('#dayname').slideDown();" />1 
                        <input type="radio" name="daynum" value="7" onclick="$('#dayname').slideDown();" />7 
                        <input type="radio" name="daynum" value="2" onclick="$('#dayname').slideDown();" />2 
                        <input type="radio" name="daynum" value="3" onclick="$('#dayname').slideDown();" />3 
                        <input type="radio" name="daynum" value="4" onclick="$('#dayname').slideDown();" />4 
                        <input type="radio" name="daynum" value="6" onclick="$('#dayname').slideDown();" />6 
                        <input type="radio" name="daynum" value="0" onclick="$('#dayname').slideUp();" />It depends
                        <div id="dayname" style="display: none;">
                            <label for="dayname">On which days of the week do you teach?</label>
                            <input type="checkbox" name="dayname[]" value="Monday" />Monday 
                            <input type="checkbox" name="dayname[]" value="Tuesday" />Tuesday 
                            <input type="checkbox" name="dayname[]" value="Wednesday" />Wednesday 
                            <input type="checkbox" name="dayname[]" value="Thursday" />Thursday 
                            <input type="checkbox" name="dayname[]" value="Friday" />Friday 
                            <input type="checkbox" name="dayname[]" value="Saturday" />Saturday 
                            <input type="checkbox" name="dayname[]" value="Sunday" />Sunday
                        </div>
                    </div>
                    <label for="parents">Should adding parents to watch progress and see announcements be mandatory?</label>
                    <input type="radio" name="parents" value="Yes" />Yes 
                    <input type="radio" name="parents" value="No" />No
                    <label for="homeroom">Are you the homeroom teacher to these students?</label>
                    <input type="radio" name="homeroom" value="Yes" />Yes 
                    <input type="radio" name="homeroom" value="No" />No<br/>
                    <div class="input-control text"><input type="text" name="subjects" placeholder="Which subjects do you teach? Make sure to separate them with a comma." /><button class="btn-clear"></button></div>
                </div>
                <br/>
                <input type="submit" value="Update Group">
                <a class="button" href="?delete=<?= $_GET["g"]; ?>">Delete Group</a>
                <a class="button" href="changecode.php?g=<?= $_GET["g"]; ?>">Get new group code</a>
            </form>
            <?php else: ?>
            <h1>Your changes have been saved.</h1>
            <?php endif; ?>
        </div>
    </body>
</html>
