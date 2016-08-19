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
		file_put_contents("./pendulo_pendientes.txt",$url."\n", FILE_APPEND);
		return false;
	}
	foreach ($dom->find('div[class=articulo_resultado_mt margenresultadosMinitiendas]') as $item) {
		$book = [];
		foreach ($item->find('a') as $data) {
			if(strpos($data->href, 'libreria')) {
				$book['isbn13'] = trim(html_entity_decode($isbn[2]));
				$book['title'] = trim(html_entity_decode($data->title));
				$isbn = explode('/', $data->href);
			}
			if(strpos($data->href, 'autor_id')) {
				$book['author'] = trim(html_entity_decode($data->title));
			}
			if(strpos($data->href, 'editorial_id')) {
				$book['publisher'] = trim(html_entity_decode($data->title));
			}
		}
		if (isset($book['isbn13'])) {
			file_put_contents("./pendulo.json", json_encode($book) . "\n", FILE_APPEND);
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
				for ($i=1; $i < 51 ; $i++) { 
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