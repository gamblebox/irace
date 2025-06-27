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
$date = date("Y-m-d");
if ($argv[1] == 'yesterday') {
	$date = date('Y-m-d', strtotime($date . '-1' . ' days'));
}
echo $date . PHP_EOL;

require_once __DIR__ . '/../../../application/configs/configdb.php';
$db = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8mb4', $user, $password, array(
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_EMULATE_PREPARES => false,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
));

$sql = "SELECT STRAIGHT_JOIN CONCAT(u.id, REPLACE('" . $date . "', '-', '')) AS ud_id, u.club_id, u.user_id, '" . $date . "' AS date, deposit_money_all, withdraw_money_all, bet_money_all
, bet_money_real_all, bet_money_service_all, service_money_all, result_money_all
, profit, cut_money_all, allin_service_money_all, deposit_service_money_all, any_service_money_all, recommend_service_money_all
FROM user_info u 
LEFT OUTER
JOIN club_info AS c ON u.club_id = c.id
LEFT OUTER
JOIN (
SELECT u.user_id u_id, SUM(IF(b.banking_type = 'I' AND b.stat = 'E',b.amount,0)) AS `deposit_money_all`
, SUM(IF(b.banking_type = 'O' AND b.stat = 'E',b.amount,0)) AS `withdraw_money_all`
FROM user_info u
LEFT OUTER
JOIN `banking` b ON u.user_id = b.user_id
WHERE DATE(b.u_time) = '" . $date . "'
GROUP BY u.user_id, DATE(b.u_time)) AS b ON u.user_id = b.u_id
LEFT OUTER
JOIN (
SELECT u.user_id u_id, DATE(o.buy_time) AS buy_date, SUM(o.`bet_money`) AS `bet_money_all`, SUM(IF((o.`money_type` = 'R'),o.`bet_money`,0)) AS `bet_money_real_all`
, SUM(IF((o.`money_type` = 'S'),o.`bet_money`,0)) AS `bet_money_service_all`, SUM(o.`service_money`) AS `service_money_all`
, SUM(o.`result_money`) AS `result_money_all`,(SUM(IF((o.`money_type` = 'R'),o.`bet_money`,0)) - SUM(o.`result_money`)) AS `profit`
FROM user_info u
LEFT OUTER
JOIN `order`o ON u.user_id = o.user_id
WHERE o.`stat` NOT IN('C','R') AND DATE(o.buy_time) = '" . $date . "'
GROUP BY u.user_id, DATE(o.buy_time)) AS o ON u.user_id = o.u_id
LEFT OUTER
JOIN (
SELECT user_id u_id, SUM(`cut_money`) AS `cut_money_all`
FROM cut_result_money
LEFT OUTER
JOIN race AS r ON r.id = race_id
WHERE DATE(r.start_time) = '" . $date . "'
GROUP BY user_id, DATE(r.start_time)) AS cr ON u.user_id = cr.u_id
LEFT OUTER
JOIN (
SELECT l.user_id u_id, SUM(IF(l.memo LIKE '%손실금%',new_money_service - old_money_service, 0)) AS allin_service_money_all
, SUM(IF(l.memo LIKE '%충전%',new_money_service - old_money_service, 0)) AS deposit_service_money_all
, SUM(IF(l.memo LIKE '%임의 지급%',new_money_service - old_money_service, 0)) AS any_service_money_all
, SUM(IF(l.memo LIKE '%추천인%',new_money_service - old_money_service, 0)) AS recommend_service_money_all
FROM log_money l
WHERE DATE(u_time) = '" . $date . "'
GROUP BY user_id) AS s ON u.user_id = s.u_id
WHERE c.club_level > -1 AND u.user_level > -3 AND u.user_level < 50 
order BY u.id ";
$data = select_query($db, $sql);
foreach ($data as $value) {
	$sql = "REPLACE INTO user_sum (ud_id, club_id, user_id, date, deposit_money_all, withdraw_money_all, bet_money_all
, bet_money_real_all, bet_money_service_all, service_money_all, result_money_all, profit, cut_money_all, allin_service_money_all
, deposit_service_money_all, any_service_money_all, recommend_service_money_all)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);";
	$ok = exec_query($db, $sql, array(
		$value->ud_id, $value->club_id, $value->user_id, $value->date,	$value->deposit_money_all, $value->withdraw_money_all, $value->bet_money_all, $value->bet_money_real_all, $value->bet_money_service_all, $value->service_money_all, $value->result_money_all, $value->profit, $value->cut_money_all, $value->allin_service_money_all, $value->deposit_service_money_all, $value->any_service_money_all, $value->recommend_service_money_all
	));
	echo $ok . PHP_EOL;
}
