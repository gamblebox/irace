<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require_once('/srv/irace/daemon/common/common.php');
require_once('/srv/irace/daemon/common/pdo-tool.php');
require_once('/srv/irace/daemon/common/config-db.php');

function get_race_data_to_json($url)
{
    global $data;
    // $code=['騎手変更'=>'기수변경','出走取消'=>'출주취소','競走除外'=>'경주제외'];
    // $url='https://www2.keiba.go.jp/KeibaWeb/TodayRaceInfo/RaceList?k_babaCode=19&k_raceDate=2016/06/21';

    $place_own_id = '10' . substr($url, -1, 1);

    $dom = new DomDocument();

    // 실행
    $dom->loadHtmlFile($url);
    $html = strtolower(substr($dom->saveHTML(), -6, 4));
    echo $html . PHP_EOL;
    if ($html !== 'html') {
        return false;
    }
    echo 'ok' . PHP_EOL;

    $xpath = new DomXPath($dom);
    $headerNames = [
        'own_id',
        'type',
        'start_date',
        'race_no',
        'entry_no',
        'memo',
        'old_start_time',
        'new_start_time'
    ];

    // *[@id="contents"]/div[1]/table/tbody
    // *[@id="contents"]/div[1]/table/tbody
    // *[@id="contents"]/div[1]

    $divs = $xpath->query('//*[@id="contents"]/div');

    // for ($div = 1; $div<5; $div++){

    foreach ($divs as $div) {
        $caption = $xpath->query('table/caption', $div);
        $tbody = $xpath->query('table/tbody', $div);

        if (strpos($caption[0]->nodeValue, '말취소내용') !== false) {
            foreach ($xpath->query('tr', $tbody[0]) as $index => $node) {

                // $rData = array();
                $rowData = array();
                $rowData[] = $place_own_id;

                foreach ($xpath->query('td', $node) as $cell) {
                    $rowData[] = trim($cell->nodeValue);
                }
                print_r($rowData);
                if (!$rowData[2]) {
                    continue;
                }

                $rowData[2] = str_replace('/', '-', $rowData[2]);
                $rowData[2] = substr(preg_replace("/([\d]{4})([\d]{2})/", "$1-$2-$3", $rowData[2]), 0, 10);
                if ($rowData[2] !== date("Y-m-d")) {
                    continue;
                }
                $rowData[5] = $rowData[4] . '번마 ' . $rowData[1];
                $rowData[6] = date("Y-m-d");
                $rowData[7] = date("Y-m-d");

                $rowData = array_slice($rowData, 0, 8);

                // print_r($rowData);

                $rowData = array_values($rowData);

                $data[] = array_combine($headerNames, $rowData);
            }
        } else if (strpos($caption[0]->nodeValue, '기수변경내용') !== false) {
            foreach ($xpath->query('tr', $tbody[0]) as $index => $node) {

                // $rData = array();
                $rowData = array();
                $rowData[] = $place_own_id;

                foreach ($xpath->query('td', $node) as $cell) {
                    $rowData[] = trim($cell->nodeValue);
                }
                // print_r($rowData);
                if (strpos($rowData[1], '없습니다')) {
                    break;
                }

                $rowData[4] = $rowData[3];
                $rowData[3] = $rowData[2];
                $rowData[2] = str_replace('/', '-', $rowData[1]);
                $rowData[2] = substr(preg_replace("/([\d]{4})([\d]{2})/", "$1-$2-$3", $rowData[2]), 0, 10);
                if ($rowData[2] !== date("Y-m-d")) {
                    continue;
                }
                $rowData[1] = '기수변경';

                $rowData[5] = $rowData[4] . '번마 ' . $rowData[1];
                $rowData[6] = date("Y-m-d");
                $rowData[7] = date("Y-m-d");

                $rowData = array_slice($rowData, 0, 8);

                // print_r($rowData);

                $rowData = array_values($rowData);

                $data[] = array_combine($headerNames, $rowData);
            }
        } else if (strpos($caption[0]->nodeValue, '출발시각변경') !== false) {
            foreach ($xpath->query('tr', $tbody[0]) as $index => $node) {

                // $rData = array();
                $rowData = array();
                $rowData[] = $place_own_id;

                foreach ($xpath->query('td', $node) as $cell) {
                    $rowData[] = trim($cell->nodeValue);
                }

                $rowData[] = '';
                $rowData[] = '';
                $rowData[] = '';
                $rowData[7] = date("Y-m-d ") . $rowData[4];
                $rowData[6] = date("Y-m-d ") . $rowData[3];
                $rowData[3] = $rowData[2];
                $rowData[2] = str_replace('/', '-', $rowData[1]);
                $rowData[2] = substr(preg_replace("/([\d]{4})([\d]{2})/", "$1-$2-$3", $rowData[2]), 0, 10);
                if ($rowData[2] !== date("Y-m-d")) {
                    continue;
                }
                $rowData[1] = '출발시각변경';
                $rowData[5] = $rowData[1] . ' ' . substr($rowData[6], -5, 5) . ' => ' . substr($rowData[7], -5, 5);
                $rowData[4] = '0';

                $rowData = array_slice($rowData, 0, 8);
                // print_r($rowData);

                $rowData = array_values($rowData);

                $data[] = array_combine($headerNames, $rowData);
            }
        } else if (strpos($caption[0]->nodeValue, '경주취소내용') !== false) {
            foreach ($xpath->query('tr', $tbody[0]) as $index => $node) {

                // $rData = array();
                $rowData = array();
                $rowData[] = $place_own_id;

                foreach ($xpath->query('td', $node) as $cell) {
                    $rowData[] = trim($cell->nodeValue);
                }

                $rowData[] = '';
                $rowData[] = '';
                $rowData[] = '';
                $rowData[] = '';
                $rowData[3] = $rowData[2];
                $rowData[2] = str_replace('/', '-', $rowData[1]);
                $rowData[2] = substr(preg_replace("/([\d]{4})([\d]{2})/", "$1-$2-$3", $rowData[2]), 0, 10);
                if ($rowData[2] !== date("Y-m-d")) {
                    continue;
                }
                $rowData[1] = '경주취소';
                $rowData[5] = $rowData[1];
                $rowData[4] = '0';
                $rowData[6] = date("Y-m-d");
                $rowData[7] = date("Y-m-d");

                $rowData = array_slice($rowData, 0, 8);

                // print_r($rowData);

                $rowData = array_values($rowData);

                $data[] = array_combine($headerNames, $rowData);
            }
        }
    }
}

$data = array();
// $date_url = 'https://www2.keiba.go.jp/KeibaWeb/TodayRaceInfo/TodayRaceInfoTop';
$alinks = [
    'https://race.kra.co.kr/raceFastreport/ChulmapyoChange.do?Act=03&Sub=1&meet=1', // ];//,
    'https://race.kra.co.kr/raceFastreport/ChulmapyoChange.do?Act=03&Sub=1&meet=2',
    'https://race.kra.co.kr/raceFastreport/ChulmapyoChange.do?Act=03&Sub=1&meet=3'
];

$database = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';port=' . $port . ';charset=utf8mb4', $user, $password, array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
));

// https://race.kra.co.kr/raceFastreport/ChulmapyoChange.do?Act=03&Sub=1&meet=1
foreach ($alinks as $alink) {
    get_race_data_to_json($alink);
}
print_r($data);
// echo $data;

// 테스트
// $data = [];
// $data[] = [
//     'own_id' => 101,
//     'type' => '출전제외',
//     'start_date' => '2024-10-13',
//     'race_no' => 11,
//     'entry_no' => 3,
//     'memo' => '3번마 출전제외',
//     'old_start_time' => '2024-10-13',
//     'new_start_time' => '2024-10-13',
// ];
// $data[] = [
//     'own_id' => 101,
//     'type' => '출전제외',
//     'start_date' => '2024-10-13',
//     'race_no' => 4,
//     'entry_no' => 3,
//     'memo' => '3번마 출전제외',
//     'old_start_time' => '2024-10-13',
//     'new_start_time' => '2024-10-13',
// ];


// sql 변경 정보 삽입

$msg = array();
$headerNames = [
    'own_id',
    'start_date',
    'race_no',
    'reg_result'
];
foreach ($data as $i => $raceChange) {

    $own_id = $raceChange['own_id'];
    $start_date = $raceChange['start_date'];
    $race_no = $raceChange['race_no'];
    $sql = "SELECT `race`.id as id, `race`.association_code, `race`.start_time, `place`.name as place_name FROM `race` left outer join `place` on `race`.place_id = `place`.id WHERE start_time >= date('" . $start_date . "') and start_time < date(date_add('" . $start_date . "', interval 1 day)) and (SELECT id from place where own_id = '" . $own_id . "') = place_id and race_no = '" . $race_no . "'";

    $race = select_query_one($database, $sql);
    $race_id = $race->id;
    $raceChange['race_id'] = $race_id;
    $raceChange['association_code'] = $race->association_code;
    $place_name = $race->place_name;

    $sql = 'SELECT stat FROM `race` WHERE id = ' . $race_id . ' LIMIT 1';
    // print_r($sql);
    $stat = select_query($database, $sql);
    // if 문 레이스아디로 레이스의 상태 체크 - 완료 아닐시 아래 진행
    if ($stat[0]->stat === 'E' || $stat[0]->stat === 'C') {
        // continue;
    }
    // INSERT INTO `goldrace`.`race_change_info` (`race_id`, `type`, `memo`, `old_start_time`, `new_start_time`) VALUES (7533, '출주취소', '어쩌구', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
    $entry_no = $raceChange['entry_no'];
    $type = $raceChange['type'];
    $memo = $place_name . " " . $race_no . "경주: " . $raceChange['memo'];
    $old_start_time = $raceChange['old_start_time'];
    $new_start_time = $raceChange['new_start_time'];
    $sql = "INSERT INTO `race_change_info` (`association_code`, `race_id`, `entry_no`, `type`, `memo`, `old_start_time`, `new_start_time`) SELECT 'krace', " . $race_id . "," . $entry_no . ", '" . $type . "' , '" . $memo . "','" . $old_start_time . "','" . $new_start_time . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race_change_info` WHERE `race_id`= " . $race_id . " and  `entry_no` = " . $entry_no . " and  `type` = '" . $type . "' and  `memo` = '" . $memo . "' and  `old_start_time` = '" . $old_start_time . "' and `new_start_time` = '" . $new_start_time . "')";
    print_r($sql);
    echo PHP_EOL;

    $result = exec_query_lastId($database, $sql);
    // echo $index . '->' . $lastId . PHP_EOL;
    print_r($result);

    if ($result->id > 0) {
        updateBettingByRaceChange($database, $raceChange);
    }
}
