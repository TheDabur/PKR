<?php
chdir("/var/www/repo/");
// Repo Setting
global $SETTING;
$SETTING = Array();
$SETTING["OUTPUT"] 		= "/var/www/repo/new.repo/";		// Output repo files
$SETTING["EX.LINKS"] 	= 1;								// Use external redirect if can
$SETTING["RM.REPOS"] 	= 1;								// Remove other repo request
$SETTING["MY.ADDONS"] = Array("/var/www/repo/new.repo.addons.txt"); // Addons list to add to repo

include "func/repo.php";

?>