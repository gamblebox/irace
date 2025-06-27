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

function get_race_data_to_json($sub_url, $cname, $remark)
{
    global $data;
    $base_url = 'https://www.jra.go.jp';
    $url = $base_url . $sub_url;
    echo '$url=>' . $url . PHP_EOL;
    echo '$cname=>' . $cname . PHP_EOL;
    // exit();

    $html = file_get_contents_post($url, array('cname' => $cname));
    // echo '$html=>' . $html . PHP_EOL;
    // exit();

    $dom = HtmlDomParser::str_get_html($html);

    // collect header names
    $headerNames = [
        'own_id',
        'rk_race_code',
        'race_no',
        'start_time',
        'length',
        'entry_count',
        'remark'
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
        $rowData[] = substr($cname, 9, 2) + 130000;


        $tds = $tr->find('th,td');
        // $onclick = $tds[0]->find('a', 1);
        // print_r($tds[0]->find('a'));
        // exit();

        // $onclick = explode('\'', $tds[6]->findOne('a')->attr['onclick']);
        //pw01dde0104202203050120220827/E0
        $tr_href = $tds[0]->find('a', 0)->attributes[0]->nodeValue;
        $tr_cname = explode('=', $tr_href)[1];
        $tr_info = explode('/', $tr_cname)[0];
        // print_r($tr_info);
        // exit();
        // $tr_cname = explode('\'', $tds[6]->findOne('a')->attr['onclick']);

        $rowData[] = $tr_cname;
        $race_no = substr($tr_info, -10, 2);
        // print_r($race_no);
        // exit();
        $rowData[] = $race_no;
        $startTime = date('Y-m-d', strtotime(substr($tr_info, -8))) . ' ' . str_replace(array('時', '分'), ':', $tds[1]->textContent) . '00';
        // print_r($startTime);
        // exit();
        $rowData[] = $startTime;
        // $entry_count = str_replace('頭', '', $tds[6]->textContent);
        $entry_count = str_replace('頭', '', $tds[4]->find('span.num')[0]->textContent);
        $rowData[] = str_replace(array(',', 'm'), '', $tds[4]->find('span.dist')[0]->textContent);
        $rowData[] = $entry_count;
        $rowData[] = $remark;

        $data[] = array_combine($headerNames, $rowData);
    }
    // print_r($data);
    // exit();
    // echo json_encode($data, JSON_UNESCAPED_UNICODE);
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
//doAction('/JRADB/accessD.html','pw01dli00/F3');return false;

$html = file_get_contents_post('https://www.jra.go.jp/JRADB/accessD.html', array('cname' => 'pw01dli00/F3'));
// echo $html;

$dom = HtmlDomParser::str_get_html($html);
$as = $dom->find('#main div:not(.win5) div a');
// print_r($as[0]->textContent);
// exit();
foreach ($as as $key => $a) {
    // $remark = trim(explode(' ', $a->textContent)[0]);
    $remark = trim(str_replace('馬番確定', '', $a->textContent));
    $span = $a->find('span.umaban', 0);
    // print_r($span->textContent);
    if ($span->textContent != '馬番確定') {
        continue;
    }

    // echo '$onclick=>' . $a->attr['onclick'];
    $onclick = explode('\'', $a->attr['onclick']);
    // echo '$onclick=>' . $onclick;
    get_race_data_to_json($onclick[1], $onclick[3], $remark);
}
// exit();
// print_r($data);
// exit();
// $date = substr($url, -18, 8 );

echo json_encode($data, JSON_UNESCAPED_UNICODE);
echo PHP_EOL;

foreach ($data as $i => $r) {
    $sql = "INSERT INTO `race` (`place_id`, `association_code`, `rk_race_code`, `race_no`, `start_date`, `start_time`, `race_length`, `entry_count`, `remark`) SELECT (select `id` from `place` where `own_id`=" . $r['own_id'] . "), 'jra', '" . $r['rk_race_code'] . "'," . $r['race_no'] . ", date('" . $r['start_time'] . "'),'" . $r['start_time'] . "'," . $r['length'] . "," . $r['entry_count'] . ",'"  . $r['remark'] . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race` WHERE `place_id`= (select `id` from `place` where `own_id`=" . $r['own_id'] . ") and  `race_no` = " . $r['race_no'] . " and  date(`start_time`) = date('" . $r['start_time'] . "') )";
    echo $sql . PHP_EOL;
    $ok = insert_sql($sql);
    echo $i . ':' . $ok . PHP_EOL;
}
// print_r($sql);
