<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

function get_race_data_to_json($url)
{
	global $data;
	$cycle_own_id = ['광명' => 201, '창원' => 202, '부산' => 203];

	// // curl 리소스를 초기화
	// $ch = curl_init();

	// // url을 설정
	// curl_setopt($ch, CURLOPT_URL, $url);

	// // 헤더는 제외하고 content 만 받음
	// curl_setopt($ch, CURLOPT_HEADER, 0);

	// // 응답 값을 브라우저에 표시하지 말고 값을 리턴
	// curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	// // 브라우저처럼 보이기 위해 user agent 사용

	// //curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0');
	// curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36');

	// //리퍼러
	// curl_setopt( $ch, CURLOPT_REFERER, $url );

	// $content = curl_exec($ch);

	// // 리소스 해제를 위해 세션 연결 닫음
	// curl_close($ch);
	//$content = iconv("euc_kr", "UTF-8", $content); 
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
	//echo $date;
	//echo $content;

	// $url = 'http://13.125.239.248/curl.php?url=' . urlencode($url);
	$url = 'http://3.35.205.96/curl.php?url=' . urlencode($url);
	echo $url;
	// exit();
	$content = file_get_contents($url);

	$dom = new DomDocument;
	//$dom->loadHtmlFile($url);
	$dom->loadHtml('<?xml encoding="UTF-8">' . $content);
	//print_r($dom);
	$xpath = new DomXPath($dom);
	//print_r($xpath);
	// collect header names
	$headerNames = ['own_id', 'start_time', 'race_no', 'length', 'entry_count']; //,'race_class','title','result','result_table'];

	/*
	foreach ($xpath->query('//tr[@class="dbitem"]//td') as $node) {
			$headerNames[] = $node->nodeValue;
	}*/

	//print_r($headerNames);

	// collect data
	//*[@id="printPop"]/h4[2]
	//*[@id="contentForm"]/div[3]/p[1]/strong/text()
	//$date_element = $xpath->query('//*[@id="contentForm"]/div[3]/p[1]');
	//*[@id="contentForm"]/div[4]/div[2]/p[1]/strong
	//*[@id="contentForm"]/div[4]/div[2]/p[1]/strong
	//*[@id="contentForm"]/div[1]/div[2]/p[1]/strong
	$date_element = $xpath->query('//*[@id="contentForm"]/div[1]/div[2]/p[1]/strong');
	$date = trim($date_element[0]->nodeValue);
	preg_match_all('/[0-9,-]+/', $date, $arr);
	// print_r($arr);

	$date = $arr[0][0] . '-' . $arr[0][1] . '-' . $arr[0][2];
	//$date = substr($date,0,4) . '-' . substr($date,8,2) . '-' . substr($date,14,2);
	// print_r($date);
	// exit();

	$list = $xpath->query('//div[@id="printPop"]')[0];
	$heads = $xpath->query('div[@class="boxr-head clearfix"]', $list);
	//*[@id="printPop"]/div[2]/table
	$tables = $xpath->query('div[@class="table bd extable1 pcType"]/table', $list);
	// print_r($tables);
	// exit();
	for ($i = 0; $i < count($heads); $i++) {
		$race = $xpath->query('h2', $heads[$i])[0]->textContent;
		$race_time = $xpath->query('p/span', $heads[$i])[0]->textContent;
		// print_r($race);
		//창원 제01경주
		$place = mb_substr(trim($race), 0, 2);
		$race_no = mb_substr(trim($race), -4, 2);
		$start_time = $date . ' ' . mb_substr(trim($race_time), -5, 5);
		$length = mb_substr(trim($race_time), -15, 4);
		$trs = $xpath->query('tbody/tr', $tables[$i * 2]);
		//*[@id="printPop"]/div[2]/table/tbody/tr[1]
		$entry_count = count($trs);
		// print_r($place);
		// print_r($race_no);
		// echo $entry_count;
		// // print_r($start_time);
		$data[] = array_combine($headerNames, array($cycle_own_id[$place], $start_time, $race_no, $length, $entry_count));
		// exit();
	}

	// print_r($data);
	// echo json_encode($data);
	// exit();
}

function insert_sql($sql)
{
	include(__DIR__ . '/../../../application/configs/configdb.php');

	$mysqli = new mysqli($host, $user, $password, $dbname);
	// 연결 오류 발생 시 스크립트 종료
	if ($mysqli->connect_errno) {
		die('Connect Error: ' . $mysqli->connect_error);
	}

	if ($mysqli->query($sql) === true) {
		return 'ok';
	} else {
		return $mysqli->error;
	}
	$result->free(); //메모리해제
}

$alinks = ['http://www.kcycle.or.kr/contents/information/fixedChuljuPage.do'];
//print_r($alinks[0]->value);


$data = array();
foreach ($alinks as $alink) {
	get_race_data_to_json($alink);
}
echo json_encode($data, JSON_UNESCAPED_UNICODE);

foreach ($data as $i => $r) {
	$sql = "INSERT INTO `race` (`place_id`, `association_code`, `start_date`, `start_time`, `race_no`, `race_length`, `entry_count`) SELECT (select `id` from `place` where `own_id`=" . $r['own_id'] .  "), 'cycle', date('" . $r['start_time'] .  "'),'"  . $r['start_time'] .  "',"  . $r['race_no'] .  "," . $r['length'] .  "," . $r['entry_count'] .  " FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race` WHERE `place_id`= (select `id` from `place` where `own_id`=" . $r['own_id'] .  ") and  `race_no` = " . $r['race_no']   .  " and  date(`start_time`) = date('" . $r['start_time'] .  "') )";
	print_r($sql);
	$ok = insert_sql($sql);

	echo $ok;
}
