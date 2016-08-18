<?php

require_once __DIR__ . '/vendor/autoload.php';

include("./crawler.php");

class Amazon extends Crawler {
	public function procesaPagina($url) {
		$pageContent = $this->getDom($url);
		if ($pageContent) {
			$books = $this->getTags('a[class=a-link-normal s-access-detail-page  a-text-normal]', $pageContent);
			foreach ($books as $book) {
				$this->procesaLibro(html_entity_decode($book->attr['href']));
			}
		}
	}

	public function procesaLibro($url) {
		$author = '';
		$bookContent = $this->getDom($url);
		if ($bookContent) {
			$bookData = [];
			foreach ($bookContent->find('span[id=productTitle]') as $data) {
				$bookData['title'] = trim(html_entity_decode($data->innertext));
			}
			foreach ($bookContent->find('span[class=author]') as $data) {
				foreach ($data->find('a') as $extra) {
					$author .= trim(str_replace(","," ",str_replace("<b>", "", html_entity_decode($extra->innertext))))." , ";
				}
			}
			$bookData['author'] = $author;
			foreach ($bookContent->find('span[class=a-size-medium a-color-price offer-price a-text-normal]') as $data) {
				$bookData['price'] = trim(str_replace('$', '', html_entity_decode($data->innertext)));
			}
			foreach ($bookContent->find('td[class=bucket]') as $data) {
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

$crawl = new Amazon();

$archivoLigas = "./amazon_ligas.txt";
$archivoPendientes = "./amazon_pendientes.txt";
while(file_exists($archivoLigas)) {
	$handle = fopen($archivoLigas, "r");
	while (($lineUrl = fgets($handle)) !== false) {
		if (strlen(trim($lineUrl)) > 0) {
			if (strpos($lineUrl,'{{pagina}}')) {
				for ($i=1; $i < 401 ; $i++) {
					$url = str_replace('{{pagina}}', $i, $lineUrl);
					$crawl->procesaPagina(html_entity_decode($url));
					flush();
				}
			} else {
				if (strpos($lineUrl,'ref=sr_pg')) {
					$crawl->procesaPagina(html_entity_decode($lineUrl));
				} else {
					$crawl->procesaLibro(html_entity_decode($lineUrl));
				}
				flush();
			}
		}
		flush();
	}
	fclose($handle);
	unlink($archivoLigas);
	if (file_exists($archivoPendientes)) {
		rename($archivoPendientes, $archivoLigas);
	}
}