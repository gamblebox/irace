<?php

?>
<?php
header("Content-Type:application/json");
header("Content-Type:text/html;charset=UTF-8");

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
$url = 'http://www2.keiba.go.jp/KeibaWeb/TodayRaceInfo/RaceList?k_babaCode=31&k_raceDate=2016/03/13';
if ($_POST['url']){
	$url = $_POST['url'];
}
//echo $url;
$place_own_id = explode( 'babaCode=', $url);
$place_own_id = explode( '&k_raceDate', $place_own_id[1]);
$place_own_id = $place_own_id[0];
//echo $place_own_id;
$date = substr($url, strlen($url)-10, 10 );
$date = str_replace('/', '-', $date);
//echo $date;


$dom = new DomDocument;
$dom->loadHtmlFile($url);
//$dom->loadHtml('$result');

$xpath = new DomXPath($dom);

// collect header names
$headerNames = ['own_id', 'race_no', 'start_time', 'change', 'weather1','weather2','weather3','length','jong','entry_count'];//,'race_class','title','result','result_table'];

/*
foreach ($xpath->query('//tr[@class="dbitem"]//td') as $node) {
    $headerNames[] = $node->nodeValue;
}*/

//print_r($headerNames);
// collect data
$data = array();
//   /html/body/table/tbody/tr[3]
foreach ($xpath->query('//tr[@class="dbnote"]') as $node) {
    $rowData = array();
		$rowData[] = $place_own_id;
		//echo  '?';
    foreach ($xpath->query('td', $node) as $cell) {
				$rowData[] = trim($cell->nodeValue);
	   }
//print_r($rowData);
		$rowData= array_slice($rowData,0,10);
		//print_r($rowData);
    $data[] = array_combine($headerNames, $rowData);
}

for ($i=0; $i<count($data); $i++) {
	$data[$i][start_time] = $date . ' ' . $data[$i][start_time] . ':00';
	$data[$i][length] = preg_replace('/[^0-9]*/s','',$data[$i][length]);
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);


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

