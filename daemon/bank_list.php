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
	DataTables\Editor,
	DataTables\Editor\Field,
	DataTables\Editor\Format,
	DataTables\Editor\Mjoin,
	DataTables\Editor\Upload,
	DataTables\Editor\Validate;

// Build our Editor instance and process the data coming from _POST
Editor::inst( $db, 'bank_in_info' )
	->fields(
		Field::inst( 'bank_in_info.id' ),
		Field::inst( 'bank_in_info.club_id' ),
		Field::inst( 'bank_in_info.bank_in_nick_name' ),
		Field::inst( 'bank_in_info.bank_in_bank_name' ),
		Field::inst( 'bank_in_info.bank_in_bank_account_no' ),
		Field::inst( 'bank_in_info.bank_in_bank_account_name' ),
		Field::inst( 'bank_in_info.isuse' )
		)
    ->on( 'preCreate', function ( $editor, $values ) {
				global $club_id ;
        $editor
            ->field( 'bank_in_info.club_id' )
            ->setValue( $club_id );
    } )		
	->where( 'bank_in_info.club_id', $club_id, ( $club_id === 255 ? '!=' : '=' ) )
	->where( 'bank_in_info.club_id', 0, '!=' )
	->process( $_POST )
	->json();
