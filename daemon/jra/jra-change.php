<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require "/srv/irace/vendor/autoload.php";
require_once('/srv/irace/daemon/common/common.php');
require_once('/srv/irace/daemon/common/pdo-tool.php');
require_once('/srv/irace/daemon/common/config-db.php');

use voku\helper\HtmlDomParser;

function file_get_contents_post($url, $arr_data = array())
{
  $postdata = http_build_query(
    $arr_data
  );

  $opts = array(
    'http' =>
    array(
      'method'  => 'POST',
      'header'  => 'Content-Type: application/x-www-form-urlencoded',
      'content' => $postdata
    )
  );

  $context  = stream_context_create($opts);

  return file_get_contents($url, false, $context);
}

function get_race_data_to_json($sub_url, $cname)
{
  global $data;
  $base_url = 'https://www.jra.go.jp';
  $url = $base_url . $sub_url;
  // echo '$url=>' . $url . PHP_EOL;
  $html = file_get_contents_post($url, array('cname' => $cname));
  // echo '$html=>' . $html . PHP_EOL;
  $dom = HtmlDomParser::str_get_html($html);

  // collect header names
  $headerNames = [
    'own_id',
    'rk_race_code',
    'race_no',
    'start_time',
    'length',
    'entry_count'
  ];
  //html/body/main/div/div/div/div[2]/div[3]/table/tbody[1]/tr
  $trs = $dom->find('#race_list tbody > tr');
  // print_r($trs);
  // exit();

  if (count($trs) < 1) {
    echo 'No Data !!' . PHP_EOL;
    return;
  }
  // exit();
  foreach ($trs as $index => $tr) {
    $rowData = array();
    $rowData[] = substr($cname, 8, 3) + 130000;


    $tds = $tr->find('th,td');
    // $onclick = $tds[0]->find('a', 1);
    // print_r($tds[0]->find('a'));
    // exit();

    $onclick = explode('\'', $tds[0]->findOne('a')->attr['onclick']);
    $rowData[] = $onclick[3];
    $race_no = substr($onclick[3], 19, 2);
    $rowData[] = $race_no;
    $rowData[] = date('Y-m-d', strtotime(substr($onclick[3], 21, 8))) . ' ' . str_replace(array('時', '分'), ':', $tds[1]->textContent) . '00';
    $entry_count = str_replace('頭', '', $tds[6]->textContent);
    $rowData[] = str_replace(array(',', 'メートル'), '', $tds[4]->textContent);
    $rowData[] = $entry_count;

    $data[] = array_combine($headerNames, $rowData);
  }
  // print_r($data);
  // exit();
  // echo json_encode($data, JSON_UNESCAPED_UNICODE);
}

$code = [
  '騎手変更' => '기수변경',
  '出走取消' => '출전취소',
  '競走除外' => '출전제외',
  '発走時刻変更' => '출발시각변경'
];
$headerNames = [
  'place_id',
  'start_date',
  'race_no',
  'entry_no',
  'type',
  'memo',
  'old_start_time',
  'new_start_time'
];

$database = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';port=' . $port . ';charset=utf8mb4', $user, $password, array(
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_EMULATE_PREPARES => false,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
));

$today = date('Ymd');
$tomorrow = date('Ymd', strtotime(date('Ymd') . '+' . '1' . ' days')); // 1일 후                                                                     
echo $tomorrow . PHP_EOL;
$get_date = $today;
if ($argv[1] == 'today') {
  $get_date = $today;
}

$data = array();
//return doAction('/JRADB/accessI.html','pw01ide01/4F')

$html = file_get_contents_post('https://www.jra.go.jp/JRADB/accessI.html', array('cname' => 'pw01ide01/4F'));
// echo '$html=>' . $html . PHP_EOL;

$dom = HtmlDomParser::str_get_html($html);
// $trs = $dom->find('body > table > tr > td > table:nth-child(3) > tr > td:nth-child(2) > table:nth-child(6) > tr');
$aTable = $dom->find('.kaisai_list_unit table.basic');
// print_r($aTable);
// exit();

foreach ($aTable as $key => $table) {
  $caption = $table->find('caption');
  // print_r($caption);
  // exit();
  $remark =  $caption[0]->textContent;
  $sql = "select place_id, start_date from race where association_code = 'jra' and start_date >= date(now()) and remark = '{$remark}' limit 1";
  // echo $sql;
  $placeInfo = select_query_one($database, $sql);
  // print_r($placeInfo);
  // exit();
  $place_id = $placeInfo->place_id;
  $start_date = $placeInfo->start_date;

  $aTr = $table->find('tr.change');
  foreach ($aTr as $key => $tr) {
    // print_r($tr);

    $th = $tr->findOne('th');
    print_r($th);
    $race_no = $th->textContent;
    print_r($race_no);

    $aDl = $tr->find('dl');
    print_r($aDl);

    foreach ($aDl as $key => $dl) {

      $dt = $dl->findOne('dt');
      print_r($dt);
      $type = $code[trim($dt->textContent)];
      print_r($type);
      $dd = $dl->findOne('dd');



      // exit();

      $entry_no = 0;
      $memo = '';
      $old_start_time = $start_date;
      $new_start_time = $start_date;

      switch ($type) {
        case '출발시각변경':
          $aText = explode('から', $dd->textContent);
          $aText = explode('に変更', end($aText))[0];
          $aText = explode('時', $aText);
          $hour = $aText[0];
          $minute = str_replace('分', '', $aText[1]);
          $new_start_time = "{$start_date} {$hour}:{$minute}:00";
          $memo = '출발시각변경' . ' => ' . $new_start_time;

          break;

        case '출전취소':
        case '출전제외':
          $entry_no = trim(explode('番', $dd->textContent)[0]);
          $memo = $entry_no . '번마 ' .  $type;
          break;

        case '기수변경':
          $entry_no = trim(explode('番', $dd->textContent)[0]);
          $memo = $entry_no . '번마 ' .  $type;
          break;

        default:
          # code...
          break;
      }

      $data[] = [
        'place_id' => $place_id,
        'start_date' => $start_date,
        'race_no' => $race_no,
        'entry_no' => $entry_no,
        'type' => $type,
        'memo' => $memo,
        'old_start_time' => $old_start_time,
        'new_start_time' => $new_start_time,
      ];
    }
  }
}

print_r($data);

foreach ($data as $i => $raceChange) {

  $place_id = $raceChange['place_id'];
  $start_date = $raceChange['start_date'];
  $race_no = $raceChange['race_no'];
  $sql = "SELECT `race`.id as id, `race`.association_code, `race`.start_time, `place`.name as place_name FROM `race` left outer join `place` on `race`.place_id = `place`.id WHERE start_time >= date('" . $start_date . "') and start_time < date(date_add('" . $start_date . "', interval 1 day)) and '" . $place_id . "' = place_id and race_no = '" . $race_no . "'";

  $race = select_query_one($database, $sql);
  $race_id = $race->id;
  $place_name = $race->place_name;

  $raceChange['race_id'] = $race_id;
  $raceChange['association_code'] = $race->association_code;

  // $sql = 'SELECT stat FROM `race` WHERE id = ' . $race_id . ' LIMIT 1';
  // print_r($sql);
  // $stat = select_query($database, $sql);
  // if 문 레이스아디로 레이스의 상태 체크 - 완료 아닐시 아래 진행
  // if ($stat[0]->stat === 'E') {
  //   // continue;
  // }
  // INSERT INTO `goldrace`.`race_change_info` (`race_id`, `type`, `memo`, `old_start_time`, `new_start_time`) VALUES (7533, '출주취소', '어쩌구', '0000-00-00 00:00:00', '0000-00-00 00:00:00');

  $entry_no = $raceChange['entry_no'];
  $type = $raceChange['type'];
  $memo = $place_name . " " . $race_no . "경주: " . $raceChange['memo'];
  $old_start_time = $raceChange['old_start_time'];
  $new_start_time = $raceChange['new_start_time'];
  $sql = "INSERT INTO `race_change_info` (`association_code`, `race_id`, `entry_no`, `type`, `memo`, `old_start_time`, `new_start_time`) SELECT 'jra', " . $race_id . "," . $entry_no . ", '" . $type . "' , '" . $memo . "','" . $old_start_time . "','" . $new_start_time . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race_change_info` WHERE `race_id`= " . $race_id . " and  `entry_no` = " . $entry_no . " and  `type` = '" . $type . "' and  `memo` = '" . $memo . "' and  `old_start_time` = '" . $old_start_time . "' and `new_start_time` = '" . $new_start_time . "')";
  echo $sql . PHP_EOL;

  $result = exec_query_lastId($database, $sql);
  // echo $index . '->' . $lastId . PHP_EOL;
  print_r($result);

  if ($result->id > 0) {
    updateBettingByRaceChange($database, $raceChange);
  }
}
