<?php

function CreateXML($my_repo)
{
	$dirs = array_filter(glob('*'), 'is_dir');
	$ads = "";
	
	$docList = new DomDocument('1.0');
	$docList->formatOutput = true;

    $root = $docList->createElement('addons');
    $docList->appendChild($root);

    foreach($dirs as $filename) 
	{
		if (!file_exists($filename.'/addon.xml'))
			continue;

        $doc = new DOMDocument();
        $doc->load($filename.'/addon.xml');
		$root2 = $doc->documentElement;
		
        $xmlString = $doc->saveXML($doc->documentElement);

        $xpath = new DOMXPath($doc);
        $query = "//addon";  // this is the name of the ROOT element

        $nodelist = $xpath->evaluate($query, $doc->documentElement);

        if( $nodelist->length > 0 ) {
            $node = $docList->importNode($nodelist->item(0), true);
            $root->appendChild($node);
        }

    }

	$docList->save($my_repo);
	file_put_contents($my_repo.".md5",md5_file($my_repo));
	file_put_contents($my_repo.".gz",gzencode(file_get_contents($my_repo),9));
	file_put_contents($my_repo.".gz.md5",md5_file($my_repo.".gz"));
	echo "\n Create: $my_repo";
}

function ModAnddonXML($addon_xml)
{
	$doc = new DOMDocument();
	$doc->loadXML($addon_xml);

	$xpath = new DOMXpath($doc);
	$result = $xpath->query('/addon/requires/import[@addon[contains(.,"repository")]]');
	if (!$result) return;
	
	foreach($result as $node)
		$node->parentNode->removeChild($node);

	return $doc->saveXML();
}

function DownloadAddon($addon_id,$addon)
{
	global $FOLDER,$SETTING;
	
	$addon_zip = $addon["zip"];
	$addon_version = $addon["version"];

	echo "\n......Getting Addon: $addon_id (".$addon["version"].")";
	$addon_files = array("addon.xml","changelog.txt","icon.png","fanart.jpg");

	// Download addon zip file
	copy ($addon_zip,$FOLDER."/".basename($addon_zip));

	// Create addon folder
	if (!file_exists($addon_id))
		mkdir ($addon_id,0755);

	// Extract files needed from zip
	$zip = new ZipArchive;
	$res = $zip->open($FOLDER."/".basename($addon_zip));
	if ($res === TRUE) 
	{
		$i=0; 
		while($item_name = $zip->getNameIndex($i)){
			if (basename($addon_zip)=="master.zip")
				$zip->renameIndex( $i, preg_replace( "/(^.*)-master/", $addon_id, $item_name ) );
			if (strpos($item_name, ".git") !== false)
				$zip->deleteIndex($i);
			// Copy needed files from zip
			if (in_array(basename($item_name),$addon_files))
				$zip->extractTo(".",$addon_id."/".basename($item_name));
			$i++;
		}
		
		// Modify addon XML, Remove external repo.
		if ($SETTING["RM.REPOS"])
		{
			$fileToModify = $addon_id."/addon.xml";
			$oldContents = $zip->getFromName($fileToModify);
			$newData = ModAnddonXML($oldContents);
			if (strlen($newData)>1)
			{
				$zip->deleteName($fileToModify);
				$zip->addFromString($fileToModify,$newData);
				file_put_contents($fileToModify,$newData);
			}
		}
		$zip->close();
	}
	
	// Copy zip file
	$local = $addon_id."/".$addon_id."-".$addon_version;
	if (file_exists($FOLDER."/".basename($addon_zip)))
		copy ($FOLDER."/".basename($addon_zip),$local.".zip");
	elseif (file_exists($FOLDER."/".basename($addon_zip)."-master"))
		copy ($FOLDER."/".basename($addon_zip)."-master",$local.".zip");
	else
	{
		echo "...\n Can't copy zip form ".$FOLDER."/".basename($addon_zip);
		return 0;
	}
	
	// Create md5 to zip file
	file_put_contents($local.".zip.md5",md5_file($local.".zip"));

	// Redirect to external server
	if (!(basename($addon_zip)=="master.zip") && $SETTING["EX.LINKS"])
	{
		// Create .htaccess
		if (file_exists($addon_id."/".'.htaccess'))
			unlink($addon_id."/".'.htaccess');
		$fp = fopen($addon_id."/".'.htaccess','a+');
		if($fp){
			fwrite($fp,'RewriteEngine On
	RewriteRule ^(.+\.zip)$ '.$addon_zip.' [R=302,NC,L]');
			fclose($fp);
		}

		// Rename zip file
		$file_parts = filePathParts($addon_id."/".basename($addon_zip));
		rename($addon_id."/".basename($addon_zip),$addon_id."/".$file_parts['filename']."_.".$file_parts['extension']);
	}
	return 1;	
}

function GetAddon($addon_id)
{
	global $kodi_packs,$getAddons,$my_packs,$SETTING;
	
	$skip_packs = Array("xbmc.metadata"=>100,"xbmc.addon"=>100,"kodi.resource"=>100,"xbmc.gui"=>100,"xbmc.json"=>100,"xbmc.python"=>100,"script.module.pil"=>100,"script.module.pysqlite"=>100,"script.module.sqlite"=>100);
	if (strpos($addon_id, "repository") !== false && $SETTING["RM.REPOS"]) return;
	if (isset($skip_packs[$addon_id])) return;

	if (isset($kodi_packs[$addon_id]) && $addon_id<>"script.module.urlresolver" && $addon_id<>"plugin.video.youtube")
		$addon = $kodi_packs[$addon_id];
	elseif (isset($getAddons[$addon_id]))
		$addon = $getAddons[$addon_id];
	else 
	{
		echo "\n*** ERROR Not found $addon_id";
		return;
	}

	if (isset($my_packs[$addon_id]) && version_compare($addon["version"],$my_packs[$addon_id])<=0) return;

	DownloadAddon($addon_id,$addon);
	$my_packs[$addon_id] = $addon["version"];
	if (count($addon["depend"])>0)
		foreach ($addon["depend"] as $k => $depend_id)
			GetAddon(trim($depend_id));
}
?>