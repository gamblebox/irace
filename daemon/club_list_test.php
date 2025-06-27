<?php

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

/*
CREATE TABLE `club_info` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`club_code` CHAR(20) NOT NULL DEFAULT '0',
	`club_name` CHAR(20) NOT NULL DEFAULT 'krace',
	`domain` CHAR(100) NOT NULL DEFAULT 'domain.com',
	`admin_theme` CHAR(10) NOT NULL DEFAULT 'default',
	`isuse_mobile` BIT(1) NOT NULL DEFAULT b'0',
	`isjoin` ENUM('Y','N') NOT NULL DEFAULT 'N',
	`m_isjoin` ENUM('Y','N') NOT NULL DEFAULT 'N',
	`club_top_info` CHAR(50) NOT NULL DEFAULT '즐거운 시간 보내세요',
	`club_top_notice` VARCHAR(255) NOT NULL DEFAULT '<p>이용중 불편 사항은<span style="color:#EE82EE"> 1:1 문의</span>나 <span style="color:#EE82EE">실시간 상담</span>을 이용해 주세요. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;상담가능 시간은 첫경주 <b>개시전 30분, 마감후 30분</b>입니다</p>',
	`club_level` TINYINT(3) UNSIGNED NOT NULL DEFAULT '10',
	`club_broad_srv` CHAR(20) NOT NULL DEFAULT 'wowza.com',
	`finish_time_offset` SMALLINT(6) NOT NULL DEFAULT '0',
	`tawk_id` CHAR(30) NOT NULL DEFAULT 'tawk_id',
	`withdraw_bank_info_confirm` ENUM('Y','N') NOT NULL DEFAULT 'N',
	`isuse_korea_race_sambok` ENUM('Y','N') NOT NULL DEFAULT 'Y',
	`iscalendar` ENUM('Y','N') NOT NULL DEFAULT 'N',
	`calendar_deposit` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '100000',
	`calendar_bet` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '200000',
	`calendar_set` SET('A','O') NOT NULL DEFAULT 'O',
	`recommender_service` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
	`allin_service_isauto` ENUM('Y','N') NOT NULL DEFAULT 'N',
	`allin_service` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
	`allin_service_min_offset` INT(10) UNSIGNED NOT NULL DEFAULT '90000',
	`allin_service_max_offset` INT(10) UNSIGNED NOT NULL DEFAULT '290000',
	`allin_service2` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
	`allin_service_min_offset2` INT(10) UNSIGNED NOT NULL DEFAULT '290000',
	`allin_service_max_offset2` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`korea_race_bet_service` TINYINT(3) UNSIGNED NOT NULL DEFAULT '20',
	`korea_race_bet_service_samssang` TINYINT(3) UNSIGNED NOT NULL DEFAULT '20',
	`korea_race_bet_service_sambok` TINYINT(3) UNSIGNED NOT NULL DEFAULT '10',
	`korea_race_bet_service_bokyun` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
	`korea_race_bet_service_dan` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
	`korea_race_bet_service_yun` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
	`japan_race_bet_service` TINYINT(3) UNSIGNED NOT NULL DEFAULT '10',
	`japan_race_bet_service_samssang` TINYINT(3) UNSIGNED NOT NULL DEFAULT '10',
	`japan_race_bet_service_sambok` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
	`japan_race_bet_service_bokyun` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
	`japan_race_bet_service_dan` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
	`japan_race_bet_service_yun` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
	`cycle_race_bet_service` TINYINT(3) UNSIGNED NOT NULL DEFAULT '10',
	`cycle_race_bet_service_sambok` TINYINT(3) UNSIGNED NOT NULL DEFAULT '10',
	`cycle_race_bet_service_bokyun` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
	`cycle_race_bet_service_dan` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
	`cycle_race_bet_service_yun` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
	`boat_race_bet_service` TINYINT(3) UNSIGNED NOT NULL DEFAULT '10',
	`boat_race_bet_service_sambok` TINYINT(3) UNSIGNED NOT NULL DEFAULT '10',
	`boat_race_bet_service_bokyun` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
	`boat_race_bet_service_dan` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
	`boat_race_bet_service_yun` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
	`deposit_service_first` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
	`deposit_service_first_offset` INT(10) UNSIGNED NOT NULL DEFAULT '100000',
	`deposit_service` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
	`deposit_service_offset` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`bet_limit_bokyun_use` ENUM('Y','N') NOT NULL DEFAULT 'N',
	`bet_limit_sambok_use` ENUM('Y','N') NOT NULL DEFAULT 'N',
	`bet_limit_samssang_use` ENUM('Y','N') NOT NULL DEFAULT 'N',
	`bet_limit_bokyun` TINYINT(3) UNSIGNED NOT NULL DEFAULT '100',
	`bet_limit_sambok` TINYINT(3) UNSIGNED NOT NULL DEFAULT '100',
	`bet_limit_samssang` TINYINT(3) UNSIGNED NOT NULL DEFAULT '100',
	`korea_race_ticketing_entry` TINYINT(3) UNSIGNED NOT NULL DEFAULT '6',
	`japan_race_ticketing_entry` TINYINT(3) UNSIGNED NOT NULL DEFAULT '6',
	`cycle_race_ticketing_entry` TINYINT(3) UNSIGNED NOT NULL DEFAULT '5',
	`boat_race_ticketing_entry` TINYINT(3) UNSIGNED NOT NULL DEFAULT '4',
	`korea_race_ticketing_bokyun_entry` TINYINT(3) UNSIGNED NOT NULL DEFAULT '6',
	`korea_race_ticketing_sambok_entry` TINYINT(3) UNSIGNED NOT NULL DEFAULT '6',
	`korea_race_ticketing_samssang_entry` TINYINT(3) UNSIGNED NOT NULL DEFAULT '6',
	`korea_race_ticketing_dan_entry` TINYINT(3) UNSIGNED NOT NULL DEFAULT '6',
	`korea_race_ticketing_yun_entry` TINYINT(3) UNSIGNED NOT NULL DEFAULT '6',
	`japan_race_ticketing_dan_entry` TINYINT(3) UNSIGNED NOT NULL DEFAULT '6',
	`japan_race_ticketing_yun_entry` TINYINT(3) UNSIGNED NOT NULL DEFAULT '6',
	`japan_race_ticketing_bokyun_entry` TINYINT(3) UNSIGNED NOT NULL DEFAULT '6',
	`japan_race_ticketing_sambok_entry` TINYINT(3) UNSIGNED NOT NULL DEFAULT '6',
	`japan_race_ticketing_samssang_entry` TINYINT(3) UNSIGNED NOT NULL DEFAULT '6',
	`korea_race_ticketing_type` CHAR(50) NOT NULL DEFAULT '복승,쌍승,삼복승,복연승,삼쌍승',
	`japan_race_ticketing_type` CHAR(50) NOT NULL DEFAULT '복승,쌍승,삼복승,삼쌍승',
	`cycle_race_ticketing_type` CHAR(50) NOT NULL DEFAULT '복승,쌍승,삼복승,삼쌍승',
	`boat_race_ticketing_type` CHAR(50) NOT NULL DEFAULT '복승,쌍승,삼복승,삼쌍승',
	`ticketing_association` CHAR(50) NOT NULL DEFAULT 'race,japanrace,cycle,boat',
	`r_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`due_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE INDEX `id` (`id`),
	UNIQUE INDEX `club_code` (`club_code`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB
AUTO_INCREMENT=256
;

*/
?>
<div id="data">
<?php
// Build our Editor instance and process the data coming from _POST
$editor = Editor::inst( $db, 'club_info' )->fields(
	//Field::inst( 'club_info.banking_type' ),
	Field::inst( 'club_info.club_name' ),
	Field::inst( 'club_info.club_code' ),
	Field::inst( 'club_info.domain' ),
	Field::inst( 'club_info.isjoin' ),
	Field::inst( 'club_info.m_isjoin' ),
	Field::inst( 'club_info.club_top_info' ),
	Field::inst( 'club_info.club_top_notice' ),
	Field::inst( 'club_info.club_level' ),
	Field::inst( 'club_info.club_broad_srv' ),
	Field::inst( 'club_info.finish_time_offset' ),
	Field::inst( 'club_info.auto_next' ),
	Field::inst( 'club_info.ischoicerace' ),
	Field::inst( 'club_info.ischoiceplace' ),
	Field::inst( 'club_info.isbackupbet' ),
	Field::inst( 'club_info.bet_unit' ),
	Field::inst( 'club_info.tawk_id' ),
	Field::inst( 'club_info.withdraw_bank_info_confirm' ),
	Field::inst( 'club_info.isuse_korea_race_sambok' ),
	Field::inst( 'club_info.dup_login' ),
	Field::inst( 'club_info.iscalendar' ),
	Field::inst( 'club_info.calendar_deposit' ),
	Field::inst( 'club_info.calendar_bet' ),
	Field::inst( 'club_info.recommender_service' ),
	Field::inst( 'club_info.allin_service_isauto' ),
	Field::inst( 'club_info.allin_service' ),
	Field::inst( 'club_info.allin_service_min_offset' ),
	Field::inst( 'club_info.allin_service_max_offset' ),
	Field::inst( 'club_info.allin_service2' ),
	Field::inst( 'club_info.allin_service_min_offset2' ),
	Field::inst( 'club_info.allin_service_max_offset2' ),
	Field::inst( 'club_info.korea_race_bet_service' ),
	Field::inst( 'club_info.korea_race_bet_service_samssang' ),
	Field::inst( 'club_info.korea_race_bet_service_sambok' ),
	Field::inst( 'club_info.korea_race_bet_service_bokyun' ),
	Field::inst( 'club_info.korea_race_bet_service_dan' ),
	Field::inst( 'club_info.korea_race_bet_service_yun' ),
	Field::inst( 'club_info.japan_race_bet_service' ),
	Field::inst( 'club_info.japan_race_bet_service_samssang' ),
	Field::inst( 'club_info.japan_race_bet_service_sambok' ),
	Field::inst( 'club_info.japan_race_bet_service_bokyun' ),
	Field::inst( 'club_info.japan_race_bet_service_dan' ),
	Field::inst( 'club_info.japan_race_bet_service_yun' ),
	Field::inst( 'club_info.cycle_race_bet_service' ),
	Field::inst( 'club_info.cycle_race_bet_service_sambok' ),
	Field::inst( 'club_info.cycle_race_bet_service_bokyun' ),
	Field::inst( 'club_info.cycle_race_bet_service_dan' ),
	Field::inst( 'club_info.cycle_race_bet_service_yun' ),
	Field::inst( 'club_info.boat_race_bet_service' ),
	Field::inst( 'club_info.boat_race_bet_service_sambok' ),
	Field::inst( 'club_info.boat_race_bet_service_bokyun' ),
	Field::inst( 'club_info.boat_race_bet_service_dan' ),
	Field::inst( 'club_info.boat_race_bet_service_yun' ),
	Field::inst( 'club_info.deposit_service_first' ),
	Field::inst( 'club_info.deposit_service_first_offset' ),
	Field::inst( 'club_info.deposit_service' ),
	Field::inst( 'club_info.deposit_service_offset' ),
	Field::inst( 'club_info.bet_limit_bokyun_use' ),
	Field::inst( 'club_info.bet_limit_sambok_use' ),
	Field::inst( 'club_info.bet_limit_samssang_use' ),
	Field::inst( 'club_info.bet_limit_bokyun' ),
	Field::inst( 'club_info.bet_limit_sambok' ),
	Field::inst( 'club_info.bet_limit_samssang' ),
	Field::inst( 'club_info.korea_race_ticketing_entry' ),
	Field::inst( 'club_info.japan_race_ticketing_entry' ),
	Field::inst( 'club_info.cycle_race_ticketing_entry' ),
	Field::inst( 'club_info.boat_race_ticketing_entry' ),
	Field::inst( 'club_info.korea_race_ticketing_bokyun_entry' ),
	Field::inst( 'club_info.korea_race_ticketing_sambok_entry' ),
	Field::inst( 'club_info.korea_race_ticketing_samssang_entry' ),
	Field::inst( 'club_info.japan_race_ticketing_bokyun_entry' ),
	Field::inst( 'club_info.japan_race_ticketing_sambok_entry' ),
	Field::inst( 'club_info.japan_race_ticketing_samssang_entry' ),
	Field::inst( 'club_info.korea_race_ticketing_type' ),
	Field::inst( 'club_info.japan_race_ticketing_type' ),
	Field::inst( 'club_info.cycle_race_ticketing_type' ),
	Field::inst( 'club_info.boat_race_ticketing_type' ),
	Field::inst( 'club_info.ticketing_association' ),
	Field::inst( 'club_info.due_money' ),
	Field::inst( 'club_info.due_date' ),
	Field::inst( 'view_club_admin.admin' )
)
//->distinct( true )
//->leftJoin( 'club_info',     'club_id',          '=', 'club_info.id' )
->leftJoin( 'view_club_admin', 'view_club_admin.club_id',          '=', 'club_info.id' )	
//->leftJoin( 'view_order_sum', 'user_id',          '=', 'view_order_sum.user_id' )	
//->distinct( true )
->where( 'club_info.id', 255, '!=' )
->where( 'club_info.id', 0, '!=' )
->where( 'club_info.club_level', -1, '>' )
//->where( 'user_info.club_user_id', '총판통합', '!=' )	
//->where( 'user_info.user_level', 100, '=' )
	//->where( 'date(bbs_qna.u_time)',date('Y-m-d',strtotime(date('Y-m-d').'-2 days')), '>')	
	//->where( 'date(order.buy_time)',date('Y-m-d',strtotime(date('Y-m-d').'-7 days')), '>')	

->process( $_POST );
	//$d = new stdClass();
	$d = json_decode($editor->json(false));
	//$d=json_decode($d, JSON_UNESCAPED_UNICODE);
	
	//print_r($d);
	
	
	foreach($d->data as $value){
		$ids[] = str_replace('row_', '', $value->DT_RowId);
	}
	$t = " and u.id in (" . implode(',', $ids) . ")" ;
	echo $t;
	
	echo '<br>';echo '<br>';echo '<br>';
	
	include (__DIR__ . '/../../../application/configs/configdb.php');
	
	$db = new PDO( 'mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8mb4', $user, $password, array(
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_EMULATE_PREPARES => false,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ ) );
//$data = array();
	
	$mysqli = new mysqli($host, $user, $password, $dbname);
 // 연결 오류 발생 시 스크립트 종료
 if ($mysqli->connect_errno) {
    die('Connect Error: '.$mysqli->connect_error);
}
	
	$sql = "select * from user_info where user_id = '_gold_kaiji'";
			//$stmt = $db->prepare( $sql );
		//$stmt->execute($sql);
		//$data = $stmt->fetchAll();
	
	$stmt = $db->query($sql);
$data = $stmt->fetchAll();
	
	$d->data = $data;
	print_r($d->data);
	
	$data = array();
	if ( $result = $mysqli->query( $sql ) ) {
	while ( $row = mysqli_fetch_object( $result ) ) {
		$data[] = $row;
	}
} else {
	$data = array( 0 => 'empty' );
}
	echo '<br>';
	echo '<br>';
	echo '<br>';
	$d->data = $data;
print_r($d->data);
	
		echo '<br>';
	echo '<br>';
	echo '<br>';
print_r($d->data);
