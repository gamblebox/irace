<?php
//$club_id = 1;
extract( $_POST );
if ( !isset( $_SESSION ) ) {
	session_start();
}
//$user_id = $_SESSION['user_id'];
if ( $_SESSION[ 'club_id' ] !== '255' ) {
	$club_id = $_SESSION[ 'club_id' ];
}
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
Editor::inst( $db, 'bank_in_info_phrases' )->fields(
	Field::inst( 'bank_in_info_phrases.club_id' ),
	Field::inst( 'bank_in_info_phrases.subject' ),
	Field::inst( 'bank_in_info_phrases.class_id' )->options( 'user_class', 'id', 'class_name', function ( $q ) {
		global $club_id;
		$q->where( function ( $r ) {
			global $club_id;
			$r->where( 'club_id', $club_id, ( $club_id === 255 ? '!=' : '=' ) );
			$r->or_where( 'id', 0, '=' );
		} );//->where( 'class_isuse', 'Y', '=' );
	} ),
	Field::inst( 'bank_in_info_phrases.u_time' ),
	Field::inst( 'bank_in_info_phrases.memo' ),
	Field::inst( 'user_class.class_name' ),
	Field::inst( 'club_info.club_code' ),
	Field::inst( 'club_info.club_name' )

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
)->on( 'preCreate', function ( $editor, $values ) {
	global $club_id;
	if ($club_id === 255){
		$editor
		->field( 'bank_in_info_phrases.club_id' )->setValue( 0 );
	}
	else{
		$editor
		->field( 'bank_in_info_phrases.club_id' )->setValue( $club_id );
	}	
} )

->leftJoin( 'club_info', 'bank_in_info_phrases.club_id', '=', 'club_info.id' )
->leftJoin( 'user_class', 'user_class.id', '=', 'bank_in_info_phrases.class_id' )
	//->leftJoin( 'user_info', 'user_info.user_id',          '=', 'bbs_qna.user_id' )	
	//->leftJoin( 'view_order_sum', 'user_id',          '=', 'view_order_sum.user_id' )	

//->where( 'bank_in_info_phrases.club_id',$club_id, '=')
->where( 'bank_in_info_phrases.club_id', $club_id, ( $club_id === 255 ? '!=' : '=' ) )

	//->or_where( 'bank_in_info_phrases.club_id', 255, '=' )
	
	//->or_where( 'bank_in_info_phrases.club_id', 0, '=' )
	//->where( 'date(bbs_qna.u_time)',date('Y-m-d',strtotime(date('Y-m-d').'-2 days')), '>')	
	//->where( 'date(order.buy_time)',date('Y-m-d',strtotime(date('Y-m-d').'-7 days')), '>')	

->process( $_POST )->json();