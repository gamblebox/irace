<?php
require "/srv/krace/vendor/autoload.php";

use Browser\Casper;
use voku\helper\HtmlDomParser;

function insert_sql($sql)
{
    include __DIR__ . '/../../../application/configs/configdb.php';
    $mysqli = new mysqli($host, $user, $password, $dbname); // 연결 오류 발생 시 스크립트 종료

    if ($mysqli->connect_errno) {
        die('Connect Error: ' . $mysqli->connect_error);
    }
    if ($mysqli->query($sql) === true) {
        return 'ok';
    } else {
        return $mysqli->error;
    }
    $result->free(); // 메모리해제
}


$casper = new Casper();
$casper->setOptions(array(
    'ignore-ssl-errors' => 'yes',
    'loadImages' => 'false',
    // 'disk-cache' => 'true',
    // 'disk-cache-path' => '/tmp/phantomjs_cache_os_baedang_' . str_replace(' ', '_', $interval)
));
$casper->setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36');

// 채널 ID ※참고 : hr1 => 복승 / hr2 => 쌍승 / hr3 => 복연승 / hr4 => 단승 / hr5 => 연승 (테이블 형식에 맞는 채널 ID를 입력해 주세요)
$targets = array(
    'bok' => 'http://zpdlfpdltm.com/php/ch1-1.php',
    'ssang' => 'http://zpdlfpdltm.com/php/ch1-2.php',
    'bokyun' => 'http://zpdlfpdltm.com/php/ch1-3.php',
    // 'dan' => 'http://zpdlfpdltm.com/php/ch1-4.php',
    // 'yun' => 'http://zpdlfpdltm.com/php/ch1-5.php',

);

while (true) {
    foreach ($targets as $key => $url) {
        echo date("Y-m-d H:i:s ") . 'casper start' . PHP_EOL;
        $casper->start($url);
        $casper->wait(5000);
        $casper->run();
        echo date("Y-m-d H:i:s ") . 'casper ran' . PHP_EOL;
    
        $html = $casper->getHtml();
        $dom = HtmlDomParser::str_get_html($html);
        $odds_table = $dom->findOne('.divTable.tb_Table');
        echo $odds_table . PHP_EOL;
        // exit();
        if (!$odds_table) {
            continue;
        }
        $race_id_type = 0 . '_' . $key;
        $sql = "REPLACE INTO `kra_odds` (`race_id_type`,`race_id`, `type`, `data`)  VALUES ('" . $race_id_type . "','" . 0 . "','" . $type . "','" . $odds_table . "')";
        // echo $sql . PHP_EOL;
        $ok = insert_sql($sql);
        echo $ok . PHP_EOL;
    }
}

