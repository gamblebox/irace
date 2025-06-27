<?php
//echo $_SERVER['HTTP_HOST'];
//echo $_SERVER['HTTP_REFERER'];
//if($_SERVER['HTTP_HOST'] !== $_SERVER['HTTP_REFERER']) exit('No direct access allowed');
header("Content-Type:application/json");
header("Content-Type:text/html;charset=UTF-8");
//header("Content-Type:application/json; charset=UTF-8");

extract($_POST);
$edate = date('Y-m-d', strtotime($edate . '+' . '1' . ' days'));
if (!isset($_SESSION)) {
	session_start();
}
if ($_SESSION['club_id'] !== '255') {
	$club_id = (int)$_SESSION['club_id'];
}
//$club_id  = 1;
$club_code = $_SESSION['club_code'];
$user_id = $_SESSION['user_id'];
$user_level = $_SESSION['user_level'];
session_write_close();

include(__DIR__ . '/../../../application/configs/configdb.php');
$mysqli = new mysqli($host, $user, $password, $dbname);
// 연결 오류 발생 시 스크립트 종료
if ($mysqli->connect_errno) {
	die('Connect Error: ' . $mysqli->connect_error);
} //$mysqli->connect_errno
//echo $mode;
//$mode = insert;
$data = array();
switch ($mode) {
	case 'delete':
		echo 'delete';
		break;
	case 'club_profit':
		$sql = "set @acm_profit = 0";
		$mysqli->query($sql);
		$sql = "select *,CASE DAYOFWEEK(d.date) WHEN '1' THEN '일' WHEN '2' THEN '월' WHEN '3' THEN '화' WHEN '4' THEN '수' WHEN '5' THEN '목' WHEN '6' THEN '금' WHEN '7' THEN '토' END as dayname, @crprofit := profit + if (cut_money_all is null, 0, cut_money_all) as crprofit , @acm_profit:=if (@crprofit is null, @acm_profit, @acm_profit+@crprofit) as acm_profit from (SELECT date FROM dates WHERE date BETWEEN '" . $sdate . "' AND '" . $edate . "') as `d` left outer join (select date(b.u_time) as banking_day, sum(if(b.banking_type = 'I' and b.stat = 'E',b.amount,0)) AS `deposit_money_all`, sum(if(b.banking_type = 'O' and b.stat = 'E',b.amount,0)) AS `withdraw_money_all` from banking as b left outer join `user_info` as `u` on `u`.user_id = b.user_id where u.club_id " . ($club_id === '255' ? '!= ' : '= ') . $club_id . " and u.user_level < 50 group by banking_day) as `b` on `d`.date = b.banking_day left outer join (select date(o.buy_time) as day, sum(`o`.`bet_money`) AS `bet_money_all`,sum(if((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) AS `bet_money_real_all`,sum(if((`o`.`money_type` = 'S'),`o`.`bet_money`,0)) AS `bet_money_service_all`,sum(`o`.`service_money`) AS `service_money_all`,sum(`o`.`result_money`) AS `result_money_all`,(sum(if((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) - sum(`o`.`result_money`)) AS `profit` from `order` as o left outer join `user_info` as `u` on `u`.user_id = o.user_id where ((`o`.`stat` != 'C') and (`o`.`stat` != 'R')) and u.club_id " . ($club_id === '255' ? '!= ' : '= ') . $club_id . " and u.user_level < 50 group by day) as o on `d`.date = o.day LEFT OUTER JOIN (SELECT date(c_date) day, SUM(`cut_money`) AS `cut_money_all` FROM `cut_result_money` where club_id " . ($club_id === '255' ? '!= ' : '= ') . $club_id . " GROUP BY date(c_date)) AS cr ON `d`.date = cr.day group by d.date";
		if ($result = $mysqli->query($sql)) {
			while ($row = mysqli_fetch_object($result)) {
				//$data[] = array( $row->day, $row->profit );
				$data[] = $row;
			} //$row = mysqli_fetch_object( $result )
		} //$result = $mysqli->query( $sql )
		else {
			$data = array(
				0 => 'empty'
			);
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		$result->free(); //메모리해제
		break;

		//
	case 'user_profit':
		$sql = "select *, @crprofit := if(profit is null, 0, profit) + if (cut_money_all is null, 0, cut_money_all) as crprofit, u.user_id as user_id from user_info as u left outer join  (select u.user_id, date(b.u_time) as banking_day, sum(if(b.banking_type = 'I' and b.stat = 'E',b.amount,0)) AS `deposit_money_all`, sum(if(b.banking_type = 'O' and b.stat = 'E',b.amount,0)) AS `withdraw_money_all` from banking as b left outer join `user_info` as `u` on `u`.user_id = b.user_id where b.u_time >= '" . $sdate . "' and b.u_time < '" . $edate . "'  group by b.user_id) as `b` on u.user_id = b.user_id left outer join (select u.user_id, sum(`o`.`bet_money`) AS `bet_money_all`, sum(if((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) AS `bet_money_real_all`,sum(if((`o`.`money_type` = 'S'),`o`.`bet_money`,0)) AS `bet_money_service_all`,sum(`o`.`service_money`) AS `service_money_all`,sum(`o`.`result_money`) AS `result_money_all`, (sum(if((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) - sum(`o`.`result_money`)) AS `profit` from `order` as o left outer join `user_info` as `u` on `u`.user_id = o.user_id where ((`o`.`stat` != 'C') and (`o`.`stat` != 'R')) and o.buy_time >= '" . $sdate . "' and o.buy_time <= '" . $edate . "'	group by u.user_id) as o on u.user_id = o.user_id LEFT OUTER JOIN (SELECT user_id u_id, SUM(`cut_money`) AS `cut_money_all` FROM cut_result_money WHERE c_date >= DATE('" . $sdate . "') AND c_date < DATE('" . $edate . "') and club_id " . ($club_id === '255' ? '!= ' : '= ') . $club_id . " GROUP BY user_id) AS cr ON u.user_id = cr.u_id where u.club_id " . ($club_id === '255' ? '!= ' : '= ') . $club_id . " and u.user_level < 50 and  o.bet_money_all > 0 group by u.user_id ";
		if ($result = $mysqli->query($sql)) {
			while ($row = mysqli_fetch_object($result)) {
				//$data[] = array( $row->day, $row->profit );
				$data[] = $row;
			} //$row = mysqli_fetch_object( $result )
		} //$result = $mysqli->query( $sql )
		else {
			$data = array(
				0 => 'empty'
			);
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		$result->free(); //메모리해제
		break;

	case 'partner_profit':
		$sql = "select * ,if(o.partner_id = '', '파트너없음', o.partner_id) as partner_id, @crprofit := if(profit is null, 0, profit) + if (cut_money_all is null, 0, cut_money_all) as crprofit from (select u.partner_id, u.user_id, date(b.u_time) as banking_day, sum(if(b.banking_type = 'I' and b.stat = 'E',b.amount,0)) AS `deposit_money_all`, sum(if(b.banking_type = 'O' and b.stat = 'E',b.amount,0)) AS `withdraw_money_all` from banking as b left outer join `user_info` as `u` on `u`.user_id = b.user_id where b.u_time >= '" . $sdate . "' and b.u_time < '" . $edate . "' and u.club_id " . ($club_id === '255' ? '!= ' : '= ') . $club_id . " and u.user_level < 50  group by u.partner_id) as `b` right outer join (select u.partner_id, u.user_id, sum(`o`.`bet_money`) AS `bet_money_all`, sum(if((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) AS `bet_money_real_all`,sum(if((`o`.`money_type` = 'S'),`o`.`bet_money`,0)) AS `bet_money_service_all`,sum(`o`.`service_money`) AS `service_money_all`,sum(`o`.`result_money`) AS `result_money_all`, (sum(if((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) - sum(`o`.`result_money`)) AS `profit` from `order` as o left outer join `user_info` as `u` on `u`.user_id = o.user_id where ((`o`.`stat` != 'C') and (`o`.`stat` != 'R')) and o.buy_time >= '" . $sdate . "' and o.buy_time < '" . $edate . "'	and u.club_id " . ($club_id === '255' ? '!= ' : '= ') . $club_id . " and u.user_level < 50  group by u.partner_id) as o on b.partner_id = o.partner_id LEFT OUTER JOIN (SELECT u.partner_id p_id, SUM(`cut_money`) AS `cut_money_all` FROM user_info u left outer join `cut_result_money` c on u.user_id = c.user_id  WHERE c_date >= DATE('" . $sdate . "') AND c_date < DATE('" . $edate . "') GROUP BY u.partner_id) AS cr ON o.partner_id = cr.p_id";
		if ($result = $mysqli->query($sql)) {
			while ($row = mysqli_fetch_object($result)) {
				//$data[] = array( $row->day, $row->profit );
				$data[] = $row;
			} //$row = mysqli_fetch_object( $result )
		} //$result = $mysqli->query( $sql )
		else {
			$data = array(
				0 => 'empty'
			);
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		$result->free(); //메모리해제
		break;

	case 'type_profit':
		$sql = "select type, sum(bet_money) as bet_money_all,sum(if((`money_type` = 'R'),`bet_money`,0)) AS `bet_money_real_all`,sum(if((`money_type` = 'S'),`bet_money`,0)) AS `bet_money_service_all`,sum(service_money) as service_money_all, sum(`order`.result_money) as result_money_all, (sum(if((`money_type` = 'R'),`bet_money`,0)) - sum(`order`.`result_money`)) AS `profit`, round((sum(`order`.result_money)/sum(bet_money))*100,1) as win_ratio, round(AVG(bet_money)) as bet_money_avg, count(*) as count  from `order` left outer join user_info on `order`.user_id = user_info.user_id where user_info.club_id " . ($club_id === '255' ? '!= ' : '= ') . $club_id . " and user_info.user_level < 50 and `order`.stat != 'R' and `order`.stat != 'C' and `order`.buy_time >= '" . $sdate . "' and `order`.buy_time < '" . $edate . "' group by type";
		if ($result = $mysqli->query($sql)) {
			while ($row = mysqli_fetch_object($result)) {
				//$data[] = array( $row->day, $row->profit );
				$data[] = $row;
			} //$row = mysqli_fetch_object( $result )
		} //$result = $mysqli->query( $sql )
		else {
			$data = array(
				0 => 'empty'
			);
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		$result->free(); //메모리해제
		break;

	case 'association_profit':
		$sql = "select association_info.name, type, sum(bet_money) as bet_money_all,sum(if((`money_type` = 'R'),`bet_money`,0)) AS `bet_money_real_all`,sum(if((`money_type` = 'S'),`bet_money`,0)) AS `bet_money_service_all`,sum(service_money) as service_money_all, sum(`order`.result_money) as result_money_all, (sum(if((`money_type` = 'R'),`bet_money`,0)) - sum(`order`.`result_money`)) AS `profit`, round((sum(`order`.result_money)/sum(bet_money))*100,1) as win_ratio, round(AVG(bet_money)) as bet_money_avg, count(*) as count  from `order` left outer join user_info on `order`.user_id = user_info.user_id left outer join race on race.id = `order`.race_id left outer join place on place.id = race.place_id left outer join association_info on association_info.id = place.association_id where user_info.club_id " . ($club_id === '255' ? '!= ' : '= ') . $club_id . " and user_info.user_level < 50 and `order`.stat != 'R' and `order`.stat != 'C' and `order`.buy_time >= '" . $sdate . "' and `order`.buy_time < '" . $edate . "'  group by association_info.name";
		if ($result = $mysqli->query($sql)) {
			while ($row = mysqli_fetch_object($result)) {
				//$data[] = array( $row->day, $row->profit );
				$data[] = $row;
			} //$row = mysqli_fetch_object( $result )
		} //$result = $mysqli->query( $sql )
		else {
			$data = array(
				0 => 'empty'
			);
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		$result->free(); //메모리해제
		break;

	case 'association_type_profit':
		$sql = "select association_info.name, type, sum(bet_money) as bet_money_all,sum(if((`money_type` = 'R'),`bet_money`,0)) AS `bet_money_real_all`,sum(if((`money_type` = 'S'),`bet_money`,0)) AS `bet_money_service_all`,sum(service_money) as service_money_all, sum(`order`.result_money) as result_money_all,round((sum(`order`.result_money)/sum(bet_money))*100,1) as win_ratio, round(AVG(bet_money)) as bet_money_avg, count(*) as count  from `order` left outer join user_info on `order`.user_id = user_info.user_id left outer join race on race.id = `order`.race_id left outer join place on place.id = race.place_id left outer join association_info on association_info.id = place.association_id where user_info.club_id " . ($club_id === '255' ? '!= ' : '= ') . $club_id . " and association_info.name = '" . $association_name . "'  and `order`.buy_time >= '" . $sdate . "' and `order`.buy_time < '" . $edate . "'  group by type";
		if ($result = $mysqli->query($sql)) {
			while ($row = mysqli_fetch_object($result)) {
				$data[] = $row;
			} //$row = mysqli_fetch_object( $result )
		} //$result = $mysqli->query( $sql )
		else {
			$data = array(
				0 => 'empty'
			);
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		$result->free(); //메모리해제
		break;

	case 'recommender':
		$sql = "SELECT *, u.user_id as user_id, b.r_time as banking_time FROM `user_info` as u left outer join (select * from `banking` where r_time >= date_add(now(), interval -6 day) and banking_type = 'I' and stat = 'E') as b on u.user_id = b.user_id where  u.recommender = '" . $user_id . "' order by b.r_time desc";
		if ($result = $mysqli->query($sql)) {
			while ($row = mysqli_fetch_object($result)) {
				$data[] = $row;
			} //$row = mysqli_fetch_object( $result )
		} //$result = $mysqli->query( $sql )
		else {
			$data = array(
				0 => 'empty'
			);
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		$result->free(); //메모리해제
		break;
	case 'todo':
		$sql = "select (select count(b.id)  from `banking` as b left outer join `user_info` as u on b.user_id = u.user_id where u.club_id = " . $club_id . " and b.stat = 'R' and date(b.r_time) > DATE_ADD(now(),INTERVAL -2 DAY)) as banking_count,(select count(id) from `bbs_qna` where club_id = " . $club_id . " and stat = 'R' and date(u_time) > DATE_ADD(now(),INTERVAL -2 DAY))  as qna_count, (select count(id) as count from `user_info` where club_id = " . $club_id . " and user_level = 0)  as user_count, (select count(id) as count from `user_info` where club_id = " . $club_id . " and bank_out_bank_info_ok = 'R')  as bank_confirm_count";
		if ($result = $mysqli->query($sql)) {
			while ($row = mysqli_fetch_object($result)) {
				$data[] = $row;
			} //$row = mysqli_fetch_object( $result )
		} //$result = $mysqli->query( $sql )
		else {
			$data = array(
				0 => 'empty'
			);
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		$result->free(); //메모리해제
		break;
	case 'signage':
		$sql = "SELECT * FROM `announce` WHERE  date(`r_time`) = date(now()) and (`club_id`= 0 or `club_id`= " . $club_id . ") and (`tarket` = 'B' or `tarket` = 'A') and (`user_id`= 0 or `user_id`= '" . $user_id . "') order by r_time desc limit 10";
		if ($result = $mysqli->query($sql)) {
			while ($row = mysqli_fetch_object($result)) {
				$data[] = $row;
			} //$row = mysqli_fetch_object( $result )
		} //$result = $mysqli->query( $sql )
		else {
			$data = array(
				0 => 'empty'
			);
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		$result->free(); //메모리해제
		break;

	case 'send_signage':
		$sql = "INSERT INTO `announce` (`club_id`, `type`, `memo`, `tarket`) VALUES (" . $club_id . ", '안내','" . $memo . "','C')";
		if ($mysqli->query($sql) === true) {
			$data[Ok] = 'Inserted';
		} //$mysqli->query( $sql ) === true
		else {
			$data[Error] = $mysqli->error;
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;

	case 'send_popup':
		$sql = "INSERT INTO `announce` (`club_id`, `type`, `memo`, `tarket`) VALUES (" . $club_id . ", '팝업안내','" . $memo . "','C')";
		if ($mysqli->query($sql) === true) {
			$data[Ok] = 'Inserted';
		} //$mysqli->query( $sql ) === true
		else {
			$data[Error] = $mysqli->error;
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;

	case 'islogin':
		$sql = "UPDATE  `user_info` SET `islogin`= now()  WHERE  `user_id`= '" . $user_id . "'";
		if ($mysqli->query($sql) === true) {
			$data[Ok] = 'Updated';
		} //$mysqli->query( $sql ) === true
		else {
			$data[Error] = $mysqli->error;
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;
	case 'setusermoney':
		$sql = "UPDATE  `user_info` SET `money_real`= `money_real` - " . $withdraw_amount . " WHERE  `user_id`= '" . $user_id . "'";
		if ($mysqli->query($sql) === true) {
			$data[Ok] = 'Updated';
		} //$mysqli->query( $sql ) === true
		else {
			$data[Error] = $mysqli->error;
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;
	case 'regiwithdraw':
		$sql = "INSERT INTO `banking` (`user_id`, `banking_type`, `bank_name`, `bank_account_no`, `amount`, `bank_account_name`) VALUES ('" . $user_id . "', 'O', '" . $bank_name . "','" . $bank_account_no . "'," . $withdraw_amount . ",'" . $bank_account_name . "')";
		if ($mysqli->query($sql) === true) {
			$data[Ok] = 'Inserted';
		} //$mysqli->query( $sql ) === true
		else {
			$data[Error] = $mysqli->error;
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;
	case 'regideposit':
		$sql = "INSERT INTO `banking` (`user_id`, `banking_type`, `amount`, `bank_account_name`) VALUES ('" . $user_id . "', 'I'," . $deposit_amount . ",'" . $deposit_name . "')";
		if ($mysqli->query($sql) === true) {
			$data[Ok] = 'Inserted';
		} //$mysqli->query( $sql ) === true
		else {
			$data[Error] = $mysqli->error;
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;
	case 'regiqna':
		$sql = "insert into `bbs_qna` (`user_id`, `subject`, `memo_q`, `club_id`) values ('" . $user_id . "', '" . $qna_subject . "','" . $memo_q . "'," . $club_id . ")";
		if ($mysqli->query($sql) === true) {
			$data[Ok] = 'Inserted';
		} //$mysqli->query( $sql ) === true
		else {
			$data[Error] = $mysqli->error;
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;
	case 'user_money':
		$sql = "SELECT `money_real`, `money_service`, `money_etc` FROM `user_info` WHERE  `user_id`= '" . $user_id . "'";
		if ($result = $mysqli->query($sql)) {
			while ($row = mysqli_fetch_object($result)) {
				$data[] = $row;
			} //$row = mysqli_fetch_object( $result )
		} //$result = $mysqli->query( $sql )
		else {
			$data = array(
				0 => 'empty'
			);
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		$result->free(); //메모리해제
		break;
	case 'withdraw':
		$sql = "SELECT * from `banking` where user_id = '" . $user_id . "' and `banking_type` = 'O' order by  `r_time` desc limit 10";
		if ($result = $mysqli->query($sql)) {
			while ($row = mysqli_fetch_object($result)) {
				$data[] = $row;
			} //$row = mysqli_fetch_object( $result )
		} //$result = $mysqli->query( $sql )
		else {
			$data = array(
				0 => 'empty'
			);
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		$result->free(); //메모리해제
		break;
	case 'check_pw':
		$sql = "select * from `user_info` where user_id = '" . $user_id . "' and user_pw = password('" . $user_pw . "')";
		if ($result = $mysqli->query($sql)) {
			while ($row = mysqli_fetch_object($result)) {
				$data[] = $row;
			} //$row = mysqli_fetch_object( $result )
		} //$result = $mysqli->query( $sql )
		else {
			$data = array(
				0 => 'empty'
			);
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		$result->free(); //메모리해제
		break;
	case 'user_config':
		$sql = "select * from `user_info` left outer join `club_info` on (`user_info`.club_id = `club_info`.id) where user_id = '" . $user_id . "'";
		if ($result = $mysqli->query($sql)) {
			while ($row = mysqli_fetch_object($result)) {
				$data[] = $row;
			} //$row = mysqli_fetch_object( $result )
		} //$result = $mysqli->query( $sql )
		else {
			$data = array(
				0 => 'empty'
			);
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		$result->free(); //메모리해제
		break;
	case 'banking_info':
		$sql = "SELECT * FROM `user_info` WHERE  `user_id`= '" . $user_id . "'";
		if ($result = $mysqli->query($sql)) {
			while ($row = mysqli_fetch_object($result)) {
				$data[] = $row;
			} //$row = mysqli_fetch_object( $result )
		} //$result = $mysqli->query( $sql )
		else {
			$data = array(
				0 => 'empty'
			);
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		$result->free(); //메모리해제
		break;
	case 'deposit':
		$sql = "SELECT * from `banking` where user_id = '" . $user_id . "' and `banking_type` = 'I' order by  `r_time` desc limit 10";
		if ($result = $mysqli->query($sql)) {
			while ($row = mysqli_fetch_object($result)) {
				$data[] = $row;
			} //$row = mysqli_fetch_object( $result )
		} //$result = $mysqli->query( $sql )
		else {
			$data = array(
				0 => 'empty'
			);
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		$result->free(); //메모리해제
		break;
	case 'result':
		$sql = "SELECT *, DATE_FORMAT(r.start_time,'%H시%i분') as time, p.name as place_name, a.name as association_name from `race` as r left outer JOIN `place` as p on r.place_id = p.id left outer JOIN `association_info` as a on p.association_id = a.id right outer JOIN (select `view_result`.race_id, group_concat(DISTINCT `type`,':',`result` separator ' ') as `result` from `view_result` where date(`view_result`.start_time) = date(now()) group by `view_result`.race_id order by `view_result`.race_id desc) as re on r.id = re.race_id left outer JOIN `view_place_result` as pr on pr.race_id = r.id where date(r.start_time) = date(now()) order by r.start_time desc";
		if ($result = $mysqli->query($sql)) {
			while ($row = mysqli_fetch_object($result)) {
				$data[] = $row;
			} //$row = mysqli_fetch_object( $result )
		} //$result = $mysqli->query( $sql )
		else {
			$data = array(
				0 => 'empty'
			);
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		$result->free(); //메모리해제
		break;
	case 'popup':
		$sql = "SELECT * from `bbs_notice` where `ispopup` = 'Y' and `club_id` = " . $club_id . " order by  `u_time` desc limit 6";
		if ($result = $mysqli->query($sql)) {
			while ($row = mysqli_fetch_object($result)) {
				$data[] = $row;
			} //$row = mysqli_fetch_object( $result )
		} //$result = $mysqli->query( $sql )
		else {
			$data = array(
				0 => 'empty'
			);
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		$result->free(); //메모리해제
		break;
	case 'notice':
		$sql = "SELECT * from `bbs_notice` where `club_id` = " . $club_id . " order by  `u_time` desc limit 20";
		if ($result = $mysqli->query($sql)) {
			while ($row = mysqli_fetch_object($result)) {
				$data[] = $row;
			} //$row = mysqli_fetch_object( $result )
		} //$result = $mysqli->query( $sql )
		else {
			$data = array(
				0 => 'empty'
			);
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		$result->free(); //메모리해제
		break;
	case 'post':
		//SELECT * from `bbs_post` where `r_user_id` = 'kaiji' order by  `s_time` desc limit 5
		$sql = "SELECT * from `bbs_post` where `r_user_id` = '" . $user_id . "' order by  `s_time` desc limit 20";
		if ($result = $mysqli->query($sql)) {
			while ($row = mysqli_fetch_object($result)) {
				$data[] = $row;
			} //$row = mysqli_fetch_object( $result )
		} //$result = $mysqli->query( $sql )
		else {
			$data = array(
				0 => 'empty'
			);
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		$result->free(); //메모리해제
		break;
		//data.sql = "SELECT * from `bbs_qna` where user_id = '" + user_id + "' order by  `u_time` desc limit 5";
	case 'qna':
		$sql = "SELECT * from `bbs_qna` where user_id = '" . $user_id . "' order by  `u_time` desc limit 5";
		if ($result = $mysqli->query($sql)) {
			while ($row = mysqli_fetch_object($result)) {
				$data[] = $row;
			} //$row = mysqli_fetch_object( $result )
		} //$result = $mysqli->query( $sql )
		else {
			$data = array(
				0 => 'empty'
			);
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		$result->free(); //메모리해제
		break;
	case 'user_money':
		$sql = "SELECT `money_real`, `money_service`, `money_etc` FROM `user_info` WHERE  `user_id`= '" . $user_id . "' limit 1";
		if ($result = $mysqli->query($sql)) {
			while ($row = mysqli_fetch_object($result)) {
				$data[] = $row;
			} //$row = mysqli_fetch_object( $result )
		} //$result = $mysqli->query( $sql )
		else {
			$data = array(
				0 => 'empty'
			);
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		$result->free(); //메모리해제
		break;
} //$mode
unset($data);
// 접속 종료
$mysqli->close();
