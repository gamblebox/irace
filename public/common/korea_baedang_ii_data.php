<?php
require_once('/srv/irace/daemon/common/domains.php');
// http://odds.apidba.com/?combine=2 복승 

// http://odds.apidba.com/?combine=3 쌍승

// http://odds.apidba.com/?combine=5 복연승
extract($_GET);
// $type = 2;

// $url = 'http://zpdlfpdltm.com/php/korea_baedang_i_data.php?type=' . $type;
$url = 'http://odds.apidba.com/?combine=' . $type;
// $url = 'http://odds.api-ba.com/?combine=' . $type;

$content = file_get_contents($url);
$odds = json_decode($content);
// print_r($odds);

$data = array();
foreach ($odds as $first => $odd) {
  foreach ($odd as $second => $value) {
    $data[$first . '-' . $second] = $value;
  }
}
// print_r($data);
// exit();

// echo json_encode($data);
echo $_GET['callback'] . '(' . json_encode($data) . ')';
