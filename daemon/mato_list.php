<?php

//$club_id=1;
//$date = date('Y-m-d');
//$sdate = $date;
//$edate = $date;
//$club_code = 'goldrace';
//$cut_user_level = '-1';
extract( $_POST );
$edate = date('Y-m-d', strtotime($edate . '+' . '1' . ' days'));
$a = $action;
if ( !isset( $_SESSION ) ) {
	session_start();
}
if ( $_SESSION[ 'club_id' ] !== '255' ) {
	$club_id = ( int )$_SESSION[ 'club_id' ];
}
$club_code = $_SESSION[ 'club_code' ];
$club_id = ( int )$club_id;
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
$editor = Editor::inst( $db, 'mato_user_info' )->fields(
	Field::inst( 'id' ),
	Field::inst( 'user_id' )->validator( 'Validate::notEmpty', array( 'message' => '아이디는 필수로 입력하여야 합니다' ) )->validator( 'Validate::unique', array( 'message' => '아이디는 중복 되지 않아야 합니다' ) ),
	Field::inst( 'memo' ),
	Field::inst( 'club_user_id' ),
	Field::inst( 'name' ),
	Field::inst( 'ktid' ),
	Field::inst( 'hope_id' ),
	Field::inst( 'nick_name' ),
	Field::inst( 'partner_id' )->options( 'partner_info', 'partner_id', array( 'partner_id', 'club_partner_id', 'partner_name' ), function ( $q ) {
			global $club_id;
			$q->where( 'club_id', $club_id, ( $club_id === 255 ? '!=' : '=' ) );
		},

		function ( $row ) {
			global $club_id;
			//return $row['club_user_id'].' ('.$row['nick_name'].')';
			if ( $club_id === 255 ) {
				return $row[ 'partner_id' ] . '(' . $row[ 'partner_name' ] . ')';
			} else {
				return $row[ 'club_partner_id' ] . '(' . $row[ 'partner_name' ] . ')';
			}
		} ),
	Field::inst( 'club_id' ),
	Field::inst( 'login_ip' ),
	Field::inst( 'mobile' ),
	Field::inst( 'mobile_web' ),
	Field::inst( 'phone' ),
	Field::inst( 'udomain' ),
	Field::inst( 'recommender' )->options( 'mato_user_info', 'user_id', array( 'user_id', 'club_user_id', 'nick_name' ), function ( $q ) {
			global $club_id;
			$q->where( 'club_id', $club_id, ( $club_id === 255 ? '!=' : '=' ) );
			$q->where( 'user_level', -4, '>' );
			$q->where( 'user_level', 50, '<' );
		},
		function ( $row ) {
			global $club_id;
			//return $row['club_user_id'].' ('.$row['nick_name'].')';
			if ( $club_id === 255 ) {
				return $row[ 'user_id' ] . '(' . $row[ 'nick_name' ] . ')';
			} else {
				return $row[ 'club_user_id' ] . '(' . $row[ 'nick_name' ] . ')';
			}
		} ),
	Field::inst( 'bank_in_bank_id' )->options( 'bank_in_info', 'id', array( 'bank_in_nick_name', 'bank_in_bank_name', 'bank_in_bank_account_no', 'bank_in_bank_account_name' ), function ( $q ) {
		global $club_id;
		$q->where( function ( $r ) {
			global $club_id;
			$r->where( 'club_id', $club_id, ( $club_id === 255 ? '!=' : '=' ) );
			$r->or_where( 'id', 0, '=' );
		} )->where( 'isuse', 'Y', '=' );
	}, function ( $row ) {
		global $club_id;
		global $club_code;
		//return $row['club_user_id'].' ('.$row['nick_name'].')';
		return $row[ 'bank_in_nick_name' ] . '(' . $row[ 'bank_in_bank_name' ] . ' ' . $row[ 'bank_in_bank_account_no' ] . ' ' . $row[ 'bank_in_bank_account_name' ] . ')';
	} ),
	Field::inst( 'bank_out_bank_name' ),
	Field::inst( 'bank_out_bank_account_no' ),
	Field::inst( 'bank_out_bank_account_name' ),
	Field::inst( 'bank_out_bank_info_ok' ),
	Field::inst( 'batch_allin_service' ),
	Field::inst( 'money_real' ),
	Field::inst( 'money_service' ),
	Field::inst( 'user_level' ),
	Field::inst( 'r_time' )->setFormatter( 'Format::ifEmpty', '2000-01-01' ),
	Field::inst( 'u_time' )->setFormatter( 'Format::ifEmpty', '2000-01-01' ),
	Field::inst( 'login_time' ),
	Field::inst( 'stat' ),
	Field::inst( 'u_iskralive' ),
	Field::inst( 'result_money_offset_config' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_result_money_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_korea_race_sb_result_money_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_korea_race_j_result_money_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_japan_race_result_money_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_cycle_race_result_money_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_boat_race_result_money_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'bet_service_config' ),
	Field::inst( 'allin_service_config' ),
	Field::inst( 'u_allin_service' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_allin_service_min_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_allin_service_max_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_allin_service2' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_allin_service_min_offset2' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_allin_service_max_offset2' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'game_config' ),
	Field::inst( 'bet_limit_config' ),
	Field::inst( 'u_bet_limit_bokyun_use' ),
	Field::inst( 'u_bet_limit_sambok_use' ),
	Field::inst( 'u_bet_limit_samssang_use' ),
	Field::inst( 'u_bet_limit_bokyun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_bet_limit_sambok' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_bet_limit_samssang' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_bet_limit_japanrace_bokyun_use' ),
	Field::inst( 'u_bet_limit_japanrace_sambok_use' ),
	Field::inst( 'u_bet_limit_japanrace_samssang_use' ),
	Field::inst( 'u_bet_limit_japanrace_bokyun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_bet_limit_japanrace_sambok' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_bet_limit_japanrace_samssang' )->setFormatter( 'Format::nullEmpty' ),	
	Field::inst( 'u_korea_race_ticketing_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_japan_race_ticketing_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_cycle_race_ticketing_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_boat_race_ticketing_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_korea_race_ticketing_bokyun_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_korea_race_ticketing_sambok_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_korea_race_ticketing_samssang_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_japan_race_ticketing_bokyun_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_japan_race_ticketing_sambok_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_japan_race_ticketing_samssang_entry' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_korea_race_ticketing_type' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_japan_race_ticketing_type' )->setFormatter( 'Format::nullEmpty' ),

/*	->setFormatter( function ( $val, $data, $opts ) {
		if ( $val === '' ) {
			return null;
		} else {
			return implode( $val );
		}
	} ),*/
	Field::inst( 'u_cycle_race_ticketing_type' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_boat_race_ticketing_type' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_ticketing_association' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_korea_race_bet_service' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_korea_race_bet_service_samssang' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_korea_race_bet_service_sambok' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_korea_race_bet_service_bokyun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_korea_race_bet_service_dan' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_korea_race_bet_service_yun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_japan_race_bet_service' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_japan_race_bet_service_sambok' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_japan_race_bet_service_bokyun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_japan_race_bet_service_dan' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_japan_race_bet_service_yun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_cycle_race_bet_service' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_cycle_race_bet_service_sambok' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_cycle_race_bet_service_samssang' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_cycle_race_bet_service_bokyun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_cycle_race_bet_service_dan' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_cycle_race_bet_service_yun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_boat_race_bet_service' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_boat_race_bet_service_sambok' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_boat_race_bet_service_samssang' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_boat_race_bet_service_bokyun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_boat_race_bet_service_dan' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_boat_race_bet_service_yun' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'deposit_service_config' ),
	Field::inst( 'u_deposit_service_first' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_deposit_service_first_min_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_deposit_service_first_max_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_deposit_service' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_deposit_service_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_finish_time_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_korea_race_finish_time_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_japan_race_finish_time_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_cycle_race_finish_time_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'u_boat_race_finish_time_offset' )->setFormatter( 'Format::nullEmpty' ),
	Field::inst( 'finish_time_offset_config' ),
	Field::inst( 'user_pw' )
)

->on( 'preCreate', function ( $editor, $values ) {
	global $club_id;
	global $club_code;
	$editor
		->field( 'club_id' )->setValue( $club_id );
	/*	$user_id = '_' . $club_code . '_' . $values[ 'club_user_id' ];
		$editor
			->field( 'user_id' )->setValue( $user_id );*/
} )

->on( 'preEdit', function ( $editor, $id, $values ) {
	global $club_code;
	if ( isset( $values[ 'club_user_id' ] ) ) {
		$user_id = '_' . $club_code . '_' . $values[ 'club_user_id' ];
		$editor
			->field( 'user_id' )->setValue( $user_id );
	}
} )

//->leftJoin( 'partner_info',     'partner_info.club_id',          '=', 'mato_user_info.club_id' )
//->leftJoin( 'view_banking_sum', 'user_id',          '=', 'view_banking_sum.user_id' )	
//->leftJoin( 'view_order_sum', 'user_id',          '=', 'view_order_sum.user_id' )	

//->where( 'club_id', $club_id, ( $club_id === 255 ? '!=' : '=' ) )
	//	->where( 'date(banking.r_time)',date('Y-m-d',strtotime(date('Y-m-d').'-7 days')), '>')	
	//->where( 'date(order.buy_time)',date('Y-m-d',strtotime(date('Y-m-d').'-7 days')), '>')	

->process( $_POST );
//->json();
$d = json_decode( $editor->json( false ) );

include (__DIR__ . '/../../../application/configs/configdb.php');
$data = array();
/*$mysqli = new mysqli( $host, $user, $password, $dbname );
// 연결 오류 발생 시 스크립트 종료
if ( $mysqli->connect_errno ) {
	die( 'Connect Error: ' . $mysqli->connect_error );
}*/

$db = new PDO( 'mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8mb4', $user, $password, array(
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_EMULATE_PREPARES => false,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ ) );

//, concat('row_', u.id) as DT_RowId
$sql = "select *,concat('row_', u.id) as DT_RowId from club_info as c  right outer join mato_user_info u on u.club_id = c.id left outer join (select u.user_id u_id, sum(if(b.banking_type = 'I' and b.stat = 'E',b.amount,0)) AS `deposit_money_all`, sum(if(b.banking_type = 'O' and b.stat = 'E',b.amount,0)) AS `withdraw_money_all` from mato_user_info u left outer join `banking` b on u.user_id = b.user_id where b.u_time) >= date('" . $sdate . "') and  b.u_time < date('" . $edate . "')  group by u.user_id ) as b on u.user_id = b.u_id left outer join (select u.user_id u_id, sum(o.`bet_money`) AS `bet_money_all`,sum(if((o.`money_type` = 'R'),o.`bet_money`,0)) AS `bet_money_real_all`,sum(if((o.`money_type` = 'S'),o.`bet_money`,0)) AS `bet_money_service_all`,sum(o.`service_money`) AS `service_money_all`,sum(o.`result_money`) AS `result_money_all`,(sum(if((o.`money_type` = 'R'),o.`bet_money`,0)) - sum(o.`result_money`)) AS `profit` from mato_user_info u left outer join `order`o on u.user_id = o.user_id where ((o.`stat` <> 'C') and (o.`stat` <> 'R')) and o.buy_time >= date('" . $sdate . "') and  o.buy_time < date('" . $edate . "') group by u.user_id) as o on u.user_id = o.u_id LEFT OUTER JOIN (SELECT user_id u_id, SUM(`cut_money`) AS `cut_money_all` FROM cut_result_money WHERE c_date >= DATE('" . $sdate . "') AND c_date < DATE('" . $edate . "') GROUP BY user_id) AS cr ON u.user_id = cr.u_id LEFT OUTER JOIN (SELECT user_id u_id, sum(if(memo like '%손실금%',new_money_service - old_money_service, 0)) AS allin_service_money_all, sum(if(memo like '%충전%',new_money_service - old_money_service, 0)) AS deposit_service_money_all, sum(if(memo like '%임의 지급%',new_money_service - old_money_service, 0)) AS any_service_money_all, sum(if(memo like '%추천인%',new_money_service - old_money_service, 0)) AS recommend_service_money_all FROM log_money  WHERE u_time >= DATE('" . $sdate . "') AND u_time < DATE('" . $edate . "') GROUP BY user_id) AS s ON u.user_id = s.u_id LEFT OUTER JOIN (SELECT a.user_id u_id, COUNT(DISTINCT a.attendance_date) as attendance_count FROM `attendance`a WHERE a.attendance_date >= date('" . $sdate . "') AND a.attendance_date < date('" . $edate . "') GROUP BY a.user_id) AS a ON u.user_id = a.u_id where c.club_level > -1 and u.user_level > " . $cut_user_level . " and u.user_level < 50";
//echo $sql;Editor::ACTION_CREATE - Create a new record
//Editor::ACTION_EDIT - Edit existing data
//Editor::ACTION_DELETE - Delete existing row(s)

if ( count( $d->data ) !== 0 ) {
	if ( $a === Editor::ACTION_CREATE ) {
		$sql .= " and u.id = " . str_replace( 'row_', '', $d->data[ 0 ]->DT_RowId );
		//$a = 1;
	} else if ( $a === Editor::ACTION_EDIT ) {
		foreach ( $d->data as $value ) {
			$ids[] = str_replace( 'row_', '', $value->DT_RowId );
		}
		$sql .= " and u.id in (" . implode( ',', $ids ) . ")";
	}

	/*if ( $result = $mysqli->query( $sql ) ) {
		while ( $row = mysqli_fetch_object( $result ) ) {
			//$row->DT_RowId = 'row_' . $row->id;
			$row->u_ticketing_association = explode( ',', $row->u_ticketing_association );
			$row->u_korea_race_ticketing_type = explode( ',', $row->u_korea_race_ticketing_type );
			$row->u_japan_race_ticketing_type = explode( ',', $row->u_japan_race_ticketing_type );
			$row->u_cycle_race_ticketing_type = explode( ',', $row->u_cycle_race_ticketing_type );
			$row->u_boat_race_ticketing_type = explode( ',', $row->u_boat_race_ticketing_type );
			$data[] = $row;
		}
	} else {
		$data = array( 0 => 'empty' );
	}*/

	$data = array();
	//$stmt = $db->query( $sql );
	$stmt = $db->prepare( $sql );
	$stmt->execute();
	$data = $stmt->fetchAll();


	foreach ( $data as $key => $value ) {
		$data[ $key ]->u_ticketing_association = explode( ',', $value->u_ticketing_association );
		$data[ $key ]->u_korea_race_ticketing_type = explode( ',', $value->u_korea_race_ticketing_type );
		$data[ $key ]->u_japan_race_ticketing_type = explode( ',', $value->u_japan_race_ticketing_type );
		$data[ $key ]->u_cycle_race_ticketing_type = explode( ',', $value->u_cycle_race_ticketing_type );
		$data[ $key ]->u_boat_race_ticketing_type = explode( ',', $value->u_boat_race_ticketing_type );
	}

	$d->data = $data;
}
echo json_encode( $d, JSON_UNESCAPED_UNICODE );
//$result->free(); //메모리해제
// 접속 종료
//$mysqli->close();
?>