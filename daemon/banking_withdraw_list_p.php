<?php
$club_id = 1;
extract($_POST);
if (!isset($_SESSION)) {
	session_start();
}
$partner_id = $_SESSION['partner_id'];
$club_id = $_SESSION['club_id'];
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
Editor::inst($db, 'banking')
	->fields(
		Field::inst('banking.banking_type'),
		Field::inst('banking.user_id')
			->options('user_info', 'user_id', array('club_user_id', 'nick_name'), null, function ($row) {
				return $row['club_user_id'] . ' (' . $row['nick_name'] . ')';
			})
			->validator('Validate::dbValues'),

		Field::inst('user_info.nick_name'),
		Field::inst('user_info.club_user_id'),
		Field::inst('banking.amount'),
		Field::inst('banking.bank_name'),
		Field::inst('banking.bank_account_no'),
		Field::inst('banking.bank_account_name'),
		Field::inst('banking.r_time'),
		Field::inst('banking.u_time'),
		Field::inst('banking.stat'),
		Field::inst('banking.isfirst')
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
	->leftJoin('user_info', 'user_info.user_id',          '=', 'banking.user_id')
	->leftJoin('club_info',     'club_info.id',          '=', 'user_info.club_id')

	->where('club_info.id', $club_id, '=')
	->where('user_info.partner_id', $partner_id, '=')
	->where('banking.banking_type', 'O', '=')
	->where('banking.r_time', date('Y-m-d', strtotime(date('Y-m-d') . '-2 days')), '>=')

	->process($_POST)
	->json();
