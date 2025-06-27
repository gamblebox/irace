<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require __DIR__ . "/../../../vendor/autoload.php";
// error_reporting(E_ALL);

/**
 * Undocumented function
 *
 * @param [object] $race
 * @return void
 */
function get_result($race)
{
    print_r($race);

    //nb uoline
    $cancel_entry_no_arr = explode(',', $race->cancel_entry_no);
    print_r($cancel_entry_no_arr);

    $entry_arr = array();
    for ($i = 1; $i < $race->entry_count + 1; $i++) {
        if (array_search((string)$i, $cancel_entry_no_arr) === false) {
            array_push($entry_arr, $i);
        }
    }
    print_r($entry_arr);
    $under_min_entry_count = false;
    if (count($entry_arr) < 7) {
        $under_min_entry_count = true;
    }

    echo '$permutations = new drupol\phpermutations\Generators\Permutations($entry_arr, 5)';
    $permutations = new drupol\phpermutations\Generators\Combinations($entry_arr, 5);
    // 	print_r($permutations);
    $permutation_sum = 0;
    foreach ($permutations->generator() as $permutation) {
        print_r($permutation);
        $permutation_sum += array_sum($permutation);
    }
    $uo_line = ceil($permutation_sum / $permutations->count()) - 0.5;
    echo $uo_line . PHP_EOL;

    // http://race.kra.co.kr/raceScore/ScoretableDetailList.do?meet=3&realRcDate=20190705&realRcNo=1&Act=04&Sub=1
    $url = 'http://race.kra.co.kr/raceScore/ScoretableDetailList.do?meet=' . ($race->own_id - 100) . '&realRcDate=' .
        str_replace('-', '', $race->start_date) . '&realRcNo=' . $race->race_no . '&Act' . '04' . '&Sub=' . 1;
    // 	$url = 'http://race.kra.co.kr/raceScore/ScoretableDetailList.do?meet=3&realRcDate=20190705&realRcNo=1&Act=04&Sub=1';
    echo $url . PHP_EOL;
    $dom = new DomDocument();

    // 실행
    $dom->loadHtmlFile($url);
    $html = substr($dom->saveHTML(), -6, 4);
    echo $html . PHP_EOL;
    if ($html !== 'html') {
        return false;
    }
    echo 'ok' . PHP_EOL;

    $xpath = new DomXPath($dom);
    // *[@id="contents"]/div[6]/table/tbody/tr[1]
    $trs = $xpath->query('//*[@id="contents"]/div[6]/table/tbody/tr');

    echo 'trs' . PHP_EOL;
    print_r($trs);
    if (count($trs) < $race->entry_count) {
        echo 'no html';
        return;
    }
    $rowData = array();
    $pow_count = 0;
    $pow_no = 0;
    $over_count = false;
    $under_count = false;
    foreach ($trs as $index => $node) {
        $cells = $xpath->query('td', $node);
        $place = strip_tags($dom->saveHTML($cells[0]));
        echo '$place' . $place . PHP_EOL;
        $entry_no = strip_tags($dom->saveHTML($cells[1]));
        if ($index == 0 && $place != '1') {
            return false;
        }
        if ($place == 5) {
            $pow_count += 1;
            $pow_no = $entry_no;
        }
        if ($index < 5) {
            if ($place == '') {
                echo '$place-stop-' . $place . PHP_EOL;
                $under_count = true;
                break;
            }
            $rowData[] = $entry_no;
        } else {
            if ($place != '' && $place < 6) {
                $over_count = true;
            }
            break;
        }
    }
    // 	print_r($rowData);

    if ($pow_count != 1) {
        $pow_no = 0;
    }
    $sum = array_sum($rowData);

    // 	$p_uo_line = ceil($race->entry_count / 2) - 0.5;
    $p_uo_line = ceil(array_sum($entry_arr) / count($entry_arr)) - 0.5;
    // 	$uo_line = ceil(serial_sum($race->entry_count) / 2) - 0.5;

    $oe = $sum % 2;

    if ($sum < $uo_line) {
        $uo = 1;
    } else {
        $uo = 0;
    }
    if ($pow_no) {
        $p_oe = $pow_no % 2;
        if ($pow_no < $p_uo_line) {
            $p_uo = 1;
        } else {
            $p_uo = 0;
        }
    }

    $results_set = '';
    if ($pow_no) {
        if ($p_oe) {
            $results_set .= '10';
        } else {
            $results_set .= '01';
        }
        if ($p_uo) {
            $results_set .= '10';
        } else {
            $results_set .= '01';
        }
    } else {
        $results_set = '0000';
    }

    if ($oe) {
        $results_set .= '10';
    } else {
        $results_set .= '01';
    }
    if ($uo) {
        $results_set .= '10';
    } else {
        $results_set .= '01';
    }
    if ($race->entry_count > 6) {
        if (!$over_count && !$under_count && !$under_min_entry_count) {
            $sql = "INSERT INTO `pb_result` (`pb_race_id`, `pb_place_1`, `pb_place_2`, `pb_place_3`, `pb_place_4`, `pb_place_5`, `pb_place_p`, `pb_place_sum`, `pb_place_oe`, `pb_place_uo`, `pb_place_p_oe`, `pb_place_p_uo`, `pb_place_uo_line`, `pb_place_p_uo_line`, `pb_results_set`, `pb_stat`) SELECT " .
                $race->id . ", " . $rowData[0] . ", " . $rowData[1] . ", " . $rowData[2] . ", " . $rowData[3] . ", " . $rowData[4] . ", " . $pow_no . ", " . $sum . ", " . $oe . ", " . $uo . ", " . $p_oe . ", " . $p_uo . ", " . $uo_line . ", " . $p_uo_line .
                ", " . base_convert($results_set, 2, 10) .
                ", 'E' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `pb_result` WHERE `pb_race_id`= " . $race->id . ")";
        } else {
            $sql = "INSERT INTO `pb_result` (`pb_race_id`, `pb_stat`) SELECT " . $race->id .
                ", 'R' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `pb_result` WHERE `pb_race_id`= " . $race->id . ")";
        }

        print_r($sql);
        $ok = insert_sql($sql);
        echo PHP_EOL . $ok . PHP_EOL;
    }
    // 임시 착순
    echo $race->id . PHP_EOL;
    //$sql = "UPDATE `race` SET `stat`='E', place_1 = '" . $rowData[0] . "', place_2 = '" . $rowData[1] . "', place_3 = '" . $rowData[2] . "' WHERE `id`= " . $race->id;
    $sql = "UPDATE `race` SET place_1 = '" . $rowData[0] . "', place_2 = '" . $rowData[1] . "', place_3 = '" . $rowData[2] . "' WHERE `id`= " . $race->id;
    print_r($sql);
    echo PHP_EOL;
    $ok = insert_sql($sql);

    // 	exit();
}

function serial_sum($end)
{
    $start = 1;
    $sum = 0;
    do {
        $sum = $sum + $start;
        $start++;
    } while ($start <= $end);
    return $sum;
}

function select_sql($sql)
{
    include(__DIR__ . '/../../../application/configs/configdb.php');

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
    include(__DIR__ . '/../../../application/configs/configdb.php');

    $mysqli = new mysqli($host, $user, $password, $dbname);
    // 연결 오류 발생 시 스크립트 종료
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

$race_day = date('Y-m-d');
// SELECT * FROM race join place on ( race.place_id = place.id) WHERE race.start_time < now() and race.start_date = '2018-06-01' and race.association_code = 'japanrace' and stat = 'P' order by start_time asc;
$sql = "SELECT race.id, race.start_date, race.race_no, race.entry_count, race.cancel_entry_no, place.own_id FROM race left join place on ( race.place_id = place.id) left join pb_result on ( race.id = pb_result.pb_race_id) WHERE DATE_ADD(race.start_time,INTERVAL 5 MINUTE) < now() and race.start_date = '" . $race_day . "' and race.association_code = 'race' and pb_result.pb_stat IS null order by start_time asc;";

// $sql = "SELECT race.id, race.start_date, race.race_no, race.entry_count, place.own_id FROM race left join place on ( race.place_id = place.id) left join pb_result on ( race.id = pb_result.pb_race_id) WHERE race.start_time < now() and race.start_date = '2019-07-05' and race.association_code = 'race' and pb_result.pb_stat IS null order by start_time asc;";

$races = select_sql($sql);
// print_r($races);
// exit();
if ($races) {
    foreach ($races as $race) {
        get_result($race);
    }
} else {
    echo 'Done' . PHP_EOL;
    exit();
}
exit();
