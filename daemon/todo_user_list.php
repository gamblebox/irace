<?php
$club_id=1;
extract($_POST); 
if ( !isset( $_SESSION ) ) {
	session_start();
}
$user_id = $_SESSION['user_id'];
if ($_SESSION['club_id'] !== '255'){
	$club_id = (int)$_SESSION['club_id'];
}
$user_level = $_SESSION['user_level'];
$club_code = $_SESSION['club_code'];
session_write_close();
/*
 * Example PHP implementation used for the index.html example
 */

// DataTables PHP library
include( "DataTables.php" );

// Alias Editor classes so they are easy to use
use
	DataTables\Editor,
	DataTables\Editor\Field,
	DataTables\Editor\Format,
	DataTables\Editor\Mjoin,
	DataTables\Editor\Upload,
	DataTables\Editor\Validate;

// Build our Editor instance and process the data coming from _POST
Editor::inst( $db, 'user_info' )
	->fields(
		//Field::inst( 'id' ),
		Field::inst( 'user_id' ),
		//Field::inst( 'club_name' ),
		Field::inst( 'club_user_id' ), 
		Field::inst( 'nick_name' ), 
		Field::inst( 'partner_id' )				
		    ->options( 'partner_info', 'partner_id', 'partner_id', function ($q) {
				global $club_id;
        $q->where( 'club_id', $club_id, ($club_id === '255'? '!=': '=') );
 			   } ),
		Field::inst( 'club_id' ),
		Field::inst( 'login_ip' ),
		Field::inst( 'mobile' ),
		Field::inst( 'mobile_web' ),
		Field::inst( 'phone' ),
		Field::inst( 'cdomain' ),
		Field::inst( 'udomain' ),
		Field::inst( 'recommender' )
		    ->options( 'user_info', 'user_id', 'user_id', function ($q) {
				global $club_id;
        $q->where( 'club_id', $club_id, ($club_id === '255'? '!=': '=') );
 			   } ),		
		Field::inst( 'bank_in_bank_id' )
		    ->options( 'bank_in_info', 'id', 'bank_in_nick_name', function ($q) {
				global $club_id;
        $q->where( 'club_id', $club_id, ($club_id === '255'? '!=': '=') )
						->where( 'isuse', 'Y', '=' );
 			   } ),				
		Field::inst( 'bank_out_bank_name' ),
		Field::inst( 'bank_out_bank_account_no' ),				
		Field::inst( 'bank_out_bank_account_name' ),				
		Field::inst( 'bank_out_bank_info_ok' ),	
		Field::inst( 'batch_allin_service' ),								
		Field::inst( 'money_real' ),								
		Field::inst( 'money_service' ),
		Field::inst( 'user_level' ),
		Field::inst( 'r_time' ),
		Field::inst( 'u_time' ),
		Field::inst( 'login_time' ),
		Field::inst( 'stat' ),
		Field::inst( 'bet_service_config' ),
		Field::inst( 'allin_service_config' ),
		Field::inst( 'u_allin_service' ),
		Field::inst( 'u_allin_service_min_offset' ),
		Field::inst( 'u_allin_service_max_offset' ),
		Field::inst( 'u_allin_service2' ),
		Field::inst( 'u_allin_service_min_offset2' ),
		Field::inst( 'u_allin_service_max_offset2' ),
		Field::inst( 'game_config' ),
		Field::inst( 'u_bet_limit_bokyun_use' ),
		Field::inst( 'u_bet_limit_sambok_use' ),
		Field::inst( 'u_bet_limit_samssang_use' ),
		Field::inst( 'u_bet_limit_bokyun' ),
		Field::inst( 'u_bet_limit_sambok' ),
		Field::inst( 'u_bet_limit_samssang' ),
		Field::inst( 'u_korea_race_ticketing_entry' ),
		Field::inst( 'u_japan_race_ticketing_entry' ),
		Field::inst( 'u_cycle_race_ticketing_entry' ),
		Field::inst( 'u_boat_race_ticketing_entry' ),
		Field::inst( 'u_korea_race_ticketing_bokyun_entry' ),
		Field::inst( 'u_korea_race_ticketing_sambok_entry' ),
		Field::inst( 'u_korea_race_ticketing_samssang_entry' ),
		Field::inst( 'u_japan_race_ticketing_bokyun_entry' ),
		Field::inst( 'u_japan_race_ticketing_sambok_entry' ),
		Field::inst( 'u_japan_race_ticketing_samssang_entry' ),
		Field::inst( 'u_korea_race_ticketing_type' )
			->getFormatter( 'Format::explode', ',' )
			->setFormatter( 'Format::implode', ',' ),
		Field::inst( 'u_japan_race_ticketing_type' )
			->getFormatter( 'Format::explode', ',' )
			->setFormatter( 'Format::implode', ',' ),
		Field::inst( 'u_cycle_race_ticketing_type' )
			->getFormatter( 'Format::explode', ',' )
			->setFormatter( 'Format::implode', ',' ),
		Field::inst( 'u_boat_race_ticketing_type' )
			->getFormatter( 'Format::explode', ',' )
			->setFormatter( 'Format::implode', ',' ),
		Field::inst( 'u_ticketing_association' )
			->getFormatter( 'Format::explode', ',' )
			->setFormatter( 'Format::implode', ',' ),
		Field::inst( 'u_korea_race_bet_service' ),
		Field::inst( 'u_korea_race_bet_service_samssang' ),
		Field::inst( 'u_korea_race_bet_service_sambok' ),
		Field::inst( 'u_korea_race_bet_service_bokyun' ),
		Field::inst( 'u_korea_race_bet_service_dan' ),
		Field::inst( 'u_korea_race_bet_service_yun' ),
		Field::inst( 'u_japan_race_bet_service' ),
		Field::inst( 'u_japan_race_bet_service_sambok' ),
		Field::inst( 'u_japan_race_bet_service_bokyun' ),
		Field::inst( 'u_japan_race_bet_service_dan' ),
		Field::inst( 'u_japan_race_bet_service_yun' ),
		Field::inst( 'u_cycle_race_bet_service' ),
		Field::inst( 'u_cycle_race_bet_service_sambok' ),
		Field::inst( 'u_cycle_race_bet_service_bokyun' ),
		Field::inst( 'u_cycle_race_bet_service_dan' ),
		Field::inst( 'u_cycle_race_bet_service_yun' ),
		Field::inst( 'u_boat_race_bet_service' ),
		Field::inst( 'u_boat_race_bet_service_sambok' ),
		Field::inst( 'u_boat_race_bet_service_bokyun' ),
		Field::inst( 'u_boat_race_bet_service_dan' ),
		Field::inst( 'u_boat_race_bet_service_yun' ),
		Field::inst( 'deposit_service_config' ),
		Field::inst( 'u_deposit_service_first' ),
		Field::inst( 'u_deposit_service_first_max_offset' ),
		Field::inst( 'u_deposit_service' ),
		Field::inst( 'u_deposit_service_offset' ),
		Field::inst( 'u_finish_time_offset' ),
		Field::inst( 'u_japan_race_finish_time_offset' ),
		Field::inst( 'finish_time_offset_config' ),
		Field::inst( 'result_money_offset_config' ),
		Field::inst( 'u_result_money_offset' ),
		Field::inst( 'u_korea_race_result_money_offset' ),
		Field::inst( 'u_japan_race_result_money_offset' ),
		Field::inst( 'user_pw' )
	)

	->on( 'preEdit', function ( $editor, $id, $values) {
			global $club_code ;
			if (isset($values['club_user_id'])){
				$user_id =  '_' . $club_code . '_' . $values['club_user_id'];
				$editor
						->field( 'user_id' )
						->setValue($user_id);
			}
	} )	

	->where( 'club_id',$club_id, ($club_id === '255'? '!=': '='))
	->where( 'user_level',0, '=')
//	->where( 'date(banking.r_time)',date('Y-m-d',strtotime(date('Y-m-d').'-7 days')), '>')	
	//->where( 'date(order.buy_time)',date('Y-m-d',strtotime(date('Y-m-d').'-7 days')), '>')	

	->process( $_POST )
	->json();