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

echo date("Y-m-d H:i:s") . PHP_EOL;

require_once __DIR__ . '/../../../application/configs/configdb.php';
$db = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8mb4', $user, $password, array(
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_EMULATE_PREPARES => false,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
));

$sql = "SELECT CONCAT(cri.club_id, r.id) AS cr_id, cri.club_id, r.id AS race_id, cut_money_all, bet_money_all, bet_money_real_all, bet_money_service_all, service_money_all, result_money_all, profit
FROM `club_race` AS cri 
LEFT OUTER 
JOIN `club_info` AS c ON cri.club_id = c.id
LEFT OUTER 
JOIN `race` AS r ON cri.race_id = r.id
LEFT OUTER
JOIN (
SELECT c.race_id, c.club_id, SUM(c.`cut_money`) AS `cut_money_all`
FROM cut_result_money c
LEFT JOIN `race` `r` ON `c`.`race_id` = `r`.`id`
WHERE DATE(r.start_time) >= DATE(DATE_ADD(NOW(), INTERVAL -1 HOUR))
GROUP BY club_id, race_id) AS cr ON cr.race_id = r.id AND cr.club_id = c.id
LEFT OUTER
JOIN (
SELECT `o`.`race_id` AS `race_id`,`o`.`club_id`, SUM(`o`.`bet_money`) AS `bet_money_all`, SUM(IF((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) AS `bet_money_real_all`, SUM(IF((`o`.`money_type` = 'S'),`o`.`bet_money`,0)) AS `bet_money_service_all`, SUM(`o`.`service_money`) AS `service_money_all`, SUM(`o`.`result_money`) AS `result_money_all`,(SUM(IF((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) - SUM(`o`.`result_money`)) AS `profit`
FROM (`order` `o`
LEFT JOIN `race` `r` ON(`o`.`race_id` = `r`.`id`))
WHERE `o`.`stat` NOT IN ('C', 'R') AND DATE(r.start_time) >= DATE(DATE_ADD(NOW(), INTERVAL -1 HOUR))
GROUP BY `o`.`club_id`, `o`.`race_id`) AS so ON (r.id = so.race_id AND so.club_id = c.id)
LEFT OUTER
JOIN (
SELECT `o`.`race_id` AS `race_id`,`o`.`club_id`, COUNT(*) AS count
FROM (`order` `o`
LEFT JOIN `race` `r` ON(`o`.`race_id` = `r`.`id`))
WHERE DATE(r.start_time) >= DATE(DATE_ADD(NOW(), INTERVAL -1 HOUR))
GROUP BY `o`.`club_id`, `o`.`race_id`) AS co ON (r.id =co.race_id AND co.club_id = c.id) 
WHERE DATE(r.start_time) >= DATE(DATE_ADD(NOW(), INTERVAL -1 HOUR)) AND c.club_level > 0 AND c.id NOT IN (0, 255) AND co.count > 0";
$data = select_query($db, $sql);
foreach ($data as $value) {
	$sql = "REPLACE INTO race_sum (cr_id, club_id, race_id, cut_money_all, bet_money_all, bet_money_real_all, bet_money_service_all
, service_money_all, result_money_all, profit)
VALUES (?,?,?,?,?,?,?,?,?,?);";
	$ok = exec_query($db, $sql, array(
		$value->cr_id, $value->club_id, $value->race_id, $value->cut_money_all, $value->bet_money_all, $value->bet_money_real_all, $value->bet_money_service_all, $value->service_money_all, $value->result_money_all, $value->profit
	));
	echo $ok . PHP_EOL;
}
