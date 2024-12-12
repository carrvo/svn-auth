#!/usr/bin/php
<?php
// setup
$log_path = '/opt/experimentation/svn-auth/print.log';
$sub_command = 'svn propget';
$read_property = 'authz:read';
$anonymous = 'anonymous';

$SVNParentPath = $argv[1];
$SVNLocationPath = $argv[2];
$location_path = $_SERVER['URI'];
$user = trim(fgets(STDIN));
$groups = trim(fgets(STDIN));
$group_array = explode(' ', $groups);
#file_put_contents($log_path, print_r($group_array, true), FILE_APPEND);

if (strcmp($group_array[0], 'svn-authz') !== 0) {
	fwrite(STDERR, "[authnz_external:svn-auth:info] $groups is not supported\n");
	exit(-3);
}
$svn_property = $group_array[1];
//if (isset($svn_property) === false) {
//	$svn_property = $read_property;
//}

$svn_path = preg_replace('/'.preg_quote($SVNLocationPath, '/').'/', $SVNParentPath, $location_path);
#file_put_contents($log_path, "$SVNLocationPath replaced with $SVNParentPath in $location_path is $svn_path\n", FILE_APPEND);
// for folder reads it queries each item with !svn/ver/{revision}/ inserted into the path
$svn_path = preg_replace('/!svn\/ver\/\d+\//', '', $svn_path);
#file_put_contents($log_path, "replaced is $svn_path\n", FILE_APPEND);

// sanitize
// DOES NOT WORK because realpath requires it to exist on the filesystem path
// NOT NEEDED because Apache will resolve the path already (and remove special path characters)
//if (strcmp(realpath($svn_path), $svn_path) !== 0) {
//	fwrite(STDERR, "$svn_path has special path characters\n");
//	exit(-4);
//}

// example
$svn_dir = '/opt/experimentation/svn-auth/';
$svn_repo = 'testsvn';
$svn_path1 = 'testfolder/testfile.txt';
$svn_path2 = 'testfolder/testing/';
$svn_path3 = 'testfolder/testing/file.txt';
$svn_path4 = 'testfolder/non-existant.txt';
#$svn_path = $_SERVER['URI'];

// troubleshooting
#file_put_contents($log_path, "env".print_r($_ENV, true)."server".print_r($_SERVER, true)."request".print_r($_REQUEST, true)."get".print_r($_GET, true)."\n", FILE_APPEND);

if (strcmp($location_path, '/external/helloworld') === 0) {
	exit(0);
}
#$svn_path = "$svn_dir$svn_repo/$svn_path1";

// Permissions
$cmd = "$sub_command $svn_property file://$svn_path";
#file_put_contents($log_path, $cmd."\n", FILE_APPEND);
$output=null;
$retval=null;
$cmd_ran = exec($cmd, $output, $retval);

// Results
if ($cmd_ran === false) {
    fwrite(STDERR, "[authnz_external:svn-auth:info] SVN failed to run\n");
    exit(-1);
}
if ($retval != 0) {
    fwrite(STDERR, "[authnz_external:svn-auth:info] SVN returned with status $retval\n");
    exit($retval);
}

foreach ($output as $authz) {
	if (strcmp($user, $authz) === 0) {
	        exit(0);
	}
	if (strcmp($anonymous, $authz) === 0){
		fwrite(STDERR, "[authnz_external:svn-auth:info] $user is granted anonymous access!\n");
		exit(0);
	}
}
fwrite(STDERR, "[authnz_external:svn-auth:info] $user is not authorized!\n");
exit(-2);
?>

