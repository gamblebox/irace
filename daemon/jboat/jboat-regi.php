<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require_once('/srv/irace/daemon/common/pdo-tool.php');
require_once('/srv/irace/daemon/common/config-db.php');
function get_race_data_to_json($url, $id)
{
  global $data;

  // $place_own_id = explode( 'babaCode=', $url);
  $place_own_id = 110000 + (int)$id;
  // echo $place_own_id . PHP_EOL;

  $dom = new DomDocument();

  // 실행
  $dom->loadHtmlFile($url);
  // echo 'saveHTML' . $dom->saveHTML() . PHP_EOL;
  $html = strtolower(substr($dom->saveHTML(), -6, 4));
  echo $html . PHP_EOL;
  if ($html !== 'html') {
    return false;
  }
  echo 'ok' . PHP_EOL;

  $xpath = new DomXPath($dom);

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
  $trs = $xpath->query('//div[@class="table1"]/table/tbody/tr');
  print_r($trs);
  if (count($trs) < 1) {
    echo 'No Data !!' . PHP_EOL;
    return;
  }
  // exit();
  foreach ($trs as $index => $tr) {
    $rowData = array();
    $rowData[] = $place_own_id;
    $rowData[] = '';

    $tds = $xpath->query('td', $tr);
    $race_no = str_replace('R', '', trim($tds[0]->nodeValue));
    // echo '$race_no=>' . $race_no . PHP_EOL;
    $rowData[] = $race_no;
    $rowData[] = $tds[1]->nodeValue;
    $entry_count = 0;
    for ($i = 3; $i < 9; $i++) {
      $value = $xpath->query('div[3]', $tds[$i]);
      $value = $value[0]->nodeValue;
      // echo '$value=>' . $value . PHP_EOL;
      if ($value != '') {
        $entry_count++;
      }
    }
    $rowData[] = 1800;
    $rowData[] = $entry_count;

    $data[] = array_combine($headerNames, $rowData);
  }
  // print_r($data);
  // exit();
  // echo json_encode($data, JSON_UNESCAPED_UNICODE);
}

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
for ($i = 1; $i < 25; $i++) {
  //https://boatrace.jp/owpc/pc/race/raceindex?jcd=02&hd=20200926
  $id = str_pad($i, 2, "0", STR_PAD_LEFT);
  $url = 'https://boatrace.jp/owpc/pc/race/raceindex?jcd=' . $id . '&hd=' . $get_date;
  echo '$url=>' . $url . PHP_EOL;
  get_race_data_to_json($url, $id);
}
// print_r($data);
// $date = substr($url, -18, 8 );
$date = preg_replace("/([\d]{4})([\d]{2})/", "$1-$2-$3", $get_date);
for ($i = 0; $i < count($data); $i++) {
  $data[$i]['start_time'] = $date . ' ' . $data[$i]['start_time'] . ':00';
}
// echo json_encode($data, JSON_UNESCAPED_UNICODE);
print_r($data);
echo PHP_EOL;

foreach ($data as $i => $r) {
  $sql = "INSERT INTO `race` (`place_id`, `association_code`, `rk_race_code`, `race_no`, `start_date`, `start_time`, `race_length`, `entry_count`) SELECT (select `id` from `place` where `own_id`=" . $r['own_id'] . "), 'jboat', '" . $r['rk_race_code'] . "'," . $r['race_no'] . ", date('" . $r['start_time'] . "'),'" . $r['start_time'] . "'," . $r['length'] . "," . $r['entry_count'] . " FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race` WHERE `place_id`= (select `id` from `place` where `own_id`=" . $r['own_id'] . ") and  `race_no` = " . $r['race_no'] . " and  date(`start_time`) = date('" . $r['start_time'] . "') )";
  print_r($sql);

  $ok = exec_query($database, $sql);
  echo $i . ':' . $ok . PHP_EOL;
}
// print_r($sql);
