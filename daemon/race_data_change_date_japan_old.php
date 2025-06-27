<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

function get_race_data_to_json($url)
{
	global $data;
	print_r($url);
	$code = ['騎手変更' => '기수변경', '出走取消' => '출전취소', '競走除外' => '출전제외'];
	//$url='http://www2.keiba.go.jp/KeibaWeb/TodayRaceInfo/RaceList?k_babaCode=19&k_raceDate=2016/06/21';

	$place_own_id = explode('babaCode=', $url);
	//$place_own_id = explode( '&k_raceDate', $place_own_id[1]);
	$place_own_id = $place_own_id[1];

	//$date = substr($url, -10, 10 );
	//$date = str_replace('/', '-', $date);

	$date = explode('k_raceDate=', $url)[1];
	$date = explode('&k_babaCode', $date)[0];
	$date = str_replace('%2f', '-', $date);
	$date = preg_replace("/([\d]{4})([\d]{2})/", "$1-$2-$3", $date);
	$dom = new DomDocument;
	//$dom->loadHtmlFile($url);
	// 실행
	$dom->loadHtmlFile($url);
	$html = strtolower(substr($dom->saveHTML(), -6, 4));
	echo $html . PHP_EOL;
	if ($html !== 'html') {
		return false;
	}
	echo 'ok' . PHP_EOL;
	// 	do {
	// 		$dom->loadHtmlFile( $url );
	// 	} while ( trim(substr( $dom->saveHTML(), -8 )) != '</html>' );
	$xpath = new DomXPath($dom);
	$headerNames = ['own_id', 'start_date', 'race_no', 'entry_no', 'type', 'memo', 'old_start_time', 'new_start_time'];
	//*[@id="container00"]/table/tbody/tr[2]/td/table/tbody/tr/td/article[1]/table/tbody
	$tbody = $xpath->query('//*[@id="container00"]//article[@class="raceInfo"]/table/tbody');
	//*[@id="container00"]/table/tbody/tr[2]/td/table/tbody/tr/td/table[2]/tbody
	foreach ($xpath->query('//tr[@class="dbnote"]', $tbody[0]) as $index => $node) {

		$rData = array();
		$rowData = array();
		$rowData[] = $place_own_id;
		$rowData[] = $date;

		foreach ($xpath->query('td', $node) as $cell) {
			$rowData[] = trim($cell->nodeValue);
		}

		if (!strchr($rowData[4], ':')) {
			continue;
		}
		echo PHP_EOL;
		print_r($rowData);
		$rowData = array_slice($rowData, 0, 8);
		$rowData[6] = $rowData[3];
		$rowData[7] = $rowData[4];
		$rowData[3] = '0';
		$rowData[4] = '출발시각변경';
		$rowData[5] = $rowData[4] . ' ' . $rowData[6] . ' => ' . $rowData[7];
		$rowData[6] = date("Y-m-d ") . $rowData[6];
		$rowData[7] = date("Y-m-d ") . $rowData[7];

		$rowData = array_values($rowData);

		$data[] = array_combine($headerNames, $rowData);
	}

	$tbody = $xpath->query('//table//td[@class="dbtbl"]//span[text() = "変更情報"]/../../..');

	foreach ($xpath->query('//tr[@class="dbdata"]', $tbody[0]) as $index => $node) {

		$rData = array();
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

function select_sql($sql)
{
	include(__DIR__ . '/../../../application/configs/configdb.php');

	$mysqli = new mysqli($host, $user, $password, $dbname);
	// 연결 오류 발생 시 스크립트 종료
	if ($mysqli->connect_errno) {
		die('Connect Error: ' . $mysqli->connect_error);
	}

	if ($result = $mysqli->query($sql)) {
		// 레코드 출력
		$v = array();
		while ($row = mysqli_fetch_object($result)) {
			//print_r( $row->id);
			$v[] = $row;
		}
	} else {
		$v = array(0 => 'empty');
	}
	return $v;

	$result->free(); //메모리해제

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

$date_url = 'http://www2.keiba.go.jp/KeibaWeb/TodayRaceInfo/TodayRaceInfoTop';

$dom = new DomDocument;
$dom->loadHtmlFile($date_url);
$xpath = new DomXPath($dom);

//$alinks = $xpath->query('//*[@id="container00"]/table//tr[2]/td/table//tr/td/table//tr/td/table[3]//tr/td/table//tr/td[3]/table//tr/td/table//tr/td/a/@href');
$alinks = $xpath->query('//*[@id="mainContainer"]/article[@class="courseInfo"]/div/table/tbody/tr[2]/td[3]/a/@href');

/*foreach ($alinks as $index=>$alink) {

	if ($alink->value === '' ){
		unset($alinks[$index]);
	}
}*/

$data = array();

foreach ($alinks as $alink) {
	$url = 'http://www2.keiba.go.jp' . $alink->value;
	get_race_data_to_json($url);
}
//print_r($data);
echo json_encode($data, JSON_UNESCAPED_UNICODE);



//sql 변경 정보 삽입 

$msg = array();
$headerNames = ['own_id', 'start_date', 'race_no', 'reg_result'];
foreach ($data as $i => $r) {

	$own_id = $r['own_id'];
	$start_date = $r['start_date'];
	$race_no = $r['race_no'];
	$sql = "SELECT `race`.id as id, `place`.name as place_name FROM `race` left outer join `place` on `race`.place_id = `place`.id WHERE start_time = date('" . $start_date . "') and (SELECT id from place where own_id = '" . $own_id . "') = place_id and race_no = '" . $race_no . "'";

	$v = select_sql($sql);
	$race_id =  $v[0]->id;
	$place_name =  $v[0]->place_name;

	$sql = 'SELECT stat FROM `race` WHERE id = ' . $race_id . ' LIMIT 1';
	//print_r($sql);
	$stat = select_sql($sql);
	//if 문 레이스아디로 레이스의 상태 체크 - 완료 아닐시 아래 진행
	if ($stat[0]->stat === 'E') {
		//continue;
	}
	//INSERT INTO `goldrace`.`race_change_info` (`race_id`, `type`, `memo`, `old_start_time`, `new_start_time`) VALUES (7533, '출주취소', '어쩌구', '0000-00-00 00:00:00', '0000-00-00 00:00:00');	
	$entry_no = $r['entry_no'];
	$type = $r['type'];
	$memo = $place_name . " " . $race_no . "경주: " . $r['memo'];
	$old_start_time = $r['old_start_time'];
	$new_start_time = $r['new_start_time'];
	$sql = "INSERT INTO `race_change_info` (`association_code`, `race_id`, `entry_no`, `type`, `memo`, `old_start_time`, `new_start_time`) SELECT 'japanrace', " . $race_id .  "," . $entry_no . ", '" . $type . "' , '" . $memo .  "','"  . $old_start_time .  "','"  .  $new_start_time .  "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race_change_info` WHERE `race_id`= "  . $race_id .  " and  `entry_no` = " . $entry_no . " and  `type` = '" . $type .  "' and  `memo` = '" . $memo .  "' and  `old_start_time` = '" . $old_start_time . "' and `new_start_time` = '" .  $new_start_time . "')";
	echo $sql;

	$ok = insert_sql($sql);
	echo $ok;
}

//$msg[] = array_combine($headerNames,['', '', '', 'success']) ;
//print_r($msg);
//return $msg;
//echo json_encode($msg, JSON_UNESCAPED_UNICODE);
