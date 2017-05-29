<?php
error_reporting(E_ALL);
set_time_limit(3000);
ini_set('max_execution_time', 3000);
ini_set('default_socket_timeout', 30);

include "func/func.php";
include "func/repo.address.php";
include "func/repo.func.php";

if (!file_exists($SETTING["OUTPUT"]))	mkdir ($SETTING["OUTPUT"],0755);
chdir($SETTING["OUTPUT"]);

$time_run = microtime(true); // Gets microseconds

echo "\nCreate TMP Folder";
global $FOLDER;
$FOLDER = "_updates";
if (file_exists($FOLDER))
	delTree($FOLDER);
mkdir ($FOLDER,0777);

echo "\nGet Current Repo Addons";
$my_repo = "addons.xml";
CreateXML($my_repo);

$my_packs = Array();
$xml = simplexml_load_file($my_repo);
$packs_xml = $xml->xpath("//addon");
foreach ($packs_xml as $pack)
	$my_packs[trim($pack[0]->attributes()->id)] = trim($pack[0]->attributes()->version);

echo "\nGet Kodi Repo Addons";
$kodi_packs = Array();
$kodi_repo = Array("helix","isengard","jarvis");
$kodi_mirror = "http://mirror.us.leaseweb.net/xbmc/addons";
foreach ($kodi_repo as $k=>$name)
{
	echo ".$name";
	$html = Get_HTML("$kodi_mirror/$name/addons.xml",1);
	$xml = simplexml_load_string($html);
	$packs_xml = $xml->xpath("//addon");
	foreach ($packs_xml as $pack)
	{
		$id = trim($pack[0]->attributes()->id);
		$version = trim($pack[0]->attributes()->version);
		$kodi_packs[$id]["version"] = $version;
		$kodi_packs[$id]["zip"] = "$kodi_mirror/$name/".$id."/".$id."-".$version.".zip";
		$kodi_packs[$id]["depend"] = GetAddonDepend($pack[0]);
	}
}

echo "\nGet External Repos Addons: ";
global $getAddons;
$getAddons = Array();

foreach ($repo as $repo_name => $repo_info)
{
	echo $repo_name.".";
	$xml_fix = GetAddonXML($repo_info["xml"]);
	if (!$xml_fix)
	{
		echo "\nError: Empty repo ".$repo_name."\n";
		unset ($repo[$repo_name]);
		continue;
	}
	$xml = simplexml_load_string($xml_fix);
	$packs_xml = $xml->xpath("//addon");

	// Get Download zip folder
	if (!isset($repo[$repo_name]["zip"]))
	{
		foreach ($packs_xml as $pack)
			if(((string)$pack[0]->extension->info[0])==$repo_info["xml"])
				$repo[$repo_name]["zip"] = (string)$pack[0]->extension->datadir;
		if (!isset($repo[$repo_name]["zip"]))
			$repo[$repo_name]["zip"] = dirname($repo[$repo_name]["xml"]);
	}
	// Add end "/"
	$repo[$repo_name]["zip"] .= (substr($repo[$repo_name]["zip"], -1) == '/' ? '' : '/');

	foreach ($packs_xml as $pack)
	{
		$id = trim($pack[0]->attributes()->id);
		$version = trim($pack[0]->attributes()->version);
		if (isset($repo_info["dont"]) && in_array($id,$repo_info["dont"])) continue;
		if (stripos($version, "beta") !== false)	continue;
		if (stripos($version, "alpha") !== false)	continue;
		// New version of existing Addon
		if (!isset($getAddons[$id]) || (version_compare($version,$getAddons[$id]["version"])>0 && !isset($getAddons[$id]["org"])))
		{
			$getAddons[$id]["version"] = $version;
			$getAddons[$id]["zip"] = $repo[$repo_name]["zip"].$id."/".$id."-".$version.".zip";
			$getAddons[$id]["depend"] = GetAddonDepend($pack[0]);
		}
		// Org repo of addon
		if (isset($repo[$repo_name]["addon"][$id]) && $repo[$repo_name]["addon"][$id]==1)
		{
			$getAddons[$id]["version"] = $version;
			$getAddons[$id]["org"] = 1;
			$getAddons[$id]["zip"] = $repo[$repo_name]["zip"].$id."/".$id."-".$version.".zip";
			$getAddons[$id]["depend"] = GetAddonDepend($pack[0]);
		}
	}
}

echo "\nGet Self Addons: ";
foreach ($self_addon as $id => $addon)
{
	$info = GetAddonInfo($addon["xml"]);
	$version = $info["version"];
	echo $id."($version).";
	$getAddons[$id]["version"] = $version;
	$getAddons[$id]["depend"] = $info["depend"];
	if (isset($addon["release"]))			
		$getAddons[$id]["zip"] = str_replace("[VER]",$version,$addon["release"]);
	elseif (isset($addon["zip"]))	
		$getAddons[$id]["zip"] = $addon["zip"];
}
ksort($getAddons);

$my_addons = Array();
foreach ($SETTING["MY.ADDONS"] as $addon_file)
{
	if (!is_file($addon_file))
	{
		echo "\nMISS: ".$addon_file;
		continue;
	}
	$lines = file($addon_file, FILE_IGNORE_NEW_LINES | FILE_USE_INCLUDE_PATH | FILE_SKIP_EMPTY_LINES);
	foreach ($lines as $line_num => $line)
		if (strlen($line)>3)
			$my_addons[trim($line)] = 0;
}

echo "\n\nDownload New Addons: ";
foreach ($my_addons as $id => $verion)
	GetAddon($id);

echo "\n\nDisconnected Addons: ";
foreach ($my_addons as $id => $verion){
	if ($verion === 0 && !isset($my_packs[$id]))
		echo "\n\t $id";
}

CreateXML($my_repo);

echo "\n".date("d/m/y H:i")." Time Elapsed: ".number_format(((microtime(true) - $time_run)),2,".",",")." s\n\n";
?>