<?php
header("Content-Type:application/json");
header("Content-Type:text/html;charset=UTF-8");
$today = date('Y-m-d');
$date = $today;
if ($_POST['date']) {
	$date = $_POST['date'];
}
$check_date = date('Ymd', strtotime($today . '+' . '-7' . ' days'));

$club_id = 1;
$ta = "'race','japanrace','cycle','boat'";
if ($_POST['club_id']) {
	$club_id = $_POST['club_id'];
}
extract($_POST);
if (!isset($_SESSION)) {
	session_start();
}
$sdate = $date;
$edate = date('Y-m-d', strtotime($sdate . '+' . '1' . ' days'));

$user_id = $_SESSION['user_id'];
if ($_SESSION['club_id'] !== '255') {
	$club_id = (int) $_SESSION['club_id'];
}
$user_level = $_SESSION['user_level'];
session_write_close();

// DataTables PHP library
include "DataTables.php";

// Alias Editor classes so they are easy to use
use DataTables\Editor, DataTables\Editor\Field, DataTables\Editor\Format, DataTables\Editor\Mjoin, DataTables\Editor\Upload, DataTables\Editor\Validate;

// $user_id = 'goldrace';
// $race_id = 1827;
// Build our Editor instance and process the data coming from _POST

// if (isset($post['race_id'])) {
// $race_id = $_POST['race_id'];
// }

Editor::inst($db, 'club_race')->fields(Field::inst('race_id'), Field::inst('club_id'), Field::inst('isuse'))
	->where('club_id', $club_id, '=')
	->where('start_date', $date, '=')
	->
	// ->where( 'date(banking.r_time)',date('Y-m-d',strtotime(date('Y-m-d').'-7 days')), '>')
	// ->where( 'date(order.buy_time)',date('Y-m-d',strtotime(date('Y-m-d').'-7 days')), '>')

	process($_POST);
// ->json(false);

// $sql = "SELECT *, r.id as race_id, p.id as place_id, p.name as place_name, a.code as association_code, a.id as association_id, a.name as association_name from `race` as r left outer JOIN `place` as p on r.place_id = p.id left outer JOIN `association_info` as a on p.association_id = a.id LEFT OUTER JOIN (SELECT race_id, SUM(`cut_money`) AS `cut_money_all` FROM cut_result_money where club_id " . ($club_id === '255'? '!= ': '= ') . $club_id . " group by race_id) as cr on cr.race_id = r.id left outer JOIN (select `view_result`.race_id, group_concat(DISTINCT `type`,':',`result` separator ' ') as `result` from `view_result` group by `view_result`.race_id order by `view_result`.race_id desc) as re on r.id = re.race_id left outer JOIN `view_place_result` as pr on pr.race_id = r.id left outer join view_result_money on (r.id =view_result_money.race_id and view_result_money.club_id " . ($club_id === '255'? '!= ': '= ') . $club_id . ") where date(r.start_time) = '" . $date . "' and ((p.id = 6 AND r.remark = '중계' ) or (p.id != 6 )) and a.code in (" . $ta . ") order by r.start_time";
// $sql = "SELECT cri.id as id, cpi.isuse as cpi_isuse, cri.isuse as isuse, cri.race_id as race_id, p.id as place_id, p.name as place_name, a.code as association_code, a.id as association_id, a.name as association_name, p.own_id, r.start_date, r.start_time, r.race_no, r.race_length, r.entry_count, r.remark, r.stat, r.cancel_entry_no, r.place_1, r.place_2, r.place_3, r.place_oe, r.odds_dan, r.odds_yun, r.odds_bok, r.odds_ssang, r.odds_bokyun, r.odds_sambok, r.odds_oe, r.odds_samssang, r.odds_all, cut_money_all, sum(bet_money_all) bet_money_all, sum(bet_money_real_all) bet_money_real_all, sum(bet_money_service_all) bet_money_service_all, sum(service_money_all) service_money_all, sum(result_money_all) result_money_all, sum(profit) profit from `club_race` as cri left outer JOIN `race` as r on cri.race_id = r.id left outer JOIN `place` as p on r.place_id = p.id left outer JOIN `club_place` as cpi on cpi.place_id = p.id left outer JOIN `association_info` as a on p.association_id = a.id LEFT OUTER JOIN (SELECT race_id, SUM(`cut_money`) AS `cut_money_all` FROM cut_result_money where club_id " . ($club_id === '255' ? '!= ' : '= ') . $club_id . " group by race_id) as cr on cr.race_id = r.id left outer join (select `o`.`race_id` AS `race_id`,`u`.`club_id` AS `club_id`,sum(`o`.`bet_money`) AS `bet_money_all`,sum(if((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) AS `bet_money_real_all`,sum(if((`o`.`money_type` = 'S'),`o`.`bet_money`,0)) AS `bet_money_service_all`,sum(`o`.`service_money`) AS `service_money_all`,sum(`o`.`result_money`) AS `result_money_all`,(sum(if((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) - sum(`o`.`result_money`)) AS `profit` from (`order` `o` left join `user_info` `u` on(`o`.`user_id` = `u`.`user_id` ) ) where ((`o`.`stat` <> 'C') and (`o`.`stat` <> 'R')) and u.club_id " . ($club_id === '255' ? '!= ' : '= ') . $club_id . " group by `o`.`race_id`,`u`.`club_id` ) as vr on (r.id =vr.race_id) where cri.club_id = " . $club_id . " and date(r.start_date) = '" . $date . "' and ((p.id = 6 AND r.remark = '중계' ) or (p.id != 6 )) and a.code in (" . $ta . ") group by r.id order by r.start_time";

/* if ($user_level > 99 && $club_id != '255' && strtotime($date) >= strtotime($check_date)) {
// 	echo 'fast';
	$sql = "SELECT cri.id AS id, cpi.isuse AS cpi_isuse, cri.isuse AS isuse, cri.race_id AS race_id, p.id AS place_id, p.name AS place_name, a.code AS association_code, a.id AS association_id, a.name AS association_name, a.a_baedang_info_bok_1, p.own_id, r.start_date, r.start_time, r.race_no, r.race_length, r.entry_count, r.remark, r.stat, r.cancel_entry_no, r.place_1, r.place_2, r.place_3, r.place_oe, pbr.pb_results_set, pobr.results_set, r.odds_dan, r.odds_yun, r.odds_bok, r.odds_ssang, r.odds_bokyun, r.odds_sambok, r.odds_oe, r.odds_samssang, r.odds_all, rs.cut_money_all, rs.bet_money_all, rs.bet_money_real_all, rs.bet_money_service_all, rs.service_money_all, rs.result_money_all, rs.profit FROM `club_race` AS cri LEFT OUTER JOIN `race` AS r ON cri.race_id = r.id LEFT OUTER JOIN `race_sum` AS rs ON (rs.race_id = r.id AND rs.club_id " . ($club_id === '255' ? '!= ' : '= ') . $club_id . ")  LEFT OUTER JOIN `pb_result` AS pbr ON r.id = pbr.pb_race_id LEFT OUTER JOIN `powerball_result` AS pobr ON r.id = pobr.race_id LEFT OUTER JOIN `place` AS p ON r.place_id = p.id LEFT OUTER JOIN `club_place` AS cpi ON cpi.place_id = p.id and cpi.club_id = cri.club_id LEFT OUTER JOIN `association_info` AS a ON p.association_id = a.id LEFT OUTER JOIN (SELECT c.race_id, SUM(c.`cut_money`) AS `cut_money_all` FROM cut_result_money c LEFT JOIN `race` `r` ON(`c`.`race_id` = `r`.`id`) WHERE c.club_id " . ($club_id === '255' ? '!= ' : '= ') . $club_id . " and date(r.start_time) = '" . $date . "' GROUP BY race_id) AS cr ON cr.race_id = r.id WHERE cri.club_id = " . $club_id . " AND date(r.start_time) = '" . $date . "' AND ((p.id = 6 AND r.remark = '중계') OR (p.id != 6)) AND a.code IN (" . $ta . ") ORDER BY r.start_time";
} else */
if ($user_level > 99) {
	// 	echo 'slow';
	$sql = "SELECT cri.id AS id, cpi.isuse AS cpi_isuse, cri.isuse AS isuse, cri.race_id AS race_id, p.id AS place_id, p.name AS place_name, a.code AS association_code, a.id AS association_id, a.name AS association_name, a.a_baedang_info_bok_1, p.own_id, r.start_date, r.start_time, r.race_no, r.race_length, r.entry_count, r.remark, r.stat, r.cancel_entry_no, r.place_1, r.place_2, r.place_3, r.place_oe, pbr.pb_results_set, pobr.results_set, r.odds_dan, r.odds_yun, r.odds_bok, r.odds_ssang, r.odds_bokyun, r.odds_sambok, r.odds_oe, r.odds_samssang, r.odds_all, cut_money_all, bet_money_all, bet_money_real_all, bet_money_service_all, service_money_all, result_money_all, profit FROM `club_race` AS cri LEFT OUTER JOIN `race` AS r ON cri.race_id = r.id LEFT OUTER JOIN `pb_result` AS pbr ON r.id = pbr.pb_race_id LEFT OUTER JOIN `powerball_result` AS pobr ON r.id = pobr.race_id LEFT OUTER JOIN `place` AS p ON r.place_id = p.id LEFT OUTER JOIN `club_place` AS cpi ON cpi.place_id = p.id and cpi.club_id = cri.club_id LEFT OUTER JOIN `association_info` AS a ON p.association_id = a.id LEFT OUTER JOIN (SELECT c.race_id, SUM(c.`cut_money`) AS `cut_money_all` FROM cut_result_money c LEFT JOIN `race` `r` ON(`c`.`race_id` = `r`.`id`) WHERE c.club_id " . ($club_id === '255' ? '!= ' : '= ') . $club_id . " and r.start_time >= '" . $sdate . "' and r.start_time < '" . $edate . "' GROUP BY race_id) AS cr ON cr.race_id = r.id LEFT OUTER JOIN (SELECT `o`.`race_id` AS `race_id`,`o`.`club_id`, SUM(`o`.`bet_money`) AS `bet_money_all`, SUM(IF((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) AS `bet_money_real_all`, SUM(IF((`o`.`money_type` = 'S'),`o`.`bet_money`,0)) AS `bet_money_service_all`, SUM(`o`.`service_money`) AS `service_money_all`, SUM(`o`.`result_money`) AS `result_money_all`,(SUM(IF((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) - SUM(`o`.`result_money`)) AS `profit` FROM (`order` `o` LEFT JOIN `race` `r` ON(`o`.`race_id` = `r`.`id`)) WHERE ((`o`.`stat` <> 'C') AND (`o`.`stat` <> 'R')) AND o.club_id " . ($club_id === '255' ? '!= ' : '= ') . $club_id . " and r.start_time >= '" . $sdate . "' and r.start_time < '" . $edate . "' GROUP BY `o`.`race_id`) AS vr ON (r.id =vr.race_id) WHERE cri.club_id = " . $club_id . " AND r.start_time >= '" . $sdate . "' and r.start_time < '" . $edate . "' AND ((p.id = 6 AND r.remark = '중계') OR (p.id != 6)) AND a.code IN (" . $ta . ") ORDER BY r.start_time";
} else {
	// 	echo 'slow partner';
	$sql = "SELECT cri.id AS id, cpi.isuse AS cpi_isuse, cri.isuse AS isuse, cri.race_id AS race_id, p.id AS place_id, p.name AS place_name, a.code AS association_code, a.id AS association_id, a.name AS association_name, a.a_baedang_info_bok_1, p.own_id, r.start_date, r.start_time, r.race_no, r.race_length, r.entry_count, r.remark, r.stat, r.cancel_entry_no, r.place_1, r.place_2, r.place_3, r.place_oe, pbr.pb_results_set, pobr.results_set, r.odds_dan, r.odds_yun, r.odds_bok, r.odds_ssang, r.odds_bokyun, r.odds_sambok, r.odds_oe, r.odds_samssang, r.odds_all, cut_money_all, bet_money_all, bet_money_real_all, bet_money_service_all, service_money_all, result_money_all, profit FROM `club_race` AS cri LEFT OUTER JOIN `race` AS r ON cri.race_id = r.id LEFT OUTER JOIN `pb_result` AS pbr ON r.id = pbr.pb_race_id LEFT OUTER JOIN `powerball_result` AS pobr ON r.id = pobr.race_id LEFT OUTER JOIN `place` AS p ON r.place_id = p.id LEFT OUTER JOIN `club_place` AS cpi ON cpi.place_id = p.id and cpi.club_id = cri.club_id LEFT OUTER JOIN `association_info` AS a ON p.association_id = a.id LEFT OUTER JOIN (SELECT c.race_id, SUM(c.`cut_money`) AS `cut_money_all` FROM cut_result_money c LEFT JOIN `race` `r` ON(`c`.`race_id` = `r`.`id`) WHERE c.club_id " . ($club_id === '255' ? '!= ' : '= ') . $club_id . " and r.start_time >= '" . $sdate . "' and r.start_time < '" . $edate . "' GROUP BY race_id) AS cr ON cr.race_id = r.id LEFT OUTER JOIN (SELECT `o`.`race_id` AS `race_id`,`o`.`club_id`, SUM(`o`.`bet_money`) AS `bet_money_all`, SUM(IF((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) AS `bet_money_real_all`, SUM(IF((`o`.`money_type` = 'S'),`o`.`bet_money`,0)) AS `bet_money_service_all`, SUM(`o`.`service_money`) AS `service_money_all`, SUM(`o`.`result_money`) AS `result_money_all`,(SUM(IF((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) - SUM(`o`.`result_money`)) AS `profit` FROM `order` `o` LEFT JOIN `race` `r` ON(`o`.`race_id` = `r`.`id`) LEFT JOIN `user_info` `u` ON(`u`.`user_id` = `o`.`user_id`) WHERE u.partner_id IN ( with recursive cte ( user_id, partner_id ) as ( select user_id, partner_id from user_info where partner_id = '" . $user_id . "' union all select r.user_id, r.partner_id from user_info r inner join cte on r.partner_id = cte.user_id ) select distinct partner_id from cte ) AND `o`.`stat` NOT IN ('C', 'R') AND o.club_id " . ($club_id === '255' ? '!= ' : '= ') . $club_id . " and r.start_time >= '" . $sdate . "' and r.start_time < '" . $edate . "' GROUP BY `o`.`race_id`) AS vr ON (r.id =vr.race_id) WHERE cri.club_id = " . $club_id . " AND r.start_time >= '" . $sdate . "' and r.start_time < '" . $edate . "' AND ((p.id = 6 AND r.remark = '중계') OR (p.id != 6)) AND a.code IN (" . $ta . ") ORDER BY r.start_time";
}

// echo $sql .PHP_EOL;
// exit();
include __DIR__ . '/../../../application/configs/configdb.php';

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
	$data = array(
		0 => 'empty'
	);
}
// $data[] = $sql;
echo json_encode($data, JSON_UNESCAPED_UNICODE);
$result->free(); // 메모리해제
unset($data);
// 접속 종료
$mysqli->close();
