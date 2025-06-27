<?php

?>
<!DOCTYPE html>

<html lang="ko">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

	<title>I Race</title>
	<!--<meta name="viewport" content="width=device-width, initial-scale=1">-->
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
</head>

<body>

	<?php
	header("Content-Type:application/json");
	header("Content-Type:text/html;charset=UTF-8");

	function get_race_data_to_json($url)
	{
		global $data;
		//$cycle_own_id = ['광명'=>201, '창원'=>202, '부산'=>203];

		// curl 리소스를 초기화
		$ch = curl_init();

		// url을 설정
		curl_setopt($ch, CURLOPT_URL, $url);

		// 헤더는 제외하고 content 만 받음
		//curl_setopt($ch, CURLOPT_HEADER, 0);

		// 응답 값을 브라우저에 표시하지 말고 값을 리턴
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		// 브라우저처럼 보이기 위해 user agent 사용
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0');

		//리퍼러
		curl_setopt($ch, CURLOPT_REFERER, "http://www.kboat.or.kr/contents/main/mainPage.do");

		$content = curl_exec($ch);

		// 리소스 해제를 위해 세션 연결 닫음
		curl_close($ch);

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
		//echo $content;

		$dom = new DomDocument;

		$dom->loadHtml('<?xml encoding="UTF-8">' . $content);
		$xpath = new DomXPath($dom);

		// collect header names
		$headerNames = ['own_id', 'start_time', 'race_no', 'length', 'entry_count', 'remark']; //,'race_class','title','result','result_table'];

		/*
	foreach ($xpath->query('//tr[@class="dbitem"]//td') as $node) {
			$headerNames[] = $node->nodeValue;
	}*/

		//print_r($headerNames);
		// collect data

		//   /html/body/table/tbody/tr[3]

		//*[@id="oddsField"]/table/tbody/tr[1]
		//print_r($xpath);
		//*[@id="contents"]/form/div[2]
		//*[@id="contents"]/form/div[2]/table/tbody
		//$tbody = $xpath->query('//div[@class="raceState"]');
		//*[@id="contents"]/form/div[2]
		$tbody = $xpath->query('//div[@class="tableType2"]/table/tbody');

		//print_r($tbody[0]);
		foreach ($xpath->query('tr', $tbody[0]) as $index => $node) {
			$rowData = array();
			$rowData[] = $place_own_id;
			//$rowData[] = $rk_race_code;
			//$rowData[] = ''.($index+1);

			foreach ($xpath->query('td', $node) as $cell) {
				$rowData[] = trim($cell->nodeValue);
			}

			//print_r($rowData);
			$rowData = array_slice($rowData, 0, 10);
			$rowData[2] = str_replace('/', '-', substr($rowData[2], 0, 10)) . ' ' . substr($rowData[8], 0, 5);
			$rowData[5] = substr($rowData[5], 0, -1);
			$rowData[6] = substr($rowData[6], 0, -3);
			unset($rowData[1]);
			unset($rowData[4]);
			unset($rowData[7]);
			unset($rowData[8]);
			//print_r($rowData);
			//unset($rowData[4]);
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
		//$result->free(); //메모리해제
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
	//$get_date = '20160327';
	//if ($_POST['date']){
	//	$get_date = $_POST['date'];
	//}

	//$url = 'http://keiba.rakuten.co.jp/race_card/list/RACEID/201603222218180200';

	//echo $date_url;
	//$dom = new DomDocument;
	//$xpath = new DomXPath($dom);
	//a/@href
	//*[@id="raceMenu"]
	$alinks = [
		'http://race.kra.co.kr/chulmainfo/ChulmaDetailInfoList.do?Act=02&Sub=1&meet=1',
		'http://race.kra.co.kr/chulmainfo/ChulmaDetailInfoList.do?Act=02&Sub=1&meet=2',
		'http://race.kra.co.kr/chulmainfo/ChulmaDetailInfoList.do?Act=02&Sub=1&meet=3'
	];
	//print_r($alinks[0]->value);

	$data = array();
	foreach ($alinks as $alink) {
		get_race_data_to_json($alink);
	}
	//$url = 'http://race.kra.co.kr/chulmainfo/ChulmaDetailInfoList.do?Act=02&Sub=1&meet=1';
	//get_race_data_to_json($url);

	echo json_encode($data, JSON_UNESCAPED_UNICODE);

	foreach ($data as $i => $r) {
		$sql = "INSERT INTO `race` (`place_id`, `start_time`, `race_no`, `race_length`, `entry_count` ,`remark`) SELECT (select `id` from `place` where `own_id`=" . $r['own_id'] .  "),'"  . $r['start_time'] .  "',"  . $r['race_no'] .  "," . $r['length'] .  "," . $r['entry_count'] . ",'" . $r['remark'] .  "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race` WHERE `place_id`= (select `id` from `place` where `own_id`=" . $r['own_id'] .  ") and  `race_no` = " . $r['race_no']   .  " and  date(`start_time`) = date('" . $r['start_time'] .  "') )";
		//if ($r['own_id'] === '102'){ echo $sql;}
		echo $sql;
		$ok = insert_sql($sql);
		echo $ok;
	}


	?>

</body>

</html>