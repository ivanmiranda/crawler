<?php 
error_reporting(E_ALL & ~E_NOTICE);
set_time_limit(50000); 

include("libs/PHPCrawler.class.php");
include("simple_html_dom.php");

class MyCrawler extends PHPCrawler  
{ 
  public function handleDocumentInfo($pagina)  
  {
    var_dump($pagina);die();
    if($dom = str_get_html($pagina->content)) {
      $datos = array();
      $autor = "";
      foreach($dom->find('span[id=productTitle]') as $data)
        array_push($datos,str_replace(","," ",$data->innertext));
      foreach($dom->find('span[id=btAsinTitle]') as $data)
        foreach ($data->find('span') as $extra)
          array_push($datos,str_replace(","," ",$extra->innertext));
      foreach($dom->find('span[class=author]') as $data)
        foreach ($data->find('a') as $extra)
          $autor .= str_replace(","," ",str_replace("<b>", "", $extra->innertext))." , ";
      array_push($datos, $autor);
      foreach($dom->find('span[class=offer-price]') as $data)
        array_push($datos,str_replace(","," ",$data->innertext));
      foreach($dom->find('span[class=color-price]') as $data)
        array_push($datos,str_replace(","," ",$data->innertext));
      foreach($dom->find('b[class=priceLarge]') as $data)
        array_push($datos,str_replace(","," ",$data->innertext));
      foreach($dom->find('td[class=bucket]') as $data) {
        foreach ($data->find('li') as $extra) {
          if(strstr($extra->innertext, "ASIN")){
            array_push($datos, str_replace(array("<b>","</b>"), "", $extra->innertext));
            break;
          }
          array_push($datos, str_replace(array("<b>","</b>"), "", $extra->innertext));
        }
      }
      foreach($dom->find('span[class=a-color-price]') as $data)
        array_push($datos,strip_tags(str_replace(","," ",$data->innertext)));
      foreach($dom->find('span[class=priceLarge]') as $data)
        array_push($datos,strip_tags(str_replace(","," ",$data->innertext)));
      file_put_contents("./amazon.csv",implode("|", $datos)."\n", FILE_APPEND);
    }
    else
      file_put_contents("./amazon_pendientes.txt",$pagina->url."\n", FILE_APPEND);
    flush();
  }  
}

$handle = fopen("./amazon_ligas.txt", "r");
$linea = 0;
if ($handle) {
  while (($line = fgets($handle)) !== false) {
    var_dump(file_get_contents($line));
    $linea++;
    $crawler = new MyCrawler(); 

    $crawler->setURL(trim($line));
    $crawler->addContentTypeReceiveRule("#text/html#"); 
    $crawler->addURLFilterRule("#\.(jpg|jpeg|gif|png)$# i");
    $crawler->addURLFollowRule("#s=books#");

    $crawler->setConnectionTimeout(60);
    $crawler->setStreamTimeout(80);

    $crawler->enableCookieHandling(false);
    $crawler->setCrawlingDepthLimit(2);

    $crawler->setWorkingDirectory("/dev/shm/"); 
    $crawler->setUrlCacheType(PHPCrawlerUrlCacheTypes::URLCACHE_SQLITE);
    $crawler->setTrafficLimit(500000 * 1024);
    //$crawler->enableResumption(); 

    // $ID = $crawler->getCrawlerId();
    // if (!file_exists("/tmp/amazon.tmp")) 
    // { 
    //   echo "PROCESO NUEVO<<<\n";
    //   $crawler_ID = $crawler->getCrawlerId(); 
    //   file_put_contents("/tmp/amazon.tmp", $crawler_ID); 
    // } 
    // else 
    // { 
    //   echo "<<<CONTINUANDO PROCESO\n";
    //   $crawler_ID = file_get_contents("/tmp/amazon.tmp"); 
    //   $crawler->resume($crawler_ID); 
    // } 

    //$crawler->goMultiProcessed(10); 

    // file_put_contents("./amazon.tmp", $linea); 

    $crawler->go(); 
    $report = $crawler->getProcessReport(); 

    if (PHP_SAPI == "cli") $lb = "\n"; 
    else $lb = "<br />"; 
         
    echo "Linea:{$linea}".$lb; 
    echo "Links followed: ".$report->links_followed.$lb; 
    echo "Documents received: ".$report->files_received.$lb; 
    echo "Bytes received: ".$report->bytes_received." bytes".$lb; 
    echo "Process runtime: ".$report->process_runtime." sec".$lb;  
  }
}
fclose($handle);