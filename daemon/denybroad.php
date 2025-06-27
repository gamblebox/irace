<?php

?>
<?php
error_reporting(E_ALL ^ E_NOTICE);
//select club_user_id, login_ip, islogin from user_info where DATE_ADD(islogin,INTERVAL 200 SECOND) < now() and DATE_ADD(islogin,INTERVAL 800 SECOND) > now()
$configVars = parse_ini_file (__DIR__ . '/../../../broadserver.ini', true);
include (__DIR__ . '/../../../application/configs/configdb.php');
// 연결 오류 발생 시 스크립트 종료
if ($mysqli->connect_errno) {
		die('Connect Error: '.$mysqli->connect_error);
}	
//DATE_ADD(islogin,INTERVAL 3000 SECOND)
//$sql = "select user_id, login_ip, broad_srv from login_ip_info where islogin,INTERVAL 3000 SECOND) < now() and DATE_ADD(islogin,INTERVAL 3600 SECOND) > now()";
$sql = "select user_id, login_ip, broad_srv from login_ip_info where DATE_ADD(islogin,INTERVAL 240 SECOND) < now() and DATE_ADD(islogin,INTERVAL 840 SECOND) > now()";
$mysqli = new mysqli($host, $user, $password, $dbname);

if ( $result = $mysqli->query( $sql ) ) {
	// 레코드 출력
	//$o = array();
	while ( $row = mysqli_fetch_object( $result ) ) {
		$data[] = $row;
	} //$row = mysqli_fetch_object( $result )
} //$result = $mysqli->query( $sql )
else {
	$data = array(
		 0 => 'empty' 
	);
}
if (count($data) === 0) {
	echo "Nothing to do ...\n";
	return;
}

$work_url = '';
$id = 'root';
//$ip = '192.168.0.100';
//extract( $_POST );
//include_once (__DIR__ . '/../../../configserver.php');
function login_ssh2 ($url, $id, $pw){
	if(!($con = ssh2_connect($url, 22))){
					echo "fail: unable to establish connection\n";
	} else {
					// try to authenticate with username root, password secretpassword
					if(!ssh2_auth_password($con, $id, $pw)) {
						return	false;
					} else {
						return $con;
					}
	}
}

if (!function_exists("ssh2_connect")) die("function ssh2_connect doesn't exist");
// log in at server1.example.com on port 22


foreach ($data as $d) {				
	$url = $d->broad_srv;	
	if ($url === null){
		$url ='103.31.15.243';
	}
	$pw = $configVars[$url]['pw'];
	
	if ($url !== $work_url){
		$con = login_ssh2 ($url, $id, $pw);
		if (!$con) {
			echo "fail: unable to authenticate\n";
		}
		else{
				// allright, we're in!
			echo "okay: logged in...\n";
		}
	}

								
	$work_url = 	$url;	
	$cmd = 'ufw delete allow from '  . $d->login_ip . ' to any port 1935';
	// execute a command
	if (!($stream = ssh2_exec($con, $cmd))) {
					echo "fail: unable to execute command\n";
	} else {
					// collect returning data from command
					stream_set_blocking($stream, true);
					$out = "";
					while ($buf = fread($stream,4096)) {
									$out .= $buf;
					}
					echo $out;
					echo $d->user_id . " execute ban broad command\n";
					fclose($stream);
	}
}
	
	


?>