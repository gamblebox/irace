<?php

?>
<?php
/*
[클릭해서 배당판을 열경우]
<a id="alink" href="http://to44.net/movie.php?place_type=H" onclick="window.location.href=this.href;">배당판</a>


[원판 안에 박을 경우]
<iframe src="http://to44.net/movie.php" width="600" height="400" border="0" scrolling="no"/>


[새창으로 띄울경우]
<a href="http://www.to44.net/movie.php" target="popup" onclick="window.open('', 'popup','width=800,height=400').focus();">Open Popup window</a>

<iframe src="http://210.140.161.59/baedang/real_jung.asp" width="500" height="350" frameborder="0" style="background:#202021;" scrolling="no"></iframe>

<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0" width="500" height="330" id="rbcc_del" align="middle">
    <param name="allowScriptAccess" value="sameDomain" />
    <param name=wmode value=transparent>
	<param name="allowFullScreen" value="false" />
	<param name="movie" value="http://210.140.172.213/real/real_cycle/cycle.swf?g_ip=210.140.172.213&g_port=9004" /><param name="quality" value="high" /><param name="bgcolor" value="#ada252" />	<embed src="http://210.140.172.213/real/real_cycle/cycle.swf?g_ip=210.140.172.213&g_port=9004" quality="high" bgcolor="#ada252" width="500" height="350" name="rbcc_del" align="middle" allowScriptAccess="sameDomain" allowFullScreen="false" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
</object>


목각 및 음성만 필요할경우
http://www.to44.net/movie.php

목각 및 음성 + 배당일경우
http://www.to44.net/cast.php



45.35.9.214
경마
IP/Rate.Ajax_.asp?type=1 복승
IP/Rate.Ajax_.asp?type=2 복연
IP/Rate.Ajax_.asp?type=3 쌍승

경정
IP/BRate.Ajax.asp?type=1 쌍승
IP/BRate.Ajax.asp?type=2 복승
IP/BRate.Ajax.asp?type=3 삼복

경륜
IP/CRate.Ajax.asp?type=1 쌍승
IP/CRate.Ajax.asp?type=2 복승
IP/CRate.Ajax.asp?type=3 삼복
*/
//$url = 'http://bag116.com/gameLogCR3.asp?gType=C&Type=2';
//$url = '45.35.9.214/Rate.Ajax_.asp?type=1';

extract($_GET);
if  ($association === '1' ){
	if  ($type === '2' ){
		$info_type = '복연승';
		$url = 'kka440.com/php/korea_baedang_s_cycle.php?type=3';
	}
	else if  ($type === '3' ){
		$info_type = '쌍승';
		$url = 'kka440.com/php/korea_baedang_s_cycle.php?type=1';
	}
	else {
		$info_type = '복승';
		$url = 'kka440.com/php/korea_baedang_s_cycle.php?type=2';
	}
}
else if  ($association === '2'  ){
	if  ($type === '2' ){
		$info_type = '복연승';
		$url = 'kka440.com/php/korea_baedang_s_boat.php?type=3';
	}
	else if  ($type === '3' ){
		$info_type = '쌍승';
		$url = 'kka440.com/php/korea_baedang_s_boat.php?type=1';
	}
	else {
		$info_type = '복승';
		$url = 'kka440.com/php/korea_baedang_s_boat.php?type=2';
	}
}
else {
	if  ($type === '2' ){
		$info_type = '복연승';
		$url = 'kka440.com/php/korea_baedang_s_race.php?type=2';
	}
	else if  ($type === '3' ){
		$info_type = '쌍승';
		$url = 'kka440.com/php/korea_baedang_s_race.php?type=3';
	}
	else {
		$info_type = '복승';
		$url = 'kka440.com/php/korea_baedang_s_race.php?type=1';
	}
}




// curl 리소스를 초기화
$ch = curl_init();

// url을 설정
curl_setopt($ch, CURLOPT_URL, $url);

// 헤더는 제외하고 content 만 받음
curl_setopt($ch, CURLOPT_HEADER, 0);

// 응답 값을 브라우저에 표시하지 말고 값을 리턴
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// 브라우저처럼 보이기 위해 user agent 사용
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0');

//리퍼러
curl_setopt($ch, CURLOPT_REFERER, "http://202.239.49.224/pages/main.php"); 

curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);

$content = curl_exec($ch);

// 리소스 해제를 위해 세션 연결 닫음
curl_close($ch);
$content = explode('<table width="630" class="table-bordered table-condensed table-striped" style="font-family: \'Nanum Gothic\'; font-size: 12px; font-weight: bold;table-layout:fixed" width="100%">', $content);
$content = '<table width="630" class="table-bordered table-condensed table-striped" style="font-family: \'Nanum Gothic\'; font-size: 12px; font-weight: bold;table-layout:fixed" width="100%">' . $content [1];
$content = explode('</body>', $content);
$content = $content[0];


echo $content;

unset($content);
?>


