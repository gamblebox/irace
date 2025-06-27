<?php
$club_id = 1;
$date = date('Y-m-d');
$sdate = $date;
$edate = $date;
extract($_POST);
if (!isset($_SESSION)) {
	session_start();
}
$club_id = $_SESSION['club_id'];
$partner_id = $_SESSION['partner_id'];
session_write_close();
/*
 * Example PHP implementation used for the index.html example
 */

// DataTables PHP library
include_once("DataTables.php");

// Alias Editor classes so they are easy to use
use
	DataTables\Editor,
	DataTables\Editor\Field,
	DataTables\Editor\Format,
	DataTables\Editor\Mjoin,
	DataTables\Editor\Upload,
	DataTables\Editor\Validate;

// Build our Editor instance and process the data coming from _POST
Editor::inst($db, 'user_info')
	->fields(
		//Field::inst( 'banking_type' ),
		Field::inst('user_id'),
		Field::inst('club_user_id'),
		//Field::inst( 'club_name' ),
		Field::inst('nick_name'),
		Field::inst('partner_id')
			->options('partner_info', 'partner_id', 'partner_id', function ($q) {
				global $club_id;
				$q->where('club_id', $club_id, '=');
			}),
		Field::inst('club_id'),
		Field::inst('login_ip'),
		Field::inst('phone'),
		Field::inst('recommender')
			->options('user_info', 'user_id', 'user_id', function ($q) {
				global $club_id;
				$q->where('club_id', $club_id, '=');
			}),
		Field::inst('bank_in_bank_id')
			->options('bank_in_info', 'id', 'bank_in_bank_name', function ($q) {
				global $club_id;
				$q->where('club_id', $club_id, '=')
					->where('isuse', 'Y', '=');
			}),
		Field::inst('bank_out_bank_name'),
		Field::inst('bank_out_bank_account_no'),
		Field::inst('bank_out_bank_account_name'),
		Field::inst('bank_out_bank_info_ok'),
		Field::inst('money_real'),
		Field::inst('money_service'),
		Field::inst('user_level'),
		Field::inst('r_time'),
		Field::inst('u_time'),
		Field::inst('login_time'),
		Field::inst('stat'),
		Field::inst('bet_service_config'),
		Field::inst('u_korea_race_bet_service'),
		Field::inst('u_korea_race_bet_service_sambok'),
		Field::inst('u_korea_race_bet_service_bokyun'),
		Field::inst('u_korea_race_bet_service_dan'),
		Field::inst('u_korea_race_bet_service_yun'),
		Field::inst('u_japan_race_bet_service'),
		Field::inst('u_japan_race_bet_service_sambok'),
		Field::inst('u_japan_race_bet_service_bokyun'),
		Field::inst('u_japan_race_bet_service_dan'),
		Field::inst('u_japan_race_bet_service_yun'),
		Field::inst('u_cycle_race_bet_service'),
		Field::inst('u_cycle_race_bet_service_sambok'),
		Field::inst('u_cycle_race_bet_service_bokyun'),
		Field::inst('u_cycle_race_bet_service_dan'),
		Field::inst('u_cycle_race_bet_service_yun'),
		Field::inst('u_boat_race_bet_service'),
		Field::inst('u_boat_race_bet_service_sambok'),
		Field::inst('u_boat_race_bet_service_bokyun'),
		Field::inst('u_boat_race_bet_service_dan'),
		Field::inst('u_boat_race_bet_service_yun'),
		Field::inst('deposit_service_config'),
		Field::inst('u_deposit_service_first'),
		Field::inst('u_deposit_service_first_offset'),
		Field::inst('u_deposit_service'),
		Field::inst('u_deposit_service_offset'),
		Field::inst('u_finish_time_offset'),
		Field::inst('finish_time_offset_config'),
		Field::inst('result_money_offset')



		//Field::inst( 'user_pw' )


		//->options( 'place', 'id', 'name' ),
		//Field::inst( 'place_id' ),
		//Field::inst( 'race.race_no' ),

		//Field::inst( 'race.start_time' ),

		//Field::inst( 'race.entry_count' ),
		//Field::inst( 'race.stat' )


		/*		Field::inst( 'first_name' )->validator( 'Validate::notEmpty' ),
		Field::inst( 'last_name' )->validator( 'Validate::notEmpty' ),
		Field::inst( 'position' ),
		Field::inst( 'email' ),
		Field::inst( 'office' ),
		Field::inst( 'extn' ),
		Field::inst( 'age' )
			->validator( 'Validate::numeric' )
			->setFormatter( 'Format::ifEmpty', null ),
		Field::inst( 'salary' )
			->validator( 'Validate::numeric' )
			->setFormatter( 'Format::ifEmpty', null ),
		Field::inst( 'start_date' )
			->validator( 'Validate::dateFormat', array(
				"format"  => Format::DATE_ISO_8601,
				"message" => "Please enter a date in the format yyyy-mm-dd"
			) )
			->getFormatter( 'Format::date_sql_to_format', Format::DATE_ISO_8601 )
			->setFormatter( 'Format::date_format_to_sql', Format::DATE_ISO_8601 )*/
	)

	->on('preCreate', function ($editor, $values) {
		global $club_id;
		$editor
			->field('club_id')
			->setValue($club_id);
	})

	//->leftJoin( 'partner_info',     'partner_info.club_id',          '=', 'user_info.club_id' )
	//->leftJoin( 'view_banking_sum', 'user_id',          '=', 'view_banking_sum.user_id' )	
	//->leftJoin( 'view_order_sum', 'user_id',          '=', 'view_order_sum.user_id' )	

	->where('club_id', $club_id, '=')
	//	->where( 'date(banking.r_time)',date('Y-m-d',strtotime(date('Y-m-d').'-7 days')), '>')	
	//->where( 'date(order.buy_time)',date('Y-m-d',strtotime(date('Y-m-d').'-7 days')), '>')	

	->process($_POST);
//->json();


//====================
$sql = "select * from club_info as c  right outer join user_info u on u.club_id = c.id left outer join (select u.user_id u_id, sum(if(b.banking_type = 'I' and b.stat = 'E',b.amount,0)) AS `deposit_money_all`, sum(if(b.banking_type = 'O' and b.stat = 'E',b.amount,0)) AS `withdraw_money_all` from user_info u left outer join `banking` b on u.user_id = b.user_id where date(b.u_time) >= date('" . $sdate . "') and  date(b.u_time) <= date('" . $edate . "') and u.partner_id = '" . $partner_id . "' group by u.user_id ) as b on u.user_id = b.u_id left outer join (select u.user_id u_id, sum(o.`bet_money`) AS `bet_money_all`,sum(if((o.`money_type` = 'R'),o.`bet_money`,0)) AS `bet_money_real_all`,sum(if((o.`money_type` = 'S'),o.`bet_money`,0)) AS `bet_money_service_all`,sum(o.`service_money`) AS `service_money_all`,sum(o.`result_money`) AS `result_money_all`,(sum(if((o.`money_type` = 'R'),o.`bet_money`,0)) - sum(o.`result_money`)) AS `profit` from user_info u left outer join `order`o on u.user_id = o.user_id where ((o.`stat` <> 'C') and (o.`stat` <> 'R')) and date(o.buy_time) >= date('" . $sdate . "') and date(o.buy_time) <= date('" . $edate . "') and u.partner_id = '" . $partner_id . "' group by u.user_id) as o on u.user_id = o.u_id LEFT OUTER JOIN (SELECT user_id u_id, SUM(`cut_money`) AS `cut_money_all` FROM cut_result_money WHERE DATE(c_date) >= DATE('" . $sdate . "') AND DATE(c_date) <= DATE('" . $edate . "') and club_id " . ($club_id === '255' ? '!= ' : '= ') . $club_id . " GROUP BY user_id) AS cr ON u.user_id = cr.u_id where u.partner_id = '" . $partner_id . "' and u.user_level > -3 and u.user_level < 50";


include(__DIR__ . '/../../../application/configs/configdb.php');
$data = array();
$mysqli = new mysqli($host, $user, $password, $dbname);
// 연결 오류 발생 시 스크립트 종료
if ($mysqli->connect_errno) {
	die('Connect Error: ' . $mysqli->connect_error);
}
if ($result = $mysqli->query($sql)) {
	while ($row = mysqli_fetch_object($result)) {
		$row->DT_RowId = 'row_' . $row->id;
		unset($row->user_pw);
		$data[] = $row;
	}
} else {
	$data = array(0 => 'empty');
}
$d = new stdClass();
$d->data = $data;

$o = new stdClass();
$sql = "select partner_id from partner_info where club_id = " . $club_id;
if ($result = $mysqli->query($sql)) {

	while ($row = mysqli_fetch_object($result)) {
		$t = new stdClass();
		$t->label = $row->partner_id;
		$t->value = $row->partner_id;
		$o->partner_id[] = $t;
		unset($t);
	}
} else {
	$data = array(0 => 'empty');
}

$sql = "select user_id from user_info where club_id = " . $club_id;
if ($result = $mysqli->query($sql)) {

	while ($row = mysqli_fetch_object($result)) {
		$t = new stdClass();
		$t->label = $row->user_id;
		$t->value = $row->user_id;
		$o->recommender[] = $t;
		unset($t);
	}
} else {
	$data = array(0 => 'empty');
}

$sql = "select id, concat(bank_in_bank_name, ' ' , bank_in_bank_account_no, ' ' , bank_in_bank_account_name) as bank_info from bank_in_info where isuse = 'Y' and club_id = " . $club_id;
if ($result = $mysqli->query($sql)) {

	while ($row = mysqli_fetch_object($result)) {
		$t = new stdClass();
		$t->label = $row->bank_info;
		$t->value = $row->id;
		$o->bank_in_bank_id[] = $t;
		unset($t);
	}
} else {
	$data = array(0 => 'empty');
}


$d->options = $o;

echo json_encode($d, JSON_UNESCAPED_UNICODE);

$result->free(); //메모리해제

unset($data);
// 접속 종료
$mysqli->close();
