<?php

?>
<!DOCTYPE html>

<html lang="ko">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

<title>I Race</title>
<!--<meta name="viewport" content="width=device-width, initial-scale=1">-->
<meta http-equiv="X-UA-Compatible" content="IE=edge">
</head>
<body>

<?php
header("Content-Type:application/json");
header("Content-Type:text/html;charset=UTF-8");
include (__DIR__ . '/../../../application/configs/configdb.php');


function insert_sql($sql){
	include (__DIR__ . '/../../../application/configs/configdb.php');
	
	$mysqli = new mysqli($host, $user, $password, $dbname);
	 // 연결 오류 발생 시 스크립트 종료
	 if ($mysqli->connect_errno) {
			die('Connect Error: '.$mysqli->connect_error);
	}	
		
	if($mysqli->query($sql) === true){
		return 'ok';
	}
	else{
		return $mysqli->error;
	}
	//$result->free(); //메모리해제
}

// 연결 오류 발생 시 스크립트 종료
if ($mysqli->connect_errno) {
		die('Connect Error: '.$mysqli->connect_error);
}	

$sql = "Select id, calendar_bet, calendar_deposit, calendar_set from `club_info`";
$mysqli = new mysqli($host, $user, $password, $dbname);

if ( $result = $mysqli->query( $sql ) ) {
	// 레코드 출력
	//$o = array();
	while ( $row = mysqli_fetch_object( $result ) ) {
		$data[] = $row;
	} //$row = mysqli_fetch_object( $result )
} //$result = $mysqli->query( $sql )
else {
	$data = array(
		 0 => 'empty' 
	);
}
//print_r($data);

foreach ($data as $d) {
	if ($d->calendar_set === 'O'){
		
		//INSERT INTO `attendance` (`user_id`, `attendance_date`) select u.user_id, date(now())  FROM (user_info u left join (select user_id, sum(amount) deposit_money_all from `banking` where banking_type = 'I' and stat = 'E' and date(u_time) = date(now()) group by user_id) as b on (u.user_id = b.user_id ) left join (select user_id, sum(bet_money) bet_money_all from `order` where money_type = 'R' and date(update_time) = date(now()) group by user_id) as o on (u.user_id = o.user_id )) WHERE NOT EXISTS (SELECT * FROM `attendance` WHERE 'kaiji' = user_id and date(attendance_date) = date(now())) and u.club_id = " . $d->id . "  and (bet_money_all > " . $d->calendar_bet . " or deposit_money_all > " .  $d->calendar_deposit . ")

// and u.club_id = " . $d->id . "
		$sql = "INSERT INTO `attendance` (`user_id`, `attendance_date`) select u.user_id, date(now())  FROM (user_info u left join (select user_id, sum(amount) deposit_money_all from `banking` where banking_type = 'I' and stat = 'E' and date(u_time) = date(now()) group by user_id) as b on (u.user_id = b.user_id ) left join (select user_id, sum(bet_money) bet_money_all from `order` where money_type = 'R' and date(update_time) = date(now()) group by user_id) as o on (u.user_id = o.user_id )) WHERE NOT EXISTS (SELECT * FROM `attendance` WHERE u.user_id = user_id and date(attendance_date) = date(now()))  and (bet_money_all >= " . $d->calendar_bet . " or deposit_money_all >= " .  $d->calendar_deposit . ") and u.club_id = " . $d->id;
	}
	else	{
		$sql = "INSERT INTO `attendance` (`user_id`, `attendance_date`) select u.user_id, date(now())  FROM (user_info u left join (select user_id, sum(amount) deposit_money_all from `banking` where banking_type = 'I' and stat = 'E' and date(u_time) = date(now()) group by user_id) as b on (u.user_id = b.user_id ) left join (select user_id, sum(bet_money) bet_money_all from `order` where money_type = 'R' and date(update_time) = date(now()) group by user_id) as o on (u.user_id = o.user_id )) WHERE NOT EXISTS (SELECT * FROM `attendance` WHERE u.user_id = user_id and date(attendance_date) = date(now()))  and (bet_money_all >= " . $d->calendar_bet . " and deposit_money_all >= " .  $d->calendar_deposit . ") and u.club_id = " . $d->id;
	}
	echo $sql;
	$ok = insert_sql($sql);
	echo $ok;
}

//echo json_encode( $data, JSON_UNESCAPED_UNICODE );
$result->free(); //메모리해제

?>


</body>
</html>