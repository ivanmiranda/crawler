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
		foreach($details->find('div[class=container]') as $container) {
			foreach($container->find('div[class=comprar]') as $row) {
				$data = $row->find('a', 0);
				$book['isbn13'] = trim(html_entity_decode($data->getAttribute('data-generico')));
				if (trim($book['isbn13']) != '') {
					$book['isbn13'] = trim(html_entity_decode($data->getAttribute('data-isbnebook')));
				}
				$book['title'] = trim(html_entity_decode($data->getAttribute('data-title')));
				$book['author'] = trim(html_entity_decode($data->getAttribute('data-autor')));
				$book['publisher'] = trim(html_entity_decode($data->getAttribute('data-editorial')));
				$book['price'] = trim(html_entity_decode($data->getAttribute('data-price')));
			}
		}
		file_put_contents("./porrua.json", json_encode($book) . "\n", FILE_APPEND);
	}
}

function processPage($url) {
	$contentPage = getContent($url);
	if (strlen(trim($contentPage)) == 0) {
		$contentPage = trim(file_get_contents($url));
	}
	if ($details = str_get_html($contentPage)) {
		$book = [];
		foreach($details->find('div[id=busqueda_item]') as $data) {
			foreach ($data->find('a') as $urlBook) {
				processBook($urlBook->href);
			}
		}
	}
}


$handle = fopen('./pendulo.json', 'r');
while (($data = fgets($handle)) !== false) {
	$book = json_decode($data);
	$url = 'https://www.porrua.mx/busqueda/todos/' . utf8_decode($book->title);
	processPage($url);
}
fclose($handle);

$handle = fopen('./sotano.json', 'r');
while (($data = fgets($handle)) !== false) {
	$book = json_decode($data);
	$url = 'https://www.porrua.mx/busqueda/todos/' . utf8_decode($book->title);
	processPage($url);
}
fclose($handle);

$handle = fopen('./amazon.json', 'r');
while (($data = fgets($handle)) !== false) {
	$book = json_decode($data);
	$url = 'https://www.porrua.mx/busqueda/todos/' . utf8_decode($book->title);
	processPage($url);
}
fclose($handle);

echo 'HECHO';
$mysql->close();