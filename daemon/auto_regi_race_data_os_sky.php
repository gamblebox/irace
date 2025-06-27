<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header("Content-Type:application/json");
header("Content-Type:text/html;charset=UTF-8");
require __DIR__ . "/../../../vendor/autoload.php";

use Browser\Casper;
use Sunra\PhpSimple\HtmlDomParser;
//include('simple_html_dom.php');
echo date("Y-m-d H:i:s") . PHP_EOL;

function get_race_data_to_json($url)
{
	echo date("Y-m-d H:i:s") . 'get func' . PHP_EOL;
	global $data;
	$exp = explode('/', $url);
	$start_date = $exp[2];
	$place_name = $exp[3];

	$association_code = 'os' . strtolower($exp[5]);
	$place_code = $association_code . '_' . $exp[4];
	$race_no = $exp[6];

	$casper = new Casper();
	// May need to set more options due to ssl issues
	$casper->setOptions(array(
		'ignore-ssl-errors' => 'yes',
		'loadImages' => 'false',
	));
	$casper->start('https://www.tab.com.au' . $url);
	//$casper->wait(500);
	$casper->waitForText('</noscript>', 1000);
	//$output = $casper->getOutput();
	$casper->run();

	echo date("Y-m-d H:i:s") . 'get func run' . PHP_EOL;

	$html = $casper->getHtml();
	$dom = HtmlDomParser::str_get_html($html);
	$place_name = $dom->find('div[data-id="meeting-name"]', 0)->plaintext;
	$entry_count = count($dom->find('div.pseudo-body div.row'));
	$length = trim(str_replace('m', '', $dom->find('li[ng-if="race.raceDistance"]', 0)->plaintext));
	$start_time = $start_date . ' ' . trim($dom->find('a[class="race-link selected"] time', 0)->plaintext);
	if ($data[count($data) - 1]['place_code'] == $place_code) {
		if (strtotime(data[count($data) - 1]['start_time']) > strtotime($start_time)) {
			$start_time = date('Y-m-d H:i', strtotime(date($start_time) . '+' . '1' . ' days'));
		}
	}

	$rowData = array($association_code, $place_name, $place_code, $race_no, $start_date, $start_time, $length, $entry_count);
	// collect header names
	$headerNames = [
		'association_code',
		'place_name',
		'place_code',
		'race_no',
		'start_date',
		'start_time',
		'length',
		'entry_count',
	]; // ,'race_class','title','result','result_table'];
	// print_r($xpath);
	$data[] = array_combine($headerNames, $rowData);
	echo date("Y-m-d H:i:s") . 'end' . PHP_EOL;
	print_r($rowData);
}

function insert_sql($sql)
{
	include __DIR__ . '/../../../application/configs/configdb.php';
	// $host = 'k3.krace.fun';
	// $user = 'aus';
	// $password = 'hellodhtm^^';
	// $dbname = 'goldrace';
	$mysqli = new mysqli($host, $user, $password, $dbname); // 연결 오류 발생 시 스크립트 종료

	if ($mysqli->connect_errno) {
		die('Connect Error: ' . $mysqli->connect_error);
	}
	if ($mysqli->query($sql) === true) {
		return 'ok';
	} else {
		return $mysqli->error;
	}
	$result->free(); // 메모리해제
}

$date_url = 'http://www.skyracing.com.au/index.php?component=racing&task=todayraces&Itemid=88&id=18';
//$date_url2 = 'https://www.tab.com.au/racing/meetings/today/H';

$casper = new Casper();
// May need to set more options due to ssl issues
$casper->setOptions(array(
	'ignore-ssl-errors' => 'yes',
	'loadImages' => 'false',
));
// $casper->setOptions(array(
//     'loadImages' => 'false'
// ));
$casper->start($date_url);
$casper->waitForText('</html>', 1000);
//$casper->wait(500);
//$output = $casper->getOutput();
$casper->run();

echo date("Y-m-d H:i:s") . 'c run' . PHP_EOL;

$html = $casper->getHtml();

$dom = HtmlDomParser::str_get_html($html);
// $elems = $dom->find(".div.race-card-row a");

$trs = $dom->find('div#todayraces_wrapper tbody tr');

//echo $trs[0]->plaintext;
foreach ($trs as $key => $value) {
	$td = $value->find('td', 1);
	$a = $value->find('a', 0);
	echo $a->href . PHP_EOL;
	get_race_data_to_json($a->href);
}
exit;
echo date("Y-m-d H:i:s") . 'find' . PHP_EOL;

$data = array();
//get_race_data_to_json($alinks[7]->href);
foreach ($alinks as $alink) {
	//echo $alink->href;
	if (count(explode('/', $alink->href)) === 7) {
		get_race_data_to_json($alink->href);
	}
}
// print_r($data);
// $date = substr($url, -18, 8 );
// $date = preg_replace("/([\d]{4})([\d]{2})/", "$1-$2-$3", $get_date);
// for ($i = 0; $i < count($data); $i ++) {
//     $data[$i]['start_time'] = $date . ' ' . $data[$i]['start_time'] . ':00';
//     $data[$i]['length'] = preg_replace('/[^0-9]*/s', '', $data[$i]['length']);
// }
// echo json_encode($data, JSON_UNESCAPED_UNICODE);

// insert
// INSERT INTO `race` (`place_id`, `rk_race_code`, `race_no`, `start_time`, `race_length`, `entry_count`) SELECT (select `id` from `place` where `own_id`=23),200301,10,'2016-05-09 16:40:00',1400,8 FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race` WHERE `place_id`= (select `id` from `place` where `own_id`=23) and `race_no` = 10 and day(`start_time`) = day('2016-05-09 16:40:00') )
// echo PHP_EOL;
//echo json_encode($data, JSON_UNESCAPED_UNICODE);
foreach ($data as $i => $r) {
	//$sql = "INSERT INTO `race` (`place_id`, `association_code`, `rk_race_code`, `race_no`, `start_time`, `race_length`, `entry_count`) SELECT (select `id` from `place` where `own_id`=" . $r['own_id'] . "), 'japanrace', '" . $r['rk_race_code'] . "'," . $r['race_no'] . ",'" . $r['start_time'] . "'," . $r['length'] . "," . $r['entry_count'] . " FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race` WHERE `place_id`= (select `id` from `place` where `own_id`=" . $r['own_id'] . ") and  `race_no` = " . $r['race_no'] . " and  date(`start_time`) = date('" . $r['start_time'] . "') )";

	$sql = "INSERT INTO `race` (`place_id`, `place_name`, `association_code`, `place_code`, `race_no`, `start_date`, `start_time`, `race_length`, `entry_count`) SELECT (select `id` from `place` where `place_code`= '" . $r['place_code'] . "'), '" . $r['place_name'] . "', '" . $r['association_code'] . "', '" . $r['place_code'] . "'," . $r['race_no'] . ",'" . $r['start_date'] . "','" . $r['start_time'] . "'," . $r['length'] . "," . $r['entry_count'] . " FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race` WHERE `place_id`= (select `id` from `place` where `place_code`= '" . $r['place_code'] . "') and `place_name` = '" . $r['place_name'] . "' and `place_code` = '" . $r['place_code'] . "' and `start_date` = '" . $r['start_date'] . "' and `race_no` = " . $r['race_no'] . ")";
	echo $sql;
	$ok = insert_sql($sql);
	echo $i . ':' . $ok . PHP_EOL;
}
// print_r($sql);
