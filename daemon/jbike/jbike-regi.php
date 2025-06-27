<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require_once('/srv/irace/daemon/common/pdo-tool.php');
require_once('/srv/irace/daemon/common/config-db.php');
function apiPost1($url, $post_data)
{
  $post_field_string = http_build_query($post_data, '', '&');

  $cpost = curl_init($url);
  curl_setopt($cpost, CURLOPT_URL, $url);
  curl_setopt($cpost, CURLOPT_RETURNTRANSFER, true);
  // curl_setopt($cpost, CURLOPT_USERPWD,"$username:$password");
  curl_setopt($cpost, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
  curl_setopt($cpost, CURLINFO_HEADER_OUT, true);
  $cookiePath = dirname(__FILE__) . '/cookies.txt';

  curl_setopt($cpost, CURLOPT_COOKIEJAR, $cookiePath);
  curl_setopt($cpost, CURLOPT_COOKIEFILE, $cookiePath); //Set header to fetch token.
  $header = array('x-csrf-token: Fetch', 'Connection: keep-alive');
  curl_setopt($cpost, CURLOPT_HTTPHEADER, $header);
  $headers = []; //Read back response headers.
  curl_setopt($cpost, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$headers) {
    $len = strlen($header);
    array_push($headers, strtolower($header));
    return $len;
  }); //Execute and close curl.
  $result = curl_exec($cpost); //Retrieve token, cookies and set header.

  // echo 'header';
  // print_r($header);
  // return;

  $token = '';
  foreach ($headers as $h) {
    if (strpos($h, 'x-csrf-token:') !== false) {
      list(, $token) = explode(': ', $h);
      $token = trim(preg_replace('/\s\s+/', '', $token));
    }
  }

  $header = array('x-csrf-token: ' . $token, 'Connection: keep-alive');

  echo 'header';
  print_r($header);
  // return;

  curl_setopt($cpost, CURLOPT_POST, true);
  curl_setopt($cpost, CURLOPT_HTTPHEADER, $header);
  curl_setopt($cpost, CURLINFO_HEADER_OUT, true);
  curl_setopt($cpost, CURLOPT_POSTFIELDS, $post_field_string);
  curl_setopt($cpost, CURLOPT_COOKIEJAR, $cookiePath);
  curl_setopt($cpost, CURLOPT_COOKIEFILE, $cookiePath);

  $result = curl_exec($cpost);
  $information = curl_getinfo($cpost);
  curl_close($cpost);
  return simplexml_load_string($result);
}

function apiPost($url, $post_data)
{
  $post_field_string = http_build_query($post_data, '', '&');

  $cpost = curl_init($url);
  curl_setopt($cpost, CURLOPT_URL, $url);
  curl_setopt($cpost, CURLOPT_RETURNTRANSFER, true);
  // curl_setopt($cpost, CURLOPT_USERPWD,"$username:$password");
  // curl_setopt($cpost, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
  curl_setopt($cpost, CURLINFO_HEADER_OUT, true);
  $cookiePath = dirname(__FILE__) . '/cookies.txt';

  curl_setopt($cpost, CURLOPT_COOKIEJAR, $cookiePath);
  curl_setopt($cpost, CURLOPT_COOKIEFILE, $cookiePath); //Set header to fetch token.
  $header = array('x-csrf-token: Fetch', 'Connection: keep-alive');
  curl_setopt($cpost, CURLOPT_HTTPHEADER, $header);
  $headers = []; //Read back response headers.
  curl_setopt($cpost, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$headers) {
    $len = strlen($header);
    array_push($headers, strtolower($header));
    return $len;
  }); //Execute and close curl.
  $result = curl_exec($cpost); //Retrieve token, cookies and set header.

  $token = '';
  foreach ($headers as $h) {
    if (strpos($h, 'x-csrf-token:') !== false) {
      list(, $token) = explode(': ', $h);
      $token = trim(preg_replace('/\s\s+/', '', $token));
    }
  }

  $header = array('x-csrf-token: ' . $token, 'Connection: keep-alive');

  echo 'token';
  print_r($token);
  // return;

  curl_setopt($cpost, CURLOPT_POST, true);
  curl_setopt($cpost, CURLOPT_HTTPHEADER, $header);
  curl_setopt($cpost, CURLINFO_HEADER_OUT, true);
  curl_setopt($cpost, CURLOPT_POSTFIELDS, $post_field_string);
  curl_setopt($cpost, CURLOPT_COOKIEJAR, $cookiePath);
  curl_setopt($cpost, CURLOPT_COOKIEFILE, $cookiePath);

  $result = curl_exec($cpost);
  $information = curl_getinfo($cpost);
  curl_close($cpost);
  return simplexml_load_string($result);
}


function get_curl_full($url, $url_json, $post_data)
{
  // $post_field_string = http_build_query($post_data, '', '&');

  // // curl 리소스를 초기화
  $ch = curl_init();

  // // url을 설정
  curl_setopt($ch, CURLOPT_URL, $url);

  // // 헤더 받음
  curl_setopt($ch, CURLOPT_HEADER, 1);

  // // 응답 값을 브라우저에 표시하지 말고 값을 리턴
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  // 브라우저처럼 보이기 위해 user agent 사용

  // curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0');
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.74 Safari/537.36');

  //리퍼러
  // curl_setopt($ch, CURLOPT_REFERER, $url);
  // curl_setopt($ch, CURLOPT_REFERER, 'https://autorace.jp/race_info/Program/isesaki/2022-04-07_3');
  curl_setopt($ch, CURLOPT_REFERER, 'https://autorace.jp');

  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);               // connection timeout : 10초

  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);                 // 원격 서버의 인증서가 유효한지 검사 여부

  // curl_setopt($ch, CURLOPT_SSLVERSION, 3); //ssl 셋팅

  // curl_setopt($ch, CURLOPT_POST, true);                               // POST 전송 여부

  // curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field_string);      // POST DATA

  // curl_setopt($ch, CURLOPT_POSTFIELDSIZE, 0); //이 값을 0으로 해야 알아서 &post_data 크기를 측정하는듯 

  $cookiePath = dirname(__FILE__) . '/cookies.txt';


  curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiePath); // 쿠키 값을 저장시킵니다.

  // curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiePath); // 쿠키 값을 불러와 curl 실행시 같이 전송


  $result = curl_exec($ch);
  // echo $result;
  // get cookie
  // multi-cookie variant contributed by @Combuster in comments
  // preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);
  preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);

  print_r($matches);
  $cookies = array();
  foreach ($matches[1] as $item) {
    parse_str($item, $cookie);
    // $arr = explode('=', $item);
    // $cookie = array($arr[0] => $arr[1]);
    $cookies = array_merge($cookies, $cookie);
  }
  var_dump($cookies);

  $post_data['x-xsrf-token'] = $cookies['XSRF-TOKEN'];
  // $post_data['race_info_session'] = $cookies['race_info_session'];


  // $post_data = array_merge($post_data, $cookies);

  print_r($post_data);

  $post_field_string = http_build_query($post_data, '', '&');


  // return;

  // json
  // $ch = curl_init();
  // url을 설정
  curl_setopt($ch, CURLOPT_URL, $url_json);

  // 헤더 받음
  curl_setopt($ch, CURLOPT_HEADER, 1);

  // // 응답 값을 브라우저에 표시하지 말고 값을 리턴
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  // 브라우저처럼 보이기 위해 user agent 사용
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.74 Safari/537.36');

  //리퍼러
  curl_setopt($ch, CURLOPT_REFERER, 'https://autorace.jp/race_info/Live/isesaki');

  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);               // connection timeout : 10초

  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);                 // 원격 서버의 인증서가 유효한지 검사 여부

  curl_setopt($ch, CURLOPT_SSLVERSION, 1); //ssl 셋팅

  curl_setopt($ch, CURLOPT_POST, true);                               // POST 전송 여부

  curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field_string);      // POST DATA

  // curl_setopt($ch, CURLOPT_POSTFIELDSIZE, 0); //이 값을 0으로 해야 알아서 &post_data 크기를 측정하는듯 

  // curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiePath); // 쿠키 값을 저장시킵니다.

  curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiePath); // 쿠키 값을 불러와 curl 실행시 같이 전송


  $result = curl_exec($ch);

  echo $result;
  // 리소스 해제를 위해 세션 연결 닫음


  curl_close($ch);

  return $result;
}

function get_curl($url, $url_json, $post_data)
{
  // $post_field_string = http_build_query($post_data, '', '&');

  // // curl 리소스를 초기화
  $ch = curl_init();

  // // url을 설정
  curl_setopt($ch, CURLOPT_URL, $url);

  // // 헤더 받음
  curl_setopt($ch, CURLOPT_HEADER, 1);

  // // 응답 값을 브라우저에 표시하지 말고 값을 리턴
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  // 브라우저처럼 보이기 위해 user agent 사용

  // curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0');
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.74 Safari/537.36');

  //리퍼러
  // curl_setopt($ch, CURLOPT_REFERER, $url);
  // curl_setopt($ch, CURLOPT_REFERER, 'https://autorace.jp/race_info/Program/isesaki/2022-04-07_3');
  curl_setopt($ch, CURLOPT_REFERER, 'https://autorace.jp');

  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);               // connection timeout : 10초

  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);                 // 원격 서버의 인증서가 유효한지 검사 여부

  // curl_setopt($ch, CURLOPT_SSLVERSION, 3); //ssl 셋팅

  // curl_setopt($ch, CURLOPT_POST, true);                               // POST 전송 여부

  // curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field_string);      // POST DATA

  // curl_setopt($ch, CURLOPT_POSTFIELDSIZE, 0); //이 값을 0으로 해야 알아서 &post_data 크기를 측정하는듯 

  $cookiePath = dirname(__FILE__) . '/cookies.txt';


  curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiePath); // 쿠키 값을 저장시킵니다.

  // curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiePath); // 쿠키 값을 불러와 curl 실행시 같이 전송


  $result = curl_exec($ch);
  // echo $result;
  // get cookie
  // multi-cookie variant contributed by @Combuster in comments
  // preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);
  preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);

  print_r($matches);
  $cookies = array();
  foreach ($matches[1] as $item) {
    parse_str($item, $cookie);
    // $arr = explode('=', $item);
    // $cookie = array($arr[0] => $arr[1]);
    $cookies = array_merge($cookies, $cookie);
  }
  var_dump($cookies);

  return $cookies;

  $post_data['x-xsrf-token'] = $cookies['XSRF-TOKEN'];
  // $post_data['race_info_session'] = $cookies['race_info_session'];


  // $post_data = array_merge($post_data, $cookies);

  print_r($post_data);

  $post_field_string = http_build_query($post_data, '', '&');


  // return;

  // json
  // $ch = curl_init();
  // url을 설정
  curl_setopt($ch, CURLOPT_URL, $url_json);

  // 헤더 받음
  curl_setopt($ch, CURLOPT_HEADER, 1);

  // // 응답 값을 브라우저에 표시하지 말고 값을 리턴
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  // 브라우저처럼 보이기 위해 user agent 사용
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.74 Safari/537.36');

  //리퍼러
  curl_setopt($ch, CURLOPT_REFERER, 'https://autorace.jp/race_info/Live/isesaki');

  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);               // connection timeout : 10초

  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);                 // 원격 서버의 인증서가 유효한지 검사 여부

  curl_setopt($ch, CURLOPT_SSLVERSION, 1); //ssl 셋팅

  curl_setopt($ch, CURLOPT_POST, true);                               // POST 전송 여부

  curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field_string);      // POST DATA

  // curl_setopt($ch, CURLOPT_POSTFIELDSIZE, 0); //이 값을 0으로 해야 알아서 &post_data 크기를 측정하는듯 

  // curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiePath); // 쿠키 값을 저장시킵니다.

  curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiePath); // 쿠키 값을 불러와 curl 실행시 같이 전송


  $result = curl_exec($ch);

  echo $result;
  // 리소스 해제를 위해 세션 연결 닫음


  curl_close($ch);

  return $result;
}

function get_curl_json($url_json, $post_data, $cookies)
{
  $cookiePath = dirname(__FILE__) . '/cookies.txt';
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

function post($url, $post_data)
{

  $postdata = http_build_query(
    $post_data
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

function get_race_data_json_to_json($place)
{
  global $data;
  // collect header names
  $headerNames = [
    'own_id',
    'rk_race_code',
    'race_no',
    'start_time',
    'length',
    'entry_count'
  ];
  $race_day = date('Y-m-d');
  $url = 'https://autorace.jp/netstadium/Live/SLiveRaceInfo/' . $place->e_name . '/?pc=' . $place->e_name . '&date=' . $race_day . '&p_race_no=&vod_date=' . $race_day;
  echo $url . PHP_EOL;
  $sucess = FALSE;
  for ($i = 0; $i < 3; $i++) {
    $file = file_get_contents($url);
    if ($file === FALSE) {
      echo 'file read error' . PHP_EOL;
      continue;
    }
    $json_race = json_decode($file);
    if (!$json_race) {
      echo 'json decoding error' . PHP_EOL;
      continue;
    } else {
      $sucess = TRUE;
      break;
    }
  }
  if (!$sucess) {
    return;
  }

  // print_r($json_race);
  $race_count = (int)$json_race[0]->racenumber;
  echo '$race_count ' . $race_count . PHP_EOL;
  if (!$race_count) {
    return;
  }

  for ($race_no = 1; $race_no < $race_count + 1; $race_no++) {
    $url = 'https://autorace.jp/netstadium/Live/SuperliveRaceHeader/' . $place->e_name . '/?now_race_no=' . $race_no . '&date='  . $race_day . '&is_ajax=1';
    echo $url . PHP_EOL;
    $sucess = FALSE;
    for ($i = 0; $i < 3; $i++) {
      $file = file_get_contents($url);
      if ($file === FALSE) {
        echo 'file read error' . PHP_EOL;
        continue;
      }
      $json_race = json_decode($file);
      if (!$json_race) {
        echo 'json decoding error' . PHP_EOL;
        continue;
      } else {
        $sucess = TRUE;
        break;
      }
    }
    if (!$sucess) {
      continue;
    }
    // print_r($json_race);
    $rowData = array();
    $rowData[] = $place->own_id;
    $rowData[] = '';
    $rowData[] = $json_race->raceno;
    $rowData[] = str_replace('/', '-', $json_race->racedate) . ' ' . $json_race->starttime;
    $rowData[] = $json_race->distance;
    $rowData[] = $json_race->playernumber;

    $data[] = array_combine($headerNames, $rowData);
  }
  // print_r($data);
  // echo json_encode($data, JSON_UNESCAPED_UNICODE);
}
function get_race_data_to_json($place)
{
  global $data;

  $placeName2Code = array(
    'kawaguchi' => 2,
    'isesaki' => 3,
    'hamamatsu' => 4,
    'iizuka' => 5,
    'sanyou' => 6,
  );
  $placeCode = $placeName2Code[$place->e_name];
  // collect header names
  $headerNames = [
    'own_id',
    'rk_race_code',
    'race_no',
    'start_time',
    'length',
    'entry_count'
  ];
  $raceDay = date('Y-m-d');
  $cookies = array();

  $post_data = array(
    'placeCode' => $placeCode,
    'raceDate' => $raceDay,
    'raceNo' => 1
  );

  $result = get_curl_json('https://autorace.jp/race_info/OtherRaceInfo', $post_data, $cookies);
  // print_r($result);
  $json = json_decode($result['contents']);
  if ($json->result != 'Success') {
    return;
  }
  print_r($json);
  $finalRaceNo = $json->body->finalRaceNo;
  $cookies = $result['cookies'];

  for ($i = 1; $i < $finalRaceNo + 1; $i++) {
    $rowData = array();

    $post_data = array(
      'placeCode' => $placeCode,
      'raceDate' => $raceDay,
      'raceNo' => $i
    );

    print_r($post_data);
    $result = get_curl_json('https://autorace.jp/race_info/OtherRaceInfo', $post_data, $cookies);
    // print_r($result);
    $cookies = $result['cookies'];

    $json = json_decode($result['contents']);
    // print_r($json);
    // exit();

    if ($json->result != 'Success') {
      return;
    }
    $rowData[] = $place->own_id;
    $rowData[] = '';
    $rowData[] = $json->body->raceNo;
    $rowData[] = $raceDay . ' ' . $json->body->raceStartTime;
    $rowData[] = $json->body->distance;

    $result = get_curl_json('https://autorace.jp/race_info/Program', $post_data, $cookies);
    $json = json_decode($result['contents']);
    if ($json->result != 'Success') {
      return;
    }
    $cookies = $result['cookies'];
    $rowData[] = count($json->body->playerList);

    $data[] = array_combine($headerNames, $rowData);
  }
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
// $places = array(
// 	'kawaguchi',
// 	'isesaki',
// 	'hamamatsu',
// 	'iizuka',
// 	'sanyou',
// );
$sql = "SELECT * FROM place WHERE association_id = 12";
$places = select_query($database, $sql);
foreach ($places as $key => $place) {
  get_race_data_to_json($place);
}
// print_r($data);
// $date = substr($url, -18, 8 );
// $date = preg_replace("/([\d]{4})([\d]{2})/", "$1-$2-$3", $get_date);
// for ($i = 0; $i < count($data); $i ++) {
//     $data[$i]['start_time'] = $date . ' ' . $data[$i]['start_time'] . ':00';
// }
echo json_encode($data, JSON_UNESCAPED_UNICODE);
echo PHP_EOL;

if ($argv[1] == 'update') {
  foreach ($data as $i => $r) {
    $sql = "UPDATE `race` SET `start_time` = '" . $r['start_time'] . "' WHERE `place_id`= (select `id` from `place` where `own_id`=" . $r['own_id'] . ") and  `race_no` = " . $r['race_no'] . " and  date(`start_time`) = date('" . $r['start_time'] . "')";
    echo $sql . PHP_EOL;
    $ok = exec_query($database, $sql);
    echo $i . ':' . $ok . PHP_EOL;
  }
  exit();
}

foreach ($data as $i => $r) {
  $sql = "INSERT INTO `race` (`place_id`, `association_code`, `rk_race_code`, `race_no`, `start_date`, `start_time`, `race_length`, `entry_count`) SELECT (select `id` from `place` where `own_id`=" . $r['own_id'] . "), 'jbike', '" . $r['rk_race_code'] . "'," . $r['race_no'] . ", date('" . $r['start_time'] . "'),'" . $r['start_time'] . "'," . $r['length'] . "," . $r['entry_count'] . " FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race` WHERE `place_id`= (select `id` from `place` where `own_id`=" . $r['own_id'] . ") and  `race_no` = " . $r['race_no'] . " and  date(`start_time`) = date('" . $r['start_time'] . "') )";
  $ok = exec_query($database, $sql);
  echo $i . ':' . $ok . PHP_EOL;
}
// print_r($sql);
