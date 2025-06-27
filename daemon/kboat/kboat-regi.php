<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require "/srv/irace/vendor/autoload.php";
require_once('/srv/irace/daemon/common/pdo-tool.php');
require_once('/srv/irace/daemon/common/config-db.php');

use voku\helper\HtmlDomParser;

function get_race_data_to_json($url)
{
  global $data;

  echo $url;
  // exit();
  $html = curl($url);

  $dom = HtmlDomParser::str_get_html($html);
  //print_r($xpath);
  // collect header names
  $headerNames = ['own_id', 'start_time', 'race_no', 'length', 'entry_count']; //,'race_class','title','result','result_table'];

  $year = trim($dom->findOne('#stndYear')->textContent);
  $date = trim($dom->findOne('#tmsDayOrd')->textContent);
  // print_r($year);
  // print_r($date);

  $date = mb_substr($year, 0, 4) . '-' . mb_substr($date, 9, 2) . '-' . mb_substr($date, 13, 2);
  //$date = substr($date,0,4) . '-' . substr($date,8,2) . '-' . substr($date,14,2);
  print_r($date);
  // exit();

  $games = $dom->find('.modStickyContainer .sectArea');
  // print_r($games);
  // exit();

  foreach ($games as $key => $game) {
    $race_info = trim($game->findOne('.comTitH2 h2')->textContent);
    $etcAdds = trim($game->findOne('p.etcAdds')->textContent);

    $trs = $game->find('.comDataTable')[0]->find('.excel_table tbody tr');

    // print_r($race_info);
    //창원 제01경주
    // $place = mb_substr($race_info, 0, 2);
    $race_no = mb_substr($race_info, 2, 2);
    $start_time = $date . ' ' . mb_substr($race_info, -6, 5);
    $length = mb_substr($etcAdds, -6, 4);
    //*[@id="printPop"]/div[2]/table/tbody/tr[1]
    $entry_count = count((array)$trs);
    // print_r($place);
    // print_r($race_no);
    // echo $entry_count;
    // // print_r($start_time);
    $data[] = array_combine($headerNames, array(211, $start_time, $race_no, $length, $entry_count));
    // print_r($data);
    // exit();
  }

  print_r($data);
  // echo json_encode($data);
  // exit();
}

$database = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';port=' . $port . ';charset=utf8mb4', $user, $password, array(
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_EMULATE_PREPARES => false,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
));

$alinks = ['https://www.kboat.or.kr/race/card/decision'];
//print_r($alinks[0]->value);


$data = array();
foreach ($alinks as $alink) {
  get_race_data_to_json($alink);
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);

foreach ($data as $i => $r) {
  $sql = "INSERT INTO `race` (`place_id`, `association_code`, `start_date`, `start_time`, `race_no`, `race_length`, `entry_count`) SELECT (select `id` from `place` where `own_id`=" . $r['own_id'] .  "), 'kboat' , date('" . $r['start_time'] .  "'),'"  . $r['start_time'] .  "',"  . $r['race_no'] .  "," . $r['length'] .  "," . $r['entry_count'] .  " FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race` WHERE `place_id`= (select `id` from `place` where `own_id`=" . $r['own_id'] .  ") and  `race_no` = " . $r['race_no']   .  " and  date(`start_time`) = date('" . $r['start_time'] .  "') )";
  $ok = exec_query($database, $sql);
  echo $ok . PHP_EOL;
}
