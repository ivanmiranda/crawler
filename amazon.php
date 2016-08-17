<?php 
error_reporting(E_ALL & ~E_NOTICE);
set_time_limit(50000); 

include("simple_html_dom.php");

function getContent($url) {
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}

function process($url) {
	if($dom = str_get_html(getContent($url))) {
		if (PHP_SAPI == "cli") $lb = "\n"; 
		else $lb = "<br />"; 
		echo "--> {$url}".$lb; 
	    foreach ($dom->find('div[class=s-item-container]') as $item) {
	      	$autor = '';
	      	foreach ($item->find('a[class=a-link-normal s-access-detail-page  a-text-normal]') as $url) {
	      		$details = str_get_html(getContent($url->attr['href']));
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
	      		file_put_contents("./amazon.json", json_encode($book) . "\n", FILE_APPEND);
	      	}
	    }
	} else {
		file_put_contents("./amazon_pendientes.txt",$pagina->url."\n", FILE_APPEND);
	}
	flush();
}

$handle = fopen("./amazon_ligas.txt", "r");
$linea = 0;
if ($handle) {
	while (($line = fgets($handle)) !== false) {
		$linea++;
		echo "Linea:{$linea}".$lb; 
		file_put_contents("./amazon.tmp", $linea);
		for ($i=1; $i < 401 ; $i++) { 
			$url = str_replace('{{pagina}}', $i, $line);
			process($line);
		}
		if (PHP_SAPI == "cli") $lb = "\n"; 
		else $lb = "<br />"; 
	}
}
fclose($handle);