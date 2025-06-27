<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
define('MAX_FILE_SIZE', 6000000);
putenv("PHANTOMJS_EXECUTABLE=/usr/local/bin/phantomjs");
// require __DIR__ . "/../../../vendor/autoload.php";
require "/srv/krace/vendor/autoload.php";

use Browser\Casper;
// use Sunra\PhpSimple\HtmlDomParser;
use voku\helper\HtmlDomParser;

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

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime($today . '+' . '1' . ' days'));

$place_code_arr = array(
	'函館' => 'jc_11',
	'青森' => 'jc_12',
	'いわき平' => 'jc_13',
	'弥彦' => 'jc_21',
	'前橋' => 'jc_22',
	'取手' => 'jc_23',
	'宇都宮' => 'jc_24',
	'大宮' => 'jc_25',
	'西武園' => 'jc_26',
	'京王閣' => 'jc_27',
	'立川' => 'jc_28',
	'松戸' => 'jc_31',
	'千葉' => 'jc_32',
	'川崎' => 'jc_34',
	'平塚' => 'jc_35',
	'小田原' => 'jc_36',
	'伊東' => 'jc_37',
	'静岡' => 'jc_38',
	'名古屋' => 'jc_42',
	'岐阜' => 'jc_43',
	'大垣' => 'jc_44',
	'豊橋' => 'jc_45',
	'富山' => 'jc_46',
	'松阪' => 'jc_47',
	'四日市' => 'jc_48',
	'福井' => 'jc_51',
	'奈良' => 'jc_53',
	'向日町' => 'jc_54',
	'和歌山' => 'jc_55',
	'岸和田' => 'jc_56',
	'玉野' => 'jc_61',
	'広島' => 'jc_62',
	'防府' => 'jc_63',
	'高松' => 'jc_71',
	'小松島' => 'jc_73',
	'高知' => 'jc_74',
	'松山' => 'jc_75',
	'小倉' => 'jc_81',
	'久留米' => 'jc_83',
	'武雄' => 'jc_84',
	'佐世保' => 'jc_85',
	'別府' => 'jc_86',
	'熊本' => 'jc_87'
);

$headerNames = [
	'place_code',
	'race_no',
	'start_time',
	'entry_count'
];

// $output = shell_exec('rm -r /tmp/phantomjs_cache_auto_regi_os');
// echo "<pre>$output</pre>";

$ref_data = array();
$date_urls = array('https://www.tab.com.au/racing/meetings/today/R', 'https://www.tab.com.au/racing/meetings/today/H', 'https://www.tab.com.au/racing/meetings/today/G', 'https://www.tab.com.au/racing/meetings/tomorrow');
// $date_urls = array('https://www.tab.com.au/racing/meetings/today/R', 'https://www.tab.com.au/racing/meetings/today/G', 'https://www.tab.com.au/racing/meetings/tomorrow');
$date_url = 'http://keirin.jp/pc/top#';
$start_date = $today;

$casper = new Casper();
$casper->setOptions(array(
	'ignore-ssl-errors' => 'yes',
	'loadImages' => 'false',
	'disk-cache' => 'true',
	'disk-cache-path' => '/tmp/phantomjs_cache_auto_regi_os'
));
$casper->setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36');

//
$casper->start($date_url);
// $casper->click('#ctabTomorrow > a');

$casper->run();
$html = $casper->getHtml();
echo $html . PHP_EOL;
$dom = HtmlDomParser::str_get_html($html);
$month_day = $dom->find('#ctabToday > a', 0)->textContent;
// echo '$month_day=>' . $month_day . PHP_EOL;
// exit();
if ($month_day != '今日(' . date('m/d') . ')') {
	echo 'No today match !!!' . PHP_EOL;
	exit();
}
// $month_day = str_replace('今日');
$buttons = $dom->find('#kaisaiInfoTable > tbody tr td:nth-child(4) button');

#kaisaiInfoTable > tbody > tr.dokanto_color > td:nth-child(4) > button
// print_r(count($buttons));
// exit();
$data = array();

for ($i = 1; $i < count($buttons) + 1; $i++) {
	$casper->start($date_url);
	$casper->click('#ctabToday > a');
	$casper->wait(1000);
	$casper->click('#kaisaiInfoTable > tbody tr:nth-child(' . $i . ') td:nth-child(4) button');
	// $casper->click($button);
	$casper->wait(1000);
	$casper->run();
	$html = $casper->getHtml();
	$dom = HtmlDomParser::str_get_html($html);
	// $html = $dom->find('#sldivSyusouList');
	// echo '#sldivSyusouList' . $html . PHP_EOL;
	// $dom = HtmlDomParser::str_get_html($html);
	$place_name = str_replace(' ', '', $dom->findOne('#hhLblJo')->textContent);
	$place_name = str_replace('競輪場', '', $dom->findOne('#hhLblJo')->textContent);
	$place_code = $place_code_arr[$place_name];
	// print_r($place);
	// exit();
	echo '$place_code->' . $place_code . PHP_EOL;
	// exit();
	$tables = $dom->find('#sldivSyusouList > table');
	// print_r($tables);
	// exit();

	foreach ($tables as $key => $table) {
		$race_no = str_replace('R', '', $table->findOne('table:nth-child(1) > tbody > tr > td > div')->textContent);
		echo '$race_no->' . $race_no . PHP_EOL;
		// #sldivSyusouList > table.sltbl_02.slma-0 > tbody > tr > td > table:nth-child(2) > tbody > tr > td.slva-top > table > tbody > tr > td:nth-child(1) > div:nth-child(2) > span
		$start_time = $table->find('tbody > tr > td > table:nth-child(2) > tbody > tr > td > table > tbody > tr > td:nth-child(1) > div:nth-child(2) > span')[0]->textContent;
		$start_time = $today . ' ' . $start_time;
		#sldivSyusouList > table.sltbl_02.slma-0 > tbody > tr > td > table:nth-child(2) > tbody > tr > td.slva-top > table > tbody > tr > td:nth-child(1) > div:nth-child(2) > span
		echo '$start_time->' . $start_time . PHP_EOL;
		$entry_count = count($table->find('tbody > tr > td > table:nth-child(2) > tbody > tr > td > table > tbody > tr > td:nth-child(2) > table td')) / 9;
		print_r($entry_count);
		echo '$entry_count->' . $entry_count . PHP_EOL;
		$data[] = array('place_code' => $place_code, 'place_name' => $place_name, 'race_no' => $race_no, 'start_time' => $start_time, 'entry_count' => $entry_count);
		// exit();
	}


	// exit();
}
print_r($data);
// exit();

$association_code = 'jcycle';
$race_length = 0;
//echo json_encode($data, JSON_UNESCAPED_UNICODE);
foreach ($data as $i => $r) {
	$sql = "INSERT INTO `race` (`place_id`, `place_name`, `association_code`, `place_code`, `race_no`, `start_date`, `start_time`, `race_length`, `entry_count`) SELECT (select `id` from `place` where `place_code`= '" . $r['place_code'] . "'), '" . $r['place_name'] . "', '" . $association_code . "', '" . $r['place_code'] . "'," . $r['race_no'] . ",'" . $start_date . "','" . $r['start_time'] . "'," . $race_length . "," . $r['entry_count'] . " FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race` WHERE `place_id`= (select `id` from `place` where `place_code`= '" . $r['place_code'] . "') and `place_name` = '" . $r['place_name'] . "' and `place_code` = '" . $r['place_code'] . "' and `start_date` = '" . $start_date . "' and `race_no` = " . $r['race_no'] . ")";
	echo $sql;
	$ok = insert_sql($sql);
	echo $i . ':' . $ok . PHP_EOL;
}
