<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header("Content-Type:application/json");
header("Content-Type:text/html;charset=UTF-8");
define('MAX_FILE_SIZE', 6000000);
putenv("PHANTOMJS_EXECUTABLE=/usr/local/bin/phantomjs");
require __DIR__ . "/../../../vendor/autoload.php";

use Browser\Casper;
use Sunra\PhpSimple\HtmlDomParser;

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

$race_day = date("Y-m-d");
echo '$race_day ' . $race_day . PHP_EOL;
$now_time = date("H");
echo '$now_time ' . $now_time . PHP_EOL;
if ($now_time < 6) {
    $race_day = date("Y-m-d", strtotime($race_day . " -1 day"));
}
echo '$race_day ' . $race_day . PHP_EOL;

function get_race_data_to_json($casper, $url, $race_id, $place_code, $race_no, $association_code)
{
    echo date("Y-m-d H:i:s") . 'get func' . PHP_EOL;
    global $data;



    try {
        // $casper = new Casper();
        // $casper->setOptions(array(
        // 'ignore-ssl-errors' => 'yes',
        // 'loadImages' => 'false',
        // ));
        $casper->start($url);
        $casper->waitForText('</html>', 5000);
        // $output = $casper->getOutput();
        // $casper->wait(5000);
        // $casper->wait(5000);
        $casper->run();
    } catch (Exception $e) {
        echo 'Exception->' . $e->getMessage() . PHP_EOL;
        exit;
    }

    // check the urls casper get through
    //     var_dump($casper->getRequestedUrls());

    // need to debug? just check the casper output
    //     var_dump($casper->getOutput());

    echo date("Y-m-d H:i:s") . 'get func run' . PHP_EOL;

    $html = $casper->getHtml();
    echo '$html ' . strlen($html) . PHP_EOL;
    $html = explode('Last Updated', $html)[0];

    // if (!empty($html)) {
    // echo '$html not empty' . PHP_EOL;
    // }
    $dom = HtmlDomParser::str_get_html($html);
    $page = trim($dom->find('body h1', 0)->plaintext); //div.page-not-found 
    echo '$page ' . $page . PHP_EOL;
    if ($page == 'Uh Oh!' || $page == 'Access Denied') {
        push_log(date("Y-m-d H:i:s") . ': ' . $url . '::' . $race_id . PHP_EOL . $html . PHP_EOL);
        return;
    }
    $stat = $dom->find('div.race-info-wrapper li.status-text', 0)->plaintext;
    echo '$stat ' . $stat . PHP_EOL;
    //     if (!$stat) {
    //         $output = shell_exec('sh /root/kill_os4php.sh ' . '/tmp/phantomjs_cache_race_result_os_new');
    //         echo "<pre>$output</pre>";
    // //         $output = shell_exec('nohup runuser root -c "php /srv/krace/application/php/admin/race_result_os_new.php &"');
    //         $output = shell_exec('php /srv/krace/application/php/admin/race_result_os_new.php > /dev/null 2>/dev/null &');
    //         push_log('kill and start race_result_os_new.php');
    //         echo "<pre>$output</pre>";

    //         echo 'exit php script' . PHP_EOL;
    //         sleep(10);
    //         exit('exit');
    //     }
    if ($stat == 'Abandoned') {
        $entry_no = 0;
        $type = '경주취소';
        $old_start_time = date("Y-m-d");
        $new_start_time = date("Y-m-d");

        $sql = "SELECT name from place where place_code = '" . $place_code . "'";
        // print_r($sql);
        $place = select_sql($sql);
        $memo = $place[0]->name . " " . $race_no . "경주: 경주취소";

        $sql = "INSERT INTO `race_change_info` (`association_code`,`race_id`, `entry_no`, `type`, `memo`, `old_start_time`, `new_start_time`) SELECT '" . $association_code . "'," . $race_id . "," . $entry_no . ", '" . $type . "' , '" . $memo . "','" . $old_start_time . "','" . $new_start_time . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race_change_info` WHERE `race_id`= " . $race_id . " and  `entry_no` = " . $entry_no . " and  `type` = '" . $type . "' and  `memo` =  '" . $memo . "' and  `old_start_time` = '" . $old_start_time . "' and `new_start_time` = '" . $new_start_time . "')";
        echo $sql;

        $ok = insert_sql($sql);
        echo $ok;
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
        // echo $place . PHP_EOL;
        $entry_no = explode('.', $value->find('td.runner-details', 0)->plaintext)[0];
        // echo $entry_no . PHP_EOL;
        $dan_ratio = round(floor(str_replace(array(
            "$",
            " ",
            ","
        ), "", $value->find('td.result-tote-odds div.result-win', 0)->plaintext) * 10) / 10, 1);
        // $dan_ratio = round(floor(str_replace(array("$", " ", ","), "", $value->find('td.result-fixed-odds div.result-win', 0)->plaintext) * 10) / 10, 1);

        // echo $dan_ratio . PHP_EOL;
        $yun_ratio = round(floor(str_replace(array(
            "$",
            " ",
            ","
        ), "", $value->find('td.result-tote-odds div.result-place', 0)->plaintext) * 10) / 10, 1);
        // $yun_ratio = round(floor(str_replace(array("$", " ", ","), "", $value->find('td.result-fixed-odds div.result-place', 0)->plaintext) * 10) / 10, 1);
        // echo $yun_ratio . PHP_EOL;
        if ($place == 1) {
            $place1 .= ' ' . $entry_no;
            $dan[] = array(
                $entry_no,
                $dan_ratio
            );
            if ($yun_ratio) {
                $yun[] = array(
                    $entry_no,
                    $yun_ratio
                );
            }
        } else if ($place == 2) {
            $place2 .= ' ' . $entry_no;
            if ($yun_ratio) {
                $yun[] = array(
                    $entry_no,
                    $yun_ratio
                );
            }
        } else if ($place == 3) {
            $place3 .= ' ' . $entry_no;
            if ($yun_ratio) {
                $yun[] = array(
                    $entry_no,
                    $yun_ratio
                );
            }
        }
    }
    $place1 = trim($place1);
    $place2 = trim($place2);
    $place3 = trim($place3);
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
    $odds_bokyun = '';
    $trs = $tables[1]->find('tbody tr');
    foreach ($trs as $key => $value) {
        $type = trim($value->find('td', 0)->plaintext);
        // echo $type . PHP_EOL;
        $select_no = $value->find('div.result-pool-name', 0)->plaintext;
        if (strpos($select_no, 'F') > -1) {
            continue;
        }
        // echo $select_no . PHP_EOL;
        $type_ratio = round(floor(str_replace(array(
            "$",
            " ",
            ","
        ), "", $value->find('div.result-pool-odds', 0)->plaintext) * 10) / 10, 1);
        // echo $type_ratio . PHP_EOL;

        switch ($type) {
            case 'Quinella':
                $select_no = explode('-', $select_no);
                sort($select_no, SORT_NUMERIC);
                $select_no = implode('-', $select_no);
                $bok[] = array(
                    $select_no,
                    $type_ratio
                );
                break;
            case 'Exacta':
                $ssang[] = array(
                    $select_no,
                    $type_ratio
                );
                break;
            case 'Trifecta':
                $samssang[] = array(
                    $select_no,
                    $type_ratio
                );
                break;
            case 'Duet':
                $select_no = explode('-', $select_no);
                sort($select_no, SORT_NUMERIC);
                $select_no = implode('-', $select_no);
                $bokyun[] = array(
                    $select_no,
                    $type_ratio
                );
                $odds_bokyun .= ' ' . number_format($type_ratio, 1, '.', '');
                break;
                // case 'First Four':
                // $sassang[] = array($select_no, $type_ratio);
                // break;
        }
    }
    print_r($bok);
    print_r($ssang);
    print_r($samssang);
    print_r($bokyun);
    print_r($sassang);
    if (count($bok) < 1) {
        return false;
    }
    $odds_bokyun = trim($odds_bokyun);
    //     if ($odds_bokyun ==  0) {
    //         $odds_bokyun = '';
    //     }
    $rowData = array(
        $dan,
        $yun,
        $bok,
        $ssang,
        $bokyun,
        $sambok,
        $samssang
    ); // , $sassang);
    $headerNames = [
        '단승',
        '연승',
        '복승',
        '쌍승',
        '복연승',
        '삼복승',
        '삼쌍승'
    ]; // , '사쌍승'];

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
            // echo $type . $place_1 . $place_2 .$place_3;
            // $sql = 'INSERT INTO `result` (`race_id`, `type`, `place_1`, `place_2`, `place_3`, `odds`) VALUES (' . $race_id . ', \'' . $type . '\' , ' . $place_1 . ',' . $place_2 . ',' . $place_3 . ',' . $b . ')';
            $sql = 'INSERT INTO `result` (`race_id`, `type`, `place_1`, `place_2`, `place_3`, `odds`) SELECT ' . $race_id . ', \'' . $type . '\' , ' . $place_1 . ',' . $place_2 . ',' . $place_3 . ',' . $b . ' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `result` WHERE `race_id`= ' . $race_id . ' and  `type` = \'' . $type . '\' and  `place_1` = ' . $place_1 . ' and  `place_2` = ' . $place_2 . ' and `place_3` = ' . $place_3 . ')';
            echo $sql;
            // print_r($sql);
            // echo '<br>';

            $ok = insert_sql($sql);
        }
        echo $i . ':' . $ok . PHP_EOL;
    }

    // $sql = "SELECT * FROM `view_place_result` WHERE race_id = " . $race_id;
    // $results = select_sql( $sql );
    // print_r($results);
    // print_r($results[0]->race_id);
    // print_r('<br>p1='.$results[0]->place_1);
    // foreach(explode(' ',$results[0]->place_1) as $p){
    $place1 = trim($place1);
    $place2 = trim($place2);
    $place3 = trim($place3);
    $oe = 0;
    $p1 = explode(' ', $place1);
    print_r($p1);

    $oe += array_sum($p1);

    // $p1c = count($p1);
    sort($p1, SORT_NUMERIC);
    $p1 = implode(' ', $p1);
    echo 'race_id=' . $race_id;
    echo 'p1=' . $p1;
    echo 'p1c=' . $p1c;

    if ($place2) {
        $p2 = explode(' ', $place2);
        $oe += array_sum($p2);
        // $p2c = count($p2);
        sort($p2, SORT_NUMERIC);
        $p2 = implode(' ', $p2);
    } else {
        $p2 = '';
    }

    // if ($p1c < 2) {
    // $p2 = explode(' ', $place2);
    // $oe += array_sum($p2);
    // $p2c = count($p2);
    // sort($p2, SORT_NUMERIC);
    // $p2 = implode(' ', $p2);
    // } else {
    // $p2 = '';
    // $p2c = 0;
    // }
    echo 'p2=' . $p2;
    echo 'p2c=' . $p2c;
    if ($place3) {
        $p3 = explode(' ', $place3);
        $oe += array_sum($p3);
        // $p3c = count($p3);
        sort($p3, SORT_NUMERIC);
        $p3 = implode(' ', $p3);
    } else {
        $p3 = '';
    }
    // if ($p2c + $p2c > 3) {
    // $p3 = '';
    // } else {
    // $p3 = $place3;
    // }
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

    // foreach ($dan as $i => $v) {
    // $odds_dan .= ' ' . $v[1];
    // }
    // $odds_dan = trim($odds_dan) ? trim($odds_dan) : '';
    // foreach ($yun as $i => $v) {
    // $odds_yun .= ' ' . $v[1];
    // }
    // $odds_yun = trim($odds_yun) ? trim($odds_yun) : '';
    // foreach ($bok as $i => $v) {
    // $odds_bok .= ' ' . $v[1];
    // }
    // $odds_bok = trim($odds_bok) ? trim($odds_bok) : '';
    // foreach ($ssang as $i => $v) {
    // $odds_ssang .= ' ' . $v[1];
    // }
    // $odds_ssang = trim($odds_ssang) ? trim($odds_ssang) : '';
    // foreach ($bokyun as $i => $v) {
    // $odds_bokyun .= ' ' . $v[1];
    // }
    // $odds_bokyun = trim($odds_bokyun) ? trim($odds_bokyun) : '';
    // foreach ($sambok as $i => $v) {
    // $odds_sambok .= ' ' . $v[1];
    // }
    // $odds_sambok = trim($odds_sambok) ? trim($odds_sambok) : '';
    // foreach ($samssang as $i => $v) {
    // $odds_samssang .= ' ' . $v[1];
    // }
    // $odds_samssang = trim($odds_samssang) ? trim($odds_samssang) : '';

    $sql = "SELECT * FROM `view_place_result` WHERE race_id = " . $race_id;
    $results = select_sql($sql);

    $odds_dan = $results[0]->dan;
    $odds_yun = $results[0]->yun;
    $odds_bok = $results[0]->bok;
    $odds_ssang = $results[0]->ssang;
    //$odds_bokyun = $results[0]->bokyun;
    $odds_sambok = $results[0]->sambok;
    $odds_samssang = $results[0]->samssang;
    /*
     * echo '<br>$odds_dan' . $odds_dan;
     * echo '<br>$odds_yun' . $odds_yun;
     * echo '<br>$odds_bok' . $odds_bok;
     * echo '<br>$odds_ssang' . $odds_ssang;
     * echo '<br>$odds_bokyun' . $odds_bokyun;
     * echo '<br>$odds_sambok' . $odds_sambok;
     */

    $sql = "select group_concat(DISTINCT `type`,':',`result` separator ' ') as `odds_all` from `view_result` where race_id = " . $race_id;
    $results = select_sql($sql);
    $odds_all = $results[0]->odds_all;

    $sql = "UPDATE `race` SET `stat`='E', place_1 = '" . $p1 . "', place_2 = '" . $p2 . "', place_3 = '" . $p3 . "', place_oe = '" . $oe . "', odds_dan = '" . $odds_dan . "', odds_yun = '" . $odds_yun . "', odds_bok = '" . $odds_bok . "', odds_ssang = '" . $odds_ssang . "', odds_bokyun = '" . $odds_bokyun . "',   odds_sambok = '" . $odds_sambok . "',   odds_samssang = '" . $odds_samssang . "',   odds_all = '" . $odds_all . "' WHERE stat = 'P' and `id`= " . $race_id;
    print_r($sql);
    $ok = insert_sql($sql);
    echo $ok . PHP_EOL;
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
try {
    $casper = new Casper();
    $casper->setOptions(array(
        'ignore-ssl-errors' => 'yes',
        'loadImages' => 'false',
        'disk-cache' => 'true',
        'disk-cache-path' => '/tmp/phantomjs_cache_race_result_os_new'
        //         'max-disk-cache-size' => '10000'
    ));
    $casper->setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36');
} catch (Exception $e) {
    echo 'Exception->' . $e->getMessage() . PHP_EOL;
    exit;
}

while (true) {
    //     $sql = "SELECT * FROM race WHERE race.start_time < now() and race.start_time > date_add(date(now()),INTERVAL -20 HOUR) and race.association_code in ('osr','osh','osg') and stat = 'P' order by start_time asc;";
    $sql = "SELECT * FROM race WHERE race.start_time < now() and race.start_date = '" . $race_day . "' and race.association_code in ('osr','osh','osg') and stat = 'P' order by start_time asc;";
    $race = select_sql($sql);
    // print_r($race);
    if ($race) {
        foreach ($race as $i => $v) {
            $url = 'https://www.tab.com.au/racing/' . $v->start_date . '/' . $v->place_name . '/' . str_replace($v->association_code . '_', '', $v->place_code) . '/' . strtoupper(str_replace('os', '', $v->association_code)) . '/' . $v->race_no;
            echo $url . PHP_EOL;
            try {
                get_race_data_to_json($casper, $url, $v->id, $v->place_code, $v->race_no, $v->association_code);
            } catch (Exception $e) {
                echo 'Exception->' . $e->getMessage() . PHP_EOL;
                exit;
            }
        }
    }/*  else {
        sleep(60);
    } */

    echo 'Restart 30 second later' . PHP_EOL;
    sleep(30);
}
