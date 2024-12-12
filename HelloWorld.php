<!DOCTYPE html>
<html>
<head>
	<title>Hello World</title>
</head>
<body>
<p>Hello World</p>
<p>
<?php
echo 'env='.getenv('SVNParentPath', true).','.getenv('Method', true);
?>
</p>
<p>env
<?php
print_r($_ENV);
?>
</p>
<p>server
<?php
print_r($_SERVER);
?>
</p>
<p>request
<?php
print_r($_REQUEST);
?>
</p>
</body>
</html>
