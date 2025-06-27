<?php

?>
<?php
$club_id = 1;
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
Editor::inst($db, 'user_info')
	->fields(
		//Field::inst( 'user_info.banking_type' ),
		Field::inst('club_info.club_name'),
		Field::inst('club_info.domain'),
		Field::inst('user_info.user_id'),
		Field::inst('user_info.nick_name'),
		Field::inst('user_info.partner'),
		Field::inst('user_info.login_ip'),
		Field::inst('user_info.phone'),
		Field::inst('user_info.recomender'),
		Field::inst('user_info.bank_in_bank_name'),
		Field::inst('user_info.bank_in_bank_account_no'),
		Field::inst('user_info.bank_in_bank_account_name'),
		Field::inst('user_info.bank_out_bank_name'),
		Field::inst('user_info.bank_out_bank_account_no'),
		Field::inst('user_info.bank_out_bank_account_name'),
		Field::inst('user_info.money_real'),
		Field::inst('user_info.money_service'),
		Field::inst('user_info.user_level'),
		Field::inst('user_info.r_time'),
		Field::inst('user_info.u_time'),
		Field::inst('user_info.stat'),
		Field::inst('view_order_sum.bet_money_all'),
		Field::inst('view_order_sum.bet_money_real_all'),
		Field::inst('view_order_sum.bet_money_service_all'),
		Field::inst('view_order_sum.service_money_all'),
		Field::inst('view_order_sum.result_money_all'),
		Field::inst('view_order_sum.profit'),
		Field::inst('view_banking_sum.deposit_money_all'),
		Field::inst('view_banking_sum.withdraw_money_all')

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

	->leftJoin('club_info',     'user_info.club_id',          '=', 'club_info.id')
	->leftJoin('view_banking_sum', 'user_info.user_id',          '=', 'view_banking_sum.user_id')
	->leftJoin('view_order_sum', 'user_info.user_id',          '=', 'view_order_sum.user_id')

	->where('user_info.club_id', $club_id, '=')
	//	->where( 'date(banking.r_time)',date('Y-m-d',strtotime(date('Y-m-d').'-7 days')), '>')	
	//->where( 'date(order.buy_time)',date('Y-m-d',strtotime(date('Y-m-d').'-7 days')), '>')	

	->process($_POST)
	->json();
