<?php

?>
<?php
$club_id=1;
$date = date('Y-m-d');
$sdate = $date;
$edate = $date;
extract($_POST); 
if ( !isset( $_SESSION ) ) {
	session_start();
}
$club_id = $_SESSION['club_id'];
$user_level = $_SESSION['user_level'];
$club_id=1;
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
Editor::inst( $db, 'partner_info' )
	->fields(
		//Field::inst( 'banking_type' ),
		Field::inst( 'partner_name' ),
		//Field::inst( 'club_id' ),
		Field::inst( 'partner_id' ),
		Field::inst( 'club_id' ),
		Field::inst( 'partner_pw' ),
		Field::inst( 'partner_level' ),
		Field::inst( 'phone' ),
		Field::inst( 'memo' ),
		Field::inst( 'r_time' )
	)
  ->on( 'preCreate', function ( $editor, $values ) {
				global $club_id ;
        $editor
            ->field( 'club_id' )
            ->setValue( $club_id );
    } )		
  //->leftJoin( 'club_info',     'club_id',          '=', 'club_info.id' )
	//->leftJoin( 'user_info', 'user_info.user_id',          '=', 'bbs_qna.user_id' )	
	//->leftJoin( 'view_order_sum', 'user_id',          '=', 'view_order_sum.user_id' )	

	->where( 'club_id' , $club_id, '=')
	//->where( 'date(bbs_qna.u_time)',date('Y-m-d',strtotime(date('Y-m-d').'-2 days')), '>')	
	//->where( 'date(order.buy_time)',date('Y-m-d',strtotime(date('Y-m-d').'-7 days')), '>')	

	->process( $_POST )
	->json();
	

