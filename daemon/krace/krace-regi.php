<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require_once('/srv/irace/daemon/common/pdo-tool.php');
require_once('/srv/irace/daemon/common/config-db.php');
function get_race_data_to_json($url)
{
  global $data;

  $place_own_id = '10' . substr($url, -1, 1);

  //$url = 'https://www.kcycle.or.kr/contents/information/fixedChuljuPage.do';
  $dom = new DomDocument;

  // 실행
  if (!$dom->loadHtmlFile($url)) {
    echo 'loadHtmlFile Fail' . PHP_EOL;
    return false;
  }
  // 	echo 'loadHtmlFile ok' . PHP_EOL;

  $xpath = new DomXPath($dom);

  // collect header names
  $headerNames = ['own_id', 'start_time', 'race_no', 'length', 'entry_count', 'remark']; //,'race_class','title','result','result_table'];

  $tbody = $xpath->query('//div[@class="tableType2"]/table/tbody');
  //print_r($tbody[0]);

  foreach ($xpath->query('tr', $tbody[0]) as $index => $node) {
    $rowData = array();
    $rowData[] = $place_own_id;

    foreach ($xpath->query('td', $node) as $cell) {
      $rowData[] = trim($cell->nodeValue);
    }
    print_r($rowData);
    // exit();

    $rowData = array_slice($rowData, 0, 11);
    $rowData[2] = str_replace('/', '-', substr($rowData[2], 0, 10)) . ' ' . substr($rowData[9], 0, 5);
    $rowData[5] = substr($rowData[5], 0, -1);
    $rowData[6] = substr($rowData[6], 0, -3);
    unset($rowData[1]);
    unset($rowData[4]);
    unset($rowData[7]);
    unset($rowData[8]);
    unset($rowData[9]);
    $rowData = array_values($rowData);

    print_r($rowData);
    // continue;
    // exit();

    // count entry count
    // 			//$url = 'https://race.kra.co.kr/chulmainfo/chulmaDetailInfoChulmapyo.do?rcDate=20190727&rcNo=1&Sub=1&Act=02&meet=1';
    // 			$url = 'https://race.kra.co.kr/chulmainfo/chulmaDetailInfoChulmapyo.do?rcDate=' . str_replace('-', '', substr($rowData[1], 0, 10)) . '&rcNo=' . $rowData[2] . '&Sub=1&Act=02&meet=' . substr($rowData[0], 2, 1);
    // 			echo $url . PHP_EOL;
    // 			$dom_chulmainfo = new DomDocument;

    // 			// 실행
    // 			if (!$dom_chulmainfo->loadHtmlFile($url)){
    // 				echo 'loadHtmlFile Fail' . PHP_EOL;
    // 				return false;
    // 			}
    // // 			echo 'loadHtmlFile ok' . PHP_EOL;

    // 			$xpath_chulmainfo = new DomXPath($dom_chulmainfo);

    // 			//*[@id="contents"]/form/div[4]/table/tbody/tr[1]
    // 			$trs = $xpath_chulmainfo->query('//*[@id="contents"]/form/div[@class="tableType2"]/table/tbody/tr');
    // // 			echo 'entry counting ' . $rowData[4] . ' = ';
    // 			$rowData[4] = count($trs);
    // 			echo $rowData[4] . PHP_EOL;
    // // 			print_r($rowData);

    // 			임시 제주 주석
    if ($rowData[0] == 102 && $rowData[5] != '중계') {
      // continue;
    }

    $data[] = array_combine($headerNames, $rowData);
  }

  // print_r($data);
  // 	echo json_encode($data, JSON_UNESCAPED_UNICODE) . PHP_EOL;
  // exit();
}

$database = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';port=' . $port . ';charset=utf8mb4', $user, $password, array(
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_EMULATE_PREPARES => false,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
));

$alinks = [
  'https://race.kra.co.kr/chulmainfo/ChulmaDetailInfoList.do?Act=02&Sub=1&meet=1',
  'https://race.kra.co.kr/chulmainfo/ChulmaDetailInfoList.do?Act=02&Sub=1&meet=2',
  'https://race.kra.co.kr/chulmainfo/ChulmaDetailInfoList.do?Act=02&Sub=1&meet=3'
];
// $alinks = ['https://race.kra.co.kr/chulmainfo/ChulmaDetailInfoList.do?Act=02&Sub=1&meet=2'	];
//print_r($alinks[0]->value);

$data = array();
foreach ($alinks as $alink) {
  get_race_data_to_json($alink);
}
//$url = 'https://race.kra.co.kr/chulmainfo/ChulmaDetailInfoList.do?Act=02&Sub=1&meet=1';
//get_race_data_to_json($url);

print_r($data);
// exit();

echo json_encode($data, JSON_UNESCAPED_UNICODE);

foreach ($data as $i => $r) {
  $sql = "INSERT INTO `race` (`place_id`, `association_code`, `start_date`, `start_time`, `race_no`, `race_length`, `entry_count` ,`remark`) SELECT (select `id` from `place` where `own_id`=" . $r['own_id'] .  "), 'krace', date('" . $r['start_time'] . "'),'" . $r['start_time'] .  "',"  . $r['race_no'] .  "," . $r['length'] .  "," . $r['entry_count'] . ",'" . $r['remark'] .  "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race` WHERE `place_id`= (select `id` from `place` where `own_id`=" . $r['own_id'] .  ") and  `race_no` = " . $r['race_no']   .  " and  date(`start_time`) = date('" . $r['start_time'] .  "') )";
  //if ($r['own_id'] === '102'){ echo $sql;}
  echo $sql . PHP_EOL;;
  $ok = exec_query($database, $sql);
  echo $ok . PHP_EOL;;
}
