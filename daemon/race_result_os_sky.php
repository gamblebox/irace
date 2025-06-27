<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header("Content-Type:application/json");
header("Content-Type:text/html;charset=UTF-8");
require("/srv/krace/vendor/autoload.php");
//require ( __DIR__ . "/../../../vendor/autoload.php");
//include_once( __DIR__ . '/../../application/configs/configdb.php' );
use Sunra\PhpSimple\HtmlDomParser;
use Browser\Casper;
//include('simple_html_dom.php');
echo date("Y-m-d H:i:s") . PHP_EOL;

function get_race_data_to_json($url, $race_id, $place_code)
{
    echo date("Y-m-d H:i:s") . 'get func' . PHP_EOL;
    global $data;

    $casper = new Casper();
    // May need to set more options due to ssl issues
    $casper->setOptions(array(
        'ignore-ssl-errors' => 'yes',
        'loadImages' => 'false'
    ));
    $casper->start($url);
    $casper->waitForText('</noscript>', 1000);
    $casper->run();

    echo date("Y-m-d H:i:s") . 'get func run' . PHP_EOL;

    $html = $casper->getHtml();
    $dom = HtmlDomParser::str_get_html($html);

    $stat = $dom->find('div.race-info-wrapper li.status-text', 0)->plaintext;
    echo $stat;
    if ($stat == 'Abandoned') {
        $entry_no = 0;
        $type = '경주취소';
        $old_start_time = date("Y-m-d ");
        $new_start_time = date("Y-m-d ");

        $sql = "SELECT name from place where place_code = '" . $place_code . "'";
        //print_r($sql);
        $place = select_sql($sql);
        $memo = $place[0]->name . " " . $race_no . "경주: 경주취소";

        $sql = "INSERT INTO `race_change_info` (`race_id`, `entry_no`, `type`, `memo`, `old_start_time`, `new_start_time`) SELECT " . $race_id . "," . $entry_no . ", '" . $type . "' , '" . $memo . "','" . $old_start_time . "','" . $new_start_time . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race_change_info` WHERE `race_id`= " . $race_id . " and  `entry_no` = " . $entry_no . " and  `type` = '" . $type . "' and  `memo` =  '" . $memo . "' and  `old_start_time` = '" . $old_start_time . "' and `new_start_time` = '" . $new_start_time . "')";
        //echo $sql;

        $ok = insert_sql($sql);
        return true;
    } else if ($stat != 'All Paying') {
        return false;
    }
    $place1 = '';
    $place2 = '';
    $place3 = '';
    $dan = array();
    $yun = array();
    $tables = $dom->find('div.race-results-wrapper section.runners-section table');
    $trs = $tables[0]->find('tbody tr');
    foreach ($trs as $key => $value) {
        $place = $value->find('td.result-position', 0)->plaintext[0];
        //echo $place . PHP_EOL;
        $entry_no = $value->find('td.runner-details', 0)->plaintext[0];
        //echo $entry_no . PHP_EOL;
        $dan_ratio = str_replace('$', '', $value->find('div.result-win', 0)->plaintext);
        //echo $dan_ratio . PHP_EOL;
        $yun_ratio = str_replace('$', '', $value->find('div.result-place', 0)->plaintext);
        //echo $yun_ratio . PHP_EOL;
        if ($place == 1) {
            $place1 .= ' ' . $entry_no;
            $dan[] = array($entry_no, $dan_ratio);
            if ($yun_ratio) {
                $yun[] = array($entry_no, $yun_ratio);
            }
        } else if ($place == 2) {
            $place2 .= ' ' . $entry_no;
            if ($yun_ratio) {
                $yun[] = array($entry_no, $yun_ratio);
            }
        } else if ($place == 3) {
            $place3 .= ' ' . $entry_no;
            if ($yun_ratio) {
                $yun[] = array($entry_no, $yun_ratio);
            }
        }
    }
    trim($place1);
    trim($place2);
    trim($place3);
    print_r($place1);
    print_r($place2);
    print_r($place3);
    print_r($dan);
    print_r($yun);

    $bok = array();
    $ssang = array();
    $samssang = array();
    $bokyun = array();
    $sassang = array();
    $trs = $tables[1]->find('tbody tr');
    foreach ($trs as $key => $value) {
        $type = trim($value->find('td.ng-binding', 0)->plaintext);
        //echo $type . PHP_EOL;
        $select_no = $value->find('div.result-pool-name', 0)->plaintext;
        //echo $select_no . PHP_EOL;
        $type_ratio = str_replace('$', '', $value->find('div.result-pool-odds', 0)->plaintext);
        //echo $type_ratio . PHP_EOL;

        switch ($type) {
            case 'Quinella':
                $bok[] = array($select_no, $type_ratio);
                break;
            case 'Exacta':
                $ssang[] = array($select_no, $type_ratio);
                break;
            case 'Trifecta':
                $samssang[] = array($select_no, $type_ratio);
                break;
            case 'Duet':
                $bokyun[] = array($select_no, $type_ratio);
                break;
            case 'First Four':
                $sassang[] = array($select_no, $type_ratio);
                break;
        }
    }
    print_r($bok);
    print_r($ssang);
    print_r($samssang);
    print_r($bokyun);
    print_r($sassang);

    $rowData = array($dan, $yun, $bok, $ssang, $bokyun, $sambok, $samssang, $sassang);
    $headerNames = ['단승', '연승', '복승', '쌍승', '복연승', '삼복승', '삼쌍승', '사쌍승'];

    $data = array_combine($headerNames, $rowData);

    echo date("Y-m-d H:i:s") . 'end' . PHP_EOL;
    print_r($data);

    foreach ($data as $key => $d) {
        $type = $key;
        print_r($d);
        foreach ($d as $c) {
            print_r($c);
            $p = explode('-', $c[0]);
            $place_1 = $p[0];
            $p[1] === null ? $place_2 = 0 : $place_2 = $p[1];
            $p[2] === null ? $place_3 = 0 : $place_3 = $p[2];
            $b = $c[1];
            if ($b > 100) {
                $b = 100;
            }
            //echo $type .  $place_1 . $place_2 .$place_3;
            //$sql = 'INSERT INTO `result` (`race_id`, `type`, `place_1`, `place_2`, `place_3`, `odds`) VALUES (' . $race_id .  ', \'' . $type . '\' , ' . $place_1 .  ','  . $place_2 .  ','  .  $place_3 .  ',' . $b . ')';
            $sql = 'INSERT INTO `result` (`race_id`, `type`, `place_1`, `place_2`, `place_3`, `odds`) SELECT ' . $race_id . ', \'' . $type . '\' , ' . $place_1 . ',' . $place_2 . ',' . $place_3 . ',' . $b . ' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `result` WHERE `race_id`= ' . $race_id . ' and  `type` = \'' . $type . '\' and  `place_1` = ' . $place_1 . ' and  `place_2` = ' . $place_2 . ' and `place_3` = ' . $place_3 . ')';
            echo $sql;
            //print_r($sql);
            //echo '<br>';

            $ok = insert_sql($sql);
        }
        echo $i . ':' . $ok . PHP_EOL;
    }



    //$sql = "SELECT * FROM `view_place_result` WHERE race_id = " . $race_id;
    //$results = select_sql( $sql );
    //print_r($results);
    //print_r($results[0]->race_id);
    //print_r('<br>p1='.$results[0]->place_1);
    //foreach(explode(' ',$results[0]->place_1) as $p){
    $place1 = trim($place1);
    $place2 = trim($place2);
    $place3 = trim($place3);
    $oe = 0;
    $p1 = explode(' ', $place1);
    print_r($p1);

    $oe += array_sum($p1);

    $p1c = count($p1);
    sort($p1, SORT_NUMERIC);
    $p1 = implode(' ', $p1);
    echo 'race_id=' . $race_id;
    echo 'p1=' . $p1;
    echo 'p1c=' . $p1c;

    if ($p1c < 2) {
        $p2 = explode(' ', $place2);
        $oe += array_sum($p2);
        $p2c = count($p2);
        sort($p2, SORT_NUMERIC);
        $p2 = implode(' ', $p2);
    } else {
        $p2 = '';
        $p2c = 0;
    }
    echo 'p2=' . $p2;
    echo 'p2c=' . $p2c;
    if ($p2c + $p2c > 3) {
        $p3 = '';
    } else {
        $p3 = $place3;
    }
    echo 'p3=' . $p3;
    echo '$oe=' . $oe;


    $oe %= 2;
    if ($oe === 0) {
        $oe += 2;
    }

    $sql = "INSERT INTO `result` (`race_id`, `type`, `place_1`, `place_2`, `place_3`, `odds`) SELECT " . $race_id . ", '홀짝' , " . $oe . ",0,0,0 FROM DUAL WHERE NOT EXISTS (SELECT * FROM `result` WHERE `race_id`= " . $race_id . " and  `type` = '홀짝' and  `place_1` = " . $oe . " and  `place_2` = 0 and `place_3` = 0)";
    //print_r($sql);
    //echo '<br>';
    $ok = insert_sql($sql);

    foreach ($dan as $i => $v) {
        $odds_dan .= ' ' . $v[1];
    }
    $odds_dan = trim($odds_dan);
    foreach ($yun as $i => $v) {
        $odds_yun .= ' ' . $v[1];
    }
    $odds_yun = trim($odds_yun);
    foreach ($bok as $i => $v) {
        $odds_bok .= ' ' . $v[1];
    }
    $odds_bok = trim($odds_bok);
    foreach ($ssang as $i => $v) {
        $odds_ssang .= ' ' . $v[1];
    }
    $odds_ssang = trim($odds_ssang);
    foreach ($bokyun as $i => $v) {
        $odds_bokyun .= ' ' . $v[1];
    }
    $odds_bokyun = trim($odds_bokyun);
    foreach ($sambok as $i => $v) {
        $odds_sambok .= ' ' . $v[1];
    }
    $odds_sambok = trim($odds_sambok);
    foreach ($samssang as $i => $v) {
        $odds_samssang .= ' ' . $v[1];
    }
    $odds_samssang = trim($odds_samssang);

    /*      echo '<br>$odds_dan' . $odds_dan;
         echo '<br>$odds_yun' . $odds_yun;
         echo '<br>$odds_bok' . $odds_bok;
         echo '<br>$odds_ssang' . $odds_ssang;
         echo '<br>$odds_bokyun' . $odds_bokyun;
         echo '<br>$odds_sambok' . $odds_sambok;
         echo '<br>$odds_samssang' . $odds_samssang;*/

    $sql = "select group_concat(DISTINCT `type`,':',`result` separator ' ') as `odds_all` from `view_result` where race_id = " . $race_id;
    $results = select_sql($sql);
    $odds_all = $results[0]->odds_all;

    $sql = "UPDATE `race` SET `stat`='E', place_1 = '" . $p1 . "', place_2 = '" . $p2 . "', place_3 = '" . $p3 . "', place_oe = '" . $oe . "', odds_dan = '" . $odds_dan . "', odds_yun = '" . $odds_yun . "', odds_bok = '" . $odds_bok . "', odds_ssang = '" . $odds_ssang . "', odds_bokyun = '" . $odds_bokyun . "',   odds_sambok = '" . $odds_sambok . "',   odds_samssang = '" . $odds_samssang . "',   odds_all = '" . $odds_all . "' WHERE  `id`= " . $race_id;
    print_r($sql);
    $ok = insert_sql($sql);
}

function select_sql($sql)
{
    //include (__DIR__ . '/../../../application/configs/configdb.php');
    $host = 'k3.krace.fun';
    $user = 'aus';
    $password = 'hellodhtm^^';
    $dbname = 'goldrace';

    $mysqli = new mysqli($host, $user, $password, $dbname);
    // 연결 오류 발생 시 스크립트 종료
    if ($mysqli->connect_errno) {
        die('Connect Error: ' . $mysqli->connect_error);
    }

    if ($result = $mysqli->query($sql)) {
        // 레코드 출력
        $v = array();
        while ($row = mysqli_fetch_object($result)) {
            //print_r( $row->id);
            $v[] = $row;
        }
    } else {
        $v = array(0 => 'empty');
    }
    return $v;

    $result->free(); //메모리해제

}

function insert_sql($sql)
{
    //include (__DIR__ . '/../../../application/configs/configdb.php');
    $host = 'k3.krace.fun';
    $user = 'aus';
    $password = 'hellodhtm^^';
    $dbname = 'goldrace';
    $mysqli = new mysqli($host, $user, $password, $dbname);    // 연결 오류 발생 시 스크립트 종료

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


//$date_url = 'https://www.tab.com.au/racing/meetings/tomorrow';
$date_url = array('https://www.tab.com.au/racing/meetings/today/R', 'https://www.tab.com.au/racing/meetings/today/H', 'https://www.tab.com.au/racing/meetings/today/G');
$date_url = 'https://www.tab.com.au/racing/meetings/today/R';

$sql = "SELECT * FROM race WHERE race.start_time < now() and race.association_code in ('osr','osh','osg') and stat = 'P';";
$race = select_sql($sql);
//print_r($race);

foreach ($race as $v) {
    $url = 'https://www.tab.com.au/racing/' . $v->start_date . '/' . $v->place_name . '/' . str_replace($v->association_code . '_', '', $v->place_code) . '/' . strtoupper(str_replace('os', '', $v->association_code)) .  '/' . $v->race_no;
    echo $url . PHP_EOL;
    get_race_data_to_json($url, $v->id, $v->place_code);
}
exit;


// print_r($data);
// $date = substr($url, -18, 8 );
// $date = preg_replace("/([\d]{4})([\d]{2})/", "$1-$2-$3", $get_date);
// for ($i = 0; $i < count($data); $i ++) {
//     $data[$i]['start_time'] = $date . ' ' . $data[$i]['start_time'] . ':00';
//     $data[$i]['length'] = preg_replace('/[^0-9]*/s', '', $data[$i]['length']);
// }
// echo json_encode($data, JSON_UNESCAPED_UNICODE);

// insert
// INSERT INTO `race` (`place_id`, `rk_race_code`, `race_no`, `start_time`, `race_length`, `entry_count`) SELECT (select `id` from `place` where `own_id`=23),200301,10,'2016-05-09 16:40:00',1400,8 FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race` WHERE `place_id`= (select `id` from `place` where `own_id`=23) and `race_no` = 10 and day(`start_time`) = day('2016-05-09 16:40:00') )
// echo PHP_EOL;
//echo json_encode($data, JSON_UNESCAPED_UNICODE);
foreach ($data as $i => $r) {

    $sql = "INSERT INTO `race` (`place_id`, `place_name`, `association_code`, `place_code`, `race_no`, `start_time`, `race_length`, `entry_count`) SELECT (select `id` from `place` where `place_code`= '" . $r['place_code'] . "'), '" . $r['place_name'] . "', '" . $r['association_code'] . "', '" . $r['place_code'] . "'," . $r['race_no'] . ",'" . $r['start_time'] . "'," . $r['length'] . "," . $r['entry_count'] . " FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race` WHERE `place_id`= (select `id` from `place` where `place_code`= '" . $r['place_code'] . "') and `place_name` = '" . $r['place_name'] . "' and `place_code` = '" . $r['place_code'] . "' and `race_no` = " . $r['race_no'] . ")";
    echo $sql;
    $ok = insert_sql($sql);
    echo $i . ':' . $ok . PHP_EOL;
}
// print_r($sql);
