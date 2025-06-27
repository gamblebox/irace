<?php
error_reporting(E_ALL & ~ E_NOTICE & ~ E_WARNING);

$url = 'http://www.powerballgame.co.kr';
$dom = new DomDocument();

$dom->loadHtmlFile($url);
// echo $dom->saveHTML();
$xpath = new DomXPath($dom);
//html/body/table[2]
$table = $xpath->query('//table');
print_r($table) ;
echo $table->nodeValue;