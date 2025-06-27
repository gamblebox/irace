<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require __DIR__ . "/../../../vendor/autoload.php";

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

function exec_query($db, $sql, $array = array())
{
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

	$sql = "SELECT id FROM club_info where club_level > 0";
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
	// 	print_r($race);
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
	$permutation_sum = 0;
	foreach ($permutations->generator() as $permutation) {
		// print_r($permutation);
		$permutation_sum += array_sum($permutation);
	}
	$uo_line = ceil($permutation_sum / $permutations->count()) - 0.5;
	// echo $uo_line . PHP_EOL;

	// pb uo line
	$p_uo_line = ceil(array_sum($entry_arr) / count($entry_arr)) - 0.5;
	// echo $p_uo_line . PHP_EOL;

	$sql = "SELECT id, odds_pbodd, odds_pbeven, odds_pbunder, odds_pbover, odds_nbodd, odds_nbeven, odds_nbunder, odds_nbover, odds_base_nbuo, is_nb_uo_var, nb_uo_var_ratio FROM club_info where club_level > 0";
	$clubs = select_query($db, $sql);
	// 	print_r($clubs);

	foreach ($clubs as $club) {
		// if ($club->id !=1) continue;

		$odds_nbunder = $club->odds_nbunder;
		$odds_nbover = $club->odds_nbover;

		if ($club->is_nb_uo_var == 'Y') {

			$sql = "SELECT if (o.place_1 & b'00000010' = b'00000010', 'under', 'over') uo, sum(round(o.bet_money / COUNT_STR( LPAD(bin(o.place_1),8,'0'), '1'))) sum FROM `order` o WHERE o.`type` = '파워마' AND o.race_id = ? AND o.club_id = ? GROUP BY uo desc";
			$results = select_query($db, $sql, array(
				$race->id,
				// 261560,
				$club->id
			));
			if (count($results)) {
				print_r($results);

				$under_sum = $over_sum = 2000;
				if ($results[0]->sum) {
					$under_sum += $results[0]->sum;
				}
				if ($results[1]->sum) {
					$over_sum += $results[1]->sum;
				}
				echo '$under_sum:' . $under_sum . PHP_EOL;
				echo '$over_sum:' . $over_sum . PHP_EOL;
				// 				sleep(10);
				// $under_sum = 5000;
				// $over_sum = 10000;

				// $odds_base_nbuo = 1.85;
				$uosum = $under_sum + $over_sum;

				$fee = $club->odds_base_nbuo / 2;

				$uosum = $uosum * $fee;
				$u_odds = $uosum / $under_sum;
				$o_odds = $uosum / $over_sum;

				$odds_nbunder = round($club->odds_base_nbuo * (100 - $club->nb_uo_var_ratio) / 100 + $u_odds * $club->nb_uo_var_ratio / 100, 2);
				$odds_nbover = round($club->odds_base_nbuo * (100 - $club->nb_uo_var_ratio) / 100 + $o_odds * $club->nb_uo_var_ratio / 100, 2);

				echo '$uosum:' . $uosum . PHP_EOL;
				echo '$u_odds:' . $odds_nbunder . PHP_EOL;
				echo '$o_odds:' . $odds_nbover . PHP_EOL;
			} else {
				$odds_nbunder = $odds_nbover = $club->odds_base_nbuo;
			}
		}

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
		$data['pbodd'] = $club->odds_pbodd;
		$data['pbeven'] = $club->odds_pbeven;
		$data['pbunder'] = $club->odds_pbunder;
		$data['pbover'] = $club->odds_pbover;
		$data['nbodd'] = $club->odds_nbodd;
		$data['nbeven'] = $club->odds_nbeven;
		$data['nbunder'] = $odds_nbunder;
		$data['nbover'] = $odds_nbover;

		$data['pbuoline'] = $p_uo_line;
		$data['nbuoline'] = $uo_line;

		// print_r($data);
		// echo json_encode($data, JSON_UNESCAPED_UNICODE) . PHP_EOL;

		if (count($data)) {
			$sql = "replace into club_odds (club_odds_race_id, club_odds_club_id, club_odds_type, club_odds_code,  club_odds_data, club_odds_stat ) VALUES (?, ?,  'pb', concat(?, '_', ?, '_', 'pb'), ?, 'P')";
			// echo $sql . PHP_EOL;
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
if (isset($argv[3])) {
	$sleep = $argv[3];
}
$interval = '1 hour';
if (isset($argv[1]) && isset($argv[2])) {
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

// $bettingTypes = array('Quinella','Exacta');

// while (true) {
$sql = "SELECT * FROM race WHERE start_time < date_add(now(), INTERVAL " . $interval . ") and start_time > date_add(now(), INTERVAL -1 hour) and stat in ('P') and association_code != 'powerball' order by start_time asc;";
// $sql = "SELECT * FROM race WHERE id = 261560;";
// echo $sql . PHP_EOL;
$races = select_query($db, $sql);
// print_r($races);

if (count($races) > 0) {
	foreach ($races as $race) {
		// 			print_r($race);
		// set_qe_odds($db, $race);
		set_pb_odds($db, $race);
	}
}
// 	if ($sleep) {
// 		echo 'Sleep fo Next turn...' . PHP_EOL;
// 		sleep($sleep);
// 	}
// 	else{
echo 'Work Done !' . PHP_EOL;
// 		exit();
// 	}
// }
