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

echo date("Y-m-d H:i:s") . PHP_EOL;

require_once __DIR__ . '/../../../application/configs/configdb.php';
$db = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8mb4', $user, $password, array(
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_EMULATE_PREPARES => false,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
));
$parameter = 'close';
if ($argv[1] != null) {
	$parameter = $argv[1];
}
switch ($parameter) {
	case 'short':
		while (TRUE) {
			$sql = "select * from race where start_time > DATE_ADD(NOW(), INTERVAL -100 minute) and start_time < NOW() AND association_code = 'powerball' and stat = 'P' order by start_time";
			$short_games = select_query($db, $sql);
			print_r($short_games);
			if (count($short_games) > 0) {
				foreach ($short_games as $short_game) {


					//                $count = 0;
					//                do  {
					//                    $count++;
					$json = file_get_contents('http://ntry.com/stats/api.php?type=powerball&c=round&m=get_result_stream_group&date_range=' . $short_game->start_date . ',' . $short_game->start_date . '&round_range=' . $short_game->race_no . ',' . $short_game->race_no . '&group_by=reg_date&mode=powerball_odd_even');
					echo $json;
					break;
					$result = json_decode($json);
					echo $short_game->race_no . '==' . $result->date_round;
					//                    echo date("Y-m-d H:i:s") . ' sleep 5 for try' . PHP_EOL;
					//                    sleep(3);
					//                } while ($close_game[0]->own_race_no != $result->times && $count < 20);
					print_r($result);
					//                if ($close_game[0]->own_race_no == $result->date_round) {
					$race_id = $short_game->id;
					$results_set = make_results_set_full($result);
					$results_set = base_convert($results_set, 2, 10);
					$results_contents = json_encode($result->ball, JSON_UNESCAPED_UNICODE);
					$own_race_no = $short_game->own_race_no;
					$sql = "INSERT INTO powerball_result ( results_set, results_contents, race_id, own_race_no, stat ) SELECT ?, ?, ?, ?, 'E' FROM DUAL WHERE NOT EXISTS ( SELECT * FROM powerball_result WHERE race_id = ? )";
					echo interpolateQuery($sql, array($results_set, $results_contents, $race_id, $own_race_no, $race_id));
					//                    $stmt = $db->prepare($sql);
					//                    $msg = $stmt->execute(array($results_set, $results_contents, $race_id, $own_race_no, $race_id));
					$sql = "UPDATE race SET stat = 'E' WHERE id = ? AND stat != 'E'";
					echo interpolateQuery($sql, array($race_id));
					//                    $stmt = $db->prepare($sql);
					//                    $msg = $stmt->execute(array( $race_id));
					//                    echo $own_race_no . '=>' . ($msg ? 'Exec Ok' : $msg) . PHP_EOL;
					//                }
				}
			}
			echo date("Y-m-d H:i:s") . ' sleep next query' . PHP_EOL;
			sleep(15);
		}
		break;
	case 'close':
		while (TRUE) {
			$sql = "select * from race where start_time < DATE_ADD(NOW(), INTERVAL 10 SECOND) AND start_time > DATE_ADD(NOW(), INTERVAL -10 SECOND) AND association_code = 'powerball' order by start_time desc LIMIT 1";
			$close_game = select_query($db, $sql);
			print_r($close_game);
			if (count($close_game) > 0) {
				$count = 0;
				do {
					$count++;
					$json = file_get_contents('http://ntry.com/data/json/games/powerball/result.json');
					$result = json_decode($json);
					print_r($result);
					echo $close_game[0]->race_no . '==' . $result->date_round;
					echo date("Y-m-d H:i:s") . ' sleep 5 for try' . PHP_EOL;
					sleep(3);
				} while ($close_game[0]->race_no != $result->date_round && $count < 20);

				if ($close_game[0]->race_no == $result->date_round) {
					$race_id = $close_game[0]->id;
					$results_set = make_results_set($result);
					$results_set = base_convert($results_set, 2, 10);
					$results_contents = json_encode($result->ball, JSON_UNESCAPED_UNICODE);
					$own_race_no = $result->times;
					$start_date = $result->date;
					$sql = "INSERT INTO powerball_result ( results_set, results_contents, race_id, own_race_no, stat ) SELECT ?, ?, ?, ?, 'E' FROM DUAL WHERE NOT EXISTS ( SELECT * FROM powerball_result WHERE race_id = ? )";
					$stmt = $db->prepare($sql);
					$msg = $stmt->execute(array($results_set, $results_contents, $race_id, $own_race_no, $race_id));
					$sql = "UPDATE race SET stat = 'E' WHERE id = ? AND stat != 'E'";
					$stmt = $db->prepare($sql);
					$msg = $stmt->execute(array($race_id));
					// echo $own_race_no . '=>' . ($msg ? 'Exec Ok' : $msg) . PHP_EOL;
					echo $race_no . ':' . $start_date . '=>' . ($msg ? 'Exec Ok' : $msg) . PHP_EOL;
				}
			}
			echo date("Y-m-d H:i:s") . ' sleep next query' . PHP_EOL;
			sleep(15);
		}
		break;

	case 'full':
		$json = file_get_contents('http://ntry.com/stats/api.php?offset=0&size=20&cmd=get_round_data&type=powerball&get_type=round&get_value=20');
		$results = json_decode($json)->data;
		print_r($results);
		foreach ($results as $result) {
			$results_set = make_results_set_full($result);
			$results_set = base_convert($results_set, 2, 10);
			//            print_r($results_set);
			$balls = array($result->ball_1, $result->ball_2, $result->ball_3, $result->ball_4, $result->ball_5, $result->powerball);
			$results_contents = json_encode($balls, JSON_UNESCAPED_UNICODE);
			print_r($results_contents);
			$own_race_no = $result->round;
			$race_no = $result->date_round;
			$start_date = $result->reg_date;
			$sql = "INSERT INTO powerball_result ( results_set, results_contents, race_id, own_race_no, race_no, stat ) SELECT ?, ?, (select id from race where race_no = ? and start_date = ?), ?, ?, 'E' FROM DUAL WHERE NOT EXISTS ( SELECT * FROM powerball_result WHERE  own_race_no = ? )";
			$ok = interpolateQuery($sql, array($results_set, $results_contents, $race_no, $start_date, $own_race_no, $race_no, $own_race_no));
			echo $ok . PHP_EOL;
			$stmt = $db->prepare($sql);
			$msg = $stmt->execute(array($results_set, $results_contents, $race_no, $start_date, $own_race_no, $race_no, $own_race_no));
			echo $msg . PHP_EOL;
			$sql = "UPDATE race SET stat = 'E', own_race_no = ? WHERE id = (select id from race where race_no = ? and start_date = ?) AND stat != 'E'";
			$ok = interpolateQuery($sql, array($own_race_no, $race_no, $start_date));
			echo $ok . PHP_EOL;
			$stmt = $db->prepare($sql);
			$msg = $stmt->execute(array($own_race_no, $race_no, $start_date));
			echo $race_no . ':' . $start_date . '=>' . ($msg ? 'Exec Ok' : $msg) . PHP_EOL;
		}
		break;

	case 'day':
		$json = file_get_contents('http://ntry.com/stats/api.php?offset=0&size=288&cmd=get_round_data&type=powerball&get_type=round&get_value=288');
		$results = json_decode($json)->data;
		print_r($results);
		foreach ($results as $result) {
			$results_set = make_results_set_full($result);
			$results_set = base_convert($results_set, 2, 10);
			//            print_r($results_set);
			$balls = array($result->ball_1, $result->ball_2, $result->ball_3, $result->ball_4, $result->ball_5, $result->powerball);
			$results_contents = json_encode($balls, JSON_UNESCAPED_UNICODE);
			print_r($results_contents);
			$own_race_no = $result->round;
			$race_no = $result->date_round;
			$sql = "INSERT INTO powerball_result ( results_set, results_contents, race_id, own_race_no, race_no, stat ) SELECT ?, ?, (select id from race where own_race_no = ?), ?, ?, 'E' FROM DUAL WHERE NOT EXISTS ( SELECT * FROM powerball_result WHERE own_race_no = ? )";
			$ok = interpolateQuery($sql, array($results_set, $results_contents, $own_race_no, $own_race_no, $race_no, $own_race_no));
			echo $ok . PHP_EOL;
			$stmt = $db->prepare($sql);
			$msg = $stmt->execute(array($results_set, $results_contents, $own_race_no, $own_race_no, $race_no, $own_race_no));
			echo $msg . PHP_EOL;
			$sql = "UPDATE race SET stat = 'E' WHERE own_race_no = ? AND stat != 'E'";
			$ok = interpolateQuery($sql, array($own_race_no));
			echo $ok . PHP_EOL;
			$stmt = $db->prepare($sql);
			$msg = $stmt->execute(array($own_race_no));
			echo $own_race_no . '=>' . ($msg ? 'Exec Ok' : $msg) . PHP_EOL;
		}
		break;

	default:;
		break;
}
