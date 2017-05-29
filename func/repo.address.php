<?php
$repo = Array();
$super_domain = "http://ftp.acc.umu.se/mirror/addons.superrepo.org/v7/";
$repo["superrepo.v7.16"]["xml"] = $super_domain.".xml/jarvis/all/addons.xml";
$repo["superrepo.v7.16"]["zip"] = $super_domain."addons/";
$repo["superrepo.v7.16s"]["xml"] = $super_domain.".xml/jarvis/genres/adult/addons.xml";
$repo["superrepo.v7.16s"]["zip"] = $super_domain."addons/";
$repo["superrepo.v7.17"]["xml"] = $super_domain.".xml/krypton/all/addons.xml";
$repo["superrepo.v7.17"]["zip"] = $super_domain."addons/";
$repo["superrepo.v7.17s"]["xml"] = $super_domain.".xml/krypton/genres/adult/addons.xml";
$repo["superrepo.v7.17s"]["zip"] = $super_domain."addons/";

$repo["meta4"]["xml"] = "http://raw.github.com/metate/meta4kodi/master/addons.xml";
$repo["meta4"]["zip"] = "http://raw.github.com/metate/meta4kodi/master/zip";
$repo["meta4"]["addon"]["plugin.video.meta"] = 1;
$repo["meta4"]["addon"]["context.meta"] = 1;

$self_addon = Array();
$self_addon["plugin.audio.tuneinradio"]["xml"] = "https://raw.githubusercontent.com/brianhornsby/plugin.audio.tuneinradio/master/addon.xml";
$self_addon["plugin.audio.tuneinradio"]["zip"] = "https://github.com/brianhornsby/plugin.audio.tuneinradio/archive/master.zip";
?>
