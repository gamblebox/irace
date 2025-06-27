<?php
extract($_GET);

$url = urldecode($url);
//$url='https://api.beta.tab.com.au/v1/tab-info-service/racing/dates/2024-02-21/meetings/R/URA/races/1?jurisdiction=NSW';
// create curl resource
$ch = curl_init();

// curl 리소스를 초기화
$ch = curl_init();

// url을 설정
curl_setopt($ch, CURLOPT_URL, $url);

// 헤더는 제외하고 content 만 받음
curl_setopt($ch, CURLOPT_HEADER, 0);

// 응답 값을 브라우저에 표시하지 말고 값을 리턴
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);

// 브라우저처럼 보이기 위해 user agent 사용
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36');

//리퍼러
//curl_setopt($ch, CURLOPT_REFERER, 'http://ntry.com');

//      curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

//curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
//curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$headers = array(
  'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
  'Cache-Control: max-age=0'
);
//$curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$content = curl_exec($ch);

$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);


//print_r($code);
//print_r($error_msg);

echo $content;

// 리소스 해제를 위해 세션 연결 닫음
curl_close($ch);
