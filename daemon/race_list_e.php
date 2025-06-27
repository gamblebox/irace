<?php

?>
<?php
header("Content-Type:application/json");
header("Content-Type:text/html;charset=UTF-8");
$date = date('Y-m-d');
if ($_POST['date']){
	$date = $_POST['date'];
}
$club_id= 1;
$ta = "'race','japanrace','cycle','boat'";
if ($_POST['club_id']){
	$club_id = $_POST['club_id'];
}
extract($_POST); 
if ( !isset( $_SESSION ) ) {
	session_start();
}
$user_id = $_SESSION['user_id'];
if ($_SESSION['club_id'] !== '255'){
	$club_id = (int)$_SESSION['club_id'];
}
$user_level = $_SESSION['user_level'];
session_write_close();

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

//$user_id = 'goldrace';
//$race_id = 1827;
// Build our Editor instance and process the data coming from _POST


//if (isset($post['race_id'])) {
//$race_id = $_POST['race_id'];
//}

Editor::inst( $db, 'club_race' )->fields(
		Field::inst( 'race_id' ),
		Field::inst( 'club_id' ),
		Field::inst( 'isuse' )
	)
	->where( 'club_id',$club_id, '=')
//	->where( 'date(banking.r_time)',date('Y-m-d',strtotime(date('Y-m-d').'-7 days')), '>')	
	//->where( 'date(order.buy_time)',date('Y-m-d',strtotime(date('Y-m-d').'-7 days')), '>')	

	->process( $_POST )
	->json();	
?>