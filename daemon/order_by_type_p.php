<?php

?>
<?php
header("Content-Type:application/json");
header("Content-Type:text/html;charset=UTF-8");
//header("Content-Type:application/json; charset=UTF-8");

//$sql = "SELECT *, r.id as race_id ,p.name as place_name, a.code as association_code, a.id as association_id, a.name as association_name from `race` as r left outer JOIN `place` as p on r.place_id = p.id left outer JOIN `association_info` as a on p.association_id = a.id   left outer JOIN  (select race_id, group_concat(type,' ',place_1,if(place_2=0,'',concat('-',place_2)),if(place_3=0,'',concat('-',place_3)), '(', odds, ')') as result from result where type in ('단승','복승','쌍승','삼복승','삼쌍승') group by race_id) as re on r.id = re.race_id  where date_add(now(), interval -7 day) < date(r.start_time) and date(now()) >= date(r.start_time)  order by r.start_time desc";
//$date = date('Y-m-d');

//$code = ['bok'=>'복승'];

$club_id = 1;
if ($_POST['club_id']) {
	$club_id = $_POST['club_id'];
}
$race_id =  5824;
if ($_POST['race_id']) {
	$race_id = $_POST['race_id'];
}

if ($_POST['date']) {
	$date = $_POST['date'];
}
extract($_POST);
if (!isset($_SESSION)) {
	session_start();
}
$user_id = $_SESSION['user_id'];
$club_id = $_SESSION['club_id'];
$user_level = $_SESSION['user_level'];
$partner_id = $_SESSION['partner_id'];
session_write_close();

//$sql = "SELECT *, r.id as race_id , p.name as place_name, bet_money_all, bet_money_real_all,service_money_all,result_money_all,(bet_money_real_all-result_money_all) as profit, a.code as association_code, a.id as association_id, a.name as association_name from `race` as r left outer JOIN `place` as p on r.place_id = p.id left outer JOIN (select race_id, sum(result_money) as result_money_all from `order` group by race_id) as rma on r.id = rma.race_id left outer JOIN (select race_id, sum(service_money) as service_money_all from `order` group by race_id) as sma on r.id = sma.race_id left outer JOIN (select race_id, sum(bet_money) as bet_money_all from `order` group by race_id) as bma on r.id = bma.race_id left outer JOIN (select race_id, sum(bet_money) as bet_money_real_all from `order` where money_type='R' group by race_id) as bmra on r.id = bmra.race_id left outer JOIN `association_info` as a on p.association_id = a.id   left outer JOIN  (select race_id, group_concat(type,' ',place_1,if(place_2=0,'',concat('-',place_2)),if(place_3=0,'',concat('-',place_3)), '(', odds, ')') as result from result where type in ('단승','복승','쌍승','삼복승','삼쌍승') group by race_id) as re on r.id = re.race_id  where date_add(now(), interval -7 day) < date(r.start_time) and date(now()) >= date(r.start_time)  order by r.start_time desc";

//$sql = "SELECT *, r.id as race_id , p.name as place_name, bet_money_all, bet_money_real_all,service_money_all,result_money_all,(bet_money_real_all-result_money_all) as profit, a.code as association_code, a.id as association_id, a.name as association_name from `race` as r left outer JOIN `place` as p on r.place_id = p.id left outer JOIN (select race_id, sum(result_money) as result_money_all from `order` group by race_id) as rma on r.id = rma.race_id left outer JOIN (select race_id, sum(service_money) as service_money_all from `order` group by race_id) as sma on r.id = sma.race_id left outer JOIN (select race_id, sum(bet_money) as bet_money_all from `order` group by race_id) as bma on r.id = bma.race_id left outer JOIN (select race_id, sum(bet_money) as bet_money_real_all from `order` where money_type='R' group by race_id) as bmra on r.id = bmra.race_id left outer JOIN `association_info` as a on p.association_id = a.id   left outer JOIN  (select race_id, group_concat(type,' ',place_1,if(place_2=0,'',concat('-',place_2)),if(place_3=0,'',concat('-',place_3)), '(', odds, ')') as result from result where type in ('단승','복승','쌍승','삼복승','삼쌍승') group by race_id) as re on r.id = re.race_id  where date(r.start_time) = '" . $date . "'  order by r.start_time";

//$sql = "SELECT *, r.id as race_id , p.name as place_name, bet_money_all, bet_money_real_all, bet_money_service_all, service_money_all,result_money_all, profit, a.code as association_code, a.id as association_id, a.name as association_name from `race` as r left outer JOIN `place` as p on r.place_id = p.id left outer JOIN view_result_money on r.id = view_result_money.race_id left outer JOIN `association_info` as a on p.association_id = a.id left outer JOIN (select `view_result`.race_id, group_concat(DISTINCT `type`,':',`result` separator ' ') as `result` from `view_result` group by `view_result`.race_id order by `view_result`.race_id desc) as re on r.id = re.race_id  where date(r.start_time) = '" . $date . "'  order by r.start_time desc";

//select  race_id, sum(o.bet_money) as bet_money_all , concat(place_1,if(place_2=0,'',concat('-',place_2)),if(place_3=0,'',concat('-',place_3))) as select_num from `order` as o left outer join user_info as u on u.user_id=o.user_id where o.`type` = '복승' and o.`race_id` = 5467 and u.club_id = 1 group by select_num ;

//$sql = "select race_id, a.name as association_name, p.name as place_name, race_no, race_length, start_time, r.stat as stat, o.type as type, sum(o.bet_money) as bet_money_all , sum(if((o.`money_type` = 'R'),o.`bet_money`,0)) AS `bet_money_real_all`,sum(if((o.`money_type` = 'S'),o.`bet_money`,0)) AS `bet_money_service_all`,sum(o.`service_money`) AS `service_money_all` ,sum(o.`result_money`) AS `result_money_all`,concat(place_1,if(place_2=0,'',concat('-',place_2)),if(place_3=0,'',concat('-',place_3))) as select_num from `order` as o left outer join `user_info` as u on u.user_id=o.user_id  left outer join `race` as r on r.id=o.race_id left outer join `place` as p on r.place_id=p.id left outer join `association_info` as a on a.id=p.association_id where o.`stat` != 'C' and o.`stat` != 'R' and  o.`type` = '" . $type . "' and o.`race_id` = " . $race_id .  " and u.club_id = " . $club_id . "  group by select_num ";


$sql = "select race_id, a.name as association_name, p.name as place_name, race_no, race_length, start_time, r.stat as stat, o.type as type, sum(o.bet_money) as bet_money_all , sum(if((o.`money_type` = 'R'),o.`bet_money`,0)) AS `bet_money_real_all`,sum(if((o.`money_type` = 'S'),o.`bet_money`,0)) AS `bet_money_service_all`,sum(o.`service_money`) AS `service_money_all` ,sum(o.`result_money`) AS `result_money_all` from `order` as o left outer join `user_info` as u on u.user_id=o.user_id  left outer join `race` as r on r.id=o.race_id left outer join `place` as p on r.place_id=p.id left outer join `association_info` as a on a.id=p.association_id where  o.`stat` != 'C' and o.`stat` != 'R' and  o.`race_id` = " . $race_id .  " and u.club_id = " . $club_id . " and u.partner_id = '" . $partner_id . "'  group by o.type";


//echo $sql;
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
$data = new stdClass();


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
			$d = array();
			while ($row = mysqli_fetch_object($result)) {

				$d[] = $row;
			}
		} else {
			$data = array(0 => 'empty');
		}

		$data->type = $d;

		$sql = "select race_id, a.name as association_name, p.name as place_name, race_no, race_length, start_time, r.stat as stat, o.type as type, sum(o.bet_money) as bet_money_all , sum(if((o.`money_type` = 'R'),o.`bet_money`,0)) AS `bet_money_real_all`,sum(if((o.`money_type` = 'S'),o.`bet_money`,0)) AS `bet_money_service_all`,sum(o.`service_money`) AS `service_money_all` ,sum(o.`result_money`) AS `result_money_all` from `order` as o left outer join `user_info` as u on u.user_id=o.user_id  left outer join `race` as r on r.id=o.race_id left outer join `place` as p on r.place_id=p.id left outer join `association_info` as a on a.id=p.association_id where  o.`stat` != 'C' and o.`stat` != 'R' and  o.`race_id` = " . $race_id .  " and u.club_id = " . $club_id . " and u.partner_id = '" . $partner_id . "' ";
		if ($result = $mysqli->query($sql)) {
			// 레코드 출력
			//$o = array();
			$d = array();
			while ($row = mysqli_fetch_object($result)) {
				$d[] = $row;
			}
		} else {
			$data = array(0 => 'empty');
		}
		$data->sum = $d;
		echo json_encode($data, JSON_UNESCAPED_UNICODE);

		$result->free(); //메모리해제
		break;
}
unset($data);
// 접속 종료
$mysqli->close();

?>

