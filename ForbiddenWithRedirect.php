<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html>
<head>
    <title>403 Forbidden</title>
</head>
<body>
    <h1>Forbidden</h1>
    <p>You don't have permission to access this resource.</p>
    <?php
    $current = getenv('REDIRECT_URL').'?'.getenv('REDIRECT_QUERY_STRING');
    $redirect = str_replace('/public', '/'.$_GET['new'], $current);
    echo '<a href="'.$redirect.'">Access this resource here.</a>';
    ?>
</body>
</html>
