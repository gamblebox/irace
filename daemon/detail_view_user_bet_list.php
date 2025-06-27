<?php
header("Content-Type:application/json");
header("Content-Type:text/html;charset=UTF-8");
$user_id = 'kaiji';
$date = date('Y-m-d');
$sdate = $date;
$edate = $date;
extract($_POST);
$edate = date('Y-m-d', strtotime($edate . '+' . '1' . ' days'));
if (!isset($_SESSION)) {
	session_start();
}
$club_id = $_SESSION['club_id'];
$user_level = $_SESSION['user_level'];
session_write_close();
// if (date('H') < 6 ) {
//     $sdate = date("Y-m-d",strtotime ($sdate . "-1 days"));
//     $edate = date("Y-m-d",strtotime ($edate . "-1 days"));
// }
//o.buy_time >= date_add('" . $sdate . "', INTERVAL 6 HOUR) and  o.buy_time < date_add('" . $edate . "', INTERVAL 30 HOUR)
if ($race_id) {
	$sql = "select u.user_id, u.club_user_id, u.nick_name,  p.name as place_name , r.race_no, o.`type`, o.place_1, o.place_2, o.place_3, o.bet_money, o.service_money, o.money_type, o.result_money, o.update_time, o.stat from `order` as o left outer join `race` as r on r.id=o.race_id  left outer join `user_info` as u on u.user_id=o.user_id left outer join `place` as p on p.id=r.place_id where o.buy_time >= date('" . $sdate . "') and o.buy_time < date('" . $edate . "') and o.user_id = '" . $user_id . "' and o.race_id = " . $race_id;
} else {
	$sql = "select u.user_id, u.club_user_id, u.nick_name,  p.name as place_name , r.race_no, o.`type`, o.place_1, o.place_2, o.place_3, o.bet_money, o.service_money, o.money_type, o.result_money, o.update_time, o.stat from `order` as o left outer join `race` as r on r.id=o.race_id  left outer join `user_info` as u on u.user_id=o.user_id left outer join `place` as p on p.id=r.place_id where o.buy_time >= date('" . $sdate . "') and o.buy_time < date('" . $edate . "') and o.user_id = '" . $user_id . "'";
}
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
	//$o = array();
	while ($row = mysqli_fetch_object($result)) {
		$data[] = $row;
		/*
					//$t = new stdClass();

					$t->id = $row->id;
									//echo $row->name;
					$t->name = $row->name;
					$t->country = $row->country;
					$o[] = $t;
					unset($t);*/
	}
} else {
	$data = array(0 => 'empty');
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);

$result->free(); //메모리해제


unset($data);
// 접속 종료
$mysqli->close();
