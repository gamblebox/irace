<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
define('MAX_FILE_SIZE', 6000000);
putenv("PHANTOMJS_EXECUTABLE=/usr/bin/phantomjs");
require __DIR__ . "/../../../vendor/autoload.php";

use Browser\Casper;
use Sunra\PhpSimple\HtmlDomParser;

echo date("Y-m-d H:i:s") . PHP_EOL;

function select_sql($sql)
{
	include __DIR__ . '/../../../application/configs/configdb.php';

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

function get_race_data_to_json($casper, $url)
{
	echo date("Y-m-d H:i:s") . ' get func' . PHP_EOL;
	global $ref_data;
	$exp = explode('/', $url);
	$start_date = $exp[2];
	$place_name = $exp[3];

	$association_code = 'os' . strtolower($exp[5]);

	if ($association_code == 'osh') {
		// return;
	}

	$place_code = $association_code . '_' . $exp[4];
	$race_no = $exp[6];

	// $casper = new Casper();
	// $casper->setOptions(array(
	// 	'ignore-ssl-errors' => 'yes',
	// 	'loadImages' => 'false',
	// ));
	$casper->start('https://www.tab.com.au' . $url);
	$casper->waitForText('</noscript>', 1000);
	$casper->run();

	echo date("Y-m-d H:i:s") . 'get func run' . PHP_EOL;

	$html = $casper->getHtml();
	$html = explode('Last Updated', $html)[0];
	$dom = HtmlDomParser::str_get_html($html);
	// $place_e_name = $dom->find('div[data-id="meeting-name"]', 0)->plaintext;
	$place_e_name = $dom->find('div.meeting-info-description', 0)->plaintext;

	$entry_count = count($dom->find('div.pseudo-body div.row'));
	$length = trim(str_replace('m', '', $dom->find('li[ng-if="race.raceDistance"]', 0)->plaintext));
	// $time = trim($dom->find('a[class="race-link selected"] time', 0)->plaintext);
	$time = trim($dom->find('div[data-test-race-starttime=""]', 0)->plaintext);
	$start_time = $start_date . ' ' . $time;
	if (substr($time, 0, 2) >= 0 && substr($time, 0, 2) < 3) {
		$start_time = date('Y-m-d H:i', strtotime(date($start_time) . '+' . '1' . ' days'));
	} else if (substr($time, 0, 2) > 2 && substr($time, 0, 2) < 6) {
		return false;
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
	];
	$data = array_combine($headerNames, $rowData);
	$ref_data = $data;
	print_r($data);

	$sql = "SELECT id FROM place WHERE place_code = '" . $place_code . "';";
	$place_id = select_sql($sql);
	$place_id = $place_id[0]->id;

	echo 'place_id:' . $place_id . PHP_EOL;

	if (!$place_id) {
		switch ($association_code) {
			case 'osr':
				$association_id = 5;
				break;
			case 'osh':
				$association_id = 6;
				break;
			case 'osg':
				$association_id = 7;
				break;
		}

		$sql = "insert into place (place_code, name, e_name, association_id) values ('" . $place_code . "','" . $place_e_name . "','" . $place_e_name . "','" . $association_id . "');";
		echo $sql;
		$ok = insert_sql($sql);
		echo $i . ':' . $ok . PHP_EOL;

		$sql = "SELECT id FROM place WHERE place_code = '" . $place_code . "';";
		$place_id = select_sql($sql);
		$place_id = $place_id[0]->id;
		echo 'new_place_id:' . $place_id . PHP_EOL;
	}

	$sql = "INSERT INTO `race` (`place_id`, `place_name`, `association_code`, `place_code`, `race_no`, `start_date`, `start_time`, `race_length`, `entry_count`) SELECT '" . $place_id . "', '" . $data['place_name'] . "', '" . $data['association_code'] . "', '" . $data['place_code'] . "'," . $data['race_no'] . ",'" . $data['start_date'] . "','" . $data['start_time'] . "'," . $data['length'] . "," . $data['entry_count'] . " FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race` WHERE `place_id` = '" . $place_id . "' and `place_code` = '" . $data['place_code'] . "' and `start_date` = '" . $data['start_date'] . "' and `race_no` = " . $data['race_no'] . ")";
	echo $sql;
	$ok = insert_sql($sql);
	echo $i . ':' . $ok . PHP_EOL;
}

$output = shell_exec('rm -r /tmp/phantomjs_cache_auto_regi_os');
echo "<pre>$output</pre>";

$ref_data = array();
$date_urls = array('https://www.tab.com.au/racing/meetings/today/R', 'https://www.tab.com.au/racing/meetings/today/H', 'https://www.tab.com.au/racing/meetings/today/G', 'https://www.tab.com.au/racing/meetings/tomorrow');
// $date_urls = array('https://www.tab.com.au/racing/meetings/today/R', 'https://www.tab.com.au/racing/meetings/today/G', 'https://www.tab.com.au/racing/meetings/tomorrow');
$date_url = 'https://www.tab.com.au/racing/meetings/today/R';

$casper = new Casper();
$casper->setOptions(array(
	'ignore-ssl-errors' => 'yes',
	'loadImages' => 'false',
	'disk-cache' => 'true',
	'disk-cache-path' => '/tmp/phantomjs_cache_auto_regi_os'
));
$casper->setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36');

$data = array();
foreach ($date_urls as $key => $value) {
	echo $value . PHP_EOL;
	$casper->start($value);
	$casper->waitForText('</noscript>', 1000);
	$casper->run();
	echo date("Y-m-d H:i:s") . ' date link run' . PHP_EOL;
	$html = $casper->getHtml();
	$dom = HtmlDomParser::str_get_html($html);
	$alinks = $dom->find("div.race-card-row a");

	foreach ($alinks as $alink) {
		echo $alink->href . PHP_EOL;
		if (count(explode('/', $alink->href)) === 7) {
			get_race_data_to_json($casper, $alink->href);
		}
	}
}
exit;
//echo json_encode($data, JSON_UNESCAPED_UNICODE);
foreach ($data as $i => $r) {
	$sql = "INSERT INTO `race` (`place_id`, `place_name`, `association_code`, `place_code`, `race_no`, `start_date`, `start_time`, `race_length`, `entry_count`) SELECT (select `id` from `place` where `place_code`= '" . $r['place_code'] . "'), '" . $r['place_name'] . "', '" . $r['association_code'] . "', '" . $r['place_code'] . "'," . $r['race_no'] . ",'" . $r['start_date'] . "','" . $r['start_time'] . "'," . $r['length'] . "," . $r['entry_count'] . " FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race` WHERE `place_id`= (select `id` from `place` where `place_code`= '" . $r['place_code'] . "') and `place_name` = '" . $r['place_name'] . "' and `place_code` = '" . $r['place_code'] . "' and `start_date` = '" . $r['start_date'] . "' and `race_no` = " . $r['race_no'] . ")";
	echo $sql;
	$ok = insert_sql($sql);
	echo $i . ':' . $ok . PHP_EOL;
}
