<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
// error_reporting(E_ALL);

$log_filename = __DIR__ . '/../../rros_error.log';

function push_log($log_str)
{
    global $log_filename;
    $now = date('Y-m-d H:i:s');
    $filep = fopen($log_filename, "a");
    if (!$filep) {
        die("can't open log file : " . $log_filename);
    }
    fputs($filep, "{$now} : {$log_str}" . PHP_EOL);
    fclose($filep);
}

echo date("Y-m-d H:i:s") . PHP_EOL;
// print_r($argv);
$interval = '10 minute';
if ($argv[1] && $argv[2]) {
    $interval = $argv[1] . ' ' . $argv[2];
}
echo $interval . PHP_EOL;

function insert_qe_odds($json, $race, $type, $ktype)
{
    // 	print_r($json);
    $race_id = $race->id;
    $race_id_type = $race_id . '_' . $type;
    $data = array();

    foreach ($json->approximates as $value) {
        $c = implode('-', $value->selections);
        $r = $value->return;
        // 			$data[] = array($c,round($r * 10 / 10, 1));
        $data[] = array($c, $r);
    }

    // echo json_encode($data, JSON_UNESCAPED_UNICODE);
    // $sql = "REPLACE INTO `login_ip_info` (`login_ip`, `islogin`, `user_id`, `broad_srv`) VALUES ('" . $login_ip . "', now(), '" . $user_id . "', '" . $broad_srv ."')";
    if (count($data)) {
        $sql = "REPLACE INTO `os_odds` (`race_id_type`,`race_id`, `type`, `data`)  VALUES ('" . $race_id_type . "','" . $race_id . "','" . $ktype . "','" . json_encode($data, JSON_UNESCAPED_UNICODE) . "')";
        echo $sql . PHP_EOL;
        $ok = insert_sql($sql);
        echo $ok . PHP_EOL;
    }
}

function insert_wp_odds($json, $race)
{
    $data_dan = array();
    $data_yun = array();
    $data_t_dan = array();
    $data_t_yun = array();
    foreach ($json->runners as $value) {
        $entry_no = $value->runnerNumber;
        if ($value->fixedOdds->bettingStatus == 'Reserve') {
            $dan_ratio = 'N/A';
            $yun_ratio = 'N/A';
        } else if ($value->fixedOdds->bettingStatus == 'LateScratched') {
            $dan_ratio = 'SCR';
            $yun_ratio = 'SCR';
        } else {
            $dan_ratio = $value->fixedOdds->returnWin;
            $yun_ratio = $value->fixedOdds->returnPlace;
        }
        if ($value->parimutuel->bettingStatus == 'Scratched') {
            $t_dan_ratio = 'SCR';
            $t_yun_ratio = 'SCR';
        } else {
            $t_dan_ratio = $value->parimutuel->returnWin;
            $t_yun_ratio = $value->parimutuel->returnPlace;
        }
        // 		echo '$value->fixedOdds->bettingStatus' . $value->fixedOdds->bettingStatus . PHP_EOL;
        // 		echo '$t_dan_ratio' . $dan_ratio . PHP_EOL;
        // 		echo '$value->parimutuel->bettingStatus' . $value->parimutuel->bettingStatus . PHP_EOL;
        // 		echo '$t_dan_ratio' . $t_dan_ratio . PHP_EOL;

        if (substr($dan_ratio, -3) == 'SCR' || substr($yun_ratio, -3) == 'SCR' || substr($t_dan_ratio, -3) == 'SCR' || substr($t_yun_ratio, -3) == 'SCR') {
            echo 'SCR' . PHP_EOL;
            $type = '출전취소';
            $memo = $race->place_name . " " . $race->race_no . "경주: " . $entry_no . "번 " . $type;
            $old_start_time = $race->start_time;
            $new_start_time = $race->start_time;
            $sql = "INSERT INTO `race_change_info` (`association_code`, `race_id`, `entry_no`, `type`, `memo`, `old_start_time`, `new_start_time`) SELECT '" . $race->association_code . "', " . $race->id . "," . $entry_no . ", '" . $type . "' , '" . $memo . "','" . $old_start_time . "','" . $new_start_time . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race_change_info` WHERE `race_id`= " . $race->id . " and  `entry_no` = " . $entry_no . " and  `type` = '" . $type . "')";
            echo $sql;
            $ok = insert_sql($sql);
            echo $ok . PHP_EOL;
        } else {
            if ($race->association_code == 'osg') {
                $sql = "SELECT cancel_entry_no FROM race WHERE `id`= " . $race->id;
                echo '$sql' . '->' . $sql . PHP_EOL;
                $cancel_entry_nos = explode(',', select_sql($sql)[0]->cancel_entry_no);
                print_r($cancel_entry_nos);
                echo $entry_no . PHP_EOL;
                push_log(implode(',', $cancel_entry_nos) . ' ' . $entry_no);
                foreach ($cancel_entry_nos as $index => $cancel_entry_no) {
                    if ($cancel_entry_no == $entry_no) {
                        unset($cancel_entry_nos[$index]);
                        $new_cancel_entry_no = implode(',', $cancel_entry_nos);
                        push_log('수정' . $new_cancel_entry_no);
                        echo '$new_cancel_entry_no' . '->' . $new_cancel_entry_no . PHP_EOL;
                        $sql = "UPDATE race SET cancel_entry_no = '" . $new_cancel_entry_no . "'  WHERE `id`= " . $race->id;
                        echo '$sql' . '->' . $sql . PHP_EOL;
                        insert_sql($sql);
                        $sql = "INSERT INTO `announce` (`association_code`, `race_id`, `type`, `memo`) VALUES ('osg', " . $race->id . ", '출전취소', '" . $race->place_name . " " . $race->race_no . "경주:  취소된 " . $entry_no . "번 정상 출전으로 정정 되었습니다')";
                        $ok = insert_sql($sql);
                        echo '$sql' . '->' . $sql . PHP_EOL;
                        break;
                    }
                }
                /*if (in_array($entry_no, $cancel_entry_nos)) {
                    $new_cancel_entry_no = implode(',', ($entry_no, $cancel_entry_nos));
                    echo '$new_cancel_entry_no' . '->' . $new_cancel_entry_no . PHP_EOL;
                    $sql = "UPDATE race SET cancel_entry_no = '" . $new_cancel_entry_no . "'  WHERE `race_id`= " . $race->id;
                    echo '$sql' . '->' . $sql . PHP_EOL;
                    insert_sql($sql);
                    $sql = "INSERT INTO `announce` (`association_code`, `race_id`, `type`, `memo`) VALUES ('osg', " . $race->id . ", '출전취소', '" . $race->place_name . " " . $race->race_no . "경주:  취소된 " . $entry_no . "번 정상 출전으로 정정 되었습니다')";
//         * $ok = insert_sql($sql);
                    echo '$sql' . '->' . $sql . PHP_EOL;
                }*/
            }
        }

        // 		if ($dan_ratio) {
        if ($dan_ratio != 'N/A' && substr($dan_ratio, -3) != 'SCR') {
            $dan_ratio = round(floor($dan_ratio * 10) / 10, 1);
        }
        $data_dan[] = array(
            $entry_no,
            $dan_ratio
        );
        // 		}
        // 		if ($yun_ratio) {
        if ($yun_ratio != 'N/A' && substr($yun_ratio, -3) != 'SCR') {
            $yun_ratio = round(floor($yun_ratio * 10) / 10, 1);
        }
        $data_yun[] = array(
            $entry_no,
            $yun_ratio
        );
        // 		}
        // 		if ($t_dan_ratio) {
        if ($t_dan_ratio != 'N/A' && substr($t_dan_ratio, -3) != 'SCR') {
            $t_dan_ratio = round(floor($t_dan_ratio * 10) / 10, 1);
        }
        $data_t_dan[] = array(
            $entry_no,
            $t_dan_ratio
        );
        // 		}
        // 		if ($t_yun_ratio) {
        if ($t_yun_ratio != 'N/A' && substr($t_yun_ratio, -3) != 'SCR') {
            $t_yun_ratio = round(floor($t_yun_ratio * 10) / 10, 1);
        }
        $data_t_yun[] = array(
            $entry_no,
            $t_yun_ratio
        );
        // 		}
    }

    // echo json_encode($data_dan, JSON_UNESCAPED_UNICODE);
    if (count($data_dan)) {
        $race_id_type = $race->id . '_fw';
        $sql = "REPLACE INTO `os_odds` (`race_id_type`,`race_id`, `type`, `data`)  VALUES ('" . $race_id_type . "','" . $race->id . "','확정단승','" . json_encode($data_dan, JSON_UNESCAPED_UNICODE) . "')";
        echo $sql . PHP_EOL;
        $ok = insert_sql($sql);
        echo $ok . PHP_EOL;
    }
    // echo json_encode($data_yun, JSON_UNESCAPED_UNICODE);
    if (count($data_yun)) {
        $race_id_type = $race->id . '_fp';
        $sql = "REPLACE INTO `os_odds` (`race_id_type`,`race_id`, `type`, `data`)  VALUES ('" . $race_id_type . "','" . $race->id . "','확정연승','" . json_encode($data_yun, JSON_UNESCAPED_UNICODE) . "')";
        echo $sql . PHP_EOL;
        $ok = insert_sql($sql);
        echo $ok . PHP_EOL;
    }
    if (count($data_t_dan)) {
        $race_id_type = $race->id . '_tw';
        $sql = "REPLACE INTO `os_odds` (`race_id_type`,`race_id`, `type`, `data`)  VALUES ('" . $race_id_type . "','" . $race->id . "','단승','" . json_encode($data_t_dan, JSON_UNESCAPED_UNICODE) . "')";
        echo $sql . PHP_EOL;
        $ok = insert_sql($sql);
        echo $ok . PHP_EOL;
    }
    // echo json_encode($data_yun, JSON_UNESCAPED_UNICODE);
    if (count($data_t_yun)) {
        $race_id_type = $race->id . '_tp';
        $sql = "REPLACE INTO `os_odds` (`race_id_type`,`race_id`, `type`, `data`)  VALUES ('" . $race_id_type . "','" . $race->id . "','연승','" . json_encode($data_t_yun, JSON_UNESCAPED_UNICODE) . "')";
        echo $sql . PHP_EOL;
        $ok = insert_sql($sql);
        echo $ok . PHP_EOL;
    }
}

function get_race_data_to_json($race)
{
    echo date("Y-m-d H:i:s") . ' start ' . PHP_EOL;

    $url = 'https://api.beta.tab.com.au/v1/tab-info-service/racing/dates/' . $race->start_date . '/meetings/' . strtoupper(str_replace('os', '', $race->association_code)) . '/' . str_replace($race->association_code . '_', '', $race->place_code) . '/races/' . $race->race_no . '?jurisdiction=NSW';
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
    if (!$sucess) return FALSE;
    // 	print_r($json);

    // Check Status
    $stat = $json->raceStatus;
    if ($stat == 'Abandoned') {
        $entry_no = 0;
        $type = '경주취소';
        $old_start_time = $race->start_time;
        $new_start_time = $race->start_time;

        $memo = $race->place_name . " " . $race->race_no . "경주: 경주취소";
        echo $memo . PHP_EOL;
        $sql = "INSERT INTO `race_change_info` (`association_code`,`race_id`, `entry_no`, `type`, `memo`, `old_start_time`, `new_start_time`) SELECT '" . $race->association_code . "'," . $race->id . "," . $entry_no . ", '" . $type . "' , '" . $memo . "','" . $old_start_time . "','" . $new_start_time . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race_change_info` WHERE `race_id`= " . $race->id . " and  `entry_no` = " . $entry_no . " and  `type` = '" . $type . "' and  `memo` =  '" . $memo . "' and  `old_start_time` = '" . $old_start_time . "' and `new_start_time` = '" . $new_start_time . "')";
        echo $sql . PHP_EOL;
        $ok = insert_sql($sql);
        echo $ok . PHP_EOL;
        return true;
    } else if ($stat == 'Closed' || $stat == 'All Paying' || $stat == 'Interim result') {
        return;
    }

    // Check Start time
    echo '$start_time ' . $json->raceStartTime . PHP_EOL;
    $start_time = date('Y-m-d H:i', strtotime($json->raceStartTime));
    echo '$start_time ' . $start_time . PHP_EOL;
    if ($start_time . ':00' != $race->start_time) {
        echo 'time changed : ' . $race->start_time . '->' . $start_time;
        $entry_no = 0;
        $type = '출발시각변경';
        $old_start_time = substr($race->start_time, 0, -3);
        $new_start_time = $start_time;

        $memo = $race->place_name . " " . $race->race_no . "경주: 출발시각변경" . ' ' . substr($old_start_time, -5) . ' => ' . substr($new_start_time, -5);
        echo $memo . PHP_EOL;
        $sql = "INSERT INTO `race_change_info` (`association_code`,`race_id`, `entry_no`, `type`, `memo`, `old_start_time`, `new_start_time`) SELECT '" . $race->association_code . "'," . $race->id . "," . $entry_no . ", '" . $type . "' , '" . $memo . "','" . $old_start_time . "','" . $new_start_time . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race_change_info` WHERE `race_id`= " . $race->id . " and  `entry_no` = " . $entry_no . " and  `type` = '" . $type . "' and  `memo` =  '" . $memo . "' and  `old_start_time` = '" . $old_start_time . "' and `new_start_time` = '" . $new_start_time . "')";
        echo $sql . PHP_EOL;
        $ok = insert_sql($sql);
        echo $ok . PHP_EOL;
    }

    // Update odds

    insert_wp_odds($json, $race);

    $url = 'https://api.beta.tab.com.au/v1/tab-info-service/racing/dates/' . $race->start_date . '/meetings/' . strtoupper(str_replace('os', '', $race->association_code)) . '/' . str_replace($race->association_code . '_', '', $race->place_code) . '/races/' . $race->race_no . '/pools/Quinella/approximates?jurisdiction=NSW';
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
    if (!$sucess) return FALSE;

    insert_qe_odds($json, $race, 'q', '복승');

    $url = 'https://api.beta.tab.com.au/v1/tab-info-service/racing/dates/' . $race->start_date . '/meetings/' . strtoupper(str_replace('os', '', $race->association_code)) . '/' . str_replace($race->association_code . '_', '', $race->place_code) . '/races/' . $race->race_no . '/pools/Exacta/approximates?jurisdiction=NSW';
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
    if (!$sucess) return FALSE;

    insert_qe_odds($json, $race, 'e', '쌍승');

    echo date("Y-m-d H:i:s") . ' end' . PHP_EOL;
}

function select_sql($sql)
{
    include __DIR__ . '/../../../application/configs/configdb.php';
    // $host = '127.0.0.1';
    // $user = 'aus';
    // $password = 'hellodhtm^^';
    // $dbname = 'goldrace';

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

// while (true) {
$sql = "SELECT r.id, r.association_code, r.place_name, r.place_code, r.start_date, r.start_time, r.race_no, p.name as place_name FROM race r left join place p on r.place_id = p.id WHERE start_time < date_add(now(), INTERVAL " . $interval . ") and start_time > date_add(now(), INTERVAL -3 minute) and r.association_code in ('osr','osh','osg') and r.stat = 'P' order by r.start_time asc;";
$race = select_sql($sql);
print_r($race);
// 	exit();
if ($race) {
    $start = date('Y-m-d H:i:s');
    echo $start . ' Work Start' . PHP_EOL;
    foreach ($race as $value) {
        get_race_data_to_json($value);
        sleep(5);
    }
    echo date('Y-m-d H:i:s') . ' Work OK!' . PHP_EOL;
} else {
    echo 'Nothing to do ...' . PHP_EOL;
}
// 	return 0;
// }