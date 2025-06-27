<?php

?>
	<?php
$url = 'https://keiba.rakuten.co.jp/odds/umafuku/RACEID/201603152015200212#headline';
if ($_GET['url']){
	$url = $_GET['url'];
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

$content = curl_exec($ch);

// 리소스 해제를 위해 세션 연결 닫음
curl_close($ch);

$result = substr($content, $s = strpos($content, '<div id="wakuUmaBanJun" style="display: block; ">'), strrpos($content, '</div><!-- wakuUmaBanJun -->') - $s); // 라쿠텐 배당판


echo '<div id="oddsField"><div class="rateField">'; //라쿠덴 div 아이디 첨가

echo $result;

echo '</div></div>';
?>
