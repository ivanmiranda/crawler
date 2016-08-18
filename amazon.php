<?php 
error_reporting(E_ALL & ~E_NOTICE);
set_time_limit(50000); 

include("./simple_html_dom.php");

function getContent($url) {
	$ch = curl_init();
	$timeout = 50;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}

function processBook($url) {
	$contentBook = getContent($url);
	echo 'BOOK::' . urldecode(html_entity_decode($url)) . '::' . strlen(trim($contentBook)) . "\n";
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
				file_put_contents("./amazon.json", json_encode($book) . "\n", FILE_APPEND);
		} else {
			file_put_contents("./amazon_pendientes.txt", $url . "\n", FILE_APPEND);
		}
	} else {
		file_put_contents("./amazon_pendientes.txt", $url . "\n", FILE_APPEND);
	}
}

function processPage($url) {
	$contentPage = getContent($url);
	if(($dom = str_get_html($contentPage)) === false) {
		file_put_contents("./amazon_pendientes.txt",$url."\n", FILE_APPEND);
		return false;
	}
	echo 'PAGE::' . $url;
	if (strlen(trim($contentPage)) != 4821) {
		foreach ($dom->find('div[class=s-item-container]') as $item) {
			$autor = '';
			foreach ($item->find('a[class=a-link-normal s-access-detail-page  a-text-normal]') as $urlBook) {
				processBook($urlBook->attr['href']);
			}
		}
	} else {
		file_put_contents("./amazon_pendientes.txt",$url."\n", FILE_APPEND);
	}
	flush();
}

$handle = fopen("./amazon_ligas.txt", "r");
$linea = 0;
if ($handle) {
	while (($lineUrl = fgets($handle)) !== false) {
		$linea++;
		if(strpos($lineUrl,'{{pagina}}')) {
			for ($i=1; $i < 401 ; $i++) { 
				$url = str_replace('{{pagina}}', $i, $lineUrl);
				processPage($url);
			}
		} else {
			if(strpos($lineUrl,'ref=sr_pg')) {
				processPage($lineUrl);
			} else {
				processBook($lineUrl);
			}
		}
	}
}
fclose($handle);