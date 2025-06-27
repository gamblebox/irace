<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

function get_race_data_to_json($url)
{
	global $data;
	//$place_own_id = explode( 'babaCode=', $url);
	//$place_own_id = explode( '&k_raceDate', $place_own_id[1]);
	//$place_own_id = $place_own_id[0];
	$place_own_id = substr($url, -10, 2);
	//echo $place_own_id;
	//$date = substr($url, -18, 8 );
	//$date = str_replace('/', '-', $date);
	$rk_race_code = substr($url, -8, 6);
	//echo $rk_race_code;
	//$date = preg_replace("/([\d]{4})([\d]{2})/", "$1-$2-$3", $date);
	//echo $date;


	$dom = new DomDocument;
	$dom->loadHtmlFile($url);
	//$dom->loadHtml('$result');

	$xpath = new DomXPath($dom);

	// collect header names
	$headerNames = ['own_id', 'rk_race_code', 'race_no', 'start_time', 'length', 'entry_count']; //,'race_class','title','result','result_table'];

	/*
	foreach ($xpath->query('//tr[@class="dbitem"]//td') as $node) {
			$headerNames[] = $node->nodeValue;
	}*/

	//print_r($headerNames);
	// collect data

	//   /html/body/table/tbody/tr[3]

	//*[@id="oddsField"]/table/tbody/tr[1]
	//print_r($xpath);

	$tbody = $xpath->query('//tbody[@class="raceState"]');
	//print_r($tbody);
	foreach ($xpath->query('tr', $tbody[0]) as $index => $node) {
		$rowData = array();
		$rowData[] = $place_own_id;
		$rowData[] = $rk_race_code;
		$rowData[] = '' . ($index + 1);

		foreach ($xpath->query('td', $node) as $cell) {
			$rowData[] = trim($cell->nodeValue);
		}

		//print_r($rowData);
		$rowData = array_slice($rowData, 0, 7);
		//print_r($rowData);
		$rowData[6] = substr($rowData[6], 0, -3);
		//print_r($rowData);
		unset($rowData[4]);
		//print_r($rowData);
		$data[] = array_combine($headerNames, $rowData);
	}


	//print_r($data);
	//return $data;
	//echo json_encode($data, JSON_UNESCAPED_UNICODE);
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

/*
//$data = file_get_contents('http://mytemporalbucket.s3.amazonaws.com/code.txt');

// curl 리소스를 초기화
$ch = curl_init();

// url을 설정
curl_setopt($ch, CURLOPT_URL, 'http://www2.keiba.go.jp/KeibaWeb/TodayRaceInfo/RaceList?k_babaCode=23&k_raceDate=2016/03/11');

// 헤더는 제외하고 content 만 받음
curl_setopt($ch, CURLOPT_HEADER, 0);

// 응답 값을 브라우저에 표시하지 말고 값을 리턴
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// 브라우저처럼 보이기 위해 user agent 사용
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0');

$content = curl_exec($ch);

// 리소스 해제를 위해 세션 연결 닫음
curl_close($ch);

//$result = substr($content, $s = strpos($content, '<div id="wakuUmaBanJun" style="display: block; ">'), strrpos($content, '</div><!-- wakuUmaBanJun -->') - $s); // 라쿠텐 배당판


//echo '<div id="oddsField"><div class="rateField">'; //라쿠덴 div 아이디 첨가
//echo $content;
$result = explode('<td class="dbtbl">',$content);
$result = explode('<table class="bs" border="0" cellspacing="0" cellpadding="0" width="100%">
<tr class="dbnoteRaceList">
<td>＊オッズ欄の　「○」＝「発売中」　　「●」＝「確定済」<br>
</td></tr>', $result[1]);
//$result= '<table>' . $result[0];
echo $result[0];
//echo '</div></div>';
*/
$tomorrow = date('Ymd', strtotime(date() . '+' . '1' . ' days')); //1일 후 
//print_r($tomorrow);
$get_date = $tomorrow;


//$url = 'http://keiba.rakuten.co.jp/race_card/list/RACEID/201603222218180200';
$date_url = 'http://keiba.rakuten.co.jp/race_card/list/RACEID/'  . $get_date . '0000000000';

//echo $date_url;
$dom = new DomDocument;
$dom->loadHtmlFile($date_url);
$xpath = new DomXPath($dom);
//a/@href
//*[@id="raceMenu"]
$alinks = $xpath->query('//*[@id="raceMenu"]//a/@href');
//print_r($alinks[0]->value);

$data = array();
foreach ($alinks as $alink) {
	$url = 'http://keiba.rakuten.co.jp/' . $alink->value;
	get_race_data_to_json($url);
}
//print_r($data);
//$date = substr($url, -18, 8 );
$date = preg_replace("/([\d]{4})([\d]{2})/", "$1-$2-$3", $get_date);
for ($i = 0; $i < count($data); $i++) {
	$data[$i]['start_time'] = $date . ' ' . $data[$i]['start_time'] . ':00';
	$data[$i]['length'] = preg_replace('/[^0-9]*/s', '', $data[$i]['length']);
}
echo json_encode($data, JSON_UNESCAPED_UNICODE);


//insert
//INSERT INTO `race` (`place_id`, `rk_race_code`, `race_no`, `start_time`, `race_length`, `entry_count`) SELECT (select `id` from `place` where `own_id`=23),200301,10,'2016-05-09 16:40:00',1400,8 FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race` WHERE `place_id`= (select `id` from `place` where `own_id`=23) and `race_no` = 10 and day(`start_time`) = day('2016-05-09 16:40:00') )
foreach ($data as $i => $r) {
	$sql = "INSERT INTO `race` (`place_id`, `rk_race_code`, `race_no`, `start_time`, `race_length`, `entry_count`) SELECT (select `id` from `place` where `own_id`=" . $r['own_id'] .  "),'"  . $r['rk_race_code'] .  "'," . $r['race_no'] .  ",'" . $r['start_time'] .  "'," . $r['length'] .  "," . $r['entry_count'] .  " FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race` WHERE `place_id`= (select `id` from `place` where `own_id`=" . $r['own_id'] .  ") and  `race_no` = " . $r['race_no']   .  " and  `start_date` = date('" . $r['start_time'] .  "') )";
	$ok = insert_sql($sql);
	echo $ok;
}
//print_r($sql);
