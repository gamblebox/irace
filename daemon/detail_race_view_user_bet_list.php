<?php
header("Content-Type:application/json");
header("Content-Type:text/html;charset=UTF-8");
$date = date('Y-m-d');
if ($_POST['date']) {
    $date = $_POST['date'];
}
$club_id = 1;
$ta = "'race','japanrace','cycle','boat'";
if ($_POST['club_id']) {
    $club_id = $_POST['club_id'];
}
extract($_POST);
if (!isset($_SESSION)) {
    session_start();
}
// $user_id = $_SESSION['user_id'];
if ($_SESSION['club_id'] !== '255') {
    $club_id = (int) $_SESSION['club_id'];
}
// $user_level = $_SESSION['user_level'];
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
//CREATE TABLE `order` (
// `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
// `user_id` CHAR(30) NOT NULL DEFAULT '',
// `club_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
// `race_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
// `association_code` ENUM('race','japanrace','cycle','boat','osr','osh','osg') NOT NULL DEFAULT 'race',
// `type` ENUM('복승','쌍승','삼복승','삼쌍승','복연승','단승','연승','홀짝') NOT NULL DEFAULT '복승',
// `place_1` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
// `place_2` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
// `place_3` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
// `money_type` ENUM('R','S','E') NOT NULL DEFAULT 'R',
// `stat` ENUM('P','C','W','L','R') NOT NULL DEFAULT 'P',
// `bet_money` INT(11) NOT NULL DEFAULT '0',
// `service_money` INT(11) NOT NULL DEFAULT '0',
// `result_money` INT(11) NOT NULL DEFAULT '0',
// `buy_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
// `cancel_time` DATETIME NULL DEFAULT NULL,
// `update_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
// PRIMARY KEY (`id`),
// INDEX `race_id` (`race_id`),
// INDEX `user_id` (`user_id`),
// INDEX `buy_time` (`buy_time`),
// INDEX `update_time` (`update_time`)
// )
// COLLATE='utf8mb4_general_ci'
// ENGINE=InnoDB
// AUTO_INCREMENT=751132
// ;

Editor::inst( $db, 'order' )->fields(
    Field::inst( 'order.user_id' ),
    Field::inst( 'user_info.nick_name' ),
//     Field::inst( 'club_id' ),
    Field::inst( 'order.race_id' ),
		Field::inst( 'order.own_race_no' ),
	
//     Field::inst( 'association_code' ),
    Field::inst( 'order.type' ),
    Field::inst( 'order.place_1' ),
    Field::inst( 'order.place_2' ),
    Field::inst( 'order.place_3' ),
	Field::inst( 'order.fixed_odds' ),
    Field::inst( 'order.money_type' ),
    Field::inst( 'order.stat' ),
    Field::inst( 'order.bet_money' ),
    Field::inst( 'order.service_money' ),
    Field::inst( 'order.result_money' ),
//     Field::inst( 'buy_time' ),
//     Field::inst( 'cancel_time' ),
    Field::inst( 'order.update_time' ),
    Field::inst( 'place.name' ),
    Field::inst( 'race.race_no' )
    
    )    
//     ->on( 'preEdit', function ( $editor, $id, $values ) {
//             $update_time = $values[ 'order.update_time' ];
//             $editor
//             ->field( 'order.update_time' )->setValue( $update_time );
//     } )
    
->leftJoin( 'race',     'race.id',          '=', 'order.race_id' )
->leftJoin( 'place', 'place.id', '=', 'race.place_id' )
->leftJoin( 'user_info', 'user_info.user_id', '=', 'order.user_id' )
// ->leftJoin( 'association_info', 'association_info.id', '=', 'place.association_id' )
//->leftJoin( 'view_place_result',     'view_place_result.race_id',          '=', 'race.id' )

        ->where( 'order.user_id', $user_id, '='  )
        ->where( 'order.race_id', $race_id, '='  )
// ->where( 'race.start_date', $date, '=' )
//->where( 'race_id', $race_id )
->process( $_POST )->json();


