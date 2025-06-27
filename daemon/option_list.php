<?php

?>
<?php
header("Content-Type:application/json");
header("Content-Type:text/html;charset=UTF-8");
//header("Content-Type:application/json; charset=UTF-8");
$club_id=1;
$mode = 'select';
extract($_POST); 
if ( !isset( $_SESSION ) ) {
	session_start();
}
$user_id = $_SESSION['user_id'];
$club_id = $_SESSION['club_id'];
$user_level = $_SESSION['user_level'];
session_write_close();

include (__DIR__ . '/../../../application/configs/configdb.php');

$mysqli = new mysqli($host, $user, $password, $dbname);
 // 연결 오류 발생 시 스크립트 종료
 if ($mysqli->connect_errno) {
    die('Connect Error: '.$mysqli->connect_error);
}

//echo $mode;
//$mode = insert;

$data = array();

switch ($mode){
	
	case 'select':
			$sql = "select * from club_info where id = " . $club_id ;
			if ($result = $mysqli->query($sql)) {
			// 레코드 출력
			//$o = array();

						while ($row = mysqli_fetch_object($result)) {
							$data[] = $row;
								/*
								//$t = new stdClass();

								$t->id = $row->id;
												//echo $row->name;
								$t->name = $row->name;
								$t->country = $row->country;
								$o[] = $t;
								unset($t);*/
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

