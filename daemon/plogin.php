<?php

?>
<?php
header("Content-Type:application/json");
header("Content-Type:text/html;charset=UTF-8");
function getRealIpAddr() {  
    if(!empty($_SERVER['HTTP_CLIENT_IP']) && getenv('HTTP_CLIENT_IP')){  
        return $_SERVER['HTTP_CLIENT_IP'];  
    } 
    elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR']) && getenv('HTTP_X_FORWARDED_FOR')){  
        return $_SERVER['HTTP_X_FORWARDED_FOR'];  
    } 
    elseif(!empty($_SERVER['REMOTE_HOST']) && getenv('REMOTE_HOST')){  
        return $_SERVER['REMOTE_HOST'];  
    } 
    elseif(!empty($_SERVER['REMOTE_ADDR']) && getenv('REMOTE_ADDR')){  
        return $_SERVER['REMOTE_ADDR'];  
    }  
    return false;  
 } 
//$now = date("Y-m-d H:i:s");
/*$club_id = 1;
$partner_id = 'ddolddol';
if ($_POST['partner_id']){
	$partner_id = $_POST['partner_id'];
} 
$user_pw ='1234';
if ($_POST['user_pw']){
	$user_pw = $_POST['user_pw'];
} 

$ip = 'kaiji';
if ($_POST['ip']){
	$ip = $_POST['ip'];
} 
$mode = 'check_id';
if ($_POST['mode']){
	$mode = $_POST['mode'];
}
$ip = $_SERVER['REMOTE_ADDR'];*/

extract($_POST); 
$ip = getRealIpAddr();

include (__DIR__ . '/../../../application/configs/configdb.php');

$mysqli = new mysqli($host, $user, $password, $dbname);
 // 연결 오류 발생 시 스크립트 종료
 if ($mysqli->connect_errno) {
    die('Connect Error: '.$mysqli->connect_error);
}

$data = array();

switch ($mode){
	case 'get_club_info':
		$sql = "select * from club_info where domain like '%" . $domain . "%' limit 1";
		$club_id = -1;
		if ( $result = $mysqli->query( $sql ) ) {
			while ( $row = mysqli_fetch_object( $result ) ) {				
				$data[] = $row;
			}
		}
		else {
			$data[ Error ] = $mysqli->error;
		}	
		echo json_encode( $data, JSON_UNESCAPED_UNICODE );
		$result->free(); //메모리해제
		break;
		
	case 'login_ok':
		$sql = sprintf("select * from `partner_info` where partner_id = '%s' AND partner_pw=password('%s')",
			$mysqli->real_escape_string('_' . $club_code. '_' . $club_partner_id),
			$mysqli->real_escape_string($partner_pw));
				
	//	$sql="select * from `partner_info` where partner_id = '" .'_' . $club_code. '_' . $club_partner_id . "' and partner_pw = password('" . $partner_pw . "')";	
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
		if ( count($data) == 0 ){
			break;
		}				
		if ( $ip == '112.164.111.11'){
			break;
		}
		$data = array();
		
		//$sql="select * from `partner_info` left outer join `club_info` on `partner_info`.club_id = `club_info`.id where partner_id =  '" .'_' . $club_code. '_' . $club_partner_id . "'";

		$sql = sprintf("select * from `partner_info` left outer join `club_info` on `partner_info`.club_id = `club_info`.id where partner_id = '%s'",
			$mysqli->real_escape_string('_' . $club_code. '_' . $club_partner_id));
		
		if ($result = $mysqli->query($sql)) {
					while ($row = mysqli_fetch_object($result)) {
						$data[] = $row;
					}
			} else {
					$data = array( 0 => 'empty');
		}		
		//print_r($data[0]->nick_name);
		if ( !isset( $_SESSION ) ) {
	session_start();
}
		//ini_set("session.gc_maxlifetime", "86400"); 
		//session_cache_limiter('private'); 
		//session_destroy();
		$_SESSION['partner_id'] = $data[0]->partner_id;
		$_SESSION['club_partner_id'] = $data[0]->club_partner_id;
		$_SESSION['club_id'] = $data[0]->club_id;
		$_SESSION['partner_name'] = $data[0]->partner_name;		
		$_SESSION['club_name'] = $data[0]->club_name;		
		$_SESSION['club_code'] = $data[0]->club_code;
		$_SESSION['partner_level'] = $data[0]->partner_level;		
		//$partner_id = $_SESSION['partner_id'];
//echo $_SESSION['club_name'];
//echo "<meta http-equiv='refresh' content='0;url=../pages/main.php'>";
//$nick_name = $_SESSION['nick_name'];
	echo json_encode($data, JSON_UNESCAPED_UNICODE);
	
	break;

	case 'check_id':
			//$sql="Select partner_id, partner_pw, partner_name, partner_level, c.id, c.club_name from `partner_info` as p join `club_info` as c on p.club_id = c.id where p.partner_id =  '" .'_' . $club_code. '_' . $club_partner_id . "'";
			$sql = sprintf("Select partner_id, partner_pw, partner_name, partner_level, c.id, c.club_name from `partner_info` as p join `club_info` as c on p.club_id = c.id where p.partner_id = '%s'",
				$mysqli->real_escape_string('_' . $club_code. '_' . $club_partner_id));						
		
			if ($result = $mysqli->query($sql)) {
			// 레코드 출력
			//$o = array();
						while ($row = mysqli_fetch_object($result)) {
							$data[] = $row;
						}
				} else {
						$data = array( 0 => 'empty');
			}
	
			echo json_encode($data, JSON_UNESCAPED_UNICODE);
			
			$result->free(); //메모리해제
			break;			
	case 'check_pw':
			//$sql="select * from `partner_info` where partner_id = '" .'_' . $club_code. '_' . $club_partner_id . "' and partner_pw = password('" . $partner_pw . "')";	
			$sql = sprintf("select * from `partner_info` where partner_id ='%s' AND partner_pw = password('%s')",
			$mysqli->real_escape_string('_' . $club_code. '_' . $club_partner_id),
			$mysqli->real_escape_string($partner_pw));		
			
			if ($result = $mysqli->query($sql)) {
						while ($row = mysqli_fetch_object($result)) {
							$data[] = $row;
						}
				} else {
						$data = array( 0 => 'empty');
			}
	
			echo json_encode($data, JSON_UNESCAPED_UNICODE);
			
			$result->free(); //메모리해제
			break;					
}
unset($data);
// 접속 종료
$mysqli->close();

?>

