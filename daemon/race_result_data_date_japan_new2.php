<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// error_reporting(E_ALL);



function get_race_data_to_json($race)
{
    $type_code = array(
        '単勝' => '단승',
        '複勝' => '연승',
        '枠連複' => '화복승',
        '馬連複' => '복승',
        '馬連単' => '쌍승',
        'ワイド' => '복연승',
        '三連複' => '삼복승',
        '三連単' => '삼쌍승'
    );

    global $data;

    $place_own_id = $race->own_id;
    $date = $race->start_date;
    //$race_no = $race->race_no;

    //http://www2.keiba.go.jp/KeibaWeb/TodayRaceInfo/RefundMoneyList?k_raceDate=2018%2f06%2f03&k_babaCode=10
    $url = 'http://www2.keiba.go.jp/KeibaWeb/TodayRaceInfo/RefundMoneyList?k_raceDate=' . str_replace('-', '%2f', $date) . '&k_babaCode=' . $place_own_id;

    echo $url . PHP_EOL;
    $dom = new DomDocument();

    // 실행
    $dom->loadHtmlFile($url);
    //     $html = substr($dom->saveHTML(), - 6, 4);
    //     echo $html . PHP_EOL;
    //     if ($html !== 'html') {
    //         return false;
    //     }
    //     echo 'ok' . PHP_EOL;


    $xpath = new DomXPath($dom);
    //*[@id="mainContainer"]/article/table
    if ($xpath->query('//*[@id="mainContainer"]/article/table[@class="cover_error"]')[0]) {
        echo '결과없음' . PHP_EOL;
        return false;
    }
    // collect header names
    // $headerNames = ['own_id', 'rk_race_code', 'race_no', 'start_time', 'length', 'entry_count'];//,'race_class','title','result','result_table'];
    $headerNames = [
        'own_id',
        'start_date',
        'race_no',
        '단승',
        '연승',
        '복승',
        '쌍승',
        '복연승',
        '삼복승',
        '삼쌍승'
    ];
    //*[@id="mainContainer"]/article[1]/div/section

    $divs = $xpath->query('//*[@id="mainContainer"]/article[1]/div/section/div');

    foreach ($divs as $index => $div) {
        $rData = array();
        $rData['own_id'] = $place_own_id;
        $rData['start_date'] = $date;

        //         print_r($div);
        //*[@id="mainContainer"]/article[1]/div/section/div[1]/h4/p[1]
        $race_no = str_replace('R', '', $xpath->query('p[@class="roundNum"]', $div)[0]->textContent);
        $rData['race_no'] = $race_no;

        $priceArea = $xpath->query('div[@class="priceArea clearfix"]', $div)[0];
        $trs = $xpath->query('table//tr', $priceArea);
        $type = '';
        foreach ($trs as $index => $tr) {
            $th = $xpath->query('th', $tr);
            $tds = $xpath->query('td', $tr);

            $temp_type = $th[0]->textContent;
            if ($temp_type == '枠連複') {
                continue;
            }
            if ($temp_type != '') {
                $type = $temp_type;
            }
            //             print_r($type);
            //             print_r($type_code[$type]);

            $rData[$type_code[$type]][] = array($tds[0]->textContent, str_replace(',', '', $tds[1]->textContent) / 100);

            echo PHP_EOL;
        }
        //print_r($rData);
        $data[] = $rData;
    }
    //print_r($priceArea);


    //     print_r($divs);
    //     print_r($divs[0]);

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

$data = array();

$race_day = date('Y-m-d');
// SELECT race.id, race.start_date, race.start_time, race.race_no, place.own_id FROM race left join place on ( race.place_id = place.id) WHERE race.start_time < now() and race.start_date = '2018-06-03' and race.association_code = 'japanrace' and stat = 'P' group by place.own_id order by start_time asc;
$sql = "SELECT race.id, race.start_date, race.start_time, race.race_no, place.own_id FROM race left join place on ( race.place_id = place.id) WHERE race.start_time > now() and race.start_date = '" . $race_day . "' and race.association_code = 'japanrace' and stat = 'P' group by place.own_id order by start_time asc;";
//$sql = "SELECT race.id, race.start_date, race.start_time, race.race_no, place.own_id FROM race left join place on ( race.place_id = place.id) WHERE race.start_time < now() and race.start_date = '2018-06-03' and race.association_code = 'japanrace' and stat = 'E' group by place.own_id order by start_time asc;";

$races = select_sql($sql);
print_r($races);

if ($races) {
    foreach ($races as $race) {
        // print_r($alink);
        // $url = 'http://www2.keiba.go.jp' . $alink->value;
        get_race_data_to_json($race);
    }
} else {
    echo 'Done' . PHP_EOL;
    exit();
}

print_r($data);


// exit();
// sql 경주결과 삽입
// print_r($data);
// $sql = 'INSERT INTO `goldrace`.`result` (`race_id`, `type`, `place_1`, `place_2`, `place_3`, `odds`) VALUES (1, \'복승\', 1, 2, 3, 50);';
// $sql_race_id = 'SELECT id FROM `race` WHERE date(start_time) = date(\'2016-04-18\') and (SELECT id from place where own_id = \'11\') = place_id and race_no = \'1\'';
$msg = array();
$headerNames = [
    'own_id',
    'start_date',
    'race_no',
    'reg_result'
];

foreach ($data as $i => $r) {
    //print_r($r);
    $own_id = $r['own_id'];
    $start_date = $r['start_date'];
    $race_no = $r['race_no'];
    $sql = 'SELECT id FROM `race` WHERE date(start_time) = date(' . '\'' . $start_date . '\') and (SELECT id from place where own_id = \'' . $own_id . '\') = place_id and race_no = \'' . $race_no . '\'';

    $v = select_sql($sql);
    // print_r( $v[0]->id);
    $race_id = $v[0]->id;
    // print_r($race_id);
    // print_r($r);

    $sql = 'SELECT stat FROM `race` WHERE id = ' . $race_id . ' LIMIT 1';
    // print_r($sql);
    $stat = select_sql($sql);
    //     echo $race_id . '->' . $stat[0]->stat;
    // if 문 레이스아디로 레이스의 상태 체크 - 취소/완료 아닐시 아래 진행
    if ($stat[0]->stat == 'E' || $stat[0]->stat == 'C') {
        echo '등록통과' . PHP_EOL;
        continue;
    }
    echo '등록진행' . PHP_EOL;
    if (count($r['단승']) === 0 && count($r['복승']) === 0 && count($r['쌍승']) === 0) {
        // $entry_no = 0;
        // $type = '경주취소';
        // $old_start_time = date( "Y-m-d " );
        // $new_start_time = date( "Y-m-d " );

        // $sql = "SELECT name from place where own_id = '" . $own_id . "'";
        // //print_r($sql);
        // $place = select_sql( $sql );
        // $memo = $place[ 0 ]->name . " " . $race_no . "경주: 경주취소";

        // $sql = "INSERT INTO `race_change_info` (`race_id`, `entry_no`, `type`, `memo`, `old_start_time`, `new_start_time`) SELECT " . $race_id . "," . $entry_no . ", '" . $type . "' , '" . $memo . "','" . $old_start_time . "','" . $new_start_time . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race_change_info` WHERE `race_id`= " . $race_id . " and `entry_no` = " . $entry_no . " and `type` = '" . $type . "' and `memo` = '" . $memo . "' and `old_start_time` = '" . $old_start_time . "' and `new_start_time` = '" . $new_start_time . "')";
        // //echo $sql;

        // $ok = insert_sql( $sql );
        // echo $ok;
        continue;
    }

    $k = -1;
    foreach ($r as $key => $d) {
        // echo $k;
        $k++;
        if ($k < 3) {
            continue;
        }
        $type = $key;
        // print_r($type);
        foreach ($d as $c) {
            $p = explode('-', $c[0]);
            $place_1 = $p[0];
            $p[1] === null ? $place_2 = 0 : $place_2 = $p[1];
            $p[2] === null ? $place_3 = 0 : $place_3 = $p[2];
            $b = $c[1];
            if ($b > 100) {
                $b = 100;
            }
            // echo $type . $place_1 . $place_2 .$place_3;
            // $sql = 'INSERT INTO `result` (`race_id`, `type`, `place_1`, `place_2`, `place_3`, `odds`) VALUES (' . $race_id . ', \'' . $type . '\' , ' . $place_1 . ',' . $place_2 . ',' . $place_3 . ',' . $b . ')';
            $sql = 'INSERT INTO `result` (`race_id`, `type`, `place_1`, `place_2`, `place_3`, `odds`) SELECT ' . $race_id . ', \'' . $type . '\' , ' . $place_1 . ',' . $place_2 . ',' . $place_3 . ',' . $b . ' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `result` WHERE `race_id`= ' . $race_id . ' and  `type` = \'' . $type . '\' and  `place_1` = ' . $place_1 . ' and  `place_2` = ' . $place_2 . ' and `place_3` = ' . $place_3 . ')';
            // print_r($sql);
            // echo '<br>';

            $ok = insert_sql($sql);
        }
        // echo $k;
        // print_r($d);
    }
    // race stat 'E' 완료로 변경
    $sql = "SELECT * FROM `result` WHERE type='삼복승' and race_id = " . $race_id;
    $results = select_sql($sql);
    // print_r($results);
    if (count($results) > 0) {
        $sql = "SELECT * FROM `view_place_result` WHERE race_id = " . $race_id;
        $results = select_sql($sql);
        // print_r($results);
        // print_r($results[0]->race_id);
        // print_r('<br>p1='.$results[0]->place_1);
        // foreach(explode(' ',$results[0]->place_1) as $p){
        $oe = 0;
        $p1 = explode(' ', $results[0]->place_1);
        //print_r($p1);
        $oe += array_sum($p1);

        $p1c = count($p1);
        sort($p1, SORT_NUMERIC);
        $p1 = implode(' ', $p1);
        //         echo '<br>race_id=' . $results[0]->race_id;
        //         echo '<br>p1=' . $p1;
        //         echo 'p1c=' . $p1c;

        if ($p1c < 2) {
            $p2 = explode(' ', $results[0]->place_2);
            $oe += array_sum($p2);
            $p2c = count($p2);
            sort($p2, SORT_NUMERIC);
            $p2 = implode(' ', $p2);
        } else {
            $p2 = '';
            $p2c = 0;
        }
        //         echo 'p2=' . $p2;
        //         echo 'p2c=' . $p2c;
        if ($p2c + $p2c > 3) {
            $p3 = '';
        } else {
            $p3 = explode(' ', $results[0]->place_3);
            $p3 = array_unique($p3);
            unset($p3[array_search('', $p3)]);
            //             print_r($p3);
            $oe += array_sum($p3);
            sort($p3, SORT_NUMERIC);
            $p3 = implode(' ', $p3);
        }
        //         echo 'p3=' . $p3;
        //         echo '$oe=' . $oe;

        $oe %= 2;
        if ($oe === 0) {
            $oe += 2;
        }

        $sql = "INSERT INTO `result` (`race_id`, `type`, `place_1`, `place_2`, `place_3`, `odds`) SELECT " . $race_id . ", '홀짝' , " . $oe . ",0,0,0 FROM DUAL WHERE NOT EXISTS (SELECT * FROM `result` WHERE `race_id`= " . $race_id . " and  `type` = '홀짝' and  `place_1` = " . $oe . " and  `place_2` = 0 and `place_3` = 0)";
        // print_r($sql);
        // echo '<br>';
        $ok = insert_sql($sql);

        $odds_dan = $results[0]->dan;
        $odds_yun = $results[0]->yun;
        $odds_bok = $results[0]->bok;
        $odds_ssang = $results[0]->ssang;
        $odds_bokyun = $results[0]->bokyun;
        $odds_sambok = $results[0]->sambok;
        $odds_samssang = $results[0]->samssang;
        /*
         * echo '<br>$odds_dan' . $odds_dan;
         * echo '<br>$odds_yun' . $odds_yun;
         * echo '<br>$odds_bok' . $odds_bok;
         * echo '<br>$odds_ssang' . $odds_ssang;
         * echo '<br>$odds_bokyun' . $odds_bokyun;
         * echo '<br>$odds_sambok' . $odds_sambok;
         * echo '<br>$odds_samssang' . $odds_samssang;
         */

        $sql = "select group_concat(DISTINCT `type`,':',`result` separator ' ') as `odds_all` from `view_result` where race_id = " . $race_id;
        $results = select_sql($sql);
        $odds_all = $results[0]->odds_all;

        $sql = "UPDATE `race` SET `stat`='E', place_1 = '" . $p1 . "', place_2 = '" . $p2 . "', place_3 = '" . $p3 . "', place_oe = '" . $oe . "',	odds_dan = '" . $odds_dan . "',	odds_yun = '" . $odds_yun . "',	odds_bok = '" . $odds_bok . "',	odds_ssang = '" . $odds_ssang . "',	odds_bokyun = '" . $odds_bokyun . "',	odds_sambok = '" . $odds_sambok . "',	odds_samssang = '" . $odds_samssang . "',	odds_all = '" . $odds_all . "' WHERE stat = 'P' and `id`= " . $race_id;
        //         print_r($sql);
        $ok = insert_sql($sql);
    }
    // $msg = $own_id . ',' . $start_date . ',' . $race_no . ' reg result : ' . $ok . '<br>';
    $rmsg = [
        $own_id,
        $start_date,
        $race_no,
        $ok
    ];
    $msg[] = array_combine($headerNames, $rmsg);
}
// $msg[] = ['', '', '', 'success'] ;

// $msg[] = array_combine($headerNames, [
//     '',
//     '',
//     '',
//     'success'
// ]);
print_r($msg);
