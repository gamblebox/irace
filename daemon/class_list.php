<?php

//$club_id=1;
extract( $_POST );
$a = $action;
if ( !isset( $_SESSION ) ) {
	session_start();
}
if ( $_SESSION[ 'club_id' ] !== '255' ) {
	$club_id = ( int )$_SESSION[ 'club_id' ];
}
$club_code = $_SESSION[ 'club_code' ];
$club_id = ( int )$club_id;
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
$editor = Editor::inst( $db, 'user_class' )->fields(
	Field::inst( 'id' ),
    Field::inst( 'club_id' ),
    Field::inst( 'class_name' )->validator( Validate::notEmpty() ),
    Field::inst( 'class_memo' ),
    Field::inst( 'class_isuse' ),
    Field::inst( 'sort' ),
	Field::inst( 'r_time' )->setFormatter( 'Format::ifEmpty', '2000-01-01' ),
	Field::inst( 'u_time' )->setFormatter( 'Format::ifEmpty', '2000-01-01' ),
	Field::inst( 'c_iskralive' ),
	Field::inst( 'c_bet_limit_powerball' ),
	Field::inst( 'c_bet_limit_powerball_pb' ),
	Field::inst( 'c_bet_limit_powerball_nb' ),
	Field::inst( 'c_bet_limit_powerball_mf' ),
// 	Field::inst( 'c_result_money_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_powerball_result_money_offset' )->validator(Validate::minNum( 10000 )),
    Field::inst( 'c_korea_race_sb_result_money_offset' )->validator(Validate::minNum( 10000 )),
    Field::inst( 'c_korea_race_j_result_money_offset' )->validator(Validate::minNum( 10000 )),
	Field::inst( 'c_japan_race_result_money_offset' )->validator(Validate::minNum( 10000 )),
	Field::inst( 'c_jra_race_result_money_offset' )->validator(Validate::minNum( 10000 )),
	Field::inst( 'c_jcycle_race_result_money_offset' )->validator(Validate::minNum( 10000 )),
	Field::inst( 'c_jboat_race_result_money_offset' )->validator(Validate::minNum( 10000 )),
	Field::inst( 'c_jbike_race_result_money_offset' )->validator(Validate::minNum( 10000 )),
    Field::inst( 'c_osr_race_result_money_offset' )->validator(Validate::minNum( 10000 )),
    Field::inst( 'c_osh_race_result_money_offset' )->validator(Validate::minNum( 10000 )),
    Field::inst( 'c_osg_race_result_money_offset' )->validator(Validate::minNum( 10000 )),
    Field::inst( 'c_cycle_race_result_money_offset' )->validator(Validate::minNum( 10000 )),
    Field::inst( 'c_boat_race_result_money_offset' )->validator(Validate::minNum( 10000 )),
    Field::inst( 'c_allin_service' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_allin_service_min_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_allin_service_max_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_allin_service2' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_allin_service_min_offset2' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_allin_service_max_offset2' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_bet_limit_bokyun_use' ),
	Field::inst( 'c_bet_limit_sambok_use' ),
	Field::inst( 'c_bet_limit_samssang_use' ),
	Field::inst( 'c_bet_limit_bokyun' )->validator(Validate::maxNum( 255 )),
	Field::inst( 'c_bet_limit_sambok' )->validator(Validate::maxNum( 255 )),
	Field::inst( 'c_bet_limit_samssang' )->validator(Validate::maxNum( 255 )),
	Field::inst( 'c_bet_limit_japanrace_bokyun_use' ),
	Field::inst( 'c_bet_limit_japanrace_sambok_use' ),
	Field::inst( 'c_bet_limit_japanrace_samssang_use' ),
	Field::inst( 'c_bet_limit_japanrace_bokyun' )->validator(Validate::maxNum( 255 )),
	Field::inst( 'c_bet_limit_japanrace_sambok' )->validator(Validate::maxNum( 255 )),
	Field::inst( 'c_bet_limit_japanrace_samssang' )->validator(Validate::maxNum( 255 )),
	Field::inst( 'c_bet_limit_jra_race_bokyun_use' ),
	Field::inst( 'c_bet_limit_jra_race_sambok_use' ),
	Field::inst( 'c_bet_limit_jra_race_samssang_use' ),
	Field::inst( 'c_bet_limit_jra_race_bokyun' )->validator(Validate::maxNum( 255 )),
	Field::inst( 'c_bet_limit_jra_race_sambok' )->validator(Validate::maxNum( 255 )),
	Field::inst( 'c_bet_limit_jra_race_samssang' )->validator(Validate::maxNum( 255 )),
	Field::inst( 'c_bet_limit_jcycle_race_sambok_use' ),
	Field::inst( 'c_bet_limit_jcycle_race_sambok' )->validator(Validate::maxNum( 255 )),
	Field::inst( 'c_bet_limit_jboat_race_sambok_use' ),
	Field::inst( 'c_bet_limit_jboat_race_sambok' )->validator(Validate::maxNum( 255 )),
	Field::inst( 'c_korea_race_ticketing_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_japan_race_ticketing_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jra_race_ticketing_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jcycle_race_ticketing_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jboat_race_ticketing_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jbike_race_ticketing_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_osr_race_ticketing_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_osh_race_ticketing_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_osg_race_ticketing_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_cycle_race_ticketing_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_boat_race_ticketing_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_korea_race_ticketing_bokyun_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_korea_race_ticketing_sambok_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_korea_race_ticketing_samssang_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_japan_race_ticketing_bokyun_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_japan_race_ticketing_sambok_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_japan_race_ticketing_samssang_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jra_race_ticketing_bokyun_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jra_race_ticketing_sambok_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jra_race_ticketing_samssang_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_korea_race_ticketing_type' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_japan_race_ticketing_type' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jra_race_ticketing_type' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jcycle_race_ticketing_type' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jboat_race_ticketing_type' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jbike_race_ticketing_type' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_osr_race_ticketing_type' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_osh_race_ticketing_type' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_osg_race_ticketing_type' )->setFormatter( 'Format::nullEmpty' ),

/*	->setFormatter( function ( $val, $data, $opts ) {
		if ( $val === '' ) {
			return null;
		} else {
			return implode( $val );
		}
	} ),*/
	Field::inst( 'c_cycle_race_ticketing_type' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_boat_race_ticketing_type' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_ticketing_association' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_korea_race_bet_service' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_korea_race_bet_service_samssang' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_korea_race_bet_service_sambok' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_korea_race_bet_service_bokyun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_korea_race_bet_service_dan' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_korea_race_bet_service_yun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_japan_race_bet_service' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_japan_race_bet_service_samssang' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_japan_race_bet_service_sambok' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_japan_race_bet_service_bokyun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_japan_race_bet_service_dan' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_japan_race_bet_service_yun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jra_race_bet_service' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jra_race_bet_service_samssang' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jra_race_bet_service_sambok' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jra_race_bet_service_bokyun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jra_race_bet_service_dan' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jra_race_bet_service_yun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jcycle_race_bet_service' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jcycle_race_bet_service_samssang' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jcycle_race_bet_service_sambok' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jcycle_race_bet_service_bokyun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jboat_race_bet_service' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jboat_race_bet_service_samssang' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jboat_race_bet_service_sambok' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jboat_race_bet_service_bokyun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jbike_race_bet_service' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jbike_race_bet_service_samssang' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jbike_race_bet_service_sambok' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jbike_race_bet_service_bokyun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_osr_race_bet_service' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_osr_race_bet_service_samssang' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_osr_race_bet_service_sambok' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_osr_race_bet_service_bokyun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_osr_race_bet_service_dan' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_osr_race_bet_service_yun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_osh_race_bet_service' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_osh_race_bet_service_samssang' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_osh_race_bet_service_sambok' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_osh_race_bet_service_bokyun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_osh_race_bet_service_dan' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_osh_race_bet_service_yun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_osg_race_bet_service' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_osg_race_bet_service_samssang' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_osg_race_bet_service_sambok' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_osg_race_bet_service_bokyun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_osg_race_bet_service_dan' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_osg_race_bet_service_yun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_cycle_race_bet_service' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_cycle_race_bet_service_sambok' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_cycle_race_bet_service_samssang' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_cycle_race_bet_service_bokyun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_cycle_race_bet_service_dan' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_cycle_race_bet_service_yun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_boat_race_bet_service' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_boat_race_bet_service_sambok' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_boat_race_bet_service_samssang' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_boat_race_bet_service_bokyun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_boat_race_bet_service_dan' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_boat_race_bet_service_yun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_deposit_service_first' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_deposit_service_first_min_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_deposit_service_first_max_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_deposit_service' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_deposit_service_offset' )->setFormatter( 'Format::nullEmpty' ),
// 	Field::inst( 'c_finish_time_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_korea_race_finish_time_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_korea_race_sb_finish_time_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_korea_race_j_finish_time_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_japan_race_finish_time_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jra_race_finish_time_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jcycle_race_finish_time_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jboat_race_finish_time_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_jbike_race_finish_time_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_osr_race_finish_time_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_osh_race_finish_time_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_osg_race_finish_time_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_cycle_race_finish_time_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'c_boat_race_finish_time_offset' )->setFormatter( 'Format::nullEmpty' )
)

->on( 'preCreate', function ( $editor, $values ) {
	global $club_id;
	$editor
		->field( 'club_id' )->setValue( $club_id );
	/*	$user_id = '_' . $club_code . '_' . $values[ 'club_user_id' ];
		$editor
			->field( 'user_id' )->setValue( $user_id );*/
} )

//->leftJoin( 'club_info',  'club_info.id', '=', 'club_id' )
//->leftJoin( 'view_banking_sum', 'user_id',          '=', 'view_banking_sum.user_id' )	
//->leftJoin( 'view_order_sum', 'user_id',          '=', 'view_order_sum.user_id' )	

->where( 'club_id', $club_id, ( $club_id === 255 ? '!=' : '=' ) )
->where( 'club_id', 0, '!=' )
//->where( 'id', 0, '!=' )
	//	->where( 'date(banking.r_time)',date('Y-m-d',strtotime(date('Y-m-d').'-7 days')), '>')	
	//->where( 'date(order.buy_time)',date('Y-m-d',strtotime(date('Y-m-d').'-7 days')), '>')	

->process( $_POST )
->json();
?>