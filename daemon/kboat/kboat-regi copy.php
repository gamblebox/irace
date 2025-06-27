<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require_once('/srv/irace/daemon/common/pdo-tool.php');
require_once('/srv/irace/daemon/common/config-db.php');

function get_race_data_to_json($url)
{
  global $data;
  //$cycle_own_id = ['광명'=>201, '창원'=>202, '부산'=>203];

  // // curl 리소스를 초기화
  // $ch = curl_init();

  // // url을 설정
  // curl_setopt($ch, CURLOPT_URL, $url);

  // // 헤더는 제외하고 content 만 받음
  // //curl_setopt($ch, CURLOPT_HEADER, 0);

  // // 응답 값을 브라우저에 표시하지 말고 값을 리턴
  // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  // // 브라우저처럼 보이기 위해 user agent 사용
  // curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0');

  // //리퍼러
  // curl_setopt($ch, CURLOPT_REFERER, "https://www.kboat.or.kr/contents/main/mainPage.do"); 	

  // $content = curl_exec($ch);

  // // 리소스 해제를 위해 세션 연결 닫음
  // curl_close($ch);

  //$place_own_id = explode( 'babaCode=', $url);
  //$place_own_id = explode( '&k_raceDate', $place_own_id[1]);
  //$place_own_id = $place_own_id[0];
  //$place_own_id ='10' . substr($url, -1, 1 );
  //echo $place_own_id;
  //$date = substr($url, -18, 8 );
  //$date = str_replace('/', '-', $date);
  //$rk_race_code = substr($url, -8, 6 );
  //echo $url;
  //$date = preg_replace("/([\d]{4})([\d]{2})/", "$1-$2-$3", $date);
  // $url = 'https://www.kboat.or.kr/contents/main/mainPage.do';
  // $url = 'https://www.kboat.or.kr/contents/information/fixedChuljuPage.do';
  // $url = 'https://13.125.239.248/curl.php?url=' . urlencode($url);
  // $url = 'https://3.35.205.96/curl.php?url=' . urlencode($url);
  echo $url;
  // exit();
  $content = curl($url);
  // $content = substr($content, 0, -2);
  // echo $content . PHP_EOL;
  // exit();
  $dom = new DomDocument;

  $dom->loadHtml('<?xml encoding="UTF-8">' . $content);

  $xpath = new DomXPath($dom);
  // print_r($xpath);
  // collect header names
  // exit();
  $headerNames = ['own_id', 'start_time', 'race_no', 'length', 'entry_count']; //,'race_class','title','result','result_table'];

  /*
	foreach ($xpath->query('//tr[@class="dbitem"]//td') as $node) {
			$headerNames[] = $node->nodeValue;
	}*/

  //print_r($headerNames);

  // collect data
  //*[@id="printPop"]/h4[2]
  //*[@id="contentForm"]/div[3]/p[1]/strong/text()
  //*[@id="content"]/div[5]/div/p[1]/strong
  //*[@id="contents"]/div[1]/div[3]/div[2]/p[1]/strong
  $date_element = $xpath->query('//*[@id="contents"]/div[1]/div[3]/div[2]/p[1]/strong');
  $date = trim($date_element[0]->nodeValue);
  preg_match_all('/[0-9,-]+/', $date, $arr);
  // print_r($arr);

  $date = $arr[0][0] . '-' . $arr[0][1] . '-' . $arr[0][2];
  //$date = substr($date,0,4) . '-' . substr($date,8,2) . '-' . substr($date,14,2);
  // print_r($date);
  // exit();
  //$tbody = $xpath->query('//div[@id="printPop"]');

  //print_r($tbody[0]);
  //*[@id="contentForm"]/div[3]/p[1]/strong

  //print_r($xpath->query('//*[@class="titPlayChart"]'));
  // print_r($xpath->query('//*[@id="content"]//*[@class="titPlayChart"]'));
  //titPlayChart
  //*[@id="content"]/h4[1]
  //*[@id="printPop"]/h4[1]
  //*[@id="content"]/h4[16]
  //*[@id="content"]/div[1]/h5[1]
  //foreach ($xpath->query('//*[@id="content"]/*[@class="titPlayChart"]') as $index=>$node) {
  $races = $xpath->query('//div[@id="printPop"]');
  // print_r($races);
  // exit();
  // print_r($races[0]);

  foreach ($races as $index => $race) {
    if ($index == count($races) - 1) {
      break;
    }
    $head = $xpath->query('div[@class="boxr-head clearfix"]', $race)[0];
    //*[@id="printPop"]/div[2]/div[1]/table
    $table = $xpath->query('div[@class="pcType"]/div[@class="table bd"]/table', $race)[0];
    $race_info = $xpath->query('h4', $head)[0]->textContent;
    $race_info_time = $xpath->query('p/span', $head)[0]->textContent;
    $race_info_length = $xpath->query('p/b', $head)[0]->textContent;
    // print_r($table);
    //창원 제01경주
    // $place = mb_substr(trim($race_info), 1, 2);
    $race_no = mb_substr(trim($race_info), 1, 2);
    $start_time = $date . ' ' . mb_substr(trim($race_info_time), -5, 5);
    $length = mb_substr(trim($race_info_length), -5, 4);
    $trs = $xpath->query('tr', $table);
    $entry_count = count($trs);
    // print_r($trs);
    // print_r($race_no);
    // echo $entry_count;
    // print_r($start_time);
    // print_r($length);
    $data[] = array_combine($headerNames, array(211, $start_time, $race_no, $length, $entry_count));
  }

  // print_r($data);
  // echo json_encode($data);
  // exit();
}

$database = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';port=' . $port . ';charset=utf8mb4', $user, $password, array(
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_EMULATE_PREPARES => false,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
));

$alinks = ['https://www.kboat.or.kr/contents/information/fixedChuljuPage.do'];
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
