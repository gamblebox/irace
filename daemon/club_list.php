<?php
extract( $_POST );
if ( !isset( $_SESSION ) ) {
	session_start();
}
$club_id = ( int )$_SESSION[ 'club_id' ];

if ( $club_id !== 255 ) {
	echo '{"Error":"권한이 없습니다"}';
	return;
}
$user_level = $_SESSION[ 'user_level' ];
session_write_close();
/*
 * Example PHP implementation used for the index.html example
 */

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

// Build our Editor instance and process the data coming from _POST
Editor::inst( $db, 'club_info' )->fields(
		//Field::inst( 'club_info.banking_type' ),
		Field::inst( 'club_info.club_name' ),
		Field::inst( 'club_info.club_code' ),
		Field::inst( 'club_info.version' ),
		Field::inst( 'club_info.domain' ),
		Field::inst( 'club_info.domain_open' ),
		Field::inst( 'club_info.admin_theme' ),
		Field::inst( 'club_info.client_theme' ),
		Field::inst( 'club_info.user_theme' ),
		Field::inst( 'club_info.isuse_mobile' ),
		Field::inst( 'club_info.isjoin' ),
		Field::inst( 'club_info.m_isjoin' ),
		Field::inst( 'club_info.club_top_info' ),
		Field::inst( 'club_info.club_top_notice' ),
		Field::inst( 'club_info.club_level' ),
		Field::inst( 'club_info.club_broad_srv' ),
		Field::inst( 'club_info.finish_time_offset' ),
		Field::inst( 'club_info.auto_next' ),
		Field::inst( 'club_info.ischoicerace' ),
		Field::inst( 'club_info.iskralive' ),
		Field::inst( 'club_info.ischoiceplace' ),
		Field::inst( 'club_info.isbackupbet' ),
		Field::inst( 'club_info.bet_unit' ),
		Field::inst( 'club_info.tawk_id' ),
		Field::inst( 'club_info.withdraw_bank_info_confirm' ),
		Field::inst( 'club_info.isuse_korea_race_sambok' ),
		Field::inst( 'club_info.dup_login' ),
		Field::inst( 'club_info.iscalendar' ),
		Field::inst( 'club_info.calendar_deposit' ),
		Field::inst( 'club_info.calendar_bet' ),
		Field::inst( 'club_info.recommender_service' ),
		Field::inst( 'club_info.allin_service_isauto' ),
		Field::inst( 'club_info.allin_service' ),
		Field::inst( 'club_info.allin_service_min_offset' ),
		Field::inst( 'club_info.allin_service_max_offset' ),
		Field::inst( 'club_info.allin_service2' ),
		Field::inst( 'club_info.allin_service_min_offset2' ),
		Field::inst( 'club_info.allin_service_max_offset2' ),
		Field::inst( 'club_info.korea_race_bet_service' ),
		Field::inst( 'club_info.korea_race_bet_service_samssang' ),
		Field::inst( 'club_info.korea_race_bet_service_sambok' ),
		Field::inst( 'club_info.korea_race_bet_service_bokyun' ),
		Field::inst( 'club_info.korea_race_bet_service_dan' ),
		Field::inst( 'club_info.korea_race_bet_service_yun' ),
		Field::inst( 'club_info.japan_race_bet_service' ),
		Field::inst( 'club_info.japan_race_bet_service_samssang' ),
		Field::inst( 'club_info.japan_race_bet_service_sambok' ),
		Field::inst( 'club_info.japan_race_bet_service_bokyun' ),
		Field::inst( 'club_info.japan_race_bet_service_dan' ),
		Field::inst( 'club_info.japan_race_bet_service_yun' ),
		Field::inst( 'club_info.cycle_race_bet_service' ),
		Field::inst( 'club_info.cycle_race_bet_service_sambok' ),
		Field::inst( 'club_info.cycle_race_bet_service_bokyun' ),
		Field::inst( 'club_info.cycle_race_bet_service_dan' ),
		Field::inst( 'club_info.cycle_race_bet_service_yun' ),
		Field::inst( 'club_info.boat_race_bet_service' ),
		Field::inst( 'club_info.boat_race_bet_service_sambok' ),
		Field::inst( 'club_info.boat_race_bet_service_bokyun' ),
		Field::inst( 'club_info.boat_race_bet_service_dan' ),
		Field::inst( 'club_info.boat_race_bet_service_yun' ),
		Field::inst( 'club_info.deposit_service_first' ),
		Field::inst( 'club_info.deposit_service_first_min_offset' ),
		Field::inst( 'club_info.deposit_service_first_max_offset' ),
		Field::inst( 'club_info.deposit_service' ),
		Field::inst( 'club_info.deposit_service_offset' ),
		Field::inst( 'club_info.bet_limit_bokyun_use' ),
		Field::inst( 'club_info.bet_limit_sambok_use' ),
		Field::inst( 'club_info.bet_limit_samssang_use' ),
		Field::inst( 'club_info.bet_limit_bokyun' ),
		Field::inst( 'club_info.bet_limit_sambok' ),
		Field::inst( 'club_info.bet_limit_samssang' ),
		Field::inst( 'club_info.korea_race_ticketing_entry' ),
		Field::inst( 'club_info.japan_race_ticketing_entry' ),
		Field::inst( 'club_info.cycle_race_ticketing_entry' ),
		Field::inst( 'club_info.boat_race_ticketing_entry' ),
		Field::inst( 'club_info.korea_race_ticketing_bokyun_entry' ),
		Field::inst( 'club_info.korea_race_ticketing_sambok_entry' ),
		Field::inst( 'club_info.korea_race_ticketing_samssang_entry' ),
		Field::inst( 'club_info.japan_race_ticketing_bokyun_entry' ),
		Field::inst( 'club_info.japan_race_ticketing_sambok_entry' ),
		Field::inst( 'club_info.japan_race_ticketing_samssang_entry' ),
		Field::inst( 'club_info.korea_race_ticketing_type' ),
		Field::inst( 'club_info.japan_race_ticketing_type' ),
		Field::inst( 'club_info.cycle_race_ticketing_type' ),
		Field::inst( 'club_info.boat_race_ticketing_type' ),
		Field::inst( 'club_info.ticketing_association' ),
		Field::inst( 'club_info.due_money' ),
		Field::inst( 'club_info.due_date' ),
		Field::inst( 'view_club_admin.admin' )
	)
	//->distinct( true )
	//->leftJoin( 'club_info',     'club_id',          '=', 'club_info.id' )
	->leftJoin( 'view_club_admin', 'view_club_admin.club_id', '=', 'club_info.id' )
	//->leftJoin( 'view_order_sum', 'user_id',          '=', 'view_order_sum.user_id' )	
	//->distinct( true )
	->where( 'club_info.id', 255, '!=' )->where( 'club_info.id', 0, '!=' )->where( 'club_info.club_level', -1, '>' )
	//->where( 'user_info.club_user_id', '총판통합', '!=' )	
	//->where( 'user_info.user_level', 100, '=' )
	//->where( 'date(bbs_qna.u_time)',date('Y-m-d',strtotime(date('Y-m-d').'-2 days')), '>')	
	//->where( 'date(order.buy_time)',date('Y-m-d',strtotime(date('Y-m-d').'-7 days')), '>')	

->process( $_POST )->json();