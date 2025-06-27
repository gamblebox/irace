<?php

header("Content-Type:application/json");
header("Content-Type:text/html;charset=UTF-8");
$date = date('Y-m-d');
if ($_POST['date']) {
	$date = $_POST['date'];
}
$club_id = 1;
$ta = "'race','japanrace','cycle','boat'";
if ($_POST['club_id']) {
	$club_id = $_POST['club_id'];
}
$partner_id = 'goldrace';
extract($_POST);
if (!isset($_SESSION)) {
	session_start();
}
$partner_id = $_SESSION['partner_id'];
$club_id = $_SESSION['club_id'];
session_write_close();


$sql = "SELECT *, r.id as race_id, p.id as place_id, p.name as place_name, a.code as association_code, a.id as association_id, a.name as association_name from `race` as r left outer JOIN `place` as p on r.place_id = p.id left outer JOIN `association_info` as a on p.association_id = a.id LEFT OUTER JOIN (SELECT race_id, SUM(cut_money) AS cut_money_all FROM cut_result_money as c left outer join user_info as u on c.user_id = u.user_id where u.partner_id = '" . $partner_id . "' and c.club_id " . ($club_id === '255' ? '!= ' : '= ') . $club_id . " group by c.race_id) as cr on cr.race_id = r.id left outer JOIN (select `view_result`.race_id, group_concat(DISTINCT `type`,':',`result` separator ' ') as `result` from `view_result` group by `view_result`.race_id order by `view_result`.race_id desc) as re on r.id = re.race_id left outer JOIN `view_place_result` as pr on pr.race_id = r.id left outer join view_result_money_by_partner on (r.id =view_result_money_by_partner.race_id and view_result_money_by_partner.partner_id = '" . $partner_id . "') where date(r.start_time) = '" . $date . "'  and ((p.id = 6 AND r.remark = '중계' ) or (p.id != 6 )) order by r.start_time";

$sql = "SELECT *, cri.id as id, cpi.isuse as cpi_isuse, cri.isuse as isuse, cri.race_id as race_id, p.id as place_id, p.name as place_name, a.code as association_code, a.id as association_id, a.name as association_name, sum(bet_money_all) bet_money_all, sum(bet_money_real_all) bet_money_real_all, sum(bet_money_service_all) bet_money_service_all, sum(service_money_all) service_money_all, sum(result_money_all) result_money_all, sum(profit) profit from `club_race` as cri left outer JOIN `race` as r  on cri.club_id = " . $club_id . " and cri.race_id = r.id left outer JOIN `place` as p on r.place_id = p.id left outer JOIN `club_place` as cpi on cpi.club_id = " . $club_id . " and cpi.place_id = p.id left outer JOIN `association_info` as a on p.association_id = a.id LEFT OUTER JOIN (SELECT race_id, SUM(`cut_money`) AS `cut_money_all` FROM cut_result_money cr left join `user_info` `u` on(`cr`.`user_id` = `u`.`user_id` and u.partner_id = '" . $partner_id . "' ) ) as cr on cr.race_id = r.id left outer join (select `o`.`race_id` AS `race_id`,`u`.`club_id` AS `club_id`,sum(`o`.`bet_money`) AS `bet_money_all`,sum(if((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) AS `bet_money_real_all`,sum(if((`o`.`money_type` = 'S'),`o`.`bet_money`,0)) AS `bet_money_service_all`,sum(`o`.`service_money`) AS `service_money_all`,sum(`o`.`result_money`) AS `result_money_all`,(sum(if((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) - sum(`o`.`result_money`)) AS `profit` from (`order` `o` left join `user_info` `u` on(`o`.`user_id` = `u`.`user_id` and u.partner_id = '" . $partner_id . "' ) ) where ((`o`.`stat` <> 'C') and (`o`.`stat` <> 'R')) and date(o.update_time) = '" . $date . "' group by `o`.`race_id`,`u`.`club_id` ) as vr on (r.id =vr.race_id and vr.club_id " . ($club_id === '255' ? '!= ' : '= ') . $club_id . ") where date(r.start_time) = '" . $date . "'  and ((p.id = 6 AND r.remark = '중계' ) or (p.id != 6 ))  and a.code in (" . $ta . ") group by r.id order by r.start_time";

if ($_POST['mode']) {
	$mode = $_POST['mode'];
} else {
	$mode = 'select';
}

include(__DIR__ . '/../../../application/configs/configdb.php');

$mysqli = new mysqli($host, $user, $password, $dbname);
// 연결 오류 발생 시 스크립트 종료
if ($mysqli->connect_errno) {
	die('Connect Error: ' . $mysqli->connect_error);
}

//echo $mode;
//$mode = insert;

$data = array();

switch ($mode) {

	case 'insert':
		if ($mysqli->query($sql) === true) {
			$data[Ok] = 'Inserted';
		} else {
			$data[Error] = $mysqli->error;
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;

	case 'update':
		if ($mysqli->query($sql) === true) {
			$data[Ok] = 'Updated';
		} else {
			$data[Error] = $mysqli->error;
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;

	case 'delete':
		echo 'delete';
		break;

	case 'select':
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
		break;
}
unset($data);
// 접속 종료
$mysqli->close();
