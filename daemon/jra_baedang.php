<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require "/srv/krace/vendor/autoload.php";

use Browser\Casper;
// use Sunra\PhpSimple\HtmlDomParser;
use voku\helper\HtmlDomParser;

$log_filename = __DIR__ . '/../../jra_baedang.log';

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
    // return '2020-09-27';
}

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

function get_race_all_odds($race)
{
    print_r($race);

    $data = array();
    $base_url = 'https://www.jra.go.jp';

    // pw01bmd1007202002060120200926/8E
    echo '$race ' . $race->rk_race_code . PHP_EOL;
    // exit();
    // doAction('/JRADB/accessO.html','pw15oli00/6D');return false;
    $html = file_get_contents_post($base_url . '/JRADB/accessO.html', array('cname' => 'pw15oli00/6D'));
    $dom = HtmlDomParser::str_get_html($html);
    $as = $dom->find('#main>div.thisweek div.content>div>div a');
    // print_r($as);
    // exit();
    // print_r(explode('\'', $as[0]->attr['onclick']));
    // exit();

    $cnames = array();
    foreach ($as as $key => $a) {
        $onclick = explode('\'', $a->attr['onclick']);
        $cnames[] = $onclick[3];
    }
    // print_r($cnames);
    // exit();

    $prefix = 'pw15orl00';
    $place_code = str_replace('jra_', '', $race->place_code);
    $place_round = substr($race->rk_race_code, 11, 8);
    $race_no = str_pad($race->race_no, 2, "0", STR_PAD_LEFT);
    $race_day = str_replace('-', '', $race->start_date);

    $cname = $prefix . $place_code . $place_round . $race_day;
    $url = $base_url . '/JRADB/accessO.html';
    echo '$url=>' . $url . PHP_EOL;
    echo '$cname=>' . $cname . PHP_EOL;
    // exit();

    foreach ($cnames as $key => $value) {
        if (substr($value, 0, -3) == $cname) {
            $cname = $value;
            break;
        }
    }
    echo '$cname=>' . $cname . PHP_EOL;
    // exit();

    $html = file_get_contents_post($url, array('cname' => $cname));
    // echo '$html=>' . $html . PHP_EOL;
    // exit();

    $dom = HtmlDomParser::str_get_html($html);
    $lis = $dom->find('#race_list tr:nth-child(' . (int)$race->race_no . ') td.odds>div>div');

    // print_r($lis);
    // exit();

    if (count($lis) < 1) {
        echo 'No Data !!' . PHP_EOL;
        return;
    }


    $etype2ktype = array(
        'umaren' => '복승',
        'umatan' => '쌍승',
        'trio' => '삼복승',
        'tierce' => '삼쌍승',
        'wide' => '복연승',
        'tanpuku' => '단승',
    );

    $etype2code = array(
        'umaren' => '2q',
        'umatan' => '2e',
        'trio' => '3q',
        'tierce' => '3e',
        'wide' => '2w',
        'tanpuku' => '1w',
    );

    foreach ($lis as $key => $li) {
        $a = $li->find('a', 0);
        if (!$a) {
            echo 'No Data !!' . PHP_EOL;
            continue;
        }
        $onclick = explode('\'', $a->attr['onclick']);
        $cname = $onclick[3];
        $url = $base_url . '/JRADB/accessO.html';
        $html = file_get_contents_post($url, array('cname' => $cname));
        if (!$html) {
            echo 'No Data !!' . PHP_EOL;
            continue;
        }
        $dom = HtmlDomParser::str_get_html($html);
        $odds_div = $dom->find('#odds_list', 0);
        if (!$odds_div) {
            echo 'No Data !!' . PHP_EOL;
            continue;
        }
        // echo '$odds_div=>' . $odds_div . PHP_EOL;
        // exit();

        $type = $li->attr['class'];
        $ktype = $etype2ktype[$type];

        /*         switch ($ktype) {
            case '복승':
            case '복연승':
                $tables = $odds_div->find('ul > li > table');
                print_r($tables);
                exit();
                break;
            case '쌍승':
                # code...
                break;
            default:
                # code...
                break;
        }

        exit(); */

        $odds_div = str_replace("___SIMPLE_HTML_DOM__VOKU__AT____", "", $odds_div);
        $odds_div = str_replace("'", "\\'", $odds_div);

        $race_id_type = $race->id . '_' . $etype2code[$type];

        $sql = "REPLACE INTO `os_odds` (`race_id_type`,`race_id`, `type`, `data`)  VALUES ('" . $race_id_type . "','" . $race->id . "','" . $ktype . "','" . $odds_div . "')";
        // echo $sql . PHP_EOL;
        $ok = insert_sql($sql);
        echo $ok . PHP_EOL;
        // exit();
    }
}

function select_sql($sql)
{
    include __DIR__ . '/../../../application/configs/configdb.php';

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


while (true) {
    $sql = "SELECT r.id, r.rk_race_code, r.association_code, r.place_name, p.place_code, r.start_date, r.start_time, r.race_no, p.name as place_name FROM race r left join place p on r.place_id = p.id WHERE start_time < date_add(now(), INTERVAL " . $interval . ") and start_time > date_add(now(), INTERVAL -5 minute) and r.association_code in ('jra') and r.stat = 'P' order by r.start_time asc;";
    // $sql = "SELECT r.id, r.rk_race_code, r.association_code, r.place_name, p.place_code, r.start_date, r.start_time, r.race_no, p.name as place_name FROM race r left join place p on r.place_id = p.id WHERE r.association_code in ('jra') order by r.start_time asc;";
    $races = select_sql($sql);
    // print_r($races);
    // exit();

    if ($races) {
        foreach ($races as $race) {
            get_race_all_odds($race);
        }
        echo date('Y-m-d H:i:s') . ' Work OK!' . PHP_EOL;
        sleep(30);
    } else {
        echo 'Nothing to do ...' . PHP_EOL;
        sleep(60);
    }
}
