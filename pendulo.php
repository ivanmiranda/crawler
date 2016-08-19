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

function processBook($url) {
	$contentBook = getContent($url);
	if (strlen(trim($contentBook)) == 0) {
		$contentBook = trim(file_get_contents($url));
	}
	if ($details = str_get_html($contentBook)) {
		$book = [];
		foreach($details->find('span[id=productTitle]') as $data) {
			$book['title'] = trim(html_entity_decode($data->innertext));
		}
		foreach($details->find('span[class=author]') as $data) {
			foreach ($data->find('a') as $extra) {
				$autor .= trim(str_replace(","," ",str_replace("<b>", "", html_entity_decode($extra->innertext))))." , ";
			}
		}
		$book['author'] = $autor;
		foreach($details->find('span[class=a-size-medium a-color-price offer-price a-text-normal]') as $data) {
			$book['price'] = trim(str_replace('$', '', html_entity_decode($data->innertext)));
		}
		foreach($details->find('td[class=bucket]') as $data) {
			foreach ($data->find('li') as $extra) {
				if(strstr($extra->innertext, 'ISBN-10')){
					$book['isbn10'] = trim(str_replace('<b>ISBN-10:</b>', '', html_entity_decode($extra->innertext)));
				}
				if(strstr($extra->innertext, 'ISBN-13')){
					$book['isbn13'] = trim(str_replace('<b>ISBN-13:</b>', '', html_entity_decode($extra->innertext)));
				}
				if(strstr($extra->innertext, 'Editor')){
					$book['publisher'] = trim(str_replace('<b>Editor:</b>', '', html_entity_decode($extra->innertext)));
				}
			}
		}
		if (isset($book['title'])) {
				file_put_contents("./pendulo.json", json_encode($book) . "\n", FILE_APPEND);
		} else {
			file_put_contents("./pendulo_pendientes.txt", $url . "\n", FILE_APPEND);
		}
	} else {
		file_put_contents("./pendulo_pendientes.txt", $url . "\n", FILE_APPEND);
	}
}

function processPage($url) {
	$contentPage = getContent($url);
	if (strlen(trim($contentPage)) == 0) {
		$contentPage = trim(file_get_contents($url));
	}
	var_dump($contentPage);die();
	if(($dom = str_get_html($contentPage)) === false) {
		file_put_contents("./pendulo_pendientes.txt",$url."\n", FILE_APPEND);
		return false;
	}
	foreach ($dom->find('div[class=articulo_resultado]') as $item) {
		foreach ($item->find('h4') as $data) {
			var_dump($data->innerText);
		}
	}
	flush();
}

$archivoLigas = "./pendulo_ligas.txt";
$archivoPendientes = "./pendulo_pendientes.txt";
while(file_exists($archivoLigas)) {
	$handle = fopen($archivoLigas, "r");
	if ($handle) {
		while (($lineUrl = fgets($handle)) !== false) {
			if (strlen(trim($lineUrl)) > 0) {
				for ($i=1; $i < 2 ; $i++) { 
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