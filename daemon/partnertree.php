<?php
error_reporting(E_ALL & ~ E_NOTICE & ~ E_WARNING);
// require __DIR__ . "/../../../vendor/autoload.php";
/* 
$log_filename = __DIR__ . '/../../../daemon.error.log';

function push_log($log_str)
{
	global $log_filename;
	$now = date('Y-m-d H:i:s');
	$filep = fopen($log_filename, "a");
	if (! $filep) {
		die("can't open log file : " . $log_filename);
	}
	fputs($filep, "{$now} : {$log_str}" . PHP_EOL);
	fclose($filep);
} */

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
	$data = array();
	$stmt = $db->prepare($sql);
	if (! $stmt->execute($array)) {
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
function anykey() {
	echo "Are you sure you want to do this?  Type 'yes' to continue: ";
	$handle = fopen ("php://stdin","r");
	$line = fgets($handle);
	if(trim($line)){
		fclose($handle);
		echo "\n";
		echo "Thank you, continuing...\n";
		return ;
	}
}

function search($array, $key, $value)
{
	$results = array();
	
	if (is_array($array)) {
		if (isset($array[$key]) && $array[$key] == $value) {
			$results[] = $array;
		}
		
		foreach ($array as $subarray) {
			$results = array_merge($results, search($subarray, $key, $value));
		}
	}
	
	return $results;
}

// echo date("Y-m-d H:i:s") . PHP_EOL;

require_once __DIR__ . '/../../../application/configs/configdb.php';
$db = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8mb4', $user, $password, array(
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_EMULATE_PREPARES => false,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
));



// 	$sql = "SELECT user_level, class_name FROM partner_class WHERE club_id = " . $club_id . " and user_level < " . $user_level . " order by user_level desc";
// 	$stmt = $db->prepare($sql);
// 	$stmt->execute();
// 	$partner_classes = $stmt->fetchAll();
// print_r($partner_classes);


$date = date('Y-m-d');
$sdate = $edate = $date;
$club_id = 1;

// $root_partner_id = $user_id;
$root_partner_id = '_gold_kaiji000';
$partner_id_list = array($root_partner_id);

$data = array();
$i = 0;


// 	foreach ($partner_classes as $partner_class) {
while (count($partner_id_list) > 0 ){
// 	echo '$partner_id_list ' . PHP_EOL;
	// print_r($partner_id_list);
	
	$c = 0;
	$next_partner_id_list = array();
	foreach ($partner_id_list as $partner_id) {
// 		echo '$partner_id ' . $partner_id . PHP_EOL;
		$sql = "select user_id from user_info where user_level = 90 and partner_id = '" . $partner_id . "'";
		$stmt = $db->prepare($sql);
		$stmt->execute();
		$result = $stmt->fetchAll();
		foreach ($result as $value) {
			array_push($next_partner_id_list,$value->user_id );
		}
		
		$partner_line_list =  array();
		
		$sql ="WITH recursive cte (user_id, partner_id, user_level) AS (
SELECT user_id, partner_id, user_level
FROM user_info
WHERE partner_id = '" . $partner_id . "' UNION ALL
SELECT r.user_id, r.partner_id, r.user_level
FROM user_info r
INNER JOIN cte ON r.partner_id = cte.user_id)
SELECT DISTINCT user_id AS partner_id
FROM cte
WHERE user_level > 50";
		$stmt = $db->prepare($sql);
		$stmt->execute();
		$result = $stmt->fetchAll();
		foreach ($result as $value) {
			array_push($partner_line_list,$value->partner_id );
		}
		
		
			
		
		
		array_unshift($partner_line_list, $partner_id);
		$partner_line = "'" . implode("','", $partner_line_list) . "'";
// 		echo '$partner_line ' . $partner_line . PHP_EOL;
// 		anykey();
		
		// 			$sql = "select id, partner_id, user_id, user_level from user_info where user_level = " . $partner_class->user_level . " and partner_id = '" . $partner_id . "'";
		$sql = "SELECT STRAIGHT_JOIN CONCAT('row_', p.id) AS DT_RowId, p.id, p.user_id, p.club_id, p.user_pw, p.partner_id, p.partner_level
,p.user_level, p.club_user_id, p.nick_name, p.phone, COUNT(DISTINCT u.user_id) AS user_count, p.phone AS phone, p.partner_calculate_rule
, p.partner_calculate_ratio, p.r_time, SUM(u.money_real) AS money_real_all, SUM(u.money_service) AS money_service_all
,SUM(IF(b.banking_type = 'I' AND b.stat = 'E',b.amount,0)) AS `deposit_money_all`, SUM(IF(b.banking_type = 'O' AND b.stat = 'E',b.amount,0)) AS `withdraw_money_all`
,SUM(o.`bet_money`) AS `bet_money_all`, SUM(IF((o.`money_type` = 'R'),o.`bet_money`,0)) AS `bet_money_real_all`, SUM(IF((o.`money_type` = 'S'),o.`bet_money`,0)) AS `bet_money_service_all`, SUM(o.`service_money`) AS `service_money_all`, SUM(o.`result_money`) AS `result_money_all`,(SUM(IF((o.`money_type` = 'R'),o.`bet_money`,0)) - SUM(o.`result_money`)) AS `profit`
,SUM(`cut_money`) AS `cut_money_all`
FROM user_info AS p
LEFT OUTER
JOIN user_info AS u ON u.partner_id IN (" . $partner_line . ") AND u.user_level < 50
LEFT OUTER
JOIN club_info AS c ON p.club_id = c.id
LEFT OUTER
JOIN banking AS b ON u.user_id = b.user_id AND DATE(b.u_time) >= DATE('" . $sdate . "') AND DATE(b.u_time) <= DATE('" . $edate . "')
LEFT OUTER
JOIN `order` AS o ON u.user_id = o.user_id AND DATE(o.buy_time) >= DATE('" . $sdate . "') AND DATE(o.buy_time) <= DATE('" . $edate . "') 
LEFT OUTER
JOIN cut_result_money AS cr ON u.user_id = cr.user_id AND DATE(cr.c_date) >= DATE('" . $sdate . "') AND DATE(cr.c_date) <= DATE('" . $edate . "')
WHERE c.club_level > 0 AND c.id = 1 AND p.user_id = '" . $partner_id . "' 
GROUP BY p.user_id";
// 		echo '$sql ' . $sql . PHP_EOL;
		
		$stmt = $db->prepare($sql);
		$stmt->execute();
		$result = $stmt->fetchAll();
// 				print_r($result);
				
		
		if (count($result) > 0) {
			$data[$i][] = $result[0];
			
// 			foreach ($result as $value) {
// 				$next_id_list[] = $value->user_id;
// 			}
		}
// 		echo '$next_partner_id_list' . PHP_EOL;
		// print_r($next_partner_id_list);
// 		anykey();
		$c++;
	}
// 	echo 'fore end ' . $i . PHP_EOL;
	$partner_id_list = $next_partner_id_list;
	// print_r($data);
	
	$i++;
}
// print_r($data);


for ($i = count($data)-1; $i > 0; $i--) {
	
	foreach ($data[$i] as $value) {
		$key = array_search($value->partner_id, array_column($data[$i-1], 'user_id'));
// 				echo '$key ' . $key . PHP_EOL;
// 				anykey();
		$data[$i-1][$key]->children[] = $value;
	}
	
}


$d = $data[0];
// print_r($data);

// print_r($data);
$data = array();
$data['data'] = $d;
// print_r($data);
echo json_encode($data);
exit();
