<?php
$log_filename = __DIR__ . '/../../deny.log';

function push_log( $log_str ) {
	global $log_filename;
	$now = date( 'Y-m-d H:i:s' );
	$filep = fopen( $log_filename, "a" );
	if ( !$filep ) {
		die( "can't open log file : " . $log_filename );
	}
	fputs( $filep, "{$now} : {$log_str}" . PHP_EOL );
	fclose( $filep );
}

function error_403() {
	header( 'HTTP/1.0 403 Forbidden' );
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


if ( !preg_match( "/" . $_SERVER[ 'HTTP_HOST' ] . "/i", $_SERVER[ 'HTTP_REFERER' ] ) ) {
	error_403();
	push_log( 'Rejected ' . 'REMOTE_ADDR :' . $_SERVER[ 'REMOTE_ADDR' ] . ', HTTP_HOST :' . $_SERVER[ 'HTTP_HOST' ] . ', HTTP_REFERER :' . $_SERVER[ 'HTTP_REFERER' ] . ', SCRIPT_FILENAME :' . $_SERVER[ 'SCRIPT_FILENAME' ] );
	exit();
}
if ( realpath( $_SERVER[ SCRIPT_FILENAME ] ) == realpath( __FILE__ ) ) {
	error_403();
	push_log( 'Rejected ' . 'REMOTE_ADDR :' . $_SERVER[ 'REMOTE_ADDR' ] . ', HTTP_HOST :' . $_SERVER[ 'HTTP_HOST' ] . ', HTTP_REFERER :' . $_SERVER[ 'HTTP_REFERER' ] . ', SCRIPT_FILENAME :' . $_SERVER[ 'SCRIPT_FILENAME' ] );
	exit();
}
if ( !isset( $_SESSION ) ) {
	session_start();
}

if ( !isset( $_SESSION[ 'club_code' ] )  ) {
	error_403();
	push_log( 'No Session ' . 'REMOTE_ADDR :' . $_SERVER[ 'REMOTE_ADDR' ] . ', HTTP_HOST :' . $_SERVER[ 'HTTP_HOST' ] . ', HTTP_REFERER :' . $_SERVER[ 'HTTP_REFERER' ] . ', SCRIPT_FILENAME :' . $_SERVER[ 'SCRIPT_FILENAME' ] );
	exit();
}
?>