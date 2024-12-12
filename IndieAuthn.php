#!/usr/bin/php
<?php

session_start();
exit(0);
$log_path = '/opt/experimentation/svn-auth/print.log';
if (isset($_SESSION['user'])) {
	file_put_contents($log_path, 'user='.$_SESSION['user']."\n", FILE_APPEND);
	exit(0);
}
file_put_contents($log_path, print_r($_SESSION, true)."\n", FILE_APPEND);
exit(1);

?>
