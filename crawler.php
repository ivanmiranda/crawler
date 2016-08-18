<?php

use Desarrolla2\Cache\Cache;
use Desarrolla2\Cache\Adapter\File;

include("./simple_html_dom.php");

class Crawler extends \stdClass {

	public function getContent($url) {
		$adapter = new File('./cache');
		$adapter->setOption('ttl', 18000);
		$cache = new Cache($adapter);
		$urlHash = md5($url);
		$cachedPage = $cache->get($urlHash);
		if (!is_null($cachedPage)) {
			return unserialize($cachedPage);
		}

		$try = 1;
		$output = [];
		while($try < 6) {
			$ch = curl_init($url);
			$options = [
				CURLOPT_HEADER => false,
				CURLOPT_COOKIEJAR => 'cookie.txt',
				CURLOPT_COOKIEFILE	=> 'cookie.txt',
				CURLOPT_USERAGENT => 'Mozilla/5.0 (FCC New Media Web Crawler)',
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FILETIME => true,
				CURLOPT_TIMEOUT => 15
			];
			curl_setopt_array($ch, $options);
			$output['html'] = trim(curl_exec($ch));
			$output['md5'] = md5($output['html']);
			$output['http_code'] = curl_getinfo($ch,CURLINFO_HTTP_CODE);
			$output['reported_size'] = curl_getinfo($ch,CURLINFO_CONTENT_LENGTH_DOWNLOAD);
			$output['actual_size'] = curl_getinfo($ch,CURLINFO_SIZE_DOWNLOAD);
			$output['type'] = curl_getinfo($ch,CURLINFO_CONTENT_TYPE);
			$output['modified'] = curl_getinfo($ch,CURLINFO_FILETIME);	
			curl_close($ch);
			if (intval($output['http_code']) != 200) {
				$try++;
			} else {
				$try = 6;
			}
		}
		if (strlen(trim($output['html']))) {
			$cache->set($urlHash, serialize($output));
		}
		return $output;
	}

	public function getDom($url) {
		$content = $this->getContent($url);
		if (strlen(trim($content['html']))) {
			return str_get_html($content['html']);
		} else {
			return false;
		}
	}

	public function getTags($pattern, $dom) {
		return $dom->find($pattern);
	}

}
