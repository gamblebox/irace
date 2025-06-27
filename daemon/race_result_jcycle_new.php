<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
define('MAX_FILE_SIZE', 6000000);
putenv("PHANTOMJS_EXECUTABLE=/usr/local/bin/phantomjs");
// require __DIR__ . "/../../../vendor/autoload.php";
require "/srv/krace/vendor/autoload.php";

use Browser\Casper;
// use Sunra\PhpSimple\HtmlDomParser;
use voku\helper\HtmlDomParser;


$url = 'http://keirin.jp/pc/top#';

function file_get_contents_post($url, $arr_data = array())
{
    $postdata = http_build_query(
        $arr_data
    );

    $opts = array(
        'http' =>
        array(
            'method'  => 'POST',
            'header'  => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $postdata
        )
    );

    $context  = stream_context_create($opts);

    return file_get_contents($url, false, $context);
}

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

function get_race_result($url, $casper, $race)
{
    echo date("Y-m-d H:i:s") . 'get func' . PHP_EOL;
    $data = array();
    $dom = HtmlDomParser::file_get_html($url);
    // 자바스크립트 없으면 안됨.
    $place_divs = $dom->find('#kaisaiInfoTable tr > td:nth-child(1) > div > div:nth-child(1)');
    print_r($place_divs);

    $index = 0;

    foreach ($place_divs as $key => $place_div) {
        echo '$place_div->textContent=>' . $place_div->textContent . PHP_EOL;
        if ($place_div->textContent == $race->place_name) {
            $index = $key + 1;
            break;
        };
    }

    if (!$index) {
        return;
    }
    // echo $index;
    $disp = 'PJ0306';
    $button = $dom->find('#kaisaiInfoTable > tbody > tr:nth-child(' . $index . ') > td:nth-child(6) > button', 0);
    $onclick = explode('\'', $button->attr['onclick']);
    $encp = $onclick[1];
    echo '$encp=>' . $encp . PHP_EOL;
    exit();

    $casper->click('#kaisaiInfoTable > tbody > tr:nth-child(' . $index . ') > td:nth-child(6) > button');
    // exit();
    $casper->wait(1000);
    $casper->click('#csub' . $race->race_no . 'R');
    $casper->wait(1000);
    $casper->run();

    echo date("Y-m-d H:i:s") . 'get func run' . PHP_EOL;

    $html = $casper->getHtml();
    echo $html . PHP_EOL;

    $dom = HtmlDomParser::str_get_html($html);

    $trs = $dom->find('#pitbodyHarai tr');
    print_r($trs);

    $jtype2type = array(
        '2車複' => '복승',
        '2車単' => '쌍승',
        '3連複' => '삼복승',
        '3連単' => '삼쌍승',
        'ワイド' => '복연승',
    );

    $type = '';
    foreach ($trs as $key => $tr) {
        $tds = $tr->find('th,td');

        print_r($tds);
        // exit();
        if (count($tds) > 3) {
            if ($tds[2]->textContent == '【未発売】' || $tds[2]->textContent == '') {
                continue;
            }
            $type =  $jtype2type[$tds[0]->textContent];
            $odds = str_replace(array('円', ','), '', $tds[2]->textContent) / 100;
            $odds = $odds > 100 ? 100 : $odds;
            switch ($type) {
                case '복승':
                    $select = explode('=', $tds[1]->textContent);
                    $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $select[0], 'place_2' => $select[1], 'place_3' => 0, 'odds' => $odds);
                    $odds_bok = $odds;
                    break;
                case '쌍승':
                    $select = explode('-', $tds[1]->textContent);
                    $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $select[0], 'place_2' => $select[1], 'place_3' => 0, 'odds' => $odds);
                    $odds_ssang = $odds;
                    break;
                case '삼복승':
                    $select = explode('=', $tds[1]->textContent);
                    $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $select[0], 'place_2' => $select[1], 'place_3' => $select[2], 'odds' => $odds);
                    $odds_sambok = $odds;
                    break;
                case '삼쌍승':
                    $select = explode('-', $tds[1]->textContent);
                    $place = $select;
                    $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $select[0], 'place_2' => $select[1], 'place_3' => $select[2], 'odds' => $odds);
                    $odds_samssang = $odds;
                    break;
                case '복연승':
                    $select = explode('=', $tds[1]->textContent);
                    $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $select[0], 'place_2' => $select[1], 'place_3' => 0, 'odds' => $odds);
                    $odds_bokyun = $odds;
                    break;
                default:
                    continue;
                    break;
            }
        } else {
            // if ($tds[1]->textContent == '' ) {
            //     continue;
            // }
            $odds = str_replace(array('円', ','), '', $tds[1]->textContent) / 100;
            $odds = $odds > 100 ? 100 : $odds;
            switch ($type) {
                case '복승':
                    $select = explode('=', $tds[0]->textContent);
                    $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $select[0], 'place_2' => $select[1], 'place_3' => 0, 'odds' => $odds);
                    $odds_bok .= ' ' . $odds;
                    break;
                case '쌍승':
                    $select = explode('-', $tds[0]->textContent);
                    $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $select[0], 'place_2' => $select[1], 'place_3' => 0, 'odds' => $odds);
                    $odds_ssang .= ' ' . $odds;
                    break;
                case '삼복승':
                    $select = explode('=', $tds[0]->textContent);
                    $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $select[0], 'place_2' => $select[1], 'place_3' => $select[2], 'odds' => $odds);
                    $odds_sambok .= ' ' . $odds;
                    break;
                case '삼쌍승':
                    $select = explode('-', $tds[0]->textContent);
                    // $place = $select;
                    $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $select[0], 'place_2' => $select[1], 'place_3' => $select[2], 'odds' => $odds);
                    $odds_samssang .= ' ' . $odds;
                    break;
                case '복연승':
                    $select = explode('=', $tds[0]->textContent);
                    $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $select[0], 'place_2' => $select[1], 'place_3' => 0, 'odds' => $odds);
                    $odds_bokyun .= ' ' . $odds;
                    break;
                default:
                    continue;
                    break;
            }
        }
    }
    print_r($data);
    if (count($data) < 4) {
        echo '등록통과(결과 4개 이하)' . PHP_EOL;
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

while (true) {
    $race_day = get_race_day();
    // $sql = "SELECT * FROM race WHERE race.start_time < now() and race.start_time > date_add(date(now()),INTERVAL -20 HOUR) and race.association_code in ('osr','osh','osg') and stat = 'P' order by start_time asc;";
    $sql = "SELECT race.*, place.e_name as place_name FROM race left join place on race.place_id = place.id WHERE race.start_time < now() and race.start_date = '" . $race_day . "' and race.association_code in ('jcycle') and stat = 'P' order by start_time asc;";
    $races = select_sql($sql);
    print_r($races);
    // exit();

    if ($races) {
        foreach ($races as $i => $race) {
            // $url = 'https://www.tab.com.au/racing/' . $v->start_date . '/' . $v->place_name . '/' . str_replace($v->association_code . '_', '', $v->place_code) . '/' . strtoupper(str_replace('os', '', $v->association_code)) . '/' . $v->race_no;
            // echo $url . PHP_EOL;
            get_race_result($url, $casper, $race);
        }
        echo 'Done' . PHP_EOL;
        sleep(30);
    } else {
        echo 'Nothing to Do ...' . PHP_EOL;
        sleep(60);
    }
}
