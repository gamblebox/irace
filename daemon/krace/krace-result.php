<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require_once('/srv/irace/daemon/common/pdo-tool.php');
require_once('/srv/irace/daemon/common/config-db.php');

function get_race_data_to_json($url)
{
    global $data;
    $code = [
        '①' => 1,
        '②' => 2,
        '③' => 3,
        '④' => 4,
        '⑤' => 5,
        '⑥' => 6,
        '⑦' => 7,
        '⑧' => 8,
        '⑨' => 9,
        '⑩' => 10,
        '⑪' => 11,
        '⑫' => 12,
        '⑬' => 13,
        '⑭' => 14,
        '⑮' => 15,
        '⑯' => 16
    ];
    // print_r($code[⑪]);
    // $place_own_id = explode( 'babaCode=', $url);
    // $place_own_id = explode( '&k_raceDate', $place_own_id[1]);
    // $place_own_id = $place_own_id[0];
    $place_own_id = '10' . substr($url, -1, 1);
    // echo $place_own_id;
    // $date = substr($url, -18, 8 );
    // $date = str_replace('/', '-', $date);
    // $rk_race_code = substr($url, -8, 6 );
    // echo $rk_race_code;
    // $date = preg_replace("/([\d]{4})([\d]{2})/", "$1-$2-$3", $date);
    // echo $date;

    // curl 리소스를 초기화
    $ch = curl_init();

    // url을 설정
    curl_setopt($ch, CURLOPT_URL, $url);

    // 헤더는 제외하고 content 만 받음
    //curl_setopt($ch, CURLOPT_HEADER, 0);

    // 응답 값을 브라우저에 표시하지 말고 값을 리턴
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // 브라우저처럼 보이기 위해 user agent 사용
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0');

    //리퍼러
    curl_setopt($ch, CURLOPT_REFERER, "https://www.kboat.or.kr/contents/main/mainPage.do");

    $content = curl_exec($ch);
    // echo $content;
    // 리소스 해제를 위해 세션 연결 닫음
    curl_close($ch);
    // exit();

    // $url = 'https://www.kcycle.or.kr/contents/information/fixedChuljuPage.do';
    $dom = new DomDocument();

    // 실행
    $dom->loadHtml($content);
    // $html = $dom->saveHTML();
    // echo $html . PHP_EOL;
    $html = strtolower(substr($dom->saveHTML(), -6, 4));
    echo $html . PHP_EOL;
    if ($html !== 'html') {
        return false;
    }
    echo 'ok' . PHP_EOL;

    //     do {
    //         $dom->loadHtmlFile($url);
    //         sleep(5);
    //         echo 'try' . PHP_EOL;
    //     } while (substr($dom->saveHTML(), - 8) === '<html>');
    //     echo 'load ok' . PHP_EOL;
    // $dom->loadHtml('$result');
    // print_r($dom);
    $xpath = new DomXPath($dom);

    // collect header names
    // $headerNames = ['own_id', 'start_time', 'race_no' , 'length', 'entry_count'];//,'race_class','title','result','result_table'];
    // $headerNames = ['own_id', 'start_date', 'race_no' , 'dan', 'yun', 'bok', 'ssang', 'bokyun', 'sambok'];//,'race_class','title','result','result_table'];
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
    /*
     * foreach ($xpath->query('//tr[@class="dbitem"]//td') as $node) {
     * $headerNames[] = $node->nodeValue;
     * }
     */

    // print_r($headerNames);
    // collect data

    // /html/body/table/tbody/tr[3]

    // *[@id="oddsField"]/table/tbody/tr[1]
    // print_r($xpath);
    // *[@id="contents"]/form/div[2]
    // *[@id="contents"]/form/div[2]/table/tbody
    // $tbody = $xpath->query('//div[@class="raceState"]');
    // *[@id="contents"]/form/div[2]
    // *[@id="contents"]/div[1]/p[1]/span
    $date_element = $xpath->query('//*[@id="contents"]/div[1]/p[1]/span');
    print_r($date_element[0]->nodeValue);
    $date = trim($date_element[0]->nodeValue);
    print_r($date);
    $date = str_replace('/', '-', substr($date, 0, 10));
    print_r($date);
    $tbody = $xpath->query('//div[@class="tableType2"]/table/tbody');
    // print_r($tbody);
    if (count($tbody) < 1) {
        echo 'no html';
        return;
    }
    foreach ($xpath->query('tr', $tbody[0]) as $index => $node) {
        $row = $xpath->query('td', $node);
        if ($row[1]->nodeValue === '') {
            continue;
        }
        $rowData = array();
        $rowData[] = $place_own_id;
        $rowData[] = $date;
        // $rowData[] = $rk_race_code;
        // $rowData[] = ''.($index+1);

        foreach ($xpath->query('td', $node) as $cell) {

            $rowData[] = trim($cell->nodeValue);
        }
        // print_r($rowData);
        // unset($rowData[9]);
        unset($rowData[10]);
        unset($rowData[11]);
        // print_r($rowData);
        // $rowData[8] ='②③④ 127.3 ②③⑤ 10.7';
        for ($k = 3; $k < count($rowData); $k++) {
            $arr = explode(' ', $rowData[$k]);
            // print_r($arr);
            // $p = array();
            $pb = array();
            foreach ($arr as $i => $d) {

                if (($i % 2) === 0) {
                    // echo '@1@';
                    // print_r('index'.$i.'->'.$d.'!');
                    $pnum = array();

                    for ($j = 0; $j < strlen($d) - 2; $j++) {

                        if (($j % 3) === 0) {
                            // print_r('index'.$j.'->'.$d[$j+0].$d[$j+1].$d[$j+2].'!');

                            $pnum[] = $code[$d[$j + 0] . $d[$j + 1] . $d[$j + 2]];
                        }
                    }
                    // unset ($pnum[0]);
                    // print_r($pnum);
                } else {
                    // echo '@2@';
                    $b = $d;
                    // print_r($pnum);
                    // echo '<br>';
                    // print_r($b);
                    // echo '<br>';
                    if ($k !== 6 && $k !== 9) {
                        sort($pnum, SORT_NUMERIC);
                    }
                    $pb[] = [
                        $pnum,
                        $b
                    ];
                    // print_r($pb);
                    // echo '<br>';
                }
                $rowData[$k] = $pb;
                // print_r($pb);
            }
        }

        // $arr = $arr[0][0];
        // preg_match_all("/([0-9])+/",$rowData[8],$match);

        // $rowData[8] = array(array(array(2,3,4),127.3),array(array(2,3,5),10.5));
        // $rowData[2] = $code[②];
        // print_r($rowData[8]);
        // print_r($rowData);

        // print_r($rowData);
        // unset($rowData[4]);
        // print_r($rowData);
        $data[] = array_combine($headerNames, $rowData);
    }

    // print_r($data);
    // return $data;
    // echo json_encode($data, JSON_UNESCAPED_UNICODE);
}

$alinks = [
    // 'https://race.kra.co.kr/raceFastreport/ScoreInfo.do?Act=03&Sub=3&meet=1', // ];//,
    'https://race.kra.co.kr/raceFastreport/ScoreInfo.do?Act=03&Sub=3&meet=1', // ];//,
    'https://race.kra.co.kr/raceFastreport/ScoreInfo.do?Act=03&Sub=3&meet=2',
    'https://race.kra.co.kr/raceFastreport/ScoreInfo.do?Act=03&Sub=3&meet=3'
];
// print_r($alinks[0]->value);

$database = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';port=' . $port . ';charset=utf8mb4', $user, $password, array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
));

$data = array();
foreach ($alinks as $alink) {
    get_race_data_to_json($alink);
}
// $url = 'https://race.kra.co.kr/chulmainfo/ChulmaDetailInfoList.do?Act=02&Sub=1&meet=1';
// get_race_data_to_json($url);

// echo json_encode($data, JSON_UNESCAPED_UNICODE);

// sql 경주결과 삽입
// print_r($data);
// $sql = 'INSERT INTO `goldrace`.`result` (`race_id`, `type`, `place_1`, `place_2`, `place_3`, `odds`) VALUES (1, \'복승\', 1, 2, 3, 50);';
// $sql_race_id = 'SELECT id FROM `race` WHERE date(start_time) = date(\'2016-04-18\') and (SELECT id from place where own_id = \'11\') = place_id and race_no = \'1\'';
// $sql_place = '';
$msg = array();
$headerNames = [
    'own_id',
    'start_date',
    'race_no',
    'reg_result'
];

foreach ($data as $i => $r) {
    //print_r($r);
    //echo PHP_EOL;

    $own_id = $r['own_id'];
    $start_date = $r['start_date'];
    $race_no = $r['race_no'];
    $sql = 'SELECT id FROM `race` WHERE start_date = date(' . '\'' . $start_date . '\') and (SELECT id from place where own_id = \'' . $own_id . '\') = place_id and race_no = \'' . $race_no . '\'';
    print_r($sql);

    $raceInfo = select_query_one($database, $sql);
    if (!$raceInfo) {
        continue;
    }
    $race_id = $raceInfo->id;
    //echo $race_id;
    //echo PHP_EOL;

    $sql = 'SELECT stat FROM `race` WHERE id = ' . $race_id . ' LIMIT 1';
    print_r($sql);
    $stat = select_query($database, $sql);
    // echo $stat[0]->stat;
    // if 문 레이스아디로 레이스의 상태 체크 - 완료 아닐시 아래 진행
    if ($stat[0]->stat === 'E' || $stat[0]->stat === 'C') {
        // echo $i;
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

            $p = $c[0];
            $place_1 = $p[0];
            $p[1] === null ? $place_2 = 0 : $place_2 = $p[1];
            $p[2] === null ? $place_3 = 0 : $place_3 = $p[2];
            $b = $c[1];
            if ($b > 100) {
                $b = 100;
            }
            // print_r($c);
            // echo '<br>';
            // echo $type . $place_1 . $place_2 .$place_3;
            // $sql = 'INSERT INTO `result` (`race_id`, `type`, `place_1`, `place_2`, `place_3`, `odds`) VALUES (' . $race_id . ', \'' . $type . '\' , ' . $place_1 . ',' . $place_2 . ',' . $place_3 . ',' . $b . ')';
            $sql = "INSERT INTO `result` (`race_id`, `type`, `place_1`, `place_2`, `place_3`, `odds`) SELECT " . $race_id . ", '" . $type . "' , " . $place_1 . "," . $place_2 . "," . $place_3 . "," . $b . " FROM DUAL WHERE NOT EXISTS (SELECT * FROM `result` WHERE `race_id`= " . $race_id . " and  `type` = '" . $type . "' and  `place_1` = " . $place_1 . " and  `place_2` = " . $place_2 . " and `place_3` = " . $place_3 . ")";
            print_r($sql);
            echo PHP_EOL;

            $ok = exec_query($database, $sql);
        }
        // echo $k;
        // print_r($d);
    }
    // race stat 'E' 완료로 변경
    // $race_id = 23333;
    // $sql = "SELECT * FROM `result` WHERE type='삼쌍승' and race_id = " . $race_id;
    $sql = "SELECT * FROM `result` WHERE race_id = " . $race_id;
    $results = select_query($database, $sql);
    // print_r($results);
    if (count($results) > 0) {
        $sql = "SELECT * FROM `view_place_result` WHERE race_id = " . $race_id;
        $results = select_query($database, $sql);
        // $p1 = '';
        // print_r($results);
        // print_r($results[0]->race_id);
        // print_r('<br>p1='.$results[0]->place_1);
        // foreach(explode(' ',$results[0]->place_1) as $p){
        $oe = 0;
        $p1 = explode(' ', $results[0]->place_1);
        print_r($p1);
        $oe += array_sum($p1);

        $p1c = count($p1);
        sort($p1, SORT_NUMERIC);
        $p1 = implode(' ', $p1);
        echo '<br>race_id=' . $results[0]->race_id;
        echo '<br>p1=' . $p1;
        echo 'p1c=' . $p1c;

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
        echo 'p2=' . $p2;
        echo 'p2c=' . $p2c;
        if ($p2c + $p2c > 3) {
            $p3 = '';
        } else {
            $p3 = explode(' ', $results[0]->place_3);
            $p3 = array_unique($p3);
            /*
             * foreach($p3 as $k=>$v){
             * if ($v === ''){
             * unset($p3[$k]);
             * }
             * }
             */
            unset($p3[array_search('', $p3)]);
            print_r($p3);
            $oe += array_sum($p3);
            sort($p3, SORT_NUMERIC);
            $p3 = implode(' ', $p3);
        }
        echo 'p3=' . $p3;
        echo '$oe=' . $oe;

        $oe %= 2;
        if ($oe === 0) {
            $oe += 2;
        }

        $sql = "INSERT INTO `result` (`race_id`, `type`, `place_1`, `place_2`, `place_3`, `odds`) SELECT " . $race_id . ", '홀짝' , " . $oe . ",0,0,0 FROM DUAL WHERE NOT EXISTS (SELECT * FROM `result` WHERE `race_id`= " . $race_id . " and  `type` = '홀짝' and  `place_1` = " . $oe . " and  `place_2` = 0 and `place_3` = 0)";
        // print_r($sql);
        // echo '<br>';

        $ok = exec_query($database, $sql);

        $odds_dan = preg_replace('/\s+/', ' ', $results[0]->dan);
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
        $results = select_query($database, $sql);
        $odds_all = $results[0]->odds_all;

        $sql = "UPDATE `race` SET `stat`='E', place_1 = '" . $p1 . "', place_2 = '" . $p2 . "', place_3 = '" . $p3 . "', place_oe = '" . $oe . "',	odds_dan = '" . $odds_dan . "',	odds_yun = '" . $odds_yun . "',	odds_bok = '" . $odds_bok . "',	odds_ssang = '" . $odds_ssang . "',	odds_bokyun = '" . $odds_bokyun . "',	odds_sambok = '" . $odds_sambok . "',	odds_samssang = '" . $odds_samssang . "',	odds_all = '" . $odds_all . "' WHERE stat = 'P' and `id`= " . $race_id;
        print_r($sql);
        echo PHP_EOL;
        $ok = exec_query($database, $sql);
        /*
         * $sql = "INSERT INTO `announce` (`association_code`, `type`, `memo`) VALUES ('race', '경주결과등록', concat((select place.name from race left outer join place on race.place_id = place.id where race.id = " . $race_id . "), ' ', (select race.race_no from race where race.id = " . $race_id . "), '경주 결과 등록 되었습니다'))";
         * $ok = exec_query($database,$sql);
         */
    }

    $rmsg = [
        $own_id,
        $start_date,
        $race_no,
        $ok
    ];
    $msg[] = array_combine($headerNames, $rmsg);
    // break;
}

$msg[] = array_combine($headerNames, [
    '',
    '',
    '',
    'success'
]);
print_r($msg);

// echo $data;
// ---
/*
 * $src = new DOMDocument('1.0', 'utf-8');
 * $src->formatOutput = true;
 * $src->preserveWhiteSpace = false;
 * $content = file_get_contents("https://www.nbs.rs/kursnaListaModul/srednjiKurs.faces?lang=lat");
 * @$src->loadHTML($content);
 * $xpath = new DOMXPath($src);
 * $values=$xpath->query('//td[ contains (@class, "tableCell") ]');
 * foreach($values as $value)
 * {
 * echo $value->nodeValue."<br />";
 * }
 */
// ------------------------------
