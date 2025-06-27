<?php

?>
<?php
$club_id = 1;
$date = date('Y-m-d');
if ($_POST['club_id']) {
	$club_id = $_POST['club_id'];
}
extract($_POST);
if (!isset($_SESSION)) {
	session_start();
}
$user_id = $_SESSION['user_id'];
$club_id = $_SESSION['club_id'];
$user_level = $_SESSION['user_level'];
/*
 * Example PHP implementation used for the index.html example
 */



// DataTables PHP library
include("DataTables.php");

// Alias Editor classes so they are easy to use
use
	DataTables\Editor,
	DataTables\Editor\Field,
	DataTables\Editor\Format,
	DataTables\Editor\Mjoin,
	DataTables\Editor\Upload,
	DataTables\Editor\Validate;

//$user_id = 'goldrace';
//$race_id = 1827;
// Build our Editor instance and process the data coming from _POST


//if (isset($post['race_id'])) {
//$race_id = $_POST['race_id'];
//}

Editor::inst($db, 'race_change_info')
	->fields(
		Field::inst('race_id'),
		Field::inst('type'),
		Field::inst('association_code'),
		Field::inst('memo'),
		Field::inst('entry_no'),
		Field::inst('old_start_time'),
		Field::inst('new_start_time'),
		Field::inst('r_time')
	)
	//->leftJoin( 'race',     'race.id',          '=', 'order.race_id' )
	//->leftJoin( 'place',     'place.id',          '=', 'race.place_id' )	
	//->leftJoin( 'association_info',     'association_info.id',          '=', 'place.association_id' )	
	//->leftJoin( 'view_place_result',     'view_place_result.race_id',          '=', 'race.id' )	

	//->where( 'user_info.club_id', $club_id )
	->where('race_id', $race_id, '=')
	//->where( 'race_id', $race_id )
	->process($_POST)
	->json();
