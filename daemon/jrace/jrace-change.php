<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require_once('/srv/irace/daemon/common/common.php');
require_once('/srv/irace/daemon/common/pdo-tool.php');
require_once('/srv/irace/daemon/common/config-db.php');

function get_race_data_to_json($url)
{
  global $data;

  //     $url = 'https://www2.keiba.go.jp/KeibaWeb/TodayRaceInfo/RaceList?k_raceDate=2018%2f04%2f02&k_babaCode=21';
  echo $url . PHP_EOL;
  $code = [
    '騎手変更' => '기수변경',
    '出走取消' => '출전취소',
    '競走除外' => '출전제외'
  ];
  // $url='https://www2.keiba.go.jp/KeibaWeb/TodayRaceInfo/RaceList?k_raceDate=2018%2f04%2f02&k_babaCode=21';

  $place_own_id = explode('babaCode=', $url);
  // $place_own_id = explode( '&k_raceDate', $place_own_id[1]);
  $place_own_id = $place_own_id[1];

  // $date = substr($url, -10, 10 );
  // $date = str_replace('/', '-', $date);

  $date = explode('k_raceDate=', $url)[1];
  $date = explode('&k_babaCode', $date)[0];
  $date = str_replace('%2f', '-', $date);
  $date = preg_replace("/([\d]{4})([\d]{2})/", "$1-$2-$3", $date);
  $dom = new DomDocument();
  // $dom->loadHtmlFile($url);
  // 실행
  $dom->loadHtmlFile($url);
  $html = strtolower(substr($dom->saveHTML(), -6, 4));
  // echo $html . PHP_EOL;
  if ($html !== 'html') {
    return false;
  }
  echo 'ok' . PHP_EOL;
  // do {
  // $dom->loadHtmlFile( $url );
  // } while ( trim(substr( $dom->saveHTML(), -8 )) != '</html>' );
  $xpath = new DomXPath($dom);
  $headerNames = [
    'own_id',
    'start_date',
    'race_no',
    'entry_no',
    'type',
    'memo',
    'old_start_time',
    'new_start_time'
  ];
  // *[@id="mainContainer"]/article[1]/div/section/table[1]/tbody/tr[@class="data"]
  $trs = $xpath->query('//*[@id="mainContainer"]/article[1]/div/section/table[1]/tbody/tr[@class="data"]');

  foreach ($trs as $index => $tr) {
    // print_r($tr);
    $span = $xpath->query('td[2]/span[@class="timechange"]', $tr);
    //         print_r($span->length);
    if ($span->length > 0) {
      echo '변경있음' . PHP_EOL;
      $race_no = $xpath->query('td[1]', $tr)[0]->textContent;
      $new_start_time = $span[0]->textContent;
      $rowData = array();
      $rowData[] = $place_own_id;
      $rowData[] = $date;
      $rowData[] = $race_no;
      $rowData[] = 0;
      $rowData[] = '출발시각변경';
      $rowData[] = '출발시각변경' . ' => ' . $new_start_time;
      $rowData[] = date("Y-m-d ");
      $rowData[] = date("Y-m-d ") . $new_start_time;
      $data[] = array_combine($headerNames, $rowData);
    } else {
      echo '변경없음' . PHP_EOL;
    }
    // foreach ($tds as $index=>$td) {
    // echo 'childNodes';
    // print_r($td->childNodes);
    // print_r($td->childNodes[0]);
    // }

    // $rData = array();
  }
  // print_r($data);

  // *[@id="mainContainer"]/article[1]/div/section/table[@class="changeInfo"]/tbody/tr[@class="data"]
  //*[@id="mainContainer"]/article[1]/div/section/table[2]
  $tbody = $xpath->query('//*[@id="mainContainer"]/article[1]/div/section/table[@class="changeInfo"]/tbody');
  //     print_r($tbody);
  foreach ($xpath->query('tr[@class="data"]', $tbody[0]) as $index => $node) {
    //         print_r($node);
    $rowData = array();
    $rowData[] = $place_own_id;
    $rowData[] = $date;

    foreach ($xpath->query('td', $node) as $cell) {
      $rowData[] = trim($cell->nodeValue);
    }

    unset($rowData[4]);
    unset($rowData[6]);
    $rowData[] = date("Y-m-d");
    $rowData[] = date("Y-m-d");

    $rowData = array_values($rowData);
    $rowData[4] = $code[$rowData[4]];
    $rowData[5] = $rowData[3] . '번마 ' . $rowData[4];

    $data[] = array_combine($headerNames, $rowData);
  }
}

$database = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';port=' . $port . ';charset=utf8mb4', $user, $password, array(
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_EMULATE_PREPARES => false,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
));

$date_url = 'https://www2.keiba.go.jp/KeibaWeb/TodayRaceInfo/TodayRaceInfoTop';

$dom = new DomDocument();
$dom->loadHtmlFile($date_url);
$xpath = new DomXPath($dom);

// $alinks = $xpath->query('//*[@id="container00"]/table//tr[2]/td/table//tr/td/table//tr/td/table[3]//tr/td/table//tr/td[3]/table//tr/td/table//tr/td/a/@href');
$alinks = $xpath->query('//*[@id="mainContainer"]/article[@class="courseInfo"]/div/table/tbody/tr[2]/td[2]/a/@href');

print_r($alinks);
/*
 * foreach ($alinks as $index=>$alink) {
 *
 * if ($alink->value === '' ){
 * unset($alinks[$index]);
 * }
 * }
 */

$data = array();

foreach ($alinks as $alink) {
  $url = 'https://www2.keiba.go.jp' . $alink->value;
  get_race_data_to_json($url);
}
print_r($data);
// echo json_encode($data, JSON_UNESCAPED_UNICODE) . PHP_EOL;

// // 테스트
// $data = [];
// $data[] = [
//   'own_id' => 10,
//   'type' => '출전제외',
//   'start_date' => '2024-10-14',
//   'race_no' => 4,
//   'entry_no' => 2,
//   'memo' => '2번마 출전제외',
//   'old_start_time' => '2024-10-14',
//   'new_start_time' => '2024-10-14',
// ];


// exit();

// sql 변경 정보 삽입

$msg = array();
$headerNames = [
  'own_id',
  'start_date',
  'race_no',
  'reg_result'
];
foreach ($data as $index => $raceChange) {

  $own_id = $raceChange['own_id'];
  $start_date = $raceChange['start_date'];
  $race_no = str_replace('R', '', $raceChange['race_no']);
  $sql = "SELECT `race`.id as id, `race`.association_code, `race`.start_time, `place`.name as place_name FROM `race` left outer join `place` on `race`.place_id = `place`.id WHERE start_time >= date('" . $start_date . "') and start_time < date(date_add('" . $start_date . "', interval 1 day)) and (SELECT id from place where own_id = '" . $own_id . "') = place_id and race_no = '" . $race_no . "'";
  // print_r($sql);

  $race = select_query_one($database, $sql);
  $race_id = $race->id;
  $raceChange['race_id'] = $race_id;
  $raceChange['association_code'] = $race->association_code;
  $place_name = $race->place_name;
  // print_r($race);

  // INSERT INTO `goldrace`.`race_change_info` (`race_id`, `type`, `memo`, `old_start_time`, `new_start_time`) VALUES (7533, '출주취소', '어쩌구', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
  $entry_no = $raceChange['entry_no'];
  $type = $raceChange['type'];
  $memo = $place_name . " " . $race_no . "경주: " . $raceChange['memo'];
  $old_start_time = $raceChange['old_start_time'];
  $new_start_time = $raceChange['new_start_time'];
  $sql = "INSERT INTO `race_change_info` (`association_code`, `race_id`, `entry_no`, `type`, `memo`, `old_start_time`, `new_start_time`) SELECT 'jrace', " . $race_id . "," . $entry_no . ", '" . $type . "' , '" . $memo . "','" . $old_start_time . "','" . $new_start_time . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race_change_info` WHERE `race_id`= " . $race_id . " and  `entry_no` = " . $entry_no . " and  `type` = '" . $type . "' and  `memo` = '" . $memo . "' and  `old_start_time` = '" . $old_start_time . "' and `new_start_time` = '" . $new_start_time . "')";
  // print_r($sql);

  $result = exec_query_lastId($database, $sql);
  // echo $index . '->' . $lastId . PHP_EOL;
  print_r($result);

  if ($result->id > 0) {
    updateBettingByRaceChange($database, $raceChange);
  }
}
