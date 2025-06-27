<?php
//	require_once(__DIR__ . '/../../application/php/deny.php');
$url = 'https://www2.keiba.go.jp/KeibaWeb/TodayRaceInfo/OddsUmLenFuku?k_raceDate=2020%2F03%2F24&k_babaCode=18&k_raceNo=3&odds_flg=0&sortFlg=2';
if ( $_GET[ 'url' ] ) {
    $url = $_GET[ 'url' ];
}
echo $url;
/*
if ($_GET['raceno']){
    $raceno = $_GET['raceno'];
}
if(strpos($url, 'umatan') !== false){
    $type = 'ssang';
    $ktype = '쌍승';
}
else if(strpos($url, 'wide') !== false){
    $type = 'bokyun';
    $ktype = '복연승';
}
else {
    $type = 'bok';
    $ktype = '복승';
}
if ($_GET['place']){
    $place = urldecode($_GET['place']);
}
*/
// curl 리소스를 초기화
$ch = curl_init();
// url을 설정
curl_setopt( $ch, CURLOPT_URL, $url );
// 헤더는 제외하고 content 만 받음
curl_setopt( $ch, CURLOPT_HEADER, 0 );
// 응답 값을 브라우저에 표시하지 말고 값을 리턴
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
// 브라우저처럼 보이기 위해 user agent 사용
curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0' );
//리퍼러
curl_setopt( $ch, CURLOPT_REFERER, $url );
curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 2 );
curl_setopt( $ch, CURLOPT_TIMEOUT, 3 );

//    $content = file_get_contents($url);
//	echo $content;

if ( ( $content = curl_exec( $ch ) ) === false ) {
    //echo 'Curl error: ' . curl_error($ch);
    echo '<div id="odds-board" style=" display:none; ">error</div>';
} else {
    if ( strpos( $url, 'OddsUmLenFuku' ) ) {
        $content = substr( $content, $s = strpos( $content, '<ul class="odd_ranking">' ) );
        $content = substr( $content, 0, strpos( $content, '</ul>' ) + 5 );
    } else if ( strpos( $url, 'OddsUmLenTan' ) ) {
        $content = substr( $content, $s = strpos( $content, '<ul class="odd_ranking">' ) );
        $content = substr( $content, 0, strpos( $content, '</ul>' ) + 5 );

    } else {
        $content = substr( $content, $s = strpos( $content, '<ul class="odd_ranking">' ) );
        $content = substr( $content, 0, strpos( $content, '</ul>' ) + 5 );
    }


    //echo '<div id="odds-board" style=" display:none; "><div id="oddsField"><div class="rateField">'; //라쿠덴 div 아이디 첨가
    echo '<div id="odds-board" style="display:block;">'; //라쿠덴 div 아이디 첨가
    echo $content;
    echo '</div>';
}
// 리소스 해제를 위해 세션 연결 닫음
curl_close( $ch );
unset( $content );
?>