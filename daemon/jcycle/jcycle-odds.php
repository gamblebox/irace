<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require_once('/srv/irace/daemon/common/pdo-tool.php');
require_once('/srv/irace/daemon/common/config-db.php');

define('MAX_FILE_SIZE', 6000000);
putenv("PHANTOMJS_EXECUTABLE=/usr/local/bin/phantomjs");
require "/srv/irace/vendor/autoload.php";

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
    $ok = exec_query($database, $sql);
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
      $ok = exec_query($database, $sql);
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
    $ok = exec_query($database, $sql);
    echo $ok . PHP_EOL;
  }
  // echo json_encode($data_yun, JSON_UNESCAPED_UNICODE);
  if (count($data_yun)) {
    $race_id_type = $race->id . '_fp';
    $sql = "REPLACE INTO `os_odds` (`race_id_type`,`race_id`, `type`, `data`)  VALUES ('" . $race_id_type . "','" . $race->id . "','확정연승','" . json_encode($data_yun, JSON_UNESCAPED_UNICODE) . "')";
    echo $sql . PHP_EOL;
    $ok = exec_query($database, $sql);
    echo $ok . PHP_EOL;
  }
  if (count($data_t_dan)) {
    $race_id_type = $race->id . '_tw';
    $sql = "REPLACE INTO `os_odds` (`race_id_type`,`race_id`, `type`, `data`)  VALUES ('" . $race_id_type . "','" . $race->id . "','단승','" . json_encode($data_t_dan, JSON_UNESCAPED_UNICODE) . "')";
    echo $sql . PHP_EOL;
    $ok = exec_query($database, $sql);
    echo $ok . PHP_EOL;
  }
  // echo json_encode($data_yun, JSON_UNESCAPED_UNICODE);
  if (count($data_t_yun)) {
    $race_id_type = $race->id . '_tp';
    $sql = "REPLACE INTO `os_odds` (`race_id_type`,`race_id`, `type`, `data`)  VALUES ('" . $race_id_type . "','" . $race->id . "','연승','" . json_encode($data_t_yun, JSON_UNESCAPED_UNICODE) . "')";
    echo $sql . PHP_EOL;
    $ok = exec_query($database, $sql);
    echo $ok . PHP_EOL;
  }
}

function get_race_all_odds($url, $casper, $race, $database)
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

      $result = exec_query_lastId($database, $sql);
      // echo $index . '->' . $lastId . PHP_EOL;
      print_r($result);

      if ($result->id > 0) {
        updateBettingByRaceChange($database, [
          // 'own_id' => $race->own_id,
          'race_id' => $race->id,
          'association_code' => 'jcycle',
          'type' => '출전취소',
          // 'start_date' => $race->start_time,
          'race_no' => $race->race_no,
          'entry_no' => $entry_no,
          // 'memo' => $memo,
          // 'old_start_time' => $race->start_time,
          // 'new_start_time' => $race->start_time,
        ]);
      }
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
    // echo $html . PHP_EOL;

    $dom = HtmlDomParser::str_get_html($html);
    $odds_table = $dom->findOne('#ozz_table > table');
    if (!$odds_table) {
      continue;
    }

    $aOdds = $dom->find('.nonb');
    foreach ($aOdds as $index => $e) {
      // $e->outertext = '<img src="foobar.png">';
      // print_r(($e));
      $odds = (float) $e->textContent;
      if ($odds == 0) {
        continue;
      }
      if (floor($odds) > 0 && floor($odds) < 10) {
        $e->outertext = '<div class="nonb red">' . $odds . '</div>';
      } else if (floor($odds) > 0 && floor($odds) < 100) {
        $e->outertext = '<div class="nonb blue">' . $odds . '</div>';
      }
    }
    $odds_table = $dom->findOne('#ozz_table > table');
    echo $odds_table;

    $race_id_type = $race->id . '_' . $target['type_code'];
    $ktype = $target['ktype'];
    $sql = "REPLACE INTO `jcycle_odds` (`race_id_type`,`race_id`, `type`, `data`)  VALUES ('" . $race_id_type . "','" . $race->id . "','" . $ktype . "','" . $odds_table . "')";
    // echo $sql . PHP_EOL;
    $ok = exec_query_msg($database, $sql);
    echo $race_id_type .  PHP_EOL;
    print_r($ok);
  }

  return;
}

// $date_url = 'http://www.tab.com.au/racing/meetings/tomorrow';
// $date_url = array('http://www.tab.com.au/racing/meetings/today/R', 'http://www.tab.com.au/racing/meetings/today/H', 'http://www.tab.com.au/racing/meetings/today/G', 'http://www.tab.com.au/racing/meetings/tomorrow');
// $date_url = 'http://www.tab.com.au/racing/meetings/today/R';

// $sql = "SELECT * FROM race_info WHERE start_time < date_add(now(), INTERVAL " . $interval . ") and association_code in ('osr','osh','osg') and stat = 'P' order by start_time asc;";
$database = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';port=' . $port . ';charset=utf8mb4', $user, $password, array(
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_EMULATE_PREPARES => false,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
));

$casper = new Casper();
$casper->setOptions(array(
  'ignore-ssl-errors' => 'yes',
  'loadImages' => 'false',
  // 'disk-cache' => 'true',
  // 'disk-cache-path' => '/tmp/',
  // 'disk-cache-path' => '/tmp/phantomjs_cache_os_baedang_' . str_replace(' ', '_', $interval)
));
$casper->setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36');

// while (true) {
$sql = "SELECT r.id, r.association_code, r.place_code, r.start_date, r.start_time, r.race_no, p.e_name as place_name FROM race r left join place p on r.place_id = p.id WHERE start_time < date_add(now(), INTERVAL " . $interval . ") and start_time > date_add(now(), INTERVAL -5 minute) and r.association_code in ('jcycle') order by r.start_time asc;";
// and r.stat = 'P' 
$races = select_query($database, $sql);
print_r($races);

if ($races) {
  foreach ($races as $race) {
    get_race_all_odds($url, $casper, $race, $database);
  }
  sleep(30);
} else {
  sleep(60);
}
// }
