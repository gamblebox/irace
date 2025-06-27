<?php

//$club_id=1;
//$date = date('Y-m-d');
//$sdate = $date;
//$edate = $date;
//$club_code = 'goldrace';
//$cut_user_level = '-1';
extract( $_POST );
$edate = date('Y-m-d', strtotime($edate . '+' . '1' . ' days'));
if ( !isset( $_SESSION ) ) {
	session_start();
}
if ( $_SESSION[ 'club_id' ] !== '255' ) {
	$club_id = ( int )$_SESSION[ 'club_id' ];
}
$club_id = ( int )$club_id;

include (__DIR__ . '/../../../application/configs/configdb.php');
$data = array();
$db = new PDO( 'mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8mb4', $user, $password, array(
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_EMULATE_PREPARES => false,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ ) );

//, concat('row_', u.id) as DT_RowId

$sql = "select concat('row_', u.id) as DT_RowId,u.user_id,u.nick_name,allin_service_money_all,deposit_service_money_all,any_real_money_all,any_service_money_all,recommend_service_money_all from (SELECT user_id u_id, sum(if(memo like '%손실금%',new_money_service - old_money_service, 0)) AS allin_service_money_all, sum(if(memo like '%충전%',new_money_service - old_money_service, 0)) AS deposit_service_money_all, sum(if(memo = '머니 임의 지급으로 인한 변동',new_money_real - old_money_real, 0)) AS any_real_money_all, sum(if(memo = '서비스 머니 임의 지급으로 인한 변동',new_money_service - old_money_service, 0)) AS any_service_money_all, sum(if(memo like '%추천인%',new_money_service - old_money_service, 0)) AS recommend_service_money_all FROM log_money WHERE u_time >= DATE('" . $sdate . "') AND u_time < DATE('" . $edate . "') GROUP BY user_id) as s left join user_info as u on u.user_id = s.u_id left join club_info as c on u.club_id = c.id where c.club_level > -1 and c.id " . ( $club_id === 255 ? '!= ' : '= ' ) . $club_id . " and u.user_level > -3 and u.user_level < 50";

$data = array();
//$stmt = $db->query( $sql );
$stmt = $db->prepare( $sql );
$stmt->execute();
$data = $stmt->fetchAll();


$d->data = $data;
echo json_encode( $d, JSON_UNESCAPED_UNICODE );
//$result->free(); //메모리해제
// 접속 종료
//$mysqli->close();
?>