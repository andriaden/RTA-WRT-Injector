<?php
include("config.inc.php");
?>
<!doctype html>
<html lang="en">
<head>
    <?php
        $title = "Home";
        include("header.php");
		exec('chmod -R 755 /www/rtawrt-injector/* && chmod -R 755 /usr/share/rtawrt-injector/*');
    ?>
</head>
<body>
    <div class="container">
        <h2>RTA-WRT INJECTOR</h2>
        <div class="btn-group">
            <a href="index.php" class="btn btn-custom" role="button">Home</a>
            <a href="log.php" class="btn btn-custom" role="button">Log</a>
            <a href="config.php" class="btn btn-custom" role="button">Config</a>
            <a href="about.php" class="btn btn-custom" role="button">About</a>
        </div>
        
        <div class="form-group">
            
        </div>

        <?php include('footer.php'); ?>
    </div>
    <?php include("javascript.php"); ?>
</body>
</html>
