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
//$club_id = 1;
//$user_id = 'kaiji';
/*if ($_POST['user_id']){
	$user_id = $_POST['user_id'];
} 
//$user_pw ='kaiji';
if ($_POST['user_pw']){
	$user_pw = $_POST['user_pw'];
} 

$ip = 'kaiji';
if ($_POST['ip']){
	$ip = $_POST['ip'];
} 
//$mode = 'login_ok';
if ($_POST['mode']){
	$mode = $_POST['mode'];
}
$ip = $_SERVER['REMOTE_ADDR']; */

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
		$sql = sprintf("SELECT `version` FROM club_info WHERE club_code='%s'",
			$mysqli->real_escape_string($club_code));
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
		$version = $data[0]->version;		
		if ($version !== 'chon'){
			$query = "SELECT * FROM user_info WHERE user_id='%s' AND user_pw=password('%s')";
		}
		else{
			$query = "SELECT * FROM user_info WHERE user_id='%s' AND user_pw='%s'";
		}
			
		$sql = sprintf($query,
			$mysqli->real_escape_string('_' . $club_code. '_' . $club_user_id),
			$mysqli->real_escape_string($user_pw));
				
		//$sql = "select * from `user_info` where user_id = '" .'_' . $club_code. '_' . $club_user_id . "' and user_pw = password('" . $user_pw . "')";
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
		
		$sql = sprintf("update user_info set login_ip = '%s' , login_time = now(), browser = '%s', cdomain = '%s' where user_id ='%s'",
			$mysqli->real_escape_string($ip),
			$mysqli->real_escape_string($browser),
			$mysqli->real_escape_string($cdomain),																
			$mysqli->real_escape_string('_' . $club_code. '_' . $club_user_id)
			);				
		
		//$sql = "update user_info set login_ip = '" . $ip . "' , login_time = now(), browser = '" . $browser . "', cdomain = '" . $cdomain . "' where user_id ='" .'_' . $club_code. '_' . $club_user_id . "'";
		if ( $mysqli->query( $sql ) === true ) {
			$data[ Ok ] = 'Ok';
		} //$mysqli->query( $sql ) === true
		else {
			$data[ Error ] = $mysqli->error;
		}
		
		$sql = sprintf("select * from `user_info` left outer join `club_info` on `user_info`.club_id = `club_info`.id where user_id = '%s'",
			$mysqli->real_escape_string('_' . $club_code. '_' . $club_user_id));
		
		//$sql = "select * from `user_info` left outer join `club_info` on `user_info`.club_id = `club_info`.id where user_id = '" .'_' . $club_code. '_' . $club_user_id . "'";
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
		//print_r($data[0]->nick_name);
		if ( !isset( $_SESSION ) ) {
	session_start();
}
		session_cache_limiter("private"); 
		ini_set("session.cookie_lifetime", 86400); 
		ini_set("session.cache_expire", 1440); 
		ini_set("session.gc_maxlifetime", 86400);
		
		$_SESSION['user_id'] = $data[0]->user_id;
		$_SESSION['club_user_id'] = $data[0]->club_user_id;
		$_SESSION['club_id'] = $data[0]->club_id;
		$_SESSION['nick_name'] = $data[0]->nick_name;		
		$_SESSION['club_name'] = $data[0]->club_name;		
		$_SESSION['club_code'] = $data[0]->club_code;
		$_SESSION['user_level'] = $data[0]->user_level;		
		//$user_id = $_SESSION['user_id'];
//echo $_SESSION['club_name'];
//echo "<meta http-equiv='refresh' content='0;url=../pages/main.php'>";
//$nick_name = $_SESSION['nick_name'];
	echo json_encode($data[0]->user_level, JSON_UNESCAPED_UNICODE);
	
	break;

	case 'check_id':
			$sql = sprintf("SELECT user_id, user_pw, nick_name, user_level, c.id, c.club_name, stat, udomain, c.dup_login, islogin FROM user_info  as u join `club_info` as c on u.club_id = c.id where u.user_id =  '%s'",
				$mysqli->real_escape_string('_' . $club_code. '_' . $club_user_id));				
			//$sql="Select user_id, user_pw, nick_name, user_level, c.id, c.club_name from `user_info` as u join `club_info` as c on u.club_id = c.id where u.user_id =  '" .'_' . $club_code. '_' . $club_user_id . "'";
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
		$sql = sprintf("SELECT `version` FROM club_info WHERE club_code='%s'",
			$mysqli->real_escape_string($club_code));
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
		$version = $data[0]->version;		
		if ($version !== 'chon'){
			$query = "SELECT * FROM user_info WHERE user_id='%s' AND user_pw=password('%s')";
		}
		else{
			$query = "SELECT * FROM user_info WHERE user_id='%s' AND user_pw='%s'";
		}		

			//$sql = "select * from `user_info` where user_id = '" .'_' . $club_code. '_' . $club_user_id . "' and user_pw = password('" . $user_pw . "')";
			
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

