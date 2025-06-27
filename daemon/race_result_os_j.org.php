<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$log_filename = __DIR__ . '/../../rros_error.log';

function push_log($log_str)
{
	global $log_filename;
	$now = date('Y-m-d H:i:s');
	$filep = fopen($log_filename, "a");
	if (!$filep) {
		die("can't open log file : " . $log_filename);
	}
	fputs($filep, "{$now} : {$log_str}" . PHP_EOL);
	fclose($filep);
}

echo date("Y-m-d H:i:s") . PHP_EOL;

$race_day = date("Y-m-d");
echo '$race_day ' . $race_day . PHP_EOL;
$now_time = date("H");
echo '$now_time ' . $now_time . PHP_EOL;
if ($now_time < 6) {
	$race_day = date("Y-m-d", strtotime($race_day . " -1 day"));
}
echo '$race_day ' . $race_day . PHP_EOL;

function get_race_data_to_json($race)
{
	echo date("Y-m-d H:i:s") . ' start ' . PHP_EOL;

	$place_code = $race->place_code;
	$race_no = $race->race_no;
	$association_code = $race->association_code;
	$race_id = $race->id;
	$sql = "SELECT name from place where place_code = '" . $place_code . "'";
	$place = select_sql($sql);

	// global $data;
	// get_race_data_to_json($value, $casper, $url, $v->id, $v->place_code, $v->race_no, $v->association_code);

	// //https://api.beta.tab.com.au/v1/tab-info-service/racing/dates/2019-07-31/meetings/R/SAL/races/5?jurisdiction=VIC
	$url = 'https://api.beta.tab.com.au/v1/tab-info-service/racing/dates/' . $race->start_date . '/meetings/' . strtoupper(str_replace('os', '', $race->association_code)) . '/' . str_replace($race->association_code . '_', '', $race->place_code) . '/races/' . $race->race_no . '?jurisdiction=NSW';
	echo $url . PHP_EOL;

	$sucess = FALSE;
	for ($i = 0; $i < 3; $i++) {
		$file = file_get_contents($url);
		if ($file === FALSE) {
			echo 'file read error' . PHP_EOL;
			continue;
		}
		$json = json_decode($file);
		if (!$json) {
			echo 'json decoding error' . PHP_EOL;
			continue;
		} else {
			$sucess = TRUE;
			break;
		}
	}
	if (!$sucess) return FALSE;

	// Check Status
	$stat = $json->raceStatus;
	echo '$stat ' . $stat . PHP_EOL;

	if ($stat == 'Abandoned') {
		$entry_no = 0;
		$type = '경주취소';
		$old_start_time = date("Y-m-d");
		$new_start_time = date("Y-m-d");

		$memo = $place[0]->name . " " . $race_no . "경주: 경주취소";

		$sql = "INSERT INTO `race_change_info` (`association_code`,`race_id`, `entry_no`, `type`, `memo`, `old_start_time`, `new_start_time`) SELECT '" . $association_code . "'," . $race_id . "," . $entry_no . ", '" . $type . "' , '" . $memo . "','" . $old_start_time . "','" . $new_start_time . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race_change_info` WHERE `race_id`= " . $race_id . " and  `entry_no` = " . $entry_no . " and  `type` = '" . $type . "' and  `memo` =  '" . $memo . "' and  `old_start_time` = '" . $old_start_time . "' and `new_start_time` = '" . $new_start_time . "')";
		echo $sql;

		$ok = insert_sql($sql);
		echo $ok;
		return true;
	} else if ($stat != 'Paying') {
		return false;
	}

	$place = array();
	$place1 = '';
	$place2 = '';
	$place3 = '';
	$place3 = '';
	$dan = array();
	$yun = array();
	$bok = array();
	$ssang = array();
	$samssang = array();
	$sambok = array();
	$bokyun = array();
	$sassang = array();

	foreach ($json->dividends as $value) {
		echo $value->wageringProduct . PHP_EOL;

		switch ($value->wageringProduct) {
			case 'Win':
				foreach ($value->poolDividends as $entry) {
					$dan[] = array(
						$entry->selections[0],
						$entry->amount
					);
				}
				break;
			case 'Place':
				foreach ($value->poolDividends as $entry) {
					$yun[] = array(
						$entry->selections[0],
						$entry->amount
					);
				}
				break;
			case 'Quinella':
				foreach ($value->poolDividends as $entry) {
					$select_no = $entry->selections;
					sort($select_no, SORT_NUMERIC);
					$bok[] = array(
						implode('-', $select_no),
						$entry->amount
					);
				}
				break;
			case 'Exacta':
				foreach ($value->poolDividends as $entry) {
					$ssang[] = array(
						implode('-', $entry->selections),
						$entry->amount
					);
				}
				break;
			case 'Trifecta':
				foreach ($value->poolDividends as $entry) {
					$samssang[] = array(
						implode('-', $entry->selections),
						$entry->amount
					);
				}
				break;
			case 'Trio':
				foreach ($value->poolDividends as $entry) {
					$select_no = $entry->selections;
					sort($select_no, SORT_NUMERIC);
					$sambok[] = array(
						implode('-', $select_no),
						$entry->amount
					);
				}
				break;
			case 'FirstFour':
				foreach ($value->poolDividends as $entry) {
					$sassang[] = array(
						implode('-', $entry->selections),
						$entry->amount
					);
				}
				break;
			default:;
				break;
		}
	}
	$p = 1;
	foreach ($json->results as $key => $value) {
		$i = 0;
		foreach ($value as $key => $entry) {
			$place[$p] .= ' ' . $entry;
			$i++;
		}
		$p += $i;
	}
	$place1 = trim($place[1]);
	$place2 = trim($place[2]);
	$place3 = trim($place[3]);
	$place4 = trim($place[4]);
	// 	print_r($place);
	// 	print_r($place1);
	// 	print_r($place2);
	// 	print_r($place3);
	// 	print_r($place4);
	// 	print_r($dan);
	// 	print_r($yun);
	// 	print_r($bok);
	// 	print_r($ssang);
	// 	print_r($samssang);
	// 	print_r($bokyun);
	// 	print_r($sassang);
	if (count($bok) < 1) {
		return false;
	}

	$rowData = array(
		$dan,
		$yun,
		$bok,
		$ssang,
		$bokyun,
		$sambok,
		$samssang
	); // , $sassang);
	$headerNames = [
		'단승',
		'연승',
		'복승',
		'쌍승',
		'복연승',
		'삼복승',
		'삼쌍승'
	]; // , '사쌍승'];

	$data = array_combine($headerNames, $rowData);

	echo date("Y-m-d H:i:s") . 'end' . PHP_EOL;
	// 	print_r($data);

	foreach ($data as $key => $d) {
		$type = $key;
		print_r($d);
		foreach ($d as $c) {
			print_r($c);
			$p = explode('-', $c[0]);
			$place_1 = $p[0];
			$p[1] === null ? $place_2 = 0 : $place_2 = $p[1];
			$p[2] === null ? $place_3 = 0 : $place_3 = $p[2];
			$b = $c[1];
			if ($b > 100) {
				$b = 100;
			}
			// echo $type . $place_1 . $place_2 .$place_3;
			// $sql = 'INSERT INTO `result` (`race_id`, `type`, `place_1`, `place_2`, `place_3`, `odds`) VALUES (' . $race_id . ', \'' . $type . '\' , ' . $place_1 . ',' . $place_2 . ',' . $place_3 . ',' . $b . ')';
			$sql = 'INSERT INTO `result` (`race_id`, `type`, `place_1`, `place_2`, `place_3`, `odds`) SELECT ' . $race_id . ', \'' . $type . '\' , ' . $place_1 . ',' . $place_2 . ',' . $place_3 . ',' . $b . ' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `result` WHERE `race_id`= ' . $race_id . ' and  `type` = \'' . $type . '\' and  `place_1` = ' . $place_1 . ' and  `place_2` = ' . $place_2 . ' and `place_3` = ' . $place_3 . ')';
			echo $sql;
			// print_r($sql);
			// echo '<br>';

			$ok = insert_sql($sql);
		}
		echo $ok . PHP_EOL;
	}

	$place1 = trim($place1);
	$place2 = trim($place2);
	$place3 = trim($place3);
	$oe = 0;
	$p1 = explode(' ', $place1);
	print_r($p1);

	$oe += array_sum($p1);

	// $p1c = count($p1);
	sort($p1, SORT_NUMERIC);
	$p1 = implode(' ', $p1);
	echo 'race_id=' . $race_id;
	echo 'p1=' . $p1;
	// 	echo 'p1c=' . $p1c;

	if ($place2) {
		$p2 = explode(' ', $place2);
		$oe += array_sum($p2);
		// $p2c = count($p2);
		sort($p2, SORT_NUMERIC);
		$p2 = implode(' ', $p2);
	} else {
		$p2 = '';
	}

	echo 'p2=' . $p2;
	// 	echo 'p2c=' . $p2c;
	if ($place3) {
		$p3 = explode(' ', $place3);
		$oe += array_sum($p3);
		// $p3c = count($p3);
		sort($p3, SORT_NUMERIC);
		$p3 = implode(' ', $p3);
	} else {
		$p3 = '';
	}

	echo 'p3=' . $p3;
	echo '$oe=' . $oe;

	$oe %= 2;
	if ($oe === 0) {
		$oe += 2;
	}

	$sql = "INSERT INTO `result` (`race_id`, `type`, `place_1`, `place_2`, `place_3`, `odds`) SELECT " . $race_id . ", '홀짝' , " . $oe . ",0,0,0 FROM DUAL WHERE NOT EXISTS (SELECT * FROM `result` WHERE `race_id`= " . $race_id . " and  `type` = '홀짝' and  `place_1` = " . $oe . " and  `place_2` = 0 and `place_3` = 0)";
	// print_r($sql);
	// echo '<br>';
	$ok = insert_sql($sql);

	$sql = "SELECT * FROM `view_place_result` WHERE race_id = " . $race_id;
	$results = select_sql($sql);

	$odds_dan = $results[0]->dan;
	$odds_yun = $results[0]->yun;
	$odds_bok = $results[0]->bok;
	$odds_ssang = $results[0]->ssang;
	// $odds_bokyun = $results[0]->bokyun;
	$odds_bokyun = '';
	$odds_sambok = $results[0]->sambok;
	$odds_samssang = $results[0]->samssang;
	/*
	 * echo '<br>$odds_dan' . $odds_dan;
	 * echo '<br>$odds_yun' . $odds_yun;
	 * echo '<br>$odds_bok' . $odds_bok;
	 * echo '<br>$odds_ssang' . $odds_ssang;
	 * echo '<br>$odds_bokyun' . $odds_bokyun;
	 * echo '<br>$odds_sambok' . $odds_sambok;
	 */

	$sql = "select group_concat(DISTINCT `type`,':',`result` separator ' ') as `odds_all` from `view_result` where race_id = " . $race_id;
	$results = select_sql($sql);
	$odds_all = $results[0]->odds_all;

	$sql = "UPDATE `race` SET `stat`='E', place_1 = '" . $p1 . "', place_2 = '" . $p2 . "', place_3 = '" . $p3 . "', place_oe = '" . $oe . "', odds_dan = '" . $odds_dan . "', odds_yun = '" . $odds_yun . "', odds_bok = '" . $odds_bok . "', odds_ssang = '" . $odds_ssang . "', odds_bokyun = '" . $odds_bokyun . "',   odds_sambok = '" . $odds_sambok . "',   odds_samssang = '" . $odds_samssang . "',   odds_all = '" . $odds_all . "' WHERE stat = 'P' and `id`= " . $race_id;
	print_r($sql);
	$ok = insert_sql($sql);
	echo $ok . PHP_EOL;
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
			// print_r( $row->id);
			$v[] = $row;
		}
	} else {
		$v = array(
			0 => 'empty'
		);
	}
	return $v;

	$result->free(); // 메모리해제
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

// $sql = "SELECT * FROM race WHERE race.start_time < now() and race.start_time > date_add(date(now()),INTERVAL -20 HOUR) and race.association_code in ('osr','osh','osg') and stat = 'P' order by start_time asc;";
$sql = "SELECT * FROM race WHERE race.start_time < now() and race.start_date = '" . $race_day . "' and race.association_code in ('osr','osh','osg') and stat = 'P' order by start_time asc;";
$race = select_sql($sql);
// print_r($race);
if ($race) {
	foreach ($race as $value) {
		get_race_data_to_json($value);
		sleep(10);
	}
} else {
	echo 'Nothing to do...' . PHP_EOL;
}
