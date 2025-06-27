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

echo date("Y-m-d H:i:s") . PHP_EOL;

require_once __DIR__ . '/../../../application/configs/configdb.php';
$db = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8mb4', $user, $password, array(
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_EMULATE_PREPARES => false,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
));
$today = date('Y-m-d');
// $today = '2019-10-30';
$tomorrow = date('Y-m-d', strtotime($today . '+' . '1' . ' days')); // 1일 후       
// if ($argv[1] == 'today') {
// 	$tomorrow = date('Y-m-d', strtotime( '+' . '0' . ' days')); // 1일 후       
// }
$open_time = $tomorrow . ' 06:04:30';

// $sql = "select own_race_no from race where start_time >= DATE(now()) and start_time < DATE_ADD(DATE(NOW()), INTERVAL +1 DAY) and place_id = 0 order by start_time desc LIMIT 1";
$sql = "select own_race_no from race where start_time >= '" . $today . "' and start_time < DATE_ADD('" . $today . "', INTERVAL +1 DAY) and place_id = 0 order by start_time desc LIMIT 1";
$open_own_race_no = select_query($db, $sql)[0]->own_race_no + 1;
// echo $open_game_id;

$own_race_no = $open_own_race_no;
$start_date = $tomorrow;

if ($argv[1] == 'today') {
	$own_race_no = 1141923;
	$open_time = $today . ' 11:39:30';
	$start_date = $today;
}
// $own_race_no = 915799 + 288 + 288;
$start_time = $open_time;

//temp
// $open_time = $today . ' 06:09:50';
// $own_race_no = 1097604;
// $start_time = $open_time;
// $start_date = $today;

$sql = "INSERT INTO race (association_code, place_id, own_race_no, race_no, start_date, start_time) SELECT 'powerball', 0, ?, ?, ?, ? FROM DUAL WHERE NOT EXISTS ( SELECT * FROM race WHERE own_race_no = ? )";
for ($i = 72; $i < 288; $i++) {
	// echo interpolateQuery($sql, array($own_race_no, $i+1, $start_date, $start_time, $own_race_no)) . PHP_EOL;
	// exit();
	$stmt = $db->prepare($sql);
	$msg = $stmt->execute(array($own_race_no, $i + 1, $start_date, $start_time, $own_race_no));
	// echo $own_race_no . ':' . $start_time . '=>' . ($msg ? 'Exec Ok' : $msg) . PHP_EOL;
	echo $i + 1 . ' : ' . $own_race_no . ':' . $start_time . '=>' . $msg . PHP_EOL;
	$own_race_no++;
	$start_time = date('Y-m-d H:i:s', strtotime($start_time . '+' . '5' . ' minutes'));
}
