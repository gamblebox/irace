<?php
header( "Content-Type:application/json" );
header( "Content-Type:text/html;charset=UTF-8" );
function nRound( $m, $size, $rd ) {
	return $rd ? round( $m, -log10( $size * 10 ) ) : floor( $m / $size / 10 ) * $size * 10;
}
$code = array(
	"ERROR_NOT_USER" => "이용 가능한 사용자가 아닙니다",
	"ERROR_FINISH_TIME" => "쓰기 가능한 시간이 마감되었습니다",
	"ERROR_PARSING_PARAM" => "해석할 수 없는 파라미터입니다",
	"ERROR_NOT_ALLOWED" => "입력 가능한 번호가 아닙니다",
	"ERROR_MAX_EMPTY" => "상한 금액이 없습니다",
	"ERROR_MAX_OVER" => "상한 금액을 초과했습니다",
	"WRITE_FAILED" => "구매 실패",
	"WRITE_SUCCESS" => "구매 완료"
);
//echo $_SERVER['HTTP_HOST'];
//echo $_SERVER['HTTP_REFERER'];
//if($_SERVER['HTTP_HOST'] !== $_SERVER['HTTP_REFERER']) exit('No direct access allowed');
//$mode = 'abet';
extract( $_POST );
if ( !isset( $_SESSION ) ) {
	session_start();
}
//$user_id = $_SESSION['user_id'];
//$club_id = $_SESSION['club_id'];
if ( $_SESSION[ 'club_id' ] !== '255' ) {
	$club_id = ( int )$_SESSION[ 'club_id' ];
}
$user_level = $_SESSION[ 'user_level' ];
session_write_close();

include_once( __DIR__ . '/../../application/configs/configdb.php' );
$mysqli = new mysqli( $host, $user, $password, $dbname );
// 연결 오류 발생 시 스크립트 종료
if ( $mysqli->connect_errno ) {
	die( 'Connect Error: ' . $mysqli->connect_error );
} //$mysqli->connect_errno

$data = array();
$sql = "select isbackupbet from club_info where id = " . $club_id;
if ( $result = $mysqli->query( $sql ) ) {
	while ( $row = mysqli_fetch_object( $result ) ) {
		$data[] = $row;
	} //$row = mysqli_fetch_object( $result )
} //$result = $mysqli->query( $sql )
else {
	$data = array(
		0 => 'empty'
	);
}
if ($data[0]->isbackupbet === 'N'){
	$data[0] = '이용권한이 없습니다';
	exit( json_encode( $data, JSON_UNESCAPED_UNICODE ) );
}

$data = array();

switch ( $mode ) {
	case 'delete':
		echo 'delete';
		break;

	case 'isbackupbet':
		$sql = "select isbackupbet from club_info where id = " . $club_id;
		if ( $result = $mysqli->query( $sql ) ) {
			while ( $row = mysqli_fetch_object( $result ) ) {
				$data[] = $row;
			} //$row = mysqli_fetch_object( $result )
		} //$result = $mysqli->query( $sql )
		else {
			$data = array(
				0 => 'empty'
			);
		}
		echo json_encode( $data, JSON_UNESCAPED_UNICODE );
		$result->free(); //메모리해제
		break;

	case 'abet_save':
		$sql = "UPDATE `club_info` set `abet_ip` = '" . $abet_ip . "', `abet_id` = '" . $abet_id . "', `abet_name` = '" . $abet_name . "', `abet_time_offset` = " . $abet_time_offset . " where id = " . $club_id;
		if ( $mysqli->query( $sql ) === true ) {
			$data[ Ok ] = 'Updated';
		} //$mysqli->query( $sql ) === true
		else {
			$data[ Error ] = $mysqli->error;
		}
		echo json_encode( $data, JSON_UNESCAPED_UNICODE );
		break;

	case 'abet':
		//http://해당 아이피:8080/write?mode=input&num1=번호1&num2=번호2&num3=장수&id=구매번호&name=구매이름&sender=14&site=1
		$buy_count = 0;
		foreach ( $ticket as $t ) {
			$num = explode( '-', $t[ 'select_num' ] );
			$amount = nRound( $t[ 'bet_money_all' ], 1000, true ) / 10000;
			$url = 'http://' . $ip . ':8080/write?mode=input&num1=' . $num[ 0 ] . '&num2=' . $num[ 1 ] . '&num3=' . $amount . '&id=' . $id . '&name=' . $name . '&sender=14&site=1';
			$msg = file_get_contents( $url );
			//$msg = 'WRITE_SUCCESS';
			$data[] = $t[ 'select_num' ] . ' ' . $amount . '장 구매: ' . $code[ $msg ];
			if ( $msg === 'WRITE_SUCCESS' ) {
				$buy_count += $amount;
			}
			//$data[] = $url;
		}
		$data[] = '총구매 장수: ' . $buy_count . ' 장';

		if ( $buy_count > 0 ) {
			$sql = "REPLACE INTO `abet_info` (`club_id`, `race_id`) VALUES (" . $club_id . ", " . $race_id . ")";
			//$data[] = $sql;

			if ( $mysqli->query( $sql ) === true ) {
				$data[] = '구매 이력 등록함';
			} //$mysqli->query( $sql ) === true
			else {
				$data[] = $mysqli->error;
			}
		}
		echo json_encode( $data, JSON_UNESCAPED_UNICODE );
		break;

	case 'abet_check':
		$sql = "select * from abet_info where race_id = " . $race_id . " and club_id = " . $club_id;
		if ( $result = $mysqli->query( $sql ) ) {
			while ( $row = mysqli_fetch_object( $result ) ) {
				$data[] = $row;
			} //$row = mysqli_fetch_object( $result )
		} //$result = $mysqli->query( $sql )
		else {
			$data = array(
				0 => 'empty'
			);
		}
		echo json_encode( $data, JSON_UNESCAPED_UNICODE );
		$result->free(); //메모리해제
		break;

	case 'send_popup':
		$sql = "INSERT INTO `announce` (`club_id`, `user_id` , `type`, `memo`, `tarket`) VALUES (" . $club_id . ", '" . $user_id . "', '팝업안내','" . $memo . "','C')";
		if ( $mysqli->query( $sql ) === true ) {
			$data[ Ok ] = 'Inserted';
		} //$mysqli->query( $sql ) === true
		else {
			$data[ Error ] = $mysqli->error;
		}
		echo json_encode( $data, JSON_UNESCAPED_UNICODE );
		break;

} //$mode
unset( $data );
// 접속 종료
$mysqli->close();
?>