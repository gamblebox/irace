<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require "/srv/krace/vendor/autoload.php";

use Browser\Casper;
// use Sunra\PhpSimple\HtmlDomParser;
use voku\helper\HtmlDomParser;

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
    // return '2020-09-26';
    return $race_day;
}

function get_race_result($race)
{
    $data = array();
    $base_url = 'https://www.jra.go.jp';
    // return doAction('/JRADB/accessS.html','pw01srl10062020040620200926/46');
    // return doAction('/JRADB/accessS.html','pw01srl10072020020620200926/2E')
    //pw01srl100720200206010020200926
    // pw01bmd1007202002060120200926/8E
    echo '$race ' . $race->rk_race_code . PHP_EOL;
    // exit();
    // doAction('/JRADB/accessH.html','pw01hli00/03');return false;
    $html = file_get_contents_post($base_url . '/JRADB/accessH.html', array('cname' => 'pw01hli00/03'));
    $dom = HtmlDomParser::str_get_html($html);
    // $as = $dom->find('table td > div table table tr > td > a');
    $as = $dom->find('#main div.content>div>div>a');
    // print_r($as);
    // exit();

    $cnames = array();
    foreach ($as as $key => $a) {
        $onclick = explode('\'', $a->attr['onclick']);
        $cnames[] = $onclick[3];
    }
    // print_r($cnames);
    // exit();
    $prefix = 'pw01hde01';
    $place_code = str_replace('jra_', '', $race->place_code);
    $place_round = substr($race->rk_race_code, 11, 8);
    $race_no = str_pad($race->race_no, 2, "0", STR_PAD_LEFT);
    $race_day = str_replace('-', '', $race->start_date);

    $cname = $prefix . $place_code . $place_round . $race_day . $postfix;
    $url = $base_url . '/JRADB/accessH.html';
    echo '$url=>' . $url . PHP_EOL;
    echo '$cname=>' . $cname . PHP_EOL;

    foreach ($cnames as $key => $value) {
        if (substr($value, 0, -3) == $cname) {
            $cname = $value;
            break;
        }
    }

    $html = file_get_contents_post($url, array('cname' => $cname));
    // echo '$html=>' . $html . PHP_EOL;
    // exit();

    $dom = HtmlDomParser::str_get_html($html);
    $tables = $dom->find('table > tr > td > table:nth-child(3) > tr > td > table > tr > td > table');
    $li = $dom->find('//*[@id="harai_' . $race->race_no . 'R"]');
    //*[@id="harai_1R"]
    // $tables = $xpath->query('/html/body/table/tr/td/table[3]/tr/td/table/tr/td/table');
    // print_r($lis);
    // exit();
    // if (count($tables) < 1){
    //     return;
    // }
    // if (!$tables[$race->race_no*3]) {
    //     return;
    // }

    // $tr_race = $tables[$race->race_no*3]->find('tr', 2);

    // print_r($tr_race);
    // exit();
    // if (count($tr_race) < 1){
    //     return;
    // }

    // 착순
    // $trs = $li->find('td:nth-child(1)>table:nth-child(2)>tr:not(:first-child)');
    $table = $li->find('table')[0];
    $trs = $table->find('tbody>tr');
    // print_r($trs);
    // exit();
    $place = array();
    foreach ($trs as $key => $tr) {
        $tds = $tr->find('td');
        $place[$tds[0]->textContent - 1] .= ' ' . $tds[2]->textContent;
    }
    // print_r($place);
    // exit();

    $jtype2type = array(
        '馬連' => '복승',
        '馬単' => '쌍승',
        '3連複' => '삼복승',
        '3連単' => '삼쌍승',
        'ワイド' => '복연승',
        '単勝' => '단승',
        '複勝' => '연승',

    );

    // 배당
    // $trs = $tables[1]->find('tbody>tr');
    $lis = $li->find('div.refund_unit ul>li');
    // print_r($lis);
    // exit();

    // $trs_new = array();
    // foreach ($trs as $key => $tr) {
    //     $tds = $tr->find('td');
    //     if (count($tds) > 7) {
    //         $trs_new[] = array($tds[0]->innerHtml(),$tds[1]->innerHtml(),$tds[2]->innerHtml());
    //         $trs_new[] = array($tds[4]->innerHtml(),$tds[5]->innerHtml(),$tds[6]->innerHtml());
    //     }

    // }
    // print_r($trs_new);
    // exit();
    // $type = '';


    foreach ($lis as $key => $li) {
        // $tds = $tr_new->find('td');
        // print_r($tr_new);
        // exit();
        $type =  $jtype2type[$li->find('dl>dt')[0]->textContent];
        //  print_r($type);
        // exit();

        // if (trim($tds[1]->textContent) == '不成立') {
        //     continue;
        // }
        // $selects = explode('<br>', $tr_new[1]);
        $selects = array();
        $arr_odds = array();

        $lines = $li->find('dl>dd>div.line');
        foreach ($lines as $key => $line) {
            $selects[] = $line->find('div.num')[0]->textContent;
            $arr_odds[] = $line->find('div.yen')[0]->textContent;
        }
        // $arr_odds = explode('<br>', $tr_new[2]);


        foreach ($selects as $key => $select) {
            $odds = str_replace(array('円', ','), '', $arr_odds[$key]) / 100;
            $odds = $odds > 100 ? 100 : $odds;

            // print_r($select);
            // exit();
            $select = explode('-', $select);
            switch ($type) {
                case '복승':
                    $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $select[0], 'place_2' => $select[1], 'place_3' => 0, 'odds' => $odds);
                    $odds_bok .= $odds . ' ';
                    break;
                case '쌍승':
                    $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $select[0], 'place_2' => $select[1], 'place_3' => 0, 'odds' => $odds);
                    $odds_ssang .= $odds . ' ';
                    break;
                case '삼복승':
                    $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $select[0], 'place_2' => $select[1], 'place_3' => $select[2], 'odds' => $odds);
                    $odds_sambok .= $odds . ' ';
                    break;
                case '삼쌍승':
                    $place = $select;
                    $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $select[0], 'place_2' => $select[1], 'place_3' => $select[2], 'odds' => $odds);
                    $odds_samssang .= $odds . ' ';
                    break;
                case '복연승':
                    $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $select[0], 'place_2' => $select[1], 'place_3' => 0, 'odds' => $odds);
                    $odds_bokyun .= $odds . ' ';
                    break;
                case '단승':
                    $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $select[0], 'place_2' => 0, 'place_3' => 0, 'odds' => $odds);
                    $odds_dan .= $odds . ' ';
                    break;
                case '연승':
                    $data[] = array('race_id' => $race->id, 'type' => $type, 'place_1' => $select[0], 'place_2' => 0, 'place_3' => 0, 'odds' => $odds);
                    $odds_yun .= $odds . ' ';
                    break;
                default:
                    // continue;
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
        echo $ok . PHP_EOL;
    }


    $odds_all = '';

    $sql = "UPDATE `race` SET `stat`='E', place_1 = '" . trim($place[0]) . "', place_2 = '" . trim($place[1]) . "', place_3 = '" . trim($place[2]) . "', odds_bok = '" . trim($odds_bok) . "', odds_ssang = '" . trim($odds_ssang) . "', odds_sambok = '" . trim($odds_sambok) . "', odds_samssang = '" . trim($odds_samssang) . "', odds_bokyun = '" . trim($odds_bokyun) . "',	odds_all = '" . $odds_all . "' WHERE stat = 'P' and `id`= " . $race->id;
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

$race_day = get_race_day();
// $sql = "SELECT * FROM race WHERE race.start_time < now() and race.start_time > date_add(date(now()),INTERVAL -20 HOUR) and race.association_code in ('osr','osh','osg') and stat = 'P' order by start_time asc;";
$sql = "SELECT *, race.id as id FROM race left join place on race.place_id = place.id WHERE race.start_time < now() and race.start_date = '" . $race_day . "' and race.association_code in ('jra') and stat = 'P' order by start_time asc;";
$races = select_sql($sql);
// print_r($races);
// exit();

if ($races) {
    foreach ($races as $i => $race) {
        get_race_result($race);
    }
    echo 'Done' . PHP_EOL;
}
