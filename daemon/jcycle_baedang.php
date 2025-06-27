<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
define('MAX_FILE_SIZE', 6000000);
putenv("PHANTOMJS_EXECUTABLE=/usr/local/bin/phantomjs");
require "/srv/krace/vendor/autoload.php";

use Browser\Casper;
use voku\helper\HtmlDomParser;

$url = 'http://keirin.jp/pc/top#';

$log_filename = __DIR__ . '/../../jcycle_baedang.log';

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
$ref_data = array();
$interval = '30 minute';
if ($argv[1] && $argv[2]) {
    $interval = $argv[1] . ' ' . $argv[2];
}
echo $interval . PHP_EOL;

function insert_qe_odds($html, $race, $type, $ktype)
{
    $race_id = $race->id;
    $race_id_type = $race_id . '_' . $type;
    $dom = HtmlDomParser::str_get_html($html);
    $elems = $dom->find("div.pseudo-body div.row");
    echo '$elems->' . count($elems) . PHP_EOL;
    // echo '$elems->' . $elems[0]->plaintext . PHP_EOL;

    $data = array();
    foreach ($elems as $e) {
        //approximate-combinations approximate-dividend
        $c = $e->find("div.approximate-combinations");
        $r = $e->find("div.approximate-dividend");
        // echo '$c[0]->' . $c[0] . PHP_EOL;
        // echo '$r[0]->' . $r[0] . PHP_EOL;
        if ($r) {
            $data[] = array(
                str_replace(" ", "", $c[0]->plaintext),
                round(floor(str_replace(array(
                    "$",
                    " ",
                    ","
                ), "", $r[0]->plaintext) * 10) / 10, 1)
            );
        }
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

function insert_wp_odds($html, $race)
{
    $dom = HtmlDomParser::str_get_html($html);
    $trs = $dom->find("div.pseudo-body div.row");
    $data_dan = array();
    $data_yun = array();
    $data_t_dan = array();
    $data_t_yun = array();
    foreach ($trs as $key => $value) {

        $entry_no = trim($value->find('div.number-cell', 0)->plaintext);
        // round(floor('123.49'*10),1)/10;
        $dan_ratio = str_replace(array(
            "$",
            " ",
            ","
        ), "", $value->find('div[data-id="fixed-odds-price"] div.animate-odd', 0)->plaintext);
        $yun_ratio = str_replace(array(
            "$",
            " ",
            ","
        ), "", $value->find('div[data-id="fixed-odds-place-price"] div.animate-odd', 0)->plaintext);
        $t_dan_ratio = str_replace(array(
            "$",
            " ",
            ","
        ), "", $value->find('div[ng-if="raceRunners.showParimutuelWin"] div.animate-odd', 0)->plaintext);
        $t_yun_ratio = str_replace(array(
            "$",
            " ",
            ","
        ), "", $value->find('animate-odds-change[current-value="runner.displayParimutuelPlace"] div.animate-odd', 0)->plaintext);

        // echo 't_yun_ratio' . $t_yun_ratio . PHP_EOL;
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
        }

        if ($dan_ratio) {
            if ($dan_ratio != 'N/A' && substr($dan_ratio, -3) != 'SCR') {
                $dan_ratio = round(floor($dan_ratio * 10) / 10, 1);
            }
            $data_dan[] = array(
                $entry_no,
                $dan_ratio
            );
        }
        if ($yun_ratio) {
            if ($yun_ratio != 'N/A' && substr($yun_ratio, -3) != 'SCR') {
                $yun_ratio = round(floor($yun_ratio * 10) / 10, 1);
            }
            $data_yun[] = array(
                $entry_no,
                $yun_ratio
            );
        }
        if ($t_dan_ratio) {
            if ($t_dan_ratio != 'N/A' && substr($t_dan_ratio, -3) != 'SCR') {
                $t_dan_ratio = round(floor($t_dan_ratio * 10) / 10, 1);
            }
            $data_t_dan[] = array(
                $entry_no,
                $t_dan_ratio
            );
        }
        if ($t_yun_ratio) {
            if ($t_yun_ratio != 'N/A' && substr($t_yun_ratio, -3) != 'SCR') {
                $t_yun_ratio = round(floor($t_yun_ratio * 10) / 10, 1);
            }
            $data_t_yun[] = array(
                $entry_no,
                $t_yun_ratio
            );
        }
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

function get_race_all_odds($url, $casper, $race)
{
    print_r($race);
    $data = array();
    echo date("Y-m-d H:i:s ") . 'casper start' . PHP_EOL;
    $casper->start($url);
    $casper->wait(1000);
    $casper->run();
    echo date("Y-m-d H:i:s ") . 'casper ran' . PHP_EOL;
    $html = $casper->getHtml();
    $dom = HtmlDomParser::str_get_html($html);
    $place_divs = $dom->find('#kaisaiInfoTable > tbody > tr > td:nth-child(1) > div > div:nth-child(1)');
    $index = 0;
    foreach ($place_divs as $key => $place_div) {
        if ($place_div->textContent == $race->place_name) {
            $index = $key + 1;
            break;
        };
    }
    $casper->click('#kaisaiInfoTable > tbody > tr:nth-child(' . $index . ') > td:nth-child(5) > button');
    $casper->wait(1000);
    $casper->click('#hhRaceBtn' . $race->race_no);
    $casper->wait(1000);
    $casper->run();
    echo date("Y-m-d H:i:s ") . 'casper ran' . PHP_EOL;
    $html = $casper->getHtml();
    $dom = HtmlDomParser::str_get_html($html);
    $entry_table_tds = $dom->find('#syusou_table > table > tbody > tr:nth-child(3) > td');
    foreach ($entry_table_tds as $entry_no => $entry_table_td) {
        if ($entry_table_td->textContent == '(欠場)') {
            echo '$entry_no=>' . $entry_no . PHP_EOL;
            $memo =  $race->place_name . " " . $race->race_no . "경주: " . $entry_no . "번 선수 출전취소";
            $sql = "INSERT INTO `race_change_info` (`association_code`, `race_id`, `entry_no`, `type`, `memo`, `old_start_time`, `new_start_time`) SELECT 'jcycle', " . $race->id . "," . $entry_no . ", '" . '출전취소' . "' , '" . $memo . "','" . $race->start_time . "','" . $race->start_time . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race_change_info` WHERE `race_id`= " . $race->id . " and  `entry_no` = " . $entry_no . " and  `type` = '" . '출전취소' . "' and  `memo` = '" . $memo . "' and  `old_start_time` = '" . $race->start_time . "' and `new_start_time` = '" . $race->start_time . "')";
            echo $sql . PHP_EOL;

            $ok = insert_sql($sql);
            echo $ok . PHP_EOL;
        }
    }

    $targets = array(
        array('btn' => '#btnKake3Rentan', 'type_code' => '3e', 'ktype' => '삼쌍승'),
        array('btn' => '#btnKake2Syatan', 'type_code' => '2e', 'ktype' => '쌍승'),
        array('btn' => '#btnKake3Renhuku', 'type_code' => '3q', 'ktype' => '삼복승'),
        array('btn' => '#btnKake2Syahuku', 'type_code' => '2q', 'ktype' => '복승'),
        array('btn' => '#btnKakeWide', 'type_code' => '2w', 'ktype' => '복연승')
    );

    foreach ($targets as $key => $target) {
        $casper->click($target['btn']);
        $casper->wait(1000);
        $casper->run();
        echo date("Y-m-d H:i:s ") . 'casper ran' . PHP_EOL;
        $html = $casper->getHtml();
        $dom = HtmlDomParser::str_get_html($html);
        $odds_table = $dom->findOne('#ozz_table > table');
        if (!$odds_table) {
            continue;
        }

        foreach ($dom->find('.nonb') as $e) {
            // $e->outertext = '<img src="foobar.png">';
            // print_r(($e));
            if (floor($e->textContent) > 0 && floor($e->textContent) < 10) {
                $e->outertext = '<div class="nonb red">' . $e->textContent . '</div>';
            } else if (floor($e->textContent) > 0 && floor($e->textContent) < 100) {
                $e->outertext = '<div class="nonb blue">' . $e->textContent . '</div>';
            }
        }
        $odds_table = $dom->findOne('#ozz_table > table');
        echo $odds_table;

        $race_id_type = $race->id . '_' . $target['type_code'];
        $ktype = $target['ktype'];
        $sql = "REPLACE INTO `jcycle_odds` (`race_id_type`,`race_id`, `type`, `data`)  VALUES ('" . $race_id_type . "','" . $race->id . "','" . $ktype . "','" . $odds_table . "')";
        // echo $sql . PHP_EOL;
        $ok = insert_sql($sql);
        echo $ok . PHP_EOL;
    }

    return;


    exit();

    $jtype2type = array(
        '2車複' => '복승',
        '2車単' => '쌍승',
        '3連複' => '삼복승',
        '3連単' => '삼쌍승',
        'ワイド' => '복연승',
    );
    // if (!$stat) {
    //     $output = shell_exec('sh /root/kill_os4php.sh ' . '/tmp/phantomjs_cache_os_baedang_' . str_replace(' ', '_', $interval) );
    //     echo "<pre>$output</pre>";
    //     $output = shell_exec('php /srv/krace/application/php/admin/os_baedang.php ' . $interval . ' > /dev/null 2>/dev/null &');
    //     push_log('kill and start os_baedang.php' . $interval);
    //     echo "<pre>$output</pre>";

    //     echo 'exit php script' . PHP_EOL;
    //     sleep(10);
    //     exit('exit');
    // }

    if ($stat == 'Abandoned') {
        $entry_no = 0;
        $type = '경주취소';
        $old_start_time = $race->start_time;
        $new_start_time = $race->start_time;

        $memo = $race->place_name . " " . $race->race_no . "경주: 경주취소";
        echo $memo . PHP_EOL;
        $sql = "INSERT INTO `race_change_info` (`association_code`,`race_id`, `entry_no`, `type`, `memo`, `old_start_time`, `new_start_time`) SELECT '" . $race->association_code . "'," . $race->id . "," . $entry_no . ", '" . $type . "' , '" . $memo . "','" . $old_start_time . "','" . $new_start_time . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race_change_info` WHERE `race_id`= " . $race->id . " and  `entry_no` = " . $entry_no . " and  `type` = '" . $type . "' and  `memo` =  '" . $memo . "' and  `old_start_time` = '" . $old_start_time . "' and `new_start_time` = '" . $new_start_time . "')";
        echo $sql;
        $ok = insert_sql($sql);
        echo $ok . PHP_EOL;
        return true;
    } else if ($stat == 'Closed' || $stat == 'All Paying' || $stat == 'Interim result') {
        return;
    }
    // $time = trim($dom->find('a[class="race-link selected"] time', 0)->plaintext);
    $time = trim($dom->find('div[data-test-race-starttime=""]', 0)->plaintext);
    echo $time  . PHP_EOL;
    if (!$time) {
        return;
    }
    $start_time = $race->start_date . ' ' . $time;
    if (substr($time, 0, 2) >= 0 && substr($time, 0, 2) < 6) {
        $start_time = date('Y-m-d H:i', strtotime(date($start_time) . '+' . '1' . ' days'));
    }
    if ($start_time . ':00' != $race->start_time) {
        echo 'time changed : ' . $race->start_time . '->' . $start_time;
        $entry_no = 0;
        $type = '출발시각변경';
        $old_start_time = substr($race->start_time, 0, -3);
        $new_start_time = $start_time;

        $memo = $race->place_name . " " . $race->race_no . "경주: 출발시각변경" . ' ' . substr($old_start_time, -5) . ' => ' . substr($new_start_time, -5);
        echo $memo . PHP_EOL;
        $sql = "INSERT INTO `race_change_info` (`association_code`,`race_id`, `entry_no`, `type`, `memo`, `old_start_time`, `new_start_time`) SELECT '" . $race->association_code . "'," . $race->id . "," . $entry_no . ", '" . $type . "' , '" . $memo . "','" . $old_start_time . "','" . $new_start_time . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race_change_info` WHERE `race_id`= " . $race->id . " and  `entry_no` = " . $entry_no . " and  `type` = '" . $type . "' and  `memo` =  '" . $memo . "' and  `old_start_time` = '" . $old_start_time . "' and `new_start_time` = '" . $new_start_time . "')";
        echo $sql;
        $ok = insert_sql($sql);
        echo $ok . PHP_EOL;
    }
    $ref_data['place_code'] = $race->place_code;
    $ref_data['start_time'] = $start_time;

    $casper->start($url . '/Quinella');
    $casper->waitForText('Last Updated:', 3000);
    $casper->click("button.toggle-flucs-button.different-button.button-inactive");
    //$casper->click("button.toggle-flucs-button.different-button.button-inactive");
    //toggle-flucs-button different-button button-inactive
    $casper->waitForText('Maximum dividend is $', 3000);
    $casper->run();
    $html = $casper->getHtml();
    $q_html = explode('Last Updated', $html)[0];
    // echo $q_html . PHP_EOL;
    $casper->start($url . '/Exacta');
    $casper->waitForText('Last Updated:', 3000);
    $casper->click("button.toggle-flucs-button.different-button.button-inactive");
    $casper->waitForText('Maximum dividend is $', 3000);
    $casper->run();
    $html = $casper->getHtml();
    $e_html = explode('Last Updated', $html)[0];
    // $casper->start($url . '/Trifecta');
    // $casper->waitForText('</noscript>', 1000);
    // $casper->click("button.toggle-flucs-button.different-button.button-inactive");
    // $casper->waitForText('Available', 1000);
    // $casper->run();
    // $t_html = $casper->getHtml();

    echo date("Y-m-d H:i:s") . 'get func run' . PHP_EOL;
    insert_wp_odds($wp_html, $race);
    insert_qe_odds($q_html, $race, 'q', '복승');
    insert_qe_odds($e_html, $race, 'e', '쌍승');
    // insert_odds($t_html, $race_id, 't', '삼쌍승');
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
// $date_url = array('https://www.tab.com.au/racing/meetings/today/R', 'https://www.tab.com.au/racing/meetings/today/H', 'https://www.tab.com.au/racing/meetings/today/G', 'https://www.tab.com.au/racing/meetings/tomorrow');
// $date_url = 'https://www.tab.com.au/racing/meetings/today/R';

// $sql = "SELECT * FROM race WHERE start_time < date_add(now(), INTERVAL " . $interval . ") and association_code in ('osr','osh','osg') and stat = 'P' order by start_time asc;";

$casper = new Casper();
$casper->setOptions(array(
    'ignore-ssl-errors' => 'yes',
    'loadImages' => 'false',
    'disk-cache' => 'true',
    'disk-cache-path' => '/tmp/phantomjs_cache_os_baedang_' . str_replace(' ', '_', $interval)
));
$casper->setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36');

while (true) {
    $sql = "SELECT r.id, r.association_code, r.place_code, r.start_date, r.start_time, r.race_no, p.e_name as place_name FROM race r left join place p on r.place_id = p.id WHERE start_time < date_add(now(), INTERVAL " . $interval . ") and start_time > date_add(now(), INTERVAL -5 minute) and r.association_code in ('jcycle') and r.stat = 'P' order by r.start_time asc;";
    $races = select_sql($sql);
    print_r($races);

    if ($races) {
        foreach ($races as $race) {
            get_race_all_odds($url, $casper, $race);
        }
        sleep(30);
    } else {
        sleep(60);
    }
}
