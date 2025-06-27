<?php
//$club_id=1;

extract( $_POST );
if ( !isset( $_SESSION ) ) {
	session_start();
}
if ( $_SESSION[ 'club_id' ] !== '255' ) {
	$club_id = ( int )$_SESSION[ 'club_id' ];
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
Editor::inst( $db, 'bbs_notice' )->fields(
	//Field::inst( 'club_info.banking_type' ),
	Field::inst( 'club_id' ),
	Field::inst( 'subject' ),
	Field::inst( 'memo' ),
	Field::inst( 'ispopup' ),
	Field::inst( 'u_time' )
)->where( 'club_id', 255, '=' )

->process( $_POST )->json();