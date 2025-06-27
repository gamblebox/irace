<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

function get_race_data_to_json($url)
{
    global $data;
    $cycle_own_id = [
        '광명' => 201,
        '창원' => 202,
        '부산' => 203
    ];
    $code = [
        '①' => 1,
        '②' => 2,
        '③' => 3,
        '④' => 4,
        '⑤' => 5,
        '⑥' => 6,
        '⑦' => 7,
        '⑧' => 8
    ];
    // // curl 리소스를 초기화
    // $ch = curl_init();

    // // url을 설정
    // curl_setopt($ch, CURLOPT_URL, $url);

    // // 헤더는 제외하고 content 만 받음
    // // curl_setopt($ch, CURLOPT_HEADER, 0);

    // // 응답 값을 브라우저에 표시하지 말고 값을 리턴
    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // // 브라우저처럼 보이기 위해 user agent 사용

    // //curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0');
    // curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36');

    // //리퍼러
    // curl_setopt( $ch, CURLOPT_REFERER, $url );

    // // 실행
    // $content = curl_exec($ch);

    // $url = 'http://13.125.239.248/curl.php?url=' . urlencode($url);
    $url = 'http://3.35.205.96/curl.php?url=' . urlencode($url);
    $url = 'http://141.164.48.95/curl.php?url=' . urlencode($url);

    echo $url;
    // exit();
    $content = file_get_contents($url);

    $html = strtolower(substr($content, -5, 4));
    if ($html !== 'html') {
        return false;
    }
    echo 'reading ok' . PHP_EOL;

    // 리소스 해제를 위해 세션 연결 닫음
    // curl_close($ch);

    $dom = new DomDocument();
    $dom->loadHtml('<?xml encoding="UTF-8">' . $content);
    $xpath = new DomXPath($dom);
    // print_r($xpath);
    // collect header names
    // $headerNames = ['own_id', 'start_time', 'race_no' , 'length', 'entry_count'];//,'race_class','title','result','result_table'];
    $headerNames = [
        'own_id',
        'start_date',
        'race_no',
        '단승',
        '연승',
        '쌍승',
        '복승',
        '삼복승',
        '쌍복승',
        '삼쌍승'
    ];

    /*
     * foreach ($xpath->query('//tr[@class="dbitem"]//td') as $node) {
     * $headerNames[] = $node->nodeValue;
     * }
     */

    // print_r($headerNames);

    // collect data
    // *[@id="printPop"]/h4[2]
    // *[@id="contentForm"]/div[3]/p[1]/strong/text()
    // *[@id="contentForm"]/div[3]/p[1]/strong
    // *[@id="contentForm"]/div[4]/p[1]/strong
    $date_element = $xpath->query('//*[@id="contentForm"]/div[4]/p[1]/strong');
    $date = trim($date_element[0]->nodeValue);
    // print_r($date);
    preg_match_all('/[0-9,-]+/', $date, $arr);
    // print_r($arr);
    $date = $arr[0][0] . '-' . $arr[0][1] . '-' . $arr[0][2];
    // $date = substr($date,0,4) . '-' . substr($date,8,2) . '-' . substr($date,14,2);
    // print_r($date);
    // *[@id="contentForm"]/table/thead/tr
    // *[@id="contentForm"]/table/tbody/tr[1]/td[1]
    // $tbody = $xpath->query('//*[@id="contentForm"]/table/tbody');
    $table = $xpath->query('//*[@id="contentForm"]/div[@class="table pcType"]/table')[0];
    $trs = $xpath->query('tbody/tr', $table);
    // print_r($trs);
    // exit();

    foreach ($trs as $index => $tr) {
        $row = $xpath->query('td', $tr);
        if ($row[1]->nodeValue === '') {
            continue;
        }

        // print_r($node);
        $rowData = array();
        // $rowData[] = $place_own_id;
        // $rowData[] = $rk_race_code;
        // $rowData[] = ''.($index+1);
        // $str = trim($node->nodeValue);
        // $place = substr($str, 0, 6);
        // print_r($cycle_own_id);
        // $rowData[] = $cycle_own_id[$place];
        // $rowData[] = $date . ' ' . substr($str, -5, 5);
        // $rowData[] = substr($str, 11, 2);
        // $rowData[] = substr($str, -19, 4);
        // $rowData[] = $entry->length;

        foreach ($xpath->query('td', $tr) as $cell) {
            $rowData[] = trim($cell->nodeValue);
        }
        // echo '<br>';
        // print_r($rowData);
        // $rowData= array_slice($rowData,0,9);
        // $rowData[2] = substr($rowData[2], 0, 10) . ' ' . substr($rowData[8], 0, 5);
        // $rowData[5] = substr($rowData[5], 0, -1);
        $str = trim($rowData[0]);
        // $place = substr($str, 0, 6);
        $rowData[0] = 211;
        $rowData[1] = $date;
        $race_no = substr($str, 0, 2);
        $rowData[2] = (int) $race_no;
        unset($rowData[3]);
        // unset($rowData[9]);
        unset($rowData[11]);
        unset($rowData[12]);
        $rowData = array_values($rowData);
        //print_r($rowData);

        // unset($rowData[4]);
        // print_r($rowData);
        // preg_match_all('/^\([0-9-]+/', $rowData[3], $arr);
        // print_r($rowData[7]);
        // preg_match_all('/\([1-9-]+\)/', $rowData[7], $p);
        // print_r($p);
        // preg_match_all('/[^\(-\)][0-9.]+[^\)-]/', $rowData[7], $b);
        // print_r($b);

        for ($k = 3; $k < count($rowData); $k++) {
            // print_r($rowData[$k]);
            // $s = explode(' ',$rowData[$k]);
            // print_r($s);
            //             preg_match_all( '/(①|②|③|④|⑤|⑥|⑦|⑧|-)+/', $rowData[ $k ], $p );
            // [①②③④⑤⑥⑦⑧-]+/

            //             $p = str_replace( [ ①, ②, ③, ④, ⑤, ⑥, ⑦, ⑧ ], [ 1, 2, 3, 4, 5, 6, 7, 8 ], $p[ 0 ] );
            //             print_r($p);
            //             preg_match_all( '/[^\(-\)][0-9.]+[^\)-]/', $rowData[ $k ], $b );
            //             print_r($b[0]);

            // print_r($rowData[$k]);
            preg_match_all('/\([1-9-]+\)/', $rowData[$k], $p);
            //print_r($p[0]);
            $p = str_replace([
                '(',
                ')'
            ], '', $p[0]);
            //             $p = $p[0];
            preg_match_all('/[^\(-\)][0-9.]+[^\)-]/', $rowData[$k], $b);

            $b = $b[0];
            $pb = array();
            foreach ($p as $j => $p) {
                $pb[] = [
                    $p,
                    (float) $b[$j]
                ];
            }
            // print_r($pb);
            $rowData[$k] = [];
            // print_r($rowData[$k]);
            $rowData[$k] = $pb;
        }

        $data[] = array_combine($headerNames, $rowData);
    }

    //print_r($data);
    // echo json_encode($data);
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

$alinks = [
    'http://www.kboat.or.kr/contents/information/raceResultList.do'
];
// $alinks = ['http://www.kcycle.or.kr/contents/information/raceResultPage.do?stndYear=2016&tms=17&dayOrd=2'];
// print_r($alinks[0]->value);

$data = array();
foreach ($alinks as $alink) {
    get_race_data_to_json($alink);
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);

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
    // print_r($r);

    $own_id = $r['own_id'];
    $start_date = $r['start_date'];
    $race_no = $r['race_no'];
    $sql = 'SELECT id FROM `race` WHERE start_date = date(' . '\'' . $start_date . '\') and (SELECT id from place where own_id = \'' . $own_id . '\') = place_id and race_no = \'' . $race_no . '\'';

    $v = select_sql($sql);
    // print_r( $v[0]->id);
    $race_id = $v[0]->id;
    $sql = 'SELECT stat FROM `race` WHERE id = ' . $race_id . ' LIMIT 1';
    // print_r($sql);
    $stat = select_sql($sql);
    echo $stat[0]->stat;
    // if 문 레이스아디로 레이스의 상태 체크 - 완료 아닐시 아래 진행
    if ($stat[0]->stat === 'E') {
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
            if (!$c) {
                continue;
            }
            $p = explode('-', $c[0]);
            if ($type === '복승' || $type === '삼복승') {
                sort($p, SORT_NUMERIC);
            }
            if ($type === '쌍복승') {
                $tp = array($p[1], $p[2]);
                sort($tp, SORT_NUMERIC);
                $p = array($p[0], $tp[0], $tp[1]);
            }
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

            print_r($sql);
            // echo '<br>';

            $ok = insert_sql($sql);
            echo $ok;
        }

        // print_r($d);
    }
    // race stat 'E' 완료로 변경
    $sql = "SELECT * FROM `result` WHERE type='삼복승' and race_id = " . $race_id;
    $results = select_sql($sql);
    // print_r($results);
    if (count($results) > 0) {
        $sql = "SELECT * FROM `view_place_result` WHERE race_id = " . $race_id;
        $results = select_sql($sql);
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
        //echo '<br>race_id=' . $results[0]->race_id;
        //echo '<br>p1=' . $p1;
        //echo 'p1c=' . $p1c;

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

        $ok = insert_sql($sql);

        $odds_dan = $results[0]->dan;
        $odds_yun = $results[0]->yun;
        $odds_bok = $results[0]->bok;
        $odds_ssang = $results[0]->ssang;
        $odds_bokyun = $results[0]->bokyun;
        $odds_sambok = $results[0]->sambok;
        $odds_samssang = $results[0]->samssang;
        $odds_ssangbok = $results[0]->ssangbok;
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

        $sql = "UPDATE `race` SET `stat`='E', place_1 = '" . $p1 . "', place_2 = '" . $p2 . "', place_3 = '" . $p3 . "', place_oe = '" . $oe . "',	odds_dan = '" . $odds_dan . "',	odds_yun = '" . $odds_yun . "',	odds_bok = '" . $odds_bok . "',	odds_ssang = '" . $odds_ssang . "',	odds_bokyun = '" . $odds_bokyun . "',	odds_sambok = '" . $odds_sambok . "',	odds_samssang = '" . $odds_samssang . "',	odds_ssangbok = '" . $odds_ssangbok . "',	odds_all = '" . $odds_all . "' WHERE stat = 'P' and `id`= " . $race_id;
        print_r($sql);
        $ok = insert_sql($sql);
        /*
         * $sql = "INSERT INTO `announce` (`association_code`, `type`, `memo`) VALUES ('boat', '경주결과등록', concat((select place.name from race left outer join place on race.place_id = place.id where race.id = " . $race_id . "), ' ', (select race.race_no from race where race.id = " . $race_id . "), '경주 결과 등록 되었습니다'))";
         * $ok = insert_sql($sql);
         */
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

$msg[] = array_combine($headerNames, [
    '',
    '',
    '',
    'success'
]);
print_r($msg);
