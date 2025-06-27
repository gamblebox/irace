<?php
$club_id = 1;
if ($_POST['club_id']) {
	$club_id = $_POST['club_id'];
}
extract($_POST);
if (!isset($_SESSION)) {
	session_start();
}
$user_id = $_SESSION['user_id'];
if ($_SESSION['club_id'] !== '255') {
	$club_id = (int)$_SESSION['club_id'];
}
$user_level = $_SESSION['user_level'];
session_write_close();
/*
 * Example PHP implementation used for the index.html example
 */



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


if ($club_id == 255) {
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
			Field::inst('user_info.nick_name'),
			Field::inst('association_info.name')

		)
		->leftJoin('race',     'race.id',          '=', 'order.race_id')
		->leftJoin('place',     'place.id',          '=', 'race.place_id')
		->leftJoin('association_info',     'association_info.id',          '=', 'place.association_id')
		->leftJoin('user_info',     'user_info.user_id',          '=', 'order.user_id')

		->where('order.buy_time', date('Y-m-d', strtotime(date('Y-m-d') . '-7 days')), '>=')
		//->where( 'race_id', $race_id )
		->process($_POST)
		->json();
} else {
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
			Field::inst('user_info.nick_name'),
			Field::inst('association_info.name')

		)
		->leftJoin('race',     'race.id',          '=', 'order.race_id')
		->leftJoin('place',     'place.id',          '=', 'race.place_id')
		->leftJoin('association_info',     'association_info.id',          '=', 'place.association_id')
		->leftJoin('user_info',     'user_info.user_id',          '=', 'order.user_id')

		->where('user_info.club_id', $club_id)
		->where('order.buy_time', date('Y-m-d', strtotime(date('Y-m-d') . '-7 days')), '>=')
		//->where( 'race_id', $race_id )
		->process($_POST)
		->json();
}
