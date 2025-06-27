<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
// require __DIR__ . "/../../../vendor/autoload.php";
require "/srv/krace/vendor/autoload.php";

use Browser\Casper;
// use Sunra\PhpSimple\HtmlDomParser;
use voku\helper\HtmlDomParser;


$url = 'http://keirin.jp/pc/top#';

function get_race_day()
{
    echo date("Y-m-d H:i:s") . PHP_EOL;

    $today = date('Y-m-d');
    $race_day = $today;
    // echo '$race_day ' . $race_day . PHP_EOL;
    $now_time = date("H");
    // echo '$now_time ' . $now_time . PHP_EOL;
    if ($now_time < 2) {
        $race_day = date("Y-m-d", strtotime($race_day . " -1 day"));
    }
    // echo '$race_day ' . $race_day . PHP_EOL;
    return $race_day;
}

function get_race_result($url, $race)
{
    //https://boatrace.jp/owpc/pc/race/raceresult?rno=1&jcd=06&hd=20200927
    echo date("Y-m-d H:i:s") . 'get func' . PHP_EOL;
    $race_day = date('Ymd');
    $data = array();
    $url = 'https://boatrace.jp/owpc/pc/race/raceresult?rno=' . $race->race_no . '&jcd=' . str_replace('jb_', '', $race->place_code) . '&hd=' . $race_day;
    echo '$url ' . $url . PHP_EOL;

    $html = file_get_contents($url);
    // echo '$html ' . $html . PHP_EOL;

    $dom = HtmlDomParser::str_get_html($html);
    // $dom = HtmlDomParser::file_get_html($url);
    $divs = $dom->find('div.table1');
    foreach ($divs as $key => $div) {
        if ($div->findOne('table.is-w495 thead th')->textContent == '勝式') {
            $table = $div->findOne('table.is-w495');
            // print_r($table);
            // exit();
        }
    }
    if (!$table) {
        return;
    }

    // print_r($table);
    // exit();
    $trs = $table->find('tbody > tr');
    // print_r($trs);

    // exit();

    $jtype2type = array(
        '2連複' => '복승',
        '2連単' => '쌍승',
        '3連複' => '삼복승',
        '3連単' => '삼쌍승',
        '拡連複' => '복연승',
    );

    $type = '';
    foreach ($trs as $key => $tr) {
        $tds = $tr->find('td');

        print_r($tds);
        // exit();
        if (count($tds) > 3) {
            $type =  $jtype2type[$tds[0]->textContent];
            if (trim($tds[1]->textContent) == '不成立') {
                continue;
            }
            $span = $tds[2]->findOne('span');
            $odds = str_replace(array('&yen;', ','), '', $span->textContent) / 100;
            $odds = $odds > 100 ? 100 : $odds;

            $select = array();
            $spans = $tds[1]->find('span.numberSet1_number');
            if (count($spans) < 1) {
                continue;
            }
            foreach ($spans as $key => $span) {
                $select[] = $span->textContent;
            }
            // print_r($select);
            // exit();

            switch ($type) {
                case '복승':
                    $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $select[0], 'place_2' => $select[1], 'place_3' => 0, 'odds' => $odds);
                    $odds_bok = $odds;
                    break;
                case '쌍승':
                    $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $select[0], 'place_2' => $select[1], 'place_3' => 0, 'odds' => $odds);
                    $odds_ssang = $odds;
                    break;
                case '삼복승':
                    $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $select[0], 'place_2' => $select[1], 'place_3' => $select[2], 'odds' => $odds);
                    $odds_sambok = $odds;
                    break;
                case '삼쌍승':
                    $place = $select;
                    $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $select[0], 'place_2' => $select[1], 'place_3' => $select[2], 'odds' => $odds);
                    $odds_samssang = $odds;
                    break;
                case '복연승':
                    $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $select[0], 'place_2' => $select[1], 'place_3' => 0, 'odds' => $odds);
                    $odds_bokyun = $odds;
                    break;
                default:
                    continue;
                    break;
            }
        } else if (count($tds) == 3) {

            $span = $tds[1]->findOne('span');
            $odds = str_replace(array('&yen;', ','), '', $span->textContent) / 100;
            $odds = $odds > 100 ? 100 : $odds;

            $select = array();
            $spans = $tds[0]->find('span.numberSet1_number');
            if (count($spans) < 1) {
                continue;
            }
            foreach ($spans as $key => $span) {
                $select[] = $span->textContent;
            }
            switch ($type) {
                case '복승':
                    $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $select[0], 'place_2' => $select[1], 'place_3' => 0, 'odds' => $odds);
                    $odds_bok .= ' ' . $odds;
                    break;
                case '쌍승':
                    $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $select[0], 'place_2' => $select[1], 'place_3' => 0, 'odds' => $odds);
                    $odds_ssang .= ' ' . $odds;
                    break;
                case '삼복승':
                    $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $select[0], 'place_2' => $select[1], 'place_3' => $select[2], 'odds' => $odds);
                    $odds_sambok .= ' ' . $odds;
                    break;
                case '삼쌍승':
                    // $place = $select;
                    $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $select[0], 'place_2' => $select[1], 'place_3' => $select[2], 'odds' => $odds);
                    $odds_samssang .= ' ' . $odds;
                    break;
                case '복연승':
                    $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $select[0], 'place_2' => $select[1], 'place_3' => 0, 'odds' => $odds);
                    $odds_bokyun .= ' ' . $odds;
                    break;
                default:
                    continue;
                    break;
            }
        }
    }
    // print_r($data);

    // exit();

    if (count($data) < 3) {
        echo '등록통과(결과 3개 이하)' . PHP_EOL;
        return;
    }

    $sql = 'SELECT stat FROM `race` WHERE id = ' . $race->id . ' LIMIT 1';
    // print_r($sql);
    $stat = select_sql($sql);
    //     echo $race_id . '->' . $stat[0]->stat;
    // if 문 레이스아디로 레이스의 상태 체크 - 취소/완료 아닐시 아래 진행
    if ($stat[0]->stat == 'E' || $stat[0]->stat == 'C') {
        echo '등록통과(취소/완료)' . PHP_EOL;
        return;
    }
    echo '등록진행' . PHP_EOL;

    foreach ($data as $key => $value) {
        $sql = 'INSERT INTO `result` (`race_id`, `type`, `place_1`, `place_2`, `place_3`, `odds`) SELECT ' . $value['race_id'] . ', \'' . $value['type'] . '\' , ' . $value['place_1'] . ',' . $value['place_2'] . ',' . $value['place_3'] . ',' . $value['odds'] . ' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `result` WHERE `race_id`= ' . $value['race_id'] . ' and  `type` = \'' . $value['type'] . '\' and  `place_1` = ' . $value['place_1'] . ' and  `place_2` = ' . $value['place_2'] . ' and `place_3` = ' . $value['place_3'] . ')';
        echo $sql . PHP_EOL;

        $ok = insert_sql($sql);
    }
    echo $ok . PHP_EOL;

    $odds_all = '';

    $sql = "UPDATE `race` SET `stat`='E', place_1 = '" . $place[0] . "', place_2 = '" . $place[1] . "', place_3 = '" . $place[2] . "', odds_bok = '" . $odds_bok . "', odds_ssang = '" . $odds_ssang . "', odds_sambok = '" . $odds_sambok . "', odds_samssang = '" . $odds_samssang . "', odds_bokyun = '" . $odds_bokyun . "',	odds_all = '" . $odds_all . "' WHERE stat = 'P' and `id`= " . $race->id;
    echo $sql . PHP_EOL;
    $ok = insert_sql($sql);
    echo $ok . PHP_EOL;
    // exit();


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

// $date_url = 'https://www.tab.com.au/racing/meetings/tomorrow';
// $date_url = array('https://www.tab.com.au/racing/meetings/today/R', 'https://www.tab.com.au/racing/meetings/today/H', 'https://www.tab.com.au/racing/meetings/today/G');
// $date_url = 'https://www.tab.com.au/racing/meetings/today/R';
$casper = new Casper();
$casper->setOptions(array(
    'ignore-ssl-errors' => 'yes',
    'loadImages' => 'false'
));

$casper->setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36');

$race_day = get_race_day();
// $sql = "SELECT * FROM race WHERE race.start_time < now() and race.start_time > date_add(date(now()),INTERVAL -20 HOUR) and race.association_code in ('osr','osh','osg') and stat = 'P' order by start_time asc;";
$sql = "SELECT *, race.id as id FROM race left join place on race.place_id = place.id WHERE race.start_time < now() and race.start_date = '" . $race_day . "' and race.association_code in ('jboat') and stat = 'P' order by start_time asc;";
$races = select_sql($sql);

// exit();

if ($races) {
    foreach ($races as $i => $race) {
        // $url = 'https://www.tab.com.au/racing/' . $v->start_date . '/' . $v->place_name . '/' . str_replace($v->association_code . '_', '', $v->place_code) . '/' . strtoupper(str_replace('os', '', $v->association_code)) . '/' . $v->race_no;
        // echo $url . PHP_EOL;
        get_race_result($url, $race);
    }
    echo 'Done' . PHP_EOL;
}
