<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$alinks = ['http://www.kcycle.or.kr/contents/information/fixedChuljuPage.do'	];
//print_r($alinks[0]->value);


$data = array();
foreach ($alinks as $alink) {
	get_race_data_to_json($alink);
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);


function get_race_data_to_json($url){
	global $data;
	$cycle_own_id = ['광명'=>201, '창원'=>202, '부산'=>203];

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
	//echo $date;

	$dom = new DomDocument;

	$dom->loadHtml($content);

	$xpath = new DomXPath($dom);
	//print_r($xpath);
	// collect header names
	$headerNames = ['own_id', 'start_time', 'race_no' , 'length', 'entry_count'];//,'race_class','title','result','result_table'];
	
	/*
	foreach ($xpath->query('//tr[@class="dbitem"]//td') as $node) {
			$headerNames[] = $node->nodeValue;
	}*/
	
	//print_r($headerNames);
	
	// collect data
	//*[@id="printPop"]/h4[2]
	//*[@id="contentForm"]/div[3]/p[1]/strong/text()
	$date_element = $xpath->query('//*[@id="contentForm"]/div[3]/p[1]');
	$date = trim($date_element[0]->nodeValue);

	$date = substr($date,0,4) . '-' . substr($date,8,2) . '-' . substr($date,14,2);
	
	$tbody = $xpath->query('//div[@id="printPop"]');
	
	//print_r($date);
//*[@id="contentForm"]/div[3]/p[1]/strong
		
			
				
	foreach ($xpath->query('*[@class="titPlayChart"]', $tbody[0]) as $index=>$node) {
		//*[@id="printPop"]/table[1]/tbody/tr[7]
		//*[@id="printPop"]/table[1]/tbody/tr[1]
			$entry = $xpath->query('table[1]/tbody/tr',  $tbody[0]);
			
			print_r($entry->length);
			$rowData = array();
			//$rowData[] = $place_own_id;
			//$rowData[] = $rk_race_code;
			//$rowData[] = ''.($index+1);
			$str = trim($node->nodeValue);
			$place = substr($str, 0, 6);
			//print_r($cycle_own_id);
			$rowData[] = $cycle_own_id[$place];
			$rowData[] = $date . ' ' . substr($str, -5, 5);
			$rowData[] = substr($str, 11, 2);
			$rowData[] = substr($str, -19, 4);
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
			//print_r($rowData);
			$data[] = array_combine($headerNames, $rowData);
			
	}
	
//print_r($data);
echo json_encode($data);
}

//echo $data;
//---
/*$src = new DOMDocument('1.0', 'utf-8');
$src->formatOutput = true;
$src->preserveWhiteSpace = false;
$content = file_get_contents("http://www.nbs.rs/kursnaListaModul/srednjiKurs.faces?lang=lat");
@$src->loadHTML($content);
$xpath = new DOMXPath($src);
$values=$xpath->query('//td[ contains (@class, "tableCell") ]');
foreach($values as $value)
{
	echo $value->nodeValue."<br />";
}*/
//------------------------------



?>

