<?php
error_reporting(E_ERROR | E_PARSE);
set_time_limit(50000); 

require_once __DIR__ . '/vendor/autoload.php';

use Sincco\Tools\Curl;

function processPage($url) {
	$curl = new \Sincco\Tools\Curl;
	$curl->addOption(CURLOPT_CONNECTTIMEOUT, 100);
	$domPage = $curl->getDom($url);
	foreach ($domPage->find('div[class=s-item-container] a[class=a-link-normal s-access-detail-page  a-text-normal]') as $item) {
		$book = [];
		$domBook = $curl->getDom(html_entity_decode($item->attr['href']));
		$book['title'] = trim(html_entity_decode($domBook->find('span[id=productTitle]', 0)->innertext));
		$book['author'] = trim(html_entity_decode($domBook->find('span[class=author] a', 0)->innertext));
		$book['price'] = str_replace('$', '', trim(html_entity_decode($domBook->find('span[class=a-size-medium a-color-price offer-price a-text-normal]', 0)->innertext)));
		foreach ($domBook->find('td[class=bucket] li') as $data) {
			if(strstr($data->innertext, 'ISBN-10')){
				$book['isbn10'] = trim(str_replace('<b>ISBN-10:</b>', '', html_entity_decode($data->innertext)));
			}
			if(strstr($data->innertext, 'ISBN-13')){
				$book['isbn13'] = str_replace('-', '', trim(str_replace('<b>ISBN-13:</b>', '', html_entity_decode($data->innertext))));
			}
			if(strstr($data->innertext, 'Editor')){
				$book['publisher'] = trim(str_replace('<b>Editor:</b>', '', html_entity_decode($data->innertext)));
			}
		}
		if (trim($book['isbn13']) != '') {
			file_put_contents("./amazon.data", json_encode($book) . "\n", FILE_APPEND);
		}
	}
	flush();
}

$handle = fopen('./amazon.txt', 'r');
while (($lineUrl = fgets($handle)) !== false) {
	for ($i=2; $i < 401 ; $i++) { 
		$url = html_entity_decode(str_replace('{{pagina}}', $i, trim($lineUrl)));
		processPage($url);
	}
}
fclose($handle);
