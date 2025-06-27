<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header("Content-Type:application/json");
header("Content-Type:text/html;charset=UTF-8");
include_once(__DIR__ . '/../../../application/configs/configdb.php');

/**
 * A custom function that automatically constructs a multi insert statement.
 *
 * @param string $tableName Name of the table we are inserting into.
 * @param array $data An "array of arrays" containing our row data.
 * @param PDO $pdoObject Our PDO object.
 * @return boolean TRUE on success. FALSE on failure.
 */
/* //Connect to MySQL with PDO.
$pdo = new PDO('mysql:host=localhost;dbname=test', 'root', '');
//An array of arrays, containing the rows that we want to insert.
    $rowsToInsert = array(
    array(
        'name' => 'John Doe',
        'dob' => '1993-01-04',
    ),
    array(
        'name' => 'Jane Doe',
        'dob' => '1987-06-14',
    ),
    array(
        'name' => 'Joe Bloggs',
        'dob' => '1989-09-29',
    )
);

//An example of adding to our "rows" array on the fly.
$rowsToInsert[] = array(
    'name' => 'Patrick Simmons',
    'dob' => '1972-11-12'
);

//Call our custom function.
pdoMultiInsert('people', $rowsToInsert, $pdo); */

$token = 'bot619682098:AAFkRyjZphcY4Z1hzx3bDlSY9rhNwa6lm4Y';
function sendMessage($chatID = array(), $messaggio, $token)
{
	echo "sending message to administrator: " . $messaggio . PHP_EOL;
	foreach ($chatID as $value) {


		$url = "https://api.telegram.org/" . $token . "/sendMessage?chat_id=" . $value;
		$url = $url . "&text=" . urlencode($messaggio);
		$ch = curl_init();
		$optArray = array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true
		);
		curl_setopt_array($ch, $optArray);
		$result = curl_exec($ch);
		curl_close($ch);
	}
}

function pdoMultiInsert($tableName, $data, $pdoObject)
{

	//Will contain SQL snippets.
	$rowsSQL = array();

	//Will contain the values that we need to bind.
	$toBind = array();

	//Get a list of column names to use in the SQL statement.
	$columnNames = array_keys($data[0]);

	//Loop through our $data array.
	foreach ($data as $arrayIndex => $row) {
		$params = array();
		foreach ($row as $columnName => $columnValue) {
			$param = ":" . $columnName . $arrayIndex;
			$params[] = $param;
			$toBind[$param] = $columnValue;
		}
		$rowsSQL[] = "(" . implode(", ", $params) . ")";
	}

	//Construct our SQL statement
	$sql = "INSERT INTO `$tableName` (" . implode(", ", $columnNames) . ") VALUES " . implode(", ", $rowsSQL);

	//Prepare our PDO statement.
	$pdoStatement = $pdoObject->prepare($sql);

	//Bind our values.
	foreach ($toBind as $param => $val) {
		$pdoStatement->bindValue($param, $val);
	}

	//Execute our statement (i.e. insert the data).
	return $pdoStatement->execute();
}

function select_query($db, $sql, $array = array())
{
	try {
		$stmt = $db->prepare($sql);
		$stmt->execute($array);
		$data = $stmt->fetchAll();
	} catch (Exception $e) {
		$data['Ok'] = 'Error';
		$data['Error'] = $e->getMessage();
	}
	return $data;
}

$db = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8mb4', $user, $password, array(
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_EMULATE_PREPARES => false,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
));
$data = array();
$check_time_bbs_qna = date('Y-m-d H:i:s');
$check_time_banking = date('Y-m-d H:i:s');
$check_time_new_user = date('Y-m-d H:i:s');

while (true) {
	$check_time_begin = date('Y-m-d H:i:s');
	$sql = "Select * from `bbs_qna` left outer join `user_info` on bbs_qna.user_id = user_info.user_id left outer join `club_info` on user_info.club_id = club_info.id where bbs_qna.c_time > ?";
	$bbs_qna = select_query($db, $sql, array($check_time_bbs_qna));
	$check_time_bbs_qna = $check_time_begin;

	$check_time_begin = date('Y-m-d H:i:s');
	$sql = "Select *, banking.r_time as r_time from `banking` left outer join `user_info` on banking.user_id = user_info.user_id left outer join `club_info` on user_info.club_id = club_info.id where banking.r_time > ?";
	$banking = select_query($db, $sql, array($check_time_banking));
	$check_time_banking = $check_time_begin;

	$check_time_begin = date('Y-m-d H:i:s');
	$sql = "Select * , `user_info`.r_time as r_time from `user_info` left outer join `club_info` on user_info.club_id = club_info.id where user_info.r_time > ? and user_info.user_level = ?";
	$new_user = select_query($db, $sql, array($check_time_new_user, 0));
	$check_time_new_user = $check_time_begin;

	foreach ($bbs_qna as $value) {
		$messaggio  = $value->c_time . PHP_EOL . ' => ' . '1:1문의: ' . $value->club_user_id . '[' . $value->nick_name . ']' . PHP_EOL . '---------------------------------------------' . PHP_EOL . $value->subject . PHP_EOL . '---------------------------------------------'  . PHP_EOL . iconv_substr(strip_tags($value->memo_q), 0, 50) . '...'  . PHP_EOL . '---------------------------------------------'  . PHP_EOL . 'http://a.' . explode(',', $value->domain)[0];
		$chatID = explode(',', $value->tg_id);
		print_r($chatID);
		sendMessage($chatID, $messaggio, $token);
	}
	foreach ($banking as $value) {
		if ($value->banking_type == 'I') {
			$messaggio  = $value->r_time . PHP_EOL . ' => ' . '충전신청: ' . $value->club_user_id . '[' . $value->nick_name . ']' . PHP_EOL . '---------------------------------------------' . PHP_EOL . number_format($value->amount) . '원 (' . $value->bank_account_name . ') <' . ($value->isfirst == 'Y' ? '첫충전' : '재충전') . '>' . PHP_EOL . '---------------------------------------------' . PHP_EOL . 'http://a.' . $value->cdomain;
		} else {
			$messaggio  = $value->r_time . PHP_EOL . ' => ' . '환전신청: ' . $value->club_user_id . '[' . $value->nick_name . ']' . PHP_EOL . '---------------------------------------------' . PHP_EOL . number_format($value->amount) . '원 [' . $value->bank_name . '] ' . $value->bank_account_no . ' (' . $value->bank_account_name . ')' . PHP_EOL . '---------------------------------------------' . PHP_EOL . 'http://a.' . $value->cdomain;
		}
		$chatID = explode(',', $value->tg_id);
		sendMessage($chatID, $messaggio, $token);
	}
	foreach ($new_user as $value) {
		$messaggio  = $value->r_time . PHP_EOL . ' => ' . '회원가입신청: ' . $value->club_user_id . '[' . $value->nick_name . ']' . PHP_EOL . '---------------------------------------------' . PHP_EOL . $value->phone . PHP_EOL . '---------------------------------------------' . PHP_EOL . 'http://a.' . $value->cdomain;
		$chatID = explode(',', $value->tg_id);
		print_r($chatID);
		sendMessage($chatID, $messaggio, $token);
	}
	echo 'Sleeping...' . PHP_EOL;
	sleep(10);
}

exit();

switch ($mode) {
	case 'delete':
		echo 'delete';
		break;

	case 'boilerplate':
		//$sql = "Select user_id, club_user_id, nick_name from `user_info` where user_level > -1 and club_id " . ($club_id ===255? '!= ': '= ') . $club_id;
		$sql = "Select * from `boilerplate` where id = ?";
		select_query($db, $sql, array($id));
		break;

	case 'ta':
		//$sql = "select ticketing_association from club_info where id = " . $club_id;
		$sql = "select ticketing_association from club_info where id = ?";
		select_query($db, $sql, array($club_id));
		break;

	case 'user_info':
		//$sql = "Select user_id, club_user_id, nick_name from `user_info` where user_level > -1 and club_id " . ($club_id ===255? '!= ': '= ') . $club_id;
		$sql = "Select * from `user_info` where user_id = ?";
		select_query($db, $sql, array($user_id));
		break;

	case 'get_user':
		//$sql = "Select user_id, club_user_id, nick_name from `user_info` where user_level > -1 and club_id " . ($club_id ===255? '!= ': '= ') . $club_id;
		$sql = "Select user_id, club_user_id, nick_name from `user_info` where user_level > -1 and club_id " . ($club_id === 255 ? '!= ' : '= ') . "?";
		select_query($db, $sql, array($club_id));
		break;

	case 'get_admin':
		//$sql = "Select user_id, club_user_id, nick_name from `user_info` where user_level > -1 and club_id " . ($club_id ===255? '!= ': '= ') . $club_id;
		$sql = "Select user_id, club_user_id, nick_name from `user_info` where user_level >= 100 and club_id " . ($club_id === 255 ? '!= ' : '= ') . "?";
		select_query($db, $sql, array($club_id));
		break;

	case 'get_partner':
		//$sql = "Select user_id, club_user_id, nick_name from `user_info` where user_level > -1 and club_id " . ($club_id ===255? '!= ': '= ') . $club_id;
		$sql = "Select partner_id, club_partner_id, partner_name from `partner_info` where club_id " . ($club_id === 255 ? '!= ' : '= ') . "?";
		select_query($db, $sql, array($club_id));
		break;

	case 'bbs_club_notice_list':
		//$sql = "SELECT * from `bbs_notice` where `club_id` = " . $club_id . " order by  `u_time` desc limit 20";
		$sql = "SELECT * from `bbs_notice` where `club_id` = ? order by  `u_time` desc limit 20";
		select_query($db, $sql, array(255));
		break;

	case 'popup':
		//$sql = "SELECT * from `bbs_notice` where `ispopup` = 'Y' and `club_id` = " . $club_id . " order by  `u_time` desc limit 3";
		$sql = "SELECT * from `bbs_notice` where `ispopup` = 'Y' and `club_id` = ? order by  `u_time` desc limit 3";
		select_query($db, $sql, array(255));
		break;

	case 'check_id':
		//$sql = "Select user_id from `user_info` where user_id =  '" . $user_id . "'";
		$sql = "Select user_id from `user_info` where user_id =  ?";
		select_query($db, $sql, array($user_id));
		break;

	case 'check_nick_name':
		//$sql = "Select user_id from `user_info` where nick_name =  '" . $nick_name . "' and club_id = " . $club_id;
		$sql = "Select user_id from `user_info` where nick_name =  ? and club_id = ?";
		select_query($db, $sql, array($nick_name, $club_id));
		break;

	case 'check_p_id':
		//$sql = "Select partner_id from `partner_info` where partner_id =  '" . $partner_id . "'";
		$sql = "Select partner_id from `partner_info` where partner_id = ?";
		select_query($db, $sql, array($partner_id));
		break;

	case 'todo':
		//$sql = "select (select count(b.id)  from `banking` as b left outer join `user_info` as u on b.user_id = u.user_id where u.club_id " . ($club_id ===255? '!= ': '= ') . $club_id . " and b.stat = 'R' and date(b.r_time) > DATE_ADD(now(),INTERVAL -2 DAY)) as banking_count,(select count(id) from `bbs_qna` where club_id " . ($club_id ===255? '!= ': '= ') . $club_id . " and stat = 'R' and date(u_time) > DATE_ADD(now(),INTERVAL -2 DAY))  as qna_count, (select count(id) from `user_info` where club_id " . ($club_id ===255? '!= ': '= ') . $club_id . " and user_level = 0)  as user_count, (select count(id) from `user_info` where club_id " . ($club_id ===255? '!= ': '= ') . $club_id . " and bank_out_bank_info_ok = 'R')  as bank_confirm_count";
		$sql = "select (select count(b.id)  from `banking` as b left outer join `user_info` as u on b.user_id = u.user_id where u.club_id " . ($club_id === 255 ? '!= ' : '= ') . "? and b.stat = 'R' and date(b.r_time) > DATE_ADD(now(),INTERVAL -2 DAY)) as banking_count,(select count(id) from `bbs_qna` where club_id " . ($club_id === 255 ? '!= ' : '= ') . "? and stat = 'R' and date(u_time) > DATE_ADD(now(),INTERVAL -2 DAY))  as qna_count, (select count(id) from `user_info` where club_id " . ($club_id === 255 ? '!= ' : '= ') . "? and user_level = 0)  as user_count, (select count(id) from `user_info` where club_id " . ($club_id === 255 ? '!= ' : '= ') . "? and bank_out_bank_info_ok = 'R')  as bank_confirm_count";
		select_query($db, $sql, array($club_id, $club_id, $club_id, $club_id));
		break;

	case 'signage':
		//$sql = "SELECT * FROM `announce` WHERE  date(`r_time`) = date(now()) and (`association_code`= '0' or association_code in (" . $ta . ")) and (`club_id`= 0 or `club_id` " . ($club_id ===255? '!= ': '= ') . $club_id . " ) and (`tarket` = 'B' or `tarket` = 'A') order by r_time desc limit 10";
		$sql = "SELECT * FROM `announce` WHERE  date(`r_time`) = date(now()) and (`association_code`= '0' or association_code in (" . $ta . ")) and (`user_id`= '0' or `user_id`= '255' or `user_id` = ?) and (`club_id`= 255 or `club_id`= 0 or `club_id` " . ($club_id === 255 ? '!= ' : '= ') . "?" . " ) and (`tarket` = 'B' or `tarket` = 'A') " . ($club_id === 255 ? "" : " and type != '마토상담신청'")  . " order by r_time desc limit 10";
		select_query($db, $sql, array($_SESSION['user_id'], $club_id));
		break;

	case 'attendance':
		//$sql = "SELECT distinct '' as title, attendance_date as start FROM `attendance` WHERE  `user_id`= '" . $user_id . "'";
		$sql = "SELECT distinct '' as title, attendance_date as start FROM `attendance` WHERE  `user_id`= ?";
		select_query($db, $sql, array($user_id));
		break;

	case 'money_log':
		//$sql = "SELECT * FROM `log_money` where date(u_time) >= date('" . $sdate . "') and  date(u_time) <= date('" . $edate . "') and user_id = '" . $user_id . "'";u_time >= DATE_ADD(?, INTERVAL 6 HOUR) AND u_time <= DATE_ADD(?, INTERVAL 30 HOUR)

		$sql = "SELECT * FROM `log_money` where u_time >= DATE_ADD(?, INTERVAL 6 HOUR) AND u_time < DATE_ADD(?, INTERVAL 30 HOUR) and user_id = ?";
		select_query($db, $sql, array($sdate, $edate, $user_id));
		break;

	case 'service_log':
		//$sql = "SELECT * FROM `log_money` where old_money_service != new_money_service and (memo like '손실금%' or memo like '충전%' or memo like '서비스%' or memo like '추천인%')" . " and date(u_time) >= date('" . $sdate . "') and date(u_time) <= date('" . $edate . "') and user_id = '" . $user_id . "'";
		$sql = "SELECT * FROM `log_money` where old_money_service != new_money_service and (memo like '손실금%' or memo like '충전%' or memo like '서비스%' or memo like '추천인%')" . " and u_time >= DATE_ADD(?, INTERVAL 6 HOUR) AND u_time < DATE_ADD(?, INTERVAL 30 HOUR) and user_id = ?";
		select_query($db, $sql, array($sdate, $edate, $user_id));
		break;

	case 'allin_service_log':
		//$sql = "SELECT * FROM `log_money` where old_money_service != new_money_service and (memo like '손실금%' or memo like '충전%' or memo like '서비스%' or memo like '추천인%')" . " and date(u_time) >= date('" . $sdate . "') and date(u_time) <= date('" . $edate . "') and user_id = '" . $user_id . "'";
		$sql = "SELECT * FROM `log_money` where old_money_service != new_money_service and memo like '손실금%'" . " and u_time >= DATE_ADD(?, INTERVAL 6 HOUR) AND u_time < DATE_ADD(?, INTERVAL 30 HOUR) and club_id = ?";
		select_query($db, $sql, array($sdate, $edate, $club_id));
		break;

	case 'club_id':
		$sql = "select id, club_name, club_code from club_info where id != 0 and id != 255 and club_level > -1";
		select_query($db, $sql);
		break;

	case 'club_info':
		//$sql = "select * from club_info where id = " . $club_id ;
		$sql = "select * from club_info where id = ?";
		select_query($db, $sql, array($club_id));
		break;

	case 'reset_user_history':
		$db->beginTransaction();
		try {
			$sql = "DELETE FROM cut_result_money WHERE club_id = ?";
			$stmt = $db->prepare($sql);
			$stmt->execute(array($club_id));

			$sql = "DELETE FROM banking WHERE club_id = ?";
			$stmt = $db->prepare($sql);
			$stmt->execute(array($club_id));

			$sql = "DELETE FROM log_money WHERE club_id = ?";
			$stmt = $db->prepare($sql);
			$stmt->execute(array($club_id));

			$sql = "DELETE FROM `order` WHERE club_id = ?";
			$stmt = $db->prepare($sql);
			$stmt->execute(array($club_id));

			$sql = "UPDATE `user_info` SET `money_real`='0', `money_service`='0' WHERE club_id = ?";
			$stmt = $db->prepare($sql);
			$stmt->execute(array($club_id));

			$db->commit();
			$data['Ok'] = 'Reseted';
		} catch (Exception $e) {
			$db->rollBack();
			$data['Ok'] = 'Error';
			$data['Error'] = '초기화중 오류'; //$e->getMessage();//
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;

	case 'create_club':
		//$sql1 = "INSERT INTO club_info (club_name, club_code, domain) values ('" . $club_name . "','" . $club_code . "','" . $domain . "')";
		//$sql2 = "INSERT INTO user_info (club_user_id, user_id, user_pw, nick_name, club_id, user_level) values ('" . $admin_id . "','" . '_' . $club_code . '_' . $admin_id . "','" . $admin_pw . "','관리자', (select id from club_info where club_code = '". $club_code . "'), 100)";
		//insert into club_place (club_id, place_id) select 333, id from place;
		//insert into club_race (club_id, race_id) select 333, id from race;	
		$db->beginTransaction();
		try {
			$sql = "INSERT INTO club_info (club_name, club_code, domain, version, admin_theme, user_theme ) values (?, ?, ?, ?, ?, ?)";
			$stmt = $db->prepare($sql);
			$stmt->execute(array($club_name, $club_code, $domain, $version, $admin_theme, $user_theme));
			$insertId = $db->lastInsertId();

			$sql = "insert into club_place (club_id, place_id) select ?, id from place";
			$stmt = $db->prepare($sql);
			$stmt->execute(array($insertId));

			$sql = "insert into club_race (club_id, race_id) select ?, id from race";
			$stmt = $db->prepare($sql);
			$stmt->execute(array($insertId));

			$sql = "INSERT INTO user_info (club_user_id, user_id, user_pw, nick_name, club_id, user_level) values (?, ?, ?, '관리자', ?, 100)";
			$admin_user_id = '_' . $club_code . '_' . $admin_id;
			$stmt = $db->prepare($sql);
			$stmt->execute(array($admin_id, $admin_user_id, $admin_pw, $insertId));

			$db->commit();
			$data['Ok'] = 'Inserted';
		} catch (Exception $e) {
			$db->rollBack();
			$data['Ok'] = 'Error';
			$data['Error'] = '등록중 오류'; //$e->getMessage();//
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;

	case 'reg_results':
		$results = array();
		$sql = "SELECT * FROM `view_place_result` WHERE race_id = ?";
		try {
			$stmt = $db->prepare($sql);
			$stmt->execute(array($race_id));
			$results = $stmt->fetchAll();
		} catch (Exception $e) {
			query_error();
			exit();
		}
		// 	    select_query($db, $sql, array($race_id));

		$odds_dan = $results[0]->dan;
		$odds_yun = $results[0]->yun;
		$odds_bok = $results[0]->bok;
		$odds_ssang = $results[0]->ssang;
		$odds_bokyun = $results[0]->bokyun;
		$odds_sambok = $results[0]->sambok;
		$odds_samssang = $results[0]->samssang;

		$sql = "select group_concat(DISTINCT `type`,':',`result` separator ' ') as `odds_all` from `view_result` where race_id = ?";
		try {
			$stmt = $db->prepare($sql);
			$stmt->execute(array($race_id));
			$results = $stmt->fetchAll();
		} catch (Exception $e) {
			query_error();
			exit();
		}

		$odds_all = $results[0]->odds_all;

		$sql = "UPDATE `race` SET odds_dan = '" . $odds_dan . "',	odds_yun = '" . $odds_yun . "',	odds_bok = '" . $odds_bok . "',	odds_ssang = '" . $odds_ssang . "',	odds_bokyun = '" . $odds_bokyun . "',	odds_sambok = '" . $odds_sambok . "',	odds_samssang = '" . $odds_samssang . "',	odds_all = '" . $odds_all . "' WHERE `id`= ?";
		// 	    $data[ 'Ok' ] = $sql;
		// 	    echo json_encode( $data, JSON_UNESCAPED_UNICODE );
		// 	    exit();
		$db->beginTransaction();
		try {
			$stmt = $db->prepare($sql);
			$stmt->execute(array($race_id));
			$db->commit();
			$data['Ok'] = 'Inserted';
		} catch (Exception $e) {
			$db->rollBack();
			$data['Ok'] = 'Error';
			$data['Error'] = '등록중 오류'; //$e->getMessage();//
		}

		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;

	case 'create_results':
		//An array of arrays, containing the rows that we want to insert.
		$rowsToInsert = array(
			array(
				'race_id' => $race_id,
				'type' => '단승',
			),
			array(
				'race_id' => $race_id,
				'type' => '복승',
			),
			array(
				'race_id' => $race_id,
				'type' => '쌍승',
			),
			array(
				'race_id' => $race_id,
				'type' => '삼복승',
			),
			array(
				'race_id' => $race_id,
				'type' => '삼쌍승',
			),
			array(
				'race_id' => $race_id,
				'type' => '복연승',
			),
			array(
				'race_id' => $race_id,
				'type' => '복연승',
			),
			array(
				'race_id' => $race_id,
				'type' => '복연승',
			),
			array(
				'race_id' => $race_id,
				'type' => '연승',
			),
			array(
				'race_id' => $race_id,
				'type' => '연승',
			),
			array(
				'race_id' => $race_id,
				'type' => '연승',
			),
			array(
				'race_id' => $race_id,
				'type' => '홀짝',
			)
		);

		//Call our custom function.
		if (pdoMultiInsert('result', $rowsToInsert, $db)) {
			$data['Ok'] = 'Inserted';
		} else {
			$data['Ok'] = 'Error';
			$data['Error'] = '등록중 오류'; //$e->getMessage();//
		}

		/* 	    $db->beginTransaction();
	    try {	        
	        $sql = "INSERT INTO result (race_id, type ) values ( " . $race_id . " , '단승' )";
	        $sql .= ", ( " . $race_id . " , '복승' )";
	        $sql .= ", ( " . $race_id . " , '쌍승' )";
	        $sql .= ", ( " . $race_id . " , '삼복승' )";
	        $sql .= ", ( " . $race_id . " , '삼쌍승' )";
	        $sql .= ", ( " . $race_id . " , '복연승' )";
	        $sql .= ", ( " . $race_id . " , '복연승' )";
	        $sql .= ", ( " . $race_id . " , '복연승' )";
	        $sql .= ", ( " . $race_id . " , '연승' )";
	        $sql .= ", ( " . $race_id . " , '연승' )";
	        $sql .= ", ( " . $race_id . " , '연승' )";
	        $sql .= ", ( " . $race_id . " , '홀짝' )";
	        
	        $stmt = $db->prepare( $sql );
	        $stmt->execute();
	        
	        $db->commit();
	        $data[ 'Ok' ] = 'Inserted';
	    } catch ( Exception $e ) {
	        $db->rollBack();
	        $data[ 'Ok' ] = 'Error';
	        $data[ 'Error' ] = '등록중 오류'; //$e->getMessage();//
	    } */


		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;

	case 'money_io_real':
		/*		$sql1 = "update user_info set money_real = money_real + " . (int)$amount . " where user_id = '" . $user_id . "'";
			
			if((int)$amount >= 0 ) {
				$sql2 = "INSERT INTO `log_money` (`old_money_real`, `new_money_real`, `old_money_service`, `new_money_service`, `memo`, `user_id`, `club_id`)
					 select money_real - " . (int)$amount . ", money_real, money_service, money_service, '머니 임의 지급으로 인한 변동'	 , user_id, club_id from user_info where user_info.user_id = '" . $user_id . "'";
			}
			else{
				$sql2 = "INSERT INTO `log_money` (`old_money_real`, `new_money_real`, `old_money_service`, `new_money_service`, `memo`, `user_id`, `club_id`)
					 select money_real + " . abs((int)$amount) . ", money_real, money_service, money_service, '머니 임의 환수로 인한 변동'	 , user_id, club_id from user_info where user_info.user_id = '" . $user_id . "'";		
			}*/

		$db->beginTransaction();
		try {
			$sql = "update user_info set money_real = money_real + ? where user_id = ?";
			$stmt = $db->prepare($sql);
			$stmt->execute(array((int)$amount, $user_id));

			if ((int)$amount >= 0) {
				$sql = "INSERT INTO `log_money` (`old_money_real`, `new_money_real`, `old_money_service`, `new_money_service`, `memo`, `user_id`, `club_id`)
			 select money_real - ?, money_real, money_service, money_service, '머니 임의 지급으로 인한 변동'	 , user_id, club_id from user_info where user_info.user_id = ?";
				$stmt = $db->prepare($sql);
				$stmt->execute(array((int)$amount, $user_id));
			} else {
				$sql = "INSERT INTO `log_money` (`old_money_real`, `new_money_real`, `old_money_service`, `new_money_service`, `memo`, `user_id`, `club_id`)
			 select money_real + ?, money_real, money_service, money_service, '머니 임의 환수로 인한 변동'	 , user_id, club_id from user_info where user_info.user_id = ?";
				$stmt = $db->prepare($sql);
				$stmt->execute(array(abs((int)$amount), $user_id));
			}
			$db->commit();
			$data['Ok'] = 'Updated';
		} catch (Exception $e) {
			$db->rollBack();
			$data['Ok'] = 'Error';
			$data['Error'] = '수정중 오류'; //$e->getMessage();//
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;

	case 'money_io_service':
		/*		$sql1 = "update user_info set money_service = money_service + " . ( int )$amount . " where user_id = '" . $user_id . "'";

				if ( ( int )$amount >= 0 ) {
					$sql2 = "INSERT INTO `log_money` (`old_money_real`, `new_money_real`, `old_money_service`, `new_money_service`, `memo`, `user_id`, `club_id`)
					 select money_real, money_real, money_service  - " . ( int )$amount . " , money_service, '서비스 머니 임의 지급으로 인한 변동'	 , user_id, club_id from user_info where user_info.user_id = '" . $user_id . "'";
				} else {
					$sql2 = "INSERT INTO `log_money` (`old_money_real`, `new_money_real`, `old_money_service`, `new_money_service`, `memo`, `user_id`, `club_id`)
					 select money_real, money_real, money_service + " . abs( ( int )$amount ) . ", money_service, '서비스 머니 임의 환수로 인한 변동'	 , user_id, club_id from user_info where user_info.user_id = '" . $user_id . "'";
				}*/
		$db->beginTransaction();
		try {
			$sql = "update user_info set money_service = money_service + ? where user_id = ?";
			$stmt = $db->prepare($sql);
			$stmt->execute(array((int)$amount, $user_id));

			if ((int)$amount >= 0) {
				$sql = "INSERT INTO `log_money` (`old_money_real`, `new_money_real`, `old_money_service`, `new_money_service`, `memo`, `user_id`, `club_id`)
			 select money_real, money_real, money_service  - ? , money_service, '서비스 머니 임의 지급으로 인한 변동'	 , user_id, club_id from user_info where user_info.user_id = ?";
				$stmt = $db->prepare($sql);
				$stmt->execute(array((int)$amount, $user_id));
			} else {
				$sql = "INSERT INTO `log_money` (`old_money_real`, `new_money_real`, `old_money_service`, `new_money_service`, `memo`, `user_id`, `club_id`)
			 select money_real, money_real, money_service + ?, money_service, '서비스 머니 임의 환수로 인한 변동'	 , user_id, club_id from user_info where user_info.user_id = ?";
				$stmt = $db->prepare($sql);
				$stmt->execute(array(abs((int)$amount), $user_id));
			}
			$db->commit();
			$data['Ok'] = 'Updated';
		} catch (Exception $e) {
			$db->rollBack();
			$data['Ok'] = 'Error';
			$data['Error'] = '수정중 오류'; //$e->getMessage();//
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;

	case 'allin_service':
		$db->beginTransaction();
		try {
			$sql = "UPDATE user_info AS u
				LEFT OUTER
				JOIN club_info c ON u.club_id = c.id
				LEFT OUTER
				JOIN (
				SELECT u.user_id, SUM(`o`.`bet_money`) AS `bet_money_all`, SUM(IF((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) AS `bet_money_real_all`, SUM(IF((`o`.`money_type` = 'S'),`o`.`bet_money`,0)) AS `bet_money_service_all`, SUM(`o`.`service_money`) AS `service_money_all`, SUM(`o`.`result_money`) AS `result_money_all`, (SUM(IF((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) - SUM(`o`.`result_money`)) AS `profit`
				FROM `order` AS o
				LEFT OUTER
				JOIN `user_info` AS `u` ON `u`.user_id = o.user_id
				WHERE ((`o`.`stat` != 'C') AND (`o`.`stat` != 'R')) AND o.buy_time >= DATE_ADD(?, INTERVAL 6 HOUR) AND o.buy_time < DATE_ADD(?, INTERVAL 30 HOUR) 
				GROUP BY u.user_id) AS o ON u.user_id = o.user_id
				LEFT OUTER
				JOIN (
				SELECT user_id u_id, SUM(`cut_money`) AS `cut_money_all`
				FROM cut_result_money
				WHERE c_date >= DATE_ADD(?, INTERVAL 6 HOUR) AND c_date < DATE_ADD(?, INTERVAL 30 HOUR)
				GROUP BY user_id) AS cr ON u.user_id = cr.u_id SET 
				 u.money_service = u.money_service + @allin_service_money := ROUND(IF(@allin_service_max_offset := IF(u.allin_service_config = 'C', c.allin_service_max_offset, u.u_allin_service_max_offset) =0, @crprofit := (IF(profit IS NULL, 0, profit) + IF (cut_money_all IS NULL, 0, cut_money_all)) * (@allin_service := IF(u.allin_service_config = 'C', c.allin_service, u.u_allin_service)/100), IF(@allin_service_max_offset >= @crprofit,@crprofit * (@allin_service/100), @allin_service_max_offset * (@allin_service/100))))
				WHERE u.club_id = ? AND u.allin_service_config != 'N' AND u.user_level < 50 AND o.bet_money_all > 0 AND (IF(profit IS NULL, 0, profit) + IF (cut_money_all IS NULL, 0, cut_money_all)) >= IF(u.allin_service_config = 'C', c.allin_service_min_offset, u.u_allin_service_min_offset)";
			$stmt = $db->prepare($sql);
			$stmt->execute(array($sdate, $edate, $sdate, $edate, $club_id));

			$sql = "INSERT INTO `log_money` (`old_money_real`, `new_money_real`, `old_money_service`, `new_money_service`, `memo`, `user_id`, `club_id`)
			 select money_real, money_real, money_service  - ROUND(IF(@allin_service_max_offset := IF(u.allin_service_config = 'C', c.allin_service_max_offset, u.u_allin_service_max_offset) =0, @crprofit := (IF(profit IS NULL, 0, profit) + IF (cut_money_all IS NULL, 0, cut_money_all)) * (@allin_service := IF(u.allin_service_config = 'C', c.allin_service, u.u_allin_service)/100), IF(@allin_service_max_offset >= @crprofit,@crprofit * (@allin_service/100), @allin_service_max_offset * (@allin_service/100)))) , money_service, '손실금(" . $sdate . ") 일괄 지급으로 인한 변동', u.user_id, u.club_id from user_info as u left outer join club_info c on u.club_id = c.id left outer join (select u.user_id, sum(`o`.`bet_money`) AS `bet_money_all`, sum(if((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) AS `bet_money_real_all`,sum(if((`o`.`money_type` = 'S'),`o`.`bet_money`,0)) AS `bet_money_service_all`,sum(`o`.`service_money`) AS `service_money_all`,sum(`o`.`result_money`) AS `result_money_all`, (sum(if((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) - sum(`o`.`result_money`)) AS `profit` from `order` as o left outer join `user_info` as `u` on `u`.user_id = o.user_id where ((`o`.`stat` != 'C') and (`o`.`stat` != 'R')) and o.buy_time >= DATE_ADD(?, INTERVAL 6 HOUR) AND o.buy_time < DATE_ADD(?, INTERVAL 30 HOUR)	group by u.user_id) as o on u.user_id = o.user_id LEFT OUTER JOIN (SELECT user_id u_id, SUM(`cut_money`) AS `cut_money_all` FROM cut_result_money WHERE c_date >= DATE_ADD(?, INTERVAL 6 HOUR) AND c_date < DATE_ADD(?, INTERVAL 30 HOUR) GROUP BY user_id) AS cr ON u.user_id = cr.u_id WHERE u.club_id = ? AND u.allin_service_config != 'N' AND u.user_level < 50 AND o.bet_money_all > 0 AND (IF(profit IS NULL, 0, profit) + IF (cut_money_all IS NULL, 0, cut_money_all)) >= IF(u.allin_service_config = 'C', c.allin_service_min_offset, u.u_allin_service_min_offset)";;
			$stmt = $db->prepare($sql);
			$stmt->execute(array($sdate, $edate, $sdate, $edate, $club_id));

			/*			if ( $allin_service_max_offset !== 0 ) {
							$sql = "update user_info as u left outer join club_info c on u.club_id = c.id left outer join (select u.user_id, sum(`o`.`bet_money`) AS `bet_money_all`, sum(if((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) AS `bet_money_real_all`,sum(if((`o`.`money_type` = 'S'),`o`.`bet_money`,0)) AS `bet_money_service_all`,sum(`o`.`service_money`) AS `service_money_all`,sum(`o`.`result_money`) AS `result_money_all`, (sum(if((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) - sum(`o`.`result_money`)) AS `profit` from `order` as o left outer join `user_info` as `u` on `u`.user_id = o.user_id where ((`o`.`stat` != 'C') and (`o`.`stat` != 'R')) and date(o.buy_time) >= ? and date(o.buy_time) <= ?	group by u.user_id) as o on u.user_id = o.user_id LEFT OUTER JOIN (SELECT user_id u_id, SUM(`cut_money`) AS `cut_money_all` FROM cut_result_money WHERE DATE(c_date) >= DATE(?) AND DATE(c_date) <= DATE(?) GROUP BY user_id) AS cr ON u.user_id = cr.u_id set u.money_service = u.money_service + round((if(profit is null, 0, profit) + if (cut_money_all is null, 0, cut_money_all)) * c.allin_service2/100) where u.club_id = ? and u.allin_service_config != 'N' and u.user_level < 50 and  o.bet_money_all > 0 and if(profit is null, 0, profit) + if (cut_money_all is null, 0, cut_money_all) >= c.allin_service_max_offset and if(c.allin_service_max_offset2 = 0, profit>0, if(profit is null, 0, profit) + if (cut_money_all is null, 0, cut_money_all) <= c.allin_service_max_offset2) and if(profit is null, 0, profit) + if (cut_money_all is null, 0, cut_money_all) >0";
							$stmt = $db->prepare( $sql );
							$stmt->execute( array( $sdate, $edate, $sdate, $edate, $club_id ) );

							$sql = "INSERT INTO `log_money` (`old_money_real`, `new_money_real`, `old_money_service`, `new_money_service`, `memo`, `user_id`, `club_id`)
							 select money_real, money_real, money_service  - round((if(profit is null, 0, profit) + if (cut_money_all is null, 0, cut_money_all)) * c.allin_service2/100) , money_service, '손실금(" . $sdate . ") 일괄 지급으로 인한 변동', u.user_id, u.club_id from user_info as u left outer join club_info c on u.club_id = c.id left outer join (select u.user_id, sum(`o`.`bet_money`) AS `bet_money_all`, sum(if((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) AS `bet_money_real_all`,sum(if((`o`.`money_type` = 'S'),`o`.`bet_money`,0)) AS `bet_money_service_all`,sum(`o`.`service_money`) AS `service_money_all`,sum(`o`.`result_money`) AS `result_money_all`, (sum(if((`o`.`money_type` = 'R'),`o`.`bet_money`,0)) - sum(`o`.`result_money`)) AS `profit` from `order` as o left outer join `user_info` as `u` on `u`.user_id = o.user_id where ((`o`.`stat` != 'C') and (`o`.`stat` != 'R')) and date(o.buy_time) >= ? and date(o.buy_time) <= ?	group by u.user_id) as o on u.user_id = o.user_id LEFT OUTER JOIN (SELECT user_id u_id, SUM(`cut_money`) AS `cut_money_all` FROM cut_result_money WHERE DATE(c_date) >= DATE(?) AND DATE(c_date) <= DATE(?) GROUP BY user_id) AS cr ON u.user_id = cr.u_id where u.club_id = ? and u.allin_service_config != 'N' and u.user_level < 50 and  o.bet_money_all > 0 and if(profit is null, 0, profit) + if (cut_money_all is null, 0, cut_money_all) >= c.allin_service_max_offset and if(c.allin_service_max_offset2 = 0, profit>0, if(profit is null, 0, profit) + if (cut_money_all is null, 0, cut_money_all) <= c.allin_service_max_offset2) and if(profit is null, 0, profit) + if (cut_money_all is null, 0, cut_money_all) >0";
							$stmt = $db->prepare( $sql );
							$stmt->execute( array( $sdate, $edate, $sdate, $edate, $club_id ) );
						}*/

			$db->commit();
			$data['Ok'] = 'Updated';
		} catch (Exception $e) {
			$db->rollBack();
			$data['Ok'] = 'Error';
			$data['Error'] = '지급중 오류'; //$e->getMessage();//
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;

	case 'check_allin_service':
		//$sql = "Select id from `log_money` where  club_id = " . $club_id . " and date(u_time) = '" . $sdate . "' and memo =  '올인서비스 일괄 지급으로 인한 변동' limit 1";
		$sql = "Select id from `log_money` where  club_id = ? and memo = '손실금(" . $sdate . ") 일괄 지급으로 인한 변동' limit 1";
		select_query($db, $sql, array($club_id));
		break;

	case 'send_popup':
		//$sql = "INSERT INTO `announce` (`club_id`, `user_id` , `type`, `memo`, `tarket`) VALUES (" . $club_id . ", '" . $user_id . "', '팝업안내','" . $memo . "','C')";
		$sql = "INSERT INTO `announce` (`club_id`, `user_id` , `type`, `memo`, `tarket`) VALUES (?, ?, '팝업안내',?,'C')";
		try {
			$stmt = $db->prepare($sql);
			$stmt->execute(array($club_id, $user_id, $memo));
			$data['Ok'] = 'Inserted';
		} catch (Exception $e) {
			$data['Ok'] = 'Error';
			$data['Error'] = '등록중 오류'; //$e->getMessage();//
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;

	case 'send_signage':
		//$sql = "INSERT INTO `announce` (`club_id`, `user_id` , `type` , `memo`, `tarket`) VALUES (" . $club_id . ", '" . $user_id . "' , '안내','" . $memo . "','C')";
		$sql = "INSERT INTO `announce` (`club_id`, `user_id` , `type` , `memo`, `tarket`) VALUES (?, ? , '안내',?,'C')";
		try {
			$stmt = $db->prepare($sql);
			$stmt->execute(array($club_id, $user_id, $memo));
			$data['Ok'] = 'Inserted';
		} catch (Exception $e) {
			$data['Ok'] = 'Error';
			$data['Error'] = '등록중 오류'; //$e->getMessage();//
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;

	case 'send_refresh':
		//$sql = "INSERT INTO `announce` (`club_id`, `user_id` , `type`, `memo`, `tarket`) VALUES (" . $club_id . ", '" . $user_id . "' , 'refresh','새로 고침','C')";
		$sql = "INSERT INTO `announce` (`club_id`, `user_id` , `type`, `memo`, `tarket`) VALUES (?, ? , 'refresh','새로 고침','C')";
		try {
			$stmt = $db->prepare($sql);
			$stmt->execute(array($club_id, $user_id));
			$data['Ok'] = 'Inserted';
		} catch (Exception $e) {
			$data['Ok'] = 'Error';
			$data['Error'] = '등록중 오류'; //$e->getMessage();//
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;

	case 'send_logout':
		//$sql = "INSERT INTO `announce` (`club_id`, `user_id` , `type`, `memo`, `tarket`) VALUES (" . $club_id . ", '" . $user_id . "' , 'logout','로그 아웃','C')";
		$sql = "INSERT INTO `announce` (`club_id`, `user_id` , `type`, `memo`, `tarket`) VALUES (?, ? , 'logout','로그 아웃','C')";
		try {
			$stmt = $db->prepare($sql);
			$stmt->execute(array($club_id, $user_id));
			$data['Ok'] = 'Inserted';
		} catch (Exception $e) {
			$data['Ok'] = 'Error';
			$data['Error'] = '등록중 오류'; //$e->getMessage();//
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;

	case 'send_popup_club':
		//$sql = "INSERT INTO `announce` (`club_id`, `user_id` , `type`, `memo`, `tarket`) VALUES (" . $club_id . ", '" . $user_id . "', '팝업안내','" . $memo . "','C')";
		$sql = "INSERT INTO `announce` (`club_id`, `user_id` , `type`, `memo`, `tarket`) VALUES (?, ?, '팝업안내',?,'A')";
		try {
			$stmt = $db->prepare($sql);
			$stmt->execute(array($club_id, $user_id, $memo));
			$data['Ok'] = 'Inserted';
		} catch (Exception $e) {
			$data['Ok'] = 'Error';
			$data['Error'] = '등록중 오류'; //$e->getMessage();//
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;

	case 'send_signage_club':
		//$sql = "INSERT INTO `announce` (`club_id`, `user_id` , `type` , `memo`, `tarket`) VALUES (" . $club_id . ", '" . $user_id . "' , '안내','" . $memo . "','C')";
		$sql = "INSERT INTO `announce` (`club_id`, `user_id` , `type` , `memo`, `tarket`) VALUES (?, ? , '안내',?,'A')";
		try {
			$stmt = $db->prepare($sql);
			$stmt->execute(array($club_id, $user_id, $memo));
			$data['Ok'] = 'Inserted';
		} catch (Exception $e) {
			$data['Ok'] = 'Error';
			$data['Error'] = '등록중 오류'; //$e->getMessage();//
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;

	case 'send_refresh_club':
		//$sql = "INSERT INTO `announce` (`club_id`, `user_id` , `type`, `memo`, `tarket`) VALUES (" . $club_id . ", '" . $user_id . "' , 'refresh','새로 고침','C')";
		$sql = "INSERT INTO `announce` (`club_id`, `user_id` , `type`, `memo`, `tarket`) VALUES (?, ? , 'refresh','새로 고침','A')";
		try {
			$stmt = $db->prepare($sql);
			$stmt->execute(array($club_id, $user_id));
			$data['Ok'] = 'Inserted';
		} catch (Exception $e) {
			$data['Ok'] = 'Error';
			$data['Error'] = '등록중 오류'; //$e->getMessage();//
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;

	case 'send_logout_club':
		//$sql = "INSERT INTO `announce` (`club_id`, `user_id` , `type`, `memo`, `tarket`) VALUES (" . $club_id . ", '" . $user_id . "' , 'logout','로그 아웃','C')";
		$sql = "INSERT INTO `announce` (`club_id`, `user_id` , `type`, `memo`, `tarket`) VALUES (?, ? , 'logout','로그 아웃','A')";
		try {
			$stmt = $db->prepare($sql);
			$stmt->execute(array($club_id, $user_id));
			$data['Ok'] = 'Inserted';
		} catch (Exception $e) {
			$data['Ok'] = 'Error';
			$data['Error'] = '등록중 오류'; //$e->getMessage();//
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;

	case 'top_notice':
		//$sql = "UPDATE `club_info` set club_top_notice = '" . $top_notice . "' where id = " . $club_id;
		$sql = "UPDATE `club_info` set club_top_notice = ? where id = ?";
		try {
			$stmt = $db->prepare($sql);
			$stmt->execute(array($top_notice, $club_id));
			$data['Ok'] = 'Updated';
		} catch (Exception $e) {
			$data['Ok'] = 'Error';
			$data['Error'] = '수정중 오류'; //$e->getMessage();//
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;

	case 'emergency':
		try {
			//$sql = "UPDATE `club_info` set domain = '' where id = " . $club_id;
			$sql = "UPDATE `club_info` set domain = '' where id = ?" . $club_id;
			$stmt = $db->prepare($sql);
			$stmt->execute(array($club_id));
			$data['Ok'] = 'Updated';

			//$sql = "INSERT INTO `announce` (`club_id`, `user_id` , `type`, `memo`, `tarket`) VALUES (" . $club_id . ", '0' , 'logout','로그 아웃','C')";
			$sql = "INSERT INTO `announce` (`club_id`, `user_id` , `type`, `memo`, `tarket`) VALUES (?, '0' , 'logout','로그 아웃','C')";
			$stmt = $db->prepare($sql);
			$stmt->execute(array($club_id));
			$data['Ok'] = 'Updated';
		} catch (Exception $e) {
			$data['Ok'] = 'Error';
			$data['Error'] = '수정중 오류'; //$e->getMessage();//
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;

	case 'pwsave':
		//$sql = "UPDATE `user_info` set user_pw = '" . $pw . "' where user_id = '" . $user_id . "'";
		$sql = "UPDATE `user_info` set user_pw = ? where user_id = ?";
		try {
			$stmt = $db->prepare($sql);
			$stmt->execute(array($pw, $user_id));
			$data['Ok'] = 'Updated';
		} catch (Exception $e) {
			$data['Ok'] = 'Error';
			$data['Error'] = '수정중 오류'; //$e->getMessage();//
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;

	case 'send_banking_update':
		//$sql = "INSERT INTO `announce` (`club_id`, `type`, `memo`, `tarket`, `user_id`) VALUES (" . $club_id . ", '충환전요청','충환전 요청 처리완료','C','" . $user_id . "')";
		$sql = "INSERT INTO `announce` (`club_id`, `type`, `memo`, `tarket`, `user_id`) VALUES (?, '충환전요청','충환전 요청 처리완료','C',?)";
		try {
			$stmt = $db->prepare($sql);
			$stmt->execute(array($club_id, $user_id));
			$data['Ok'] = 'Inserted';
		} catch (Exception $e) {
			$data['Ok'] = 'Error';
			$data['Error'] = '등록중 오류'; //$e->getMessage();//
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;

	case 'send_bank_confirm':
		//$sql = "INSERT INTO `announce` (`club_id`, `type`, `memo`, `tarket`, `user_id`) VALUES (" . $club_id . ", '계좌승인','계좌승인 요청 처리완료','C','" . $user_id . "')";
		$sql = "INSERT INTO `announce` (`club_id`, `type`, `memo`, `tarket`, `user_id`) VALUES (?, '계좌승인','계좌승인 요청 처리완료','C',?)";
		try {
			$stmt = $db->prepare($sql);
			$stmt->execute(array($club_id, $user_id));
			$data['Ok'] = 'Inserted';
		} catch (Exception $e) {
			$data['Ok'] = 'Error';
			$data['Error'] = '등록중 오류'; //$e->getMessage();//
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;

	case 'option':
		//$sql = "UPDATE `club_info` SET " . $option . " where id = " . $club_id;
		$sql = "UPDATE `club_info` SET " . $option . " where id = ?";
		try {
			$stmt = $db->prepare($sql);
			$stmt->execute(array($club_id));
			$data['Ok'] = 'Updated';
		} catch (Exception $e) {
			$data['Ok'] = 'Error';
			$data['Error'] = '수정중 오류'; //$e->getMessage();//
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		break;
} //$mode
