<?php

function Get_HTML ($url,$curl=1)
{
	if (!file_exists("_mycache/"))
		mkdir ("_mycache/",0777);
	$cache_file = "_mycache/".sanitize_file_name($url).".cache";
	if (!file_exists("_mycache"))
		mkdir ("_mycache",0755 ,true); 
	if (file_exists($cache_file) && (filemtime($cache_file) > (time() - 60 * 10)) && filesize($cache_file)>1000)
		return file_get_contents($cache_file);
	else 
	{
		//usleep( rand ( 1000000, 5000000));
		$urlInfo = parse_url($url);
		if (!$curl)
			$html = file_get_contents($url);
		else
		{
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			$html = curl_exec($ch);
			curl_close($ch);
		}

		if ((file_exists($cache_file) && (strlen($html) > filesize($cache_file)*0.8)) or !file_exists($cache_file))
			file_put_contents($cache_file, $html);

		return $html;
	}
}
function sanitize_file_name($filename) 
{
	$special_chars = array("?", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}", chr(0));
	$filename = preg_replace( "#\x{00a0}#siu", ' ', $filename );
	$filename = str_replace( $special_chars, '', $filename );
	$filename = str_replace( array( '%20', '+' ), '-', $filename );
	$filename = preg_replace( '/[\r\n\t -]+/', '-', $filename );
	$filename = trim( $filename, '.-_' );
	return $filename;
}

function filePathParts($link) {
	$xmlFile = pathinfo($link);
	return $xmlFile;
}

// Delete full folder
function delTree($dir) { 
   $files = array_diff(scandir($dir), array('.','..')); 
    foreach ($files as $file) { 
      (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file"); 
    } 
    return rmdir($dir); 
} 


function ZipMe($file_zip,$rootPath,$split=0)
{
	// Initialize empty "delete list"
	$filesToIgnore = array(".","..");

	// Create recursive directory iterator
	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($rootPath),
		RecursiveIteratorIterator::LEAVES_ONLY
	);

	// Initialize archive object
	if (file_exists($file_zip)) unlink($file_zip);
	$zip = new ZipArchive;
	$zip->open($file_zip, ZipArchive::CREATE);
	$size = 0;
	$i=1; $file_zip_org=$file_zip;
	foreach ($files as $name => $file) {
		$filePath = $file->getRealPath();

        if( in_array(substr($file, strrpos($file, '/')+1), $filesToIgnore) ) continue;
		$zip->addFile($filePath,substr($file, strpos($file, '/',5)+1));
		$size += filesize($filePath);
		if (($size/1024/1024)>200 && (strpos($file_zip, 'base') !== false) && $split)
		{
			$zip->close();
			chmod($file_zip, 0755);
			$file_zip = trim($file_zip_org,".zip");
			$file_zip .= ".".$i++.".zip";
			// Initialize archive object
			if (file_exists($file_zip)) unlink($file_zip);
			$zip = new ZipArchive;
			$zip->open($file_zip, ZipArchive::CREATE);
			$size = 0;
		}			
	}
	$zip->close();
	chmod($file_zip, 0755);
}

function recurse_copy($src,$dst) 
{ 
	$dir = opendir($src); 
	@mkdir($dst); 
	while(false !== ( $file = readdir($dir)) ) { 
		if (( $file != '.' ) && ( $file != '..' )) { 
			if ( is_dir($src . '/' . $file) ) { 
				recurse_copy($src . '/' . $file,$dst . '/' . $file); 
			} 
			else 
			{
				copy($src.'/'.$file,$dst.'/'.$file);
			}
		} 
	} 
	closedir($dir); 
} 

function GetAddonXML($link)
{
	$xml_fix = Get_HTML($link);
	
	// Cannot get XML
	if($xml_fix === FALSE)	return 0;

	// Remove bad string
	$pattern = '/<description lang="en">(.*)<\/description>/siU';
	$xml_fix = trim(preg_replace($pattern, "", $xml_fix));

	// HTML -> Simple XML
	if ((strpos($xml_fix, "<addons>") === false) && (strpos($xml_fix, "</addon>") === false))
		return 0;
	return $xml_fix;
}

function GetAddonInfo($xml_url,$local=0)
{
	$result = Array();
	if ($local)
		if (file_exists($xml_url))
			$xml = file_get_contents($xml_url);
		else
		{
			echo "\n NO XML $xml_url";
			return;
		}
	else
		$xml = GetAddonXML($xml_url);
	if(!$xml)
		return;
	$xml = simplexml_load_string($xml);
	$addon = $xml->xpath("//addon");
	$result["version"] = trim($addon[0]->attributes()->version);
	$result["depend"] = GetAddonDepend($addon[0]);
	return $result;
}

function GetAddonDepend($pack)
{
	if (!isset($pack->requires->import)) return;
	$result = Array();
	foreach ($pack->requires->import as $import)
			$result[]=trim($import->attributes()->addon);
	return $result;
}

?>