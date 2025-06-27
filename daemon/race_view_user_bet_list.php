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
$sql = "select u.user_id, u.club_user_id, u.nick_name, `o`.`race_id` AS `race_id`,`u`.`club_id` AS `club_id`, p.name as place_name, r.race_no as race_no, r.start_time as start_time, r.stat as race_stat, count(o.id) as ticket_count ,sum(`o`.`bet_money`) AS `bet_money_all`,sum(if((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) AS `bet_money_real_all`,sum(if((`o`.`money_type` = 'S'),`o`.`bet_money`,0)) AS `bet_money_service_all`,sum(`o`.`service_money`) AS `service_money_all`,sum(`o`.`result_money`) AS `result_money_all`, cr.cut_money, (sum(if((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) - sum(`o`.`result_money`)) AS `profit` from (`order` `o` left join `user_info` `u` on((`o`.`user_id` = `u`.`user_id`))) left join cut_result_money cr on cr.user_id = u.user_id and cr.race_id = o.race_id left join race r on o.race_id = r.id left join place p on r.place_id = p.id where ((`o`.`stat` <> 'C') and (`o`.`stat` <> 'R')) and o.buy_time >= date('" . $sdate . "') and  o.buy_time < date('" . $edate . "') and u.user_id = '" . $user_id . "' group by `o`.`race_id`";

//$sql = "select *, p.name as place_name , o.stat from `order` as o left outer join `race` as r on r.id=o.race_id  left outer join `user_info` as u on u.user_id=o.user_id left outer join `place` as p on p.id=r.place_id where date(o.buy_time) >= date('" . $sdate . "') and  date(o.buy_time) <= date('" . $edate . "') and o.user_id = '" . $user_id . "'";
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
