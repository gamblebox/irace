<?php

?>
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
		/*
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
*/
		$dom = new DomDocument;

		//$dom->loadHtml('<?xml encoding="UTF-8">' . $content);
		$dom->loadHtmlFile($url);
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
		//*[@id="content"]/div[5]/div/p[1]/strong
		$date_element = $xpath->query('//*[@id="content"]/div[5]/div/p[1]/strong');
		//print_r($date_element[0]);
		$date = trim($date_element[0]->nodeValue);
		//print_r($date);
		preg_match_all('/[0-9,-]+/', $date, $arr);
		//print_r($arr);
		$date = $arr[0][0] . '-' . $arr[0][1] . '-' . $arr[0][2];
		//$date = substr($date,0,4) . '-' . substr($date,11,2) . '-' . substr($date,20,2);
		//print_r($date);
		//$tbody = $xpath->query('//div[@id="printPop"]');

		//print_r($tbody[0]);
		//*[@id="contentForm"]/div[3]/p[1]/strong

		//print_r($xpath->query('//*[@class="titPlayChart"]'));
		//titPlayChart
		//*[@id="content"]/h4[1]
		//*[@id="printPop"]/h4[1]
		//*[@id="content"]/h4[16]
		foreach ($xpath->query('//*[@id="content"]/*[@class="titPlayChart"]') as $index => $node) {
			//*[@id="printPop"]/table[1]/tbody/tr[7]
			//*[@id="printPop"]/table[1]/tbody/tr[1]
			//*[@id="content"]/table[1]/tbody/tr[1]/td[1]
			$entry = $xpath->query('//*[@id="content"]/table[1]/tr');

			//print_r($entry);
			//print_r($node->nodeValue);
			$rowData = array();
			//$rowData[] = $place_own_id;
			//$rowData[] = $rk_race_code;
			//$rowData[] = ''.($index+1);
			$str = trim($node->nodeValue);
			//$place = substr($str, 0, 6);
			print_r($str);
			$rowData[] = 211;
			//print_r($date);
			$rowData[] = $date . ' ' . substr($str, -5, 5);
			//print_r(substr($str,-12, 5));
			//$rowData[] = substr($str, 4, 2);
			preg_match_all('/[0-9,-]+/', $str, $arr);
			print_r($arr);
			$rowData[] = $arr[0][0];
			$rowData[] = $arr[0][2];
			$rowData[] = $entry->length;


			//foreach ($xpath->query('td', $node) as $cell) {
			//		$rowData[] = trim($cell->nodeValue);
			// }

			//print_r($rowData);
			//			$rowData= array_slice($rowData,0,9);
			//			$rowData[2] = substr($rowData[2], 0, 10) . ' ' . substr($rowData[8], 0, 5);
			//			$rowData[5] = substr($rowData[5], 0, -1);
			//			$rowData[6] = substr($rowData[6], 0, -3);
			//			unset($rowData[1]);
			//			unset($rowData[4]);
			//			unset($rowData[7]);			
			//			unset($rowData[8]);
			//print_r($rowData);
			//unset($rowData[4]);
			print_r($rowData);
			$data[] = array_combine($headerNames, $rowData);
		}

		//print_r($data);
		//echo json_encode($data);
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


	$alinks = ['http://www.kboat.or.kr/contents/information/fixedChuljuPage.do'];
	//print_r($alinks[0]->value);


	$data = array();
	foreach ($alinks as $alink) {
		get_race_data_to_json($alink);
	}

	echo json_encode($data, JSON_UNESCAPED_UNICODE);

	foreach ($data as $i => $r) {
		$sql = "INSERT INTO `race` (`place_id`, `start_time`, `race_no`, `race_length`, `entry_count`) SELECT (select `id` from `place` where `own_id`=" . $r['own_id'] .  "),'"  . $r['start_time'] .  "',"  . $r['race_no'] .  "," . $r['length'] .  "," . $r['entry_count'] .  " FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race` WHERE `place_id`= (select `id` from `place` where `own_id`=" . $r['own_id'] .  ") and  `race_no` = " . $r['race_no']   .  " and  date(`start_time`) = date('" . $r['start_time'] .  "') )";
		$ok = insert_sql($sql);
		echo $ok;
	}

	?>

</body>

</html>