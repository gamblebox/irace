<?php

?>
<?php
extract($_POST);
if (!isset($_SESSION)) {
	session_start();
}
$user_id = $_SESSION['user_id'];
$club_id = $_SESSION['club_id'];
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

// Build our Editor instance and process the data coming from _POST
Editor::inst($db, 'race')
	->fields(
		Field::inst('association_info.name'),
		Field::inst('place.name'),
		Field::inst('race.place_id')
			->options('place', 'id', 'name'),
		//Field::inst( 'place_id' ),
		Field::inst('race.race_no'),

		Field::inst('race.start_time'),

		Field::inst('race.entry_count'),
		Field::inst('race.stat')


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

	->leftJoin('place',     'place.id',          '=', 'race.place_id')
	->leftJoin('association_info', 'association_info.id',          '=', 'place.association_id')
	->where('date(race.start_time)', date('Y-m-d', strtotime(date('Y-m-d') . '-1 days')), '>')

	->process($_POST)
	->json();
