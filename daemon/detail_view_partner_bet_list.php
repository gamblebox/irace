<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

extract($_POST);
$edate = date('Y-m-d', strtotime($edate . '+' . '1' . ' days'));
if (!isset($_SESSION)) {
	session_start();
}
$club_id = $_SESSION['club_id'];
$user_level = $_SESSION['user_level'];
session_write_close();

include(__DIR__ . '/../../../application/configs/configdb.php');
$db = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8mb4', $user, $password, array(
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_EMULATE_PREPARES => false,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
));

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

//select u.club_user_id, u.nick_name,  p.name as place_name , r.race_no, o.`type`, o.place_1, o.place_2, o.place_3, o.bet_money, o.service_money, o.money_type, o.result_money, o.update_time, o.stat from `order` as o left outer join `race` as r on r.id=o.race_id  left outer join `user_info` as u on u.user_id=o.user_id left outer join `place` as p on p.id=r.place_id where date(o.buy_time) >= date('2016-11-04') and  date(o.buy_time) <= date('2016-11-17')  and u.partner_id = '_krace_중국'
$sql = "select u.user_id, u.nick_name,  p.name as place_name , r.race_no, o.`type`, o.place_1, o.place_2, o.place_3, o.bet_money, o.service_money, o.money_type, o.result_money, o.update_time, o.stat from `order` as o left outer join `race` as r on r.id=o.race_id  left outer join `user_info` as u on u.user_id=o.user_id left outer join `place` as p on p.id=r.place_id where o.buy_time >= date('" . $sdate . "') and  o.buy_time < date('" . $edate . "') and u.partner_id in (" . $partner_line . ")";
//echo $sql;
include(__DIR__ . '/../../../application/configs/configdb.php');

$mysqli = new mysqli($host, $user, $password, $dbname);
// 연결 오류 발생 시 스크립트 종료
if ($mysqli->connect_errno) {
	die('Connect Error: ' . $mysqli->connect_error);
}

$data = array();

if ($result = $mysqli->query($sql)) {
	// 레코드 출력
	while ($row = mysqli_fetch_object($result)) {
		$data[] = $row;
	}
} else {
	$data = array(0 => 'empty');
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);

$result->free(); //메모리해제


unset($data);
// 접속 종료
$mysqli->close();
