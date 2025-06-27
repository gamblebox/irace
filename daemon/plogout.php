<?php
if ( !isset( $_SESSION ) ) {
	session_start();
}
session_destroy();
?>
<meta http-equiv='refresh' content='0;url=/admin/pages/plogin.html'>