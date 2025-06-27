<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

extract( $_POST );
$a = $action;
if ( !isset( $_SESSION ) ) {
	session_start();
}
$user_id = $_SESSION['user_id'];
if ($_SESSION['club_id'] !== '255') {
	$club_id = (int) $_SESSION['club_id'];
}
$user_level = $_SESSION['user_level'];
$club_code = $_SESSION[ 'club_code' ];
$club_id = ( int )$club_id;
$club_id = 1;
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
Editor::inst( $db, 'partner_class' )->fields(
		Field::inst( 'club_id' ),
		Field::inst( 'cd_name' ),
		Field::inst( 'cd_level' ))
	->on( 'preCreate', function ( $editor, $values ) {
		global $club_id;
		$editor
			->field( 'club_id' )->setValue( $club_id );
	} )

->where( 'club_id', $club_id, ( $club_id == 255 ? '!=' : '=' ) )

->process( $_POST )->json();