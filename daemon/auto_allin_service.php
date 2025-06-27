<?php

header( "Content-Type:application/json" );
header( "Content-Type:text/html;charset=UTF-8" );
include (__DIR__ . '/../../../application/configs/configdb.php');

function query_error() {
	$data[ 'Ok' ] = 'Error';
	$data[ 'Error' ] = '통신 오류'; //$e->getMessage();//
	exit( json_encode( $data, JSON_UNESCAPED_UNICODE ) );
}

function select_query( $db, $sql, $array ) {
	try {
		$stmt = $db->prepare( $sql );
		$stmt->execute( $array );
		$data = $stmt->fetchAll();
	} catch ( Exception $e ) {
		query_error();
	}
	echo json_encode( $data, JSON_UNESCAPED_UNICODE );
}

$date = date('Y-m-d');
$sdate = $date;
// $edate = $date;
$edate = date('Y-m-d', strtotime($edate . '+' . '1' . ' days'));

$db = new PDO( 'mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8mb4', $user, $password, array(
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_EMULATE_PREPARES => false,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ ) );
$data = array();

$db->beginTransaction();
try {
	$sql = "UPDATE user_info AS u
				LEFT OUTER
				JOIN club_info c ON u.club_id = c.id
				LEFT OUTER
				JOIN (
				SELECT u.user_id, SUM(`o`.`bet_money`) AS `bet_money_all`, SUM(IF((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) AS `bet_money_real_all`, SUM(IF((`o`.`money_type` = 'S'),`o`.`bet_money`,0)) AS `bet_money_service_all`, SUM(`o`.`service_money`) AS `service_money_all`, SUM(`o`.`result_money`) AS `result_money_all`, (SUM(IF((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) - SUM(`o`.`result_money`)) AS `profit`
				FROM `order` AS o
				LEFT OUTER
				JOIN `user_info` AS `u` ON `u`.user_id = o.user_id
				WHERE ((`o`.`stat` != 'C') AND (`o`.`stat` != 'R')) AND o.buy_time >= DATE(?) AND o.buy_time < DATE(?)
				GROUP BY u.user_id) AS o ON u.user_id = o.user_id
				LEFT OUTER
				JOIN (
				SELECT user_id u_id, SUM(`cut_money`) AS `cut_money_all`
				FROM cut_result_money
				WHERE c_date >= DATE(?) AND c_date < DATE(?)
				GROUP BY user_id) AS cr ON u.user_id = cr.u_id SET 
				 u.money_service = u.money_service + @allin_service_money := ROUND(IF(@allin_service_max_offset := IF(u.allin_service_config = 'C', c.allin_service_max_offset, u.u_allin_service_max_offset) =0, @crprofit := (IF(profit IS NULL, 0, profit) + IF (cut_money_all IS NULL, 0, cut_money_all)) * (@allin_service := IF(u.allin_service_config = 'C', c.allin_service, u.u_allin_service)/100), IF(@allin_service_max_offset >= @crprofit,@crprofit * (@allin_service/100), @allin_service_max_offset * (@allin_service/100))))
				WHERE c.allin_service_isauto = 'Y' AND u.allin_service_config != 'N' AND u.user_level < 50 AND o.bet_money_all > 0 AND (IF(profit IS NULL, 0, profit) + IF (cut_money_all IS NULL, 0, cut_money_all)) >= IF(u.allin_service_config = 'C', c.allin_service_min_offset, u.u_allin_service_min_offset)";
	$stmt = $db->prepare( $sql );
	$stmt->execute( array( $sdate, $edate, $sdate, $edate ) );

	$sql = "INSERT INTO `log_money` (`old_money_real`, `new_money_real`, `old_money_service`, `new_money_service`, `memo`, `user_id`, `club_id`)
			 select money_real, money_real, money_service  - ROUND(IF(@allin_service_max_offset := IF(u.allin_service_config = 'C', c.allin_service_max_offset, u.u_allin_service_max_offset) =0, @crprofit := (IF(profit IS NULL, 0, profit) + IF (cut_money_all IS NULL, 0, cut_money_all)) * (@allin_service := IF(u.allin_service_config = 'C', c.allin_service, u.u_allin_service)/100), IF(@allin_service_max_offset >= @crprofit,@crprofit * (@allin_service/100), @allin_service_max_offset * (@allin_service/100)))) , money_service, '손실금(" . $sdate . ") 자동 지급으로 인한 변동', u.user_id, u.club_id from user_info as u left outer join club_info c on u.club_id = c.id left outer join (select u.user_id, sum(`o`.`bet_money`) AS `bet_money_all`, sum(if((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) AS `bet_money_real_all`,sum(if((`o`.`money_type` = 'S'),`o`.`bet_money`,0)) AS `bet_money_service_all`,sum(`o`.`service_money`) AS `service_money_all`,sum(`o`.`result_money`) AS `result_money_all`, (sum(if((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) - sum(`o`.`result_money`)) AS `profit` from `order` as o left outer join `user_info` as `u` on `u`.user_id = o.user_id where ((`o`.`stat` != 'C') and (`o`.`stat` != 'R')) and o.buy_time >= ? and o.buy_time <= ?	group by u.user_id) as o on u.user_id = o.user_id LEFT OUTER JOIN (SELECT user_id u_id, SUM(`cut_money`) AS `cut_money_all` FROM cut_result_money WHERE c_date >= DATE(?) AND c_date < DATE(?) GROUP BY user_id) AS cr ON u.user_id = cr.u_id WHERE c.allin_service_isauto = 'Y' AND u.allin_service_config != 'N' AND u.user_level < 50 AND o.bet_money_all > 0 AND (IF(profit IS NULL, 0, profit) + IF (cut_money_all IS NULL, 0, cut_money_all)) >= IF(u.allin_service_config = 'C', c.allin_service_min_offset, u.u_allin_service_min_offset)";
	$stmt = $db->prepare( $sql );
	$stmt->execute( array( $sdate, $edate, $sdate, $edate ) );
	
	$db->commit();
	$data[ 'Ok' ] = 'Updated';
} catch ( Exception $e ) {
	$db->rollBack();
	$data[ 'Ok' ] = 'Error';
	$data[ 'Error' ] = '지급중 오류'; //$e->getMessage();//
}
echo json_encode( $data, JSON_UNESCAPED_UNICODE );
?>