<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
// require __DIR__ . "/../../../vendor/autoload.php";

$log_filename = __DIR__ . '/../../../daemon.error.log';

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

/**
 * Replaces any parameter placeholders in a query with the value of that
 * parameter.
 * Useful for debugging. Assumes anonymous parameters from
 * $params are are in the same order as specified in $query
 *
 * @param string $query
 *        	The sql query with parameter placeholders
 * @param array $params
 *        	The array of substitution parameters
 * @return string The interpolated query
 */
function interpolateQuery($query, $params)
{
	$keys = array();

	// build a regular expression for each parameter
	foreach ($params as $key => $value) {
		if (is_string($key)) {
			$keys[] = '/:' . $key . '/';
		} else {
			$keys[] = '/[?]/';
		}
	}

	$query = preg_replace($keys, $params, $query, 1, $count);

	// trigger_error('replaced '.$count.' keys');

	return $query;
}

function query_error($e)
{
	$data = array();
	$data['success'] = FALSE;
	$data['error'] = $e->getMessage();
	exit(json_encode($data, JSON_UNESCAPED_UNICODE));
}

// echo json_encode($data, JSON_UNESCAPED_UNICODE);
function select_query($db, $sql, $array = array())
{
	$stmt = $db->prepare($sql);
	if (!$stmt->execute($array)) {
		$data = $db->errorInfo()[2];
	}
	$data = $stmt->fetchAll();
	return $data;
}

function exec_query_transaction($db, $sql, $array = array())
{
	$db->beginTransaction();
	try {
		$stmt = $db->prepare($sql);
		if ($stmt->execute($array)) {
			$db->commit();
		}
		$db->commit();
		$data = TRUE;
	} catch (Exception $e) {
		$db->rollBack();
		$data = $e->getMessage();
	}
	return $data;
}

function exec_query($db, $sql, $array = array())
{
	$stmt = $db->prepare($sql);
	$data = $stmt->execute($array);
	return $data;
}

function set_qe_odds($db, $race)
{
	// print_r($race);
	$entry_arr = array();

	// for ($i = 1; $i < $race->entry_count + 1; $i ++) {
	// if (array_search($i, explode(',', $race->cancel_entry_no)) === FALSE) {
	// array_push($entry_arr, $i);
	// }
	// }
	for ($i = 1; $i < $race->entry_count + 1; $i++) {
		array_push($entry_arr, $i);
	}
	$permutations = new drupol\phpermutations\Generators\Combinations($entry_arr, 2);

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

function set_pb_odds($db, $race)
{
	print_r($race);
	$entry_arr = array();

	for ($i = 1; $i < $race->entry_count + 1; $i++) {
		if (array_search($i, explode(',', $race->cancel_entry_no)) === FALSE) {
			array_push($entry_arr, $i);
		}
	}

	$permutations = array();
	if (substr($race->associatio_code, 0, 2) == 'os') {
		$permutations = new drupol\phpermutations\Generators\Combinations($entry_arr, 4);
	} else {
		$permutations = new drupol\phpermutations\Generators\Combinations($entry_arr, 5);
	}

	$sql = "SELECT id, odds_pbodd, odds_pbeven, odds_pbubder, odds_pboover, odds_nbodd, odds_nbeven, odds_nbunder, odds_nbover, odds_base_nbuo, is_nb_uo_var, nb_uo_var_ratio FROM club_info";
	$clubs = select_query($db, $sql);
	// print_r($clubs);

	foreach ($clubs as $club) {

		$sql = "SELECT if (o.place_1 & b'00000010' = b'00000010', 'under', 'over') uo, sum(round(o.bet_money / COUNT_STR( LPAD(bin(o.place_1),8,'0'), '1'))) sum FROM `order` o WHERE o.`type` = '파워마' AND o.race_id = ? AND o.club_id = ? GROUP BY uo desc";
		$results = select_query($db, $sql, array(
			// $race->id,
			261560,
			1
		));
		if (count($results)) {
			print_r($results);
		}

		if ($club->is_nb_uo_var == 'Y') {
			$under_sum = $results[0]->sum;
			$over_sum = $results[1]->sum;

			$under_sum = 5000;
			$over_sum = 10000;

			// $odds_base_nbuo = 1.85;
			$uosum = $under_sum + $over_sum;

			$fee = $club->odds_base_nbuo / 2;

			$uosum = $uosum * $fee;
			$u_odds = $uosum / $under_sum;
			$o_odds = $uosum / $over_sum;

			$odds_nbunder = $club->odds_base_nbuo * (100 - $club->nb_uo_var_ratio) / 100 + $u_odds * $club->nb_uo_var_ratio / 100;
			$odds_nbover = $club->odds_base_nbuo * (100 - $club->nb_uo_var_ratio) / 100 + $o_odds * $club->nb_uo_var_ratio / 100;

			echo '$uosum:' . $uosum . PHP_EOL;
			echo '$u_odds:' . $u_odds . PHP_EOL;
			echo '$o_odds:' . $o_odds . PHP_EOL;
		} else {
			$odds_nbunder = $club->odds_nbunder;
			$odds_nbover = $club->odds_nbunder;
		}

		exit();

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

function make_results_set($result)
{
	if ($result->pow_ball_oe == '홀') {
		$results_set .= '10';
	} else {
		$results_set .= '01';
	}
	if ($result->pow_ball_unover == '언더') {
		$results_set .= '10';
	} else {
		$results_set .= '01';
	}
	if ($result->def_ball_oe == '홀') {
		$results_set .= '10';
	} else {
		$results_set .= '01';
	}
	if ($result->def_ball_unover == '언더') {
		$results_set .= '10';
	} else {
		$results_set .= '01';
	}
	if ($result->def_ball_size == '대') {
		$results_set .= '100';
	} else if ($result->def_ball_size == '중') {
		$results_set .= '010';
	} else {
		$results_set .= '001';
	}
	return $results_set;
}

function make_results_set_full($result)
{
	/* 	stdClass Object
	(
		[idx] => 190816916284
		[reg_date] => 2019-08-16
		[round] => 916284
		[date_round] => 198
		[ball_1] => 17
		[ball_2] => 18
		[ball_3] => 24
		[ball_4] => 8
		[ball_5] => 26
		[powerball] => 2
		[sum] => 93
		[sum_odd_even] => ODD
		[sum_odd_even_alias] => 홀
		[sum_under_over] => OVER
		[sum_under_over_alias] => 오버
		[sum_section] => F
		[sum_size] => L
		[sum_size_alias] => 대
		[powerball_odd_even] => EVEN
		[powerball_odd_even_alias] => 짝
		[powerball_under_over] => UNDER
		[powerball_under_over_alias] => 언더
		[powerball_section] => A
		) */
	if ($result->powerball_odd_even == 'ODD') {
		$results_set .= '10';
	} else {
		$results_set .= '01';
	}
	if ($result->powerball_under_over == 'UNDER') {
		$results_set .= '10';
	} else {
		$results_set .= '01';
	}
	if ($result->sum_odd_even == 'ODD') {
		$results_set .= '10';
	} else {
		$results_set .= '01';
	}
	if ($result->sum_under_over == 'UNDER') {
		$results_set .= '10';
	} else {
		$results_set .= '01';
	}
	if ($result->sum_size == 'L') {
		$results_set .= '100';
	} else if ($result->sum_size == 'M') {
		$results_set .= '010';
	} else {
		$results_set .= '001';
	}
	return $results_set;
}

function make_results_set_full_secondary($result)
{
	/* 	stdClass Object
	 (
	 	[trClass] => trOdd
		[round] => 934407
		[todayRound] => 177
		[time] => 14:43
		[powerball] => 2
		[powerballPeriod] => A (0~2)
		[powerballOddEven] => even
		[powerballUnderOver] => under
		[number] => 07, 06, 28, 03, 21
		[numberSum] => 65
		[numberSumPeriod] => D (58~65)
		[numberPeriod] => 중 (65~80)
		[numberOddEven] => odd
		[numberUnderOver] => under
	 ) */
	if ($result->powerballOddEven == 'odd') {
		$results_set .= '10';
	} else {
		$results_set .= '01';
	}
	if ($result->powerballUnderOver == 'under') {
		$results_set .= '10';
	} else {
		$results_set .= '01';
	}
	if ($result->numberOddEven == 'odd') {
		$results_set .= '10';
	} else {
		$results_set .= '01';
	}
	if ($result->numberUnderOver == 'under') {
		$results_set .= '10';
	} else {
		$results_set .= '01';
	}
	if ($result->numberPeriod == '대 (81~130)') {
		$results_set .= '100';
	} else if ($result->numberPeriod == '중 (65~80)') {
		$results_set .= '010';
	} else {
		$results_set .= '001';
	}
	return $results_set;
}

function scrap()
{
	// $geturl="https://www.dhlottery.co.kr/gameInfo.do?method=powerWinNoList";
	$geturl = "https://www.dhlottery.co.kr/user.do?method=login&returnUrl=%2FgameInfo.do%3Fmethod%3DpowerWinNoList&alertMsg=%B7%CE%B1%D7%C0%CE+%C8%C4+%C0%CC%BF%EB%C0%CC+%B0%A1%B4%C9%C7%D5%B4%CF%B4%D9.&returnUrl=%2FgameInfo.do%3Fmethod%3DpowerWinNoList";


	$loginurl = 'https://www.dhlottery.co.kr/userSsl.do?method=login';
	//  $loginurl = 'https://www.dhlottery.co.kr/user.do?method=login&returnUrl=';


	$postfields = 'returnUrl=/&newsEventYn=&userId=casuist&password=hanjd!@11&checkSave=on';

	$cookieFile = auth_site_cookie_store($loginurl, $postfields);
	echo $cookieFile . PHP_EOL;

	echo $result = auth_site_get($geturl, "/srv/krace/www/upload/" . $cookieFile, $postfields);
}

function auth_site_cookie_store($loginurl, $postfields)
{
	$parseURL = parse_url($loginurl);
	$ch = curl_init();
	// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 주소가 https가 아니라면 지울것
	// curl_setopt($ch, CURLOPT_SSLVERSION, 1); // 주소가 https가 아니라면 지울것
	curl_setopt($ch, CURLOPT_URL, "$loginurl");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "$postfields");
	curl_setopt($ch, CURLOPT_COOKIEJAR, "/srv/krace/www/upload/" . $parseURL['host'] . ".cookie");

	ob_start();
	echo $result = curl_exec($ch);
	ob_end_clean();
	curl_close($ch);

	return $parseURL['host'] . ".cookie";
}

function auth_site_get($geturl, $cookiefile, $postfields)
{
	$parseURL = parse_url($geturl);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER, 1);
	// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 주소가 https가 아니라면 지울것
	// curl_setopt($ch, CURLOPT_SSLVERSION, 1); // 주소가 https가 아니라면 지울것
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 100);

	curl_setopt($ch, CURLOPT_COOKIEJAR, "/srv/krace/www/upload/" . $parseURL['host'] . ".cookie");
	curl_setopt($ch, CURLOPT_COOKIEFILE, "$cookiefile");

	curl_setopt($ch, CURLOPT_URL, "$geturl");
	$result = curl_exec($ch);
	curl_close($ch);

	return $result;
}



echo date("Y-m-d H:i:s") . PHP_EOL;

require_once __DIR__ . '/../../../application/configs/configdb.php';
$db = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8mb4', $user, $password, array(
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_EMULATE_PREPARES => false,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
));
$parameter = 'close';
if (isset($argv[1])) {
	$parameter = $argv[1];
}

$geturl = "https://www.dhlottery.co.kr/user.do?method=login&returnUrl=%2FgameInfo.do%3Fmethod%3DpowerWinNoList&alertMsg=%B7%CE%B1%D7%C0%CE+%C8%C4+%C0%CC%BF%EB%C0%CC+%B0%A1%B4%C9%C7%D5%B4%CF%B4%D9.&returnUrl=%2FgameInfo.do%3Fmethod%3DpowerWinNoList";
$geturl = "https://www.dhlottery.co.kr/gameInfo.do?method=powerWinNoList";
// $geturl="https://www.dhlottery.co.kr/common.do?method=main";


$loginurl = 'https://www.dhlottery.co.kr/userSsl.do?method=login';

$postfields = 'returnUrl=/&newsEventYn=&userId=casuist&password=hanjd!@11&checkSave=on';

$cookieFile = auth_site_cookie_store($loginurl, $postfields);
echo $cookieFile . PHP_EOL;
exit();
echo $result = auth_site_get($geturl, "/srv/krace/www/upload/" . $cookieFile, $postfields);

$dom = new DomDocument();

// 실행
$dom->loadHTML($result);

$xpath = new DomXPath($dom);
//*[@id="mainContainer"]/article/table
$trs = $xpath->query('//table[@class="tbl_data tbl_data_col"]//tbody/tr');
print_r($trs);


exit();



// $json = file_get_contents('https://www.powerballgame.co.kr/?view=action&action=ajaxPowerballLog&actionType=dayLog&date=' . date("Y-m-d") . '&page=1');
// $json = file_get_contents('https://www.powerballgame.co.kr/?view=action&action=ajaxPowerballLog&actionType=dayLog&date=' . date("Y-m-d") . '&page=1');
//$json = file_get_contents('https://www.powerballgame.co.kr/?view=action&action=ajaxPowerballLog&actionType=dayLog&date=' . date("Y-m-d") . '&page=5');
// echo $json;
//$json = "{\"content\":[{\"trClass\":\"trEven\",\"round\":\"997498\",\"todayRound\":\"4\",\"time\":\"00:18\",\"powerball\":\"0\",\"powerballPeriod\":\"A (0~2)\",\"powerballOddEven\":\"even\",\"powerballUnderOver\":\"under\",\"number\":\"18, 20, 15, 01, 08\",\"numberSum\":\"62\",\"numberSumPeriod\":\"D (58~65)\",\"numberPeriod\":\"\uc18c (15~64)\",\"numberOddEven\":\"even\",\"numberUnderOver\":\"under\"},{\"trClass\":\"trOdd\",\"round\":\"997497\",\"todayRound\":\"3\",\"time\":\"00:13\",\"powerball\":\"3\",\"powerballPeriod\":\"B (3~4)\",\"powerballOddEven\":\"odd\",\"powerballUnderOver\":\"under\",\"number\":\"01, 08, 28, 09, 07\",\"numberSum\":\"53\",\"numberSumPeriod\":\"C (50~57)\",\"numberPeriod\":\"\uc18c (15~64)\",\"numberOddEven\":\"odd\",\"numberUnderOver\":\"under\"},{\"trClass\":\"trEven\",\"round\":\"997496\",\"todayRound\":\"2\",\"time\":\"00:08\",\"powerball\":\"6\",\"powerballPeriod\":\"C (5~6)\",\"powerballOddEven\":\"even\",\"powerballUnderOver\":\"over\",\"number\":\"22, 02, 09, 06, 17\",\"numberSum\":\"56\",\"numberSumPeriod\":\"C (50~57)\",\"numberPeriod\":\"\uc18c (15~64)\",\"numberOddEven\":\"even\",\"numberUnderOver\":\"under\"},{\"trClass\":\"trOdd\",\"round\":\"997495\",\"todayRound\":\"1\",\"time\":\"00:03\",\"powerball\":\"4\",\"powerballPeriod\":\"B (3~4)\",\"powerballOddEven\":\"even\",\"powerballUnderOver\":\"under\",\"number\":\"26, 14, 07, 13, 09\",\"numberSum\":\"69\",\"numberSumPeriod\":\"E (66~78)\",\"numberPeriod\":\"\uc911 (65~80)\",\"numberOddEven\":\"odd\",\"numberUnderOver\":\"under\"}],\"endYN\":\"N\"}";
//echo $json;
$results = json_decode($json)->content;
print_r($results);
foreach ($results as $result) {
	$results_set = make_results_set_full_secondary($result);
	$results_set = base_convert($results_set, 2, 10);
	$balls = explode(',', $result->number);
	array_push($balls, $result->powerball);
	$results_contents = json_encode($balls, JSON_UNESCAPED_UNICODE);
	$own_race_no = $result->round;
	$race_no = $result->todayRound;
	$sql = "INSERT INTO powerball_result ( results_set, results_contents, race_id, own_race_no, race_no, stat ) SELECT ?, ?, (select id from race where own_race_no = ?), ?, ?, 'E' FROM DUAL WHERE NOT EXISTS ( SELECT * FROM powerball_result WHERE own_race_no = ? )";
	$stmt = $db->prepare($sql);
	$msg = $stmt->execute(array($results_set, $results_contents, $own_race_no, $own_race_no, $race_no, $own_race_no));
	$sql = "UPDATE race SET stat = 'E' WHERE own_race_no = ? AND stat != 'E'";
	$stmt = $db->prepare($sql);
	$msg = $stmt->execute(array($own_race_no));
	echo $own_race_no . '=>' . ($msg ? 'Exec Ok' : $msg) . PHP_EOL;
}
