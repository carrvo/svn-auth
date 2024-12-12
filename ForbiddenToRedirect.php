<?php
$current = getenv('REDIRECT_URL').'?'.getenv('REDIRECT_QUERY_STRING');
$redirect = str_replace('/public', '/'.$_GET['new'], $current);
header('Location: '.$redirect);
?>
