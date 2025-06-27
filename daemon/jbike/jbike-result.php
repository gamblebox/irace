<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require_once('/srv/irace/daemon/common/pdo-tool.php');
require_once('/srv/irace/daemon/common/config-db.php');

function get_race_day()
{
  echo date("Y-m-d H:i:s") . PHP_EOL;

  $today = date('Y-m-d');
  $race_day = $today;
  // echo '$race_day ' . $race_day . PHP_EOL;
  $now_time = date("H");
  // echo '$now_time ' . $now_time . PHP_EOL;
  if ($now_time < 2) {
    $race_day = date("Y-m-d", strtotime($race_day . " -1 day"));
  }
  // echo '$race_day ' . $race_day . PHP_EOL;
  return $race_day;
}

// Function to convert class of given object 
function convertObjectClass($array, $final_class)
{
  return unserialize(sprintf(
    'O:%d:"%s"%s',
    strlen($final_class),
    $final_class,
    strstr(serialize($array), ':')
  ));
}

function get_curl($url)
{
  $cookiePath = dirname(__FILE__) . '/cookies_race_result.txt';

  // json
  $ch = curl_init();
  // url을 설정
  curl_setopt($ch, CURLOPT_URL, $url);

  // 헤더 받음
  curl_setopt($ch, CURLOPT_HEADER, 1);

  // 응답 값을 브라우저에 표시하지 말고 값을 리턴
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  // 브라우저처럼 보이기 위해 user agent 사용
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.74 Safari/537.36');

  //리퍼러
  curl_setopt($ch, CURLOPT_REFERER, 'https://autorace.jp');

  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);            // connection timeout : 10초

  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);                 // 원격 서버의 인증서가 유효한지 검사 여부

  // curl_setopt($ch, CURLOPT_SSLVERSION, 2); //ssl 셋팅

  // curl_setopt($ch, CURLOPT_POST, true);                               // POST 전송 여부

  // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

  // curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field_string);      // POST DATA
  // curl_setopt($ch, CURLOPT_POSTFIELDSIZE, 0); //이 값을 0으로 해야 알아서 &post_data 크기를 측정하는듯 

  // curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiePath); // 쿠키 값을 불러와 curl 실행시 같이 전송
  curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiePath); // 쿠키 값을 저장시킵니다.

  curl_setopt($ch, CURLINFO_HEADER_OUT, true); // enable tracking

  $response = curl_exec($ch);

  $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
  $headerString = substr($response, 0, $headerSize);
  $contents = substr($response, $headerSize);

  preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headerString, $matches);

  // print_r($matches);
  $cookies = array();
  foreach ($matches[1] as $item) {
    parse_str($item, $cookie);
    $cookies = array_merge($cookies, $cookie);
  }

  $result['contents'] = $contents;
  $result['cookies'] = $cookies;

  // preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headerString, $matches);

  // print_r($matches);
  // $cookies = array();
  // foreach ($matches[1] as $item) {
  //     parse_str($item, $cookie);
  //     $cookies = array_merge($cookies, $cookie);
  // }

  // $result['contents'] = $contents;
  // $result['cookies'] = $cookies;

  // $info = curl_getinfo($ch); // request info
  // print_r($info);

  // echo $result;
  // 리소스 해제를 위해 세션 연결 닫음
  curl_close($ch);

  return $result;
}
function get_curl_json($url_json, $post_data, $cookies)
{
  $cookiePath = dirname(__FILE__) . '/cookies_race_result.txt';
  $post_field_string = json_encode($post_data);

  // json
  $ch = curl_init();
  // url을 설정
  curl_setopt($ch, CURLOPT_URL, $url_json);

  // 헤더 받음
  curl_setopt($ch, CURLOPT_HEADER, 1);

  // 응답 값을 브라우저에 표시하지 말고 값을 리턴
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  // 브라우저처럼 보이기 위해 user agent 사용
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.74 Safari/537.36');

  //리퍼러
  curl_setopt($ch, CURLOPT_REFERER, 'https://autorace.jp');

  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);            // connection timeout : 10초

  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);                 // 원격 서버의 인증서가 유효한지 검사 여부

  // curl_setopt($ch, CURLOPT_SSLVERSION, 2); //ssl 셋팅

  curl_setopt($ch, CURLOPT_POST, true);                               // POST 전송 여부

  $headers = array(
    'x-xsrf-token: ' . $cookies['XSRF-TOKEN'],
    "Content-Type: application/json",
  );
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

  curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field_string);      // POST DATA
  // curl_setopt($ch, CURLOPT_POSTFIELDSIZE, 0); //이 값을 0으로 해야 알아서 &post_data 크기를 측정하는듯 

  curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiePath); // 쿠키 값을 불러와 curl 실행시 같이 전송
  curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiePath); // 쿠키 값을 저장시킵니다.

  curl_setopt($ch, CURLINFO_HEADER_OUT, true); // enable tracking

  $response = curl_exec($ch);

  $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
  $headerString = substr($response, 0, $headerSize);
  $contents = substr($response, $headerSize);

  preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headerString, $matches);

  // print_r($matches);
  $cookies = array();
  foreach ($matches[1] as $item) {
    parse_str($item, $cookie);
    $cookies = array_merge($cookies, $cookie);
  }

  $result['contents'] = $contents;
  $result['cookies'] = $cookies;

  // $info = curl_getinfo($ch); // request info
  // print_r($info);

  // echo $result;
  // 리소스 해제를 위해 세션 연결 닫음
  curl_close($ch);

  return $result;
}

function get_race_result($database, $race)
{
  $placeName2Code = array(
    'kawaguchi' => 2,
    'isesaki' => 3,
    'hamamatsu' => 4,
    'iizuka' => 5,
    'sanyou' => 6,
  );
  $placeCode = $placeName2Code[$race->e_name];
  $result = get_curl('https://autorace.jp');
  print_r($result);

  $cookies = $result['cookies'];

  $url = 'https://autorace.jp/race_info/RaceResult';
  $postData = array(
    'placeCode' => $placeCode,
    'raceDate' => $race->start_date,
    'raceNo' => $race->race_no
  );
  print_r($postData);

  $result = get_curl_json($url, $postData, $cookies);
  print_r($result);
  // exit();

  $json = json_decode($result['contents']);
  print_r($json);
  // exit();

  if ($json->result != 'Success') {
    return;
  }

  $key2type = array(
    'rfw' => '복승',
    'rtw' => '쌍승',
    'rf3' => '삼복승',
    'rt3' => '삼쌍승',
    'wid' => '복연승'
  );
  $data = array();

  $odds_bok = '';
  $odds_ssang = '';
  $odds_sambok = '';
  $odds_samssang = '';
  $odds_bokyun = '';
  foreach ($json->body->refundInfo as $key => $value) {
    echo $key . ':' . PHP_EOL;
    print_r($value);

    $type = $key2type[$key];
    if (!$type) {
      continue;
    }

    foreach ($value->list as $item) {
      print_r($item);
      $item = (array)$item;

      $place_1 = $item['1thCarNo'];
      $place_2 = $item['2thCarNo'];
      $place_3 = $item['3thCarNo'];
      if (!$place_1) $place_1 = 0;
      if (!$place_2) $place_2 = 0;
      if (!$place_3) $place_3 = 0;

      $odd = $item['refund'] / 100;
      $odd = $odd > 100 ? 100 : $odd;

      switch ($type) {
        case '복승':
          $odds_bok .= ' ' . $odd;
          break;

        case '쌍승':
          $odds_ssang .= ' ' . $odd;
          break;

        case '삼복승':
          $odds_sambok .= ' ' . $odd;
          break;

        case '삼쌍승':
          $odds_samssang .= ' ' . $odd;
          break;

        case '복연승':
          $odds_bokyun .= ' ' . $odd;
          break;

        default:
          # code...
          break;
      }

      $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $place_1, 'place_2' => $place_2, 'place_3' => $place_3, 'odds' => $odd);
    }
  }
  // print_r($data);
  // exit();

  if (count($data) < 3) {
    echo '등록통과(결과 3개 이하)' . PHP_EOL;
    return;
  }

  $sql = 'SELECT stat FROM `race` WHERE id = ' . $race->id . ' LIMIT 1';
  // print_r($sql);
  $stat = select_query($database, $sql);
  //     echo $race_id . '->' . $stat[0]->stat;
  // if 문 레이스아디로 레이스의 상태 체크 - 취소/완료 아닐시 아래 진행
  if ($stat[0]->stat == 'E' || $stat[0]->stat == 'C') {
    echo '등록통과(취소/완료)' . PHP_EOL;
    return;
  }
  echo '등록진행' . PHP_EOL;

  foreach ($data as $key => $value) {
    $sql = 'INSERT INTO `result` (`race_id`, `type`, `place_1`, `place_2`, `place_3`, `odds`) SELECT ' . $value['race_id'] . ', \'' . $value['type'] . '\' , ' . $value['place_1'] . ',' . $value['place_2'] . ',' . $value['place_3'] . ',' . $value['odds'] . ' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `result` WHERE `race_id`= ' . $value['race_id'] . ' and  `type` = \'' . $value['type'] . '\' and  `place_1` = ' . $value['place_1'] . ' and  `place_2` = ' . $value['place_2'] . ' and `place_3` = ' . $value['place_3'] . ')';
    echo $sql . PHP_EOL;

    $ok = exec_query($database, $sql);
    echo $ok . PHP_EOL;
  }

  $odds_all = '';
  $place = array(
    $json->body->raceResult[0]->carNo,
    $json->body->raceResult[1]->carNo,
    $json->body->raceResult[2]->carNo
  );


  $sql = "UPDATE `race` SET `stat`='E', place_1 = '" . $place[0] . "', place_2 = '" . $place[1] . "', place_3 = '" . $place[2] . "', odds_bok = '" . trim($odds_bok) . "', odds_ssang = '" . trim($odds_ssang) . "', odds_sambok = '" . trim($odds_sambok) . "', odds_samssang = '" . trim($odds_samssang) . "', odds_bokyun = '" . trim($odds_bokyun) . "',	odds_all = '" . $odds_all . "' WHERE stat = 'P' and `id`= " . $race->id;
  echo $sql . PHP_EOL;
  $ok = exec_query($database, $sql);
  echo $ok . PHP_EOL;
}

$race_day = get_race_day();
// $race_day = '2022-04-12';
// print_r($race_day);

$database = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';port=' . $port . ';charset=utf8mb4', $user, $password, array(
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_EMULATE_PREPARES => false,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
));
// $sql = "SELECT * FROM race WHERE race.start_time < now() and race.start_time > date_add(date(now()),INTERVAL -20 HOUR) and race.association_code in ('osr','osh','osg') and stat = 'P' order by start_time asc;";
$sql = "SELECT *, race.id as id FROM race left join place on race.place_id = place.id WHERE race.start_time < now() and race.start_date = '" . $race_day . "' and race.association_code in ('jbike') and stat = 'P' order by start_time asc;";
echo $sql . PHP_EOL;
$races = select_query($database, $sql);
print_r($races);
// exit();

if ($races) {
  foreach ($races as $i => $race) {
    get_race_result($database, $race);
  }
  echo 'Done' . PHP_EOL;
} else {
  echo 'Nothing To DO...' . PHP_EOL;
}
