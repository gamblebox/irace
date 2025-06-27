<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
// error_reporting(E_ALL);
define('MAX_FILE_SIZE', 6000000);
putenv("PHANTOMJS_EXECUTABLE=/usr/local/bin/phantomjs");

require __DIR__ . "/../../../vendor/autoload.php";
// require ( __DIR__ . "/../../../vendor/autoload.php");
// include_once( __DIR__ . '/../../application/configs/configdb.php' );
use Browser\Casper;
use Sunra\PhpSimple\HtmlDomParser;
use function simplehtmldom_1_5\str_get_html;
// include('simple_html_dom.php');

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
// print_r($argv);
$ref_data = array();
$interval = '10 minute';
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
    //     echo '$elems->' . $elems[0]->plaintext . PHP_EOL;

    $data = array();
    foreach ($elems as $e) {
        //approximate-combinations approximate-dividend
        $c = $e->find("div.approximate-combinations");
        $r = $e->find("div.approximate-dividend");
        //         echo '$c[0]->' . $c[0] . PHP_EOL;
        //         echo '$r[0]->' . $r[0] . PHP_EOL;
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

function get_race_data_to_json($casper, $url, $race)
{
    echo $start_time;
    echo $association_code;
    echo date("Y-m-d H:i:s") . 'get func' . PHP_EOL;
    global $data;
    global $interval;

    //     $url="https://www.tab.com.au/racing/2018-11-05/THE-MEADOWS/MEA/G/12";
    // $casper = new Casper();
    // // May need to set more options due to ssl issues
    // $casper->setOptions(array(
    // 'ignore-ssl-errors' => 'yes',
    // 'loadImages' => 'false',
    // ));
    //     echo '$casper ' . PHP_EOL;
    //     print_r($casper);
    $casper->start($url);
    $casper->waitForText('Last Updated:', 3000);
    $casper->run();
    $html = $casper->getHtml();
    //     echo '$html ' . $html . PHP_EOL;
    //     echo '$html ' . strlen($html) . PHP_EOL;
    //     if(strpos($html, 'functionality of this site it is necessary to enable JavaScript') !== false) {
    //         echo "포함되어 있습니다만...";
    //     } else {
    //         echo "없군요.";
    //     }
    $wp_html = explode('Last Updated', $html)[0];
    //     echo '$wp_html ' . strlen($wp_html) . PHP_EOL;
    $dom = HtmlDomParser::str_get_html($wp_html);

    $page = trim($dom->find('div.page-not-found h1', 0)->plaintext);
    echo '$page ' . $page . PHP_EOL;
    if ($page == 'Uh Oh!') {
        return;
    }
    $stat = $dom->find('div.race-info-wrapper li.status-text', 0)->plaintext;
    echo '$stat ' . $stat . PHP_EOL;
    echo $interval . PHP_EOL;
    //     if (!$stat) {
    //         $output = shell_exec('sh /root/kill_os4php.sh ' . '/tmp/phantomjs_cache_os_baedang_' . str_replace(' ', '_', $interval) );
    //         echo "<pre>$output</pre>";
    //         $output = shell_exec('php /srv/krace/application/php/admin/os_baedang.php ' . $interval . ' > /dev/null 2>/dev/null &');
    //         push_log('kill and start os_baedang.php' . $interval);
    //         echo "<pre>$output</pre>";

    //         echo 'exit php script' . PHP_EOL;
    //         sleep(10);
    //         exit('exit');
    //     }

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
    //     $time = trim($dom->find('a[class="race-link selected"] time', 0)->plaintext);
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
    //     echo $q_html . PHP_EOL;
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

//https://api.beta.tab.com.au/v1/tab-info-service/racing/dates/2019-07-06/meetings/R/RAN/races/6/pools/Exacta/approximates?jurisdiction=VIC


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
    // code...

    $sql = "SELECT r.id, r.association_code, r.place_name, r.place_code, r.start_date, r.start_time, r.race_no, p.name as place_name FROM race r left join place p on r.place_id = p.id WHERE start_time < date_add(now(), INTERVAL " . $interval . ") and start_time > date_add(now(), INTERVAL -30 minute) and r.association_code in ('osr','osh','osg') and r.stat = 'P' order by r.start_time asc;";
    $race = select_sql($sql);
    // print_r($race);
    if ($race) {
        foreach ($race as $v) {
            $url = 'https://www.tab.com.au/racing/' . $v->start_date . '/' . $v->place_name . '/' . str_replace($v->association_code . '_', '', $v->place_code) . '/' . strtoupper(str_replace('os', '', $v->association_code)) . '/' . $v->race_no;
            echo $url . PHP_EOL;
            get_race_data_to_json($casper, $url, $v);
        }
    } else {
        sleep(60);
    }
}
// exit;

// print_r($data);
// $date = substr($url, -18, 8 );
// $date = preg_replace("/([\d]{4})([\d]{2})/", "$1-$2-$3", $get_date);
// for ($i = 0; $i < count($data); $i ++) {
// $data[$i]['start_time'] = $date . ' ' . $data[$i]['start_time'] . ':00';
// $data[$i]['length'] = preg_replace('/[^0-9]*/s', '', $data[$i]['length']);
// }
// echo json_encode($data, JSON_UNESCAPED_UNICODE);

// insert
// INSERT INTO `race` (`place_id`, `rk_race_code`, `race_no`, `start_time`, `race_length`, `entry_count`) SELECT (select `id` from `place` where `own_id`=23),200301,10,'2016-05-09 16:40:00',1400,8 FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race` WHERE `place_id`= (select `id` from `place` where `own_id`=23) and `race_no` = 10 and day(`start_time`) = day('2016-05-09 16:40:00') )
// echo PHP_EOL;
// echo json_encode($data, JSON_UNESCAPED_UNICODE);
/*
 * foreach ($data as $i => $r) {
 *
 * $sql = "INSERT INTO `race` (`place_id`, `place_name`, `association_code`, `place_code`, `race_no`, `start_time`, `race_length`, `entry_count`) SELECT (select `id` from `place` where `place_code`= '" . $r['place_code'] . "'), '" . $r['place_name'] . "', '" . $r['association_code'] . "', '" . $r['place_code'] . "'," . $r['race_no'] . ",'" . $r['start_time'] . "'," . $r['length'] . "," . $r['entry_count'] . " FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race` WHERE `place_id`= (select `id` from `place` where `place_code`= '" . $r['place_code'] . "') and `place_name` = '" . $r['place_name'] . "' and `place_code` = '" . $r['place_code'] . "' and `race_no` = " . $r['race_no'] . ")";
 * echo $sql;
 * $ok = insert_sql($sql);
 * echo $i . ':' . $ok . PHP_EOL;
 * }
 */
// print_r($sql);
