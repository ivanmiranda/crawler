<?php 
error_reporting(E_ERROR | E_PARSE);
set_time_limit(50000); 

include("./simple_html_dom.php");

function getContent($url) {
	$ch = curl_init();
	$timeout = 100;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}


function processPage($url) {
	$contentPage = getContent($url);
	if (strlen(trim($contentPage)) == 0) {
		$contentPage = trim(file_get_contents($url));
	}
	if(($dom = str_get_html($contentPage)) === false) {
		file_put_contents("./sanborns_pendientes.txt",$url."\n", FILE_APPEND);
		return false;
	}
	foreach ($dom->find('div[class=content_block]') as $item) {
		$book = [];
		foreach ($item->find('a[class=add]') as $data) {
			$book['isbn13'] = $data->attr['rel'];
		}
		foreach ($item->find('div[class=desc]b') as $data) {
			$book['title'] = $data->innertext;
		}
		foreach ($item->find('span[style=font-weight:bold;color:#F00]') as $data) {
			$book['price'] = str_replace(',', '', str_replace('$', '', $data->innertext));
		}
		if (isset($book['isbn13'])) {
		 	file_put_contents("./sanborns.json", json_encode($book) . "\n", FILE_APPEND);
		 }
	}
	flush();
}

$archivoLigas = "./sanborns_ligas.txt";
$archivoPendientes = "./sanborns_pendientes.txt";
while(file_exists($archivoLigas)) {
	$handle = fopen($archivoLigas, "r");
	if ($handle) {
		while (($lineUrl = fgets($handle)) !== false) {
			if (strlen(trim($lineUrl)) > 0) {
				for ($i=1; $i < 20 ; $i++) { 
					$url = str_replace('{{pagina}}', $i, $lineUrl);
					processPage(html_entity_decode($url));
				}
			}
		}
	}
	fclose($handle);
	unlink($archivoLigas);
	rename($archivoPendientes, $archivoLigas);
}