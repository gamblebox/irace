<?php
//$club_id = 1;

header( "Content-Type:application/json" );
header( "Content-Type:text/html;charset=UTF-8" );
extract( $_POST );
if ( !isset( $_SESSION ) ) {
	session_start();
}
$user_id = $_SESSION[ 'user_id' ];
if ( $_SESSION[ 'club_id' ] !== '255' ) {
	$club_id = ( int )$_SESSION[ 'club_id' ];
}
$club_id = ( int )$club_id;
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
Editor::inst( $db, 'bbs_post' )->fields(
		Field::inst( 'bbs_post.r_user_id' )->validator( 'Validate::notEmpty' )->options( 'user_info', 'user_id', array( 'nick_name', 'club_user_id' ), function ( $q ) {
			global $club_id;
			$q->where( 'club_id', $club_id, ( $club_id === 255 ? '!=' : '=' ) );
		}, function ( $row ) {
			return $row[ 'club_user_id' ] . '(' . $row[ 'nick_name' ] . ')';
		} ),
		Field::inst( 'bbs_post.s_user_id' ),
		//Field::inst( 'club_id' ),
		Field::inst( 'bbs_post.club_id' ),
		Field::inst( 'user_info.nick_name' ),
		Field::inst( 'user_info.club_user_id' ),
		Field::inst( 'bbs_post.subject' )->validator( 'Validate::notEmpty' ),
		Field::inst( 'bbs_post.r_time' ),
		Field::inst( 'bbs_post.s_time' ),
		Field::inst( 'bbs_post.memo' )->validator( 'Validate::notEmpty' ),		
		Field::inst( 'bbs_post.boilerplate_id' )->options( 'boilerplate', 'id', array('subject', 'memo'), function ( $q ) {
		    global $club_id;
		    $q->where( 'club_id', $club_id, ( $club_id === 255 ? '!=' : '=' ) )->or_where( 'club_id', 0, '=' );
		}, function ( $row ) {
		    return $row[ 'subject' ] == '' ? '' : $row[ 'subject' ] . ': ' . strip_tags(iconv_substr($row[ 'memo' ], 0, 90, "utf-8")) . '...';
		} ),
		Field::inst( 'bbs_post.stat' )

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
	)->on( 'preCreate', function ( $editor, $values ) {
		global $club_id;
		$editor
			->field( 'bbs_post.club_id' )->setValue( $club_id );
	} )
	//->leftJoin( 'club_info',     'club_id',          '=', 'club_info.id' )
	->leftJoin( 'user_info', 'user_info.user_id', '=', 'bbs_post.r_user_id' )
	//->leftJoin( 'view_order_sum', 'user_id',          '=', 'view_order_sum.user_id' )	

//->where( 'user_info.club_id', $club_id, '=' )
->where( 'user_info.club_id', $club_id, ( $club_id === 255 ? '!=' : '=' ) )
	//->where( 'date(bbs_post.s_time)', date( 'Y-m-d', strtotime( date( 'Y-m-d' ) . '-30 days' ) ), '>' )
	//->where( 'date(order.buy_time)',date('Y-m-d',strtotime(date('Y-m-d').'-7 days')), '>')	

->process( $_POST )->json();