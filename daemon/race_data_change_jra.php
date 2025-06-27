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

function get_race_data_to_json($sub_url, $cname)
{
    global $data;
    $base_url = 'https://www.jra.go.jp';
    $url = $base_url . $sub_url;
    // echo '$url=>' . $url . PHP_EOL;
    $html = file_get_contents_post($url, array('cname' => $cname));
    // echo '$html=>' . $html . PHP_EOL;
    $dom = HtmlDomParser::str_get_html($html);

    // collect header names
    $headerNames = [
        'own_id',
        'rk_race_code',
        'race_no',
        'start_time',
        'length',
        'entry_count'
    ];
    //html/body/main/div/div/div/div[2]/div[3]/table/tbody[1]/tr
    $trs = $dom->find('#race_list tbody > tr');
    // print_r($trs);
    // exit();

    if (count($trs) < 1) {
        echo 'No Data !!' . PHP_EOL;
        return;
    }
    // exit();
    foreach ($trs as $index => $tr) {
        $rowData = array();
        $rowData[] = substr($cname, 8, 3) + 130000;


        $tds = $tr->find('th,td');
        // $onclick = $tds[0]->find('a', 1);
        // print_r($tds[0]->find('a'));
        // exit();

        $onclick = explode('\'', $tds[0]->findOne('a')->attr['onclick']);
        $rowData[] = $onclick[3];
        $race_no = substr($onclick[3], 19, 2);
        $rowData[] = $race_no;
        $rowData[] = date('Y-m-d', strtotime(substr($onclick[3], 21, 8))) . ' ' . str_replace(array('時', '分'), ':', $tds[1]->textContent) . '00';
        $entry_count = str_replace('頭', '', $tds[6]->textContent);
        $rowData[] = str_replace(array(',', 'メートル'), '', $tds[4]->textContent);
        $rowData[] = $entry_count;

        $data[] = array_combine($headerNames, $rowData);
    }
    // print_r($data);
    // exit();
    // echo json_encode($data, JSON_UNESCAPED_UNICODE);
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
    include(__DIR__ . '/../../../application/configs/configdb.php');

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
$today = date('Ymd');
$tomorrow = date('Ymd', strtotime(date(Ymd) . '+' . '1' . ' days')); // 1일 후                                                                     
echo $tomorrow . PHP_EOL;
$get_date = $today;
if ($argv[1] == 'today') {
    $get_date = $today;
}

$data = array();
//return doAction('/JRADB/accessI.html','pw01ide01/4F')

$html = file_get_contents_post('https://www.jra.go.jp/JRADB/accessI.html', array('cname' => 'pw01ide01/4F'));
// echo '$html=>' . $html . PHP_EOL;

$dom = HtmlDomParser::str_get_html($html);
$trs = $dom->find('body > table > tr > td > table:nth-child(3) > tr > td:nth-child(2) > table:nth-child(6) > tr');

// $trss = array();
// $trss[] = $dom->find('body > table > tr > td > table:nth-child(3) > tr > td:nth-child(2) > table:nth-child(6) > tr');
// print_r($trs1);
// exit();

// $trss[] = $dom->find('body > table > tr > td > table:nth-child(3) > tr > td:nth-child(2) > table:nth-child(10) > tr');
// $trs = array_merge($trs1, $trs2);

// print_r($trs);
// exit();
$code = [
    '騎手変更' => '기수변경',
    '出走取消' => '출전취소',
    '競走除外' => '출전제외',
    '発走時刻変更' => '출발시각변경'
];
$headerNames = [
    'place_id',
    'start_date',
    'race_no',
    'entry_no',
    'type',
    'memo',
    'old_start_time',
    'new_start_time'
];

// foreach ($trss as $key => $trs) {
foreach ($trs as $key => $tr) {
    $tds = $tr->find('td');
    // print_r($tds);
    for ($i = 0; $i < count($tds); $i += 2) {
        if ($key == 0) {
            if ($tds[$i]->textContent == 'R') {
                $race[$i] = $tds[$i + 1]->textContent;
                $sql = "select place_id, start_date from race where association_code = 'jra' and start_date >= date(now()) and remark = '" . $race[$i] . "' limit 1";
                // echo $sql;
                $result = select_sql($sql)[0];
                // print_r($result);
                if (!$result) {
                    break;
                }
                $race_place_id[$i] = $result->place_id;
                $race_start_date[$i] = $result->start_date;
                // exit();
            }
            /* if ($tds[2]->textContent == 'R') {
                    $race_2 = $tds[3]->textContent;
                    $sql = "select place_id, start_date from race where association_code = 'jra' and start_date >= date(now()) and remark = '" . $race_2 . "' limit 1";
                    // echo $sql;
                    $result = select_sql($sql)[0];
                    print_r($result);
                    if (!$result) {
                        break;
                    }
                    $race_2_place_id = $result->place_id;
                    $race_2_start_date = $result->start_date;
                }
                if ($tds[4]->textContent == 'R') {
                    $race_2 = $tds[5]->textContent;
                    $sql = "select place_id, start_date from race where association_code = 'jra' and start_date >= date(now()) and remark = '" . $race_2 . "' limit 1";
                    // echo $sql;
                    $result = select_sql($sql)[0];
                    print_r($result);
                    if (!$result) {
                        break;
                    }
                    $race_3_place_id = $result->place_id;
                    $race_3_start_date = $result->start_date;
                } */
        } else {
            if ($tds[$i + 1]->textContent) {
                $change_race_no = $tds[$i]->textContent;
                $change_infos = explode('<br>', $tds[$i + 1]->innerHTML);
                echo '$change_race_no=>' . $change_race_no . PHP_EOL;
                print_r($change_infos);
                foreach ($change_infos as $key => $change_info) {
                    $entry_no = 0;
                    if (strpos($change_info, '<strong>') !== false) {
                        echo '$change_info=>' . $change_info . PHP_EOL;
                        $e_type = str_replace(array('<strong>', '</strong>'), '', $change_info);
                        $type = $code[$e_type];
                        switch ($type) {
                            case '출전제외':
                            case '출전취소':
                                $entry_no = trim(explode('番', $change_infos[$key + 1])[0]);
                                $memo = $entry_no . '번마 ' .  $type;
                                $data[] = array_combine($headerNames, array($race_place_id[$i], $race_start_date[$i], $change_race_no,  $entry_no, $type, $memo, $race_start_date[$i], $race_start_date[$i]));
                                break;

                            case '출발시각변경':
                                $new_start_time = trim(explode('→', $change_infos[$key + 1])[1]);
                                $memo = '출발시각변경' . ' => ' . $new_start_time;
                                $data[] = array_combine($headerNames, array($race_place_id[$i], $race_start_date[$i], $change_race_no,  $entry_no, $type, $memo, $race_start_date[$i], $race_start_date[$i] . ' ' . $new_start_time));
                                break;

                            default:
                                # code...
                                break;
                        }
                    }
                }
            }
            /* if ($tds[3]->textContent) {
                    $change_race_no = $tds[2]->textContent;
                    $change_infos = explode('<br>', $tds[3]->innerHTML);
                    foreach ($change_infos as $key => $change_info) {
                        $entry_no = 0;
                        if (strpos($change_info, '<strong>') !== false){
                            $e_type = str_replace(array('<strong>','</strong>'), '', $change_info);
                            $type = $code[$e_type];
                            switch ($type) {
                                case '출전제외':
                                case '출전취소':
                                    $entry_no = trim(explode('番', $change_infos[$key + 1])[0]);
                                    $memo = $entry_no . '번마 ' .  $type;
                                    $data[] = array_combine($headerNames,array($race_2_place_id, $race_2_start_date, $change_race_no,  $entry_no, $type, $memo, $race_2_start_date, $race_2_start_date));
                                    break;

                                case '출발시각변경':
                                    $new_start_time = trim(explode('→', $change_infos[$key + 1])[1]);
                                    $memo = '출발시각변경' . ' => ' . $new_start_time;
                                    $data[] = array_combine($headerNames,array($race_2_place_id, $race_2_start_date, $change_race_no,  $entry_no, $type, $memo, $race_2_start_date, $race_2_start_date . ' ' . $new_start_time));
                                    break;

                                default:
                                    # code...
                                    break;
                            }
                        }
                    }
                }
                if ($tds[5]->textContent) {
                    $change_race_no = $tds[4]->textContent;
                    $change_infos = explode('<br>', $tds[5]->innerHTML);
                    foreach ($change_infos as $key => $change_info) {
                        $entry_no = 0;
                        if (strpos($change_info, '<strong>') !== false){
                            $e_type = str_replace(array('<strong>','</strong>'), '', $change_info);
                            $type = $code[$e_type];
                            switch ($type) {
                                case '출전제외':
                                case '출전취소':
                                    $entry_no = trim(explode('番', $change_infos[$key + 1])[0]);
                                    $memo = $entry_no . '번마 ' .  $type;
                                    $data[] = array_combine($headerNames,array($race_3_place_id, $race_3_start_date, $change_race_no,  $entry_no, $type, $memo, $race_3_start_date, $race_3_start_date));
                                    break;

                                case '출발시각변경':
                                    $new_start_time = trim(explode('→', $change_infos[$key + 1])[1]);
                                    $memo = '출발시각변경' . ' => ' . $new_start_time;
                                    $data[] = array_combine($headerNames,array($race_3_place_id, $race_3_start_date, $change_race_no,  $entry_no, $type, $memo, $race_3_start_date, $race_3_start_date . ' ' . $new_start_time));
                                    break;

                                default:
                                    # code...
                                    break;
                            }
                        }
                    }
                } */
        }
    }
}
// }
print_r($data);

foreach ($data as $i => $r) {

    $place_id = $r['place_id'];
    $start_date = $r['start_date'];
    $race_no = $r['race_no'];
    $sql = "SELECT `race`.id as id, `race`.start_time, `place`.name as place_name FROM `race` left outer join `place` on `race`.place_id = `place`.id WHERE start_time >= date('" . $start_date . "') and start_time < date(date_add('" . $start_date . "', interval 1 day)) and '" . $place_id . "' = place_id and race_no = '" . $race_no . "'";

    $v = select_sql($sql);
    $race_id = $v[0]->id;
    $place_name = $v[0]->place_name;

    $sql = 'SELECT stat FROM `race` WHERE id = ' . $race_id . ' LIMIT 1';
    // print_r($sql);
    $stat = select_sql($sql);
    // if 문 레이스아디로 레이스의 상태 체크 - 완료 아닐시 아래 진행
    if ($stat[0]->stat === 'E') {
        // continue;
    }
    // INSERT INTO `goldrace`.`race_change_info` (`race_id`, `type`, `memo`, `old_start_time`, `new_start_time`) VALUES (7533, '출주취소', '어쩌구', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
    $entry_no = $r['entry_no'];
    $type = $r['type'];
    $memo = $place_name . " " . $race_no . "경주: " . $r['memo'];
    $old_start_time = $r['old_start_time'];
    $new_start_time = $r['new_start_time'];
    $sql = "INSERT INTO `race_change_info` (`association_code`, `race_id`, `entry_no`, `type`, `memo`, `old_start_time`, `new_start_time`) SELECT 'jra', " . $race_id . "," . $entry_no . ", '" . $type . "' , '" . $memo . "','" . $old_start_time . "','" . $new_start_time . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race_change_info` WHERE `race_id`= " . $race_id . " and  `entry_no` = " . $entry_no . " and  `type` = '" . $type . "' and  `memo` = '" . $memo . "' and  `old_start_time` = '" . $old_start_time . "' and `new_start_time` = '" . $new_start_time . "')";
    echo $sql . PHP_EOL;

    $ok = insert_sql($sql);
    echo $i . '->' . $ok . PHP_EOL;
}

exit();


$as = $dom->find('#main ul:not(.win5) li a');
// print_r(explode('\'', $as[0]->attr['onclick']));
foreach ($as as $key => $a) {
    $span = $a->find('span.umaban', 0);
    print_r($span);
    if ($span->textContent != '馬番確定') {
        continue;
    }
    $onclick = explode('\'', $a->attr['onclick']);
    get_race_data_to_json($onclick[1], $onclick[3]);
}

print_r($data);
// exit();
// $date = substr($url, -18, 8 );

echo json_encode($data, JSON_UNESCAPED_UNICODE);
echo PHP_EOL;

foreach ($data as $i => $r) {
    $sql = "INSERT INTO `race` (`place_id`, `association_code`, `rk_race_code`, `race_no`, `start_date`, `start_time`, `race_length`, `entry_count`) SELECT (select `id` from `place` where `own_id`=" . $r['own_id'] . "), 'jra', '" . $r['rk_race_code'] . "'," . $r['race_no'] . ", date('" . $r['start_time'] . "'),'" . $r['start_time'] . "'," . $r['length'] . "," . $r['entry_count'] . " FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race` WHERE `place_id`= (select `id` from `place` where `own_id`=" . $r['own_id'] . ") and  `race_no` = " . $r['race_no'] . " and  date(`start_time`) = date('" . $r['start_time'] . "') )";
    echo $sql . PHP_EOL;
    $ok = insert_sql($sql);
    echo $i . ':' . $ok . PHP_EOL;
}
// print_r($sql);
