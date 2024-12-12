<?php

require __DIR__ . '/vendor/autoload.php';

session_start();
IndieAuth\Client::$clientID = 'http://auth.apache.local/indie/';
IndieAuth\Client::$redirectURL = 'http://auth.apache.local/indie/redirect.php';

list($response, $error) = IndieAuth\Client::complete($_GET);
  // You'll probably want to save the user's URL in the session
$_SESSION['user'] = $response['me'];
$_SESSION['response'] = $response;
$_SESSION['error'] = $error;
header("Location: http://auth.apache.local/indie/svn/testsvn/testfolder/");
die();
?>
<!DOCTYPE html>
<html>
<head>
</head>
<body>
<?php
if($error) {
  echo "<p>Error: ".$error['error']."</p>";
  echo "<p>".$error['error_description']."</p>";
} else {
  // Login succeeded!
  // The library will return the user's profile URL in the property "me"
  // It will also return the full response from the authorization or token endpoint, as well as debug info
	echo "<p>URL: ".$response['me']."</p>";
	echo "<p>user:".$user['me']."</p>";
  if(isset($response['response']['access_token'])) {
    echo "<p>Access Token: ".$response['response']['access_token']."</p>";
    echo "<p>Scope: ".$response['response']['scope']."</p>";
  }

  // The full parsed response from the endpoint will be available as:
  // $response['response']

  // The raw response:
  // $response['raw_response']

  // The HTTP response code:
  // $response['response_code']

}
?>
<a href="http://auth.apache.local/indie/svn/testsvn/testfolder/">testfolder</a>
<?php
echo "<p>session: " . print_r($_SESSION, true) . "</p>";
?>
</body>
</html>
