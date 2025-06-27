<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
echo date("Y-m-d H:i:s") . PHP_EOL;

function select_sql($sql)
{
	include __DIR__ . '/../../../application/configs/configdb.php';

	$mysqli = new mysqli($host, $user, $password, $dbname);
	// 연결 오류 발생 시 스크립트 종료
	if ($mysqli->connect_errno) {
		die('Connect Error: ' . $mysqli->connect_error);
	}

	if ($result = $mysqli->query($sql)) {
		// 레코드 출력
		$v = array();
		while ($row = mysqli_fetch_object($result)) {
			// print_r( $row->id);
			$v[] = $row;
		}
	} else {
		$v = array(
			0 => 'empty'
		);
	}
	return $v;

	$result->free(); // 메모리해제
}

function insert_sql($sql)
{
	include __DIR__ . '/../../../application/configs/configdb.php';
	$mysqli = new mysqli($host, $user, $password, $dbname); // 연결 오류 발생 시 스크립트 종료

	if ($mysqli->connect_errno) {
		die('Connect Error: ' . $mysqli->connect_error);
	}
	if ($mysqli->query($sql) === true) {
		return 'ok';
	} else {
		return $mysqli->error;
	}
	$result->free(); // 메모리해제
}

function get_race_data_to_json($url)
{
	echo date("Y-m-d H:i:s") . ' start ' . PHP_EOL;
	$url = 'http://aus.zizi.best:8080/scrap.php?url=' . urlencode($url);
	echo $url . PHP_EOL;

	$sucess = FALSE;
	for ($i = 0; $i < 3; $i++) {
		$file = file_get_contents($url);
		if ($file === FALSE) {
			echo 'file read error' . PHP_EOL;
			continue;
		}
		$json = json_decode($file);
		if (!$json) {
			echo 'json decoding error' . PHP_EOL;
			continue;
		} else {
			$sucess = TRUE;
			break;
		}
	}
	if (!$sucess)
		return FALSE;

	// print_r($json);
	// collect header names
	$headerNames = [
		'association_code',
		'place_name',
		'place_code',
		'race_no',
		'start_date',
		'start_time',
		'length',
		'entry_count',
		'broadcast_channel',
		'pb_stat'
	];

	foreach ($json->meetings as $meeting) {
		// meetingDate: "2019-07-31"
		// meetingName: "SALE"
		// prizeMoney: "$350000.00"
		// raceType: "R"
		$data = array();
		$start_date = $meeting->meetingDate;
		$place_name = $meeting->meetingName;
		$place_e_name = $place_name;
		$association_code = 'os' . strtolower($meeting->raceType);
		$place_code = $association_code . '_' . $meeting->venueMnemonic;
		$rowData = array();

		foreach ($meeting->races as $race) {
			$start_time = date('Y-m-d H:i', strtotime($race->raceStartTime));
			$race_no = $race->raceNumber;
			$broadcastChannel = $race->broadcastChannel;
			$length = $race->raceDistance;
			$url = 'https://api.beta.tab.com.au/v1/tab-info-service/racing/dates/' . $start_date . '/meetings/' . strtoupper(str_replace('os', '', $association_code)) . '/' . str_replace($association_code . '_', '', $place_code) . '/races/' . $race_no . '?jurisdiction=NSW';
			$url = 'http://aus.zizi.best:8080/scrap.php?url=' . urlencode($url);
			echo $url . PHP_EOL;

			$sucess = FALSE;
			for ($i = 0; $i < 3; $i++) {
				$file = file_get_contents($url);
				if ($file === FALSE) {
					echo 'file read error' . PHP_EOL;
					continue;
				}
				$json_race = json_decode($file);
				if (!$json_race) {
					echo 'json decoding error' . PHP_EOL;
					continue;
				} else {
					$sucess = TRUE;
					break;
				}
			}
			if (!$sucess) {
				continue;
			}

			$pb_stat = 'N';
			foreach ($json_race->betTypes as $betType) {
				if ($betType->wageringProduct == 'FirstFour') {
					$pb_stat = 'P';
					break;
				}
			}
			$entry_count = count($json_race->runners);
			if ($entry_count < 7) {
				$pb_stat = 'P';
			}
			$rowData = array(
				$association_code,
				$place_name,
				$place_code,
				$race_no,
				$start_date,
				$start_time,
				$length,
				$entry_count,
				$broadcastChannel,
				$pb_stat
			);
			// print_r($rowData);
			$data[] = array_combine($headerNames, $rowData);
		}

		// print_r($data);

		$sql = "SELECT id FROM place WHERE place_code = '" . $place_code . "';";
		$place_id = select_sql($sql);
		$place_id = $place_id[0]->id;

		echo 'place_id:' . $place_id . PHP_EOL;

		if (!$place_id) {
			switch ($association_code) {
				case 'osr':
					$association_id = 5;
					break;
				case 'osh':
					$association_id = 6;
					break;
				case 'osg':
					$association_id = 7;
					break;
			}

			$sql = "insert into place (place_code, name, e_name, association_id) values ('" . $place_code . "','" . $place_e_name . "','" . $place_e_name . "','" . $association_id . "');";
			echo $sql . PHP_EOL;
			$ok = insert_sql($sql);
			echo $ok . PHP_EOL;

			$sql = "SELECT id FROM place WHERE place_code = '" . $place_code . "';";
			$place_id = select_sql($sql);
			$place_id = $place_id[0]->id;
			echo 'new_place_id:' . $place_id . PHP_EOL;
		}

		foreach ($data as $i => $r) {
			$sql = "SELECT id, broadcast_channel FROM `race` WHERE `place_id`= (select `id` from `place` where `place_code`= '" . $r['place_code'] . "') and `place_name` = '" . $r['place_name'] . "' and `place_code` = '" . $r['place_code'] . "' and `start_date` = '" . $r['start_date'] . "' and `race_no` = " . $r['race_no'];
			// echo $sql . PHP_EOL;
			$result = select_sql($sql);
			if ($result[0]) {
				// Check broadcast channel
				echo '$broadcastChannel ' . $r['broadcast_channel'] . PHP_EOL;

				if ($r['broadcast_channel'] && $r['broadcast_channel'] != $result[0]->broadcast_channel) {
					$sql = "UPDATE race SET broadcast_channel = '" . $r['broadcast_channel'] . "' WHERE id = " . $result[0]->id;
					echo $sql . PHP_EOL;
					$ok = insert_sql($sql);
					echo $ok . PHP_EOL;
				}
			} else {
				$sql = "INSERT INTO `race` (`place_id`, `place_name`, `association_code`, `place_code`, `race_no`, `start_date`, `start_time`, `race_length`, `entry_count`, `broadcast_channel`, `pb_stat`) SELECT (select `id` from `place` where `place_code`= '" . $r['place_code'] . "'), '" . $r['place_name'] . "', '" . $r['association_code'] . "', '" . $r['place_code'] . "'," . $r['race_no'] . ",'" . $r['start_date'] . "','" . $r['start_time'] . "'," . $r['length'] . "," . $r['entry_count'] . ",'" . $r['broadcast_channel'] . "','" . $r['pb_stat'] . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race` WHERE `place_id`= (select `id` from `place` where `place_code`= '" . $r['place_code'] . "') and `place_name` = '" . $r['place_name'] . "' and `place_code` = '" . $r['place_code'] . "' and `start_date` = '" . $r['start_date'] . "' and `race_no` = " . $r['race_no'] . ")";
				echo $sql . PHP_EOL;
				$ok = insert_sql($sql);
				echo $ok . PHP_EOL;
			}
		}
	}
}

// https://api.beta.tab.com.au/v1/tab-info-service/racing/next-to-go/races?jurisdiction=NSW
// https://api.beta.tab.com.au/v1/tab-info-service/racing/dates/today/meetings?jurisdiction=NSW
get_race_data_to_json('https://api.beta.tab.com.au/v1/tab-info-service/racing/dates/today/meetings?jurisdiction=NSW');
// get_race_data_to_json('https://api.beta.tab.com.au/v1/tab-info-service/racing/dates/tomorrow/meetings?jurisdiction=NSW');
