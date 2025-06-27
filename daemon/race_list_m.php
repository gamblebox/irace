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
extract($_POST);
if (!isset($_SESSION)) {
    session_start();
}
$user_id = $_SESSION['user_id'];
if ($_SESSION['club_id'] !== '255') {
    $club_id = (int) $_SESSION['club_id'];
}
$user_level = $_SESSION['user_level'];
session_write_close();

// DataTables PHP library
include( "DataTables.php" );

// Alias Editor classes so they are easy to use
use
DataTables\ Editor,
DataTables\ Editor\ Field,
DataTables\ Editor\ Format,
DataTables\ Editor\ Mjoin,
DataTables\ Editor\ Upload,
DataTables\ Editor\ Validate;

//$user_id = 'goldrace';
//$race_id = 1827;
// Build our Editor instance and process the data coming from _POST


//if (isset($post['race_id'])) {
//$race_id = $_POST['race_id'];
//}

Editor::inst( $db, 'race' )->fields(
	Field::inst( 'race.id' ),
	Field::inst( 'place.name' ),
	Field::inst( 'place.own_id' ),
	Field::inst( 'race.place_id' )->options( 'place', 'id', 'name' ),
	Field::inst( 'race.rk_race_code' ),
	Field::inst( 'race.association_code' ),
	Field::inst( 'race.race_no' ),
	Field::inst( 'race.place_1' ),
	Field::inst( 'race.place_2' ),
	Field::inst( 'race.place_3' ),
	Field::inst( 'race.place_oe' ),
	Field::inst( 'race.odds_dan' ),
	Field::inst( 'race.odds_yun' ),
	Field::inst( 'race.odds_bok' ),
	Field::inst( 'race.odds_ssang' ),
	Field::inst( 'race.odds_bokyun' ),
	Field::inst( 'race.odds_sambok' ),
	Field::inst( 'race.odds_samssang' ),
	Field::inst( 'race.odds_oe' ),	
	Field::inst( 'race.odds_all' ),
    Field::inst( 'race.start_date' ),
	Field::inst( 'race.start_time' ),
	Field::inst( 'race.entry_count' ),
	Field::inst( 'race.isuse' ),
	Field::inst( 'race.race_length' ),
	Field::inst( 'race.change' ),
	Field::inst( 'race.cancel_entry_no' ),
	Field::inst( 'race.stat' ),
	Field::inst( 'race.race_type' ),
	Field::inst( 'race.remark' ),
	/*		Field::inst( 'view_place_result.place_1' ),
			Field::inst( 'view_place_result.place_2' ),
			Field::inst( 'view_place_result.place_3' ),
			Field::inst( 'view_place_result.dan' ),
			Field::inst( 'view_place_result.yun' ),
			Field::inst( 'view_place_result.bok' ),
			Field::inst( 'view_place_result.ssang' ),
			Field::inst( 'view_place_result.sambok' ),
			Field::inst( 'view_place_result.samssang' ),
			Field::inst( 'view_place_result.bokyun' ),*/
	Field::inst( 'association_info.name' )

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
//->leftJoin( 'race',     'race.id',          '=', 'order.race_id' )
->leftJoin( 'place', 'place.id', '=', 'race.place_id' )->leftJoin( 'association_info', 'association_info.id', '=', 'place.association_id' )
//->leftJoin( 'view_place_result',     'view_place_result.race_id',          '=', 'race.id' )	

//->where( 'user_info.club_id', $club_id )
->where( 'race.start_date', $date, '=' )
	//->where( 'race_id', $race_id )
->process( $_POST )->json();

	
