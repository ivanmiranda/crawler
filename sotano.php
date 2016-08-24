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
		foreach($details->find('h2[class=subtitBigCentradoIZQ]') as $data) {
			$book['title'] = trim(html_entity_decode($data->innertext));
		}
		foreach($details->find('a[class=autor]') as $data) {
			$book['author'] = trim(html_entity_decode($data->innertext));
		}
		$intPosicion = 1;
		foreach($details->find('span[class=editorial]') as $data) {
			if ($intPosicion == 1) {
				$book['publisher'] = trim(html_entity_decode($data->innertext));
			} else {
				$book['isbn13'] = trim(html_entity_decode($data->innertext));
			}
			$intPosicion++;
		}
		foreach($details->find('span[class=azul]') as $data) {
			$book['price'] = trim(str_replace('$', '', html_entity_decode($data->innertext)));
		}
		if (isset($book['title'])) {
				file_put_contents("./sotano.json", json_encode($book) . "\n", FILE_APPEND);
		} else {
			file_put_contents("./sotano_pendientes.txt", $url . "\n", FILE_APPEND);
		}
	} else {
		file_put_contents("./sotano_pendientes.txt", $url . "\n", FILE_APPEND);
	}
}

function processPage($url) {
	$contentPage = getContent($url);
	if (strlen(trim($contentPage)) == 0) {
		$contentPage = trim(file_get_contents($url));
	}
	if(($dom = str_get_html($contentPage)) === false) {
		file_put_contents("./sotano_pendientes.txt",$url."\n", FILE_APPEND);
		return false;
	}
	if (strlen(trim($contentPage)) != 4821) {
		foreach ($dom->find('figure[class=effect-zoe]') as $item) {
			foreach ($item->find('a') as $urlBook) {
				if (!strpos($urlBook->attr['href'], '.php')) {
					var_dump($urlBook->attr['href']);
					processBook('https://www.elsotano.com/' . html_entity_decode($urlBook->attr['href']));
				}
			}
		}
	} else {
		file_put_contents("./sotano_pendientes.txt", $url."\n", FILE_APPEND);
	}
	flush();
}

$archivoLigas = "./sotano_ligas.txt";
$archivoPendientes = "./sotano_pendientes.txt";
while(file_exists($archivoLigas)) {
	$handle = fopen($archivoLigas, "r");
	if ($handle) {
		while (($lineUrl = fgets($handle)) !== false) {
			if (strlen(trim($lineUrl)) > 0) {
				if (strpos($lineUrl,'{{pagina}}')) {
					for ($i=1; $i < 166 ; $i++) { 
						$url = str_replace('{{pagina}}', $i, $lineUrl);
						processPage(html_entity_decode($url));
					}
				} else {
					if (strpos($lineUrl,'libros.php')) {
						processPage(html_entity_decode($lineUrl));
					} else {
						processBook(html_entity_decode($lineUrl));
					}
				}
			}
		}
	}
	fclose($handle);
	unlink($archivoLigas);
	rename($archivoPendientes, $archivoLigas);
}