<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header("Content-Type:application/json");
header("Content-Type:text/html;charset=UTF-8");
putenv("PHANTOMJS_EXECUTABLE=/usr/local/bin/phantomjs");
require __DIR__ . "/../../../vendor/autoload.php";
//require ( __DIR__ . "/../../../vendor/autoload.php");
//include_once( __DIR__ . '/../../application/configs/configdb.php' );
use Browser\Casper;
use Sunra\PhpSimple\HtmlDomParser;
//include('simple_html_dom.php');
echo date("Y-m-d H:i:s") . PHP_EOL;

function insert_qe_odds($html, $race_id, $type, $ktype)
{
	$race_id_type = $race_id . '_' . $type;
	$dom = HtmlDomParser::str_get_html($html);
	$elems = $dom->find("div.pseudo-body div.ng-scope");

	$data = array();
	foreach ($elems as $e) {
		$c = $e->find("div.approximate-combinations");
		$r = $e->find("div.approximate-dividend");
		$data[] = array(str_replace(" ", "", $c[0]->plaintext), str_replace(array("$", " "), "", $r[0]->plaintext));
	}
	//echo json_encode($data, JSON_UNESCAPED_UNICODE);
	//$sql = "REPLACE INTO `login_ip_info` (`login_ip`, `islogin`, `user_id`, `broad_srv`)  VALUES ('" . $login_ip . "', now(), '" . $user_id . "', '" . $broad_srv ."')";
	if (count($data)) {
		$sql = "REPLACE INTO `os_odds` (`race_id_type`,`race_id`, `type`, `data`)  VALUES ('" . $race_id_type . "','" . $race_id . "','" . $ktype . "','" . json_encode($data, JSON_UNESCAPED_UNICODE) . "')";
		echo $sql . PHP_EOL;
		$ok = insert_sql($sql);
		echo $ok . PHP_EOL;
	}
}

function insert_wp_odds($html, $race_id)
{

	$dom = HtmlDomParser::str_get_html($html);
	$trs = $dom->find("div.pseudo-body div.row");
	$data_dan = array();
	$data_yun = array();
	foreach ($trs as $key => $value) {
		$entry_no = trim($value->find('div.number-cell', 0)->plaintext);
		$dan_ratio = $value->find('div[data-id="fixed-odds-price"] div.animate-odd', 0)->plaintext;
		$yun_ratio = $value->find('div[data-id="fixed-odds-place-price"] div.animate-odd', 0)->plaintext;
		$data_dan[] = array($entry_no, $dan_ratio);
		$data_yun[] = array($entry_no, $yun_ratio);
	}

	//echo json_encode($data_dan, JSON_UNESCAPED_UNICODE);
	if (count($data_dan)) {
		$race_id_type = $race_id . '_w';
		$sql = "REPLACE INTO `os_odds` (`race_id_type`,`race_id`, `type`, `data`)  VALUES ('" . $race_id_type . "','" . $race_id . "','단승','" . json_encode($data_dan, JSON_UNESCAPED_UNICODE) . "')";
		echo $sql . PHP_EOL;
		$ok = insert_sql($sql);
		echo $ok . PHP_EOL;
	}
	//echo json_encode($data_yun, JSON_UNESCAPED_UNICODE);
	if (count($data_yun)) {
		$race_id_type = $race_id . '_p';
		$sql = "REPLACE INTO `os_odds` (`race_id_type`,`race_id`, `type`, `data`)  VALUES ('" . $race_id_type . "','" . $race_id . "','연승','" . json_encode($data_dan, JSON_UNESCAPED_UNICODE) . "')";
		echo $sql . PHP_EOL;
		$ok = insert_sql($sql);
		echo $ok . PHP_EOL;
	}
}

function get_race_data_to_json($url, $race_id, $place_code, $race_no)
{
	echo date("Y-m-d H:i:s") . 'get func' . PHP_EOL;
	global $data;

	$casper = new Casper();
	// May need to set more options due to ssl issues
	$casper->setOptions(array(
		'ignore-ssl-errors' => 'yes',
		'loadImages' => 'false',
	));
	$casper->start($url);
	$casper->waitForText('</noscript>', 1000);
	$casper->run();
	$wp_html = $casper->getHtml();
	$dom = HtmlDomParser::str_get_html($wp_html);
	$stat = $dom->find('div.race-info-wrapper li.status-text', 0)->plaintext;
	echo $stat;
	if ($stat == 'Abandoned' || $stat == 'Closed' || $stat == 'All Paying' || $stat == 'Interim result') {
		return;
	}

	$casper->start($url . '/Quinella');
	$casper->waitForText('</noscript>', 1000);
	$casper->click("button.toggle-flucs-button.different-button.button-inactive");
	$casper->waitForText('Quinellas Available', 1000);
	$casper->run();
	$q_html = $casper->getHtml();
	$casper->start($url . '/Exacta');
	$casper->waitForText('</noscript>', 1000);
	$casper->click("button.toggle-flucs-button.different-button.button-inactive");
	$casper->waitForText('Exactas Available', 1000);
	$casper->run();
	$e_html = $casper->getHtml();
	// $casper->start($url . '/Trifecta');
	// $casper->waitForText('</noscript>', 1000);
	// $casper->click("button.toggle-flucs-button.different-button.button-inactive");
	// $casper->waitForText('Available', 1000);
	// $casper->run();
	// $t_html = $casper->getHtml();

	echo date("Y-m-d H:i:s") . 'get func run' . PHP_EOL;
	insert_wp_odds($wp_html, $race_id);
	insert_qe_odds($q_html, $race_id, 'q', '복승');
	insert_qe_odds($e_html, $race_id, 'e', '쌍승');
	//insert_odds($t_html, $race_id, 't', '삼쌍승');
}

function select_sql($sql)
{
	include __DIR__ . '/../../../application/configs/configdb.php';
	// $host = '127.0.0.1';
	// $user = 'aus';
	// $password = 'hellodhtm^^';
	// $dbname = 'goldrace';

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
	include __DIR__ . '/../../../application/configs/configdb.php';
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

//$date_url = 'https://www.tab.com.au/racing/meetings/tomorrow';
//$date_url = array('https://www.tab.com.au/racing/meetings/today/R', 'https://www.tab.com.au/racing/meetings/today/H', 'https://www.tab.com.au/racing/meetings/today/G', 'https://www.tab.com.au/racing/meetings/tomorrow');
//$date_url = 'https://www.tab.com.au/racing/meetings/today/R';

$sql = "SELECT * FROM race WHERE start_time < date_add(now(), INTERVAL 1 HOUR) and association_code in ('osr','osh','osg') and stat = 'P' order by start_time asc;";
$race = select_sql($sql);
//print_r($race);

foreach ($race as $v) {
	$url = 'https://www.tab.com.au/racing/' . $v->start_date . '/' . $v->place_name . '/' . str_replace($v->association_code . '_', '', $v->place_code) . '/' . strtoupper(str_replace('os', '', $v->association_code)) . '/' . $v->race_no;
	echo $url . PHP_EOL;
	get_race_data_to_json($url, $v->id, $v->place_code, $v->race_no);
}
//exit;

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
/*foreach ($data as $i => $r) {

$sql = "INSERT INTO `race` (`place_id`, `place_name`, `association_code`, `place_code`, `race_no`, `start_time`, `race_length`, `entry_count`) SELECT (select `id` from `place` where `place_code`= '" . $r['place_code'] . "'), '" . $r['place_name'] . "', '" . $r['association_code'] . "', '" . $r['place_code'] . "'," . $r['race_no'] . ",'" . $r['start_time'] . "'," . $r['length'] . "," . $r['entry_count'] . " FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race` WHERE `place_id`= (select `id` from `place` where `place_code`= '" . $r['place_code'] . "') and `place_name` = '" . $r['place_name'] . "' and `place_code` = '" . $r['place_code'] . "' and `race_no` = " . $r['race_no'] . ")";
echo $sql;
$ok = insert_sql($sql);
echo $i . ':' . $ok . PHP_EOL;
}*/
// print_r($sql);
