<?php

$select_num = '2-6';
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
$race_id =  5820;
if ($_POST['race_id']) {
	$race_id = $_POST['race_id'];
}
//$date = date('Y-m-d');
$date = date('2016-05-25');
if ($_POST['date']) {
	$date = $_POST['date'];
}
extract($_POST);
if (!isset($_SESSION)) {
	session_start();
}
$edate = date('Y-m-d', strtotime($date . '+' . '1' . ' days'));
$user_id = $_SESSION['user_id'];
$club_id = $_SESSION['club_id'];
$user_level = $_SESSION['user_level'];
$partner_id = $_SESSION['partner_id'];
session_write_close();

/*
 * Example PHP implementation used for the index.html example
 */
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


// DataTables PHP library
include("DataTables.php");

// Alias Editor classes so they are easy to use
use
	DataTables\Editor,
	DataTables\Editor\Field,
	DataTables\Editor\Format,
	DataTables\Editor\Mjoin,
	DataTables\Editor\Upload,
	DataTables\Editor\Validate;

Editor::inst($db, 'order')
	->fields(
		Field::inst('place.name'),
		Field::inst('race.race_no'),
		Field::inst('race.start_time'),
		Field::inst('order.race_id'),
		Field::inst('order.type'),
		Field::inst('order.place_1'),
		Field::inst('order.place_2'),
		Field::inst('order.place_3'),
		Field::inst('order.money_type'),
		Field::inst('order.stat'),
		Field::inst('order.bet_money'),
		Field::inst('order.service_money'),
		Field::inst('order.result_money'),
		Field::inst('order.buy_time'),
		Field::inst('order.update_time'),
		Field::inst('order.user_id'),
		Field::inst('user_info.user_id'),
		Field::inst('user_info.club_user_id'),
		Field::inst('user_info.partner_id'),
		Field::inst('user_info.nick_name'),
		Field::inst('association_info.name')
	)
	->leftJoin('race',     'race.id',          '=', 'order.race_id')
	->leftJoin('place',     'place.id',          '=', 'race.place_id')
	->leftJoin('association_info',     'association_info.id',          '=', 'place.association_id')
	->leftJoin('user_info',     'user_info.user_id',          '=', 'order.user_id')

	->where('user_info.club_id', $club_id)
	->where('user_info.partner_id', $partner_id)
	->where('order.type', $type, ($type === '모든승식' ? '!=' : '='))
	->where('order.place_1', $place_1)
	->where('order.place_2', $place_2)
	->where('order.place_3', $place_3)
	->where('race.id', $race_id)
	->where('order.buy_time', date($date), '>=')
	->where('order.buy_time', date($edate), '<')
	//->where( 'race_id', $race_id )
	->process($_POST)
	->json();
