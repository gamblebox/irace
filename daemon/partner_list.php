<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

extract($_POST);
$edate = date('Y-m-d', strtotime($edate . '+' . '1' . ' days'));
$a = $action;
if (!isset($_SESSION)) {
	session_start();
}
$user_id = $_SESSION['user_id'];
if ($_SESSION['club_id'] !== '255') {
	$club_id = (int) $_SESSION['club_id'];
}
$user_level = $_SESSION['user_level'];
$club_code = $_SESSION['club_code'];
$club_id = (int)$club_id;
session_write_close();

// $club_id=1;
// $date = date('Y-m-d');
// $sdate = $date;
// $edate = $date;
// $user_level = 100;
// $club_code = 'gold';
// $action = '';
include(__DIR__ . '/../../../application/configs/configdb.php');
$db = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8mb4', $user, $password, array(
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_EMULATE_PREPARES => false,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
));

// if ( $action == 'edit' ){
// $partner_idx = (int)str_replace('row_', '', key($data));
// $sql = "select p.user_id, p.partner_calculate_rule, p.partner_calculate_ratio, p.partner_calculate_sf_win_ratio, p.partner_calculate_sf_lose_ratio, p.partner_calculate_mf_win_ratio, p.partner_calculate_mf_lose_ratio from user_info p where p.user_id = (select partner_id from user_info where id = ?)";
// $stmt = $db->prepare($sql);
// $stmt->execute(array($partner_idx));
// $partner_infos = $stmt->fetch();
// }
if (isset($data)) {
	$key = key($data);
	if ($key == '0') {
		$up_partner_id = $data[0]['partner_id'];
		$edit_user_id = $data[0]['user_id'];
	} else {
		$user_idx = (int) str_replace('row_', '', $key);
		$sql = "select user_id, partner_id from user_info where id = ?";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(
			$user_idx
		));
		$result = $stmt->fetch();
		$up_partner_id = $result->partner_id;
		$edit_user_id = $result->user_id;
	}

	$sql = "select p.user_id, p.partner_calculate_rule, p.partner_calculate_ratio, p.partner_calculate_sf_win_ratio, p.partner_calculate_sf_lose_ratio, p.partner_calculate_mf_win_ratio, p.partner_calculate_mf_lose_ratio from user_info p where p.user_id = ?";
	$stmt = $db->prepare($sql);
	$stmt->execute(array(
		$up_partner_id
	));
	$partner_infos = $stmt->fetch();
}

// print_r($partner_infos);

/*
 * Example PHP implementation used for the index.html example
 */

// DataTables PHP library
include("DataTables.php");

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
$editor = Editor::inst($db, 'user_info')->fields(
	// 	->fields(
	//Field::inst( 'banking_type' ),
	Field::inst('nick_name'),
	Field::inst('money_point'),
	Field::inst('user_id')->validator('Validate::notEmpty', array('message' => '아이디는 필수로 입력하여야 합니다'))->validator('Validate::unique', array('message' => '아이디는 중복 되지 않아야 합니다')),
	Field::inst('club_user_id'),
	Field::inst('club_id'),
	Field::inst('user_pw'),
	Field::inst('user_level'),
	Field::inst('partner_id')->options(
		'user_info',
		'user_id',
		array('user_id', 'club_user_id', 'nick_name'),
		function ($q) {
			global $club_id;
			global $user_level;
			global $user_id;
			$q->where('club_id', $club_id, ($club_id === 255 ? '!=' : '='));
			$q->where('user_level', 90, '>=');
			$q->where('user_level', 100, '<');
			if ($user_level < 100) {
				$q->where(function ($r) {
					global $user_id;
					$r->where('partner_id', "( with recursive cte ( user_id, partner_id ) as ( select user_id, partner_id from user_info where partner_id = '" . $user_id . "' union all select r.user_id, r.partner_id from user_info r inner join cte on r.partner_id = cte.user_id ) select distinct partner_id from cte )", 'IN', false);
					$r->or_where('user_id', $user_id);
				});
			}
		},

		function ($row) {
			global $club_id;
			//return $row['club_user_id'].' ('.$row['nick_name'].')';
			if ($club_id === 255) {
				return $row['user_id'] . '(' . $row['nick_name'] . ')';
			} else {
				return $row['club_user_id'] . '(' . $row['nick_name'] . ')';
			}
		}
	),
	Field::inst('partner_level'),
	Field::inst('partner_class_id')->options('partner_class', 'id', 'cd_name', function ($q) {
		global $club_id;
		$q->where('club_id', $club_id, ($club_id === 255 ? '!=' : '='));
	})->setFormatter('Format::nullEmpty'),
	Field::inst('partner_calculate_rule'),
	Field::inst('partner_calculate_ratio'),
	Field::inst('partner_calculate_sf_win_ratio')->validator(function ($val, $data, $field, $host) {
		global $partner_infos;
		return ($partner_infos && $val > $partner_infos->partner_calculate_sf_win_ratio) ?	'상위 롤링 (' . $partner_infos->partner_calculate_sf_win_ratio . ') 보다 많을 수 없습니다' :	true;
	}),
	Field::inst('partner_calculate_sf_lose_ratio')->validator(function ($val, $data, $field, $host) {
		global $partner_infos;
		return ($partner_infos && $val > $partner_infos->partner_calculate_sf_lose_ratio) ?	'상위 롤링 (' . $partner_infos->partner_calculate_sf_lose_ratio . ') 보다 많을 수 없습니다' :	true;
	}),
	Field::inst('partner_calculate_mf_win_ratio')->validator(function ($val, $data, $field, $host) {
		global $partner_infos;
		return ($partner_infos && $val > $partner_infos->partner_calculate_mf_win_ratio) ?	'상위 롤링 (' . $partner_infos->partner_calculate_mf_win_ratio . ') 보다 많을 수 없습니다' :	true;
	}),
	Field::inst('partner_calculate_mf_lose_ratio')->validator(function ($val, $data, $field, $host) {
		global $partner_infos;
		return ($partner_infos && $val > $partner_infos->partner_calculate_mf_lose_ratio) ?	'상위 롤링 (' . $partner_infos->partner_calculate_mf_lose_ratio . ') 보다 많을 수 없습니다' :	true;
	}),
	Field::inst('phone'),
	Field::inst('memo'),
	Field::inst('r_time')->setFormatter('Format::ifEmpty', '2000-01-01'),
	Field::inst('u_time')->setFormatter('Format::ifEmpty', '2000-01-01')
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
	->where('club_id', $club_id, ($club_id === '255' ? '!=' : '='))
	->process($_POST);
// ->json();
$d = json_decode($editor->json(false));


// include (__DIR__ . '/../../../application/configs/configdb.php');


$db = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8mb4', $user, $password, array(
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_EMULATE_PREPARES => false,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
));

// echo $data[$key]['partner_calculate_rule'];
//==================
if (isset($data)) {
	if (isset($data[$key]['partner_calculate_rule'])) {
		$sql = "UPDATE user_info set partner_calculate_rule = ? where user_id in (WITH recursive cte (user_id, partner_id, user_level) AS (
 SELECT user_id, partner_id, user_level FROM user_info WHERE user_id = ? UNION ALL SELECT r.user_id, r.partner_id, r.user_level FROM user_info r INNER JOIN cte ON r.partner_id = cte.user_id AND r.user_level = 90) SELECT DISTINCT user_id FROM cte WHERE user_level = 90 ) and user_id != ?";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(($data[$key]['partner_calculate_rule']), $edit_user_id, $edit_user_id));
	}
	if (isset($data[$key]['partner_calculate_sf_win_ratio'])) {
		$sql = "UPDATE user_info set partner_calculate_sf_win_ratio = if (partner_calculate_sf_win_ratio > ?, ?, partner_calculate_sf_win_ratio) where user_id in (WITH recursive cte (user_id, partner_id, user_level) AS (
 SELECT user_id, partner_id, user_level FROM user_info WHERE user_id = ? UNION ALL SELECT r.user_id, r.partner_id, r.user_level FROM user_info r INNER JOIN cte ON r.partner_id = cte.user_id AND r.user_level = 90) SELECT DISTINCT user_id FROM cte WHERE user_level = 90 ) and user_id != ?";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($data[$key]['partner_calculate_sf_win_ratio'], $data[$key]['partner_calculate_sf_win_ratio'], $edit_user_id, $edit_user_id));
	}
	if (isset($data[$key]['partner_calculate_sf_lose_ratio'])) {
		$sql = "UPDATE user_info set partner_calculate_sf_lose_ratio = if (partner_calculate_sf_lose_ratio > ?, ?, partner_calculate_sf_lose_ratio) where user_id in (WITH recursive cte (user_id, partner_id, user_level) AS (
 SELECT user_id, partner_id, user_level FROM user_info WHERE user_id = ? UNION ALL SELECT r.user_id, r.partner_id, r.user_level FROM user_info r INNER JOIN cte ON r.partner_id = cte.user_id AND r.user_level = 90) SELECT DISTINCT user_id FROM cte WHERE user_level = 90 ) and user_id != ?";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($data[$key]['partner_calculate_sf_lose_ratio'], $data[$key]['partner_calculate_sf_lose_ratio'], $edit_user_id, $edit_user_id));
	}
	if (isset($data[$key]['partner_calculate_mf_win_ratio'])) {
		$sql = "UPDATE user_info set partner_calculate_mf_win_ratio = if (partner_calculate_mf_win_ratio > ?, ?, partner_calculate_sf_win_ratio) where user_id in (WITH recursive cte (user_id, partner_id, user_level) AS (
 SELECT user_id, partner_id, user_level FROM user_info WHERE user_id = ? UNION ALL SELECT r.user_id, r.partner_id, r.user_level FROM user_info r INNER JOIN cte ON r.partner_id = cte.user_id AND r.user_level = 90) SELECT DISTINCT user_id FROM cte WHERE user_level = 90 ) and user_id != ?";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($data[$key]['partner_calculate_mf_win_ratio'], $data[$key]['partner_calculate_mf_win_ratio'], $edit_user_id, $edit_user_id));
	}
	if (isset($data[$key]['partner_calculate_mf_lose_ratio'])) {
		$sql = "UPDATE user_info set partner_calculate_mf_lose_ratio = if (partner_calculate_mf_lose_ratio > ?, ?, partner_calculate_mf_lose_ratio) where user_id in (WITH recursive cte (user_id, partner_id, user_level) AS (
 SELECT user_id, partner_id, user_level FROM user_info WHERE user_id = ? UNION ALL SELECT r.user_id, r.partner_id, r.user_level FROM user_info r INNER JOIN cte ON r.partner_id = cte.user_id AND r.user_level = 90) SELECT DISTINCT user_id FROM cte WHERE user_level = 90 ) and user_id != ?";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($data[$key]['partner_calculate_mf_lose_ratio'], $data[$key]['partner_calculate_mf_lose_ratio'], $edit_user_id, $edit_user_id));
	}
}

// ====================
$data = array();

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
	$partner_id_list = array(
		$user_id
	);
}
// echo $partner_id_list;

$i = 0;
while (count($partner_id_list) > 0) {
	$c = 0;
	$next_partner_id_list = array();
	foreach ($partner_id_list as $partner_id) {
		// 		echo '$partner_id ' . $partner_id . PHP_EOL;
		$sql = "select user_id from user_info where user_level = 90 and partner_id = '" . $partner_id . "'";
		$stmt = $db->prepare($sql);
		$stmt->execute();
		$result = $stmt->fetchAll();
		foreach ($result as $value) {
			array_push($next_partner_id_list, $value->user_id);
		}
		$partner_line_list =  array();
		$sql = "WITH recursive cte (user_id, partner_id, user_level) AS (
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
			array_push($partner_line_list, $value->partner_id);
		}
		array_unshift($partner_line_list, $partner_id);
		$partner_line = "'" . implode("','", $partner_line_list) . "'";
		// 					echo '$partner_line ' . $partner_line . PHP_EOL;
		$sql = "SELECT STRAIGHT_JOIN CONCAT('row_', p.id) AS DT_RowId, p.id, p.user_id, p.club_id, p.user_pw, p.money_point, p.memo, p.partner_id, p.partner_level, p.user_level, p.club_user_id, pc.id as partner_class_id, pc.cd_name, p.nick_name, p.phone, COUNT(u.user_id) AS user_count, p.phone AS phone, p.partner_calculate_rule, p.partner_calculate_ratio, p.partner_calculate_sf_win_ratio, p.partner_calculate_sf_lose_ratio, p.partner_calculate_mf_win_ratio, p.partner_calculate_mf_lose_ratio, p.r_time, SUM(u.money_real) AS money_real_all, SUM(u.money_service) AS money_service_all, b.deposit_money_all, b.withdraw_money_all, o.bet_money_all, o.bet_money_real_all, o.bet_money_service_all, o.service_money_all, o.result_money_all, o.profit, cr.cut_money_all
FROM user_info p
LEFT OUTER
JOIN partner_class AS pc ON p.partner_class_id = pc.id 
LEFT OUTER
JOIN user_info AS u ON u.partner_id IN (" . $partner_line . ") AND u.user_level < 50
LEFT OUTER
JOIN club_info AS c ON p.club_id = c.id
LEFT OUTER
JOIN (
SELECT '" . $partner_id . "' p_id, SUM(IF(b.banking_type = 'I' AND b.stat = 'E',b.amount,0)) AS `deposit_money_all`, SUM(IF(b.banking_type = 'O' AND b.stat = 'E',b.amount,0)) AS `withdraw_money_all`
FROM user_info u
LEFT OUTER
JOIN `banking` b ON u.user_id = b.user_id
WHERE b.u_time >= DATE('" . $sdate . "') AND b.u_time < DATE('" . $edate . "') AND u.partner_id IN (" . $partner_line . ")
) AS b ON u.partner_id = b.p_id
LEFT OUTER
JOIN (
SELECT '" . $partner_id . "' p_id, SUM(o.`bet_money`) AS `bet_money_all`, SUM(IF((o.`money_type` = 'R'),o.`bet_money`,0)) AS `bet_money_real_all`, SUM(IF((o.`money_type` = 'S'),o.`bet_money`,0)) AS `bet_money_service_all`, SUM(o.`service_money`) AS `service_money_all`, SUM(o.`result_money`) AS `result_money_all`,(SUM(IF((o.`money_type` = 'R'),o.`bet_money`,0)) - SUM(o.`result_money`)) AS `profit`
FROM user_info u
LEFT OUTER
JOIN `order`o ON u.user_id = o.user_id
WHERE o.`stat` NOT IN ('C', 'R') AND o.buy_time >= DATE('" . $sdate . "') AND o.buy_time < DATE('" . $edate . "') AND u.partner_id IN (" . $partner_line . ")
) AS o ON u.partner_id = o.p_id
LEFT OUTER
JOIN (
SELECT '" . $partner_id . "' p_id, SUM(`cut_money`) AS `cut_money_all`
FROM user_info u
LEFT OUTER
JOIN `cut_result_money` c ON u.user_id = c.user_id 
LEFT OUTER JOIN race AS r ON r.id = race_id 
WHERE r.start_time >= DATE('" . $sdate . "') AND r.start_time <= DATE('" . $edate . "') AND u.partner_id IN (" . $partner_line . ")
) AS cr ON u.partner_id = cr.p_id
WHERE p.user_level = 90 AND p.user_id = '" . $partner_id . "'";
		// 					echo '$sql ' . $sql . PHP_EOL;
		$stmt = $db->prepare($sql);
		$stmt->execute();
		$result = $stmt->fetchAll();
		// 				print_r($result);
		if (count($result) > 0) {
			$data[$i][] = $result[0];
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
for ($i = count($data) - 1; $i > 0; $i--) {
	foreach ($data[$i] as $value) {
		$key = array_search($value->partner_id, array_column($data[$i - 1], 'user_id'));
		// 				echo '$key ' . $key . PHP_EOL;
		// 				anykey();
		$data[$i - 1][$key]->children[] = $value;
	}
}
// print_r($data);
if ($data) {
	$data = $data[0];
}
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
	$d->data = $data;
}
echo json_encode($d, JSON_UNESCAPED_UNICODE);
