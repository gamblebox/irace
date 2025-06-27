<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
define('MAX_FILE_SIZE', 6000000);
putenv("PHANTOMJS_EXECUTABLE=/usr/local/bin/phantomjs");
// require __DIR__ . "/../../../vendor/autoload.php";
require "/srv/irace/vendor/autoload.php";
require_once('/srv/irace/daemon/common/pdo-tool.php');
require_once('/srv/irace/daemon/common/config-db.php');

use Browser\Casper;
// use Sunra\PhpSimple\HtmlDomParser;
use voku\helper\HtmlDomParser;

echo date("Y-m-d H:i:s") . PHP_EOL;

$database = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';port=' . $port . ';charset=utf8mb4', $user, $password, array(
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_EMULATE_PREPARES => false,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
));

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime($today . '+' . '1' . ' days'));

// if ($argv[1] == 'today') {
//   $get_date = $today;
// }

$place_code_arr = array(
  '函館' => 'jc_11',
  '青森' => 'jc_12',
  'いわき平' => 'jc_13',
  '弥彦' => 'jc_21',
  '前橋' => 'jc_22',
  '取手' => 'jc_23',
  '宇都宮' => 'jc_24',
  '大宮' => 'jc_25',
  '西武園' => 'jc_26',
  '京王閣' => 'jc_27',
  '立川' => 'jc_28',
  '松戸' => 'jc_31',
  '千葉' => 'jc_32',
  '川崎' => 'jc_34',
  '平塚' => 'jc_35',
  '小田原' => 'jc_36',
  '伊東' => 'jc_37',
  '静岡' => 'jc_38',
  '名古屋' => 'jc_42',
  '岐阜' => 'jc_43',
  '大垣' => 'jc_44',
  '豊橋' => 'jc_45',
  '富山' => 'jc_46',
  '松阪' => 'jc_47',
  '四日市' => 'jc_48',
  '福井' => 'jc_51',
  '奈良' => 'jc_53',
  '向日町' => 'jc_54',
  '和歌山' => 'jc_55',
  '岸和田' => 'jc_56',
  '玉野' => 'jc_61',
  '広島' => 'jc_62',
  '防府' => 'jc_63',
  '高松' => 'jc_71',
  '小松島' => 'jc_73',
  '高知' => 'jc_74',
  '松山' => 'jc_75',
  '小倉' => 'jc_81',
  '久留米' => 'jc_83',
  '武雄' => 'jc_84',
  '佐世保' => 'jc_85',
  '別府' => 'jc_86',
  '熊本' => 'jc_87'
);

$headerNames = [
  'place_code',
  'race_no',
  'start_time',
  'entry_count'
];

// $output = shell_exec('rm -r /tmp/phantomjs_cache_auto_regi_os');
// echo "<pre>$output</pre>";

$ref_data = array();

$date_url = 'http://keirin.jp/pc/top#';
$start_date = $today;

$casper = new Casper();
$casper->setOptions(array(
  'ignore-ssl-errors' => 'yes',
  'loadImages' => 'false',
  // 'disk-cache' => 'true',
  // 'disk-cache-path' => '/tmp/phantomjs_cache_auto_regi_os'
));
$casper->setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36');

//
$casper->start($date_url);
// $casper->click('#ctabTomorrow > a');

$casper->run();
$html = $casper->getHtml();
echo $html . PHP_EOL;
$dom = HtmlDomParser::str_get_html($html);
$month_day = $dom->find('#ctabToday > a', 0)->textContent;
// echo '$month_day=>' . $month_day . PHP_EOL;
// exit();
if ($month_day != '今日(' . date('m/d') . ')') {
  echo 'No today match !!!' . PHP_EOL;
  exit();
}
// $month_day = str_replace('今日');
$buttons = $dom->find('#kaisaiInfoTable > tbody tr td:nth-child(4) button');

#kaisaiInfoTable > tbody > tr.dokanto_color > td:nth-child(4) > button
// print_r(count($buttons));
// exit();
$data = array();

for ($i = 1; $i < count($buttons) + 1; $i++) {
  $casper->start($date_url);
  $casper->click('#ctabToday > a');
  $casper->wait(1000);
  $casper->click('#kaisaiInfoTable > tbody tr:nth-child(' . $i . ') td:nth-child(4) button');
  // $casper->click($button);
  $casper->wait(1000);
  $casper->run();
  $html = $casper->getHtml();
  $dom = HtmlDomParser::str_get_html($html);
  // $html = $dom->find('#sldivSyusouList');
  // echo '#sldivSyusouList' . $html . PHP_EOL;
  // $dom = HtmlDomParser::str_get_html($html);
  $place_name = str_replace(' ', '', $dom->findOne('#hhLblJo')->textContent);
  $place_name = str_replace('競輪場', '', $dom->findOne('#hhLblJo')->textContent);
  $place_code = $place_code_arr[$place_name];
  // print_r($place);
  // exit();
  echo '$place_code->' . $place_code . PHP_EOL;
  // exit();
  $tables = $dom->find('#sldivSyusouList > table');
  // print_r($tables);
  // exit();

  foreach ($tables as $key => $table) {
    $race_no = str_replace('R', '', $table->findOne('table:nth-child(1) > tbody > tr > td > div')->textContent);
    echo '$race_no->' . $race_no . PHP_EOL;
    // #sldivSyusouList > table.sltbl_02.slma-0 > tbody > tr > td > table:nth-child(2) > tbody > tr > td.slva-top > table > tbody > tr > td:nth-child(1) > div:nth-child(2) > span
    $start_time = $table->find('tbody > tr > td > table:nth-child(2) > tbody > tr > td > table > tbody > tr > td:nth-child(1) > div:nth-child(2) > span')[0]->textContent;
    $start_time = $today . ' ' . $start_time;
    #sldivSyusouList > table.sltbl_02.slma-0 > tbody > tr > td > table:nth-child(2) > tbody > tr > td.slva-top > table > tbody > tr > td:nth-child(1) > div:nth-child(2) > span
    echo '$start_time->' . $start_time . PHP_EOL;
    $entry_count = count($table->find('tbody > tr > td > table:nth-child(2) > tbody > tr > td > table > tbody > tr > td:nth-child(2) > table td')) / 9;
    print_r($entry_count);
    echo '$entry_count->' . $entry_count . PHP_EOL;
    $data[] = array('place_code' => $place_code, 'place_name' => $place_name, 'race_no' => $race_no, 'start_time' => $start_time, 'entry_count' => $entry_count);
    // exit();
  }


  // exit();
}
print_r($data);
// exit();

$association_code = 'jcycle';
$race_length = 0;
//echo json_encode($data, JSON_UNESCAPED_UNICODE);
foreach ($data as $i => $r) {
  $sql = "INSERT INTO `race` (`place_id`, `place_name`, `association_code`, `place_code`, `race_no`, `start_date`, `start_time`, `race_length`, `entry_count`) SELECT (select `id` from `place` where `place_code`= '" . $r['place_code'] . "'), '" . $r['place_name'] . "', '" . $association_code . "', '" . $r['place_code'] . "'," . $r['race_no'] . ",'" . $start_date . "','" . $r['start_time'] . "'," . $race_length . "," . $r['entry_count'] . " FROM DUAL WHERE NOT EXISTS (SELECT * FROM `race` WHERE `place_id`= (select `id` from `place` where `place_code`= '" . $r['place_code'] . "') and `place_name` = '" . $r['place_name'] . "' and `place_code` = '" . $r['place_code'] . "' and `start_date` = '" . $start_date . "' and `race_no` = " . $r['race_no'] . ")";
  echo $sql;
  $ok = exec_query($database, $sql);
  echo $i . ':' . $ok . PHP_EOL;
}
