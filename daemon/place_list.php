<?php
$club_id = 1;
extract( $_POST );
if ( !isset( $_SESSION ) ) {
	session_start();
}
//$ta='';
extract($_POST);

$user_id = $_SESSION[ 'user_id' ];
$club_id = $_SESSION[ 'club_id' ];
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
Editor::inst( $db, 'club_place' )->fields(
	//Field::inst( 'place.banking_type' ),
	Field::inst( 'club_place.isuse' ),
	Field::inst( 'place.name' ),
	Field::inst( 'association_info.name' ),
    Field::inst( 'association_info.code' )
)

->leftJoin( 'place', 'place.id',          '=', 'club_place.place_id' )	
->leftJoin( 'association_info', 'association_info.id',          '=', 'place.association_id' )	

->where( 'club_place.club_id', $club_id, '=' )
//->where( 'association_info.code', "japanrace", 'IN', false  )
->where( function ( $q ) {
    global $ta;
    $q->where( 'association_info.code',"(" . $ta . ")", 'IN', false );
} )

->process( $_POST )->json();