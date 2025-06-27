<?php
extract($_POST); 
if ( !isset( $_SESSION ) ) {
	session_start();
}
$user_id = $_SESSION['user_id'];
$club_id = $_SESSION['club_id'];
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
		Field::inst( 'user_id' )->validator( 'Validate::notEmpty' )->validator( 'Validate::unique' ),
		Field::inst( 'club_user_id' )->validator( 'Validate::notEmpty' ),
		Field::inst( 'nick_name' )->validator( 'Validate::notEmpty' ),
		Field::inst( 'user_pw' ),
		Field::inst( 'user_level' ),
		Field::inst( 'club_id' )
		)
    ->on( 'preCreate', function ( $editor, $values ) {
				global $club_id ;
				global $club_code ;
        $editor
            ->field( 'user_level' )
            ->setValue( 100 );
        $editor						
            ->field( 'club_id' )
            ->setValue( $club_id );
				$user_id =  '_' . $club_code . '_' . $values['club_user_id'];
				$editor
						->field( 'user_id' )
						->setValue($user_id);						
    } )		
	->where( 'club_id',$club_id, '=')
	->where( 'user_level',100, '=')

	->process( $_POST )
	->json();
