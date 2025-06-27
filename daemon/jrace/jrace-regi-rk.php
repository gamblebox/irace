<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require_once('/srv/irace/daemon/common/pdo-tool.php');
require_once('/srv/irace/daemon/common/config-db.php');
function get_race_data_to_json($url)
{
    global $data;
    echo $url . PHP_EOL;
    // $place_own_id = explode( 'babaCode=', $url);
    $place_own_id = substr($url, -10, 2);
    echo $place_own_id . PHP_EOL;
    $rk_race_code = substr($url, -8, 6);
    echo $rk_race_code . PHP_EOL;

    $dom = new DomDocument();

    // 실행
    $dom->loadHtmlFile($url);
    // echo 'saveHTML' . $dom->saveHTML() . PHP_EOL;
    $html = strtolower(substr($dom->saveHTML(), -6, 4));
    echo $html . PHP_EOL;
    if ($html !== 'html') {
        return false;
    }
    echo 'ok' . PHP_EOL;
    // do {
    //     $dom->loadHtmlFile($url);
    // } while ( trim(substr( $dom->saveHTML(), -8 )) != '</html>' );

    $xpath = new DomXPath($dom);

    // collect header names
    $headerNames = [
        'own_id',
        'rk_race_code',
        'race_no',
        'start_time',
        'length',
        'entry_count'
    ]; // ,'race_class','title','result','result_table'];
    // print_r($xpath);

    $tbody = $xpath->query('//tbody[@class="raceState"]');
    // print_r($tbody);
    foreach ($xpath->query('tr', $tbody[0]) as $index => $node) {
        $rowData = array();
        $rowData[] = $place_own_id;
        $rowData[] = $rk_race_code;
        $rowData[] = '' . ($index + 1);
        foreach ($xpath->query('td', $node) as $cell) {
            $rowData[] = trim($cell->nodeValue);
        }
        // print_r($rowData);
        $rowData = array_slice($rowData, 0, 7);
        // print_r($rowData);
        $rowData[6] = substr($rowData[6], 0, -3);
        // print_r($rowData);
        unset($rowData[4]);
        // print_r($rowData);
        $data[] = array_combine($headerNames, $rowData);
    }
    // print_r($data);
    // echo json_encode($data, JSON_UNESCAPED_UNICODE);
}

$database = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';port=' . $port . ';charset=utf8mb4', $user, $password, array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
));

$today = date('Ymd');
$tomorrow = date('Ymd', strtotime(date('Ymd') . '+' . '1' . ' days')); // 1일 후                                                                     

echo $tomorrow . PHP_EOL;
$get_date = $tomorrow;
if ($argv[1] == 'today') {
    $get_date = $today;
}

// $url = 'https://keiba.rakuten.co.jp/race_card/list/RACEID/201603222218180200';
$date_url = 'https://keiba.rakuten.co.jp/race_card/list/RACEID/' . $get_date . '0000000000';

echo $date_url . PHP_EOL;
$dom = new DomDocument();
$dom->loadHtmlFile($date_url);
$xpath = new DomXPath($dom);
// a/@href
// *[@id="raceMenu"]
//*[@id="raceMenu"]/ul/li[5]/a
$alinks = $xpath->query('//*[@id="raceMenu"]//a/@href');
print_r($alinks[0]->value);

$data = array();
foreach ($alinks as $alink) {
    $url = 'https://keiba.rakuten.co.jp/' . $alink->value;
    $url = $alink->value;
    get_race_data_to_json($url);
}
// print_r($data);
// $date = substr($url, -18, 8 );
$date = preg_replace("/([\d]{4})([\d]{2})/", "$1-$2-$3", $get_date);
for ($i = 0; $i < count($data); $i++) {
    $data[$i]['start_time'] = $date . ' ' . $data[$i]['start_time'] . ':00';
    $data[$i]['length'] = preg_replace('/[^0-9]*/s', '', $data[$i]['length']);
}
print_r($data);

// insert
// INSERT INTO `race` (`place_id`, `rk_race_code`, `race_no`, `start_time`, `race_length`, `entry_count`) SELECT (select `id` from `place` where `own_id`=23),200301,10,'2016-05-09 16:40:00',1400,8 FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race` WHERE `place_id`= (select `id` from `place` where `own_id`=23) and `race_no` = 10 and day(`start_time`) = day('2016-05-09 16:40:00') )
echo PHP_EOL;
foreach ($data as $i => $r) {
    $sql = "INSERT INTO `race` (`place_id`, `association_code`, `rk_race_code`, `race_no`, `start_date`, `start_time`, `race_length`, `entry_count`) SELECT (select `id` from `place` where `own_id`=" . $r['own_id'] . "), 'jrace', '" . $r['rk_race_code'] . "'," . $r['race_no'] . ", date('" . $r['start_time'] . "'),'" . $r['start_time'] . "'," . $r['length'] . "," . $r['entry_count'] . " FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race` WHERE `place_id`= (select `id` from `place` where `own_id`=" . $r['own_id'] . ") and  `race_no` = " . $r['race_no'] . " and  date(`start_time`) = date('" . $r['start_time'] . "') )";
    print_r($sql);

    $result = exec_query_msg($database, $sql);
    echo $i . ':' . PHP_EOL;
    print_r($result);
}
// print_r($sql);
