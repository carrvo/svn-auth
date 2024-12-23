#!/usr/bin/php
<?php
// setup
$sub_command = 'svn propget';
$read_property = 'authz:read';
$anonymous = 'anonymous';

$context = json_decode(getenv('CONTEXT'), true);
$SVNParentPath = $context['SVNParentPath'];
$SVNLocationPath = $context['SVNLocationPath'];
$location_path = $_SERVER['URI'];
$user = trim(fgets(STDIN));
$groups = trim(fgets(STDIN));
$group_array = explode(' ', $groups);

$anonymous_override = false;
if (strcmp($group_array[0], $anonymous) === 0) {
	fwrite(STDERR, "[authnz_external:svn-auth:info] checking for anonymous access\n");
	$anonymous_override = true;
}	
elseif (strcmp($group_array[0], 'svn-authz') !== 0) {
	fwrite(STDERR, "[authnz_external:svn-auth:info] $groups is not supported\n");
	exit(3);
}
$svn_property = $group_array[1];

$svn_path = preg_replace('/'.preg_quote($SVNLocationPath, '/').'/', $SVNParentPath, $location_path);
// for folder reads it queries each item with !svn/ver/{revision}/ inserted into the path
$svn_path = preg_replace('/!svn\/ver\/\d+\//', '', $svn_path);

// Permissions
$cmd = "$sub_command $svn_property file://$svn_path";
$output=null;
$retval=null;
$cmd_ran = exec($cmd, $output, $retval);

// Results
if ($cmd_ran === false) {
    fwrite(STDERR, "[authnz_external:svn-auth:info] SVN failed to run\n");
    exit(1);
}
if ($retval != 0) {
    fwrite(STDERR, "[authnz_external:svn-auth:info] SVN returned with status $retval\n");
    exit($retval);
}

foreach ($output as $authz) {
	// Grant the user their permissions BEFORE checking anonymous
	// (though this is actually ordered by the property).
	// When anonymous override, we need to skip specific user checks
	if ($anonymous_override === false) {
		if (strcmp($user, $authz) === 0) {
	        	exit(0);
		}
	}
	if (strcmp($anonymous, $authz) === 0){
		fwrite(STDERR, "[authnz_external:svn-auth:info] $user is granted anonymous access!\n");
		exit(0);
	}
}
fwrite(STDERR, "[authnz_external:svn-auth:info] $user is not authorized!\n");
exit(2);
?>

