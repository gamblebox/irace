<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

extract( $_POST );
$a = $action;
if ( !isset( $_SESSION ) ) {
	session_start();
}
$user_id = $_SESSION['user_id'];
if ($_SESSION['club_id'] !== '255') {
	$club_id = (int) $_SESSION['club_id'];
}
$user_level = $_SESSION['user_level'];
$club_code = $_SESSION[ 'club_code' ];
$club_id = ( int )$club_id;


// $club_id=1;
// $date = date('Y-m-d');
// $sdate = $date;
// $edate = $date;
// $user_level = 100;
// $club_code = 'gold';
// $action = '';

/*
 * Example PHP implementation used for the index.html example
 */

// DataTables PHP library
include( "DataTables.php" );

// Alias Editor classes so they are easy to use
use
	DataTables\Editor,
	DataTables\Editor\Field,
	DataTables\Editor\Format,
	DataTables\Editor\Mjoin,
	DataTables\Editor\Upload,
	DataTables\Editor\Validate;

// Build our Editor instance and process the data coming from _POST
// Editor::inst( $db, 'user_info' )
$editor = Editor::inst( $db, 'user_info' )->fields(
// 	->fields(
		//Field::inst( 'banking_type' ),
		Field::inst( 'nick_name' ),
		//Field::inst( 'club_id' ),
// 		Field::inst( 'user_id' )->validator( 'Validate::notEmpty' )->validator( 'Validate::unique' ),
		Field::inst( 'user_id' )->validator( 'Validate::notEmpty', array( 'message' => '아이디는 필수로 입력하여야 합니다' ) )->validator( 'Validate::unique', array( 'message' => '아이디는 중복 되지 않아야 합니다' ) ),
		Field::inst( 'club_user_id' ),
		Field::inst( 'club_id' ),
		Field::inst( 'user_pw' ),
		Field::inst( 'user_level' ),
	Field::inst( 'partner_id' ),
		Field::inst( 'partner_level' ),
	Field::inst( 'partner_class_id' )->options( 'partner_class', 'id', 'cd_name', function ( $q ) {
		global $club_id;
		$q->where( 'club_id', $club_id, ( $club_id === 255 ? '!=' : '=' ) );
	} )->setFormatter( 'Format::nullEmpty' ),
		Field::inst( 'partner_calculate_rule' ),
		Field::inst( 'partner_calculate_ratio' ),
		Field::inst( 'phone' ),
		Field::inst( 'memo' ),
	Field::inst( 'r_time' )->setFormatter( 'Format::ifEmpty', '2000-01-01' ),
	Field::inst( 'u_time' )->setFormatter( 'Format::ifEmpty', '2000-01-01' )
)
	->on('preCreate', function ($editor, $values) {
	global $club_id;
	global $club_code;
	$editor->field('club_id')
		->setValue($club_id);
	$editor->field('user_level')
		->setValue(90);
// 	$user_id = '_' . $club_code . '_' . $values[ 'club_user_id' ];
// 	$editor->field( 'user_id' )->setValue( $user_id );
	
})
	->on('preEdit', function ($editor, $id, $values) {
	global $club_code;
	if (isset($values['club_user_id'])) {
		$user_id = '_' . $club_code . '_' . $values['club_user_id'];
		$editor->field('user_id')
			->setValue($user_id);
	}
})
/* 	->on( 'preCreate', function ( $editor, $values ) {
			global $club_id ;
			global $club_code ;
			$editor
					->field( 'club_id' )
					->setValue( $club_id );
			$editor
					->field( 'user_level' )
					->setValue(90);
			$partner_id =  '_' . $club_code . '_' . $values['club_partner_id'];
			$editor
					->field( 'user_id' )
					->setValue($partner_id);
	} )
	->on( 'preEdit', function ( $editor, $id, $values) {
			global $club_code ;
			if (isset($values['club_partner_id'])){
				$partner_id =  '_' . $club_code . '_' . $values['club_partner_id'];
				$editor
						->field( 'user_id' )
						->setValue($partner_id);
			}
	} )	 */		
  //->leftJoin( 'club_info',     'club_id',          '=', 'club_info.id' )
	//->leftJoin( 'user_info', 'user_info.user_id',          '=', 'bbs_qna.user_id' )	
	//->leftJoin( 'view_order_sum', 'user_id',          '=', 'view_order_sum.user_id' )	

	->where('club_id', $club_id, ($club_id === '255' ? '!=' : '='))
	->
// ->where( 'date(bbs_qna.u_time)',date('Y-m-d',strtotime(date('Y-m-d').'-2 days')), '>')
// ->where( 'date(order.buy_time)',date('Y-m-d',strtotime(date('Y-m-d').'-7 days')), '>')

process($_POST);
// ->json();
$d = json_decode($editor->json(false));
	
	
include (__DIR__ . '/../../../application/configs/configdb.php');
$data = array();

$db = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8mb4', $user, $password, array(
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_EMULATE_PREPARES => false,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
));

	// ====================
if ($user_level > 99) {
	$partner_id_list = array();
	$sql = "select user_id from user_info where user_level = 90 and partner_id = '' and club_id " . ($club_id ==	255 ? '!= ' : '= ') . $club_id;
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$result = $stmt->fetchAll();
	foreach ($result as $value) {
		array_push($partner_id_list, $value->user_id);
	}
} else {
	// $root_partner_id = $user_id;
	// $root_partner_id = $user_id;
	$partner_id_list = array(
		$user_id
	);
}
// echo $partner_id_list;
	
	// 	$data = array();
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
			/* 			$sql = "SELECT STRAIGHT_JOIN CONCAT('row_', p.id) AS DT_RowId, p.id, p.user_id, p.club_id, p.user_pw, p.partner_id, p.partner_level
			 ,p.user_level, p.club_user_id, p.nick_name, p.phone, COUNT(DISTINCT u.user_id) AS user_count, p.phone AS phone, p.partner_calculate_rule
			 , p.partner_calculate_ratio, p.r_time, SUM(DISTINCT u.money_real) AS money_real_all, SUM(DISTINCT u.money_service) AS money_service_all
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
			 GROUP BY p.user_id"; */
			$sql = "SELECT STRAIGHT_JOIN CONCAT('row_', p.id) AS DT_RowId, p.id, p.user_id, p.club_id, p.user_pw, p.partner_id, p.partner_level, p.user_level, p.club_user_id, pc.id as partner_class_id, pc.cd_name, p.nick_name, p.phone, COUNT(u.user_id) AS user_count, p.phone AS phone, p.partner_calculate_rule, p.partner_calculate_ratio, p.r_time, SUM(u.money_real) AS money_real_all, SUM(u.money_service) AS money_service_all, b.deposit_money_all, b.withdraw_money_all, o.bet_money_all, o.bet_money_real_all, o.bet_money_service_all, o.service_money_all, o.result_money_all, o.profit, cr.cut_money_all
FROM user_info p
LEFT OUTER
JOIN partner_class AS pc ON p.partner_class_id = pc.id 
LEFT OUTER
JOIN user_info AS u ON u.partner_id IN (" . $partner_line . ") AND u.user_level < 50
LEFT OUTER
JOIN club_info AS c ON p.club_id = c.id
LEFT OUTER
JOIN (
SELECT u.partner_id p_id, SUM(IF(b.banking_type = 'I' AND b.stat = 'E',b.amount,0)) AS `deposit_money_all`, SUM(IF(b.banking_type = 'O' AND b.stat = 'E',b.amount,0)) AS `withdraw_money_all`
FROM user_info u
LEFT OUTER
JOIN `banking` b ON u.user_id = b.user_id
WHERE DATE(b.u_time) >= DATE('" . $sdate . "') AND DATE(b.u_time) <= DATE('" . $edate . "') AND u.partner_id IN (" . $partner_line . ")
) AS b ON u.partner_id = b.p_id
LEFT OUTER
JOIN (
SELECT u.partner_id p_id, SUM(o.`bet_money`) AS `bet_money_all`, SUM(IF((o.`money_type` = 'R'),o.`bet_money`,0)) AS `bet_money_real_all`, SUM(IF((o.`money_type` = 'S'),o.`bet_money`,0)) AS `bet_money_service_all`, SUM(o.`service_money`) AS `service_money_all`, SUM(o.`result_money`) AS `result_money_all`,(SUM(IF((o.`money_type` = 'R'),o.`bet_money`,0)) - SUM(o.`result_money`)) AS `profit`
FROM user_info u
LEFT OUTER
JOIN `order`o ON u.user_id = o.user_id
WHERE o.`stat` NOT IN ('C', 'R') AND DATE(o.buy_time) >= DATE('" . $sdate . "') AND DATE(o.buy_time) <= DATE('" . $edate . "') AND u.partner_id IN (" . $partner_line . ")
) AS o ON u.partner_id = o.p_id
LEFT OUTER
JOIN (
SELECT u.partner_id p_id, SUM(`cut_money`) AS `cut_money_all`
FROM user_info u
LEFT OUTER
JOIN `cut_result_money` c ON u.user_id = c.user_id
WHERE DATE(c_date) >= DATE('" . $sdate . "') AND DATE(c_date) <= DATE('" . $edate . "') AND u.partner_id IN (" . $partner_line . ")
) AS cr ON u.partner_id = cr.p_id
WHERE p.user_level = 90 AND p.user_id = '" . $partner_id . "'";
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
	// print_r($data);
	if ($data[0]) {
		$data = $data[0];
	}
	
	
	
	/* 
	foreach ($partner_id_list as $partner_id) {
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
		
// 		$sql = "select STRAIGHT_JOIN concat('row_', p.id) as DT_RowId, p.id, p.user_id, p.club_id, p.user_pw, p.partner_id, p.partner_level, p.user_level, p.club_user_id, p.nick_name, p.phone, count(u.user_id) as user_count, p.phone as phone, p.partner_calculate_rule, p.partner_calculate_ratio, p.r_time, sum(u.money_real) as money_real_all, sum(u.money_service) as money_service_all, b.deposit_money_all, b.withdraw_money_all, o.bet_money_all, o.bet_money_real_all, o.bet_money_service_all, o.service_money_all, o.result_money_all, o.profit, cr.cut_money_all from (select * from user_info where user_level = 90) as p LEFT OUTER JOIN user_info AS u ON u.partner_id = p.user_id left outer join club_info as c on p.club_id = c.id left outer join (select u.partner_id p_id, sum(if(b.banking_type = 'I' and b.stat = 'E',b.amount,0)) AS `deposit_money_all`, sum(if(b.banking_type = 'O' and b.stat = 'E',b.amount,0)) AS `withdraw_money_all` from user_info u left outer join `banking` b on u.user_id = b.user_id where date(b.u_time) >= date('" . $sdate . "') and  date(b.u_time) <= date('" . $edate . "') and u.partner_id != '' group by u.partner_id ) as b on u.partner_id = b.p_id left outer join (select u.partner_id p_id, sum(o.`bet_money`) AS `bet_money_all`,sum(if((o.`money_type` = 'R'),o.`bet_money`,0)) AS `bet_money_real_all`,sum(if((o.`money_type` = 'S'),o.`bet_money`,0)) AS `bet_money_service_all`,sum(o.`service_money`) AS `service_money_all`,sum(o.`result_money`) AS `result_money_all`,(sum(if((o.`money_type` = 'R'),o.`bet_money`,0)) - sum(o.`result_money`)) AS `profit` from user_info u left outer join `order`o on u.user_id = o.user_id  where o.`stat` not in ('C', 'R') and date(o.buy_time) >= date('" . $sdate . "') and  date(o.buy_time) <= date('" . $edate . "') and u.partner_id != '' group by u.partner_id) as o on u.partner_id = o.p_id LEFT OUTER JOIN (SELECT u.partner_id p_id, SUM(`cut_money`) AS `cut_money_all` FROM user_info u left outer join `cut_result_money` c on u.user_id = c.user_id  WHERE DATE(c_date) >= DATE('" . $sdate . "') AND DATE(c_date) <= DATE('" . $edate . "') and u.partner_id != '' GROUP BY u.partner_id) AS cr ON u.partner_id = cr.p_id where c.club_level > 0 and c.id " . ($club_id === '255' ? '!= ' : '= ') . $club_id . "";
		$sql = "SELECT STRAIGHT_JOIN CONCAT('row_', p.id) AS DT_RowId, p.id, p.user_id, p.club_id, p.user_pw, p.partner_id, p.partner_level, p.user_level, p.club_user_id, p.nick_name, p.phone, COUNT(u.user_id) AS user_count, p.phone AS phone, p.partner_calculate_rule, p.partner_calculate_ratio, p.r_time, SUM(u.money_real) AS money_real_all, SUM(u.money_service) AS money_service_all, b.deposit_money_all, b.withdraw_money_all, o.bet_money_all, o.bet_money_real_all, o.bet_money_service_all, o.service_money_all, o.result_money_all, o.profit, cr.cut_money_all
FROM user_info p
LEFT OUTER
JOIN user_info AS u ON u.partner_id IN (" . $partner_line . ") AND u.user_level < 50
LEFT OUTER
JOIN club_info AS c ON p.club_id = c.id
LEFT OUTER
JOIN (
SELECT u.partner_id p_id, SUM(IF(b.banking_type = 'I' AND b.stat = 'E',b.amount,0)) AS `deposit_money_all`, SUM(IF(b.banking_type = 'O' AND b.stat = 'E',b.amount,0)) AS `withdraw_money_all`
FROM user_info u
LEFT OUTER
JOIN `banking` b ON u.user_id = b.user_id
WHERE DATE(b.u_time) >= DATE('" . $sdate . "') AND DATE(b.u_time) <= DATE('" . $edate . "') AND u.partner_id IN (" . $partner_line . ")
) AS b ON u.partner_id = b.p_id
LEFT OUTER
JOIN (
SELECT u.partner_id p_id, SUM(o.`bet_money`) AS `bet_money_all`, SUM(IF((o.`money_type` = 'R'),o.`bet_money`,0)) AS `bet_money_real_all`, SUM(IF((o.`money_type` = 'S'),o.`bet_money`,0)) AS `bet_money_service_all`, SUM(o.`service_money`) AS `service_money_all`, SUM(o.`result_money`) AS `result_money_all`,(SUM(IF((o.`money_type` = 'R'),o.`bet_money`,0)) - SUM(o.`result_money`)) AS `profit`
FROM user_info u
LEFT OUTER
JOIN `order`o ON u.user_id = o.user_id
WHERE o.`stat` NOT IN ('C', 'R') AND DATE(o.buy_time) >= DATE('" . $sdate . "') AND DATE(o.buy_time) <= DATE('" . $edate . "') AND u.partner_id IN (" . $partner_line . ")
) AS o ON u.partner_id = o.p_id
LEFT OUTER
JOIN (
SELECT u.partner_id p_id, SUM(`cut_money`) AS `cut_money_all`
FROM user_info u
LEFT OUTER
JOIN `cut_result_money` c ON u.user_id = c.user_id
WHERE DATE(c_date) >= DATE('" . $sdate . "') AND DATE(c_date) <= DATE('" . $edate . "') AND u.partner_id IN (" . $partner_line . ")
) AS cr ON u.partner_id = cr.p_id
WHERE p.user_level = 90 AND p.user_id = '" . $partner_id . "'";
		$stmt = $db->prepare($sql);
		$stmt->execute();
		$result = $stmt->fetch();
		$data[] = $result;
	}
	 */
	
/* } else {
	// $root_partner_id = $user_id;
	$root_partner_id = $user_id;
	$partner_id_list = array($root_partner_id);
	
// 	$data = array();
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
/* 			$sql = "SELECT STRAIGHT_JOIN CONCAT('row_', p.id) AS DT_RowId, p.id, p.user_id, p.club_id, p.user_pw, p.partner_id, p.partner_level
,p.user_level, p.club_user_id, p.nick_name, p.phone, COUNT(DISTINCT u.user_id) AS user_count, p.phone AS phone, p.partner_calculate_rule
, p.partner_calculate_ratio, p.r_time, SUM(DISTINCT u.money_real) AS money_real_all, SUM(DISTINCT u.money_service) AS money_service_all
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
			$sql = "SELECT STRAIGHT_JOIN CONCAT('row_', p.id) AS DT_RowId, p.id, p.user_id, p.club_id, p.user_pw, p.partner_id, p.partner_level, p.user_level, p.club_user_id, p.nick_name, p.phone, COUNT(u.user_id) AS user_count, p.phone AS phone, p.partner_calculate_rule, p.partner_calculate_ratio, p.r_time, SUM(u.money_real) AS money_real_all, SUM(u.money_service) AS money_service_all, b.deposit_money_all, b.withdraw_money_all, o.bet_money_all, o.bet_money_real_all, o.bet_money_service_all, o.service_money_all, o.result_money_all, o.profit, cr.cut_money_all
FROM user_info p
LEFT OUTER
JOIN user_info AS u ON u.partner_id IN (" . $partner_line . ") AND u.user_level < 50
LEFT OUTER
JOIN club_info AS c ON p.club_id = c.id
LEFT OUTER
JOIN (
SELECT u.partner_id p_id, SUM(IF(b.banking_type = 'I' AND b.stat = 'E',b.amount,0)) AS `deposit_money_all`, SUM(IF(b.banking_type = 'O' AND b.stat = 'E',b.amount,0)) AS `withdraw_money_all`
FROM user_info u
LEFT OUTER
JOIN `banking` b ON u.user_id = b.user_id
WHERE DATE(b.u_time) >= DATE('" . $sdate . "') AND DATE(b.u_time) <= DATE('" . $edate . "') AND u.partner_id IN (" . $partner_line . ")
) AS b ON u.partner_id = b.p_id
LEFT OUTER
JOIN (
SELECT u.partner_id p_id, SUM(o.`bet_money`) AS `bet_money_all`, SUM(IF((o.`money_type` = 'R'),o.`bet_money`,0)) AS `bet_money_real_all`, SUM(IF((o.`money_type` = 'S'),o.`bet_money`,0)) AS `bet_money_service_all`, SUM(o.`service_money`) AS `service_money_all`, SUM(o.`result_money`) AS `result_money_all`,(SUM(IF((o.`money_type` = 'R'),o.`bet_money`,0)) - SUM(o.`result_money`)) AS `profit`
FROM user_info u
LEFT OUTER
JOIN `order`o ON u.user_id = o.user_id
WHERE o.`stat` NOT IN ('C', 'R') AND DATE(o.buy_time) >= DATE('" . $sdate . "') AND DATE(o.buy_time) <= DATE('" . $edate . "') AND u.partner_id IN (" . $partner_line . ")
) AS o ON u.partner_id = o.p_id
LEFT OUTER
JOIN (
SELECT u.partner_id p_id, SUM(`cut_money`) AS `cut_money_all`
FROM user_info u
LEFT OUTER
JOIN `cut_result_money` c ON u.user_id = c.user_id
WHERE DATE(c_date) >= DATE('" . $sdate . "') AND DATE(c_date) <= DATE('" . $edate . "') AND u.partner_id IN (" . $partner_line . ")
) AS cr ON u.partner_id = cr.p_id
WHERE p.user_level = 90 AND p.user_id = '" . $partner_id . "'";
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
	// print_r($data);
	
	$data = $data[0];
}
 */

if (count($d->data) !== 0) {
	if ($a === Editor::ACTION_CREATE) {
		$sql .= " and p.id = " . str_replace('row_', '', $d->data[0]->DT_RowId);
		// $a = 1;
	} else if ($a === Editor::ACTION_EDIT) {
		foreach ($d->data as $value) {
			$ids[] = str_replace('row_', '', $value->DT_RowId);
		}
		$sql .= " and p.id in (" . implode(',', $ids) . ")";
	}
// 	if ($user_level > 99) {
// 		$sql .= " group by p.user_id";
// 		$data = array();
// 		$stmt = $db->prepare($sql);
// 		$stmt->execute();
// 		$data = $stmt->fetchAll();
// 	}

// 	foreach ($data as $key => $value) {
// 		$data[$key]->u_ticketing_association = explode(',', $value->u_ticketing_association);
// 		$data[$key]->u_korea_race_ticketing_type = explode(',', $value->u_korea_race_ticketing_type);
// 		$data[$key]->u_japan_race_ticketing_type = explode(',', $value->u_japan_race_ticketing_type);
// 		$data[$key]->u_cycle_race_ticketing_type = explode(',', $value->u_cycle_race_ticketing_type);
// 		$data[$key]->u_boat_race_ticketing_type = explode(',', $value->u_boat_race_ticketing_type);
// 	}
	$d->data = $data;
}
// $d->data = $sql;
echo json_encode($d, JSON_UNESCAPED_UNICODE);
	// 접속 종료
