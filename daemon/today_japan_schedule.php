<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
$place_code = array(
	3 => "obihiro",
	10 => "morioka",
	11 => "mizusawa",
	18 => "urawa",
	19 => "funabashi",
	20 => "ooi",
	21 => "kawasaki",
	22 => "kanazawa",
	23 => "kasamatsu",
	24 => "nagoya",
	27 => "sonoda",
	31 => "kouchi",
	32 => "saga",
	36 => "monbetsu"
);

function get_race_data_to_json($url)
{
	$data = array();
	$place_own_id = substr($url, - 10, 2);
	$rk_race_code = substr($url, - 8, 6);
	$dom = new DomDocument();
	$dom->loadHtmlFile($url);
	$html = strtolower(substr($dom->saveHTML(), - 6, 4));
	if ($html !== 'html') {
		return false;
	}
	$xpath = new DomXPath($dom);
	$headerNames = [
		'own_id',
		'rk_race_code',
		'race_no',
		'start_time',
		'length',
		'entry_count'
	];
	$tbody = $xpath->query('//tbody[@class="raceState"]');
	foreach ($xpath->query('tr', $tbody[0]) as $index => $node) {
		$rowData = array();
		$rowData[] = (int)$place_own_id;
		$rowData[] = $rk_race_code;
		$rowData[] = '' . ($index + 1);
		foreach ($xpath->query('td', $node) as $cell) {
			$rowData[] = trim($cell->nodeValue);
		}
		$rowData = array_slice($rowData, 0, 7);
		$rowData[6] = substr($rowData[6], 0, - 3);
		unset($rowData[4]);
		$data[] = array_combine($headerNames, $rowData);
	}
	return $data;
}

$get_date = date('Ymd', strtotime(date(Ymd) . '+' . '0' . ' days')); // 1일 후                                                                     
$date_url = 'http://keiba.rakuten.co.jp/race_card/list/RACEID/' . $get_date . '0000000000';

$dom = new DomDocument();
$dom->loadHtmlFile($date_url);
$xpath = new DomXPath($dom);
$alinks = $xpath->query('//*[@id="raceMenu"]//a/@href');

foreach ($alinks as $alink) {
    $url = 'http://keiba.rakuten.co.jp/' . $alink->value;
    $url = $alink->value;
    $data = get_race_data_to_json($url);
    echo $place_code[$data[0]['own_id']] . '|' . date('H:i', strtotime($data[0]['start_time'] . '- 30 minute')) . '|' . date('H:i', strtotime($data[count($data)-1]['start_time'] . '+ 30 minute')) . PHP_EOL;
}
