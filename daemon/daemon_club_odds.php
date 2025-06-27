<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
// error_reporting(E_ALL);

require __DIR__ . "/../../../vendor/autoload.php";

$log_filename = __DIR__ . '/../../daemon.error.log';

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

function insert_qe_odds($html, $race, $type, $ktype)
{
	$race_id = $race->id;
	$race_id_type = $race_id . '_' . $type;
	$dom = HtmlDomParser::str_get_html($html);
	$elems = $dom->find("div.pseudo-body div.row");
	echo '$elems->' . count($elems) . PHP_EOL;
	// echo '$elems->' . $elems[0]->plaintext . PHP_EOL;

	$data = array();
	foreach ($elems as $e) {
		// approximate-combinations approximate-dividend
		$c = $e->find("div.approximate-combinations");
		$r = $e->find("div.approximate-dividend");
		// echo '$c[0]->' . $c[0] . PHP_EOL;
		// echo '$r[0]->' . $r[0] . PHP_EOL;
		if ($r) {
			$data[] = array(
				str_replace(" ", "", $c[0]->plaintext),
				round(floor(str_replace(array(
					"$",
					" ",
					","
				), "", $r[0]->plaintext) * 10) / 10, 1)
			);
		}
	}
	// echo json_encode($data, JSON_UNESCAPED_UNICODE);
	// $sql = "REPLACE INTO `login_ip_info` (`login_ip`, `islogin`, `user_id`, `broad_srv`) VALUES ('" . $login_ip . "', now(), '" . $user_id . "', '" . $broad_srv ."')";
	if (count($data)) {
		$sql = "REPLACE INTO `os_odds` (`race_id_type`,`race_id`, `type`, `data`)  VALUES ('" . $race_id_type . "','" . $race_id . "','" . $ktype . "','" . json_encode($data, JSON_UNESCAPED_UNICODE) . "')";
		echo $sql . PHP_EOL;
		$ok = insert_sql($sql);
		echo $ok . PHP_EOL;
	}
}

function insert_wp_odds($html, $race)
{
	$dom = HtmlDomParser::str_get_html($html);
	$trs = $dom->find("div.pseudo-body div.row");
	$data_dan = array();
	$data_yun = array();
	$data_t_dan = array();
	$data_t_yun = array();
	foreach ($trs as $key => $value) {

		$entry_no = trim($value->find('div.number-cell', 0)->plaintext);
		// round(floor('123.49'*10),1)/10;
		$dan_ratio = str_replace(array(
			"$",
			" ",
			","
		), "", $value->find('div[data-id="fixed-odds-price"] div.animate-odd', 0)->plaintext);
		$yun_ratio = str_replace(array(
			"$",
			" ",
			","
		), "", $value->find('div[data-id="fixed-odds-place-price"] div.animate-odd', 0)->plaintext);
		$t_dan_ratio = str_replace(array(
			"$",
			" ",
			","
		), "", $value->find('div[ng-if="raceRunners.showParimutuelWin"] div.animate-odd', 0)->plaintext);
		$t_yun_ratio = str_replace(array(
			"$",
			" ",
			","
		), "", $value->find('animate-odds-change[current-value="runner.displayParimutuelPlace"] div.animate-odd', 0)->plaintext);

		// echo 't_yun_ratio' . $t_yun_ratio . PHP_EOL;
		if (substr($dan_ratio, -3) == 'SCR' || substr($yun_ratio, -3) == 'SCR' || substr($t_dan_ratio, -3) == 'SCR' || substr($t_yun_ratio, -3) == 'SCR') {
			echo 'SCR' . PHP_EOL;
			$type = '출전취소';
			$memo = $race->place_name . " " . $race->race_no . "경주: " . $entry_no . "번 " . $type;
			$old_start_time = $race->start_time;
			$new_start_time = $race->start_time;
			$sql = "INSERT INTO `race_change_info` (`association_code`, `race_id`, `entry_no`, `type`, `memo`, `old_start_time`, `new_start_time`) SELECT '" . $race->association_code . "', " . $race->id . "," . $entry_no . ", '" . $type . "' , '" . $memo . "','" . $old_start_time . "','" . $new_start_time . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race_change_info` WHERE `race_id`= " . $race->id . " and  `entry_no` = " . $entry_no . " and  `type` = '" . $type . "')";
			echo $sql;
			$ok = insert_sql($sql);
			echo $ok . PHP_EOL;
		}

		if ($dan_ratio) {
			if ($dan_ratio != 'N/A' && substr($dan_ratio, -3) != 'SCR') {
				$dan_ratio = round(floor($dan_ratio * 10) / 10, 1);
			}
			$data_dan[] = array(
				$entry_no,
				$dan_ratio
			);
		}
		if ($yun_ratio) {
			if ($yun_ratio != 'N/A' && substr($yun_ratio, -3) != 'SCR') {
				$yun_ratio = round(floor($yun_ratio * 10) / 10, 1);
			}
			$data_yun[] = array(
				$entry_no,
				$yun_ratio
			);
		}
		if ($t_dan_ratio) {
			if ($t_dan_ratio != 'N/A' && substr($t_dan_ratio, -3) != 'SCR') {
				$t_dan_ratio = round(floor($t_dan_ratio * 10) / 10, 1);
			}
			$data_t_dan[] = array(
				$entry_no,
				$t_dan_ratio
			);
		}
		if ($t_yun_ratio) {
			if ($t_yun_ratio != 'N/A' && substr($t_yun_ratio, -3) != 'SCR') {
				$t_yun_ratio = round(floor($t_yun_ratio * 10) / 10, 1);
			}
			$data_t_yun[] = array(
				$entry_no,
				$t_yun_ratio
			);
		}
	}

	// echo json_encode($data_dan, JSON_UNESCAPED_UNICODE);
	if (count($data_dan)) {
		$race_id_type = $race->id . '_fw';
		$sql = "REPLACE INTO `os_odds` (`race_id_type`,`race_id`, `type`, `data`)  VALUES ('" . $race_id_type . "','" . $race->id . "','확정단승','" . json_encode($data_dan, JSON_UNESCAPED_UNICODE) . "')";
		echo $sql . PHP_EOL;
		$ok = insert_sql($sql);
		echo $ok . PHP_EOL;
	}
	// echo json_encode($data_yun, JSON_UNESCAPED_UNICODE);
	if (count($data_yun)) {
		$race_id_type = $race->id . '_fp';
		$sql = "REPLACE INTO `os_odds` (`race_id_type`,`race_id`, `type`, `data`)  VALUES ('" . $race_id_type . "','" . $race->id . "','확정연승','" . json_encode($data_yun, JSON_UNESCAPED_UNICODE) . "')";
		echo $sql . PHP_EOL;
		$ok = insert_sql($sql);
		echo $ok . PHP_EOL;
	}
	if (count($data_t_dan)) {
		$race_id_type = $race->id . '_tw';
		$sql = "REPLACE INTO `os_odds` (`race_id_type`,`race_id`, `type`, `data`)  VALUES ('" . $race_id_type . "','" . $race->id . "','단승','" . json_encode($data_t_dan, JSON_UNESCAPED_UNICODE) . "')";
		echo $sql . PHP_EOL;
		$ok = insert_sql($sql);
		echo $ok . PHP_EOL;
	}
	// echo json_encode($data_yun, JSON_UNESCAPED_UNICODE);
	if (count($data_t_yun)) {
		$race_id_type = $race->id . '_tp';
		$sql = "REPLACE INTO `os_odds` (`race_id_type`,`race_id`, `type`, `data`)  VALUES ('" . $race_id_type . "','" . $race->id . "','연승','" . json_encode($data_t_yun, JSON_UNESCAPED_UNICODE) . "')";
		echo $sql . PHP_EOL;
		$ok = insert_sql($sql);
		echo $ok . PHP_EOL;
	}
}

function get_race_data_to_json($casper, $url, $race)
{
	echo $start_time;
	echo $association_code;
	echo date("Y-m-d H:i:s") . 'get func' . PHP_EOL;
	global $data;
	global $interval;

	// $url="https://www.tab.com.au/racing/2018-11-05/THE-MEADOWS/MEA/G/12";
	// $casper = new Casper();
	// // May need to set more options due to ssl issues
	// $casper->setOptions(array(
	// 'ignore-ssl-errors' => 'yes',
	// 'loadImages' => 'false',
	// ));
	// echo '$casper ' . PHP_EOL;
	// print_r($casper);
	$casper->start($url);
	$casper->waitForText('Last Updated:', 3000);
	$casper->run();
	$html = $casper->getHtml();
	// echo '$html ' . $html . PHP_EOL;
	// echo '$html ' . strlen($html) . PHP_EOL;

	$wp_html = explode('Last Updated', $html)[0];
	// echo '$wp_html ' . strlen($wp_html) . PHP_EOL;
	$dom = HtmlDomParser::str_get_html($wp_html);

	$page = trim($dom->find('div.page-not-found h1', 0)->plaintext);
	echo '$page ' . $page . PHP_EOL;
	if ($page == 'Uh Oh!') {
		return;
	}
	$stat = $dom->find('div.race-info-wrapper li.status-text', 0)->plaintext;
	echo '$stat ' . $stat . PHP_EOL;
	echo $interval . PHP_EOL;
	// if (!$stat) {
	// $output = shell_exec('sh /root/kill_os4php.sh ' . '/tmp/phantomjs_cache_os_baedang_' . str_replace(' ', '_', $interval) );
	// echo "<pre>$output</pre>";
	// $output = shell_exec('php /srv/krace/application/php/admin/os_baedang.php ' . $interval . ' > /dev/null 2>/dev/null &');
	// push_log('kill and start os_baedang.php' . $interval);
	// echo "<pre>$output</pre>";

	// echo 'exit php script' . PHP_EOL;
	// sleep(10);
	// exit('exit');
	// }

	if ($stat == 'Abandoned') {
		$entry_no = 0;
		$type = '경주취소';
		$old_start_time = $race->start_time;
		$new_start_time = $race->start_time;

		$memo = $race->place_name . " " . $race->race_no . "경주: 경주취소";
		echo $memo . PHP_EOL;
		$sql = "INSERT INTO `race_change_info` (`association_code`,`race_id`, `entry_no`, `type`, `memo`, `old_start_time`, `new_start_time`) SELECT '" . $race->association_code . "'," . $race->id . "," . $entry_no . ", '" . $type . "' , '" . $memo . "','" . $old_start_time . "','" . $new_start_time . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race_change_info` WHERE `race_id`= " . $race->id . " and  `entry_no` = " . $entry_no . " and  `type` = '" . $type . "' and  `memo` =  '" . $memo . "' and  `old_start_time` = '" . $old_start_time . "' and `new_start_time` = '" . $new_start_time . "')";
		echo $sql;
		$ok = insert_sql($sql);
		echo $ok . PHP_EOL;
		return true;
	} else if ($stat == 'Closed' || $stat == 'All Paying' || $stat == 'Interim result') {
		return;
	}
	// $time = trim($dom->find('a[class="race-link selected"] time', 0)->plaintext);
	$time = trim($dom->find('div[data-test-race-starttime=""]', 0)->plaintext);
	echo $time . PHP_EOL;
	if (!$time) {
		return;
	}
	$start_time = $race->start_date . ' ' . $time;
	if (substr($time, 0, 2) >= 0 && substr($time, 0, 2) < 6) {
		$start_time = date('Y-m-d H:i', strtotime(date($start_time) . '+' . '1' . ' days'));
	}
	if ($start_time . ':00' != $race->start_time) {
		echo 'time changed : ' . $race->start_time . '->' . $start_time;
		$entry_no = 0;
		$type = '출발시각변경';
		$old_start_time = substr($race->start_time, 0, -3);
		$new_start_time = $start_time;

		$memo = $race->place_name . " " . $race->race_no . "경주: 출발시각변경" . ' ' . substr($old_start_time, -5) . ' => ' . substr($new_start_time, -5);
		echo $memo . PHP_EOL;
		$sql = "INSERT INTO `race_change_info` (`association_code`,`race_id`, `entry_no`, `type`, `memo`, `old_start_time`, `new_start_time`) SELECT '" . $race->association_code . "'," . $race->id . "," . $entry_no . ", '" . $type . "' , '" . $memo . "','" . $old_start_time . "','" . $new_start_time . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race_change_info` WHERE `race_id`= " . $race->id . " and  `entry_no` = " . $entry_no . " and  `type` = '" . $type . "' and  `memo` =  '" . $memo . "' and  `old_start_time` = '" . $old_start_time . "' and `new_start_time` = '" . $new_start_time . "')";
		echo $sql;
		$ok = insert_sql($sql);
		echo $ok . PHP_EOL;
	}
	$ref_data['place_code'] = $race->place_code;
	$ref_data['start_time'] = $start_time;

	$casper->start($url . '/Quinella');
	$casper->waitForText('Last Updated:', 3000);
	$casper->click("button.toggle-flucs-button.different-button.button-inactive");
	// $casper->click("button.toggle-flucs-button.different-button.button-inactive");
	// toggle-flucs-button different-button button-inactive
	$casper->waitForText('Maximum dividend is $', 3000);
	$casper->run();
	$html = $casper->getHtml();
	$q_html = explode('Last Updated', $html)[0];
	// echo $q_html . PHP_EOL;
	$casper->start($url . '/Exacta');
	$casper->waitForText('Last Updated:', 3000);
	$casper->click("button.toggle-flucs-button.different-button.button-inactive");
	$casper->waitForText('Maximum dividend is $', 3000);
	$casper->run();
	$html = $casper->getHtml();
	$e_html = explode('Last Updated', $html)[0];
	// $casper->start($url . '/Trifecta');
	// $casper->waitForText('</noscript>', 1000);
	// $casper->click("button.toggle-flucs-button.different-button.button-inactive");
	// $casper->waitForText('Available', 1000);
	// $casper->run();
	// $t_html = $casper->getHtml();

	echo date("Y-m-d H:i:s") . 'get func run' . PHP_EOL;
	insert_wp_odds($wp_html, $race);
	insert_qe_odds($q_html, $race, 'q', '복승');
	insert_qe_odds($e_html, $race, 'e', '쌍승');
	// insert_odds($t_html, $race_id, 't', '삼쌍승');
}

function select_query($db, $sql, $array = array())
{
	$data = array();
	try {
		$stmt = $db->prepare($sql);
		$stmt->execute($array);
		$data = $stmt->fetchAll();
	} catch (Exception $e) {
		$data['success'] = FALSE;
		$data['message'] = $e->getMessage();
	}
	return $data;
}

function exec_query($db, $sql, $array = array())
{
	$data = '';
	$db->beginTransaction();
	try {
		$stmt = $db->prepare($sql);
		if ($stmt->execute($array)) {
			$db->commit();
		}
		$data = TRUE;
	} catch (Exception $e) {
		$db->rollBack();
		$data = $e->getMessage();
	}
	return $data;
}

function set_pb_odds($db, $race)
{
	// print_r($race);
	$entry_arr = array();

	for ($i = 1; $i < $race->entry_count + 1; $i++) {
		if (array_search($i, explode(',', $race->cancel_entry_no)) === FALSE) {
			array_push($entry_arr, $i);
		}
	}

	$permutations = new drupol\phpermutations\Generators\Combinations($entry_arr, 4);

	$sql = "SELECT id FROM club_info";
	$clubs = select_query($db, $sql);
	// print_r($clubs);

	foreach ($clubs as $club) {
		// if ($club->id != 1 && $club->id != 3) {
		// continue;
		// }

		// $sql = "SELECT race_id, SUM(o.bet_money) AS bet_money_all, CONCAT(place_1, IF(place_2=0,'', CONCAT('-',place_2)), IF(place_3=0,'', CONCAT('-',place_3))) AS select_num
		// FROM `order` AS o
		// WHERE o.stat NOT IN ('R','C') AND o.`type` = '복승' AND o.`race_id` = ? AND o.club_id = ?
		// GROUP BY select_num";

		// $results = select_query($db, $sql, array(
		// $race->id,
		// $club->id
		// ));

		$sql = "SELECT race_id, SUM(o.bet_money) AS bet_money_all, CONCAT(place_1, IF(place_2=0,'', CONCAT('-',place_2)), IF(place_3=0,'', CONCAT('-',place_3))) AS select_num
FROM `order` AS o
WHERE o.stat NOT IN ('R','C') AND o.`type` = '복승' AND o.`race_id` = ?
GROUP BY select_num";

		$results = select_query($db, $sql, array(
			$race->id
		));

		// if (count($results)) {
		// print_r($results);
		// }
		$sum = 0;
		foreach ($results as $value) {
			$sum += $value->bet_money_all;
		}
		// echo $sum . PHP_EOL;

		// 수수료 적용
		$sum = round($sum * 0.9);
		// echo $sum . PHP_EOL;

		$select_odds = array();
		$data = array();
		foreach ($results as $value) {
			$odds = round($sum / $value->bet_money_all, 1);
			if ($odds < 1) {
				$odds = 1.0;
			}
			// $selects[] = array(
			// $value->select_num,
			// $value->bet_money_all,
			// $odds
			// );
			$select_odds[$value->select_num] = $odds;
		}
		// print_r($selects);

		// echo json_encode($data, JSON_UNESCAPED_UNICODE);

		foreach ($permutations as $permutation) {
			$select_num = implode('-', $permutation);
			$data[$select_num] = $select_odds[$select_num];
		}
		// print_r($data);

		$json = 'JSON_OBJECT("pbodd",odds_pbodd,"pbeven",odds_pbeven,"pbunder",odds_pbunder,"pbover",odds_pbover,"nbodd",odds_nbodd,"nbeven",odds_nbeven,"nbunder",odds_nbunder,"nbover",odds_nbover)';
		$sql = "replace into club_odds (club_odds_race_id, club_odds_club_id, club_odds_code, club_odds_type, club_odds_data, club_odds_stat ) SELECT " . $v->id . ", id, CONCAT(" . $v->id . ", '_', id, '_pb') , 'pb', " . $json . ", 'P' FROM club_info";
		echo $sql . PHP_EOL;
		$ok = insert_sql($sql);
		echo $ok . PHP_EOL;

		if (count($data)) {
			$sql = "replace into club_odds (club_odds_race_id, club_odds_club_id, club_odds_type, club_odds_code,  club_odds_data, club_odds_stat ) VALUES (?, ?,  'qe', concat(?, '_', ?, '_', 'q'), ?, 'P')";
			// echo $sql . PHP_EOL;
			// $ok = interpolateQuery($sql, array($race->id, $race->id, json_encode($data, JSON_UNESCAPED_UNICODE)));
			// echo $ok . PHP_EOL;
			$msg = exec_query($db, $sql, array(
				$race->id,
				$club->id,
				$race->id,
				$club->id,
				json_encode($data, JSON_UNESCAPED_UNICODE)
			));
			echo $club->id . ':' . $race->id . '=>' . ($msg ? 'Exec Ok' : $msg) . PHP_EOL;
		}
	}
}

echo date("Y-m-d H:i:s") . PHP_EOL;
// print_r($argv);
$sleep = 10;
if ($argv[3]) {
	$sleep = $argv[3];
}
$interval = '1 hour';
if ($argv[1] && $argv[2]) {
	$interval = $argv[1] . ' ' . $argv[2];
}
echo $interval . ' ' . $sleep . PHP_EOL;
// extract($_POST);
require_once __DIR__ . '/../../../application/configs/configdb.php';
$db = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8mb4', $user, $password, array(
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_EMULATE_PREPARES => false,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
));

// while (true) {
$sql = "SELECT r.id, r.association_code, r.place_name, r.place_code, r.start_date, r.start_time, r.race_no, p.name as place_name FROM race r left join place p on r.place_id = p.id WHERE start_time < date_add(now(), INTERVAL " . $interval . ") and start_time > date_add(now(), INTERVAL -1 minute) and r.entry_count >6 and r.stat = 'P' order by r.start_time asc;";
$races = select_sql($sql);
// print_r($race);

if ($races) {
	foreach ($races as $race) {
		set_pb_odds($db, $race);
	}
}
//     sleep($sleep);
// }
