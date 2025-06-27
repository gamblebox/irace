<?php

?>
<?php
header("Content-Type:application/json");
header("Content-Type:text/html;charset=UTF-8");
//header("Content-Type:application/json; charset=UTF-8");

//$sql = "SELECT *, r.id as race_id ,p.name as place_name, a.code as association_code, a.id as association_id, a.name as association_name from `race` as r left outer JOIN `place` as p on r.place_id = p.id left outer JOIN `association_info` as a on p.association_id = a.id   left outer JOIN  (select race_id, group_concat(type,' ',place_1,if(place_2=0,'',concat('-',place_2)),if(place_3=0,'',concat('-',place_3)), '(', odds, ')') as result from result where type in ('단승','복승','쌍승','삼복승','삼쌍승') group by race_id) as re on r.id = re.race_id  where date_add(now(), interval -7 day) < date(r.start_time) and date(now()) >= date(r.start_time)  order by r.start_time desc";
//$date = date('Y-m-d');

//$code = ['bok'=>'복승'];
$select_num = '2-3';
if ($_POST['select_num']) {
	$select_num = $_POST['select_num'];
}
$type = '복승';
if ($_POST['type']) {
	$type = $_POST['type'];
}
$club_id = 1;
if ($_POST['club_id']) {
	$club_id = $_POST['club_id'];
}
$race_id =  5467;
if ($_POST['race_id']) {
	$race_id = $_POST['race_id'];
}

$user_id =  'goldrace';
if ($_POST['user_id']) {
	$user_id = $_POST['user_id'];
}
$date = date('Y-m-d');
if ($_POST['date']) {
	$date = $_POST['date'];
}
extract($_POST);
$edate = date('Y-m-d', strtotime($date . '+' . '1' . ' days'));
if (!isset($_SESSION)) {
	session_start();
}
$user_id = $_SESSION['user_id'];
$club_id = $_SESSION['club_id'];
$user_level = $_SESSION['user_level'];
session_write_close();
//$sql = "SELECT *, r.id as race_id , p.name as place_name, bet_money_all, bet_money_real_all,service_money_all,result_money_all,(bet_money_real_all-result_money_all) as profit, a.code as association_code, a.id as association_id, a.name as association_name from `race` as r left outer JOIN `place` as p on r.place_id = p.id left outer JOIN (select race_id, sum(result_money) as result_money_all from `order` group by race_id) as rma on r.id = rma.race_id left outer JOIN (select race_id, sum(service_money) as service_money_all from `order` group by race_id) as sma on r.id = sma.race_id left outer JOIN (select race_id, sum(bet_money) as bet_money_all from `order` group by race_id) as bma on r.id = bma.race_id left outer JOIN (select race_id, sum(bet_money) as bet_money_real_all from `order` where money_type='R' group by race_id) as bmra on r.id = bmra.race_id left outer JOIN `association_info` as a on p.association_id = a.id   left outer JOIN  (select race_id, group_concat(type,' ',place_1,if(place_2=0,'',concat('-',place_2)),if(place_3=0,'',concat('-',place_3)), '(', odds, ')') as result from result where type in ('단승','복승','쌍승','삼복승','삼쌍승') group by race_id) as re on r.id = re.race_id  where date_add(now(), interval -7 day) < date(r.start_time) and date(now()) >= date(r.start_time)  order by r.start_time desc";

//$sql = "SELECT *, r.id as race_id , p.name as place_name, bet_money_all, bet_money_real_all,service_money_all,result_money_all,(bet_money_real_all-result_money_all) as profit, a.code as association_code, a.id as association_id, a.name as association_name from `race` as r left outer JOIN `place` as p on r.place_id = p.id left outer JOIN (select race_id, sum(result_money) as result_money_all from `order` group by race_id) as rma on r.id = rma.race_id left outer JOIN (select race_id, sum(service_money) as service_money_all from `order` group by race_id) as sma on r.id = sma.race_id left outer JOIN (select race_id, sum(bet_money) as bet_money_all from `order` group by race_id) as bma on r.id = bma.race_id left outer JOIN (select race_id, sum(bet_money) as bet_money_real_all from `order` where money_type='R' group by race_id) as bmra on r.id = bmra.race_id left outer JOIN `association_info` as a on p.association_id = a.id   left outer JOIN  (select race_id, group_concat(type,' ',place_1,if(place_2=0,'',concat('-',place_2)),if(place_3=0,'',concat('-',place_3)), '(', odds, ')') as result from result where type in ('단승','복승','쌍승','삼복승','삼쌍승') group by race_id) as re on r.id = re.race_id  where date(r.start_time) = '" . $date . "'  order by r.start_time";

//$sql = "SELECT *, r.id as race_id , p.name as place_name, bet_money_all, bet_money_real_all, bet_money_service_all, service_money_all,result_money_all, profit, a.code as association_code, a.id as association_id, a.name as association_name from `race` as r left outer JOIN `place` as p on r.place_id = p.id left outer JOIN view_result_money on r.id = view_result_money.race_id left outer JOIN `association_info` as a on p.association_id = a.id left outer JOIN (select `view_result`.race_id, group_concat(DISTINCT `type`,':',`result` separator ' ') as `result` from `view_result` group by `view_result`.race_id order by `view_result`.race_id desc) as re on r.id = re.race_id  where date(r.start_time) = '" . $date . "'  order by r.start_time desc";
$num = explode('-', $select_num);
//print($num[2]);
$place_1 = $num[0];
if ($num[1]) {
	$place_2 = $num[1];
} else {
	$place_2 = 0;
}
if ($num[2]) {
	$place_3 = $num[2];
} else {
	$place_3 = 0;
}
//echo $place_3;

$sql = "select *, p.name as place_name , o.stat from `order` as o left outer join `race` as r on r.id=o.race_id  left outer join `user_info` as u on u.user_id=o.user_id left outer join `place` as p on p.id=r.place_id where o.buy_time >= date('" . $date . "') and o.buy_time < date('" . $edate . "') and o.user_id = '" . $user_id . "'";

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

?>

