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
		//Field::inst( 'banking_type' ),
		Field::inst( 'user_id' )->validator( 'Validate::notEmpty' )->validator( 'Validate::unique' ),
		Field::inst( 'club_user_id' )->validator( 'Validate::notEmpty' ),
		//Field::inst( 'club_name' ),
		Field::inst( 'nick_name' ),
		Field::inst( 'partner_id' )
		    ->options( 'partner_info', 'partner_id', 'club_partner_id', function ($q) {
				global $club_id;
        $q->where( 'club_id', $club_id, '=' );
 			   } ),
		Field::inst( 'club_id' ),
		Field::inst( 'bank_in_bank_id' )
		    ->options( 'bank_in_info', 'id', 'bank_in_bank_name', function ($q) {
				global $club_id;
        $q->where( 'club_id', $club_id, '=' )
						->where( 'isuse', 'Y', '=' );
 			   } )->setFormatter( 'Format::nullEmpty'),
		Field::inst( 'bank_out_bank_name' )->setFormatter( 'Format::nullEmpty'),
		Field::inst( 'bank_out_bank_account_no' )->setFormatter( 'Format::nullEmpty'),		
		Field::inst( 'bank_out_bank_account_name' )->setFormatter( 'Format::nullEmpty'),
		Field::inst( 'bank_out_bank_info_ok' ),
		Field::inst( 'u_time' )
	)
/*
	->on( 'preEdit', function ( $editor, $id, $values ) {
			global $club_code ;
			$user_id =  '_' . $club_code . '_' . $values['club_user_id'];
			$editor
					->field( 'user_id' )
					->setValue($user_id);
	} )		 	
*/
	->where( 'club_id',$club_id, ($club_id === '255'? '!=': '='))
	->where( 'bank_out_bank_info_ok','R', '=')

//	->where( 'date(banking.r_time)',date('Y-m-d',strtotime(date('Y-m-d').'-7 days')), '>')	
	//->where( 'date(order.buy_time)',date('Y-m-d',strtotime(date('Y-m-d').'-7 days')), '>')	

	->process( $_POST )
	->json();	
	
