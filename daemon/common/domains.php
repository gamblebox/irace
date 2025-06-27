<?php
$log_filename = __DIR__ . '/../../domain_error.log';
/*$REMOTE_ADDR = array(
	'103.31.15.136',
	'103.31.15.243',
	'104.238.160.138'
	);*/
function push_log($log_str)
{
  global $log_filename;
  $now = date('Y-m-d H:i:s');
  $filep = fopen($log_filename, "a");
  if (!$filep) {
    die("can't open log file : " . $log_filename);
  }
  fputs($filep, "{$now} : {$log_str}" . 'REMOTE_ADDR :' . $_SERVER['REMOTE_ADDR'] . ', User ID :' . $_SESSION['user_id'] . ', HTTP_HOST :' . $_SERVER['HTTP_HOST'] . ', HTTP_REFERER :' . $_SERVER['HTTP_REFERER'] . ', SCRIPT_FILENAME :' . $_SERVER['SCRIPT_FILENAME'] . ', data :' . $_POST['doc'] . '-' . $_POST['mode'] . ', HTTP_USER_AGENT :' . $_SERVER['HTTP_USER_AGENT'] . PHP_EOL);
  fclose($filep);
}

function error_403()
{
  header('HTTP/1.0 403 Forbidden');
  echo '<html>
<head><title>403 Forbidden</title></head>
<body bgcolor="white">
<center><h1>403 Forbidden</h1></center>
<hr><center>nginx</center>
</body>
</html>
<!-- a padding to disable MSIE and Chrome friendly error page -->
<!-- a padding to disable MSIE and Chrome friendly error page -->
<!-- a padding to disable MSIE and Chrome friendly error page -->
<!-- a padding to disable MSIE and Chrome friendly error page -->
<!-- a padding to disable MSIE and Chrome friendly error page -->
<!-- a padding to disable MSIE and Chrome friendly error page -->';
}

/*if(!in_array($_SERVER[ 'REMOTE_ADDR' ], $REMOTE_ADDR) ){
	error_403();
	push_log( 'REMOTE - ');
	exit();	
}*/

$domains = array(
  '777-free.com',
  '777-gr.com',
  'qr-135.com',
  'ur-99.com',
  'ami-789.com',
  'ur-77.com',
  'pom-77.com',
  'pow-33.com',
  'mona-on.com',
  'naco-777.com',
  'coco-777.com',
  'mona-20.com',
  'mona-mo.com',
  'mona-in.com',
  'mona-sm.com',
  'mona-sb.com',
  'naco-in.com',
  'royal-7799.com',
  'ry8282.com',
  'sat-7979.com',
  'isports.isportsbox.com',
  'ama1.isportsbox.com',
  'kra-37.com',
  'ur-22.com',
  'ur-88.com',
  'soso77.com',
  'kra-88.com',
  'xn--om2b27qtydba177g.com',
  '케이레이스.com',
  'kkk-111.com',
  'haha5050.com',
  'irace.zizi.best',
  'demo.isportsbox.net',
  'b1.isportsbox.com',
  'demo-sports.isportsbox.net',
  'ur-147.com',
  'ur-357.com',
  'no2323.com',
  'han82.com',
  'kapa6070.com',
  'bbss365.com',
  'jjgg99.com',
  'soso80.com',
  'soso90.com',
  'kttk123.com',
  'kt5677.com',
  'ur-225.com',
  'ur-2212.com',
  'cfcf20.com',
  'kokorace77.com',
  'nknk20.com',
  'cscs22.com',
  'apap99999.com',
  '77dp777.com',
  'pom77.com',
  'hshs11.com',
  'kbkb11.com',
  'dks333.com',
  'psps22.com',
  'oopp00.com',
  'dadh77.com',
  'ir.zizi.best',
  'kr.zizi.best',
  'ttkk3399.com',
  'st-123.com',
  'kkra9999.com',
  'ur-7979.com',
  'ur-211.com',
  'ttoo4949.com',
  'yyoo4949.com',
  'kkdd2200.com',
  'paw33.com',
  'mal77.com',
  'ur-8282.com',
  'k4.krace.fun',
  'tito369.com',
  'nana6161.com',
  'krace.loc',
  'dfg600.com',
  'dsa-1004.com',
  'awaw6969.com',
  'masa88.com',
  'kwkw7878.com',
  'pda11.com',
  'pma22.com',
  'rkskekfkak.com',
  'ur-1004.com',
  'hihi22.com',
  'malmalgogo.com',
  'bsbs99.com',
  'hror793.com',
  'sam7878.com',
  'ur-159.com',
  'ur-365.com',
  'ygh111.com',
  'rhgid963.com',
  'tneh478.com',
  'jjrr991.com',
  'tmdqn258.com',
  'gkak789.com',
  'may732.com',
  'su3737.com',
  'su6464.com',
  'ur-137.com',
  'spsp622.com',
  'rhrh2244.com',
  'pgh111.com',
  'fgh111.com',
  'adad111.com',
  'rtrt8956.com',
  'ubby4253.com',
  'podud2389.com',
  'tkwk852.com',
  'agh111.com',
  'ngh111.com',
  'auau8852.com',
  'cbcb7441.com',
  'rkwk4885.com',
  'qhtjd8633.com',
  'tjtj5541.com',
  'alla7979.com',
  'hhkk1213.com',
  'ur-777.com',
  'urace122.com',
  'urace202.com',
  'kood2010.com',
  'racek2002.com',
  'adzc1227.com',
  'mcs1225.com',
  'pow979.com',
  'pow1626.com',
  'eqs-456.com',
  'eqs-678.com',
  'wpwn258.com',
  'qntks159.com',
  'rhkcjs357.com',
  'alal4656.com',
  'kno01.com',
  'krace1001.com',
  'effk11.com',
  'qkek3020.com',
  'pit5490.com',
  'uni131.com',
  'pwo1625.com',
  'gns993.com',
  '393mkg.com',
  '619kom.com',
  'kabb744.com',
  'mwt7410.com',
  'ang315.com',
  'vivi5151.com',
  'kfs52.com',
  'done26.com',
  'tygh-884.com',
  'vip-race.biz',
  'foo351.com',
  'hoo853.com',
  'tls694.com',
  'deo6541.com',
  'vke122.com',
  'jun546.com',
  'skk333.com',
  'okc777.com',
  'okk666.com',
  'ktk999.com',
  'aks88.net',
  'ghk88.net',
  'lys1199.com',
  'wkd2165.com',
  'irace.club',
  'irace.krace.fun',
  'irace.space',
  'sun.irace.space',
  'moon.irace.space',
  'arace.club',
  'krace.club',
  'eqs-777.com',
  'eqs-789.com',
  'eqs-426.com',
  'eqs-135.com',
  'eqs-156.com',
  'ggr852.com',
  'gmr1122.com',
  'zb6452.com',
  'ghk3311.com',
  'xm1634.com',
  'jkr698.com',
  'hkr564.com',
  'wn7522.com',
  'rh3357.com',
  'rhkd335.com',
  'whd6080.com',
  'pouo32.com',
  'audm564.com',
  'rkwk212.com',
  'tkd231.com',
  'cro77.com',
  'wp615.com',
  'wp493.com',
  'rkwk654.com',
  'rkwk655.com',
  'kce88.com',
  'rkwk11.com',
  'rja222.com',
  'cro524.com',
  'rkwk999.com',
  'sun.krace.fun',
  'hoh62.com',
  'krace.fun',
  'vps404.com',
  'zpdl77.com',
  'ch.krace.fun',
  'k3.krace.fun',
  'zpdlfpdltm.com',
  'chon.krace.fun',
  'vultr.krace.fun',
  'a2.krace.fun',
  'krace.fun',
  'aaa.com',
  'hkr113.com',
  'hkr114.com',
  'hkr115.com',
  //'hkr202.com',
  'hkr222.com',
  'rud111.com',
  'wjd111.com',
  // 	'hih147.com',
  'hkr606.com',
  'hkr808.com',
  'hkr909.com',
  'jks116.com',
  'jks316.com',
  'opt754.com',
  'krac1.com',
  'ekf222.com',
  'akf82.com',
  'jks216.com',
  'krs112.com',
  'krs222.com',
  'race001.com',
  'race002.com',
  'race003.com',
  'race004.com',
  'sbs7979.net',
  //'jrs111.com',
  // 	'ans159.com',
  'mar367.com',
  'mar555.com',
  'fof555.com',
  'luc456.com',
  'cjd214.com',
  'ekf111.com',
  // 	'fkd333.com',
  'wr78.net',
  'wr21.net'
);

$host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);

// preg_match( '@^(?:http://)?([^/]+)@i', $_SERVER[ 'HTTP_REFERER' ], $matches );
// //preg_match('@^(?:http://)?([^/]+)@i', 'http://www.vultr.krace.fun/php/domains.php', $matches);
// $host = $matches[ 1 ];

if (substr($host, 0, 2) === 'a.' || substr($host, 0, 2) === 'm.' || substr($host, 0, 2) === 'p.') {
  $host = substr($host, 2);
}
if (substr($host, 0, 4) === 'www.') {
  $host = substr($host, 4);
}

//print_r($host) ;REMOTE_ADDRHTTP_HOST
//print_r($_SERVER) ;
if (!in_array($host, $domains)) {
  error_403();
  push_log('Domains - ');
  exit();
}
