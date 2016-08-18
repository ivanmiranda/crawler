<?php

require_once __DIR__ . '/vendor/autoload.php';

include("./crawler.php");

$find = ['div[class=s-item-container]','a[class=a-link-normal s-access-detail-page  a-text-normal]'];

$crawl = new Crawler();
$pageContent = $crawl->getDom('https://www.amazon.com.mx/s/ref=sr_pg_2?fst=as%3Aoff&rh=n%3A9298576011%2Cp_n_feature_ten_browse-bin%3A9775230011%2Cn%3A%219298577011%2Cn%3A9535742011%2Cp_n_feature_browse-bin%3A9590861011%2Cn%3A9535826011&page=2&bbn=9535742011&ie=UTF8&qid=1471538975');
if ($pageContent) {
	$books = $crawl->getTags('a[class=a-link-normal s-access-detail-page  a-text-normal]', $pageContent);
	foreach ($books as $book) {
		$author = '';
		$bookContent = $crawl->getDom(html_entity_decode($book->attr['href']));
		if ($bookContent) {
			$bookData = [];
			foreach($bookContent->find('span[id=productTitle]') as $data) {
				$bookData['title'] = trim(html_entity_decode($data->innertext));
			}
			foreach($bookContent->find('span[class=author]') as $data) {
				foreach ($data->find('a') as $extra) {
					$author .= trim(str_replace(","," ",str_replace("<b>", "", html_entity_decode($extra->innertext))))." , ";
				}
			}
			$bookData['author'] = $author;
			foreach($bookContent->find('span[class=a-size-medium a-color-price offer-price a-text-normal]') as $data) {
				$bookData['price'] = trim(str_replace('$', '', html_entity_decode($data->innertext)));
			}
			foreach($bookContent->find('td[class=bucket]') as $data) {
				foreach ($data->find('li') as $extra) {
					if(strstr($extra->innertext, 'ISBN-10')){
						$bookData['isbn10'] = trim(str_replace('<b>ISBN-10:</b>', '', html_entity_decode($extra->innertext)));
					}
					if(strstr($extra->innertext, 'ISBN-13')){
						$bookData['isbn13'] = trim(str_replace('<b>ISBN-13:</b>', '', html_entity_decode($extra->innertext)));
					}
					if(strstr($extra->innertext, 'Editor')){
						$bookData['publisher'] = trim(str_replace('<b>Editor:</b>', '', html_entity_decode($extra->innertext)));
					}
				}
			}
			if (isset($bookData['title'])) {
				file_put_contents("./amazon.json", json_encode($bookData) . "\n", FILE_APPEND);
			} else {
				file_put_contents("./amazon_pendientes.txt", $url . "\n", FILE_APPEND);
			}
		}
	}
}